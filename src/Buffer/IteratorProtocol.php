<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/7/15
 * Time: 下午3:16
 */

namespace Minimalism\Buffer;


/**
 * Class Protocol
 * @package Minimalism\Buffer
 *
 * @method int|null readUInt8()
 * @method int|null readUInt16BE()
 * @method int|null readUInt16LE()
 * @method int|null readUInt32BE()
 * @method int|null readUInt32LE()
 * @method int|null readUInt64BE()
 * @method int|null readUInt64LE()
 * @method int|null readInt32BE()
 * @method int|null readInt32LE()
 * @method int|null readFloat()
 * @method int|null readDouble()
 */
class IteratorProtocol
{
    private $protocol;

    /**
     * @var \Generator
     */
    private $gen;

    private $buffer;

    /**
     * @var array
     * [
     *  [int|string|array, callable]
     * ]
     */
    private $typeMap;

    public function __construct(Buffer $buffer)
    {
        $this->buffer = $buffer;
        $this->buffer->on("write", function() {
            $this->doParse();
        });
        $this->initTypeMap();
    }

    public function parse(callable $protocol)
    {
        $closure = \Closure::fromCallable($protocol);
        $reflect = new \ReflectionFunction($closure);
        if (! $reflect->isGenerator()) {
            throw new \InvalidArgumentException();
        }
        $this->protocol = $protocol;
        $this->gen = $protocol($this);
        $this->doParse();
    }

    private function initTypeMap()
    {
        $this->typeMap = [
            "UInt8" => [1, function($bytes) {
                $ret = unpack("Cr", $bytes);
                return $ret == false ? null : $ret["r"];
            }],
            "UInt16BE" => [2, function($bytes) {
                $ret = unpack("nr", $this->buffer->read($bytes));
                return $ret === false ? null : $ret["r"];
            }],
            "UInt16LE" => [2, function($bytes)  {
                $ret = unpack("vr", $bytes);
                return $ret === false ? null : $ret["r"];
            }],
            "UInt32BE" => [4, function($bytes) {
                $ret = unpack("nhi/nlo", $bytes);
                return $ret === false ? null : (($ret["hi"] << 16) | $ret["lo"]);
            }],
            "UInt32LE" => [4, function($bytes) {
                $ret = unpack("vlo/vhi", $bytes);
                return $ret === false ? null : (($ret["hi"] << 16) | $ret["lo"]);
            }],
            "UInt64BE" => [8,function($bytes) {
                $param = unpack("Nhi/Nlow", $bytes);
                return bcadd(bcmul($param["hi"], "4294967296", 0), $param["low"]);
            }],
            "UInt64LE" => [8, function($bytes) {
                $param = unpack("Vlow/Vhi", $bytes);
                return bcadd(bcmul($param["hi"], "4294967296", 0), $param["low"]);
            }],
            "Int32BE" => [4,function($bytes) {
                $ret = unpack("Nr", $bytes);
                return $ret === false ? null : $ret["r"];
            }],
            "Int32LE" => [4,function($bytes) {
                $ret = unpack("Vr", $bytes);
                return $ret === false ? null : $ret["r"];
            }],
            "Float" => [4, function($bytes) {
                $ret = unpack("fr", $bytes);
                return $ret === false ? null : $ret["r"];
            }],
            "Double" => [8, function($bytes) {
                $ret = unpack("dr", $bytes);
                return $ret === false ? null : $ret["r"];
            }],
        ];
    }

    /**
     * @param string $type read${Type}
     * @param int|string|string[] $mix
     * @param callable $transformer
     */
    public function register($type, $mix, callable $transformer = null)
    {
        if ($transformer === null) {
            $transformer = function($a) { return $a; };
        }
        $this->typeMap[$type] = [$mix, $transformer];
    }

    public function write($bytes)
    {
        $this->buffer->write($bytes);
    }

    public function __call($name, $args)
    {
        if (substr($name, 0, 4) === "read") {
            $type = substr($name, 4);
            if (isset($this->typeMap[$type])) {
                return $this->typeMap[$type];
            }
        }
        throw new \BadMethodCallException("$name is not registered");
    }

    private function doParse()
    {
        try {
            while ($this->gen->valid()) {
                $current = $this->gen->current();

                switch (true) {
                    case is_int($current):
                        if ($this->readInt($len = $current) === false) {
                            return;
                        }
                        break;

                    case is_string($current):
                        if ($this->readString($seq = $current) === false) {
                            return;
                        }
                        break;

                    case is_array($current):
                        list($current, $transformer) = $current;

                        switch (true) {
                            case is_int($current):
                                if ($this->readInt($len = $current, $transformer) === false) {
                                    return;
                                }
                                break;

                            case is_string($current);
                                if ($this->readString($seq = $current) === false) {
                                    return;
                                }
                                break;

                            case is_array($current):
                                $find = false;
                                foreach ($seqs = $current as $seq) {
                                    if (is_string($seq) && $this->readString($seq)) {
                                        $find = true;
                                        break;
                                    }
                                }
                                if ($find === false) {
                                    return;
                                }
                                break;

                            default:
                                throw new \InvalidArgumentException("invalid type: " . json_encode($current));
                        }
                        break;

                    default:
                        throw new \InvalidArgumentException("invalid type: " . json_encode($current));
                }

            }


            $protocol = $this->protocol;
            $this->gen = $protocol($this);
            $this->doParse();
        } catch (\Throwable $t) {
            echo $t;
            // TODO
        }
    }

    private function readString($seq, callable $transformer = null)
    {
        if (empty($seq)) {
            $this->gen->send("");
        } else {
            $bytes = $this->buffer->readUntil($seq);
            if ($bytes === false) {
                return false;
            }
            if ($transformer) {
                $bytes = $transformer($bytes);
            }
            $this->gen->send($bytes);
        }
        return true;
    }

    private function readInt($len, callable $transformer = null)
    {
        if ($len <= 0) {
            $this->gen->send("");
        } else {
            $readable = $this->buffer->readableBytes();
            if ($len <= $readable) {
                $bytes = $this->buffer->read($len);
                if ($transformer) {
                    $bytes = $transformer($bytes);
                }
                $this->gen->send($bytes);
            } else {
                return false;
            }
        }
        return true;
    }
}