<?php

/**
 * class swoole_process
 *
 * @since 3.0.4
 *
 * @package swoole_process
 */

/**
 * class swoole_process 进程管理模块，用来替代 PHP 的 pcntl 扩展
 *
 * @since 3.0.4
 */
class swoole_process
{

    /**
     * @var int
     */
    public $pid = -1;

    /**
     * @var int
     * @internal
     */
    public $pipe = -1;

    /**
     * @var callable 新创建的子进程要执行的函数
     */
    public $callback;

    /**
     * @var int
     */
    public $id;

    /**
     * __construct 创建子进程
     *
     * @since 3.0.4
     *
     * @param callable $callback 子进程创建成功后要执行的函数入口
     * @param bool     $redirect_stdin_and_stdout [optional] 重定向子进程的标准输入和输出。
     *                             此选项设置为 true 后，在进程内echo将不是打印屏幕，而是写入到管道。
     *                             读取键盘输入将变为从管道中读取数据。默认为阻塞读取。
     * @param int      $pipe_type [optional] 是否创建管道，如果 $redirect_stdin_and_stdout=true，则该参数被忽略
     *
     */
    public function __construct(callable $callback, bool $redirect_stdin_and_stdout = false, int $pipe_type = 2)
    {
    }

    /**
     * __destruct 子进程析构函数
     *
     * @since 3.0.4
     *
     * @return
     */
    public function __destruct()
    {
    }

    /**
     * wait 回收结束运行的子进程，必须要执行 wait 回收结束运行的子进程，否则子进程就会变成僵尸进程
     *
     * @since 3.0.4
     *
     * @param $blocking [optional]
     *
     * @return array|false 成功返回一个数组，包含子进程的 Pid、退出状态码、被哪种信号 Kill
     *                     $result = array('code' => 0, 'pid' => 10001, 'signal' => 15);
     *                     失败返回 false
     */
    public static function wait(bool $blocking = null)
    {
    }

    /**
     * signal 设置要异步监听的信号，只能用于异步程序中
     *
     * @since 3.0.4
     *
     * @param int      $signo 信号值
     * @param callable $callback 回调函数，如果 $callback=null，则表示移除信号监听
     *
     * @return
     */
    public static function signal(int $signo, callable $callback)
    {
    }

    /**
     * kill 向指定子进程发送信号
     *
     * @since 3.0.4
     *
     * @param int $pid 子进程的进程号
     * @param int $sig [optional] 可选参数，默认为 SIGTERM。$sig=0，可以检测进程是否存在，不会发送信号
     *
     * @return bool 成功返回 true，失败返回 false
     */
    public static function kill(int $pid, int $sig = SIGTERM)
    {
    }

    /**
     * daemon 使当前进程变为一个守护进程
     *
     * @since 3.0.4
     *
     * @param bool $nochdir [optional] 为 true 时，表示不要切换当前目录到根目录
     * @param bool $noclose [optional] 为 true 时，表示不要关闭标准输入输出文件描述符
     *
     * @return bool 成功返回 true，失败返回 false
     */
    public static function daemon(bool $nochdir = true, bool $noclose = true)
    {
    }

    /**
     * setaffinity 设置CPU亲和性，可以将进程绑定到特定的CPU核上。
     *
     * @since 3.0.4
     *
     * @param array $cpu_set 数组，表示绑定哪些CPU核，如 array(0,2,3) 表示绑定CPU0/CPU2/CPU3
     *
     * @return bool 成功返回 true，失败返回 false
     */
    public static function setaffinity(array $cpu_set)
    {
    }

    /**
     * useQueue 启用消息队列作为进程间通信
     *
     * @since 3.0.4
     *
     * @param int $msgkey [optional] 消息队列的key，默认会使用ftok(__FILE__, 1)作为KEY
     * @param int $mode [optional]   通信模式，默认值为 2，表示争抢模式，所有创建的子进程都会从消息队列中取数据
     *
     * @return bool 成功返回 true，失败返回 false，创建消息队列失败同样返回 false
     */
    public function useQueue(int $msgkey = 0, int $mode = 2)
    {
    }

