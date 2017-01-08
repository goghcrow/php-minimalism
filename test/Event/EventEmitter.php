<?php

namespace Minimalism\Test\Event;


use Minimalism\Event\EventEmitter;

require __DIR__ . "/../../src/Event/EventEmitter.php";

$testPrepend = function() {
    $createListener = function($id, $data) {
        return function($recv, $len) use($id, $data) {
            assert($recv === $data);
            assert($len === strlen($data));
            echo $id;
        };
    };

    $event = new EventEmitter();

    $data = "hello world!";
    $event->on("readable", $createListener(1, $data));
    $event->on("readable", $createListener(2, $data));
    $event->on("readable", $createListener(3, $data), true);

    ob_start();
    $event->emit("readable", $data, strlen($data));
    assert(ob_get_clean() === "312");
};

$testOnce = function() {
    $event = new EventEmitter();

    $event->on("event", function() {
        echo 1;
    });

    ob_start();
    $event->emit("event");
    $event->emit("event");
    assert(ob_get_clean() === "11");

    // ==================================

    $event->once("another", function() {
        echo 2;
    });

    $event->on(EventEmitter::REMOVE, function($event) {
        echo "rm_$event|";
    });

    // once 事件首先自动移除自己, 然后出发事件
    ob_start();
    $event->emit("another");
    $event->emit("another");
    assert(ob_get_clean() === "rm_another|2");

    // ==================================

    $event->once("event", function() {
        echo 3;
    });

    ob_start();
    $event->emit("event");
    $event->emit("event");
    assert(ob_get_clean() === "1rm_event|31");
};


$testHookAdd = function() {
    $event = new EventEmitter();
    $event->on(EventEmitter::ADD, function($event, callable $listener) {
        echo "add$event|";
    });

    $createCb = function($id) {
        return function() use($id) {
            echo $id;
        };
    };

    ob_start();
    $event->on("event1", $createCb(1));
    $event->on("event1", $createCb(2));
    $event->emit("event1");
    assert(ob_get_clean() === "addevent1|addevent1|12");
};


class Invoker
{
    public $id;
    public function __construct($id)
    {
        $this->id = $id;
    }

    public function __invoke()
    {
    }
}

$testRemoveAndHookRemove = function() {
    $event = new EventEmitter();

    $createCb = function($id) {
        return function() use($id) {
            echo $id;
        };
    };

    $event->on(EventEmitter::REMOVE, function($event, callable $listener) {
        echo "rm_$event|";
    });

    $event->on("event1", $cb1 = $createCb(1));
    $event->on("event1", $cb2 = $createCb(2));
    $event->on("event1", $cb3 = $createCb(3));

    ob_start();
    $event->remove("event1", $cb2);
    $event->emit("event1");
    assert(ob_get_clean() === "rm_event1|13");


    $event = new EventEmitter();
    $event->on(EventEmitter::REMOVE, function($event, callable $listener) {
        if ($listener instanceof Invoker) {
            echo "rm_{$event}_{$listener->id}|";
        }
    });

    $event->on("event1", new Invoker(1));
    $event->on("event1", new Invoker(2));
    $event->on("event2", new Invoker(3));
    $event->on("event2", new Invoker(4));
    $event->on("event3", new Invoker(5));

    // remove 时候按照 LIFO顺序进行
    ob_start();
    $event->remove("event1");
    assert(ob_get_clean() === "rm_event1_2|rm_event1_1|");

    ob_start();
    $event->remove();
    assert(ob_get_clean() === "rm_event2_4|rm_event2_3|rm_event3_5|");
};


$testOnException = function() {
    $event = new EventEmitter();

    $arg1 = "arg1";
    $arg2 = "arg2";
    $ex = new \Exception("");
    $event->on(EventEmitter::ERROR, $onEx = function($event, \Exception $exception, ...$args)
            use($ex, $arg1, $arg2) {
        assert($event === "testEx");
        assert($exception === $ex);
        assert($args[0] === $arg1);
        assert($args[1] === $arg2);
    });

    $event->on("testEx", function($arg1, $arg2) use($ex) {
        throw $ex;
    });
    $event->emit("testEx", $arg1, $arg2);

    $event->remove(EventEmitter::ERROR, $onEx);

    try {
        $event->emit("testEx", $arg1, $arg2);
        assert(false);
    } catch (\Exception $ex) {
        assert($ex);
    }
};

$testEmitError = function() {
    $event = new EventEmitter();
    try {
        $event->emit(EventEmitter::ERROR, new \Exception());
        assert(false);
    } catch (\Exception $ex) {
        assert($ex);
    }

    $event->on(EventEmitter::ERROR, function(\Exception $ex) {});
    $event->emit(EventEmitter::ERROR, new \Exception());
};




$testPrepend();
$testOnce();
$testHookAdd();
$testRemoveAndHookRemove();
$testOnException();
$testEmitError();



$example1 = function() {
    $event = new EventEmitter();
    $event->on("message", function($msg) {
        echo "$msg\n";
    });

    swoole_timer_tick(500, function() use($event) {
        $event->emit("message", "time: " . microtime(true));
    });
};


$exampleOnce = function() {
    // once api
    $event = new EventEmitter();
    $event->once("once1", function() {
        echo "once1\n";
    });
    $event->emit("once1");
    $event->emit("once1");


    // remove 模拟once
    $event = new EventEmitter();
    $event->on("once2", $f = function() use($event, &$f) {
        echo "once2\n";
        $event->remove("once2", $f);
    });
    $event->emit("once2");
    $event->emit("once2");
};



//$example1();
$exampleOnce();