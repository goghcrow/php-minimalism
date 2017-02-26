<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/27
 * Time: 上午12:01
 */

namespace Minimalism\A\Client;


use function Minimalism\A\Core\callcc;

class SwooleHttpClient extends \swoole_http_client
{
    public function awaitGet($uri, $timeout = 1000)
    {
        return callcc(function($k) use($uri) {
            $this->get($uri, $k);
        }, $timeout);
    }

    public function awaitPost($uri, $post, $timeout = 1000)
    {
        return callcc(function($k) use($uri, $post) {
            $this->post($uri, $post, $k);
        }, $timeout);
    }

    public function awaitExecute($uri, $timeout = 1000)
    {
        return callcc(function($k) use($uri) {
            $this->execute($uri, $k);
        }, $timeout);
    }
}