<?php

namespace Minimalism\DI;

use Closure;
use ReflectionMethod;
use ReflectionClass;
use RuntimeException;

/**
 * class DI : Constructor Injection Container
 *
 * 主要实现基于类构造函数实现依赖注入的容易
 * [不负责autoload]
 * [配置规则]
 * 1. 可以通过构造函数配置, 也可以通过数组访问配置
 * 2. 接口对应的实现需要配置
 * 3. 虚类对应的可实例化类也需要配置
 * 4. 普通类不需要配置
 * 5. 普通变量(标量,数组,对象实例)也不需要配置
 * 6. 嵌套依赖会自动通过构造函数注入
 * [使用方式参见example]
 */
class DI
{

    /**
     * 依赖映射容器
     * key => [className|interface]::class | any other string
     * value => mixed
     * @var array
     */
    protected $dependenciesMap;

    /**
     * 被声明为单例模式的className::class
     * @var array
     */
    protected $singletonClassNames;

    /**
     * 已实例化对象容器
     * k => className::class
     * v => new ClassName(...$args)
     * @var array
     */
    protected $instancesMap;

    /**
     * DI constructor.
     * @param array $diConf
     *
     * 配置:
     * 1. not_class && not_interface  => mixed
     * 普通配置无类型, 依赖通过参数名称注入
     * 2. interface => class impl interface
     * 3. parent class => class
     * 4. abstract class => class
     */
    public function __construct(array $diConf) {
        $this->instancesMap = [];
        $this->init($diConf);
    }

    protected function init(array $diConf) {
        if (empty($diConf)) {
            return;
        }

        foreach ($diConf as $contract => $impl) {
            if (is_array($impl) && isset($impl["class"])) {
                $class = $impl["class"];
                $this->dependenciesMap[$contract] = $class;

                if (isset($impl["single"]) && $impl["single"]) {
                    $this->singletonClassNames[$class] = true;
                }
            } else {
                $this->dependenciesMap[$contract] = $impl;
            }
        }
    }

    /**
     * 自动注入Closure参数,然后执行
     * @param Closure $closure
     * @return mixed
     */
    public function __invoke(Closure $closure) {
        $method = new ReflectionMethod($closure, "__invoke");
        $arguments = $this->getArguments($method);
        return call_user_func_array($closure, $arguments);
    }

    /**
     * @param $key
     * @return mixed
     */
    public function make($key) {
        if (class_exists($key, true) || interface_exists($key, true)) {
            return $this->instance(new ReflectionClass($key));
        } else if (isset($this->dependenciesMap[$key])){
            return $this->dependenciesMap[$key];
        }
        throw new DIException("Can not make Key '{$key}");
    }

    /**
     * 将callable参数注入后 返回一个无参闭包对象
     * @param callable $callable
     * @return Closure
     */
    public function inject(callable $callable) {
        try {
            $closure = \Minimalism\DI\Closure::fromCallable($callable);
        } catch (ClosureException $ex) {
            throw new DIException($ex->getMessage(), $ex->getCode());
        }

        $method = new ReflectionMethod($closure, "__invoke");
        $arguments = $this->getArguments($method);
        return function() use($closure, $arguments) {
            return call_user_func_array($closure, $arguments);
        };
    }

    /**
     * 根据配置,获取反射方法实参
     * @param ReflectionMethod $method
     * @param [] $circleCheck
     * @return array
     */
    protected function getArguments(ReflectionMethod $method, $circleCheck = []) {
        $arguments = [];
        foreach($method->getParameters() as $parameter) {
            // TODO: php7 if($parameter->hasType()) { $reflectionType = $parameter->getType(); }
            $parameterClazz = $parameter->getClass();
            if ($parameterClazz != null) {
                // 有类型提示 function(TypeHint $para, ...), 根据TypeHint查找依赖
                $arguments[] = $this->instance($parameterClazz, $circleCheck);
            } else {
                // 无类型提示 function($argName) 根据实参变量名"argName"查找依赖
                $name = $parameter->name;
                if (!isset($this->dependenciesMap[$name])) {
                    throw new DIException("ParameterName \${$name} Not Found");
                }
                $arguments[] = $this->dependenciesMap[$name];
            }
        }
        return $arguments;
    }

