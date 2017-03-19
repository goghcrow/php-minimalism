<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/3/19
 * Time: 下午8:48
 */

class TOC
{
    public $fn;
    public $active;
    public $accumulated;

    public function __construct(\Closure $fn)
    {
        $this->fn = $fn->bindTo($this, static::class);
        $this->accumulated = new \SplQueue();
        $this->active = false;
    }

    public function __invoke()
    {
        // 第二次进入时，仅仅只push参数，然后return
        $this->accumulated->enqueue(func_get_args());
        if ($this->active) {
            return null;
        }

        $this->active = true;

        $fn = $this->fn;
        $val = null;
        while (!$this->accumulated->isEmpty()) {
            $args = $this->accumulated->dequeue();
            $val = $fn(...$args);
        }

        $this->active = false;
        return $val;
    }
}


function sum($n, $m = 0)
{
    if ($n === 0) {
        return $m;
    } else {
        return sum($n - 1, $m + $n);
    }
}

//echo sum(1000);exit;


$sum = new TOC(function($n, $m = 0) {
    if ($n === 0) {
        return $m;
    } else {
        return $this($n - 1, $m + $n);
    }
});

echo $sum(1000);exit;