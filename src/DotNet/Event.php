<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/4/2
 * Time: 下午9:59
 */

namespace Minimalism\DotNet;


class Event
{
    /** @var static */
    private static $unObservedExceptionEvent;

    private $unObservedExceptions;
    private $eventHandlers;
    private $sender;

    /**
     * @return Event|static
     */
    public static function getUnObservedExceptionEvent()
    {
        if (self::$unObservedExceptionEvent === null) {
            self::$unObservedExceptionEvent = new static(null);
        }
        return self::$unObservedExceptionEvent;
    }

    public function __construct($sender)
    {
        $this->sender = $sender;
        $this->eventHandlers = new \SplObjectStorage();
        $this->unObservedExceptions = [];
    }

    public function registerHandler(callable $handler)
    {
        $this->eventHandlers->attach(\Closure::fromCallable($handler));
    }

    public function unRegisterHandler(callable $handler)
    {
        $this->eventHandlers->detach(\Closure::fromCallable($handler));
    }

    public function __invoke(...$args)
    {
        foreach ($this->eventHandlers as $handler) {
            try {
                /** @var \Closure $handler */
                $handler($this->sender, ...$args);
            } catch (\Throwable $t) {
                if ($unObservedExceptionEvent = self::$unObservedExceptionEvent) {
                    $unObservedExceptionEvent($this->sender, $t);
                } else {
                    $this->unObservedExceptions[] = [$this->sender, $t];
                }
            }
        }
    }

    public function __destruct()
    {
        if ($this->unObservedExceptions) {
            throw new UnObservedException($this->unObservedExceptions);
        }
    }
}

class UnObservedException extends \RuntimeException
{
    private $unObservedExceptions;

    public function __construct(array $unObservedExceptions)
    {
        $this->unObservedExceptions = $unObservedExceptions;
    }

    public function getUnObservedExceptions()
    {
        return $this->unObservedExceptions;
    }
}


class Program
{
    public $onAlarm;

    public function __construct()
    {
        $this->onAlarm = new Event($this);
    }

    public function main()
    {
        // Event本身不关系handler是否抛出异常
        // 如果抛出异常,
        // 已经注册了UnObservedExceptionHandler则会触发handler
        Event::getUnObservedExceptionEvent()->registerHandler(function($_, $senders, \Throwable $t) {
            echo $t->getMessage(), "\n";
            // 异常处理器要避免抛出异常, 否则会造成无限递归
            // throw new \Exception();
        });
        // 未绑定异常处理时, 在event对象析构时候会把异常抛出




        $this->onAlarm->registerHandler(function($sender, ...$args) {
            echo "hello\n";
        });
        $this->onAlarm->registerHandler(function($sender, ...$args) {
            echo "world\n";
        });
        $this->onAlarm->registerHandler(function($sender, ...$args) {
            throw new \Exception("exception");
        });


        $onAlarm = $this->onAlarm;
        $onAlarm();
    }
}

(new Program())->main();