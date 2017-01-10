<?php

namespace Minimalism\Aop;

use Closure;
use Exception;

/**
 * Class ProxyClass
 * @package Minimalism\Aop
 *
 * 代理类
 * 避免冲突, 不定义属性与正常方法
 * 理论上可以代理所有魔术方法
 */
final class ProxyClass
{
    /**
     * 代理具体类的方法调用
     * @param  string 	$name
     * @param  array 	$args
     * @return mixed
     *
     * 不负责检测方法调用是否正确, 默认只调用原对象方法
     */
    public function __call($name, $args)
    {
        $return = null;

        $aop = ProxyPool::getAop($this);

        // 将 methodWrapper 与 exceptionHandler
        // 这两个方法从Aop类中的public方法修改为这里的闭包, 用性能换private

        /**
         * 包装原方法,用于around
         * @param  string $method 方法名
         * @param  array  $args 方法参数数组
         * @return Closure
         */
        $methodWrapper = function($method) use ($aop) {
            return function(array $args) use($method, $aop) {
                return call_user_func_array([$aop->getObject(), $method], $args);
            };
        };

        /**
         * 正常流程与around流程异常处理
         * @param  Exception $ex
         * @param  string    $name 方法名
         * @param  &mixed    &$return 返回值
         * @throws Exception
         */
        $exceptionHandler = function(Exception $ex, $name, &$return) use($aop) {
            $advices = $aop->getAdvices(Aop::EXCEPTION, Aop::X, $name);
            if($advices) {
                // !!! advice方法签名参数必须为引用, 参数通用引用传递，发生异常时可以修改返回值
                foreach ($advices as $advice) {
                    $advice($ex, $return);
                }
            } else {
                throw $ex;
            }
        };


        // BEFORE
        // 参数通用引用传递，请求前可以修改参数, !!! advice方法签名参数必须为引用
        $advices = $aop->getAdvices(Aop::BEFORE, Aop::X, $name);
        foreach ($advices as $advice) {
            $advice($args);
        }


        $advices = $aop->getAdvices(Aop::AROUND, Aop::X, $name);
        if($advices) {
            // AROUND
            try {
                // advice 接受三个参数
                // 1.原方法传入参数，可修改
                // 2.旧方法closure, 参数为array
                // 3.around返回值carry
                foreach ($advices as $advice) {
                    $return = $advice($args, $methodWrapper($name), $return);
                }
            } catch(Exception $ex) {
                $exceptionHandler($ex, $name, $return);
            }
        } else {
            // EXCEPTION
            try  {
                $return = call_user_func_array([$aop->getObject(), $name], $args);
            } catch(Exception $e) {
                $exceptionHandler($e, $name, $return);
            }
        }


        // AFTER
        // 参数通用引用传递，请求后可以修改返回值, !!! advice方法签名参数必须为引用
        $advices = $aop->getAdvices(Aop::AFTER, Aop::X, $name);
        foreach ($advices as $advice) {
            $advice($return);
        }

        return $return;
    }

    /**
     * 代理属性写
     * @param string 	$name
     * @param mixed 	$value
     */
    public function __set($name, $value)
    {
        $aop = ProxyPool::getAop($this);

        // BEFORE
        // !!!advice方法签名参数必须为引用, 参数通用引用传递，赋值前可修改值
        $advices = $aop->getAdvices(Aop::BEFORE, Aop::W, $name);
        foreach ($advices as $advice) {
            // FIXME class line file info
            $advice($value);
        }

        // SET
        $aop->getObject()->$name = $value;

        // AFTER
        $advices = $aop->getAdvices(Aop::AFTER, Aop::W, $name);
        foreach ($advices as $advice) {
            // FIXME class line file info
            $advice($value);
        }
    }

    /**
     * 代理属性写
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        $aop = ProxyPool::getAop($this);

        // BEFORE
        $advices = $aop->getAdvices(Aop::BEFORE, Aop::R, $name);
        foreach ($advices as $advice) {
            $advice();
        }

        // GET
        $return = $aop->getObject()->$name;

        // AFTER
        // !!! advice方法签名参数必须为引用, 参数通用引用传递，读取之后可修改返回值
        $advices = $aop->getAdvices(Aop::AFTER, Aop::R, $name);
        foreach ($advices as $advice) {
            $advice($return);
        }

        return $return;
    }
}