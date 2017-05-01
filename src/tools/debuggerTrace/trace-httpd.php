<?php

namespace Minimalism\DebuggerTrace;


$port = isset($argv[1]) ? intval($argv[1]) : 7777;

$serv = new TraceServer($port);
$serv->start();

class TraceServer
{
    const KEY = "X-Trace-Callback";

    public $localIp;
    public $port;

    /**
     * @var \swoole_http_server
     */
    public $traceServer;

    public $fds = [];

    public function __construct($port = 7777)
    {
        $this->localIp = gethostbyname(gethostname());
        $this->port = $port;
        $this->traceServer = new \swoole_websocket_server("0.0.0.0", $port, SWOOLE_BASE);

        $this->traceServer->set([
            // 'log_file' => __DIR__ . '/trace.log',
            // 'buffer_output_size' => 1024 * 1024 * 1024,
            // 'pipe_buffer_size' => 1024 * 1024 * 1024,
            // 'max_connection' => 100,
            // 'max_request' => 100000,
            'dispatch_mode' => 3,
            'open_tcp_nodelay' => 1,
            'open_cpu_affinity' => 1,
            'daemonize' => 0,
            'reactor_num' => 1,
            'worker_num' => 1,
        ]);
    }

    public function start()
    {
        $this->traceServer->on('start', [$this, 'onStart']);
        $this->traceServer->on('shutdown', [$this, 'onShutdown']);

        $this->traceServer->on('workerStart', [$this, 'onWorkerStart']);
        $this->traceServer->on('workerStop', [$this, 'onWorkerStop']);
        $this->traceServer->on('workerError', [$this, 'onWorkerError']);

        $this->traceServer->on('connect', [$this, 'onConnect']);
        $this->traceServer->on('request', [$this, 'onRequest']);

        $this->traceServer->on('open', [$this, 'onOpen']);
        $this->traceServer->on('handshake', [$this, 'onHandshake']);
        $this->traceServer->on('message', [$this, 'onMessage']);

        $this->traceServer->on('close', [$this, 'onClose']);

        sys_echo("server starting {$this->localIp}:{$this->port}");
        $this->traceServer->start();
    }

    public function onStart(\swoole_websocket_server $server) { sys_echo("server starting ......"); }
    public function onShutdown(\swoole_websocket_server $server) { sys_echo("server shutdown ....."); }
    public function onConnect() { }

    public function onWorkerStart(\swoole_websocket_server $server, $workerId)
    {
        $_SERVER["WORKER_ID"] = $workerId;
        sys_echo("worker #$workerId starting .....");
        $this->startHeartbeat();
    }
    public function onWorkerStop(\swoole_websocket_server $server, $workerId) { }
    public function onWorkerError(\swoole_websocket_server $server, $workerId, $workerPid, $exitCode, $sigNo)
    {
        sys_echo("worker error happening [workerId=$workerId, workerPid=$workerPid, exitCode=$exitCode, signalNo=$sigNo]...");
    }

    public function onHandshake(\swoole_http_request $request, \swoole_http_response $response)
    {
        $wsSecKey = $this->checkAndCalcWebSocketKey($request->header);

        if ($wsSecKey === false) {
            $response->status(400);
            $response->end();
            return false;
        }

        // @from https://zh.wikipedia.org/wiki/WebSocket
        // Sec-WebSocket-Version 表示支持的Websocket版本。RFC6455要求使用的版本是13，之前草案的版本均应当被弃用
        $headers = [
            'Upgrade'               => 'websocket',
            'Connection'            => 'Upgrade',
            'Sec-WebSocket-Accept'  => $wsSecKey,
            'Sec-WebSocket-Version' => '13',
            'KeepAlive'             => 'off',
        ];

        foreach ($headers as $key => $val) {
            $response->header($key, $val);
        }

        // 设置ws连接的sid, 必须保证sw与http同域, 共享cookie中sid传递到request中
        // trace信息携带该sid, 根据该sid将trace信息推送到正确的ws连接
        $response->cookie("sid", $request->fd);
        $response->header("X-Trace-Sid", $request->fd);

        // 101: switching protocols
        $response->status(101);
        $response->end();

        $this->fds[$request->fd] = true;
        return true;
    }

    public function onClose(\swoole_websocket_server $server, $fd)
    {
        // sys_echo("closed. fd#$fd");
        unset($this->fds[$fd]);
    }

    public function onOpen(\swoole_websocket_server $server, \swoole_http_request $request)
    {
        sys_echo("handshake success with fd#{$request->fd}");
    }

    public function onMessage(\swoole_websocket_server $server, \swoole_websocket_frame $frame)
    {
        /*
        if ($frame->data == "close") {
            $server->close($frame->fd);
            unset($this->fds[$frame->fd]);
        } else {
            sys_echo("receive from {$frame->fd}:{$frame->data}, opcode:{$frame->opcode}, finish:{$frame->finish}");
        }
        */
    }

    // block
    private function getHostByAddr($addr)
    {
        static $cache = [];
        if (!isset($cache[$addr])) {
            $cache[$addr] = gethostbyaddr($addr) ?: $addr;
        }

        return $cache[$addr];
    }

