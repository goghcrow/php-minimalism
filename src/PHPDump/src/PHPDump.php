<?php

namespace Minimalism\PHPDump;


use Minimalism\Buffer\Buffer;
use Minimalism\PHPDump\Buffer\BufferFactory;
use Minimalism\PHPDump\Pcap\Pcap;

class PHPDump
{
    /**
     * @var Buffer
     */
    private $buffer;

    private $pcap;

    /**
     * @var \swoole_process|resource
     */
    private $proc;

    private $pid;

    public function __construct()
    {
        $this->buffer = BufferFactory::make();
        $this->pcap = new Pcap($this->buffer);
    }

    public function readFile($file)
    {
        swoole_async_read($file, function($filename, $content) {
            if ($content === "") {
                swoole_event_exit();
            } else {
                $this->buffer->write($content);
                $this->pcap->captureLoop();
            }
        });
    }

    public function readTcpdump($filter = "")
    {
        $fd = $this->forkTcpdump($filter);
        $this->loopRead($fd);
    }

    public function readTcpdump2($filter = "", $bufferSize = 8192)
    {
        $this->proc = proc_open("tcpdump -i any -s0 -U -w - $filter", [ 1 => ["pipe", "w"] ], $pipes);
        if ($this->proc === false) {
            sys_abort("proc_open fail");
        }

        register_shutdown_function(function() { proc_terminate($this->proc); `pkill tcpdump`; });

        /*
        $pcapFd = $pipes[1];
        $read = [$pcapFd];
        $write = $except = null;
        while (true) {
            $n = stream_select($read, $write, $except, null);
            if ($n) {
                $contents = stream_get_contents($read[0], $bufferSize);
                if ($contents !== false) {
                    $this->buffer->write($contents);
                    $this->pcap->captureLoop();
                }
            }
        }
        //*/


        while (is_resource($pipes[1])) {
            $contents = stream_get_contents($pipes[1], $bufferSize);
            if ($contents !== false) {
                $this->buffer->write($contents);
                $this->pcap->captureLoop();
            } else {
                sys_error("stream_get_contents return false");
            }
        }
    }

    private function forkTcpdump($filter = "")
    {
        $this->proc = new \swoole_process(function(\swoole_process $proc) use($filter) {
            $args = ["-i", "any", "-s", "0","-U", "-w", "-"];

            // 旧版本tcpdump -s 0
            // tcpdump version 4.1-PRE-CVS_2016_05_10
            // libpcap version 1.4.0
            // Setting snaplen to 0 sets it to the default of 65535
            // 但是, 新版本
            // tcpdump.4.9.0 version 4.9.0
            // libpcap version 1.8.1
            // Setting snaplen to 0 sets it to the default of 262144
            // 因为 -s 0 的snaplen > 65535
            // -s 0 等于抓取最大值

            if ($filter) {
                $args[] = $filter;
            }
            $proc->exec("/usr/sbin/tcpdump", $args);
            $proc->exit();
        }, true, 1);

        $this->pid = $this->proc->start();
        if ($this->pid === false) {
            sys_abort("fork fail");
        }

        return $this->proc->pipe;
    }

    /**
     * sudo tcpdump -i any -s 0 -U -w - host 10.9.97.143 and port 8050 | hexdump
     * sudo tcpdump -i any -s0 -U -w - port 8050 | php novadump.php
     * @param bool|int|resource $fd
     */
    private function loopRead($fd = STDIN)
    {
        swoole_event_add($fd, function($fd) {
            static $i = 0;

            if ($this->proc) {
                // hack~
                // !!!! buffer 太小会造成莫名其妙 pack包数据有问题....
                $recv = $this->proc->read(1024 * 1024 * 2);
                // swoole_process 将标准错误与标准输出重定向到一起了..这里先pass到两行tcpdump的标准错误输出
                // tcpdump 新版本 stdout -> stderr -> stderr -> stdout 顺序输出, 应该过滤掉 2, 3
                if (++$i <= 2) {
                    sys_echo($recv);
                    $recv = "";
                }
            } else {
                $recv = stream_get_contents($fd);
            }

            if (strlen($recv) > 0) {
                // Hex::dump($recv, "vvv/8/6");
                $this->buffer->write($recv);
                $this->pcap->captureLoop();
            }
        });

        register_shutdown_function(function() { `kill -9 {$this->pid}`; });
        // 控制 register_shutdown_function 执行顺序, 否则 kill 会比event_wait先执行
        swoole_event_wait();
    }
}