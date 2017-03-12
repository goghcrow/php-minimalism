<?php

/**
 * swoole
 *
 * @since 1.9.7-alpha
 *
 * iniEntries:
 * swoole.aio_thread_num = 2
 * swoole.display_errors = On
 * swoole.use_namespace = Off
 * swoole.fast_serialize = Off
 * swoole.unixsock_buffer_size = 8388608
 */



namespace 
{
	/**
	 * @since 1.9.7-alpha
	 */
	define("SWOOLE_BASE", 4);
	
	/**
	 * @since 1.9.7-alpha
	 */
	define("SWOOLE_THREAD", 2);
	
	/**
	 * @since 1.9.7-alpha
	 */
	define("SWOOLE_PROCESS", 3);
	
	/**
	 * @since 1.9.7-alpha
	 */
	define("SWOOLE_IPC_UNSOCK", 1);
	
	/**
	 * @since 1.9.7-alpha
	 */
	define("SWOOLE_IPC_MSGQUEUE", 2);
	
	/**
	 * @since 1.9.7-alpha
	 */
	define("SWOOLE_IPC_PREEMPTIVE", 3);
	
	/**
	 * @since 1.9.7-alpha
	 */
	define("SWOOLE_SOCK_TCP", 1);
	
	/**
	 * @since 1.9.7-alpha
	 */
	define("SWOOLE_SOCK_TCP6", 3);
	
	/**
	 * @since 1.9.7-alpha
	 */
	define("SWOOLE_SOCK_UDP", 2);
	
	/**
	 * @since 1.9.7-alpha
	 */
	define("SWOOLE_SOCK_UDP6", 4);
	
	/**
	 * @since 1.9.7-alpha
	 */
	define("SWOOLE_SOCK_UNIX_DGRAM", 5);
	
	/**
	 * @since 1.9.7-alpha
	 */
	define("SWOOLE_SOCK_UNIX_STREAM", 6);
	
	/**
	 * @since 1.9.7-alpha
	 */
	define("SWOOLE_TCP", 1);
	
	/**
	 * @since 1.9.7-alpha
	 */
	define("SWOOLE_TCP6", 3);
	
	/**
	 * @since 1.9.7-alpha
	 */
	define("SWOOLE_UDP", 2);
	
	/**
	 * @since 1.9.7-alpha
	 */
	define("SWOOLE_UDP6", 4);
	
	/**
	 * @since 1.9.7-alpha
	 */
	define("SWOOLE_UNIX_DGRAM", 5);
	
	/**
	 * @since 1.9.7-alpha
	 */
	define("SWOOLE_UNIX_STREAM", 6);
	
	/**
	 * @since 1.9.7-alpha
	 */
	define("SWOOLE_SOCK_SYNC", 0);
	
	/**
	 * @since 1.9.7-alpha
	 */
	define("SWOOLE_SOCK_ASYNC", 1);
	
	/**
	 * @since 1.9.7-alpha
	 */
	define("SWOOLE_SYNC", 2048);
	
	/**
	 * @since 1.9.7-alpha
	 */
	define("SWOOLE_ASYNC", 1024);
	
	/**
	 * @since 1.9.7-alpha
	 */
	define("SWOOLE_KEEP", 4096);
	
	/**
	 * @since 1.9.7-alpha
	 */
	define("SWOOLE_EVENT_READ", 512);
	
	/**
	 * @since 1.9.7-alpha
	 */
	define("SWOOLE_EVENT_WRITE", 1024);
	
	/**
	 * @since 1.9.7-alpha
	 */
	define("SWOOLE_VERSION", "1.9.7-alpha");
	
	/**
	 * @since 1.9.7-alpha
	 */
	define("SWOOLE_AIO_BASE", 0);
	
	/**
	 * @since 1.9.7-alpha
	 */
	define("SWOOLE_AIO_LINUX", 1);
	
	/**
	 * @since 1.9.7-alpha
	 */
	define("SWOOLE_FILELOCK", 2);
	
	/**
	 * @since 1.9.7-alpha
	 */
	define("SWOOLE_MUTEX", 3);
	
	/**
	 * @since 1.9.7-alpha
	 */
	define("SWOOLE_SEM", 4);
	
	/**
	 * @since 1.9.7-alpha
	 */
	define("SWOOLE_RWLOCK", 1);
	
	/**
	 * @since 1.9.7-alpha
	 */
	define("WEBSOCKET_OPCODE_TEXT", 1);
	
	/**
	 * @since 1.9.7-alpha
	 */
	define("WEBSOCKET_OPCODE_BINARY", 2);
	
	/**
	 * @since 1.9.7-alpha
	 */
	define("WEBSOCKET_OPCODE_PING", 9);
	
	/**
	 * @since 1.9.7-alpha
	 */
	define("WEBSOCKET_STATUS_CONNECTION", 1);
	
	/**
	 * @since 1.9.7-alpha
	 */
	define("WEBSOCKET_STATUS_HANDSHAKE", 2);
	
	/**
	 * @since 1.9.7-alpha
	 */
	define("WEBSOCKET_STATUS_FRAME", 3);
	
	/**
	 * @since 1.9.7-alpha
	 */
	define("WEBSOCKET_STATUS_ACTIVE", 3);
	
}


namespace 
{
	/**
	 * 
	 * @since 1.9.7-alpha
	 * 
	 * @return
	 */
	function swoole_version() {}
	
	/**
	 * 
	 * @since 1.9.7-alpha
	 * 
	 * @return
	 */
	function swoole_cpu_num() {}
	
	/**
	 * 
	 * @since 1.9.7-alpha
	 * 
	 * @return
	 */
	function swoole_last_error() {}
	
	/**
	 * 
	 * @since 1.9.7-alpha
	 * 
	 * @param $fd
	 * @param $read_callback
	 * @param $write_callback [optional]
	 * @param $events [optional]
	 * @return
	 */
	function swoole_event_add($fd, $read_callback, $write_callback = null, $events = null) {}
	
	/**
	 * 
	 * @since 1.9.7-alpha
	 * 
	 * @param $fd
	 * @param $read_callback [optional]
	 * @param $write_callback [optional]
	 * @param $events [optional]
	 * @return
	 */
	function swoole_event_set($fd, $read_callback = null, $write_callback = null, $events = null) {}
	
	/**
	 * 
	 * @since 1.9.7-alpha
	 * 
	 * @param $fd
	 * @return
	 */
	function swoole_event_del($fd) {}
	
	/**
	 * 
	 * @since 1.9.7-alpha
	 * 
	 * @return
	 */
	function swoole_event_exit() {}
	
	/**
	 * 
	 * @since 1.9.7-alpha
	 * 
	 * @return
	 */
	function swoole_event_wait() {}
	
	/**
	 * 
	 * @since 1.9.7-alpha
	 * 
	 * @param $fd
	 * @param $data
	 * @return
	 */
	function swoole_event_write($fd, $data) {}
	
	/**
	 * 
	 * @since 1.9.7-alpha
	 * 
	 * @param $callback
	 * @return
	 */
	function swoole_event_defer($callback) {}
	
	/**
	 * 
	 * @since 1.9.7-alpha
	 * 
	 * @param $ms
	 * @param $callback
	 * @param $param [optional]
	 * @return
	 */
	function swoole_timer_after($ms, $callback, $param = null) {}
	
	/**
	 * 
	 * @since 1.9.7-alpha
	 * 
	 * @param $ms
	 * @param $callback
	 * @return
	 */
	function swoole_timer_tick($ms, $callback) {}
	
	/**
	 * 
	 * @since 1.9.7-alpha
	 * 
	 * @param $timer_id
	 * @return
	 */
	function swoole_timer_exists($timer_id) {}
	
	/**
	 * 
	 * @since 1.9.7-alpha
	 * 
	 * @param $timer_id
	 * @return
	 */
	function swoole_timer_clear($timer_id) {}
	
	/**
	 * 
	 * @since 1.9.7-alpha
	 * 
	 * @param array $settings
	 * @return
	 */
	function swoole_async_set(array $settings) {}
	
	/**
	 * 
	 * @since 1.9.7-alpha
	 * 
	 * @param $filename
	 * @param $callback
	 * @param $chunk_size [optional]
	 * @param $offset [optional]
	 * @return
	 */
	function swoole_async_read($filename, $callback, $chunk_size = null, $offset = null) {}
	
	/**
	 * 
	 * @since 1.9.7-alpha
	 * 
	 * @param $filename
	 * @param $content
	 * @param $offset [optional]
	 * @param $callback [optional]
	 * @return
	 */
	function swoole_async_write($filename, $content, $offset = null, $callback = null) {}
	
	/**
	 * 
	 * @since 1.9.7-alpha
	 * 
	 * @param $filename
	 * @param $callback
	 * @return
	 */
	function swoole_async_readfile($filename, $callback) {}
	
	/**
	 * 
	 * @since 1.9.7-alpha
	 * 
	 * @param $filename
	 * @param $content
	 * @param $callback [optional]
	 * @param $flags [optional]
	 * @return
	 */
	function swoole_async_writefile($filename, $content, $callback = null, $flags = null) {}
	
	/**
	 * 
	 * @since 1.9.7-alpha
	 * 
	 * @param $domain_name
	 * @param $content
	 * @return
	 */
	function swoole_async_dns_lookup($domain_name, $content) {}
	
	/**
	 * 
	 * @since 1.9.7-alpha
	 * 
	 * @param $read_array
	 * @param $write_array
	 * @param $error_array
	 * @param $timeout [optional]
	 * @return
	 */
	function swoole_client_select(&$read_array, &$write_array, &$error_array, $timeout = null) {}
	
	/**
	 * 
	 * @since 1.9.7-alpha
	 * 
	 * @param $read_array
	 * @param $write_array
	 * @param $error_array
	 * @param $timeout [optional]
	 * @return
	 */
	function swoole_select(&$read_array, &$write_array, &$error_array, $timeout = null) {}
	
	/**
	 * 
	 * @since 1.9.7-alpha
	 * 
	 * @param $process_name
	 * @return
	 */
	function swoole_set_process_name($process_name) {}
	
	/**
	 * 
	 * @since 1.9.7-alpha
	 * 
	 * @return
	 */
	function swoole_get_local_ip() {}
	
	/**
	 * 
	 * @since 1.9.7-alpha
	 * 
	 * @param $errno
	 * @return
	 */
	function swoole_strerror($errno) {}
	
	/**
	 * 
	 * @since 1.9.7-alpha
	 * 
	 * @return
	 */
	function swoole_errno() {}
	
	/**
	 * 
	 * @since 1.9.7-alpha
	 * 
	 * @return
	 */
	function swoole_load_module() {}
	
}


namespace 
{
	/**
	 * swoole_server
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_server
	{
	
		/**
		 * __construct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $host
		 * @param $port [optional]
		 * @param $mode [optional]
		 * @param $sock_type [optional]
		 * @return
		 */
		public function __construct($host, $port = null, $mode = null, $sock_type = null) {} 
	
		/**
		 * listen
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $host
		 * @param $port
		 * @param $sock_type
		 * @return
		 */
		public function listen($host, $port, $sock_type) {} 
	
		/**
		 * addlistener
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $host
		 * @param $port
		 * @param $sock_type
		 * @return
		 */
		public function addlistener($host, $port, $sock_type) {} 
	
		/**
		 * on
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $event_name
		 * @param $callback
		 * @return
		 */
		public function on($event_name, $callback) {} 
	
		/**
		 * set
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param array $settings
		 * @return
		 */
		public function set(array $settings) {} 
	
		/**
		 * start
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function start() {} 
	
		/**
		 * send
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $send_data
		 * @param $reactor_id [optional]
		 * @return
		 */
		public function send($fd, $send_data, $reactor_id = null) {} 
	