    public function onRequest(\swoole_http_request $request, \swoole_http_response $response)
    {
        $server = $request->server;
        $uri = $server["request_uri"];
        $method = $server["request_method"];


        $remoteAddr = $server["remote_addr"];
        $remotePort = $server["remote_port"];
        $remoteHost = $this->getHostByAddr($remoteAddr);
        sys_echo("$method $uri [$remoteHost:$remotePort]");

        if ($uri === "/favicon.ico") {
            $response->status(404);
            $response->end();
            return;
        }

        if ($uri === "/report") {
            if (isset($request->get["sid"])) {
                $fd = $request->get["sid"];
                if ($this->traceServer->exist($fd)) {
                    if (isset($this->fds[$fd])) {

                        // 响应上报trace信息请求, 并通过web socket连接中转
                        $body = $request->rawcontent();
                        $response->status(200);
                        $response->end();

                        $this->pushTrace($fd, $body);
                        return;
                    }
                } else {
                    unset($this->fds[$fd]);
                }
            }
            $response->status(404);
            $response->end();
            return;
        }

        if ($uri === "/request") {
            $get = $request->get;
            if (isset($get["uri"]) && isset($get["sid"])) {
                $uri = $get["uri"];
                $sid = $get["sid"];

                $scheme = strtolower(parse_url($uri, PHP_URL_SCHEME));
                if ($scheme === "nova") {
                    $body = $request->rawcontent();
                    $this->novaRequest($sid, $response, $uri, $body);
                } else if ($scheme === "http" || $scheme === "https") { // TODO support https
                    $this->httpRequest($sid, $response, $uri);
                }
                return;
            }
            $response->status(200);
            $response->end('{"error":"invalid args"}');
            return;
        }

        $response->status(200);
        $this->trace($response);
    }

    // nova://provider-address/com.xxx.XxxService?args={}&attach={}"
    private function novaRequest($sid, \swoole_http_response $response, $uri, $body)
    {
        $host = parse_url($uri, PHP_URL_HOST);
        $port = parse_url($uri, PHP_URL_PORT);
        $serviceMethod = ltrim(parse_url($uri, PHP_URL_PATH), "/");
        $query = parse_url($uri, PHP_URL_QUERY);
        parse_str($query, $GET);

        if (empty($serviceMethod)) {
            $response->status(200);
            $response->end("{\"error\":\"invalid service: $serviceMethod\"}");
            return;
        }

        // service & method
        $split = strrpos($serviceMethod, ".");
        if ($split === false) {
            $response->status(200);
            $response->end("{\"error\":\"invalid service $serviceMethod\"}");
            return;
        }
        $service = substr($serviceMethod, 0, $split);
        $method = substr($serviceMethod, $split + 1);

        // args
        $args = [];
        if (isset($GET["args"])) {
            $args = json_decode($GET["args"], true);
            if ($args === null) {
                $args = [];
            }
            if (json_last_error() !== JSON_ERROR_NONE) {
                $response->status(200);
                $response->end("{\"error\":\"invalid json args: " .  json_last_error_msg() . "\"}");
            }
        }

        // attach
        $attach = [];
        if (isset($GET['attach'])) {
            $attach = json_decode($GET['attach'], true);
            if ($attach === null) {
                $attach = [];
            }
            if (json_last_error() !== JSON_ERROR_NONE) {
                $response->status(200);
                $response->end("{\"error\":\"invalid json attach: " .  json_last_error_msg() . "\"}");
            }
        }
        $attach[self::KEY] = $this->getCallbackUrl($sid);


        NovaClient::call($host, $port, $service, $method, $args, $attach, function(\swoole_client $cli, $resp, $errMsg) use($response) {
            if ($cli->isConnected()) {
                $cli->close();
            }
            if ($errMsg) {
                $response->status(200);
                $response->end("{\"error\":\"nova call: $errMsg\"}");
                return;
            } else {
                list($ok, $res, $attach) = $resp;
                $response->status(200);
                $response->end(json_encode([
                    "ok" => $ok,
                    "nova_result" => $res,
                ]));
            }
            return;

        });
        return;
    }

    private function httpRequest($sid, \swoole_http_response $response, $uri)
    {
        $host = parse_url($uri, PHP_URL_HOST);
        $port = parse_url($uri, PHP_URL_PORT);
        $path = parse_url($uri, PHP_URL_PATH);
        $query = parse_url($uri, PHP_URL_QUERY);

        $headers = [
            self::KEY => $this->getCallbackUrl($sid),
        ];
        $cookies = [];
        $data = "";
        $method = "GET";
        $timeout = 3000;


        DNS::lookup($host, function($ip) use($path, $port, $method, $query, $headers, $cookies, $data, $timeout, $response) {
            if ($ip === null) {
                $response->status(200);
                $response->end('{"error":"dns lookup timeout"}');
                return;
            }

            $cli = new \swoole_http_client($ip, intval($port));
            $cli->setHeaders([
                "Connection" => "Closed",
                "Content-Type" => "application/json;charset=utf-8",
            ]);
            $timerId = swoole_timer_after($timeout, function() use($cli, $response) {
                $response->status(200);
                $response->end('{"error":"timeout"}');
                if ($cli->isConnected()) {
                    $cli->close();
                }
            });
            $cli->setMethod($method);
            $cli->setData($data);
            $cli->setHeaders($headers);
            $cli->setCookies($cookies);
            $cli->execute("{$path}?{$query}", function(\swoole_http_client $cli) use($timerId, $response) {
                swoole_timer_clear($timerId);
                $response->status(200);
                $response->end($cli->body);
                $cli->close();
            });
        });
    }

