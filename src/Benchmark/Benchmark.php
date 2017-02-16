<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/17
 * Time: 上午1:05
 */

namespace Minimalism\Benchmark;


class Benchmark
{
    public static $final_file = "report.jtl";

    /**
     * @var Client[]
     */
    public static $clients = [];

    public static $pids = [];

    public static $reports = [];

    public static $enable;

    /**
     * @param TestPlan $test
     * @param array $setting
     *
     * 使用jmeter将日志文件导出报告
     * ~/apache-jmeter-3.1/bin/jmeter -g report.jtl -o ./report
     * cd report
     * php -S 0.0.0.0:9999
     */
    public static function start(TestPlan $test, array $setting = [])
    {
        $conf = $test->config();
        fprintf(STDERR, $conf->__toString() . "\n");

        for ($i = 0; $i < $conf->procNum; $i++) {

            $report_file = "{$conf->label}_{$i}.jtl";

            $pid = pcntl_fork();
            if ($pid < 0) {
                fprintf(STDERR, "fork fail");
                exit(1);
            }


            else if ($pid === 0) {


                ini_set("memory_limit", "1024M");
                swoole_async_set([
                    "thread_num" => 5,
                    "aio_max_buffer" => 1024 * 1024 * 10,
                ]);


                pcntl_signal(SIGTERM, function() {
                    foreach (self::$clients as $client) {
                        $client->stop();
                    }
                    Report::stop();
                });



                Report::start($conf->label, $report_file);

                for ($j = 0; $j < $conf->concurrency; $j++) {
                    self::$clients[] = Client::make($test, $conf, $setting);
                }
                exit(0);
            }


            self::$pids[] = $pid;
            self::$reports[] = $report_file;
        }

        pcntl_signal(SIGINT, function() {
            foreach (self::$pids as $pid) {
                posix_kill($pid, SIGTERM);
            }
            self::$enable = false;
        });

        self::$enable = true;
        self::loop();

        self::merge(self::$final_file);
    }

    public static function loop()
    {
        while (self::$enable) {
            usleep(200 * 1000);
            pcntl_signal_dispatch();
        }

        foreach (self::$pids as $pid) {
            pcntl_waitpid($pid, $status);
            if (!pcntl_wifexited($status)) {
                fprintf(STDERR, "$pid %s exit [exit_status=%d, stop_sig=%d, term_sig=%d]\n",
                    pcntl_wifexited($status) ? "normal": "abnormal",
                    pcntl_wexitstatus($status),
                    pcntl_wstopsig($status),
                    pcntl_wtermsig($status)
                );
            }
        }
    }

    public static function merge($file)
    {
        $title = "timeStamp,elapsed,label,responseCode,responseMessage,threadName,dataType," .
            "success,failureMessage,bytes,sentBytes,grpThreads,allThreads,Latency,IdleTime,Connect";

        // TODO 清洗数据
        `echo '$title' > $file`;
        foreach (self::$reports as $report) {
            `cat $report >> $file`;
            unlink($report);
        }
    }
}