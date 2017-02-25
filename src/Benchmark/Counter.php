<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/25
 * Time: 下午9:07
 */

namespace Minimalism\Benchmark;


if (PHP_OS === "Darwin") {
    class Counter
    {
        private static $key = "line1";
        private static $column = "counter";

        private $table;

        public function __construct()
        {
            $this->table = new \swoole_table(1); // 只有一行的表格
            $this->table->column(self::$column, \swoole_table::TYPE_INT, 8);
            $this->table->create();
            $this->reset();
        }

        public function reset()
        {
            $this->table->lock();
            $this->table->set(self::$key, [
                "counter" => 0,
            ]);
            $this->table->unlock();
        }

        public function incr($by = 1)
        {
            $this->table->lock();
            $this->table->incr(self::$key, self::$column, $by);
            $this->table->unlock();
        }

        public function decr($by = 1)
        {
            $this->table->lock();
            $this->table->decr(self::$key, self::$column, $by);
            $this->table->unlock();
        }

        public function get()
        {
            return $this->table->get(self::$key)[self::$column];
        }
    }
} else {
    class Counter
    {
        private $key;

        public function __construct()
        {
            $this->key = "__counter__:" . microtime(true);
            $this->reset();
        }

        public function reset()
        {
            apcu_store($this->key, 0);
        }

        public function incr($by = 1)
        {
            return apcu_inc($this->key, $by);
        }

        public function decr($by = 1)
        {
            return apcu_dec($this->key, $by);
        }

        public function get()
        {
            return apcu_fetch($this->key);
        }

        public function __destruct()
        {
            apcu_delete($this->key);
        }
    }
}