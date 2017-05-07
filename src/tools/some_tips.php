<?php

// debug php.ini
// report_memleaks=1

// time USE_ZEND_ALLOC=0 php ...

ini_set("memory_limit", -1);




// linux
// 物理内存占用
function heap() {
    echo `grep VmRSS /proc/self/status`;
}







function array_flat(array $array) {
    return iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveArrayIterator($array)), false);
}

$arr = [1,2, [3, [4, [5]]], 6];
//print_r(array_flat($arr));



ob_start("ob_gzhandler");

//ob_start('ob_tidyhandler');
//echo "<p>HELLO</p>";





// 检测索引
///*


mysqli_report(MYSQLI_REPORT_INDEX);
$mysqli = mysqli_connect("127.0.0.1", "root", "123456", "information_schema", 3306);
$mysql_result = mysqli_query($mysqli, "select * from COLUMNS limit 1");
var_dump($mysql_result->fetch_assoc());




mysqli_report(MYSQLI_REPORT_STRICT | MYSQLI_REPORT_ALL);
$mysqli = mysqli_connect("127.0.0.1", "root", "123456", "information_schema", 3306);

try {
    $mysql_result = mysqli_query($mysqli, "select * from COLUMNS limit 1");
    var_dump($mysql_result->fetch_assoc());
} catch (\mysqli_sql_exception $ex) {
    echo $ex;
}
//*/
