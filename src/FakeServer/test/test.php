<?php



class TimerFlag
{
    public $flag;

    private $isRun;
    private $min;
    private $max;

    public function __construct($min, $max, $init = true)
    {
        $this->min = $min;
        $this->max = $max;
        $this->flag = boolval($init);
        $this->isRun = false;
    }

    public function run()
    {
        if ($this->isRun === false) {
            $this->delaySwitch();
            $this->isRun = true;
        }
    }

    private function delaySwitch()
    {
        $delay = mt_rand($this->min, $this->max);
        swoole_timer_after($delay, function() {
            $this->flag = !$this->flag;
            $this->delaySwitch();
        });
    }
}




// 一段时间 mysql 协议登录阶段直接 被server close
// 一段时间 2000ms后 server端关闭连接
function test_close($conf)
{
    $timerFlag = new TimerFlag(1000, 5000, true);

    $mysqlServer = new \Minimalism\FakeServer\MySQL\FakeMySQLServer($conf);
    $mysqlServer->on("login", function(MySQLConnection $conn, $vars) use($timerFlag) {
        $timerFlag->run();

        if ($timerFlag->flag) {
            // 连接成功，2000ms 后关闭
            swoole_timer_after(2000, function() use($conn) {
                $conn->close();
            });

            $conn->responseOK();
//            return true;
        } else {
            // mysql 握手阶段直接关闭
            $conn->close();
            $conn->responseERR();
//            return false;
        }
    });

    $mysqlServer->start();
}

// mysql 协议 握手阶段 hold住5000ms
function test_timeout($conf, $delay = 5000)
{
    $timerFlag = new TimerFlag(1000, 5000, true);

    $mysqlServer = new \Minimalism\FakeServer\MySQL\FakeMySQLServer($conf);
    $mysqlServer->on("login", function(MySQLConnection $conn, $vars) use($delay, $timerFlag) {
        $timerFlag->run();

        // flag 背后有定时器间断性改变
        if ($timerFlag->flag) {
            $conn->responseOK();
        } else {
            swoole_timer_after($delay, function() use($conn) {
                $conn->responseOK();
            });
        }

        swoole_timer_after(2000, function() use($conn) {
            $conn->close();
        });
    });

    $mysqlServer->start();
}


//test_close($conf);

test_timeout($conf);
