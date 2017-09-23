<?php

namespace Minimalism\Event;

// Node.js EventEmmit

class EventEmitter
{
    use EventEmitterTrait;

    const ADD = __CLASS__ . ".newListener";
    const REMOVE = __CLASS__ . ".removeListener";
    const ERROR = __CLASS__ . ".error";
}


trait EventEmitterTrait
{
    protected $eventHandlers = [];

    /**
     * @param $event
     * @param array ...$args
     * @return bool
     * @throws \Throwable|\Exception
     *
     * EventEmitter::emit(EventEmitter::ERROR, new \Exception());
     */
    public function emit($event, ...$args)
    {
        if ($event === null) {
            return false;
        }

        if (isset($this->eventHandlers[$event])) {
            foreach ($this->eventHandlers[$event] as $listener) {
                try {
                    $listener(...$args);
                    continue;
                    // 兼容PHP7&PHP5
                } catch (\Throwable $ex) {
                } catch (\Exception $ex) {}
                if ($event === EventEmitter::ERROR || !isset($this->eventHandlers[EventEmitter::ERROR])) {
                    throw $ex;
                }
                $this->emit(EventEmitter::ERROR, $event, $ex, ...$args);
            }
            return true;
        } else {
            if ($event === EventEmitter::ERROR) {
                if (isset($args[0]) && ($args[0] instanceof \Throwable || $args[0] instanceof \Exception)) {
                    throw $args[0];
                }
            }
            return false;
        }
    }

    public function on($event, callable $listener, $prepend = false)
    {
        if ($event === null) {
            return false;
        }

        if (!isset($this->eventHandlers[$event])) {
            $this->eventHandlers[$event] = [];
        }

        // 避免递归emit Add事件, 先emit再add
        if (isset($this->eventHandlers[EventEmitter::ADD])) {
            $this->emit(EventEmitter::ADD, $event, $listener);
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

        $index = array_search($listener, $this->eventHandlers[$event], true);
        if ($index !== false) {
            unset($this->eventHandlers[$event][$index]);
            if (empty($this->eventHandlers[$event])) {
                unset($this->eventHandlers[$event]);
            }

            if (isset($this->eventHandlers[EventEmitter::REMOVE])) {
                $this->emit(EventEmitter::REMOVE, $event, $listener);
            }
        }

//        foreach ($this->eventHandlers[$event] as $key => $listener_) {
//            if ($listener === $listener_) {
//                unset($this->eventHandlers[$event][$key]);
//                // 没有listener, 清空type, 可以使用isset(eventHandlers[type]) 方便判断
//                if (empty($this->eventHandlers[$event])) {
//                    unset($this->eventHandlers[$event]);
//                }
//
//                if (isset($this->eventHandlers[EventEmitter::REMOVE])) {
//                    $this->emit(EventEmitter::REMOVE, $event, $listener);
//                }
//                break;
//            }
//        }
    }

    private function removeAllListeners($event = null)
    {
        if (isset($this->eventHandlers[EventEmitter::REMOVE])) {
            if ($event === null) {
                foreach ($this->eventHandlers as $event => $_) {
                    if ($event === EventEmitter::REMOVE) {
                        continue;
                    }
                    $this->removeAllListeners($event);
                }
                $this->removeAllListeners(EventEmitter::REMOVE);
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