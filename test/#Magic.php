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
    $bak = $var;
    $var = uniqid(time()); // 给变量唯一值
    $name = array_search($var, $scope, true);
    $var = $bak;
    return $name;
}


$varName = null;
assert(get_variable_name($varName) === "varName");



function f()
{
    $localVar = null;
    $name = get_variable_name($localVar, get_defined_vars());
    assert($name === "localVar");
}
f();


// chrome DEBUGGER
xdebug_break();