    // TODO: 配置地址
    private function getCallbackUrl($sid)
    {
        // server.ngrok.cc:55674
        // return "http://47.90.92.56:55674/report?sid=$sid";
        return "http://{$this->localIp}:{$this->port}/report?sid=$sid";
    }

    /**
     * @param $fd
     * @param $data
     * WEBSOCKET_OPCODE_CONTINUATION_FRAME = 0x0,
     * WEBSOCKET_OPCODE
     * _TEXT_FRAME = 0x1,
     * WEBSOCKET_OPCODE_BINARY_FRAME = 0x2,
     * WEBSOCKET_OPCODE_CONNECTION_CLOSE = 0x8,
     * WEBSOCKET_OPCODE_PING = 0x9,
     * WEBSOCKET_OPCODE_PONG = 0xa,
     */
    private function pushTrace($fd, $data)
    {
        $payload = $this->pack($data);
        $this->traceServer->push($fd, $payload, 0x2, true);
    }

    private function startHeartbeat()
    {
        swoole_timer_tick(5000, function() {
            foreach ($this->fds as $fd => $_) {
                $payload = $this->pack("PING");
                $this->traceServer->push($fd, $payload, 0x2);
            }
        });
    }

    const RFC6455GUID = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";

    /**
     * Sec-WebSocket-Key是随机的字符串，服务器端会用这些数据来构造出一个SHA-1的信息摘要。
     * 把“Sec-WebSocket-Key”加上一个特殊字符串“258EAFA5-E914-47DA-95CA-C5AB0DC85B11”，
     * 然后计算SHA-1摘要，之后进行BASE-64编码，将结果做为“Sec-WebSocket-Accept”头的值，返回给客户端。
     * 如此操作，可以尽量避免普通HTTP请求被误认为Websocket协议。
     * @from https://zh.wikipedia.org/wiki/WebSocket
     *
     * @param array $header
     * @return bool|string
     */
    private function checkAndCalcWebSocketKey(array $header)
    {
        if (isset($header['sec-websocket-key']))  {
            $wsSecKey = $header['sec-websocket-key'];

            // base64 RFC http://www.ietf.org/rfc/rfc4648.txt
            // http://stackoverflow.com/questions/475074/regex-to-parse-or-validate-base64-data
            // ^(?:[A-Za-z0-9+/]{4})*(?:[A-Za-z0-9+/]{2}==|[A-Za-z0-9+/]{3}=)?$

            // The number of '=' signs at the end of a base64 value must not exceed 2
            // In base64, if the value ends with '=' then the last character must be one of [AEIMQUYcgkosw048]
            // In base64, if the value ends with '==' then the last character must be one of [AQgw]

            $isValidBase64 = preg_match('#^[+/0-9A-Za-z]{21}[AQgw]==$#', $wsSecKey) != 0;
            $isValidLen = strlen(base64_decode($wsSecKey)) === 16;

            if ($isValidBase64 && $isValidLen) {
                return base64_encode(sha1($wsSecKey . self::RFC6455GUID, true));
            }
        }

        return false;
    }

    private function pack($pushData)
    {
        return pack("N", strlen($pushData)) . $pushData;
    }

    private function trace(\swoole_http_response $response)
    {
        $response->end(<<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Zan Debugger Trace</title>
  <style>
/*html { overflow: hidden; }*/
body {
  font-family: monospace, Arial, sans-serif;
  background: #333;
  
  color: white;
  font-size: 10pt;
  /*margin: 0;*/
  height: 100%;
  
  min-width: 500px;
  min-height: 500px;
}

.background {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 24pt;
  background-color: #030303;
}

#screen1 {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 24pt;
  
  width: 100%;
  height: 100%;
  /*overflow: auto;*/
  /*overflow-y: scroll;*/
  /*vertical-align: top;*/
  /*-webkit-user-select: text;*/
  cursor: text;
}

/*
.terminal {
  min-height: 100%;
  color: #e3e3e3;
  font-family: monospace;
  display: flex;
  flex-direction: column;
}

.terminal .ter-header {
  flex: 0.1;
}
.terminal .ter-request {
  flex: 0.45;
  overflow: scroll;
}
.terminal .ter-trace {
  flex: 0.45;
  overflow: scroll;
}
*/

/*
pre {
  white-space: pre-wrap;
}
*/

#requestLog {
  background: #444;
  color: #e3e3e3;
  border: 1px solid #666;
  padding: 3px;
  height: 200px;
  overflow: auto;
  position: absolute;
  top: 135px;
  right: 5px;
  left: 5px;
}

#traceLog {
  background: #444;
  color: #e3e3e3;
  border: 1px solid #666;
  padding: 3px;
  overflow: auto;
  position: absolute;
  top: 350px;
  right: 5px;
  bottom: 5px;
  left: 5px;
}





