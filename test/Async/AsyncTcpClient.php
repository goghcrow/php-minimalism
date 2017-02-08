<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/9
 * Time: 上午1:09
 */

namespace Minimalism\Test\AsyncTask;


use Minimalism\Async\Async;
use Minimalism\Async\AsyncTcpClient;

require __DIR__ . "/../../vendor/autoload.php";

Async::exec(function() {

    start:
    try {
        $cli = new AsyncTcpClient();
        $ip = (yield Async::dns("www.baidu.com"));
        yield $cli->connect($ip, 80);

        loop:
        $recv = (yield $cli->send(<<<HTTP
GET / HTTP/1.1
Host: www.baidu.com
Connection: keep-alive
User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36


HTTP
    ));
        echo "\n";

        goto loop;

        $recv1 = (yield);
        echo $recv1,"\n";

    } catch (\Exception $ex) {
        echo $ex;
        goto start;
    } finally {

    }
});