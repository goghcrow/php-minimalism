<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/25
 * Time: 下午6:15
 */

namespace Minimalism\Benchmark;


/**
 * Class Qps
 * !!! apc.enable_cli
 * @package Minimalism\Benchmark
 *
 * apcu 在mac上会发生死锁 !!!
 */
class Qps
{
    /**
     * @var Counter
     */
    private static $ok_counter;

    /**
     * @var Counter
     */
    private static $ko_counter;

    public static function init()
    {
        self::$ok_counter = new Counter();
        self::$ko_counter = new Counter();
    }

    public static function success()
    {
        if (self::$ok_counter) {
            self::$ok_counter->incr();
        }
    }

    public static function fail()
    {
        if (self::$ko_counter) {
            self::$ko_counter->incr();
        }
    }

    public static function summary($interval)
    {
        $okCount = self::$ok_counter->get();
        $koCount = self::$ko_counter->get();;

        list($qps, $rt) = self::computation($okCount + $koCount, $interval);
        list($ok_qps, $ok_rt) = self::computation($okCount, $interval);
        list($ko_qps, $ko_rt) = self::computation($koCount, $interval);

        $time = date("H:i:s", time());
        $summary = "[$time] qps=$qps, ok=$ok_qps, ko=$ko_qps, rt=$rt\n";
        fprintf(STDERR, $summary);

        if ($okCount) {
            self::$ok_counter->decr($okCount);
        }
        if ($koCount) {
            self::$ko_counter->decr($koCount);
        }
    }

    public static function computation($requests, $elapsed)
    {
        if ($requests) {
            $avg_res_time = number_format($elapsed / $requests, 2);
        } else {
            $avg_res_time = 0;
        }
        $qps = intval($requests / $elapsed * 1000);

        return [$qps, $avg_res_time];
    }
}