/********************************************************************/

::-webkit-scrollbar {
  height: 14px;
  width: 10px;
}

::-webkit-scrollbar-track {
  background: #150010;
}

::-webkit-scrollbar-thumb {
  background: #302;
}
::-webkit-scrollbar-thumb:window-inactive {
  background: #302;
}
::-webkit-scrollbar-corner {
  background: #201;
}

.splash-button {
  position: relative;
  top: -3px;
  height: 26px;
  width: 48px;
  /*background-image: url('minilogo.png');*/
}
.splash-button:hover {
  /*background-image: url('minilogo-hover.png');*/
  cursor: pointer;
}
.send-button {
  position: relative;
  top: 0;
  height: 26px;
  width: 26px;
  /*background-image: url('return.png');*/
}
.send-button:hover {
  /*background-image: url('return-hover.png');*/
  cursor: pointer;
}
.input-holder {
  width: 100%;
  padding-right: 0.2em;
}

/********************************************************************/

input[type="text"] {
  background-color: #444;
  color: #fff;
  font-family: monospace;
  border: thin solid #f00;
}

.bar2 input[type="text"] {
  background-color: #111;
  border-width: 0;
  width: 100%;
  outline: none;
}
.bar2 input[type="text"]:active {
  border-width: 0;
}

.consolebar input[type="button"] {
  padding: 0;
  width: 5em;
}

.preserved {
  white-space: pre-wrap;
}

.consolebar {
  position: absolute;
  bottom: 0;
  width: 100%;
  height: 24pt;
  background-color: #222;
}

.consolebar .time {
  font-family: monospace;
  width: 1em;
  color: #ddd;
}

.sec {
  color: #777;
}

.consolebar .leftpad {
  padding-left: 1em;
}

.consolebar .rightpad {
  padding-right: 1em;
}

.bar2 {
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 24pt;
}

/********************************************************************/
.hide-when-not-connected {
  display: none;
}

.connected .hide-when-not-connected {
  display: block;
}

.connected .hide-when-not-connected p {
  color: #33ff44
}

.connected .hide-when-connected {
  display: none;
}

input[type="number"] {
  width: 5em;
}

button {
  cursor: pointer;
  margin-left: 20px;
}

#serverStop {
  background-color: #D14836;
  background-image: -webkit-linear-gradient(top,#DD4B39,#D14836);
  border: 1px solid transparent;
  color: white;
  border-radius: 2px;
}

#serverStop:hover {
  background-color: #C53727;
  background-image: -webkit-linear-gradient(top, #DD4B39, #C53727);
  border: 1px solid #B0281A;
}

#logClear {
  background-color: #a0d0d1;
  background-image: -webkit-linear-gradient(top,#72d1ca,#a0d0d1);
  border: 1px solid transparent;
  color: white;
  border-radius: 2px;
}
#logClear:hover {
  background-color: #b8d0d1;
  background-image: -webkit-linear-gradient(top, #abd1d0, #b8d0d1);
  border: 1px solid #b8d0d1;
}

#requestGo {
  background-color: #a0d0d1;
  background-image: -webkit-linear-gradient(top,#72d1ca,#a0d0d1);
  border: 1px solid transparent;
  color: white;
  border-radius: 2px;
}

#requestGo:hover {
  background-color: #b8d0d1;
  background-image: -webkit-linear-gradient(top, #abd1d0, #b8d0d1);
  border: 1px solid #b8d0d1;
}

#url {
  background: #444;
  color: #ececec;
  border: 1px solid #666;
  overflow: auto;
  width: 296px;
  height: 20px;
}
  </style>
</head>
<body>
  <!--<div class="background"></div>-->

  <!--<div id="screen1" class="screen">-->
    <!--<div id="ter1" class="terminal">-->
      <div class="ter-header" id="server">
      <h1>Zan Debugger Trace</h1>
      
      <div class="hide-when-connected">
        Connect To 
        <select id="addresses"><option></option></select>:
        <input type="number" id="serverPort">
        <button id="serverStart">Start!</button>
      </div>
      
      <div class="hide-when-not-connected">
        <p>Connected To <span class="connect-to"></span>
          <button id="serverStop">Stop!</button>
          <button id="logClear">Clear</button>
        </p>
        
        <p>Request <span class="request"></span>
          <!--example-->
          <!--nova://10.9.188.33:8050/com.youzan.material.general.service.MediaService.getMediaList?args={"query":{"categoryId":2,"kdtId":1,"pageNo":1,"pageSize":5}}&attach={"xxx":"yyy"}-->
          <!--http://127.0.0.1:8000/index/index/test-->
          <input type="text" id="url" value="http://127.0.0.1:8000/index/index/test">
          <button id="requestGo">Go!</button>
        </p>
      </div>
      </div>
      
      <div class="ter-request"><pre><code id="requestLog"></code></pre></div>
      
      <div class="ter-trace"><pre><code id="traceLog"></code></pre></div>
    <!--</div>-->
  <!--</div>-->

  <!--<div class="bar2">-->
    <!--<table class="consolebar">-->
      <!--<tr>-->
        <!--<td id="clock1" style="color: transparent;" class="time rightpad leftpad">00:00:00</td>-->
        <!--<td class="input-holder"><input id="msg1" type="text" value=""></td>-->
        <!--<td class="button rightpad">-->
          <!--<div class="send-button" id="sendbut1" type="button" value="send">Send</div>-->
        <!--</td>-->
        <!--<td class="button rightpad">-->
          <!--<div class="splash-button" id="menubut1" type="button" value="menu">Menu</div>-->
        <!--</td>-->
      <!--</tr>-->
    <!--</table>-->
  <!--</div>-->
  
    
  <link href="http://apps.bdimg.com/libs/highlight.js/9.1.0/styles/monokai-sublime.min.css" rel="stylesheet">
  <script id="worker_highlight" type="javascript/worker">
    self.onmessage = function(event) {
      importScripts('http://apps.bdimg.com/libs/highlight.js/9.1.0/highlight.min.js')
      importScripts('http://apps.bdimg.com/libs/highlight.js/9.1.0/languages/json.min.js')
      let result = self.hljs.highlightAuto(event.data)
      self.postMessage(result.value)
    }    
  </script>
  
  <script>
