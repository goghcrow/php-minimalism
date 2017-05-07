<?php

namespace Minimalism\PHPDump\Thrift;


final class TMessageType extends Enum
{
    const CALL  = 1;
    const REPLY = 2;
    const EXCEPTION = 3;
    const ONEWAY = 4;
}