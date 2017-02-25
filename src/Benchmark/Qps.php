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
 * apcu 在mac上回发生死锁 !!!
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

    private static $report_last;

    public static function init()
    {
        self::$ok_counter = new Counter();
        self::$ko_counter = new Counter();
        self::$report_last = self::now();
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

    public static function summary()
    {
        $now = self::now();
        $elapsed = $now - self::$report_last;

        $okCount = self::$ok_counter->get();
        $koCount = self::$ko_counter->get();;

        self::computation("ALL", $okCount + $koCount, $elapsed);
        self::computation("OK", $okCount, $elapsed);
        self::computation("KO", $koCount, $elapsed);

        if ($okCount) {
            self::$ok_counter->decr($okCount);
        }
        if ($koCount) {
            self::$ko_counter->decr($koCount);
        }
        self::$report_last = $now;
    }

    public static function computation($desc, $requests, $elapsed)
    {
        if ($requests === 0) {
            $avg_res_time = 0;
        } else {
            $avg_res_time = number_format($elapsed / $requests, 2);
        }
        $qps = intval($requests / $elapsed * 1000);

        $time = date("Y-m-d H:i:s", time());
        $summary = "[$time] QPS $desc qps=$qps, avg=$avg_res_time\n";
        fprintf(STDERR, $summary);
    }

    private static function now()
    {
        return intval(microtime(true) * 1000);
    }
}