(function (exports) {
  'use strict'

  // bytes : Uint8Array

  function str2bytes(string, encode = 'utf-8') {
    return new TextEncoder(encode).encode(string)
  }

  function bytes2str(uint8array, encode = 'utf-8') {
    return new TextDecoder(encode).decode(uint8array)
  }

  /**
   * Buffer 
   * @author xiaofeng
   * 
   * +-------------------+------------------+------------------+
   * | prependable bytes |  readable bytes  |  writable bytes  |
   * |                   |     (CONTENT)    |                  |
   * +-------------------+------------------+------------------+
   * |                   |                  |                  |
   * V                   V                  V                  V
   * 0      <=      readerIndex   <=   writerIndex    <=     size
   */
  function Buffer(size) {
    this.bytes = new Uint8Array(size)
    this.writerIndex = 0
    this.readerIndex = 0
  }

  Buffer.fromBytes = function(bytes) {
    let buf = new Buffer(bytes.length * 2)
    buf.write(bytes)
    return buf
  }

  Buffer.fromString = function(str) {
    return Buffer.fromBytes(str2bytes(str))
  }

  Buffer.fromArrayBuffer = function(arraybuffer) {
    return Buffer.fromBytes(new Uint8Array(arraybuffer))
  }

  Buffer.prototype.readableBytes = function() {
    return this.writerIndex - this.readerIndex
  }

  Buffer.prototype.writableBytes = function() {
    return this.bytes.length - this.writerIndex
  }

  Buffer.prototype.prependableBytes = function() {
    return this.readerIndex
  }

  Buffer.prototype.capacity = function() {
    return this.bytes.length
  }

  Buffer.prototype.get = function(len) {
    len = Math.min(len, this.readableBytes())
    return this.rawRead(this.readerIndex, len)
  }

  Buffer.prototype.read = function(len) {
    len = Math.min(len, this.readableBytes())
    let read = this.rawRead(this.readerIndex, len)
    this.readerIndex += len
    if (this.readerIndex === this.writerIndex) {
      this.reset()
    }
    return read
  }

  Buffer.prototype.skip = function(len) {
    len = Math.min(len, this.readableBytes())
    this.readerIndex += len
    if (this.readerIndex === this.writerIndex) {
      this.reset()
    }
    return len
  }

  Buffer.prototype.readFull = function() {
    return this.read(this.readableBytes())
  }

  Buffer.prototype.writeArrayBuffer = function(arraybuffer) {
    return this.write(new Uint8Array(arraybuffer))
  }
  
  Buffer.prototype.writeString = function(str) {
    return this.write(str2bytes(str))
  }

  Buffer.prototype.write = function(bytes) {
    if (bytes.length === 0) {
      return
    }

    let len = bytes.length

    if (len <= this.writableBytes()) {
      this.rawWrite(this.writerIndex, bytes)
      this.writerIndex += len
      return
    }

    // expand
    if (len > (this.prependableBytes() + this.writableBytes())) {
      this.expand((this.readableBytes() + len) * 2)
    }
    
    // copy-move
    if (this.readerIndex !== 0) {
      this.bytes.copyWithin(0, this.readerIndex, this.writerIndex)
      this.writerIndex -= this.readerIndex
      this.readerIndex = 0
    }

    this.rawWrite(this.writerIndex, bytes)
    this.writerIndex += len
  }

  Buffer.prototype.reset = function() {
    this.readerIndex = 0
    this.writerIndex = 0
  }

  Buffer.prototype.toSring = function() {
    return bytes2str(this.bytes.slice(this.readerIndex, this.writerIndex))
  }

  // private
  Buffer.prototype.rawRead = function (offset, len) {
    if (offset < 0 || offset + len > this.bytes.length) {
      throw new RangeError('Trying to read beyond buffer length') 
    }
    return this.bytes.slice(offset, offset + len)
  }

  // private
  Buffer.prototype.rawWrite = function (offset, bytes) {
    let len = bytes.length
    if (offset < 0 || offset + len > this.bytes.length) {
      throw new RangeError('Trying to write beyond buffer length') 
    }
    for (let i = 0; i < len; i++) {
      this.bytes[offset + i] = bytes[i]
    }
  }

  // private
  Buffer.prototype.expand = function (size) {
    if (size <= this.bytes.capacity) {
      return
    }
    let buf = new Uint8Array(size)
    buf.set(this.bytes)
    this.bytes = buf
  }

  exports.Buffer = Buffer
  exports.str2bytes = str2bytes
  exports.bytes2str = bytes2str

}(window))
  </script>

  <script>

  function highlight(codeEl) {
    let raw = document.querySelector('#worker_highlight').textContent
    let blob = new Blob([raw], { type: "text/javascript" })
    let worker = new Worker(window.URL.createObjectURL(blob))
    worker.onmessage = function(event) { 
      codeEl.innerHTML = event.data
    }
    worker.postMessage(codeEl.textContent)
  }
  
  var log = (function(){
    var traceLog = document.querySelector("#traceLog");
    var requestLog = document.querySelector("#requestLog");
    
    var trace = function(el) {
      return function(str) {
        if (!str) {
          return
        }
        if (str.length>0 && str.charAt(str.length-1)!='\\n') {
          str+='\\n'
        }
        // var t = new Date().toLocaleTimeString()
        el.innerText= /*t + '  ' + */ str + el.innerText;  
        
        // 高亮返回值
        highlight(el)

        // 高亮console json
        JSONstringify(str)
      };
    };
    
    return {
        trace: trace(traceLog),
        response: trace(requestLog)
    };
  })();
  
 
  function Trace(addr, port) {
    this.isConnected = false;
    this.addr = addr;
    this.port = port;
    this.buffer = new Buffer(1024 * 1024);
  }

  Trace.prototype.start = function(onStart, onClose) {
    this.onStart = onStart
    this.onClose = onClose
    
    var wsServer = 'ws://' + this.addr + ':' + this.port;
    
    var ws = new WebSocket(wsServer);
    if (!ws) {
      return;
    }
    
    // 使用ArrayBuffer接收的是二进制数据
    ws.binaryType = 'arraybuffer';
    
    ws.addEventListener('open', function (evt) {
      // 无API可以获取websocket http response的header信息，这里使用cookie不准确
      // response回来时设置的cookie可能在ws连接事件完成之前被改变 ？！
      this.sid = getCookie('sid')
      this.isConnected = true;
      console.info("Connected to WebSocket server.");
      this.onStart(evt)
    }.bind(this));

    ws.addEventListener('close', function (evt) {
      this.isConnected = false;
      console.info("Disconnected");
      this.onClose(evt)
    }.bind(this));

    ws.addEventListener('error', function (evt, e) {
      this.isConnected = false;
      console.error('Error occured: ' + evt.data);
      this.onClose(evt)
    }.bind(this));

    ws.addEventListener('message', function (evt) {
      /*
      纯文本, 发现 websocket同样会分包
      if (evt.data.length) {
        var traceData = JSON.parse(evt.data)
        console.log(traceData);
        log.trace(JSON.stringify(traceData, null, 2));
      }
      */
      
      
      // 处理粘包
      this.buffer.writeArrayBuffer(evt.data)
       
      while(true) {
        if (this.buffer.readableBytes() < 4) {
          return
        }
      
        var arraybuffer = this.buffer.get(4).buffer
        var len = new DataView(arraybuffer).getUint32(); // PHP pack('N')
        if (this.buffer.readableBytes() < len + 4) {
          return
        }
      
        this.buffer.skip(4)
      
        var str = bytes2str(this.buffer.read(len))
        if (str === "PING") {
          // 单独处理心跳
          this.send("PONG");
        } else {
          try {
            var traceData = JSON.parse(str)
            console.log(traceData);
            log.trace(JSON.stringify(traceData, null, 2));
          } catch (e) {
            console.error(e)
          }
        }
      }

      
    }.bind(this));

    this.ws = ws;
  }

  Trace.prototype.stop = function() {
    // this.ws.readyState !== WebSocket.CLOSED 
    if (this.ws && this.isConnected) {
      this.ws.close()
    }
  }

  Trace.prototype.send = function(toSend) {
    // trace.ws.readyState === WebSocket.OPEN
    if (this.ws && this.isConnected) {
      return this.ws.send(toSend)
    } else {
      return false;
    }
  }

  var trace 

  function startServer() {
    // trace.ws.readyState !== WebSocket.OPEN
    if (trace && trace.isConnected) {
      return;
    }

    // var addr=document.getElementById("addresses").value;
    // var port=parseInt(document.getElementById("serverPort").value);
    
    var addr = window.location.hostname
    var port = window.location.port ? window.location.port : 80
    trace = new Trace(addr, port);
    trace.start(function() {
      document.querySelector(".connect-to").innerText=addr+":"+port;
      document.querySelector("#server").className="connected";
    }, function() {
      document.querySelector("#server").className="";
    })
  }

  function stopServer() {
    // trace.ws.readyState !== WebSocket.CLOSED
    if (trace && trace.isConnected) {
      trace.stop();
    }
  }

  document.getElementById('serverStart').addEventListener('click', startServer);
  document.getElementById('serverStop').addEventListener('click', stopServer);
  document.getElementById('requestGo').addEventListener('click', (function(){
    var url = document.getElementById('url')
    return function() {
      // 首先清空trace记录
      traceLog.innerText = '';
      
      // 请求带上ws的sid(fd), 识别report的trace信息归属的哪个ws连接
      fetch('./request?sid=' + trace.sid + '&uri=' + encodeURIComponent(url.value))  
        .then(
          function(response) {  
            if (response.status !== 200) {  
              console.error('request error. Status Code: ' +  response.status);  
              return; 
            }
            // response.json()
            return response.text();
          }  
        )
        .then(function(text) {
          try {
            var resp = JSON.parse(text)
            console.log(resp);
            log.response(JSON.stringify(resp, null, 2));
          } catch (e) {
            log.response(text);
          }
        })
        .catch(function(err) {
          console.log('Fetch Error: ', err);  
          log.response('fetch error');
        });
    };
  })());
  
  document.getElementById('logClear').addEventListener('click', (function(){
    var traceLog = document.querySelector("#traceLog");
    var requestLog = document.querySelector("#requestLog");
    return function() {
      traceLog.innerText = '';
      requestLog.innerText = '';
      console.clear();
    };
  })());
  
  
  // init
  document.addEventListener('DOMContentLoaded', function() {
    let isChrome = /Chrome/.test(navigator.userAgent) && /Google Inc/.test(navigator.vendor)
    if (isChrome) {
      document.getElementById('serverStart').click();      
    } else {
      alert("请更换Chrome浏览器访问!!!")
    }
  })
  
  
  function JSONstringify(json) {
    if (typeof json != 'string') {
        json = JSON.stringify(json, undefined, '\t');
    }

    var 
        arr = [],
        _string = 'color:green',
        _number = 'color:darkorange',
        _boolean = 'color:blue',
        _null = 'color:magenta',
        _key = 'color:red';

    json = json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
        var style = _number;
        if (/^"/.test(match)) {
            if (/:$/.test(match)) {
                style = _key;
            } else {
                style = _string;
            }
        } else if (/true|false/.test(match)) {
            style = _boolean;
        } else if (/null/.test(match)) {
            style = _null;
        }
        arr.push(style);
        arr.push('');
        return '%c' + match + '%c';
    });

    arr.unshift(json);

    console.log.apply(console, arr);
  }
  
  // http://stackoverflow.com/questions/4003823/javascript-getcookie-functions/4004010#4004010
  function getCookies() {
    var c = document.cookie, v = 0, cookies = {};
    if (document.cookie.match(/^\s*\$Version=(?:"1"|1);\s*(.*)/)) {
      c = RegExp.$1;
      v = 1;
    }
    if (v === 0) {
      c.split(/[,;]/).map(function(cookie) {
        var parts = cookie.split(/=/, 2),
            name = decodeURIComponent(parts[0].trimLeft()),
            value = parts.length > 1 ? decodeURIComponent(parts[1].trimRight()) : null;
        cookies[name] = value;
      });
    } else {
      c.match(/(?:^|\s+)([!#$%&'*+\-.0-9A-Z^`a-z|~]+)=([!#$%&'*+\-.0-9A-Z^`a-z|~]*|"(?:[\x20-\x7E\x80\xFF]|\\[\x00-\x7F])*")(?=\s*[,;]|$)/g).map(function($0, $1) {
        var name = $0,
            value = $1.charAt(0) === '"'
                        ? $1.substr(1, -1).replace(/\\(.)/g, "$1")
                        : $1;
        cookies[name] = value;
      });
    }
    
    return cookies;
  }
  function getCookie(name) {
    return getCookies()[name];
  }

  </script>
</body>
</html>
HTML
);
    }
}