    /**
     * freeQueue 删除消息队列。此方法与useQueue成对使用，useQueue创建队列，使用freeQueue销毁队列。销毁队列后队列中的数据会被清空。
     *
     * @since 3.0.4
     *
     * @return bool 成功返回 true，失败返回 false
     */
    public function freeQueue()
    {
    }

    /**
     * start 执行fork系统调用，启动进程
     *
     * @since 3.0.4
     *
     * @return int|false 创建成功返回子进程的PID，创建失败返回false。可使用swoole_errno和swoole_strerror得到错误码和错误信息。
     */
    public function start()
    {
    }

    /**
     * write 向管道内写入数据
     *
     * @since 3.0.4
     *
     * @param string $data 要写入管道的数据
     *                     $data的长度在Linux系统下最大不超过8K，MacOS/FreeBSD下最大不超过2K
     *                     在子进程内调用write，父进程可以调用read接收此数据
     *                     在父进程内调用write，子进程可以调用read接收此数据
     *
     * @return int|false 成功返回写入数据的长度，失败返回 false
     */
    public function write(string $data)
    {
    }

    /**
     * close 闭创建的好的管道
     *
     * @since 3.0.4
     *
     * @return int|false 成功返回 errno，失败返回 false
     */
    public function close()
    {
    }

    /**
     * read 从管道中读取数据
     *
     * @since 3.0.4
     *
     * @param int $buf_size [optional] 缓冲区的大小，默认为8192，最大不超过64K
     *
     * @return
     */
    public function read(int $buf_size = 8192)
    {
    }

    /**
     * push 投递数据到消息队列中
     *
     * @since 3.0.4
     *
     * @param string $data 要投递的数据，长度受限与操作系统内核参数的限制。默认为8192，最大不超过65536
     *
     * @return bool 成功返回 true，失败返回 false
     *              默认模式下（阻塞模式），如果队列已满，push 方法会阻塞等待
     *              非阻塞模式下，如果队列已满，push 方法会立即返回 false
     */
    public function push(string $data)
    {
    }

    /**
     * pop 从队列中取数据
     *
     * @since 3.0.4
     *
     * @param int $maxsize [optional] 表示获取数据的最大长度，默认值为 8192，最大不超过 64k
     *
     * @return string|false 成功返回获取到的数据，失败返回 false
     *                      默认模式下，如果队列中没有数据，pop 方法会阻塞等待
     *                      非阻塞模式下，如果队列中没有数据，pop 方法会立即返回 false，并设置错误码为 ENOMSG
     */
    public function pop(int $maxsize = 8192)
    {
    }

    /**
     * exit
     *
     * @since 3.0.4
     *
     * @param $ret_code [optional]
     *
     * @return
     */
    //public function exit(int $ret_code = 0){}

    /**
     * exec 执行一个外部程序，此函数是 exec 系统调用的封装
     *      执行成功后，当前进程的代码段将会被新程序替换。子进程蜕变成另外一套程序。父进程与当前进程仍然是父子进程关系。
     *      父进程与新进程之间可以通过可以通过标准输入输出进行通信，必须启用标准输入输出重定向。
     *
     * @since 3.0.4
     *
     * @param string $execfile 指定可执行文件的绝对路径，如 "/usr/bin/python"
     * @param array  $args 数组，是 exec 的参数列表，如 array('test.py', 123)，相当与 python test.py 123
     *
     * @return bool 成功返回 true，失败返回 false
     */
    public function exec(string $execfile, array $args)
    {
    }

    /**
     * name 修改进程名称。此函数是 swoole_set_process_name 的别名
     *      在执行exec后，进程名称会被新的程序重新设置
     * @since 3.0.4
     *
     * @param string $process_name 进程名称
     *
     * @return void
     */
    public function name(string $process_name)
    {
    }

}

