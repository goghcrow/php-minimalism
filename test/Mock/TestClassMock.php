<?php

namespace Minimalism\Test\Mock;


class TestClassMock
{
    // mock 返回结果
    public function say($something) {
        return __METHOD__ . "($something)";
    }

    // 将 private static 修改成 public 并mock
    public function staticMethod() {
        $args = implode(", ", func_get_args());
        return __METHOD__ . "($args)";
    }

    // 将 private 修改成public 并mock返回结果
    public function privateMethod() {
        $args = implode(", ", func_get_args());
        return __METHOD__ . "($args)";
    }
}