class NovaClient
{
    private static $ver_mask = 0xffff0000;
    private static $ver1 = 0x80010000;

    private static $t_call  = 1;
    private static $t_reply  = 2;
    private static $t_ex  = 3;

    public static $connectTimeout = 2000;
    public static $sendTimeout = 4000;

    private $connectTimerId;
    private $sendTimerId;
    private $seq;

    /** @var \swoole_client */
    public $client;

    private $host;
    private $port;
    private $recvArgs;
    private $callback;

    public function __construct($host, $port)
    {
        $this->host = $host;
        $this->port = $port;

        $this->client = $this->makeClient();
    }

    public static function call($host, $port, $service, $method, array $args, array $attach, callable $callback)
    {
        (new static($host, $port))->invoke($service, $method, $args, $attach, $callback);
    }

    /**
     * @param string $service
     * @param string $method
     * @param array $args
     * @param array $attach
     * @param callable $callback (receive, errorMsg)
     */
    public function invoke($service, $method, array $args, array $attach, callable $callback)
    {
        $this->recvArgs = func_get_args();
        $this->callback = $callback;

        if ($this->client->isConnected()) {
            $this->send();
        } else {
            $this->connect();
        }
    }

    private function makeClient()
    {
        $client = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);

        $client->set([
            "open_length_check" => 1,
            "package_length_type" => 'N',
            "package_length_offset" => 0,
            "package_body_offset" => 0,
            "open_nova_protocol" => 1,
            "socket_buffer_size" => 1024 * 1024 * 2,
        ]);

