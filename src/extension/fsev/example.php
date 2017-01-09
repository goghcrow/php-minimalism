<?php

$fp = fsev_open();
while(true) {
	$data = fread($fp, 8192);
	$ret = fsev_decode($data);
	print_r($ret);
}
fclose($fp);