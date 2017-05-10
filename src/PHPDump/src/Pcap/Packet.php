<?php

namespace Minimalism\PHPDump\Pcap;


abstract class Packet
{
    private static $filters = [];
    private static $terminators = [];

    public static function registerBefore($filter)
    {
        assert(is_callable($filter));
        if (!isset(self::$filters[static::class])) {
            self::$filters[static::class] = [];
        }
        self::$filters[static::class][] =  $filter;
    }

    public static function registerAfter($terminator)
    {
        assert(is_callable($terminator));
        if (!isset(self::$terminators[static::class])) {
            self::$terminators[static::class] = [];
        }
        self::$terminators[static::class][] = $terminator;
    }

    public function beforeAnalyze()
    {
        $filters = isset(self::$filters[static::class]) ? self::$filters[static::class] : [];
        foreach ($filters as $filter) {
            if ($filter($this) === false) {
                return false;
            }
        }
        return true;
    }

    public function afterAnalyze()
    {
        $terminators = isset(self::$terminators[static::class]) ? self::$terminators[static::class] : [];
        foreach ($terminators as $copyTee) {
            $copyTee($this);
        }
    }

    /**
     * @param Connection $connection
     */
    abstract public function analyze(Connection $connection);
}