		/**
		 * sendto
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $ip
		 * @param $port
		 * @param $send_data
		 * @param $server_socket [optional]
		 * @return
		 */
		public function sendto($ip, $port, $send_data, $server_socket = null) {} 
	
		/**
		 * sendwait
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $conn_fd
		 * @param $send_data
		 * @return
		 */
		public function sendwait($conn_fd, $send_data) {} 
	
		/**
		 * exist
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @return
		 */
		public function exist($fd) {} 
	
		/**
		 * protect
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $is_protected [optional]
		 * @return
		 */
		public function protect($fd, $is_protected = null) {} 
	
		/**
		 * sendfile
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $conn_fd
		 * @param $filename
		 * @param $offset [optional]
		 * @return
		 */
		public function sendfile($conn_fd, $filename, $offset = null) {} 
	
		/**
		 * close
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $reset [optional]
		 * @return
		 */
		public function close($fd, $reset = null) {} 
	
		/**
		 * confirm
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @return
		 */
		public function confirm($fd) {} 
	
		/**
		 * pause
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @return
		 */
		public function pause($fd) {} 
	
		/**
		 * resume
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @return
		 */
		public function resume($fd) {} 
	
		/**
		 * task
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $data
		 * @param $worker_id [optional]
		 * @param $finish_callback [optional]
		 * @return
		 */
		public function task($data, $worker_id = null, $finish_callback = null) {} 
	
		/**
		 * taskwait
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $data
		 * @param $timeout [optional]
		 * @param $worker_id [optional]
		 * @return
		 */
		public function taskwait($data, $timeout = null, $worker_id = null) {} 
	
		/**
		 * taskWaitMulti
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param array $tasks
		 * @param $timeout [optional]
		 * @return
		 */
		public function taskWaitMulti(array $tasks, $timeout = null) {} 
	
		/**
		 * finish
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $data
		 * @return
		 */
		public function finish($data) {} 
	
		/**
		 * reload
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function reload() {} 
	
		/**
		 * shutdown
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function shutdown() {} 
	
		/**
		 * stop
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $worker_id [optional]
		 * @return
		 */
		public function stop($worker_id = null) {} 
	
		/**
		 * getLastError
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function getLastError() {} 
	
		/**
		 * heartbeat
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $reactor_id
		 * @return
		 */
		public function heartbeat($reactor_id) {} 
	
		/**
		 * connection_info
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $reactor_id [optional]
		 * @return
		 */
		public function connection_info($fd, $reactor_id = null) {} 
	
		/**
		 * connection_list
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $start_fd
		 * @param $find_count [optional]
		 * @return
		 */
		public function connection_list($start_fd, $find_count = null) {} 
	
		/**
		 * getClientInfo
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $reactor_id [optional]
		 * @return
		 */
		public function getClientInfo($fd, $reactor_id = null) {} 
	
		/**
		 * getClientList
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $start_fd
		 * @param $find_count [optional]
		 * @return
		 */
		public function getClientList($start_fd, $find_count = null) {} 
	
		/**
		 * after
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $ms
		 * @param $callback
		 * @param $param [optional]
		 * @return
		 */
		public function after($ms, $callback, $param = null) {} 
	
		/**
		 * tick
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $ms
		 * @param $callback
		 * @return
		 */
		public function tick($ms, $callback) {} 
	
		/**
		 * clearTimer
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $timer_id
		 * @return
		 */
		public function clearTimer($timer_id) {} 
	
		/**
		 * defer
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $callback
		 * @return
		 */
		public function defer($callback) {} 
	
		/**
		 * sendMessage
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $dst_worker_id
		 * @param $data
		 * @return
		 */
		public function sendMessage($dst_worker_id, $data) {} 
	
		/**
		 * addProcess
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param swoole_process $process
		 * @return
		 */
		public function addProcess(swoole_process $process) {} 
	
