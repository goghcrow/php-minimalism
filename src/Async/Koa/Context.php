<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/26
 * Time: 上午1:34
 */

namespace Minimalism\Async\Koa;


class Context
{
    public $request;
    public $response;

    /** @var Application */
    public $app;
    /** @var Request */
    public $req;
    /** @var Response */
    public $res;

    public function inspect()
    {
        return $this->toJSON();
    }

    public function toJSON()
    {
        return [
//            "request": this.request.toJSON(),
//      "response": this.response.toJSON(),
//      "app": this.app.toJSON(),
//      "originalUrl": this.originalUrl,
//      "req": '<original node req>',
//      "res": '<original node res>',
//      "socket": '<original node socket>'
        ];
    }
}