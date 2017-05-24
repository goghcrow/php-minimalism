<?php

namespace Minimalism\PHPDump\Pcap;

/**
 * Class Packet : Protocol Data Unit
 * @package Minimalism\PHPDump\Pcap
 */
abstract class PDU
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

    public function preInspect()
    {
        $filters = isset(self::$filters[static::class]) ? self::$filters[static::class] : [];
        foreach ($filters as $filter) {
            if ($filter($this) === false) {
                return false;
            }
        }
        return true;
    }

    public function postInspect()
    {
        $terminators = isset(self::$terminators[static::class]) ? self::$terminators[static::class] : [];
        foreach ($terminators as $copyTee) {
            $copyTee($this);
        }
    }

    /**
     * @param Connection $connection
     */
    abstract public function inspect(Connection $connection);
}