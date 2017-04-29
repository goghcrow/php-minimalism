<?php

$port = isset($argv[1]) ? intval($argv[1]) : 7777;

$serv = new TraceServer($port);
$serv->start();

class TraceServer
{
    const KEY = "X-Trace-Callback";

    public $localIp;
    public $host;
    public $port;

    /**
     * @var \swoole_http_server
     */
    public $traceServer;

    public $fds = [];

    public function __construct($port = 7777, $host = "0.0.0.0")
    {
        $this->localIp = gethostbyname(gethostname());
        $this->host = $host;
        $this->port = $port;
        $this->traceServer = new \swoole_websocket_server("0.0.0.0", $port, SWOOLE_BASE);

        $this->traceServer->set([
            // 'log_file' => __DIR__ . '/trace.log',
            'buffer_output_size' => 1024 * 1024 * 1024,
            'pipe_buffer_size' => 1024 * 1024 * 1024,
            'max_connection' => 100,
            'max_request' => 100000,
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
        
        $this->traceServer->start();

        $this->startHeartbeat();
    }

    public function startHeartbeat()
    {
        swoole_timer_tick(5000, function() {
            foreach ($this->fds as $fd => $_) {
                $this->traceServer->push($fd, "", 0x9); // PING
            }
        });
    }

    public function onStart(\swoole_websocket_server $server)
    {
        sys_echo("server starting .....");
    }

    public function onShutdown(\swoole_websocket_server $server)
    {
        sys_echo("server shutdown .....");
    }

    public function onWorkerStart(\swoole_websocket_server $server, $workerId)
    {
        $_SERVER["WORKER_ID"] = $workerId;
        sys_echo("worker #$workerId starting .....");
    }

    public function onWorkerStop(\swoole_websocket_server $server, $workerId)
    {
        sys_echo("worker #$workerId stopping ....");
    }

    public function onWorkerError(\swoole_websocket_server $server, $workerId, $workerPid, $exitCode, $sigNo)
    {
        sys_echo("worker error happening [workerId=$workerId, workerPid=$workerPid, exitCode=$exitCode, signalNo=$sigNo]...");
    }

    public function onConnect()
    {
        // sys_echo("connecting ......");
    }

    public function onHandshake(\swoole_http_request $request, \swoole_http_response $response)
    {
        //自定定握手规则，没有设置则用系统内置的（只支持version:13的）
        if (!isset($request->header['sec-websocket-key']))  {
            //'Bad protocol implementation: it is not RFC6455.'
            $response->end();
            return false;
        }

        if (0 === preg_match('#^[+/0-9A-Za-z]{21}[AQgw]==$#', $request->header['sec-websocket-key'])
            || 16 !== strlen(base64_decode($request->header['sec-websocket-key']))
        ) {
            // Header Sec-WebSocket-Key is illegal;
            $response->end();
            return false;
        }

        $key = base64_encode(
            sha1($request->header['sec-websocket-key'] .
                '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));

        $headers = [
            'Upgrade'               => 'websocket',
            'Connection'            => 'Upgrade',
            'Sec-WebSocket-Accept'  => $key,
            'Sec-WebSocket-Version' => '13',
            'KeepAlive'             => 'off',
        ];

        foreach ($headers as $key => $val) {
            $response->header($key, $val);
        }
        // 设置ws连接sid, 必须保证sw与http同域, 共享cookie中sid传递到request中
        $response->cookie("sid", $request->fd);
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
        if ($frame->data == "close") {
            $server->close($frame->fd);
            unset($this->fds[$frame->fd]);
        } else {
            sys_echo("receive from {$frame->fd}:{$frame->data}, opcode:{$frame->opcode}, finish:{$frame->finish}");
        }
    }

    public function onRequest(\swoole_http_request $request, \swoole_http_response $response)
    {
        $server = $request->server;
        $uri = $server["request_uri"];
        $method = $server["request_method"];
        sys_echo("$method $uri");

        if ($uri === "/favicon.ico") {
            $response->status(404);
            $response->end();
            return;
        }

        if ($uri === "/report") {
            if (isset($request->get["sid"])) {
                $sid = $request->get["sid"];
                if ($this->traceServer->exist($sid)) {
                    if (isset($this->fds[$sid])) {

                        // 响应上报trace信息请求, 并通过web socket连接中转
                        $body = $request->rawcontent();
                        $response->status(200);
                        $response->end();

                        $this->pushTrace($sid, $body);
                        return;
                    }
                } else {
                    unset($this->fds[$sid]);
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

    private function novaRequest($sid, \swoole_http_response $response, $uri, $body)
    {
        $host = parse_url($uri, PHP_URL_HOST);
        $port = parse_url($uri, PHP_URL_PORT);
        // TODO
    }

    private function httpRequest($sid, \swoole_http_response $response, $uri)
    {
        $host = parse_url($uri, PHP_URL_HOST);
        $port = parse_url($uri, PHP_URL_PORT);
        $path = parse_url($uri, PHP_URL_PATH);
        $query = parse_url($uri, PHP_URL_QUERY);

        $headers = [
            self::KEY => "http://{$this->localIp}:{$this->port}/report?sid=$sid"
        ];
        $cookies = [];
        $data = "";
        $method = "GET";
        $timeout = 5000;

        DnsClient::lookup($host, function($host, $ip)
            use($path, $port, $method, $query, $headers, $cookies, $data, $timeout, $response)
        {
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
                $cli->close();
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

    /**
     * @param $fd
     * @param $data
     * WEBSOCKET_OPCODE_CONTINUATION_FRAME = 0x0,
     * WEBSOCKET_OPCODE_TEXT_FRAME = 0x1,
     * WEBSOCKET_OPCODE_BINARY_FRAME = 0x2,
     * WEBSOCKET_OPCODE_CONNECTION_CLOSE = 0x8,
     * WEBSOCKET_OPCODE_PING = 0x9,
     * WEBSOCKET_OPCODE_PONG = 0xa,
     */
    private function pushTrace($fd, $data)
    {
        $h = pack("N", strlen($data));
        $payload = $h . $data;
        $this->traceServer->push($fd, $payload, 0x2, true);
        return;


        // 分包测试
        /*
        while (strlen($payload) > 0) {
            usleep(10000);
            $a = substr($payload, 0, 10);
            $payload = substr($payload, 10);
            sys_echo("push $a");
            $this->traceServer->push($fd, $a, 0x2, true);
        }
        */
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
body {
  font-family: Arial, sans-serif;
  background: #333;
  color: white;
  min-width: 200px;
  min-height: 200px;
}

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

#traceLog {
  background: #444;
  color: #e3e3e3;
  border: 1px solid #666;
  padding: 3px;
  overflow: auto;
  position: absolute;
  top: 240px;
  right: 5px;
  bottom: 5px;
  left: 5px;
}

#requestLog {
  background: #444;
  color: #e3e3e3;
  border: 1px solid #666;
  padding: 3px;
  height: 100px;
  overflow: auto;
  position: absolute;
  top: 125px;
  right: 5px;
  left: 5px;
}

  </style>
</head>
<body>
  <section id="server">
  <h1>Zan Debugger Trace</h1>
  
  <div class="hide-when-connected">Connect To 
    <select id="addresses"><option></option></select>:
    <input type="number" id="serverPort"></input>
    <button id="serverStart">Start!</button>
  </div>
  
  <div class="hide-when-not-connected">
    <p>Connected To <span class="connect-to"></span>
      <button id="serverStop">Stop!</button>
      <button id="logClear">Clear</button>
    </p>
    
    <p>Request <span class="request"></span>
      <input type="text" id="url" value="http://127.0.0.1:8000/index/index/test">
      <button id="requestGo">Go!</button>
    </p>
    
    <pre id="requestLog"></pre>
    <pre id="traceLog"></pre>
  </div>
  
  </section>

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
        var t = new Date().toLocaleTimeString()
        el.innerText= t + '  ' + str + el.innerText;
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
      // console.info('Retrieved data from server: ' + evt.data.length);
      // 纯文本, 发现 websocket同样会分包
      /*
      if (evt.data.length) {
        var traceData = JSON.parse(evt.data)
        console.log(traceData);
        log.trace(JSON.stringify(traceData, null, 2));
      }
      */
      
      this.buffer.writeArrayBuffer(evt.data)
      
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
      // console.log(str);
      var traceData = JSON.parse(str)
      console.log(traceData);
      log.trace(JSON.stringify(traceData, null, 2));
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

    var addr=document.getElementById("addresses").value;
    var port=parseInt(document.getElementById("serverPort").value);
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
      // 请求带上ws的sid(fd), 识别report的trace信息归属的哪个ws连接
      fetch('./request?sid=' + getCookie('sid') + '&uri=' + encodeURIComponent(url.value))  
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
          log.response(text);
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
    };
  })());
  
  
  // init
  document.getElementById("addresses").innerHTML = '<option>' + window.location.hostname  + '</option>'
  document.getElementById("serverPort").value = window.location.port;
  document.getElementById('serverStart').click();
  
  
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
    private static $auto_reconnect = false;

    private static $ver_mask = 0xffff0000;
    private static $ver1 = 0x80010000;

    private static $t_call  = 1;
    private static $t_reply  = 2;
    private static $t_ex  = 3;

    private $timeout;
    /** @var \swoole_client */
    public $client;
    private $host;
    private $port;
    private $recvArgs;

    public function __construct($host, $port, $timeout = 2000)
    {
        $this->host = $host;
        $this->port = $port;
        $this->timeout = $timeout;

        $this->client = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
        $this->init();
    }

    private $onError;
    private $onClose;

    public function on()
    {

    }

    public function invoke($service, $method, array $args, array $attach, callable $onReceive)
    {
        $this->recvArgs = func_get_args();

        if ($this->client->isConnected()) {
            $this->send();
        } else {
            $this->connect();
        }
    }

    private function init()
    {
        $this->client->set([
            "open_length_check" => 1,
            "package_length_type" => 'N',
            "package_length_offset" => 0,
            "package_body_offset" => 0,
            "open_nova_protocol" => 1,
            "socket_buffer_size" => 1024 * 1024 * 2,
        ]);

        $this->client->on("error", function(\swoole_client $client) {
            $this->cancel("connect_timeout");
            sys_echo("\033[1;31m连接出错: " . socket_strerror($client->errCode) . "\033[0m");
            if (self::$auto_reconnect) {
                $this->connect();
            } else if ($onError = $this->onError) {
                $onError("连接出错: ", socket_strerror($client->errCode));
            }
        });

        $this->client->on("close", function(\swoole_client $client) {
            $this->cancel("connect_timeout");
            if ($onClose = $this->onClose) {
                $onClose($client);
            }

        });
        $this->client->on("receive", function(\swoole_client $client, $data) {
            $recv = end($this->recvArgs);
            $recv($client, ...self::unpackResponse($data));
        });
    }

    private function connect()
    {
        $this->client->on("connect", function(\swoole_client $client) {
            $this->cancel("connect_timeout");
            $this->invoke(...$this->recvArgs);
        });

        $this->deadline($this->timeout, "connect_timeout");

        DnsClient::lookup($this->host, function($host, $ip) {
            if ($ip === null && $onError = $this->onError) {
                sys_echo("\033[1;31mDNS查询超时 host:{$host}\033[0m");
                $onError("DNS查询超时 host:{$host}");
            } else {
                $this->client->connect($ip, $this->port);
            }
        });
    }

    private function send()
    {
        $novaBin = self::packNova(...$this->recvArgs); // 多一个onRecv参数,不过没关系
        assert($this->client->send($novaBin));
    }

    /**
     * @param string $recv
     * @return array
     */
    private static function unpackResponse($recv)
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
        $ok = nova_encode("Com.Youzan.Nova.Framework.Generic.Service.GenericService", "invoke",
            $localIp, $localPort,
            nova_get_sequence(),
            $attach, $thriftBin, $return);
        assert($ok);
        return $return;
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

    /**
     * @param int $duration
     * @param string $prop
     * @return bool|int
     */
    private function deadline($duration, $prop)
    {
        if (property_exists($this->client, $prop)) {
            return false;
        }
        return $this->client->{$prop} = swoole_timer_after($duration, function() {
            echo "\033[1;31m", "连接超时", "\033[0m\n";
            $this->client->close();

            // TODO
        });
    }

    /**
     * @param $prop
     * @return bool
     */
    private function cancel($prop)
    {
        if (property_exists($this->client, $prop)) {
            $s = swoole_timer_clear($this->client->{$prop});
            unset($this->client->{$prop});
            return $s;
        }
        return false;
    }
}



class DnsClient
{
    const maxRetryCount = 3;
    const timeout = 100;
    private $timerId;
    public $count = 0;
    public $host;
    public $callback;

    public static function lookup($host, $callback)
    {
        $self = new static;
        $self->host = $host;
        $self->callback = $callback;
        return $self->resolve();
    }

    public function resolve()
    {
        $this->onTimeout(static::timeout);

        return swoole_async_dns_lookup($this->host, function($host, $ip) {
            if ($this->timerId && swoole_timer_exists($this->timerId)) {
                swoole_timer_clear($this->timerId);
            }
            call_user_func($this->callback, $host, $ip);
        });
    }


    public function onTimeout($duration)
    {
        if ($this->count < static::maxRetryCount) {
            $this->timerId = swoole_timer_after($duration, [$this, "resolve"]);
            $this->count++;
        } else {
            $this->timerId = swoole_timer_after($duration, function() {
                call_user_func($this->callback, $this->host, null);
            });
        }
    }
}


function sys_echo($context) {
    $workerId = isset($_SERVER["WORKER_ID"]) ? $_SERVER["WORKER_ID"] : "";
    $dataStr = date("Y-m-d H:i:s", time());
    echo "[$dataStr #$workerId] $context\n";
}