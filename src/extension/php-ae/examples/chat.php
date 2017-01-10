#! /Users/chuxiaofeng/yz_env/php/bin/php -dextension=ae.so
<?php

$chat = new Chat("127.0.0.1", 9090);
$chat->start();

class Chat
{
	private $lsock;
	private $clients = [];
	private $timerId;

	public function __construct($host, $port)
	{
		$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
		socket_bind($socket, $host, $port);
		socket_listen($socket);
		socket_set_nonblock($socket);
		$this->lsock = $socket;

		$this->onAccept();
		$this->onStdIn();
	}

	private function onStdIn()
	{
		$stdIn = fopen("php://stdin", "r");
		\Ae\EventLoop::add($stdIn, \Ae\EventLoop::READABLE, function($stdIn) {
			$recv = trim(fread($stdIn, 8192));
			$this->onCmd(0, $recv);
			$this->broadcast(0, $recv);
		});
	}

	public function start()
	{
		$this->timerId = \Ae\EventLoop::tick(3000, function($id) {
			$this->broadcast(0, "__heartbear__");
		});
		\Ae\EventLoop::start();
	}

	private function broadcast($fromCid, $msg)
	{
		if ($fromCid === 0) {
			$payload = "server: $msg\n";
		} else {
			$payload = "client<$fromCid>: $msg\n";
			echo $payload;
		}
		foreach ($this->clients as $i => $client) {
			if ($i === $fromCid) {
				continue;
			}
			
			$this->writeNow($client, $payload);
		}
	}

	private function writeNow($socket, $payload)
	{
		if (!$payload) {
			return;
		}

		$n = @socket_write($socket, $payload);
		if ($n <= 0) {
			$this->onIOError($socket);
			return;
		}
		if ($n === strlen($payload)) {
			return;
		} else {
			$this->writeAfter($socket, substr($payload, $n));
		}
	}

	private function writeAfter($socket, &$payload)
	{
		if (!$payload) {
			return;
		}

		\Ae\EventLoop::add($socket, \Ae\EventLoop::WRITABLE, function() use($socket, &$payload) {
			$n = @socket_write($client, $payload);
			if ($n <= 0) {
				$this->onIOError($socket);
				return;
			}
			
			if ($n === strlen($payload)) {
				\Ae\EventLoop::del($socket, \Ae\EventLoop::WRITABLE);
				unset($payload);			
			} else {
				$payload = substr($payload, $n);
			}
		});
	}

	private function onIOError($socket)
	{
		\Ae\EventLoop::del($socket, \Ae\EventLoop::READABLE);
		\Ae\EventLoop::del($socket, \Ae\EventLoop::WRITABLE);
		@socket_close($socket);
	}

	private function onAccept()
	{
		$ok = \Ae\EventLoop::add($this->lsock, \Ae\EventLoop::READABLE, $this->acceptHandler());
		
		if (!$ok) {
			@socket_close($this->lsock);
		}
	}

	private function onReceive($clientSock)
	{
		$ok = \Ae\EventLoop::add($clientSock, \Ae\EventLoop::READABLE, $this->receiveHandler());
		
		if (!$ok) {
			@socket_close($clientSock);
		}
	}

	private function acceptHandler()
	{
		return function($socket, $mask) {
			$clientSock = socket_accept($socket);
			socket_set_nonblock($clientSock);
			$this->writeNow($clientSock, "\n=== Welcom! ===\n");

			$cid = intval($clientSock);
			$this->broadcast($cid, "connected");

			$this->clients[intval($clientSock)] = $clientSock;

			$this->onReceive($clientSock);
		};
	}

	private function receiveHandler()
	{
		return function($socket, $mask) {
			$cid = intval($socket);
			$recv = socket_read($socket, 8192);
			if ($recv === "") {
				$this->broadcast($cid, "closed");
				\Ae\EventLoop::del($socket, \Ae\EventLoop::READABLE);
				unset($this->clients[$cid]);
			} else {
				$recv = trim($recv);
				$this->onCmd($cid, $recv);
				$this->broadcast($cid, $recv);
			}
		};
	}

	private function onCmd($cid, $cmd)
	{
		$args = explode(" ", $cmd);
		if (empty($args)) {
			return;
		}

		switch(true) {
			case $args[0] == "quit":
				if ($cid === 0) {
					\Ae\EventLoop::del($this->timerId);
					foreach ($this->clients as $client) {
						@socket_close($client);
					}
				}
				\Ae\EventLoop::stop();
				break;

		}
	}
}
