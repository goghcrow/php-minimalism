<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/4/4
 * Time: 下午11:31
 */

namespace Minimalism\A\Core;


class Gen
{
    public $isfirst = true;
    public $generator;

    public function __construct(\Generator $generator)
    {
        $this->generator = $generator;
    }

    public function valid()
    {
        return $this->generator->valid();
    }

    public function send($value = null)
    {
        if ($this->isfirst) {
            $this->isfirst = false;
            return $this->generator->current();
        } else {
            return $this->generator->send($value);
        }
    }

    public function throw_(\Exception $ex)
    {
        return $this->generator->throw($ex);
    }

    public static function isGenFun(callable $fn)
    {
        $closure = Closure::fromCallable($fn);
        $ref = new \ReflectionFunction($closure);
        return $ref->isGenerator();
    }

    /**
     * @param $task
     * @param array $args
     * @return
     */
    public static function from($task, ...$args)
    {
        if ($task instanceof \Generator) {
            return $task;
        }

        if (is_callable($task)) {
            // 1. 这里可以反射判断 callable isGeneratorFun
            /*
            if (self::isGenFun($task)) {
                $gen = $task(...$args);
            } else {
                $gen = function() use($task, $args) { yield $task(...$args); };
            }
            */
            // 2. 或者直接嵌套一层generator
            $gen = function() use($task, $args) { yield $task(...$args); };
        } else {
            $gen = function() use($task) { yield $task; };
        }
        return $gen();
    }
}