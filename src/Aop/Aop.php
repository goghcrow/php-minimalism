<?php

namespace Minimalism\Aop;

use InvalidArgumentException;

/**
 * Class Aop
 * @package Minimalism\Aop
 *
 * 说明
 * 1. 原有类内部方法的调用与属性读写无法触发添加的advice，只能在类的client端触发
 * 2. 每个切面可以绑定多个advice，顺次触发
 * 3. 不支持静态方法的aop，不支持private方法的aop
 *
 * TODO：通过参数控制支持私有变量与方法的AOP（是否需要？）
 * 通配符情况下，Advice的参数有问题(参数类型与数量不匹配)
 * 新建一个AopPoint类，统一Advice传入的参数
 */
class Aop
{
	const BEFORE 	= 1;		    // 方法或属性读写前
	const AFTER 	= 2;		    // 方法或属性读写后
	const AROUND 	= 3;		    // 替换原方法
	const EXCEPTION = 4;		    // 方法执行触发异常

	const R = 0b100;                // public属性写
	const W = 0b10;                 // public属性写
	const X = 0b1;                  // public方法执行

    protected $events;
	protected $object;
	protected $proxyObject;
    
    public function getObject()
    {
        return $this->object;
    }

    public function getProxy()
    {
        return $this->proxyObject;
    }

    /**
     * Aop constructor.
     * @param $object
     */
	public function __construct($object)
    {
        $this->object = /*clone*/ $object;
		$this->proxyObject = new ProxyClass;
        ProxyPool::setAop($this->proxyObject, $this);
	}

	/**
	 * 方法名与属性名匹配
	 * @param  string $pattern 模式
	 * @param  string $name    属性名与方法名
	 * @return bool
	 */
	protected function match($pattern, $name)
    {
		// return preg_match($pattern, $name);
		return fnmatch($pattern, $name);
	}

	/**
	 * 添加Advice
	 * @param int   	$type    Aop类型
	 * @param int   	$rwx     RWX（RW属性读写，X方法执行）
	 * @param string   	$pattern 方法或属性名称（支持fnmatch）
	 * @param callable 	$advice  Advice
	 */
	public function addAdvice($type, $rwx, $pattern, callable $advice)
    {
		if(!$type || !$rwx || !$pattern) {
			throw new InvalidArgumentException('!$type || !$rwx || !$pattern');
		}

        $eventKey = "$type.$rwx";
        if (!isset($this->events[$eventKey])) {
            $this->events[$eventKey] = [];
        }
        if (!isset($this->events[$eventKey][$pattern])) {
            $this->events[$eventKey][$pattern] = [];
        }
        $this->events[$eventKey][$pattern][] = $advice;
	}

	/**
	 * 获取Advice列表
	 * @param  int 		$type Aop类型
	 * @param  int 		$rwx  RWX（RW属性读写，X方法执行）
	 * @param  string 	$name 具体方法或属性名称
	 * @return array
	 */
	public function getAdvices($type, $rwx, $name)
    {
		if(!$type || !$rwx || !$name) {
            throw new InvalidArgumentException('!$type || !$rwx || !$pattern');
		}

        $eventKey = "$type.$rwx";
        if (!isset($this->events[$eventKey])) {
            return [];
        }

        $ret = [];
        $patterns = $this->events[$eventKey];
        foreach($patterns as $pattern => $advices) {
            if($this->match($pattern, $name)) {
                $ret = array_merge($ret, $advices);
            }
        }
        return $ret;
    }
}