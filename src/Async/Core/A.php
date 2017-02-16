<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/16
 * Time: 上午3:00
 */
namespace Minimalism\A;

function A($task, callable $continuation = null, \stdClass $ctx = null)
{
    if (is_callable($task)) {
        $task = $task();
    }
    assert($task instanceof \Generator);
    (new AsyncTask($task, $ctx ?: new \stdClass))->start($continuation);
}

function call_cc(callable $fun)
{
    return new CallCC($fun);
}

interface IAsync
{
    public function start(callable $continuation);
}

class CancelTaskException extends \Exception { }


final class AsyncTask implements IAsync
{
    private $generator;
    public $continuation;
    public $context;

    public function __construct(\Generator $generator, \stdClass $context = null)
    {
        $this->generator = new Generator($generator);
        $this->context = $context ?: new \stdClass;
    }

    public function start(callable $continuation = null)
    {
        $this->continuation = $continuation;
        $this->next();
    }

    public function next($result = null, \Exception $ex = null)
    {
        if ($ex instanceof CancelTaskException || !$this->generator->valid()) {
            goto continuation;
        }

        try {
            if ($ex) {
                $value = $this->generator->throwex($ex);
            } else {
                $value = $this->generator->send($result);
            }

            if ($this->generator->valid()) {

                if ($value instanceof Syscall) {
                    $value = $value($this);
                }

                if ($value instanceof \Generator) {
                    $value = new self($value, $this->context);
                }

                if ($value instanceof IAsync) {
                    $value->start([$this, "next"]);
                } else {
                    $this->next($value, null);
                }
            } else {

                continuation:
                if ($continuation = $this->continuation) {
                    $continuation($result, $ex);
                }
            }
        } catch (\Exception $ex) {
            $this->next(null, $ex);
        }
    }
}

class Generator
{
    private $g;
    private $isfirst = true;

    public function __construct(\Generator $g)
    {
        $this->g = $g;
    }

    public function valid()
    {
        return $this->g->valid();
    }

    public function send($value = null)
    {
        if ($this->isfirst) {
            $this->isfirst = false;
            return $this->g->current();
        } else {
            return $this->g->send($value);
        }
    }

    // throw: keywords can be used as name in php7 only
    public function throwex(\Exception $ex)
    {
        return $this->g->throw($ex);
    }
}

class CallCC implements IAsync
{
    public $fun;

    public function __construct(callable $fun)
    {
        $this->fun = $fun;
    }

    public function start(callable $continuation)
    {
        $fun = $this->fun;
        $fun($continuation);
    }
}

class Syscall
{
    private $callback = null;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function __invoke(AsyncTask $task)
    {
        $cb = $this->callback;
        return $cb($task);
    }
}

//-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=

function arg2k($k, $n)
{
    return function() use($n, $k) {
        return $k(func_get_arg($n));
    };
}

function await_sleep($ms)
{
    return call_cc(function($k) use($ms) {
        return swoole_timer_after($ms, $k);
    });
}

function await_dns_lookup($host)
{
    return call_cc(function($k) use($host) {
        swoole_async_dns_lookup($host, arg2k($k, 1));
    });
}

class HttpClient extends \swoole_http_client
{
    public function awaitGet($uri)
    {
        return call_cc(function($k) use($uri) {
            return $this->get($uri, $k);
        });
    }

    public function awaitPost($uri, $post)
    {
        return call_cc(function($k) use($uri, $post) {
            return $this->post($uri, $post, $k);
        });
    }

    public function awaitExecute($uri)
    {
        return call_cc(function($k) use($uri) {
            return $this->execute($uri, $k);
        });
    }
}



A(function() {
    $ip = (yield await_dns_lookup("www.baidu.com"));
    echo $ip, "\n";

    $cli = new HttpClient($ip, 80);
    $cli = (yield $cli->awaitGet("/"));
    echo $cli->body, "\n";

    yield await_sleep(1000);
    echo "sleep 1000\n";
});
