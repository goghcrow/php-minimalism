<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/4/2
 * Time: 下午10:27
 */

namespace Minimalism\A\Core;


/**
 * Class TaskStatus
 * @package Minimalism\A\Core
 */
final class TaskStatus
{
    // The task has been initialized but has not yet been scheduled.
    const Created = 0;

    // The task is running but has not yet completed.
    const Running = 1;

    // The task has finished executing and is implicitly waiting for attached child tasks to complete.
    const WaitingForChildrenToComplete = 2;

    // The task completed execution successfully.
    const RanToCompletion = 3;

    // The task completed due to an unhandled exception.
    const Faulted = 4;

    // The task acknowledged cancellation by throwing an OperationCanceledException with its own CancellationToken while the token was in signaled state, or the task's CancellationToken was already signaled before the task started executing. For more information, see Task Cancellation.
    const Canceled = 5;

    const WaitingForContinue = 6;

    public static function getName($status)
    {
        static $map;
        if ($map === null) {
            $clazz = new \ReflectionClass(self::class);
            $map = array_flip($clazz->getConstants());
        }

        if (isset($map[$status])) {
            return $map[$status];
        } else {
            return "Unknown";
        }
    }

    public static function isEnd($status)
    {
        return in_array($status, [
            self::RanToCompletion,
            self::Faulted,
            self::Canceled,
        ], true);
    }
}