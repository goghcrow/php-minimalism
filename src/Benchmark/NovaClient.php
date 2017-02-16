<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/16
 * Time: 下午10:33
 */

namespace Minimalism\Benchmark;


class NovaClient extends TcpClient
{
    private static $ver_mask = 0xffff0000;
    private static $ver1 = 0x80010000;

    private static $t_call  = 1;
    private static $t_reply  = 2;
    private static $t_ex  = 3;

    public function __construct(TestPlan $test, Config $conf, array $setting = [])
    {
        $setting = $setting + [
                "open_length_check" => 1,
                "package_length_type" => 'N',
                "package_length_offset" => 0,
                "package_body_offset" => 0,
                "open_nova_protocol" => 1,
            ];
        parent::__construct($test, $conf, $setting);
    }

    public function onReceive($client, $recv = null)
    {
        $this->cancelTimer();
        $this->tickRequest();

        if ($this->errno()) {
            $this->onError();
        } else {
            $this->recv = $recv;

            $r = NovaClient::unpackResponse($recv);
            if ($this->test->assert($client, $r)) {
                $this->success();
            } else {
                $this->fail();
            }

            $this->send();
        }
    }

    /**
     * @param string $recv
     * @return array
     */
    public static function unpackResponse($recv)
    {
        list($response, $attach) = self::unpackNova($recv);
        $res = $err_res = null;
        if (isset($response["error_response"])) {
            $err_res = $response["error_response"];
        } else {
            $res = $response["response"];
        }
        return [$res, $err_res, $attach];
    }

    /**
     * @param \swoole_client $client
     * @param string $service
     * @param string $method
     * @param array $args
     * @param array $attach
     * @return string
     */
    public static function packNova(\swoole_client $client, $service, $method, array $args, array $attach)
    {
        $args = self::packArgs($args);
        $thriftBin = self::packThrift($service, $method, $args);
        $attach = json_encode($attach, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $sockInfo = $client->getsockname();
        $localIp = ip2long($sockInfo["host"]);
        $localPort = $sockInfo["port"];

        $return = "";
        $ok = nova_encode("Com.Youzan.Nova.Framework.Generic.Service.GenericService", "invoke",
            $localIp, $localPort,
            nova_get_sequence(),
            $attach, $thriftBin, $return);
        assert($ok);
        return $return;
    }

    /**
     * @param string $raw
     * @return array
     */
    private static function unpackNova($raw)
    {
        $service = $method = $ip = $port = $seq = $attach = $thriftBin = null;
        $ok = nova_decode($raw, $service, $method, $ip, $port, $seq, $attach, $thriftBin);
        assert($ok);

        $attach = json_decode($attach, true, 512, JSON_BIGINT_AS_STRING);

        $response = self::unpackThrift($thriftBin);
        $response = json_decode($response, true, 512, JSON_BIGINT_AS_STRING);
        assert(json_last_error() === 0);

        return [$response, $attach];
    }


    /**
     * @param string $buf
     * @return string
     */
    private static function unpackThrift($buf)
    {
        $read = function($n) use(&$offset, $buf) {
            static $offset = 0;
            assert(strlen($buf) - $offset >= $n);
            $offset += $n;
            return substr($buf, $offset - $n, $n);
        };

        $ver1 = unpack('N', $read(4))[1];
        if ($ver1 > 0x7fffffff) {
            $ver1 = 0 - (($ver1 - 1) ^ 0xffffffff);
        }
        assert($ver1 < 0);
        $ver1 = $ver1 & self::$ver_mask;
        assert($ver1 === self::$ver1);

        $type = $ver1 & 0x000000ff;
        $len = unpack('N', $read(4))[1];
        $name = $read($len);
        $seq = unpack('N', $read(4))[1];
        assert($type !== self::$t_ex); // 不应该透传异常
        // invoke return string
        $fieldType = unpack('c', $read(1))[1];
        assert($fieldType === 11); // string
        $fieldId = unpack('n', $read(2))[1];
        assert($fieldId === 0);
        $len = unpack('N', $read(4))[1];
        $str = $read($len);
        $fieldType = unpack('c', $read(1))[1];
        assert($fieldType === 0); // stop

        return $str;
    }

    /**
     * @param array $args
     * @return string
     */
    private static function packArgs(array $args = [])
    {
        foreach ($args as $key => $arg) {
            if (is_object($arg) || is_array($arg)) {
                $args[$key] = json_encode($arg, JSON_BIGINT_AS_STRING, 512);
            } else {
                $args[$key] = strval($arg);
            }
        }
        return json_encode($args, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param string $serviceName
     * @param string $methodName
     * @param string $args
     * @return string
     */
    private static function packThrift($serviceName, $methodName, $args, $seq = 0)
    {
        // pack \Com\Youzan\Nova\Framework\Generic\Service\GenericService::invoke
        $payload = "";

        // -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
        $type = self::$t_call; // call
        $ver1 = self::$ver1 | $type;

        $payload .= pack('N', $ver1);
        $payload .= pack('N', strlen("invoke"));
        $payload .= "invoke";
        $payload .= pack('N', $seq);

        // -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
        // {{{ pack args
        $fieldId = 1;
        $fieldType = 12; // struct
        $payload .= pack('c', $fieldType); // byte
        $payload .= pack('n', $fieldId); //u16

        // -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
        // {{{ pack struct \Com\Youzan\Nova\Framework\Generic\Service\GenericRequest
        $fieldId = 1;
        $fieldType = 11; // string
        $payload .= pack('c', $fieldType);
        $payload .= pack('n', $fieldId);
        $payload .= pack('N', strlen($serviceName));
        $payload .= $serviceName;

        $fieldId = 2;
        $fieldType = 11;
        $payload .= pack('c', $fieldType);
        $payload .= pack('n', $fieldId);
        $payload .= pack('N', strlen($methodName));
        $payload .= $methodName;

        $fieldId = 3;
        $fieldType = 11;
        $payload .= pack('c', $fieldType);
        $payload .= pack('n', $fieldId);
        $payload .= pack('N', strlen($args));
        $payload .= $args;

        $payload .= pack('c', 0); // stop
        // pack struct end }}}
        // -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-

        $payload .= pack('c', 0); // stop
        // pack arg end }}}
        // -=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-

        return $payload;
    }
}