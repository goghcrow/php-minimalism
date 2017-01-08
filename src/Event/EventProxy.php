<?php

namespace Minimalism\Event;

/**
 * Class EventProxy
 * @package Event
 *
 * TODO: https://github.com/JacksonTian/eventproxy/blob/master/lib/eventproxy.js
 */
class EventProxy extends EventEmitter
{
    const ALL = "\0all";

    public function emit($event, ...$args)
    {
        $ret = parent::emit($event, ...$args);
        parent::emit(static::ALL, $event);
        return $ret;
    }

    public function throwEx(/*$event, */\Exception $ex)
    {
        return $this->emit(static::ERROR, $ex);
    }

    /**
     * all once
     * @param array $events
     * @param callable $listener
     * @return $this
     */
    public function all(array $events, callable $listener)
    {
        $result = [];
        $times = 0;
        $eventCount = count($events);

        if ($eventCount === 0) {
            return $this;
        }

        foreach ($events as $event) {
            $this->once($event, function(...$args) use(&$result, &$times, $event) {
                $result[$event] = $args;
                $times++;
            });
        }

        $this->on(static::ALL, function($event) use(&$result, &$times, $eventCount, $listener) {
            if ($times < $eventCount || !isset($result[$event])) {
                return;
            }
            $this->remove(static::ALL);
            $listener($result);
        });

        return $this;
    }

    /**
     * any once
     * @param callable $listener
     * @param array ...$events
     * @return $this
     */
    public function any(callable $listener, ...$events)
    {
        if (empty($events)) {
            return $this;
        }

        $anyEvent = "\0" . implode("_", $events);
        $this->once($anyEvent, $listener);

        foreach ($events as $event) {
            $this->once($event, function(...$args) use($anyEvent, $event) {
                $this->emit($anyEvent, $event, ...$args);
            });
        }

        return $this;
    }
}