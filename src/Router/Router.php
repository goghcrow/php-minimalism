<?php
/**
 * User: xiaofeng
 * Date: 2016/6/14
 * Time: 17:03
 *
 * 依照radix tree的思路
 * 简化为按"/"分隔创建路由树节点,且树无压缩
 * 仅仅支持:name通配符
 * 路由仅仅匹配一个结果,受路由注册顺序影响
 *
 */

namespace Minimalism\Router;

use BadMethodCallException;
use InvalidArgumentException;

/**
 * Class Route
 * @package xiaofeng
 *
 * ["GET", "POST", "PUT", "PATCH", "DELETE", "TRACE", "CONNECT", "OPTIONS", "HEAD"];
 * @method request(string $path, mixed $handler)
 * @method get(string $path, mixed $handler)
 * @method post(string $path, mixed $handler)
 * @method put(string $path, mixed $handler)
 * @method patch(string $path, mixed $handler)
 * @method delete(string $path, mixed $handler)
 * @method trace(string $path, mixed $handler)
 * @method connect(string $path, mixed $handler)
 * @method options(string $path, mixed $handler)
 * @method head(string $path, mixed $handler)
 */
class Router
{
    protected $dummyNode;

    public function __construct() {
        $this->dummyNode = new _Node;
    }

    /**
     * @param string $httpMethod
     * @param string $path
     * @param mixed $handler
     */
    protected function register($httpMethod, $path, $handler)
    {
        if (!$path) return;
        $path = ltrim(strval($path));
        if ($path[0] !== "/") {
            $path = "/$path";
        }
        $pathArr = explode("/", rtrim($path, "/"));
        $pathArr[0] = "/";
        $finalSegment = array_pop($pathArr);
        $node = $this->dummyNode;
        foreach ($pathArr as $segment) {
            $node = $node->addChild($segment);
        }
        $node->addChild($finalSegment, $httpMethod, $handler);
    }

    /**
     * @param _Node $node
     * @param array $pathArr
     * @param array $args
     * @return bool|_Node
     */
    protected function matchNode(_Node $node, array &$pathArr, &$args)
    {

        while ($node->children && $pathArr) {
            $segment = array_pop($pathArr);
            $matchedNodes = [];

            // 子节点不包含通配符，简化处理
            if (!$node->hasWildcardChild) {
                if (isset($node->children[$segment])) {
                    $matchedNode = $node->children[$segment];
                    $matchResult = $this->matchNode($matchedNode, $pathArr, $args);
                    // 实际上是 递归过程该方法最后return的$node
                    if ($matchResult === false) {
                        return false;
                    }
                    return $matchResult;
                } else {
                    $pathArr[] = $segment;
                    return false;
                }
            }

            // 子节点包含通配符情况
            foreach ($node->children as $childSegment => $childNode) {
                /* @var $childNode _Node */
                if ($childNode->isWildcard) {
                    // 因为优先遍历通配符,但是符合通配符的路径不一定后续完全匹配
                    // 所以延迟参数的绑定
                    // TODO 验证参数类型
                    $matchedNodes[] = [$childNode, $childNode->parameter, $segment];
                } else if ($childSegment === $segment) {
                    $matchedNodes[] = [$childNode, null, null];
                }
            }

            if (!$matchedNodes) {
                $pathArr[] = $segment;
                return false;
            }

            foreach ($matchedNodes as list($matchedNode, $parameter, $argument)) {
                /* @var $matchedNode _Node */
                $matchResult = $this->matchNode($matchedNode, $pathArr, $args);
                // 实际上是 递归过程该方法最后return的$node
                if ($matchResult !== false) {
                    // 命中分支含有变量,此处进行绑定
                    if ($parameter !== null) {
                        $args[$parameter] = $argument;
                    }
                    return $matchResult;
                }
            }

            return false;
        }

        // 返回树状路由表最后遍历的节点
        return $node;
    }
    
    public function dispatch($httpMethod, $uri)
    {
        $httpMethod = strtoupper($httpMethod);

        if ($uri === "/" || $uri === "") {
            $pathArr = ["/"];
        } else {
            $pathArr = array_reverse(explode("/", trim(explode("?", $uri)[0], "/")));
            $pathArr[] = "/";
        }

        // 按注册顺序优先匹配通配符, 大小写敏感
        $args = [];
        $node = $this->matchNode($this->dummyNode, $pathArr, $args);
        // $pathArr 未消耗完
        if ($pathArr) {
            return false;
        }

        // 当前节点有handler且方法匹配, 方法未定义,默认匹配
        if ($node->handlers) {
            // 优先获取固定方法
            if (isset($node->handlers[$httpMethod])) {
                return [$node->handlers[$httpMethod], $args];
            }
            // 否则获取request匹配全部方法
            if (isset($node->handlers["REQUEST"])) {
                return [$node->handlers["REQUEST"], $args];
            }
        }

        return false;
    }

    public function __call($httpMethod, $arguments)
    {
        // 最后一个request是用来适用所有方法的
        static $methods = ["GET", "POST", "PUT", "PATCH", "DELETE", "TRACE", "CONNECT", "OPTIONS", "HEAD", "REQUEST"];
        $httpMethod = strtoupper($httpMethod);
        if (!in_array($httpMethod, $methods, true)) {
            throw new BadMethodCallException("Not Supprot HttpMethod {$httpMethod}");
        }
        if (count($arguments) !== 2) {
            throw new InvalidArgumentException("Call $httpMethod Needs Arguments (\$path[ ,\$handler])");
        }
        $this->register($httpMethod, $arguments[0], $arguments[1]);
    }
}


/**
 * Class Node
 * @access private
 * @package xiaofeng
 */
class _Node
{
    /* @var $children array (string => static) */
    public $children = [];
    public $hasWildcardChild = false;

    /* @var $handlers array (httpMethod => handlers) */
    public $handlers = [];
    public $isWildcard = false;
    public $parameter;

    public function addChild($segment, $httpMethod = null, $handler = null)
    {
        $isWildcard = $segment[0] === ":";
        if (!isset($this->children[$segment])) {
            $this->children[$segment] = new static;
        }
        $node = $this->children[$segment];

        // 节点可能已经附带handler, 不应该覆盖
        if (!isset($node->handlers[$httpMethod])) {
            $node->isWildcard = $isWildcard;
            if ($isWildcard) {
                $node->parameter = substr($segment, 1);
                $this->hasWildcardChild = true;
            }
            $node->handlers[$httpMethod] = $handler;
        }

        return $node;
    }
}