		/**
		 * stats
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function stats() {} 
	
		/**
		 * bind
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $uid
		 * @return
		 */
		public function bind($fd, $uid) {} 
	
	}
	
	
	/**
	 * swoole_server
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_server
	{
	
		/**
		 * __construct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $host
		 * @param $port [optional]
		 * @param $mode [optional]
		 * @param $sock_type [optional]
		 * @return
		 */
		public function __construct($host, $port = null, $mode = null, $sock_type = null) {} 
	
		/**
		 * listen
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $host
		 * @param $port
		 * @param $sock_type
		 * @return
		 */
		public function listen($host, $port, $sock_type) {} 
	
		/**
		 * addlistener
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $host
		 * @param $port
		 * @param $sock_type
		 * @return
		 */
		public function addlistener($host, $port, $sock_type) {} 
	
		/**
		 * on
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $event_name
		 * @param $callback
		 * @return
		 */
		public function on($event_name, $callback) {} 
	
		/**
		 * set
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param array $settings
		 * @return
		 */
		public function set(array $settings) {} 
	
		/**
		 * start
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function start() {} 
	
		/**
		 * send
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $send_data
		 * @param $reactor_id [optional]
		 * @return
		 */
		public function send($fd, $send_data, $reactor_id = null) {} 
	
		/**
		 * sendto
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $ip
		 * @param $port
		 * @param $send_data
		 * @param $server_socket [optional]
		 * @return
		 */
		public function sendto($ip, $port, $send_data, $server_socket = null) {} 
	
		/**
		 * sendwait
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $conn_fd
		 * @param $send_data
		 * @return
		 */
		public function sendwait($conn_fd, $send_data) {} 
	
		/**
		 * exist
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @return
		 */
		public function exist($fd) {} 
	
		/**
		 * protect
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $is_protected [optional]
		 * @return
		 */
		public function protect($fd, $is_protected = null) {} 
	
		/**
		 * sendfile
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $conn_fd
		 * @param $filename
		 * @param $offset [optional]
		 * @return
		 */
		public function sendfile($conn_fd, $filename, $offset = null) {} 
	
		/**
		 * close
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $reset [optional]
		 * @return
		 */
		public function close($fd, $reset = null) {} 
	
		/**
		 * confirm
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @return
		 */
		public function confirm($fd) {} 
	
		/**
		 * pause
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @return
		 */
		public function pause($fd) {} 
	
		/**
		 * resume
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @return
		 */
		public function resume($fd) {} 
	
		/**
		 * task
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $data
		 * @param $worker_id [optional]
		 * @param $finish_callback [optional]
		 * @return
		 */
		public function task($data, $worker_id = null, $finish_callback = null) {} 
	
		/**
		 * taskwait
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $data
		 * @param $timeout [optional]
		 * @param $worker_id [optional]
		 * @return
		 */
		public function taskwait($data, $timeout = null, $worker_id = null) {} 
	
		/**
		 * taskWaitMulti
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param array $tasks
		 * @param $timeout [optional]
		 * @return
		 */
		public function taskWaitMulti(array $tasks, $timeout = null) {} 
	
		/**
		 * finish
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $data
		 * @return
		 */
		public function finish($data) {} 
	
		/**
		 * reload
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function reload() {} 
	
		/**
		 * shutdown
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function shutdown() {} 
	
		/**
		 * stop
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $worker_id [optional]
		 * @return
		 */
		public function stop($worker_id = null) {} 
	
		/**
		 * getLastError
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function getLastError() {} 
	
		/**
		 * heartbeat
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $reactor_id
		 * @return
		 */
		public function heartbeat($reactor_id) {} 
	
		/**
		 * connection_info
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $reactor_id [optional]
		 * @return
		 */
		public function connection_info($fd, $reactor_id = null) {} 
	
		/**
		 * connection_list
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $start_fd
		 * @param $find_count [optional]
		 * @return
		 */
		public function connection_list($start_fd, $find_count = null) {} 
	
		/**
		 * getClientInfo
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $reactor_id [optional]
		 * @return
		 */
		public function getClientInfo($fd, $reactor_id = null) {} 
	
		/**
		 * getClientList
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $start_fd
		 * @param $find_count [optional]
		 * @return
		 */
		public function getClientList($start_fd, $find_count = null) {} 
	
		/**
		 * after
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $ms
		 * @param $callback
		 * @param $param [optional]
		 * @return
		 */
		public function after($ms, $callback, $param = null) {} 
	
		/**
		 * tick
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $ms
		 * @param $callback
		 * @return
		 */
		public function tick($ms, $callback) {} 
	
		/**
		 * clearTimer
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $timer_id
		 * @return
		 */
		public function clearTimer($timer_id) {} 
	
		/**
		 * defer
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $callback
		 * @return
		 */
		public function defer($callback) {} 
	
		/**
		 * sendMessage
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $dst_worker_id
		 * @param $data
		 * @return
		 */
		public function sendMessage($dst_worker_id, $data) {} 
	
		/**
		 * addProcess
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param swoole_process $process
		 * @return
		 */
		public function addProcess(swoole_process $process) {} 
	
		/**
		 * stats
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function stats() {} 
	
		/**
		 * bind
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $uid
		 * @return
		 */
		public function bind($fd, $uid) {} 
	
	}
	
	
	/**
	 * swoole_timer
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_timer
	{
	
		/**
		 * tick
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $ms
		 * @param $callback
		 * @param $param [optional]
		 * @return
		 */
		public static function tick($ms, $callback, $param = null) {} 
	
		/**
		 * after
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $ms
		 * @param $callback
		 * @return
		 */
		public static function after($ms, $callback) {} 
	
		/**
		 * exists
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $timer_id
		 * @return
		 */
		public static function exists($timer_id) {} 
	
		/**
		 * clear
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $timer_id
		 * @return
		 */
		public static function clear($timer_id) {} 
	
	}
	
	
	/**
	 * swoole_timer
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_timer
	{
	
		/**
		 * tick
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $ms
		 * @param $callback
		 * @param $param [optional]
		 * @return
		 */
		public static function tick($ms, $callback, $param = null) {} 
	
		/**
		 * after
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $ms
		 * @param $callback
		 * @return
		 */
		public static function after($ms, $callback) {} 
	
		/**
		 * exists
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $timer_id
		 * @return
		 */
		public static function exists($timer_id) {} 
	
		/**
		 * clear
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $timer_id
		 * @return
		 */
		public static function clear($timer_id) {} 
	
	}
	
	
	/**
	 * swoole_event
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_event
	{
	
		/**
		 * add
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $read_callback
		 * @param $write_callback [optional]
		 * @param $events [optional]
		 * @return
		 */
		public static function add($fd, $read_callback, $write_callback = null, $events = null) {} 
	
		/**
		 * del
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @return
		 */
		public static function del($fd) {} 
	
		/**
		 * set
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $read_callback [optional]
		 * @param $write_callback [optional]
		 * @param $events [optional]
		 * @return
		 */
		public static function set($fd, $read_callback = null, $write_callback = null, $events = null) {} 
	
		/**
		 * exit
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public static function exit() {} 
	
		/**
		 * write
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $data
		 * @return
		 */
		public static function write($fd, $data) {} 
	
		/**
		 * wait
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public static function wait() {} 
	
		/**
		 * defer
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $callback
		 * @return
		 */
		public static function defer($callback) {} 
	
	}
	
	
	/**
	 * swoole_event
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_event
	{
	
		/**
		 * add
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $read_callback
		 * @param $write_callback [optional]
		 * @param $events [optional]
		 * @return
		 */
		public static function add($fd, $read_callback, $write_callback = null, $events = null) {} 
	
		/**
		 * del
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @return
		 */
		public static function del($fd) {} 
	
		/**
		 * set
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $read_callback [optional]
		 * @param $write_callback [optional]
		 * @param $events [optional]
		 * @return
		 */
		public static function set($fd, $read_callback = null, $write_callback = null, $events = null) {} 
	
		/**
		 * exit
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public static function exit() {} 
	
		/**
		 * write
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $data
		 * @return
		 */
		public static function write($fd, $data) {} 
	
		/**
		 * wait
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public static function wait() {} 
	
		/**
		 * defer
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $callback
		 * @return
		 */
		public static function defer($callback) {} 
	
	}
	
	
	/**
	 * swoole_async
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_async
	{
	
		/**
		 * read
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $filename
		 * @param $callback
		 * @param $chunk_size [optional]
		 * @param $offset [optional]
		 * @return
		 */
		public static function read($filename, $callback, $chunk_size = null, $offset = null) {} 
	
		/**
		 * write
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $filename
		 * @param $content
		 * @param $offset [optional]
		 * @param $callback [optional]
		 * @return
		 */
		public static function write($filename, $content, $offset = null, $callback = null) {} 
	
		/**
		 * readFile
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $filename
		 * @param $callback
		 * @return
		 */
		public static function readFile($filename, $callback) {} 
	
		/**
		 * writeFile
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $filename
		 * @param $content
		 * @param $callback [optional]
		 * @param $flags [optional]
		 * @return
		 */
		public static function writeFile($filename, $content, $callback = null, $flags = null) {} 
	
		/**
		 * dnsLookup
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $domain_name
		 * @param $content
		 * @return
		 */
		public static function dnsLookup($domain_name, $content) {} 
	
		/**
		 * set
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param array $settings
		 * @return
		 */
		public static function set(array $settings) {} 
	
	}
	
	
	/**
	 * swoole_async
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_async
	{
	
		/**
		 * read
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $filename
		 * @param $callback
		 * @param $chunk_size [optional]
		 * @param $offset [optional]
		 * @return
		 */
		public static function read($filename, $callback, $chunk_size = null, $offset = null) {} 
	
		/**
		 * write
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $filename
		 * @param $content
		 * @param $offset [optional]
		 * @param $callback [optional]
		 * @return
		 */
		public static function write($filename, $content, $offset = null, $callback = null) {} 
	
		/**
		 * readFile
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $filename
		 * @param $callback
		 * @return
		 */
		public static function readFile($filename, $callback) {} 
	
		/**
		 * writeFile
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $filename
		 * @param $content
		 * @param $callback [optional]
		 * @param $flags [optional]
		 * @return
		 */
		public static function writeFile($filename, $content, $callback = null, $flags = null) {} 
	
		/**
		 * dnsLookup
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $domain_name
		 * @param $content
		 * @return
		 */
		public static function dnsLookup($domain_name, $content) {} 
	
		/**
		 * set
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param array $settings
		 * @return
		 */
		public static function set(array $settings) {} 
	
	}
	
	
	/**
	 * swoole_connection_iterator
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_connection_iterator implements \Iterator, \Traversable, \Countable, \ArrayAccess
	{
	
		/**
		 * rewind
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function rewind() {} 
	
		/**
		 * next
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function next() {} 
	
		/**
		 * current
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function current() {} 
	
		/**
		 * key
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function key() {} 
	
		/**
		 * valid
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function valid() {} 
	
		/**
		 * count
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function count() {} 
	
		/**
		 * offsetExists
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @return
		 */
		public function offsetExists($fd) {} 
	
		/**
		 * offsetGet
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @return
		 */
		public function offsetGet($fd) {} 
	
		/**
		 * offsetSet
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $value
		 * @return
		 */
		public function offsetSet($fd, $value) {} 
	
		/**
		 * offsetUnset
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @return
		 */
		public function offsetUnset($fd) {} 
	
	}
	
	
	/**
	 * swoole_connection_iterator
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_connection_iterator implements \Iterator, \Traversable, \Countable, \ArrayAccess
	{
	
		/**
		 * rewind
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function rewind() {} 
	
		/**
		 * next
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function next() {} 
	
		/**
		 * current
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function current() {} 
	
		/**
		 * key
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function key() {} 
	
		/**
		 * valid
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function valid() {} 
	
		/**
		 * count
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function count() {} 
	
		/**
		 * offsetExists
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @return
		 */
		public function offsetExists($fd) {} 
	
		/**
		 * offsetGet
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @return
		 */
		public function offsetGet($fd) {} 
	
		/**
		 * offsetSet
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $value
		 * @return
		 */
		public function offsetSet($fd, $value) {} 
	
		/**
		 * offsetUnset
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @return
		 */
		public function offsetUnset($fd) {} 
	
	}
	
	
	/**
	 * swoole_exception
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_exception extends \Exception
	{
	
		protected $message = "";
		protected $code = 0;
		protected $file;
		protected $line;
	
		/**
		 * __clone
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		final private function __clone() {} 
	
		/**
		 * __construct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $message [optional]
		 * @param $code [optional]
		 * @param $previous [optional]
		 * @return
		 */
		public function __construct($message = null, $code = null, $previous = null) {} 
	
		/**
		 * __wakeup
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function __wakeup() {} 
	
		/**
		 * getMessage
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		final public function getMessage() {} 
	
		/**
		 * getCode
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		final public function getCode() {} 
	
		/**
		 * getFile
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		final public function getFile() {} 
	
		/**
		 * getLine
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		final public function getLine() {} 
	
		/**
		 * getTrace
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		final public function getTrace() {} 
	
		/**
		 * getPrevious
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		final public function getPrevious() {} 
	
		/**
		 * getTraceAsString
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		final public function getTraceAsString() {} 
	
		/**
		 * __toString
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function __toString() {} 
	
	}
	
	
	/**
	 * swoole_exception
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_exception extends \Exception
	{
	
		protected $message = "";
		protected $code = 0;
		protected $file;
		protected $line;
	
		/**
		 * __clone
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		final private function __clone() {} 
	
		/**
		 * __construct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $message [optional]
		 * @param $code [optional]
		 * @param $previous [optional]
		 * @return
		 */
		public function __construct($message = null, $code = null, $previous = null) {} 
	
		/**
		 * __wakeup
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function __wakeup() {} 
	
		/**
		 * getMessage
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		final public function getMessage() {} 
	
		/**
		 * getCode
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		final public function getCode() {} 
	
		/**
		 * getFile
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		final public function getFile() {} 
	
		/**
		 * getLine
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		final public function getLine() {} 
	
		/**
		 * getTrace
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		final public function getTrace() {} 
	
		/**
		 * getPrevious
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		final public function getPrevious() {} 
	
		/**
		 * getTraceAsString
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		final public function getTraceAsString() {} 
	
		/**
		 * __toString
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function __toString() {} 
	
	}
	
	
	/**
	 * swoole_server_port
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_server_port
	{
	
		/**
		 * __construct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		private function __construct() {} 
	
		/**
		 * __destruct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function __destruct() {} 
	
		/**
		 * set
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param array $settings
		 * @return
		 */
		public function set(array $settings) {} 
	
		/**
		 * on
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $event_name
		 * @param $callback
		 * @return
		 */
		public function on($event_name, $callback) {} 
	
	}
	
	
	/**
	 * swoole_server_port
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_server_port
	{
	
		/**
		 * __construct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		private function __construct() {} 
	
		/**
		 * __destruct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function __destruct() {} 
	
		/**
		 * set
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param array $settings
		 * @return
		 */
		public function set(array $settings) {} 
	
		/**
		 * on
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $event_name
		 * @param $callback
		 * @return
		 */
		public function on($event_name, $callback) {} 
	
	}
	
	
	/**
	 * swoole_client
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_client
	{
	
		const MSG_OOB = 1;
		const MSG_PEEK = 2;
		const MSG_DONTWAIT = 128;
		const MSG_WAITALL = 64;
	
		public $errCode = 0;
		public $sock = 0;
		public $reuse = "";
		public $reuseCount = 0;
	
		/**
		 * __construct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $type
		 * @param $async [optional]
		 * @return
		 */
		public function __construct($type, $async = null) {} 
	
		/**
		 * __destruct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function __destruct() {} 
	
		/**
		 * set
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param array $settings
		 * @return
		 */
		public function set(array $settings) {} 
	
		/**
		 * connect
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $host
		 * @param $port [optional]
		 * @param $timeout [optional]
		 * @param $sock_flag [optional]
		 * @return
		 */
		public function connect($host, $port = null, $timeout = null, $sock_flag = null) {} 
	
		/**
		 * recv
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $size [optional]
		 * @param $flag [optional]
		 * @return
		 */
		public function recv($size = null, $flag = null) {} 
	
		/**
		 * send
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $data
		 * @param $flag [optional]
		 * @return
		 */
		public function send($data, $flag = null) {} 
	
		/**
		 * pipe
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $dst_socket
		 * @return
		 */
		public function pipe($dst_socket) {} 
	
		/**
		 * sendfile
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $filename
		 * @param $offset [optional]
		 * @return
		 */
		public function sendfile($filename, $offset = null) {} 
	
		/**
		 * sendto
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $ip
		 * @param $port
		 * @param $data
		 * @return
		 */
		public function sendto($ip, $port, $data) {} 
	
		/**
		 * sleep
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function sleep() {} 
	
		/**
		 * wakeup
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function wakeup() {} 
	
		/**
		 * pause
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function pause() {} 
	
		/**
		 * resume
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function resume() {} 
	
		/**
		 * isConnected
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function isConnected() {} 
	
		/**
		 * getsockname
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function getsockname() {} 
	
		/**
		 * getpeername
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function getpeername() {} 
	
		/**
		 * close
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $force [optional]
		 * @return
		 */
		public function close($force = null) {} 
	
		/**
		 * on
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $event_name
		 * @param $callback
		 * @return
		 */
		public function on($event_name, $callback) {} 
	
	}
	
	
	/**
	 * swoole_client
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_client
	{
	
		const MSG_OOB = 1;
		const MSG_PEEK = 2;
		const MSG_DONTWAIT = 128;
		const MSG_WAITALL = 64;
	
		public $errCode = 0;
		public $sock = 0;
		public $reuse = "";
		public $reuseCount = 0;
	
		/**
		 * __construct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $type
		 * @param $async [optional]
		 * @return
		 */
		public function __construct($type, $async = null) {} 
	
		/**
		 * __destruct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function __destruct() {} 
	
		/**
		 * set
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param array $settings
		 * @return
		 */
		public function set(array $settings) {} 
	
		/**
		 * connect
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $host
		 * @param $port [optional]
		 * @param $timeout [optional]
		 * @param $sock_flag [optional]
		 * @return
		 */
		public function connect($host, $port = null, $timeout = null, $sock_flag = null) {} 
	
		/**
		 * recv
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $size [optional]
		 * @param $flag [optional]
		 * @return
		 */
		public function recv($size = null, $flag = null) {} 
	
		/**
		 * send
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $data
		 * @param $flag [optional]
		 * @return
		 */
		public function send($data, $flag = null) {} 
	
		/**
		 * pipe
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $dst_socket
		 * @return
		 */
		public function pipe($dst_socket) {} 
	
		/**
		 * sendfile
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $filename
		 * @param $offset [optional]
		 * @return
		 */
		public function sendfile($filename, $offset = null) {} 
	
		/**
		 * sendto
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $ip
		 * @param $port
		 * @param $data
		 * @return
		 */
		public function sendto($ip, $port, $data) {} 
	
		/**
		 * sleep
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function sleep() {} 
	
		/**
		 * wakeup
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function wakeup() {} 
	
		/**
		 * pause
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function pause() {} 
	
		/**
		 * resume
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function resume() {} 
	
		/**
		 * isConnected
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function isConnected() {} 
	
		/**
		 * getsockname
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function getsockname() {} 
	
		/**
		 * getpeername
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function getpeername() {} 
	
		/**
		 * close
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $force [optional]
		 * @return
		 */
		public function close($force = null) {} 
	
		/**
		 * on
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $event_name
		 * @param $callback
		 * @return
		 */
		public function on($event_name, $callback) {} 
	
	}
	
	
	/**
	 * swoole_http_client
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_http_client
	{
	
		public $errCode = 0;
		public $sock = 0;
	
		/**
		 * __construct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $host
		 * @param $port [optional]
		 * @param $ssl [optional]
		 * @return
		 */
		public function __construct($host, $port = null, $ssl = null) {} 
	
		/**
		 * __destruct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function __destruct() {} 
	
		/**
		 * set
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param array $settings
		 * @return
		 */
		public function set(array $settings) {} 
	
		/**
		 * setMethod
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $method
		 * @return
		 */
		public function setMethod($method) {} 
	
		/**
		 * setHeaders
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param array $headers
		 * @return
		 */
		public function setHeaders(array $headers) {} 
	
		/**
		 * setCookies
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param array $cookies
		 * @return
		 */
		public function setCookies(array $cookies) {} 
	
		/**
		 * setData
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $data
		 * @return
		 */
		public function setData($data) {} 
	
		/**
		 * addFile
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $path
		 * @param $name
		 * @param $type [optional]
		 * @param $filename [optional]
		 * @param $offset [optional]
		 * @return
		 */
		public function addFile($path, $name, $type = null, $filename = null, $offset = null) {} 
	
		/**
		 * execute
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $path
		 * @param $callback
		 * @return
		 */
		public function execute($path, $callback) {} 
	
		/**
		 * push
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $data
		 * @param $opcode [optional]
		 * @param $finish [optional]
		 * @return
		 */
		public function push($data, $opcode = null, $finish = null) {} 
	
		/**
		 * get
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $path
		 * @param $callback
		 * @return
		 */
		public function get($path, $callback) {} 
	
		/**
		 * post
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $path
		 * @param $data
		 * @param $callback
		 * @return
		 */
		public function post($path, $data, $callback) {} 
	
		/**
		 * upgrade
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $path
		 * @param $callback
		 * @return
		 */
		public function upgrade($path, $callback) {} 
	
		/**
		 * download
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $path
		 * @param $file
		 * @param $callback
		 * @param $offset [optional]
		 * @return
		 */
		public function download($path, $file, $callback, $offset = null) {} 
	
		/**
		 * isConnected
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function isConnected() {} 
	
		/**
		 * close
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function close() {} 
	
		/**
		 * on
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $event_name
		 * @param $callback
		 * @return
		 */
		public function on($event_name, $callback) {} 
	
	}
	
	
	/**
	 * swoole_http_client
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_http_client
	{
	
		public $errCode = 0;
		public $sock = 0;
	
		/**
		 * __construct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $host
		 * @param $port [optional]
		 * @param $ssl [optional]
		 * @return
		 */
		public function __construct($host, $port = null, $ssl = null) {} 
	
		/**
		 * __destruct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function __destruct() {} 
	
		/**
		 * set
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param array $settings
		 * @return
		 */
		public function set(array $settings) {} 
	
		/**
		 * setMethod
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $method
		 * @return
		 */
		public function setMethod($method) {} 
	
		/**
		 * setHeaders
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param array $headers
		 * @return
		 */
		public function setHeaders(array $headers) {} 
	
		/**
		 * setCookies
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param array $cookies
		 * @return
		 */
		public function setCookies(array $cookies) {} 
	
		/**
		 * setData
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $data
		 * @return
		 */
		public function setData($data) {} 
	
		/**
		 * addFile
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $path
		 * @param $name
		 * @param $type [optional]
		 * @param $filename [optional]
		 * @param $offset [optional]
		 * @return
		 */
		public function addFile($path, $name, $type = null, $filename = null, $offset = null) {} 
	
		/**
		 * execute
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $path
		 * @param $callback
		 * @return
		 */
		public function execute($path, $callback) {} 
	
		/**
		 * push
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $data
		 * @param $opcode [optional]
		 * @param $finish [optional]
		 * @return
		 */
		public function push($data, $opcode = null, $finish = null) {} 
	
		/**
		 * get
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $path
		 * @param $callback
		 * @return
		 */
		public function get($path, $callback) {} 
	
		/**
		 * post
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $path
		 * @param $data
		 * @param $callback
		 * @return
		 */
		public function post($path, $data, $callback) {} 
	
		/**
		 * upgrade
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $path
		 * @param $callback
		 * @return
		 */
		public function upgrade($path, $callback) {} 
	
		/**
		 * download
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $path
		 * @param $file
		 * @param $callback
		 * @param $offset [optional]
		 * @return
		 */
		public function download($path, $file, $callback, $offset = null) {} 
	
		/**
		 * isConnected
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function isConnected() {} 
	
		/**
		 * close
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function close() {} 
	
		/**
		 * on
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $event_name
		 * @param $callback
		 * @return
		 */
		public function on($event_name, $callback) {} 
	
	}
	
	
	/**
	 * swoole_process
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_process
	{
	
		const IPC_NOWAIT = 256;
	
		/**
		 * __construct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $callback
		 * @param $redirect_stdin_and_stdout [optional]
		 * @param $pipe_type [optional]
		 * @return
		 */
		public function __construct($callback, $redirect_stdin_and_stdout = null, $pipe_type = null) {} 
	
		/**
		 * __destruct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function __destruct() {} 
	
		/**
		 * wait
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $blocking [optional]
		 * @return
		 */
		public static function wait($blocking = null) {} 
	
		/**
		 * signal
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $signal_no
		 * @param $callback
		 * @return
		 */
		public static function signal($signal_no, $callback) {} 
	
		/**
		 * alarm
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $usec
		 * @return
		 */
		public static function alarm($usec) {} 
	
		/**
		 * kill
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $pid
		 * @param $signal_no [optional]
		 * @return
		 */
		public static function kill($pid, $signal_no = null) {} 
	
		/**
		 * daemon
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $nochdir [optional]
		 * @param $noclose [optional]
		 * @return
		 */
		public static function daemon($nochdir = null, $noclose = null) {} 
	
		/**
		 * useQueue
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $key
		 * @param $mode [optional]
		 * @return
		 */
		public function useQueue($key, $mode = null) {} 
	
		/**
		 * statQueue
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function statQueue() {} 
	
		/**
		 * freeQueue
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function freeQueue() {} 
	
		/**
		 * start
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function start() {} 
	
		/**
		 * write
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $data
		 * @return
		 */
		public function write($data) {} 
	
		/**
		 * close
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function close() {} 
	
		/**
		 * read
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $size [optional]
		 * @return
		 */
		public function read($size = null) {} 
	
		/**
		 * push
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $data
		 * @return
		 */
		public function push($data) {} 
	
		/**
		 * pop
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $size [optional]
		 * @return
		 */
		public function pop($size = null) {} 
	
		/**
		 * exit
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $exit_code [optional]
		 * @return
		 */
		public function exit($exit_code = null) {} 
	
		/**
		 * exec
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $exec_file
		 * @param $args
		 * @return
		 */
		public function exec($exec_file, $args) {} 
	
		/**
		 * name
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $process_name
		 * @return
		 */
		public function name($process_name) {} 
	
	}
	
	
	/**
	 * swoole_process
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_process
	{
	
		const IPC_NOWAIT = 256;
	
		/**
		 * __construct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $callback
		 * @param $redirect_stdin_and_stdout [optional]
		 * @param $pipe_type [optional]
		 * @return
		 */
		public function __construct($callback, $redirect_stdin_and_stdout = null, $pipe_type = null) {} 
	
		/**
		 * __destruct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function __destruct() {} 
	
		/**
		 * wait
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $blocking [optional]
		 * @return
		 */
		public static function wait($blocking = null) {} 
	
		/**
		 * signal
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $signal_no
		 * @param $callback
		 * @return
		 */
		public static function signal($signal_no, $callback) {} 
	
		/**
		 * alarm
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $usec
		 * @return
		 */
		public static function alarm($usec) {} 
	
		/**
		 * kill
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $pid
		 * @param $signal_no [optional]
		 * @return
		 */
		public static function kill($pid, $signal_no = null) {} 
	
		/**
		 * daemon
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $nochdir [optional]
		 * @param $noclose [optional]
		 * @return
		 */
		public static function daemon($nochdir = null, $noclose = null) {} 
	
		/**
		 * useQueue
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $key
		 * @param $mode [optional]
		 * @return
		 */
		public function useQueue($key, $mode = null) {} 
	
		/**
		 * statQueue
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function statQueue() {} 
	
		/**
		 * freeQueue
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function freeQueue() {} 
	
		/**
		 * start
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function start() {} 
	
		/**
		 * write
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $data
		 * @return
		 */
		public function write($data) {} 
	
		/**
		 * close
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function close() {} 
	
		/**
		 * read
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $size [optional]
		 * @return
		 */
		public function read($size = null) {} 
	
		/**
		 * push
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $data
		 * @return
		 */
		public function push($data) {} 
	
		/**
		 * pop
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $size [optional]
		 * @return
		 */
		public function pop($size = null) {} 
	
		/**
		 * exit
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $exit_code [optional]
		 * @return
		 */
		public function exit($exit_code = null) {} 
	
		/**
		 * exec
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $exec_file
		 * @param $args
		 * @return
		 */
		public function exec($exec_file, $args) {} 
	
		/**
		 * name
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $process_name
		 * @return
		 */
		public function name($process_name) {} 
	
	}
	
	
	/**
	 * swoole_table
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_table implements \Iterator, \Traversable, \Countable
	{
	
		const TYPE_INT = 1;
		const TYPE_STRING = 7;
		const TYPE_FLOAT = 6;
	
		/**
		 * __construct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $table_size
		 * @return
		 */
		public function __construct($table_size) {} 
	
		/**
		 * column
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $name
		 * @param $type
		 * @param $size [optional]
		 * @return
		 */
		public function column($name, $type, $size = null) {} 
	
		/**
		 * create
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function create() {} 
	
		/**
		 * destroy
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function destroy() {} 
	
		/**
		 * set
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $key
		 * @param array $value
		 * @return
		 */
		public function set($key, array $value) {} 
	
		/**
		 * get
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $key
		 * @return
		 */
		public function get($key) {} 
	
		/**
		 * count
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function count() {} 
	
		/**
		 * del
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $key
		 * @return
		 */
		public function del($key) {} 
	
		/**
		 * exist
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $key
		 * @return
		 */
		public function exist($key) {} 
	
		/**
		 * incr
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $key
		 * @param $column
		 * @param $incrby [optional]
		 * @return
		 */
		public function incr($key, $column, $incrby = null) {} 
	
		/**
		 * decr
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $key
		 * @param $column
		 * @param $decrby [optional]
		 * @return
		 */
		public function decr($key, $column, $decrby = null) {} 
	
		/**
		 * rewind
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function rewind() {} 
	
		/**
		 * next
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function next() {} 
	
		/**
		 * current
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function current() {} 
	
		/**
		 * key
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function key() {} 
	
		/**
		 * valid
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function valid() {} 
	
	}
	
	
	/**
	 * swoole_table
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_table implements \Iterator, \Traversable, \Countable
	{
	
		const TYPE_INT = 1;
		const TYPE_STRING = 7;
		const TYPE_FLOAT = 6;
	
		/**
		 * __construct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $table_size
		 * @return
		 */
		public function __construct($table_size) {} 
	
		/**
		 * column
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $name
		 * @param $type
		 * @param $size [optional]
		 * @return
		 */
		public function column($name, $type, $size = null) {} 
	
		/**
		 * create
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function create() {} 
	
		/**
		 * destroy
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function destroy() {} 
	
		/**
		 * set
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $key
		 * @param array $value
		 * @return
		 */
		public function set($key, array $value) {} 
	
		/**
		 * get
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $key
		 * @return
		 */
		public function get($key) {} 
	
		/**
		 * count
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function count() {} 
	
		/**
		 * del
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $key
		 * @return
		 */
		public function del($key) {} 
	
		/**
		 * exist
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $key
		 * @return
		 */
		public function exist($key) {} 
	
		/**
		 * incr
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $key
		 * @param $column
		 * @param $incrby [optional]
		 * @return
		 */
		public function incr($key, $column, $incrby = null) {} 
	
		/**
		 * decr
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $key
		 * @param $column
		 * @param $decrby [optional]
		 * @return
		 */
		public function decr($key, $column, $decrby = null) {} 
	
		/**
		 * rewind
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function rewind() {} 
	
		/**
		 * next
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function next() {} 
	
		/**
		 * current
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function current() {} 
	
		/**
		 * key
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function key() {} 
	
		/**
		 * valid
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function valid() {} 
	
	}
	
	
	/**
	 * swoole_lock
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_lock
	{
	
		/**
		 * __construct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $type [optional]
		 * @param $filename [optional]
		 * @return
		 */
		public function __construct($type = null, $filename = null) {} 
	
		/**
		 * __destruct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function __destruct() {} 
	
		/**
		 * lock
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function lock() {} 
	
		/**
		 * trylock
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function trylock() {} 
	
		/**
		 * lock_read
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function lock_read() {} 
	
		/**
		 * trylock_read
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function trylock_read() {} 
	
		/**
		 * unlock
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function unlock() {} 
	
	}
	
	
	/**
	 * swoole_lock
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_lock
	{
	
		/**
		 * __construct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $type [optional]
		 * @param $filename [optional]
		 * @return
		 */
		public function __construct($type = null, $filename = null) {} 
	
		/**
		 * __destruct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function __destruct() {} 
	
		/**
		 * lock
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function lock() {} 
	
		/**
		 * trylock
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function trylock() {} 
	
		/**
		 * lock_read
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function lock_read() {} 
	
		/**
		 * trylock_read
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function trylock_read() {} 
	
		/**
		 * unlock
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function unlock() {} 
	
	}
	
	
	/**
	 * swoole_atomic
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_atomic
	{
	
		/**
		 * __construct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $value [optional]
		 * @return
		 */
		public function __construct($value = null) {} 
	
		/**
		 * add
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $add_value [optional]
		 * @return
		 */
		public function add($add_value = null) {} 
	
		/**
		 * sub
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $sub_value [optional]
		 * @return
		 */
		public function sub($sub_value = null) {} 
	
		/**
		 * get
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function get() {} 
	
		/**
		 * set
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $value
		 * @return
		 */
		public function set($value) {} 
	
		/**
		 * cmpset
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $cmp_value
		 * @param $new_value
		 * @return
		 */
		public function cmpset($cmp_value, $new_value) {} 
	
	}
	
	
	/**
	 * swoole_atomic
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_atomic
	{
	
		/**
		 * __construct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $value [optional]
		 * @return
		 */
		public function __construct($value = null) {} 
	
		/**
		 * add
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $add_value [optional]
		 * @return
		 */
		public function add($add_value = null) {} 
	
		/**
		 * sub
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $sub_value [optional]
		 * @return
		 */
		public function sub($sub_value = null) {} 
	
		/**
		 * get
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function get() {} 
	
		/**
		 * set
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $value
		 * @return
		 */
		public function set($value) {} 
	
		/**
		 * cmpset
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $cmp_value
		 * @param $new_value
		 * @return
		 */
		public function cmpset($cmp_value, $new_value) {} 
	
	}
	
	
	/**
	 * swoole_http_server
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_http_server extends \swoole_server
	{
	
		private $global = 0;
	
		/**
		 * on
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $event_name
		 * @param $callback
		 * @return
		 */
		public function on($event_name, $callback) {} 
	
		/**
		 * start
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function start() {} 
	
		/**
		 * __construct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $host
		 * @param $port [optional]
		 * @param $mode [optional]
		 * @param $sock_type [optional]
		 * @return
		 */
		public function __construct($host, $port = null, $mode = null, $sock_type = null) {} 
	
		/**
		 * listen
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $host
		 * @param $port
		 * @param $sock_type
		 * @return
		 */
		public function listen($host, $port, $sock_type) {} 
	
		/**
		 * addlistener
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $host
		 * @param $port
		 * @param $sock_type
		 * @return
		 */
		public function addlistener($host, $port, $sock_type) {} 
	
		/**
		 * set
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param array $settings
		 * @return
		 */
		public function set(array $settings) {} 
	
		/**
		 * send
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $send_data
		 * @param $reactor_id [optional]
		 * @return
		 */
		public function send($fd, $send_data, $reactor_id = null) {} 
	
		/**
		 * sendto
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $ip
		 * @param $port
		 * @param $send_data
		 * @param $server_socket [optional]
		 * @return
		 */
		public function sendto($ip, $port, $send_data, $server_socket = null) {} 
	
		/**
		 * sendwait
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $conn_fd
		 * @param $send_data
		 * @return
		 */
		public function sendwait($conn_fd, $send_data) {} 
	
		/**
		 * exist
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @return
		 */
		public function exist($fd) {} 
	
		/**
		 * protect
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $is_protected [optional]
		 * @return
		 */
		public function protect($fd, $is_protected = null) {} 
	
		/**
		 * sendfile
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $conn_fd
		 * @param $filename
		 * @param $offset [optional]
		 * @return
		 */
		public function sendfile($conn_fd, $filename, $offset = null) {} 
	
		/**
		 * close
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $reset [optional]
		 * @return
		 */
		public function close($fd, $reset = null) {} 
	
		/**
		 * confirm
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @return
		 */
		public function confirm($fd) {} 
	
		/**
		 * pause
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @return
		 */
		public function pause($fd) {} 
	
		/**
		 * resume
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @return
		 */
		public function resume($fd) {} 
	
		/**
		 * task
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $data
		 * @param $worker_id [optional]
		 * @param $finish_callback [optional]
		 * @return
		 */
		public function task($data, $worker_id = null, $finish_callback = null) {} 
	
		/**
		 * taskwait
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $data
		 * @param $timeout [optional]
		 * @param $worker_id [optional]
		 * @return
		 */
		public function taskwait($data, $timeout = null, $worker_id = null) {} 
	
		/**
		 * taskWaitMulti
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param array $tasks
		 * @param $timeout [optional]
		 * @return
		 */
		public function taskWaitMulti(array $tasks, $timeout = null) {} 
	
		/**
		 * finish
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $data
		 * @return
		 */
		public function finish($data) {} 
	
		/**
		 * reload
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function reload() {} 
	
		/**
		 * shutdown
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function shutdown() {} 
	
		/**
		 * stop
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $worker_id [optional]
		 * @return
		 */
		public function stop($worker_id = null) {} 
	
		/**
		 * getLastError
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function getLastError() {} 
	
		/**
		 * heartbeat
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $reactor_id
		 * @return
		 */
		public function heartbeat($reactor_id) {} 
	
		/**
		 * connection_info
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $reactor_id [optional]
		 * @return
		 */
		public function connection_info($fd, $reactor_id = null) {} 
	
		/**
		 * connection_list
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $start_fd
		 * @param $find_count [optional]
		 * @return
		 */
		public function connection_list($start_fd, $find_count = null) {} 
	
		/**
		 * getClientInfo
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $reactor_id [optional]
		 * @return
		 */
		public function getClientInfo($fd, $reactor_id = null) {} 
	
		/**
		 * getClientList
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $start_fd
		 * @param $find_count [optional]
		 * @return
		 */
		public function getClientList($start_fd, $find_count = null) {} 
	
		/**
		 * after
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $ms
		 * @param $callback
		 * @param $param [optional]
		 * @return
		 */
		public function after($ms, $callback, $param = null) {} 
	
		/**
		 * tick
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $ms
		 * @param $callback
		 * @return
		 */
		public function tick($ms, $callback) {} 
	
		/**
		 * clearTimer
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $timer_id
		 * @return
		 */
		public function clearTimer($timer_id) {} 
	
		/**
		 * defer
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $callback
		 * @return
		 */
		public function defer($callback) {} 
	
		/**
		 * sendMessage
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $dst_worker_id
		 * @param $data
		 * @return
		 */
		public function sendMessage($dst_worker_id, $data) {} 
	
		/**
		 * addProcess
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param swoole_process $process
		 * @return
		 */
		public function addProcess(swoole_process $process) {} 
	
		/**
		 * stats
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function stats() {} 
	
		/**
		 * bind
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $uid
		 * @return
		 */
		public function bind($fd, $uid) {} 
	
	}
	
	
	/**
	 * swoole_http_server
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_http_server extends \swoole_server
	{
	
		private $global = 0;
	
		/**
		 * on
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $event_name
		 * @param $callback
		 * @return
		 */
		public function on($event_name, $callback) {} 
	
		/**
		 * start
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function start() {} 
	
		/**
		 * __construct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $host
		 * @param $port [optional]
		 * @param $mode [optional]
		 * @param $sock_type [optional]
		 * @return
		 */
		public function __construct($host, $port = null, $mode = null, $sock_type = null) {} 
	
		/**
		 * listen
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $host
		 * @param $port
		 * @param $sock_type
		 * @return
		 */
		public function listen($host, $port, $sock_type) {} 
	
		/**
		 * addlistener
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $host
		 * @param $port
		 * @param $sock_type
		 * @return
		 */
		public function addlistener($host, $port, $sock_type) {} 
	
		/**
		 * set
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param array $settings
		 * @return
		 */
		public function set(array $settings) {} 
	
		/**
		 * send
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $send_data
		 * @param $reactor_id [optional]
		 * @return
		 */
		public function send($fd, $send_data, $reactor_id = null) {} 
	
		/**
		 * sendto
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $ip
		 * @param $port
		 * @param $send_data
		 * @param $server_socket [optional]
		 * @return
		 */
		public function sendto($ip, $port, $send_data, $server_socket = null) {} 
	
		/**
		 * sendwait
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $conn_fd
		 * @param $send_data
		 * @return
		 */
		public function sendwait($conn_fd, $send_data) {} 
	
		/**
		 * exist
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @return
		 */
		public function exist($fd) {} 
	
		/**
		 * protect
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $is_protected [optional]
		 * @return
		 */
		public function protect($fd, $is_protected = null) {} 
	
		/**
		 * sendfile
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $conn_fd
		 * @param $filename
		 * @param $offset [optional]
		 * @return
		 */
		public function sendfile($conn_fd, $filename, $offset = null) {} 
	
		/**
		 * close
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $reset [optional]
		 * @return
		 */
		public function close($fd, $reset = null) {} 
	
		/**
		 * confirm
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @return
		 */
		public function confirm($fd) {} 
	
		/**
		 * pause
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @return
		 */
		public function pause($fd) {} 
	
		/**
		 * resume
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @return
		 */
		public function resume($fd) {} 
	
		/**
		 * task
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $data
		 * @param $worker_id [optional]
		 * @param $finish_callback [optional]
		 * @return
		 */
		public function task($data, $worker_id = null, $finish_callback = null) {} 
	
		/**
		 * taskwait
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $data
		 * @param $timeout [optional]
		 * @param $worker_id [optional]
		 * @return
		 */
		public function taskwait($data, $timeout = null, $worker_id = null) {} 
	
		/**
		 * taskWaitMulti
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param array $tasks
		 * @param $timeout [optional]
		 * @return
		 */
		public function taskWaitMulti(array $tasks, $timeout = null) {} 
	
		/**
		 * finish
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $data
		 * @return
		 */
		public function finish($data) {} 
	
		/**
		 * reload
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function reload() {} 
	
		/**
		 * shutdown
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function shutdown() {} 
	
		/**
		 * stop
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $worker_id [optional]
		 * @return
		 */
		public function stop($worker_id = null) {} 
	
		/**
		 * getLastError
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function getLastError() {} 
	
		/**
		 * heartbeat
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $reactor_id
		 * @return
		 */
		public function heartbeat($reactor_id) {} 
	
		/**
		 * connection_info
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $reactor_id [optional]
		 * @return
		 */
		public function connection_info($fd, $reactor_id = null) {} 
	
		/**
		 * connection_list
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $start_fd
		 * @param $find_count [optional]
		 * @return
		 */
		public function connection_list($start_fd, $find_count = null) {} 
	
		/**
		 * getClientInfo
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $reactor_id [optional]
		 * @return
		 */
		public function getClientInfo($fd, $reactor_id = null) {} 
	
		/**
		 * getClientList
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $start_fd
		 * @param $find_count [optional]
		 * @return
		 */
		public function getClientList($start_fd, $find_count = null) {} 
	
		/**
		 * after
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $ms
		 * @param $callback
		 * @param $param [optional]
		 * @return
		 */
		public function after($ms, $callback, $param = null) {} 
	
		/**
		 * tick
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $ms
		 * @param $callback
		 * @return
		 */
		public function tick($ms, $callback) {} 
	
		/**
		 * clearTimer
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $timer_id
		 * @return
		 */
		public function clearTimer($timer_id) {} 
	
		/**
		 * defer
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $callback
		 * @return
		 */
		public function defer($callback) {} 
	
		/**
		 * sendMessage
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $dst_worker_id
		 * @param $data
		 * @return
		 */
		public function sendMessage($dst_worker_id, $data) {} 
	
		/**
		 * addProcess
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param swoole_process $process
		 * @return
		 */
		public function addProcess(swoole_process $process) {} 
	
		/**
		 * stats
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function stats() {} 
	
		/**
		 * bind
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $uid
		 * @return
		 */
		public function bind($fd, $uid) {} 
	
	}
	
	
	/**
	 * swoole_http_response
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_http_response
	{
	
		/**
		 * initHeader
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function initHeader() {} 
	
		/**
		 * cookie
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $name
		 * @param $value [optional]
		 * @param $expires [optional]
		 * @param $path [optional]
		 * @param $domain [optional]
		 * @param $secure [optional]
		 * @param $httponly [optional]
		 * @return
		 */
		public function cookie($name, $value = null, $expires = null, $path = null, $domain = null, $secure = null, $httponly = null) {} 
	
		/**
		 * rawcookie
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $name
		 * @param $value [optional]
		 * @param $expires [optional]
		 * @param $path [optional]
		 * @param $domain [optional]
		 * @param $secure [optional]
		 * @param $httponly [optional]
		 * @return
		 */
		public function rawcookie($name, $value = null, $expires = null, $path = null, $domain = null, $secure = null, $httponly = null) {} 
	
		/**
		 * status
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $http_code
		 * @return
		 */
		public function status($http_code) {} 
	
		/**
		 * gzip
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $compress_level [optional]
		 * @return
		 */
		public function gzip($compress_level = null) {} 
	
		/**
		 * header
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $key
		 * @param $value
		 * @param $ucwords [optional]
		 * @return
		 */
		public function header($key, $value, $ucwords = null) {} 
	
		/**
		 * write
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $content
		 * @return
		 */
		public function write($content) {} 
	
		/**
		 * end
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $content [optional]
		 * @return
		 */
		public function end($content = null) {} 
	
		/**
		 * sendfile
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $filename
		 * @param $offset [optional]
		 * @return
		 */
		public function sendfile($filename, $offset = null) {} 
	
		/**
		 * __destruct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function __destruct() {} 
	
	}
	
	
	/**
	 * swoole_http_response
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_http_response
	{
	
		/**
		 * initHeader
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function initHeader() {} 
	
		/**
		 * cookie
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $name
		 * @param $value [optional]
		 * @param $expires [optional]
		 * @param $path [optional]
		 * @param $domain [optional]
		 * @param $secure [optional]
		 * @param $httponly [optional]
		 * @return
		 */
		public function cookie($name, $value = null, $expires = null, $path = null, $domain = null, $secure = null, $httponly = null) {} 
	
		/**
		 * rawcookie
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $name
		 * @param $value [optional]
		 * @param $expires [optional]
		 * @param $path [optional]
		 * @param $domain [optional]
		 * @param $secure [optional]
		 * @param $httponly [optional]
		 * @return
		 */
		public function rawcookie($name, $value = null, $expires = null, $path = null, $domain = null, $secure = null, $httponly = null) {} 
	
		/**
		 * status
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $http_code
		 * @return
		 */
		public function status($http_code) {} 
	
		/**
		 * gzip
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $compress_level [optional]
		 * @return
		 */
		public function gzip($compress_level = null) {} 
	
		/**
		 * header
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $key
		 * @param $value
		 * @param $ucwords [optional]
		 * @return
		 */
		public function header($key, $value, $ucwords = null) {} 
	
		/**
		 * write
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $content
		 * @return
		 */
		public function write($content) {} 
	
		/**
		 * end
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $content [optional]
		 * @return
		 */
		public function end($content = null) {} 
	
		/**
		 * sendfile
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $filename
		 * @param $offset [optional]
		 * @return
		 */
		public function sendfile($filename, $offset = null) {} 
	
		/**
		 * __destruct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function __destruct() {} 
	
	}
	
	
	/**
	 * swoole_http_request
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_http_request
	{
	
		/**
		 * rawcontent
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function rawcontent() {} 
	
		/**
		 * __destruct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function __destruct() {} 
	
	}
	
	
	/**
	 * swoole_http_request
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_http_request
	{
	
		/**
		 * rawcontent
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function rawcontent() {} 
	
		/**
		 * __destruct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function __destruct() {} 
	
	}
	
	
	/**
	 * swoole_buffer
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_buffer
	{
	
		/**
		 * __construct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $size [optional]
		 * @return
		 */
		public function __construct($size = null) {} 
	
		/**
		 * __destruct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function __destruct() {} 
	
		/**
		 * __toString
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function __toString() {} 
	
		/**
		 * substr
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $offset
		 * @param $length [optional]
		 * @param $seek [optional]
		 * @return
		 */
		public function substr($offset, $length = null, $seek = null) {} 
	
		/**
		 * write
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $offset
		 * @param $data
		 * @return
		 */
		public function write($offset, $data) {} 
	
		/**
		 * read
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $offset
		 * @param $length
		 * @return
		 */
		public function read($offset, $length) {} 
	
		/**
		 * append
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $data
		 * @return
		 */
		public function append($data) {} 
	
		/**
		 * expand
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $size
		 * @return
		 */
		public function expand($size) {} 
	
		/**
		 * recycle
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function recycle() {} 
	
		/**
		 * clear
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function clear() {} 
	
	}
	
	
	/**
	 * swoole_buffer
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_buffer
	{
	
		/**
		 * __construct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $size [optional]
		 * @return
		 */
		public function __construct($size = null) {} 
	
		/**
		 * __destruct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function __destruct() {} 
	
		/**
		 * __toString
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function __toString() {} 
	
		/**
		 * substr
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $offset
		 * @param $length [optional]
		 * @param $seek [optional]
		 * @return
		 */
		public function substr($offset, $length = null, $seek = null) {} 
	
		/**
		 * write
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $offset
		 * @param $data
		 * @return
		 */
		public function write($offset, $data) {} 
	
		/**
		 * read
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $offset
		 * @param $length
		 * @return
		 */
		public function read($offset, $length) {} 
	
		/**
		 * append
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $data
		 * @return
		 */
		public function append($data) {} 
	
		/**
		 * expand
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $size
		 * @return
		 */
		public function expand($size) {} 
	
		/**
		 * recycle
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function recycle() {} 
	
		/**
		 * clear
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function clear() {} 
	
	}
	
	
	/**
	 * swoole_websocket_server
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_websocket_server extends \swoole_http_server
	{
	
		/**
		 * on
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $event_name
		 * @param $callback
		 * @return
		 */
		public function on($event_name, $callback) {} 
	
		/**
		 * push
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $data
		 * @param $opcode [optional]
		 * @param $finish [optional]
		 * @return
		 */
		public function push($fd, $data, $opcode = null, $finish = null) {} 
	
		/**
		 * exist
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @return
		 */
		public function exist($fd) {} 
	
		/**
		 * pack
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $data
		 * @param $opcode [optional]
		 * @param $finish [optional]
		 * @param $mask [optional]
		 * @return
		 */
		public static function pack($data, $opcode = null, $finish = null, $mask = null) {} 
	
		/**
		 * unpack
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $data
		 * @return
		 */
		public static function unpack($data) {} 
	
		/**
		 * start
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function start() {} 
	
		/**
		 * __construct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $host
		 * @param $port [optional]
		 * @param $mode [optional]
		 * @param $sock_type [optional]
		 * @return
		 */
		public function __construct($host, $port = null, $mode = null, $sock_type = null) {} 
	
		/**
		 * listen
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $host
		 * @param $port
		 * @param $sock_type
		 * @return
		 */
		public function listen($host, $port, $sock_type) {} 
	
		/**
		 * addlistener
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $host
		 * @param $port
		 * @param $sock_type
		 * @return
		 */
		public function addlistener($host, $port, $sock_type) {} 
	
		/**
		 * set
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param array $settings
		 * @return
		 */
		public function set(array $settings) {} 
	
		/**
		 * send
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $send_data
		 * @param $reactor_id [optional]
		 * @return
		 */
		public function send($fd, $send_data, $reactor_id = null) {} 
	
		/**
		 * sendto
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $ip
		 * @param $port
		 * @param $send_data
		 * @param $server_socket [optional]
		 * @return
		 */
		public function sendto($ip, $port, $send_data, $server_socket = null) {} 
	
		/**
		 * sendwait
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $conn_fd
		 * @param $send_data
		 * @return
		 */
		public function sendwait($conn_fd, $send_data) {} 
	
		/**
		 * protect
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $is_protected [optional]
		 * @return
		 */
		public function protect($fd, $is_protected = null) {} 
	
		/**
		 * sendfile
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $conn_fd
		 * @param $filename
		 * @param $offset [optional]
		 * @return
		 */
		public function sendfile($conn_fd, $filename, $offset = null) {} 
	
		/**
		 * close
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $reset [optional]
		 * @return
		 */
		public function close($fd, $reset = null) {} 
	
		/**
		 * confirm
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @return
		 */
		public function confirm($fd) {} 
	
		/**
		 * pause
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @return
		 */
		public function pause($fd) {} 
	
		/**
		 * resume
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @return
		 */
		public function resume($fd) {} 
	
		/**
		 * task
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $data
		 * @param $worker_id [optional]
		 * @param $finish_callback [optional]
		 * @return
		 */
		public function task($data, $worker_id = null, $finish_callback = null) {} 
	
		/**
		 * taskwait
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $data
		 * @param $timeout [optional]
		 * @param $worker_id [optional]
		 * @return
		 */
		public function taskwait($data, $timeout = null, $worker_id = null) {} 
	
		/**
		 * taskWaitMulti
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param array $tasks
		 * @param $timeout [optional]
		 * @return
		 */
		public function taskWaitMulti(array $tasks, $timeout = null) {} 
	
		/**
		 * finish
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $data
		 * @return
		 */
		public function finish($data) {} 
	
		/**
		 * reload
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function reload() {} 
	
		/**
		 * shutdown
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function shutdown() {} 
	
		/**
		 * stop
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $worker_id [optional]
		 * @return
		 */
		public function stop($worker_id = null) {} 
	
		/**
		 * getLastError
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function getLastError() {} 
	
		/**
		 * heartbeat
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $reactor_id
		 * @return
		 */
		public function heartbeat($reactor_id) {} 
	
		/**
		 * connection_info
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $reactor_id [optional]
		 * @return
		 */
		public function connection_info($fd, $reactor_id = null) {} 
	
		/**
		 * connection_list
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $start_fd
		 * @param $find_count [optional]
		 * @return
		 */
		public function connection_list($start_fd, $find_count = null) {} 
	
		/**
		 * getClientInfo
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $reactor_id [optional]
		 * @return
		 */
		public function getClientInfo($fd, $reactor_id = null) {} 
	
		/**
		 * getClientList
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $start_fd
		 * @param $find_count [optional]
		 * @return
		 */
		public function getClientList($start_fd, $find_count = null) {} 
	
		/**
		 * after
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $ms
		 * @param $callback
		 * @param $param [optional]
		 * @return
		 */
		public function after($ms, $callback, $param = null) {} 
	
		/**
		 * tick
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $ms
		 * @param $callback
		 * @return
		 */
		public function tick($ms, $callback) {} 
	
		/**
		 * clearTimer
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $timer_id
		 * @return
		 */
		public function clearTimer($timer_id) {} 
	
		/**
		 * defer
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $callback
		 * @return
		 */
		public function defer($callback) {} 
	
		/**
		 * sendMessage
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $dst_worker_id
		 * @param $data
		 * @return
		 */
		public function sendMessage($dst_worker_id, $data) {} 
	
		/**
		 * addProcess
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param swoole_process $process
		 * @return
		 */
		public function addProcess(swoole_process $process) {} 
	
		/**
		 * stats
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function stats() {} 
	
		/**
		 * bind
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $uid
		 * @return
		 */
		public function bind($fd, $uid) {} 
	
	}
	
	
	/**
	 * swoole_websocket_server
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_websocket_server extends \swoole_http_server
	{
	
		/**
		 * on
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $event_name
		 * @param $callback
		 * @return
		 */
		public function on($event_name, $callback) {} 
	
		/**
		 * push
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $data
		 * @param $opcode [optional]
		 * @param $finish [optional]
		 * @return
		 */
		public function push($fd, $data, $opcode = null, $finish = null) {} 
	
		/**
		 * exist
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @return
		 */
		public function exist($fd) {} 
	
		/**
		 * pack
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $data
		 * @param $opcode [optional]
		 * @param $finish [optional]
		 * @param $mask [optional]
		 * @return
		 */
		public static function pack($data, $opcode = null, $finish = null, $mask = null) {} 
	
		/**
		 * unpack
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $data
		 * @return
		 */
		public static function unpack($data) {} 
	
		/**
		 * start
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function start() {} 
	
		/**
		 * __construct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $host
		 * @param $port [optional]
		 * @param $mode [optional]
		 * @param $sock_type [optional]
		 * @return
		 */
		public function __construct($host, $port = null, $mode = null, $sock_type = null) {} 
	
		/**
		 * listen
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $host
		 * @param $port
		 * @param $sock_type
		 * @return
		 */
		public function listen($host, $port, $sock_type) {} 
	
		/**
		 * addlistener
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $host
		 * @param $port
		 * @param $sock_type
		 * @return
		 */
		public function addlistener($host, $port, $sock_type) {} 
	
		/**
		 * set
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param array $settings
		 * @return
		 */
		public function set(array $settings) {} 
	
		/**
		 * send
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $send_data
		 * @param $reactor_id [optional]
		 * @return
		 */
		public function send($fd, $send_data, $reactor_id = null) {} 
	
		/**
		 * sendto
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $ip
		 * @param $port
		 * @param $send_data
		 * @param $server_socket [optional]
		 * @return
		 */
		public function sendto($ip, $port, $send_data, $server_socket = null) {} 
	
		/**
		 * sendwait
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $conn_fd
		 * @param $send_data
		 * @return
		 */
		public function sendwait($conn_fd, $send_data) {} 
	
		/**
		 * protect
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $is_protected [optional]
		 * @return
		 */
		public function protect($fd, $is_protected = null) {} 
	
		/**
		 * sendfile
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $conn_fd
		 * @param $filename
		 * @param $offset [optional]
		 * @return
		 */
		public function sendfile($conn_fd, $filename, $offset = null) {} 
	
		/**
		 * close
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $reset [optional]
		 * @return
		 */
		public function close($fd, $reset = null) {} 
	
		/**
		 * confirm
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @return
		 */
		public function confirm($fd) {} 
	
		/**
		 * pause
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @return
		 */
		public function pause($fd) {} 
	
		/**
		 * resume
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @return
		 */
		public function resume($fd) {} 
	
		/**
		 * task
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $data
		 * @param $worker_id [optional]
		 * @param $finish_callback [optional]
		 * @return
		 */
		public function task($data, $worker_id = null, $finish_callback = null) {} 
	
		/**
		 * taskwait
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $data
		 * @param $timeout [optional]
		 * @param $worker_id [optional]
		 * @return
		 */
		public function taskwait($data, $timeout = null, $worker_id = null) {} 
	
		/**
		 * taskWaitMulti
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param array $tasks
		 * @param $timeout [optional]
		 * @return
		 */
		public function taskWaitMulti(array $tasks, $timeout = null) {} 
	
		/**
		 * finish
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $data
		 * @return
		 */
		public function finish($data) {} 
	
		/**
		 * reload
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function reload() {} 
	
		/**
		 * shutdown
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function shutdown() {} 
	
		/**
		 * stop
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $worker_id [optional]
		 * @return
		 */
		public function stop($worker_id = null) {} 
	
		/**
		 * getLastError
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function getLastError() {} 
	
		/**
		 * heartbeat
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $reactor_id
		 * @return
		 */
		public function heartbeat($reactor_id) {} 
	
		/**
		 * connection_info
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $reactor_id [optional]
		 * @return
		 */
		public function connection_info($fd, $reactor_id = null) {} 
	
		/**
		 * connection_list
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $start_fd
		 * @param $find_count [optional]
		 * @return
		 */
		public function connection_list($start_fd, $find_count = null) {} 
	
		/**
		 * getClientInfo
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $reactor_id [optional]
		 * @return
		 */
		public function getClientInfo($fd, $reactor_id = null) {} 
	
		/**
		 * getClientList
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $start_fd
		 * @param $find_count [optional]
		 * @return
		 */
		public function getClientList($start_fd, $find_count = null) {} 
	
		/**
		 * after
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $ms
		 * @param $callback
		 * @param $param [optional]
		 * @return
		 */
		public function after($ms, $callback, $param = null) {} 
	
		/**
		 * tick
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $ms
		 * @param $callback
		 * @return
		 */
		public function tick($ms, $callback) {} 
	
		/**
		 * clearTimer
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $timer_id
		 * @return
		 */
		public function clearTimer($timer_id) {} 
	
		/**
		 * defer
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $callback
		 * @return
		 */
		public function defer($callback) {} 
	
		/**
		 * sendMessage
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $dst_worker_id
		 * @param $data
		 * @return
		 */
		public function sendMessage($dst_worker_id, $data) {} 
	
		/**
		 * addProcess
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param swoole_process $process
		 * @return
		 */
		public function addProcess(swoole_process $process) {} 
	
		/**
		 * stats
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function stats() {} 
	
		/**
		 * bind
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $uid
		 * @return
		 */
		public function bind($fd, $uid) {} 
	
	}
	
	
	/**
	 * swoole_websocket_frame
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_websocket_frame
	{
	
	}
	
	
	/**
	 * swoole_websocket_frame
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_websocket_frame
	{
	
	}
	
	
	/**
	 * swoole_mysql
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_mysql
	{
	
		/**
		 * __construct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function __construct() {} 
	
		/**
		 * __destruct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function __destruct() {} 
	
		/**
		 * connect
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param array $server_config
		 * @param $callback
		 * @return
		 */
		public function connect(array $server_config, $callback) {} 
	
		/**
		 * query
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $sql
		 * @param $callback
		 * @return
		 */
		public function query($sql, $callback) {} 
	
		/**
		 * close
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function close() {} 
	
		/**
		 * on
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $event_name
		 * @param $callback
		 * @return
		 */
		public function on($event_name, $callback) {} 
	
	}
	
	
	/**
	 * swoole_mysql
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_mysql
	{
	
		/**
		 * __construct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function __construct() {} 
	
		/**
		 * __destruct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function __destruct() {} 
	
		/**
		 * connect
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param array $server_config
		 * @param $callback
		 * @return
		 */
		public function connect(array $server_config, $callback) {} 
	
		/**
		 * query
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $sql
		 * @param $callback
		 * @return
		 */
		public function query($sql, $callback) {} 
	
		/**
		 * close
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function close() {} 
	
		/**
		 * on
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $event_name
		 * @param $callback
		 * @return
		 */
		public function on($event_name, $callback) {} 
	
	}
	
	
	/**
	 * swoole_mysql_exception
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_mysql_exception extends \Exception
	{
	
		protected $message = "";
		protected $code = 0;
		protected $file;
		protected $line;
	
		/**
		 * __clone
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		final private function __clone() {} 
	
		/**
		 * __construct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $message [optional]
		 * @param $code [optional]
		 * @param $previous [optional]
		 * @return
		 */
		public function __construct($message = null, $code = null, $previous = null) {} 
	
		/**
		 * __wakeup
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function __wakeup() {} 
	
		/**
		 * getMessage
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		final public function getMessage() {} 
	
		/**
		 * getCode
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		final public function getCode() {} 
	
		/**
		 * getFile
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		final public function getFile() {} 
	
		/**
		 * getLine
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		final public function getLine() {} 
	
		/**
		 * getTrace
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		final public function getTrace() {} 
	
		/**
		 * getPrevious
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		final public function getPrevious() {} 
	
		/**
		 * getTraceAsString
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		final public function getTraceAsString() {} 
	
		/**
		 * __toString
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function __toString() {} 
	
	}
	
	
	/**
	 * swoole_mysql_exception
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_mysql_exception extends \Exception
	{
	
		protected $message = "";
		protected $code = 0;
		protected $file;
		protected $line;
	
		/**
		 * __clone
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		final private function __clone() {} 
	
		/**
		 * __construct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $message [optional]
		 * @param $code [optional]
		 * @param $previous [optional]
		 * @return
		 */
		public function __construct($message = null, $code = null, $previous = null) {} 
	
		/**
		 * __wakeup
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function __wakeup() {} 
	
		/**
		 * getMessage
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		final public function getMessage() {} 
	
		/**
		 * getCode
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		final public function getCode() {} 
	
		/**
		 * getFile
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		final public function getFile() {} 
	
		/**
		 * getLine
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		final public function getLine() {} 
	
		/**
		 * getTrace
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		final public function getTrace() {} 
	
		/**
		 * getPrevious
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		final public function getPrevious() {} 
	
		/**
		 * getTraceAsString
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		final public function getTraceAsString() {} 
	
		/**
		 * __toString
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function __toString() {} 
	
	}
	
	
	/**
	 * swoole_module
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_module
	{
	
	}
	
	
	/**
	 * swoole_module
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_module
	{
	
	}
	
	
	/**
	 * swoole_mmap
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_mmap
	{
	
		/**
		 * open
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $filename
		 * @param $size [optional]
		 * @param $offset [optional]
		 * @return
		 */
		public static function open($filename, $size = null, $offset = null) {} 
	
	}
	
	
	/**
	 * swoole_mmap
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_mmap
	{
	
		/**
		 * open
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $filename
		 * @param $size [optional]
		 * @param $offset [optional]
		 * @return
		 */
		public static function open($filename, $size = null, $offset = null) {} 
	
	}
	
	
	/**
	 * swoole_channel
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_channel
	{
	
		/**
		 * __construct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $size
		 * @return
		 */
		public function __construct($size) {} 
	
		/**
		 * __destruct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function __destruct() {} 
	
		/**
		 * push
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $data
		 * @return
		 */
		public function push($data) {} 
	
		/**
		 * pop
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function pop() {} 
	
		/**
		 * stats
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function stats() {} 
	
	}
	
	
	/**
	 * swoole_channel
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_channel
	{
	
		/**
		 * __construct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $size
		 * @return
		 */
		public function __construct($size) {} 
	
		/**
		 * __destruct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function __destruct() {} 
	
		/**
		 * push
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $data
		 * @return
		 */
		public function push($data) {} 
	
		/**
		 * pop
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function pop() {} 
	
		/**
		 * stats
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function stats() {} 
	
	}
	
	
	/**
	 * swoole_redis_server
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_redis_server extends \swoole_server
	{
	
		const NIL = 1;
		const ERROR = 0;
		const STATUS = 2;
		const INT = 3;
		const STRING = 4;
		const SET = 5;
		const MAP = 6;
	
		/**
		 * start
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function start() {} 
	
		/**
		 * setHandler
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $command
		 * @param $callback
		 * @param $number_of_string_param [optional]
		 * @param $type_of_array_param [optional]
		 * @return
		 */
		public function setHandler($command, $callback, $number_of_string_param = null, $type_of_array_param = null) {} 
	
		/**
		 * format
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $type
		 * @param $value [optional]
		 * @return
		 */
		public static function format($type, $value = null) {} 
	
		/**
		 * __construct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $host
		 * @param $port [optional]
		 * @param $mode [optional]
		 * @param $sock_type [optional]
		 * @return
		 */
		public function __construct($host, $port = null, $mode = null, $sock_type = null) {} 
	
		/**
		 * listen
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $host
		 * @param $port
		 * @param $sock_type
		 * @return
		 */
		public function listen($host, $port, $sock_type) {} 
	
		/**
		 * addlistener
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $host
		 * @param $port
		 * @param $sock_type
		 * @return
		 */
		public function addlistener($host, $port, $sock_type) {} 
	
		/**
		 * on
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $event_name
		 * @param $callback
		 * @return
		 */
		public function on($event_name, $callback) {} 
	
		/**
		 * set
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param array $settings
		 * @return
		 */
		public function set(array $settings) {} 
	
		/**
		 * send
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $send_data
		 * @param $reactor_id [optional]
		 * @return
		 */
		public function send($fd, $send_data, $reactor_id = null) {} 
	
		/**
		 * sendto
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $ip
		 * @param $port
		 * @param $send_data
		 * @param $server_socket [optional]
		 * @return
		 */
		public function sendto($ip, $port, $send_data, $server_socket = null) {} 
	
		/**
		 * sendwait
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $conn_fd
		 * @param $send_data
		 * @return
		 */
		public function sendwait($conn_fd, $send_data) {} 
	
		/**
		 * exist
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @return
		 */
		public function exist($fd) {} 
	
		/**
		 * protect
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $is_protected [optional]
		 * @return
		 */
		public function protect($fd, $is_protected = null) {} 
	
		/**
		 * sendfile
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $conn_fd
		 * @param $filename
		 * @param $offset [optional]
		 * @return
		 */
		public function sendfile($conn_fd, $filename, $offset = null) {} 
	
		/**
		 * close
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $reset [optional]
		 * @return
		 */
		public function close($fd, $reset = null) {} 
	
		/**
		 * confirm
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @return
		 */
		public function confirm($fd) {} 
	
		/**
		 * pause
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @return
		 */
		public function pause($fd) {} 
	
		/**
		 * resume
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @return
		 */
		public function resume($fd) {} 
	
		/**
		 * task
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $data
		 * @param $worker_id [optional]
		 * @param $finish_callback [optional]
		 * @return
		 */
		public function task($data, $worker_id = null, $finish_callback = null) {} 
	
		/**
		 * taskwait
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $data
		 * @param $timeout [optional]
		 * @param $worker_id [optional]
		 * @return
		 */
		public function taskwait($data, $timeout = null, $worker_id = null) {} 
	
		/**
		 * taskWaitMulti
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param array $tasks
		 * @param $timeout [optional]
		 * @return
		 */
		public function taskWaitMulti(array $tasks, $timeout = null) {} 
	
		/**
		 * finish
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $data
		 * @return
		 */
		public function finish($data) {} 
	
		/**
		 * reload
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function reload() {} 
	
		/**
		 * shutdown
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function shutdown() {} 
	
		/**
		 * stop
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $worker_id [optional]
		 * @return
		 */
		public function stop($worker_id = null) {} 
	
		/**
		 * getLastError
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function getLastError() {} 
	
		/**
		 * heartbeat
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $reactor_id
		 * @return
		 */
		public function heartbeat($reactor_id) {} 
	
		/**
		 * connection_info
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $reactor_id [optional]
		 * @return
		 */
		public function connection_info($fd, $reactor_id = null) {} 
	
		/**
		 * connection_list
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $start_fd
		 * @param $find_count [optional]
		 * @return
		 */
		public function connection_list($start_fd, $find_count = null) {} 
	
		/**
		 * getClientInfo
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $reactor_id [optional]
		 * @return
		 */
		public function getClientInfo($fd, $reactor_id = null) {} 
	
		/**
		 * getClientList
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $start_fd
		 * @param $find_count [optional]
		 * @return
		 */
		public function getClientList($start_fd, $find_count = null) {} 
	
		/**
		 * after
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $ms
		 * @param $callback
		 * @param $param [optional]
		 * @return
		 */
		public function after($ms, $callback, $param = null) {} 
	
		/**
		 * tick
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $ms
		 * @param $callback
		 * @return
		 */
		public function tick($ms, $callback) {} 
	
		/**
		 * clearTimer
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $timer_id
		 * @return
		 */
		public function clearTimer($timer_id) {} 
	
		/**
		 * defer
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $callback
		 * @return
		 */
		public function defer($callback) {} 
	
		/**
		 * sendMessage
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $dst_worker_id
		 * @param $data
		 * @return
		 */
		public function sendMessage($dst_worker_id, $data) {} 
	
		/**
		 * addProcess
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param swoole_process $process
		 * @return
		 */
		public function addProcess(swoole_process $process) {} 
	
		/**
		 * stats
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function stats() {} 
	
		/**
		 * bind
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $uid
		 * @return
		 */
		public function bind($fd, $uid) {} 
	
	}
	
	
	/**
	 * swoole_redis_server
	 *  
	 * @since 1.9.7-alpha
	 * 
	 * @package 
	 */
	class swoole_redis_server extends \swoole_server
	{
	
		const NIL = 1;
		const ERROR = 0;
		const STATUS = 2;
		const INT = 3;
		const STRING = 4;
		const SET = 5;
		const MAP = 6;
	
		/**
		 * start
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function start() {} 
	
		/**
		 * setHandler
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $command
		 * @param $callback
		 * @param $number_of_string_param [optional]
		 * @param $type_of_array_param [optional]
		 * @return
		 */
		public function setHandler($command, $callback, $number_of_string_param = null, $type_of_array_param = null) {} 
	
		/**
		 * format
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $type
		 * @param $value [optional]
		 * @return
		 */
		public static function format($type, $value = null) {} 
	
		/**
		 * __construct
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $host
		 * @param $port [optional]
		 * @param $mode [optional]
		 * @param $sock_type [optional]
		 * @return
		 */
		public function __construct($host, $port = null, $mode = null, $sock_type = null) {} 
	
		/**
		 * listen
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $host
		 * @param $port
		 * @param $sock_type
		 * @return
		 */
		public function listen($host, $port, $sock_type) {} 
	
		/**
		 * addlistener
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $host
		 * @param $port
		 * @param $sock_type
		 * @return
		 */
		public function addlistener($host, $port, $sock_type) {} 
	
		/**
		 * on
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $event_name
		 * @param $callback
		 * @return
		 */
		public function on($event_name, $callback) {} 
	
		/**
		 * set
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param array $settings
		 * @return
		 */
		public function set(array $settings) {} 
	
		/**
		 * send
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $send_data
		 * @param $reactor_id [optional]
		 * @return
		 */
		public function send($fd, $send_data, $reactor_id = null) {} 
	
		/**
		 * sendto
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $ip
		 * @param $port
		 * @param $send_data
		 * @param $server_socket [optional]
		 * @return
		 */
		public function sendto($ip, $port, $send_data, $server_socket = null) {} 
	
		/**
		 * sendwait
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $conn_fd
		 * @param $send_data
		 * @return
		 */
		public function sendwait($conn_fd, $send_data) {} 
	
		/**
		 * exist
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @return
		 */
		public function exist($fd) {} 
	
		/**
		 * protect
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $is_protected [optional]
		 * @return
		 */
		public function protect($fd, $is_protected = null) {} 
	
		/**
		 * sendfile
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $conn_fd
		 * @param $filename
		 * @param $offset [optional]
		 * @return
		 */
		public function sendfile($conn_fd, $filename, $offset = null) {} 
	
		/**
		 * close
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $reset [optional]
		 * @return
		 */
		public function close($fd, $reset = null) {} 
	
		/**
		 * confirm
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @return
		 */
		public function confirm($fd) {} 
	
		/**
		 * pause
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @return
		 */
		public function pause($fd) {} 
	
		/**
		 * resume
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @return
		 */
		public function resume($fd) {} 
	
		/**
		 * task
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $data
		 * @param $worker_id [optional]
		 * @param $finish_callback [optional]
		 * @return
		 */
		public function task($data, $worker_id = null, $finish_callback = null) {} 
	
		/**
		 * taskwait
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $data
		 * @param $timeout [optional]
		 * @param $worker_id [optional]
		 * @return
		 */
		public function taskwait($data, $timeout = null, $worker_id = null) {} 
	
		/**
		 * taskWaitMulti
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param array $tasks
		 * @param $timeout [optional]
		 * @return
		 */
		public function taskWaitMulti(array $tasks, $timeout = null) {} 
	
		/**
		 * finish
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $data
		 * @return
		 */
		public function finish($data) {} 
	
		/**
		 * reload
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function reload() {} 
	
		/**
		 * shutdown
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function shutdown() {} 
	
		/**
		 * stop
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $worker_id [optional]
		 * @return
		 */
		public function stop($worker_id = null) {} 
	
		/**
		 * getLastError
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function getLastError() {} 
	
		/**
		 * heartbeat
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $reactor_id
		 * @return
		 */
		public function heartbeat($reactor_id) {} 
	
		/**
		 * connection_info
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $reactor_id [optional]
		 * @return
		 */
		public function connection_info($fd, $reactor_id = null) {} 
	
		/**
		 * connection_list
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $start_fd
		 * @param $find_count [optional]
		 * @return
		 */
		public function connection_list($start_fd, $find_count = null) {} 
	
		/**
		 * getClientInfo
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $reactor_id [optional]
		 * @return
		 */
		public function getClientInfo($fd, $reactor_id = null) {} 
	
		/**
		 * getClientList
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $start_fd
		 * @param $find_count [optional]
		 * @return
		 */
		public function getClientList($start_fd, $find_count = null) {} 
	
		/**
		 * after
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $ms
		 * @param $callback
		 * @param $param [optional]
		 * @return
		 */
		public function after($ms, $callback, $param = null) {} 
	
		/**
		 * tick
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $ms
		 * @param $callback
		 * @return
		 */
		public function tick($ms, $callback) {} 
	
		/**
		 * clearTimer
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $timer_id
		 * @return
		 */
		public function clearTimer($timer_id) {} 
	
		/**
		 * defer
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $callback
		 * @return
		 */
		public function defer($callback) {} 
	
		/**
		 * sendMessage
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $dst_worker_id
		 * @param $data
		 * @return
		 */
		public function sendMessage($dst_worker_id, $data) {} 
	
		/**
		 * addProcess
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param swoole_process $process
		 * @return
		 */
		public function addProcess(swoole_process $process) {} 
	
		/**
		 * stats
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @return
		 */
		public function stats() {} 
	
		/**
		 * bind
		 *  
		 * @since 1.9.7-alpha
		 * 
		 * @param $fd
		 * @param $uid
		 * @return
		 */
		public function bind($fd, $uid) {} 
	
	}
	
	
}
