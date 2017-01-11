<?php

namespace Minimalism\Test\Crontab;


use Minimalism\Crontab\CronExpression;

require __DIR__ . "/../../src/Crontab/CronExpression.php";

$cron = CronExpression::parse("* * 0,23 1,31 1,12 6");
var_export(explode(" ", date("s i G j n w")));


$cron = CronExpression::parse("*/2 * * * * *");
$cron = CronExpression::parse("* * * 7 * sat");
var_dump($cron->check(time()));


class CronTest
{
    public function test()
    {
        echo time() . ":" . __METHOD__, PHP_EOL;
    }
}

$conf = [
    "test" => [
        "cron" => "* * * * * *",
        "class"=> CronTest::class,
        "method"=> "test",
        "args" => [],
    ],
    "test1" => [
        "cron" => "* * * * * *",
        "class"=> CronTest::class,
        "method"=> "test",
        "args"  => [],
    ],
];

$conf = new Cron($conf);
$conf->run();

class Cron
{
    protected $crons = [];
    protected $timerId;

    public function __construct(array $conf)
    {
        foreach ($conf as $name => $item) {
            $callable = [new CronTest(), "test"];
            $this->crons[$name] = [
                "cron"      => CronExpression::parse($item["cron"]),
                "callable"  => $callable,
                "args"      => $item["args"],
            ];
        }
    }

    public function run()
    {
        $this->timerId = \swoole_timer_tick(1000, function() {
            $ts = time();
            foreach ($this->crons as $name => $task) {
                if ($task["cron"]->check($ts)) {
                    call_user_func_array($task["callable"], $task["args"]);
                }
            }
        });
    }

    public function stop()
    {
        \swoole_timer_clear($this->timerId);
    }
}