        $client->on("error", function(\swoole_client $client) {
            $this->clearTimer();
            $cb = $this->callback;
            $cb($client, null, "ERROR: " . socket_strerror($client->errCode));
        });

        $client->on("close", function(/*\swoole_client $client*/) {
            $this->clearTimer();
        });

        $client->on("connect", function(/*\swoole_client $client*/) {
            swoole_timer_clear($this->connectTimerId);
            $this->invoke(...$this->recvArgs);
        });

        $client->on("receive", function(\swoole_client $client, $data) {
            // fwrite(STDERR, "recv: " . implode(" ", str_split(bin2hex($data), 2)) . "\n");
            swoole_timer_clear($this->sendTimerId);
            $cb = $this->callback;
            $cb($client, self::unpackResponse($data, $this->seq), null);
        });

        return $client;
    }

    private function connect()
    {
        DNS::lookup($this->host, function($ip, $host) {
            if ($ip === null) {
                $cb = $this->callback;
                $cb($this->client, null, "DNS查询超时 host:{$host}");
            } else {
                $this->connectTimerId = swoole_timer_after(self::$connectTimeout, function() {
                    $cb = $this->callback;
                    $cb($this->client, null, "连接超时 {$this->host}:{$this->port}");
                });
                assert($this->client->connect($ip, $this->port));
            }
        });
    }

    private function send()
    {
        $this->sendTimerId = swoole_timer_after(self::$sendTimeout, function() {
            $cb = $this->callback;
            $cb($this->client, null, "Nova请求超时");
        });
        $novaBin = self::packNova(...$this->recvArgs); // 多一个onRecv参数,不过没关系
        assert($this->client->send($novaBin));
    }

    /**
     * @param string $recv
     * @param int $expectSeq
     * @return array
     */
    private static function unpackResponse($recv, $expectSeq)
    {
        list($response, $attach) = self::unpackNova($recv, $expectSeq);
        $hasError = isset($response["error_response"]);
        if ($hasError) {
            $res = $response["error_response"];
        } else {
            $res = $response["response"];
        }
        return [!$hasError, $res, $attach];
    }

    /**
     * @param string $raw
     * @param int $expectSeq
     * @return array
     */
    private static function unpackNova($raw, $expectSeq)
    {
        $service = $method = $ip = $port = $seq = $attach = $thriftBin = null;
        $ok = nova_decode($raw, $service, $method, $ip, $port, $seq, $attach, $thriftBin);
        assert($ok);
        assert(intval($expectSeq) === intval($seq));

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
        /*$name = */$read($len);
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
     * @param string $service
     * @param string $method
     * @param array $args
     * @param array $attach
     * @return string
     */
    private function packNova($service, $method, array $args, array $attach)
    {
        $args = self::packArgs($args);
        $thriftBin = self::packThrift($service, $method, $args);
        $attach = json_encode($attach, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $sockInfo = $this->client->getsockname();
        $localIp = ip2long($sockInfo["host"]);
        $localPort = $sockInfo["port"];

        $return = "";
        $this->seq = nova_get_sequence();
        $ok = nova_encode("Com.Youzan.Nova.Framework.Generic.Service.GenericService", "invoke",
            $localIp, $localPort,
            $this->seq,
            $attach, $thriftBin, $return);
        assert($ok);
        return $return;
    }

    /**
     * @param string $serviceName
     * @param string $methodName
     * @param string $args
     * @param int $seq
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

    private function clearTimer()
    {
        if (swoole_timer_exists($this->connectTimerId)) {
            swoole_timer_clear($this->connectTimerId);
        }
        if (swoole_timer_exists($this->sendTimerId)) {
            swoole_timer_clear($this->sendTimerId);
        }
    }
}


/**
 * Class DNS
 * 200ms超时,重新发起新的DNS请求,重复5次
 * 无论哪个请求先收到回复立即call回调, cb 保证只会被call一次
 */
final class DNS
{
    public static $maxRetry = 5;
    public static $timeout = 200;

    public static function lookup($host, callable $cb)
    {
        self::helper($host, self::once($cb), self::$maxRetry);
    }

    private static function helper($host, callable $cb, $n)
    {
        if ($n <= 0) {
            return $cb(null, $host);
        }

        $t = swoole_timer_after(self::$timeout, function() use($host, $cb, $n) {
            self::helper($host, $cb, --$n);
        });

        return swoole_async_dns_lookup($host, function($host, $ip) use($t, $cb) {
            if (swoole_timer_exists($t)) {
                swoole_timer_clear($t);
            }
            $cb($ip, $host);
        });
    }

    private static function once(callable $fun)
    {
        $called = false;
        return function(...$args) use(&$called, $fun) {
            if ($called) {
                return;
            }
            $fun(...$args);
            $called = true;
        };
    }
}

function sys_echo($context) {
    $workerId = isset($_SERVER["WORKER_ID"]) ? " #" . $_SERVER["WORKER_ID"] : "";
    $dataStr = date("Y-m-d H:i:s", time());
    echo "[{$dataStr}{$workerId}] $context\n";
}