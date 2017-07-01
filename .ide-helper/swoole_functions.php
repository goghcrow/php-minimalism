<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/16
 * Time: 上午10:52
 */

/**
 *
 * @since 2.0.0
 *
 * @return
 */
function swoole_version() {}

/**
 * 获取本机cpu数量
 *
 * @since 2.0.0
 *
 * @return int
 */
function swoole_cpu_num() {}

/**
 * nova协议解包
 *
 * @since 2.0.0
 *
 * @param string $buf 二进制字符串
 * @param string &$service_name 服务名
 * @param string &$method_name 方法名
 * @param string &$ip
 * @param int &$port
 * @param int &$seq_no
 * @param string &$attach 附加字段 通常为json编码字符串
 * @param string &$data nova body
 * @return bool
 */
function nova_decode($buf, &$service_name, &$method_name, &$ip, &$port, &$seq_no, &$attach, &$data) {}

/**
 * nova协议解包
 *
 * @since 2.0.0
 *
 * @param string $service_name
 * @param string $method_name
 * @param string $ip
 * @param int $port
 * @param int $seq_no
 * @param string $attach 附加字段 通常为json编码字符串
 * @param string $data 协议body
 * @param string &$buf 打包结果
 * @return bool
 */
function nova_encode($service_name, $method_name, $ip, $port, $seq_no, $attach, $data, &$buf) {}

/**
 *
 * @since 2.0.0
 *
 * @param $session_array
 * @return
 */
function zan_session_encode($session_array) {}

/**
 *
 * @since 2.0.0
 *
 * @param $data
 * @return
 */
function zan_session_decode($data) {}

/**
 *
 * @since 2.0.0
 *
 * @param string $bin
 * @return bool
 */
function is_nova_packet($bin) {}

/**
 *
 * @since 2.0.0
 *
 * @return int
 */
function nova_get_sequence() {}

/**
 *
 * @since 2.0.0
 *
 * @return
 */
function nova_get_time() {}

/**
 *
 * @since 2.0.0
 *
 * @return
 */
function nova_get_ip() {}

/**
 *
 * @since 2.0.0
 *
 * @param $fd
 * @param $cb
 * @return
 */
function swoole_event_add($fd, $cb) {}

/**
 *
 * @since 2.0.0
 *
 * @return
 */
function swoole_event_set() {}

/**
 *
 * @since 2.0.0
 *
 * @param $fd
 * @return
 */
function swoole_event_del($fd) {}

/**
 *
 * @since 2.0.0
 *
 * @return
 */
function swoole_event_exit() {}

/**
 *
 * @since 2.0.0
 *
 * @return
 */
function swoole_event_wait() {}

/**
 *
 * @since 2.0.0
 *
 * @param $fd
 * @param $data
 * @return
 */
function swoole_event_write($fd, $data) {}

/**
 *
 * @since 2.0.0
 *
 * @param $callback
 * @return
 */
function swoole_event_defer($callback) {}

/**
 * 延时执行
 *
 * @since 2.0.0
 * @param int  $ms  延时执行时间
 * @param callable $callback
 * @param param[option]   携带参数，回调时会携带，默认为NULL
 * @return int  调用失败返回false，成功返回定时器id
 */
//function swoole_timer_after($ms, callable $callback, $param = null) {}

/**
 * 周期性定时器
 *
 * @since 2.0.0
 * @param int   $ms  定时器间隔
 * @param callable   $callback
 * @param $param [optional]      携带参数，回调时会携带，默认为NULL
 * @return int  调用失败返回false，成功返回定时器id
 */
//function swoole_timer_tick($ms, $callback,$param = null) {}

/**
 * 查看定时器是否存在
 *
 * @since 2.0.0
 * @param $timer_id
 * @return bool
 */
function swoole_timer_exists($timer_id) {}

/**
 * 清除指定定时器
 *
 * @since 2.0.0
 * @param int $timer_id 定时器id
 * @return bool
 */
function swoole_timer_clear($timer_id) {}

/**
 * 设置异步IO操作配置
 *
 * @since 2.0.0
 * @link https://wiki.swoole.com/wiki/page/182.html
 * @param array $settings
 * ```补充选项：“aio_max_buffer” ＝> 1*1024*1024，设置aio最大buf```
 * @return bool
 */
function swoole_async_set($settings) {}

/**
 * 异步读取文件数据
 *
 * 若读取数据较大，则回多次回调用户；最后一次回调数据长度为0，表示读取结束
 *
 * @since 2.0.0
 * @param string $filename
 * @param string $callback
 * @param int $chunk_size [optional]  读取数据的长度，默认-1:读取整个文件.
 * @param int $offset [optional]  文件起始偏移量，从文件偏移开始读取，默认为0
 * @return bool
 */
function swoole_async_read($filename, $callback, $chunk_size = null, $offset = null) {}

/**
 * 异步写文件
 *
 * 若需要写入的数据大，可分批写入，用户需要设置好每次写入文件的偏移，避免分批写入出现乱序
 *
 * @since 2.0.0
 * @param string $filename  文件名
 * @param string $content   待写入文件的数据，数据长度不大于buf_max_len,@swoole_async_set  aio_max_buffer选项
 * @param int $offset [optional]  写入数据的相对文件起始的偏移量
 * @param callable $callback [optional]  结果回调
 * @return bool
 */
function swoole_async_write($filename, $content, $offset = null, $callback = null) {}

/**
 * 异步dns查询
 *
 * @since 2.0.0
 * @param string $domain_name  域名
 * @param callable $callback    用户callback
 * @return bool
 */
function swoole_async_dns_lookup($domain_name, $callback) {}

/**
 * 清空 dns 缓存
 *
 * @since 2.0.0
 * @return
 */
function swoole_clean_dns_cache() {}

/**
 *
 * @since 2.0.0
 *
 * @param $read_array
 * @param $write_array
 * @param $error_array
 * @param $timeout [optional]
 * @return
 */
function swoole_client_select($read_array, $write_array, $error_array, $timeout = null) {}

/**
 *swoole_set_process_name 设置进程名称
 *
 * @since 2.0.0
 *
 * @param $process_name
 * @return
 */
function swoole_set_process_name($process_name) {}

/**
 *swoole_get_local_ip 获取本机所有的网络接口ip
 *
 * @since 2.0.0
 *
 * @return array
 */
function swoole_get_local_ip() {}

/**
 *swoole_strerror 获取errno 对应的字符串
 *
 * @since 2.0.0
 *
 * @param int $errno
 * @return string
 */
function swoole_strerror($errno) {}

/**
 * swoole_errno 获取errno
 *
 * @since 2.0.0
 *
 * @return int
 */
function swoole_errno() {}