<?php

namespace Minimalism;

/**
 * Class Signature
 * 一份api签名方案的实现
 *
 * User: xiaofeng
 * Date: 2015/9/7
 * Time: 10:45
 */
final class Signature
{
    private static $salts;

    public static function setSalt(array $salt)
    {
        self::$salts = $salt;
    }

    /**
     * 给请求附加签名
     * @param &array $request
     * @throws SignatureException
     * @author xiaofeng
     */
    public static function sign(array &$request)
    {
        $salts = self::getSalt(-1);
        $salt_index = mt_rand(0, count($salts) -1);
        $time_stamp = self::getMillisecond();
        $sign = self::getSign(array_merge($request, [
            'salt_index' => $salt_index,
            'time_stamp' => $time_stamp,
        ]));
        $sign = strtolower($sign) . $salts[$salt_index];
        $vtoken = md5($sign);
        $request['sign'] = implode(',', [$vtoken, $salt_index, $time_stamp]);
    }

    /**
     * 验证
     * @param $rawBody
     * @param float $expired 超时毫秒
     * @throws SignatureException
     */
    public static function auth($rawBody, $expired = 1e4)
    {
        $json = json_decode(strtolower($rawBody), true);

        if(!is_array($json) || !isset($json['sign'])) {
            throw new SignatureException("Bad Request Body: sign missing");
        }

        $postSigns = explode(',', $json['sign']);
        if(count($postSigns) !== 3) {
            throw new SignatureException("Bad Request Body: sign is not completed");
        }

        list($vtoken, $json['salt_index'], $json['time_stamp']) = $postSigns;

        // !!! int overflow
        $elapsed = abs(self::getMillisecond() - /*(int)*/$json['time_stamp']);
        if($elapsed > $expired) {
            throw new SignatureException("Request Expired");
        }

        unset($json['sign']);

        $sign = self::getSign($json) . self::getSalt($json['salt_index']);

        // For Debug
        // if(defined('DEBUG') && $vtoken == 'debug_token') { return true; }

        if (md5($sign) !== $vtoken) {
            throw new SignatureException("Bad Token");
        }
    }

    /**
     * 获取盐值数组
     * @param $index
     * @return mixed
     * @throws SignatureException
     */
    private static function getSalt($index = -1)
    {
        if($index === -1) {
            return self::$salts;
        }

        if(!isset(self::$salts[$index])) {
            throw new SignatureException("Salt Not Found: salt_index is $index");
        }
        return self::$salts[$index];
    }

    /**
     * 毫秒级时间戳
     * 微秒（µs）：10-6秒 毫秒（ms）：10-3秒
     * @author xiaofeng
     */
    private static function getMillisecond()
    {
        return round(microtime(true) * 1e3);
    }

    /**
     * 二维参数一维化
     * @param array $array
     * @return string
     */
    private static function getSign(array $array)
    {
        // return json_encode($array);
        self::vlist($array, $vlist);
        ksort($vlist, SORT_STRING); // php数组可以存放混合类型，sort默认混合类型排序，混合类型排序在静态编译语言下不是默认实现
        $all = [];
        foreach($vlist as $k => $v) {
            sort($v, SORT_STRING);
            $all[] = $k . '=' . implode('-', $v);
        }
        return implode('&', $all);
    }

    /**
     * 多维结构化参数二维化处理
     * @param array $array 结构化参数
     * @param &array $vlist OUT_PARAM
     * @param string $lastKey 递归外层数组key
     */
    private static function vlist(array $array, &$vlist, $lastKey = null)
    {
        foreach($array as $k => $v) {
            if(is_array($v)) {
                self::vlist($v, $vlist, $k);
            } else {
                // 简单通过key是否是数组判断 list or map
                if(is_numeric($k)) {
                    $vlist[$lastKey] = isset($vlist[$lastKey]) ? array_merge($vlist[$lastKey], $array) : $array;
                    break;
                } else {
                    $vlist[$k][] = $v;
                }
            }
        }
    }

}

class SignatureException extends \RuntimeException { }
