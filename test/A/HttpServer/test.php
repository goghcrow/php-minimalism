<?php
swoole_async_read(__FILE__, function($a, $b) { var_dump(func_get_args()); });