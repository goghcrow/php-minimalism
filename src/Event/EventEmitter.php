<?php

namespace Minimalism\Event;

class EventEmitter
{
    const ADD = "\0newListener";
    const REMOVE = "\0removeListener";
    const ERROR = "\0error";

    protected $eventHandlers = [];

    /**
     * @param $event
     * @param array ...$args
     * @return bool
     * @throws \Exception
     *
     * EventEmitter::emit(EventEmitter::ERROR, new \Exception());
     */
    public function emit($event, ...$args)
    {
        if (isset($this->eventHandlers[$event])) {
            foreach ($this->eventHandlers[$event] as $listener) {
                try {
                    $listener(...$args);
                } catch (\Exception $ex) {
                    if ($event === static::ERROR || !isset($this->eventHandlers[static::ERROR])) {
                        throw $ex;
                    }
                    $this->emit(static::ERROR, $event, $ex, ...$args);
                }
            }
            return true;
        } else {
            if ($event === static::ERROR) {
                assert($args[0] instanceof \Exception);
                throw $args[0];
            }
            return false;
        }
    }

    public function on($event, callable $listener, $prepend = false)
    {
        if (!isset($this->eventHandlers[$event])) {
            $this->eventHandlers[$event] = [];
        }

        // 避免递归emit Add事件, 先emit再add
        if (isset($this->eventHandlers[static::ADD])) {
            $this->emit(static::ADD, $event, $listener);
        }

        if ($prepend) {
            array_unshift($this->eventHandlers[$event], $listener);
        } else {
            $this->eventHandlers[$event][] = $listener;
        }

        return $this;
    }

    public function once($event, callable $listener, $prepend = false)
    {
        return $this->on($event, $this->onceWrap($event, $listener), $prepend);
    }

    /**
     * @param string|null $event
     * @param callable|null $listener
     * @return $this
     *
     * remove(type, listener) 移除type事件的listener
     * remove(type) 移除type事件所有listener
     * remote() 移除EventEmitter所有type的所有listener
     */
    public function remove($event = null, callable $listener = null) {
        if ($event === null) {
            assert($listener === null);
        }

        if ($listener === null) {
            $this->removeAllListeners($event);
        } else {
            $this->removeListener($event, $listener);
        }

        return $this;
    }

    private function removeListener($event, callable $listener)
    {
        if (!isset($this->eventHandlers[$event])) {
            return;
        }

        foreach ($this->eventHandlers[$event] as $key => $listener_) {
            if ($listener === $listener_) {
                unset($this->eventHandlers[$event][$key]);
                // 没有listener, 清空type, 可以使用isset(eventHandlers[type]) 方便判断
                if (empty($this->eventHandlers[$event])) {
                    unset($this->eventHandlers[$event]);
                }

                if (isset($this->eventHandlers[static::REMOVE])) {
                    $this->emit(static::REMOVE, $event, $listener);
                }
                break;
            }
        }
    }

    private function removeAllListeners($event = null)
    {
        if (isset($this->eventHandlers[static::REMOVE])) {
            if ($event === null) {
                foreach ($this->eventHandlers as $event => $_) {
                    if ($event === static::REMOVE) {
                        continue;
                    }
                    $this->removeAllListeners($event);
                }
                $this->removeAllListeners(static::REMOVE);
            } else {
                if (isset($this->eventHandlers[$event])) {
                    // LIFO order
                    $listeners = array_reverse($this->eventHandlers[$event]);
                    foreach ($listeners as $listener) {
                        $this->removeListener($event, $listener);
                    }
                }
            }
        } else {
            if ($event === null) {
                $this->eventHandlers = [];
            } else {
                unset($this->eventHandlers[$event]);
            }
        }
    }

    private function onceWrap($event, callable $listener)
    {
        return $g = function(...$args) use($event, $listener, &$g) {
            // 一次性事件自动移除
            $this->removeListener($event, $g);
            $listener(...$args);
        };
    }
}