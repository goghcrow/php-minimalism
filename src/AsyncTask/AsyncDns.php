<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/1/31
 * Time: 下午1:40
 */

namespace Minimalism\AsyncTask;


class AsyncDns extends AsyncWithTimeout
{
    public $host;
    public $complete;

    public function __construct($host, $timeout)
    {
        $this->host = $host;
        $this->timeout = $timeout;
    }

    protected function execute()
    {
        swoole_async_dns_lookup($this->host, function($host, $ip) {
            $this->returnVal($ip);
        });
    }
}