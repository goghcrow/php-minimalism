<?php
$tickMap = [];

$server = new \swoole_http_server("0.0.0.0", 8888);
$server->set([ "worker_num" => 2,
    "dispatch_mod" => 4, // IP 分配， 现实中应该使用 5 UID，按照用户id绑定到worker ,tickMap 也应该使用UID，而不是fd !!!!
]);
$server->on("close", function(\swoole_http_server $serv, $fd) {
    global $tickMap;
    if (isset($tickMap[$fd])) {
        list($hbId, $tickId) = $tickMap[$fd];
        swoole_timer_clear($hbId);
        swoole_timer_clear($tickId);
    }
});
$server->on("request", function(\swoole_http_request $req, \swoole_http_response $res) {
    global $tickMap;

    // TODO 根据 Last-Event-ID 处理断线重连
    $uri = $req->server["request_uri"];
    $method = $req->server["request_method"];
    if ($method === "GET" && $uri === "/sse") {
        $res->header("Content-Type", "text/event-stream");
        $res->header("Cache-Control", "no-cache");
        $res->header("Connection", "keep-alive");
        $res->header("Access-Control-Allow-Origin", "*");

        $res->write("retry: 1000\n"); // 断线1s重试

        // 触发client 自定义hello事件
        $res->write("event: hello\n");
        $res->write("data: world\n\n"); // hello 事件消息

        // $res->write("id: retryId\n");

        // heartbeat
        $hbId = swoole_timer_tick(5000, function() use($res) {
            $res->write(": hb");
        });

        $tickId = swoole_timer_tick(1000, function() use($res) {
            $data = time();
            $res->write("data: $data\n\n");
        });
        $tickMap[$req->fd] = [$hbId, $tickId];
    } else {
        $html = <<<'HTML'
<ul></ul> 
<script>
// API 文档 https://developer.mozilla.org/en-US/docs/Web/API/EventSource

const eventList = document.querySelector('ul')
const onMsg = (e) => {
  const newElement = document.createElement("li")
  newElement.textContent = "message: " + e.data
  eventList.appendChild(newElement)
} 

const source = new EventSource("sse", { withCredentials: true })

source.addEventListener("open", function (event) {
}, false)

source.addEventListener("message", function (event) {
  let data = event.data
  console.log("onMessage: " + data)
  onMsg(event)
}, false)


source.addEventListener("error", function (event) {
  console.error(event)
}, false)


// 自定义事件
source.addEventListener("hello", function (event) {
  let data = event.data
  console.log("onHello: " + data)
  onMsg(event)
}, false)


// source.close()
</script>
HTML;

        $res->status(200);
        $res->end($html);
    }
});
$server->start();