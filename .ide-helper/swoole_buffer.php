<?php

/**
 * class swoole_buffer
 *
 * @since 3.0.4
 *
 * @package swoole_buffer
 */

/**
 * class swoole_buffer
 *
 * @since 3.0.4
 */
class swoole_buffer
{

    /**
     * @var int
     */
    public $capacity = 0;

    /**
     * @var int
     */
    public $length = 0;

    /**
     * __construct swoole_buffer 构造函数，创建一个内存对象
     *
     * @since 3.0.4
     *
     * @param int $size [optional] 默认为 128 字节，指定缓冲区内存的初始尺寸。当申请的内存容量不够时 swoole 底层会自动扩容。
     *
     */
    public function __construct(int $size = 128) {}

    /**
     * __destruct 析构函数
     *
     * @since 3.0.4
     * @internal
     *
     */
    public function __destruct() {}

    /**
     * __toString
     *
     * @since 3.0.4
     * @internal
     * @return
     */
    public function __toString() {}

    /**
     * substr 从缓冲区中取出内容。
     *
     * @since 3.0.4
     * @param int $offset             表示偏移量，如果为负数，表示倒数计算偏移量，从 buffer 末尾倒数偏移 $offset 字节做为起始偏移量
     * @param int $length [optional]  表示读取数据的长度，默认为从 $offset 到整个缓存区末尾
     * @param bool $remove [optional] 表示从缓冲区的头部将此数据移除。只有 $offset = 0 时此参数才有效。
     *                                $remove 后内存并没有释放，只是底层进行了指针偏移。当销毁此对象时才会真正释放内存
     *
     * @return string|false 成功返回取出的字符串，失败返回 false
     */
    public function substr(int $offset, int $length = -1, bool $remove = false) {}

    /**
     * write 向缓存区的 $offset 起始的内存位置写入数据 $data
     * read/write 函数可以直接读写内存。所以使用务必要谨慎，否则可能会破坏现有数据。
     *
     * @since 3.0.4
     *
     * @param int    $offset 偏移量，要写入的起始位置
     * @param string $data   要写入的数据
     *
     * @return int|false 成功返回写入的数据长度，失败返回 false
     */
    public function write(int $offset, string $data) {}

    /**
     * read 从缓冲区 $offset 位置开始，读取长度 $length 的数据
     *
     * @since 3.0.4
     *
     * @param int $offset 偏移量
     * @param int $length 要读取的数据长度
     * @return string|false 成功返回读取到的数据的长度，失败返回 false
     */
    public function read(int $offset, int $length) {}

    /**
     * append 将数据 $data 追加到缓存区末尾。
     *
     * @since 3.0.4
     *
     * @param string $data 要写入的数据
     * @return int|false 成功返回缓冲区数据总长度，失败返回 false
     */
    public function append(string $data) {}

    /**
     * expand 将缓冲区扩容至新的大小 $new_size
     *
     * @since 3.0.4
     *
     * @param int $new_size 新的缓冲区大小，必须大于当前缓冲区的大小
     *
     * @return bool 成功返回 true，失败返回 false
     */
    public function expand(int $new_size) {}

    /**
     * recycle 回收缓冲中已经废弃的内存
	 * 
	 * ```php
     *  此方法能够在不清空缓冲区和使用 swoole_buffer->clear() 的情况下，
	 *  回收通过 substr() 移除但仍存在的部分内存空间。
	 *  只有当 substr 方法的第三个参数 $remove = true 时，recycle 才生效。
     * ```
	 * @since 3.0.4
     *
     * @return void
     */
    public function recycle() {}

    /**
     * clear 清理缓存区数据
     * 执行此操作后，缓存区将重置。swoole_buffer对象就可以用来处理新的请求了。
     *
     * @since 3.0.4
     *
     * @return void
     */
    public function clear() {}

}
