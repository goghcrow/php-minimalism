<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/19
 * Time: 下午8:15
 */

namespace Minimalism\A\Core\dev;

// @see http://tech.meituan.com/promise-insight.html
// @see https://developer.mozilla.org/zh-CN/docs/Web/JavaScript/Reference/Global_Objects/Promise
// @see http://wiki.commonjs.org/wiki/Promises/A
// @see https://www.promisejs.org/
// @see http://javascript.ruanyifeng.com/advanced/promise.html




// @see https://wohugb.gitbooks.io/promise/content/what/indexmd.html

//use Minimalism\A\Core\AsyncTask;

require __DIR__ . "/../../../../vendor/autoload.php";


// @see API 参照这个来实现 https://developer.mozilla.org/zh-CN/docs/Web/JavaScript/Reference/Global_Objects/Promise

/**
 * Class Promise
 * @package Minimalism\A\Core\dev
 * @see https://developer.mozilla.org/zh-CN/docs/Web/JavaScript/Reference/Global_Objects/Promise
 */
class Promise
{
    const PENDING = 1;
    const RESOLVED = 2;
    const REJECTED = 3;

    public $state;
    public $fun;

    public $value;
    public $exception;

//    public $deferreds;
    public $onFulfilled;
    public $onRejected;


    public function __construct(callable $fun)
    {
        $this->fun = $fun;
        $this->state = self::PENDING;
//        $this->deferred = [];
        $this(...Helper::only([$this, "resolve"], [$this, "reject"]));
    }

    public function __invoke(callable $resolve, callable $reject)
    {
        $fun = $this->fun;
        $fun($reject, $reject);
    }

    public function all()
    {

    }

    public function race()
    {

    }

    /**
     * Continuation
     * @param $value
     */
    public function resolve($value)
    {
        // 强制转换为异步
        swoole_event_defer(function() use($value) {
            // call once, don't check state
            $this->state = self::RESOLVED;
            $this->value = $value;
            $this->checkState();
        });
    }

    /**
     * Continuation
     * @param \Exception $ex
     */
    public function reject(\Exception $ex)
    {
        // 强制转换为异步
        swoole_event_defer(function() use($ex) {
            // call once, don't check state
            $this->state = self::REJECTED;
            $this->exception = $ex;
            $this->checkState();
        });
    }

    /**
     * @param callable $onFulfilled User-Continuation
     * @param callable|null $onRejected User-Continuation
     * @return static
     */
    public function then(callable $onFulfilled, callable $onRejected = null)
    {
        return new static(function(callable $resolve, callable $reject) use($onFulfilled, $onRejected) {
//            $this->deferred[] =
            $this->onFulfilled = Helper::once(function($value) use($onFulfilled, $resolve) {
                // TODO $onFulfilled
                return $resolve($value);
            });
            if ($onRejected) {
                $this->onRejected = Helper::once(function(\Exception $ex) use($onRejected, $reject) {
                    return $reject($ex);
                });
            }
            $this->checkState();
        });
    }

    public function catch_(callable $onRejected)
    {
//        $this->onRejected = Helper::once($onRejected);
//        $this->checkState();
//        return $this;
    }

    private function checkState()
    {
        if ($this->state === self::PENDING) {
            return;
        } else if ($this->state === self::RESOLVED) {
            foreach ($this->deferreds as $resolve) {
                $resolve($this->value);
            }
            if ($this->onFulfilled) {
                $resolve = $this->onFulfilled;
                $resolve($this->value);
            }
        } else if ($this->state === self::REJECTED) {
            if ($this->onRejected) {
                $reject = $this->onRejected;
                $reject($this->exception);
            }
        } else {
            assert(false);
        }
    }
}


function dns($host)
{
    return new Promise(function($resolve, $_) use($host) {
        swoole_async_dns_lookup($host, function($_, $ip) use($resolve) {
           $resolve($ip);
        });
    });
}

function t4()
{
    $promise = dns("www.baidu.com");
    $promise->then(function($r) {
        echo $r, "\n";
        return dns("www.jd.com");
    })->then(function($r) {
        echo $r, "\n";
        return dns("www.bing.com");
    })->then(function($r) {
        echo $r, "\n";
    })->catch_(function(\Exception $ex) {
        echo $ex, "\n";
    });
}

t4();
exit;




function t1($t)
{
    $promise = dns_lookup("www.baidu.com", $t);
    $promise->then(function($r) {
        echo $r, "\n";
    })->catch_(function(\Exception $ex) {
        echo $ex, "\n";
    });
}

function t2($t)
{
    $promise = dns_lookup("www.baidu.com", $t);
    $promise->then(function($r) {
        echo $r, "\n";
    }, function(\Exception $ex) {
        echo $ex, "\n";
    });
}
//t2(1);
//t2(100);

function t3($t)
{
    $promise = dns_lookup("www.baidu.com", $t);
    $promise->catch_(function(\Exception $ex) {
        echo $ex, "\n";
    });
}

//t3(100);
//t3(1);



function dns_lookup($host, $timeout)
{
    return new Promise(function($resolve, $reject) use($host, $timeout) {
        $timer = null;
        $r = swoole_async_dns_lookup($host, function($host, $ip) use($resolve, $reject, &$timer) {
            if ($timer) {
                swoole_timer_clear($timer);
                $resolve($ip);
            }
        });

        if ($r) {
            $timer = swoole_timer_after($timeout, function() use($reject, &$timer) {
                $reject(new \Exception("dns lookup timeout"));
                $timer = null;
            });
        } else {
            $reject(new \Exception("dns lookup fail"));
        }

    });
}
