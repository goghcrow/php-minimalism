<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/25
 * Time: 下午9:07
 */

namespace Minimalism\Benchmark;


class Counter
{
    private $key;

    public function __construct()
    {
        $this->key = "__counter__:" . microtime(true);
        $this->reset();
    }

    public function reset()
    {
        apcu_store($this->key, 0);
    }

    public function incr($by = 1)
    {
        return apcu_inc($this->key, $by);
    }

    public function decr($by = 1)
    {
        return apcu_dec($this->key, $by);
    }

    public function get()
    {
        return apcu_fetch($this->key);
    }

    public function __destruct()
    {
        apcu_delete($this->key);
    }
}