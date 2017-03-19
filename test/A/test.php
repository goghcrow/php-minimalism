<?php

interface Async
{
    public function begin(callable $continuation);
}

class Trampoline
{
    public $fn;
    public function __construct(callable $fn)
    {
        $this->fn = $fn;
    }
    public function __invoke($cc)
    {
        $fn = $this->fn;
        $fn($cc);
    }
}

//function trampoline(callable $f)
//{
//    $fn = $f;
//    while ($fn instanceof Trampoline) {
//        $fn = $fn();
//    }
//    return $fn;
//}

function trampoline(callable $f)
{
    if ($f instanceof Trampoline) {
        $f("trampoline");
    } else {
        return $f;
    }
}

final class AsyncTask implements Async
{
    private $isfirst = true;

    public $parent;
    public $generator;
    public $continuation;

    /**
     * AsyncTask constructor.
     * @param \Generator $generator
     * @param AsyncTask|null $parent
     */
    public function __construct(\Generator $generator, AsyncTask $parent = null)
    {
        $this->generator = $generator;
        $this->parent = $parent;
    }

    public function begin(callable $continuation = null, $result = null, $ex = null)
    {
        if ($continuation) {
            $this->continuation = $continuation;
        }

        try {
            if ($ex) {
                $value = $this->generator->throw($ex);
            } else {
                if ($this->isfirst) {
                    $this->isfirst = false;
                    $value = $this->generator->current();
                } else {
                    $value = $this->generator->send($result);
                }
            }

            if ($this->generator->valid()) {
                if ($value instanceof \Generator) {
                    $value = new self($value, $this);
                }

                if ($value instanceof Async) {
                    return new Trampoline(function($cc) use($value) {
                        return $value->begin([$this, "begin"]);
                    });
                } else {
                    return new Trampoline(function() use($continuation, $value) {
                        return $this->begin($continuation, $value, null);
                    });
                }
            } else {
                if ($continuation = $this->continuation) {
                    $continuation($result, null);
                }
            }
        } catch (\Exception $ex) {
            if ($this->generator->valid()) {
                return new Trampoline(function() use($continuation, $ex) {
                    return $this->begin($continuation, null, $ex);
                });
            } else {
                if ($continuation = $this->continuation) {
                    $continuation(null, $ex);
                }
            }
        }
    }
}


class Sleep implements Async
{
    public function begin(callable $cc)
    {
        swoole_timer_after(1000, function() use($cc) {
            $cc();
        });
        return new Trampoline(function() {});
    }
}
function gen()
{
    while (true) {
        echo 1;
        yield new Sleep();
    }
}

$task = new AsyncTask(gen());
$next = trampoline($task->begin());
var_dump($next);
//$next();