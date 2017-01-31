<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/1/31
 * Time: 下午1:41
 */

namespace Minimalism\Async\Core;


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