    /**
     * @param string $depClassName
     * @param array $toCheck
     */
    private function circleDependencyCheck($depClassName, &$toCheck) {
        if (in_array($depClassName, $toCheck, true)) {
            $toCheck[] = $depClassName;
            $path = implode(" -> ", $toCheck);
            throw new DICircleDependencyException("Found Circle Dependency In Path $path");
        } else {
            $toCheck[] = $depClassName;
        }
    }

    /**
     * 根据反射类构造函数自动注入依赖实例化
     * @param ReflectionClass $clazz
     * @param array $circleCheck
     * @return object
     */
    protected function _instanceNew(ReflectionClass $clazz, &$circleCheck) {
        if (!$clazz->isInstantiable()) {
            throw new DIException("Cannot instantiate class {$clazz->name}");
        }
        $this->circleDependencyCheck($clazz->getName(), $circleCheck);

        $ctorMethod = $clazz->getConstructor();
        if ($ctorMethod !== null) {
            return $clazz->newInstanceArgs($this->getArguments($ctorMethod, $circleCheck));
        } else {
            return $clazz->newInstanceWithoutConstructor();
        }
    }

    /**
     * 单例模式实例化对象
     * @param ReflectionClass $clazz
     * @param $circleCheck
     * @return object
     */
    protected function _instanceOnce(ReflectionClass $clazz, &$circleCheck) {
        $name = $clazz->name;
        if (!isset($this->instancesMap[$name])) {
            $this->instancesMap[$name] = $this->_instanceNew($clazz, $circleCheck);
        }
        return $this->instancesMap[$name];
    }

    /**
     * 根据配置自动实例化对象
     * @param ReflectionClass $clazz
     * @param array $circleCheck
     * @return object
     */
    protected function _instance(ReflectionClass $clazz, &$circleCheck) {
        $isSingleton = isset($this->singletonClassNames[$clazz->name]);
        if ($isSingleton) {
            return $this->_instanceOnce($clazz, $circleCheck);
        } else {
            return $this->_instanceNew($clazz, $circleCheck);
        }
    }

    /**
     * 根据配置,从接口或者类获取依赖对象
     * @param ReflectionClass $parameterClazz
     * @param array $circleCheck
     * @return object
     */
    protected function instance(ReflectionClass $parameterClazz, &$circleCheck = []) {
        $clazzName = $parameterClazz->name;
        if ($parameterClazz->isInterface()) {
            if (!isset($this->dependenciesMap[$clazzName])) {
                throw new DIException("Interface \"{$clazzName}\" Implements Class Not Found");
            }
            // 处理多态
            $implementedClazzName = (string) $this->dependenciesMap[$clazzName];
            if (!class_exists($implementedClazzName, true)) {
                throw new DIException("Interface \"{$clazzName}\" Implements Class \"{$implementedClazzName}\" Not Found");
            }
            if (!is_subclass_of($implementedClazzName, $clazzName)) {
                throw new DIException("{$implementedClazzName} Does Not Implements {$clazzName}");
            }
            return $this->_instance(new ReflectionClass($implementedClazzName), $circleCheck);
        } else {
            // 处理多态
            if (isset($this->dependenciesMap[$clazzName])) {
                $subClazzName = (string) $this->dependenciesMap[$clazzName];
                // 类不存在 is_subclass_of 返回 false
                if (!is_subclass_of($subClazzName, $clazzName)) {
                    throw new DIException("{$subClazzName} Is Not SubClass Of {$clazzName}");
                }
                return $this->_instance(new ReflectionClass($subClazzName), $circleCheck);
            } else {
                return $this->_instance($parameterClazz, $circleCheck);
            }
        }
    }
}

class DIException extends RuntimeException {}
class DICircleDependencyException extends RuntimeException {}