<?php

/**
 * get_variable_name
 * @access private
 * @param $var
 * @param array|NULL $scope
 * @return mixed
 * @author laruence
 * http://www.laruence.com/2010/12/08/1716.html
 */
function get_variable_name(&$var, array $scope = NULL) {
    $scope = $scope ?: $GLOBALS;
    $tmp = $var;
    $var = uniqid(time()); // 给变量唯一值
    $name = array_search($var, $scope, true);
    $var = $tmp;
    return $name;
}


$varName = null;
assert(get_variable_name($varName) === "varName");



// chrome DEBUGGER
xdebug_break();
