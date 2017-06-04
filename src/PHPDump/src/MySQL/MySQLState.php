<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/5/24
 * Time: 下午11:10
 */

namespace Minimalism\PHPDump\MySQL;


class MySQLState
{
    const UNDEFINED = 0;
    const LOGIN = 1;
    const REQUEST = 2;
    const RESPONSE_OK = 3;
    const RESPONSE_MESSAGE = 4;
    const RESPONSE_TABULAR = 5;
    const RESPONSE_SHOW_FIELDS = 6;
    const FIELD_PACKET = 7;
    const ROW_PACKET = 8;
    const RESPONSE_PREPARE = 9;
    const PREPARED_PARAMETERS = 10;
    const PREPARED_FIELDS = 11;
    const AUTH_SWITCH_REQUEST = 12;
    const AUTH_SWITCH_RESPONSE = 13;

    public static function getName($state)
    {
        $cache = null;
        if ($cache === null) {
            $clazz = new \ReflectionClass(static::class);
            $valueNames = array_flip($clazz->getConstants());
        }
        if (isset($valueNames[$state])) {
            return $valueNames[$state];
        } else {
            return "UNKNOWN";
        }
    }
}