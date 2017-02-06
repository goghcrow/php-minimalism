<?php

namespace Minimalism\Test;

use Minimalism\Config\Yaconf;

require __DIR__ . "/../../src/Config/Yaconf.php";


test1();
test2();
test3();

function test1()
{

    $ini = <<<'INI'
; comment
name="yaconf"
year=2015
features[]="fast"
features.1="light"
features.plus="zero-copy"
features[plus2]="zero-copy"
features.constant=PHP_VERSION ;常量
features.env=${HOME} ;环境变量
INI;

    $result = Yaconf::parse($ini);
    assert($result === array (
            'name' => 'yaconf',
            'year' => '2015',
            'features' =>
                array (
                    0 => 'fast',
                    'plus2' => 'zero-copy',
                    1 => 'light',
                    'plus' => 'zero-copy',
                    'constant' => '5.6.21',
                    'env' => '/Users/chuxiaofeng',
                ),
        ));

    Yaconf::$conf["foo"] = $result;

    assert(Yaconf::get("foo.name") === "yaconf");
    assert(Yaconf::get("foo.year") === "2015");
    assert(Yaconf::get("foo.features.1") === "light");
    assert(Yaconf::get("foo.features.plus") === "zero-copy");
    assert(Yaconf::has("foo.features.plus2") === true);
    assert(Yaconf::get("foo.features.undef") === null);
    assert(Yaconf::get("foo.features.undef.undef") === null);
    assert(Yaconf::has("foo.features.undef.undef") === false);
}

function test2()
{
    $ini = <<<'INI'
[base]
parent="yaconf"
children="NULL"

[children:base]
children="set"
INI;

    assert(Yaconf::parse($ini) === array (
            'base' =>
                array (
                    'parent' => 'yaconf',
                    'children' => 'NULL',
                ),
            'children' =>
                array (
                    'parent' => 'yaconf',
                    'children' => 'set',
                ),
        ));
}


function test3()
{
    $ini = <<<'INI'
[children:other]
children="set"
INI;
    try {
        Yaconf::parse($ini);
        assert(false);
    } catch (\Exception $ex) {}

    $ini = <<<'INI'
[base]

[children:base:1]
children="set"
INI;
    try {
        Yaconf::parse($ini);
        assert(false);
    } catch (\Exception $ex) {}
}