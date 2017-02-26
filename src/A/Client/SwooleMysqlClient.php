<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/27
 * Time: 上午12:02
 */

namespace Minimalism\A\Client;


use Minimalism\A\Client\Exception\MysqlClientException;
use function Minimalism\A\Core\callcc;

class SwooleMysqlClient extends \swoole_mysql
{
    public function __construct()
    {
        parent::__construct();
    }

    public function awaitConnect(array $conf, $timeout = 1000)
    {
        return callcc(function($k) use($conf) {
            $this->on("connect", $this->cc($k));
            $this->connect($conf);
        }, $timeout);
    }

    public function awaitQuery($sql, array $bind = [], $timeout = 1000)
    {
        return callcc(function($k) use($sql, $bind) {
            parent::query($sql, $bind, $this->cc($k));
        }, $timeout);
    }

    private function cc($k)
    {
        return function(\swoole_mysql $mysql, $r = null) use($k) {
            if ($this->errno) {
                $k(null, new MysqlClientException($this->error, $this->errno));
            } else {
                $k($r);
            }
        };
    }
}