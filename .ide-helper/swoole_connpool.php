<?php

/**
 * class swoole_connpool
 *
 * @since 3.0.4
 *
 * @package swoole_connpool
 */

/**
 * class swoole_connpool
 *
 * @since 3.0.4
 */
class swoole_connpool
{
    /**
     * 连接正常
     * @var int
     */
    const SWOOLE_CONNNECT_OK = 1;

    /**
     * 连接异常
     * @var int
     */
    const SWOOLE_CONNNECT_ERR = 2;

    /**
     * TCP 连接池
     * @var int
     */
    const SWOOLE_CONNPOOL_TCP = 1;

    /**
     * redis 连接池
     * @var int
     */
    const SWOOLE_CONNPOOL_REDIS = 2;

    /**
     * mysql 连接池
     * @var int
     */
    const SWOOLE_CONNPOOL_MYSQL = 3;
    

    /**
     * swoole_connpool constructor.
     *
     * @since 3.0.4
     *
     * @param int $connPoolType ，连接池类型
     * ```php
     * swoole_connpool::SWOOLE_CONNPOOL_TCP   ,  tcp连接池
     * swoole_connpool::SWOOLE_CONNPOOL_REDIS ,   redis连接池
     * swoole_connpool::SWOOLE_CONNPOOL_MYSQL ,   mysql连接池
     * ```
     */
    public function __construct(int $connPoolType) { }

    /**
     * setCfg 连接配置，支持重置
     *
     * @since 3.0.4
     *
     * @param array $cfg
     * @param array $cfg 连接池连接配置信息, createConnpool 会对该参数进行校验
     * ```php
     * 连接池配置信息包括两部分，一部分是与连接池的通用配置信息，另一部是具体类型的连接池所需的连接信息
     * 连接池通用配置信息：
     *  "hbTimeout" ＝> 500,      		// 心跳超时时间，int类型，单位ms，可选，默认500ms 
     *  "connectTimeout"  => 500,	 	// 连接超时时间，int类型，单位ms，可选，默认500ms
     *  "hbIntervalTime"  => 0,	 	 	// 心跳间隔时间，int类型，单位ms，可选，默认为0ms
     *  "connectInterval" => 300	 	// 重连间隔，   int类型，单位ms，可选，默认100ms～400ms
     *  "maxConnectInterval" => 3000,	// 最大重连时间，int类型，单位ms，可选，默认2000ms～5000ms
     *  "maxConnectTimes"    => 3,		// 最大连接次数，int类型,       可选，默认3次
     * 连接池具体连接相关配置，如，
     * tcp 连接池：
     *  "host" => "127.0.0.1",            // 连接地址， string类型，必选
     *  "port" => 12345                   // 连接端口， int类型，   必选
     * redis 连接池，参考swoole_redis::connect接口的配置项 
     * mysql 连接池，参考swoole_connect::connect接口的配置项
     * ```
     * @return bool  true设置成功，false设置失败
     *
     */
    public function setConfig(array $cfg){ }
    
    /**
     * createConnPool 创建连接池，接口内部对所有已经设置的参数进行校验
     *
     * @since 3.0.4
     *
     * @param int $minPoolnum 连接池最小对象个数
     * @param int $maxPoolnum 连接池最大对象个数
     *
     * @return bool true设置成功，false设置失败
     */
    public function createConnPool(int $minPoolnum, int $maxPoolnum){ }
    
    /**
     *  on 设置回调函数
     *
     * @since 3.0.4
     *
     * @param string $cbName 回调名称
     * ```php
     * "hbConstruct"    // 心跳消息构造
     * "hbCheck"        // 心跳回复校验
     * ```
     * @param callable $hbCallback 回调函数，参见hbConstruct／hbCheck
     * 
     * ```php
     * hbConstruct 心跳消息构造函数
     * return array 
     *      "method" => "get",    // 心跳发送接口，string 类型，对于redis连接池 必选，其他连接池无效
     *      "args" => "",		  // 心跳数据，string类型
     * function hbConstruct() {}
     * ```
     * ```php 
     * hbCheck 心跳消息校验函数
     * param  swoole_connpool $pool 连接池
     * param  $conn_obj  连接对象，类型与具体连接池类型相关
     * param  $data      回调数据
     * 
     * return bool   true 校验成功，false 校验失败     
     * function hbCheck(swoole_connpool $pool,$conn_obj,$data) {}
     * ```
     *  
     * @return bool  true设置成功，false设置失败
     */
    public function on(string $cbName, callable $hbCallback){ }

    /**
     * get 获取连接对象
     *
     * @since 3.0.4
     *
     * @param int $timeout 超时时间，单位ms
     * @param callable $objCall 回调函数，函数原型参见connObjCallback
     * ```php				  
     * connObjCallback 获取连接对象结果回调
     *   @param $pool swoole_connpool 连接池对象
     *   @param $conObj  false：获取连接连接超时，其他，为连接对象，如tcp连接池，conObj为swoole_client类型；
     *   function connObjCallback(swoole_connpool $pool,$conObj){}
     * ```
     *
     * @return bool true调用成功，false调用失败
     */
    public function get(int $timeout, callable $objCall){ }

    /**
     * release 释放连接对象，会对用户参数进行校验
     *
     * @since 3.0.4
     *
     * @param mixed $connObj 连接对象，如tcp连接池时，为swoole_client类型
     * @param int $conStatus 可选，默认为swoole_connpool::SWOOLE_CONNNECT_OK
     * ```php
     * 连接状态
     * 	  swoole_connpool::SWOOLE_CONNNECT_OK
     *    swoole_connpool::SWOOLE_CONNNECT_ERR
     * ```
     * @return bool true调用成功，false调用失败
     */
    public function release($connObj, int $conStatus = self::SWOOLE_CONNNECT_OK){}


    /**
     * destroy 销毁连接池对象，调用此接口前，需要将连接对象全部释放，调用此接口后，不能再使用该连接池其他方法
     * 
     * @since 3.0.4
     */
    public function destroy() { }


    /**
     * getStatInfo 获取连接状态
     *
     * @since 3.0.4
     *
     * @return  array
     * ```php
     * [
     *  "all_conn_obj" => 0,    //int类型，当前连接池的大小
     *  "idle_conn_obj" => 0,	//int类型，当前空闲连接数
     * ]
     * ```
     */
    public function getStatInfo() { }
}
