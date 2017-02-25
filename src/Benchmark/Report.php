<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/16
 * Time: 下午10:25
 */

namespace Minimalism\Benchmark;


class Report
{
    private static $offset = 0;
    private static $report;
    private static $report_last;
    private static $file;
    private static $label;
    private static $enable;
    private static $pid;

    public static function start($label, $file)
    {
        self::$label = $label;
        self::$file = $file;
        self::$pid = posix_getpid();
        self::$report_last = self::now();
        self::$enable = true;

        self::summary();
    }

    public static function stop()
    {
        self::$enable = false;
    }

    public static function success($elapsed, $bytes, $sentBytes)
    {
        self::$report[] = [
            'timeStamp' => self::now(),
            'elapsed' => $elapsed,
            'label' => self::$label,
            'responseCode' => 200,
            'responseMessage' => 'OK',
            'threadName' => 'thread 1-1',
            'dataType' => 'text',
            'success' => 'true',
            'failureMessage' => '',
            'bytes' => $bytes,
            'sentBytes' => $sentBytes,
            'grpThreads' => 1,
            'allThreads' => 1,
            'Latency' => 0,
            'IdleTime' => 0,
            'Connect' => 0,
        ];

        Qps::success();
    }

    public static function fail($elapsed, $bytes, $sentBytes, $msg, $code = 0)
    {
        self::$report[] = [
            'timeStamp' => self::now(),
            'elapsed' => $elapsed,
            'label' => self::$label,
            'responseCode' => $code,
            'responseMessage' => 'FAIL',
            'threadName' => 'thread 1-1',
            'dataType' => 'text',
            'success' =>'false',
            'failureMessage' => $msg,
            'bytes' => $bytes,
            'sentBytes' => $sentBytes,
            'grpThreads' => 1,
            'allThreads' => 1,
            'Latency' => 0,
            'IdleTime' => 0,
            'Connect' => 0,
        ];

        Qps::fail();
    }

    /**
     * 单进程数据
     */
    private static function summary()
    {
        swoole_timer_after(2000, function() {
            $now = self::now();
            $elapsed = $now - self::$report_last;
            Qps::computation("pid=" . self::$pid, count(self::$report), $elapsed);

            if (empty(self::$report)) {
                self::summary();
            } else {
                $r = [];
                foreach (self::$report as $item) {
                    $r[] = implode(",", array_values($item));
                }
                self::$report = [];
                self::$report_last = $now;
                $log = implode("\n", $r) . "\n";
                self::write($log, function() {
                    if (self::$enable) {
                        self::summary();
                    } else {
                        // 保证日志文件落盘
                        swoole_event_exit();
                    }
                });
            }
        });
    }

    private static function write($contents, callable $cb)
    {
        $file = new File(self::$file);
        $file->write($contents, self::$offset, $cb);
        self::$offset += strlen($contents);
    }

    public static function now()
    {
        return intval(microtime(true) * 1000);
    }
}