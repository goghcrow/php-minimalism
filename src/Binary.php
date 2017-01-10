<?php

namespace Minimalism;


class Binary
{
    public $body;
    public $bigEndian = true;

    public function __construct($body = '')
    {
        $this->body = $body;
    }

    public function writeUChar($i)
    {
        $this->body .= pack('C', $i);
    }

    public function writeUShort($i)
    {
        if ($this->bigEndian ) {
            $this->body .= pack('n', $i);
        } else {
            $this->body .= pack('v', $i);
        }
    }

    public function writeUInt($i)
    {
        if ($this->bigEndian) {
            $this->body .= pack('N', $i);
        } else {
            $this->body .= pack('V', $i);
        }
    }
    public function writeUInt64($uint64_str)
    {
        $low = (bcmod($uint64_str, '4294967296'));
        $hi = bcdiv($uint64_str, '4294967296',0);
        if ($this->bigEndian ) {
            $this->body .=pack('NN',$hi,$low);
        } else {
            $this->body .=pack('VV',$low,$hi);
        }
    }

    public function writeInt($i)
    {
        if ($this->bigEndian) {
            $this->body .= pack('N', $i);
        } else {
            $this->body .= pack('V', $i);
        }
    }

    public function writeTinyString($s)
    {
        if ($s == '') {
            $this->body .= pack('C', 0);
        } else {
            $this->body .= pack('C', strlen($s)) . $s;
        }
    }

    public function writeShortString($s)
    {
        if ($this->bigEndian) {
            if ($s == '') {
                $this->body .= pack('n', 0);
            } else {
                $this->body .= pack('n', strlen($s)) . $s;
            }
        } else {
            if ($s == '') {
                $this->body .= pack('v', 0);
            } else {
                $this->body .= pack('v', strlen($s)) . $s;
            }
        }
    }

    public function writeLongString($s)
    {
        if ($s == '') {
            $this->body .= "\0\0\0\0\0";
        } else {
            if ($this->bigEndian) {
                $this->body .= pack('N', strlen($s)) . $s;
            } else {
                $this->body .= pack('V', strlen($s)) . $s;
            }
        }
    }

    public function writeString($s, $len = 0)
    {
        if ($len > 0) {
            $this->body .= pack('a' . ($len - 1) . 'x', $s);
        } else {
            $this->body .= $s . chr(0);
        }
    }

    public function writeFloat($f)
    {
        $this->body .= pack('f', $f);
    }

    public function writeDouble($d)
    {
        $this->body .= pack('d', $d);
    }

    public function readUChar()
    {
        $ret = @unpack('Cret', $this->body);
        if ($ret == false) {
            return null;
        }
        $this->body = substr($this->body, 1);
        return $ret['ret'];
    }

    public function readUShort()
    {
        if ($this->bigEndian) {
            $ret = @unpack('nret', $this->body);
        } else {
            $ret = @unpack('vret', $this->body);
        }
        if ($ret == false) {
            return null;
        }
        $this->body = substr($this->body, 2);
        return $ret['ret'];
    }

    public function readUInt()
    {
        if ($this->bigEndian) {
            $ret = @unpack('nhi/nlo', $this->body);
        } else {
            $ret = @unpack('vlo/vhi', $this->body);
        }

        if ($ret == false) {
            return null;
        }
        $this->body = substr($this->body, 4);
        return (($ret['hi'] << 16) | $ret['lo']);
    }

    public function readUInt64()
    {
        if ($this->bigEndian) {
            $param = unpack('Nhi/Nlow',$this->body);
        } else {
            $param = unpack('Vlow/Vhi',$this->body);
        }
        $u_int64 = bcadd(bcmul($param['hi'],'4294967296',0),$param['low']);
        return $u_int64;
    }

    public function readInt()
    {
        if ($this->bigEndian) {
            $ret = @unpack('Nret', $this->body);
        } else {
            $ret = @unpack('Vret', $this->body);
        }

        if ($ret == false) {
            return null;
        }
        $this->body = substr($this->body, 4);
        return $ret['ret'];
    }

    public function readData($len, $offset = 0)
    {
        $ret = substr($this->body, $offset, $len);
        $this->body = substr($this->body, $len + $offset);
        return $ret;
    }

    public function readString($end0=false)
    {
        $ret = @unpack('Clen', $this->body);
        if ($ret == false) {
            return null;
        }
        $rets = substr($this->body, 1, $ret['len']);
        $this->body = substr($this->body, $ret['len'] + 1);
        // 长度为0时substr会返回false，需要特殊处理
        if ($ret['len'] == 0) {
            return "";
        }
        return $end0 ? substr($rets,0,-1) : $rets;
    }

    public function readShortString($end0=false)
    {
        if ($this->bigEndian) {
            $ret = @unpack('nlen', $this->body);
        } else {
            $ret = @unpack('vlen', $this->body);
        }

        if ($ret == false) {
            return null;
        }
        $rets = substr($this->body, 2, $ret['len']);
        $this->body = substr($this->body, $ret['len'] + 2);
        // 长度为0时substr会返回false，需要特殊处理
        if ($ret['len'] == 0) {
            return "";
        }
        return $end0 ? substr($rets,0,-1) : $rets;
    }

    public function readInt32String($end0=false)
    {
        if ($this->bigEndian) {
            $ret = @unpack('Nlen', $this->body);
        } else {
            $ret = @unpack('Vlen', $this->body);
        }

        if ($ret == false) {
            return null;
        }
        $rets = substr($this->body, 4, $ret['len']);
        $this->body = substr($this->body, $ret['len'] + 4);
        // 长度为0时substr会返回false，需要特殊处理
        if ($ret['len'] == 0) {
            return "";
        }
        return $end0 ? substr($rets,0,-1) : $rets;
    }

    public function readStdString($len = 0, $droplen = 0)
    {
        $p = strpos($this->body, "\0");
        if ($p === false && $len == 0) {
            return null;
        }

        if ($len == 0) {
            $rets = substr($this->body, 0, $p);
            $this->body = substr($this->body, $p + 1);
        } else {
            $rets = substr($this->body, 0, (($p < $len)? $p: ($len - $droplen)));
            $this->body = substr($this->body, $len);
        }

        return $rets;
    }

    public function readFixedString($len)
    {
        if ($len >= strlen($this->body)) {
            $data = $this->body;
            $this->body = '';
        } else {
            $data = substr($this->body, 0, $len);
            $this->body = substr($this->body, $len);
        }
        return $data;
    }

    public function readFloat()
    {
        $ret = @unpack('fret', $this->body);
        if ($ret == false) {
            return null;
        }
        $this->body = substr($this->body, 4);
        return $ret['ret'];
    }

    public function readDouble()
    {
        $ret = @unpack('dret', $this->body);
        if ($ret == false) {
            return null;
        }
        $this->body = substr($this->body, 8);
        return $ret['ret'];
    }

    public function readReset()
    {
        $ret = $this->body;
        $this->body = '';
        return $ret;
    }

    /**
     * @param array $val_arr 值的数组对应结构化的struct
     * @param string $format_str 格式化字符串
     * @return false/str
     */
    public static function binaryFormat($val_arr,$format_str)
    {
        if (!is_array($val_arr))
        {
            return false;
        }
        $param = array_merge( array($format_str) ,$val_arr);
        $pkt = call_user_func_array('pack',$param);
        return $pkt;
    }
}
