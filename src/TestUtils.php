<?php

namespace Minimalism;


class TestUtils
{
    /**
     * https://en.wikipedia.org/wiki/Units_of_information
     * @param int $bytes
     */
    public static function formatBytes($bytes) {
        $func = static::formatUnits(["Byte", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB"], 1024);
        return $func($bytes);
    }

    /**
     * https://en.wikipedia.org/wiki/Orders_of_magnitude_(time)
     * second s 1 -> millisecond ms 10^-3 -> microsecond μs 10^-6 -> nanosecond  ns 10^-9 ...
     * @param int $milliseconds
     */
    public static function formatMillisecond($milliseconds) {
        // μs => us
        $func = static::formatUnits(["us", "ms", "s"], 1000);
        return $func($milliseconds);

    }

    /**
     * 格式化函数生成
     * @param  array  $units 单位由小到大排列
     * @param  int $base 单位之间关系必须一致
     * @return \Closure
     * @author xiaofeng
     */
    public static function formatUnits(array $units, $base) {
        /**
         * @param int $numbers 待格式化数字，以$units[0]为单位
         * @param string $prefix
         * @return string
         */
        return $iter = function($numbers, $prefix = "") use ($units, $base, &$iter) {
            if($numbers == 0) {
                return ltrim($prefix);
            }
            if($numbers < $base) {
                return ltrim("$prefix {$numbers}{$units[0]}");
            }

            $i = intval(floor(log($numbers, $base)));
            $unit = $units[$i];
            $unitBytes = pow($base, $i);
            $n = floor($numbers / $unitBytes);
            return $iter($numbers - $n * $unitBytes, "$prefix $n$unit");
        };
    }

    /**
     * @param callable $func
     */
    public static function cost(callable $func) {
        $start = microtime(true);
        $startMemUsage = memory_get_usage();

        try {
            $func();
        } catch (\Exception $ex) {
            echo $ex;
        }

        $elapsed = microtime(true) - $start;
        $memUsage = memory_get_peak_usage() - $startMemUsage;
        // $memUsage = memory_get_usage() - $startMemUsage;

        echo PHP_EOL, str_repeat("=", 60), PHP_EOL;
        echo "Cost Summary:", PHP_EOL;
        echo str_pad("elapsed seconds", 30), static::formatMillisecond($elapsed * 1000000), PHP_EOL;
        echo str_pad("memory usage", 30), static::formatBytes($memUsage), PHP_EOL;
        echo str_pad("    emalloc memory", 30), static::formatBytes(memory_get_usage()), PHP_EOL;
        echo str_pad("    malloc memory", 30), static::formatBytes(memory_get_usage(true)), PHP_EOL;
        echo str_pad("    emalloc peak memory", 30), static::formatBytes(memory_get_peak_usage()), PHP_EOL;
        echo str_pad("    malloc peak memory", 30), static::formatBytes(memory_get_peak_usage(true)), PHP_EOL;
        echo str_repeat("=", 60), PHP_EOL;
    }


    /*
    public static function xhprof()
    {
        if (function_exists("xhprof_enable")) {
            xhprof_enable(XHPROF_FLAGS_NO_BUILTINS | XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY);
            register_shutdown_function(function() {
                print_r(xhprof_disable());
            });
        }
    }

    public static function memoryProf($id)
    {
        if (function_exists('memprof_enable')) {
            memprof_enable();
            register_shutdown_function(function() use($id) {
                memprof_dump_callgrind(fopen("/tmp/{$id}.out", "w"));
            });
        }
    }

    public static function objectsSummary()
    {
        if (function_exists("meminfo_objects_summary")) {
            gc_collect_cycles();
            $stdout = fopen('php://stdout','w');
            meminfo_objects_summary($stdout);
        }
    }
    */
}