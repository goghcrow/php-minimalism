<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/3/5
 * Time: 上午2:09
 */

namespace Minimalism\A\Server\Http\Exception;


class HttpException extends \Exception
{
    /** @var bool 是否对外暴露错误 */
    public $expose = false;

    public $status;

    public function __construct($message, $code, \Exception $previous)
    {
        parent::__construct($message, $code, $previous);
    }
}