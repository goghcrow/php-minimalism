<?php

ini_set("display_errors", true);
error_reporting(E_ALL);

if (function_exists("opcache_reset")) {
    opcache_reset();
}
