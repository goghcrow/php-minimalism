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
    public $expose = true;

    /** @var int http错误状态码, 分离异常码与http status */
    public $status = 500;

    public function __construct($status, $message, $code = 0, $previous = null)
    {
        $this->status = $status;
        parent::__construct($message, $code, $previous);
    }
}