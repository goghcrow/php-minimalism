<?php


exit;

$dir = new \DirectoryIterator(__DIR__);
function func(...$args)
{
    var_dump($args);
}

func(...$dir);
exit;



$raw = file_get_contents("./mac_dump.pcap");
var_dump(strlen($raw));
$buffer = MemoryBuffer::ofBytes($raw);
new PcapNG($buffer);

//echo bin2hex(substr($raw, 0, 100));


// 0a 0d 0d 0a 94 00 00 00
// 4d 3c 2b 1a 01000000




///* Block data to be passed between functions during reading */
//typedef struct wtapng_block_s {
//    guint32             type;           /* block_type as defined by pcapng */
//    wtap_block_t        block;
//
//    /*
//     * XXX - currently don't know how to handle these!
//     *
//     * For one thing, when we're reading a block, they must be
//     * writable, i.e. not const, so that we can read into them,
//     * but, when we're writing a block, they can be const, and,
//     * in fact, they sometimes point to const values.
//     */
//    struct wtap_pkthdr *packet_header;
//    Buffer             *frame_buffer;
//} wtapng_block_t;










/*
typedef struct {
    gboolean shb_read;           // < Set when first SHB read
    gboolean byte_swapped;
    guint16 version_major;
    guint16 version_minor;
    GArray *interfaces;          // < Interfaces found in the capture file.
    gint8 if_fcslen;
    wtap_new_ipv4_callback_t add_new_ipv4;
    wtap_new_ipv6_callback_t add_new_ipv6;
} pcapng_t;
 */



class wtapng_block
{
    public $type;
    public $block;
    public $packet_header;
    public $frame_buffer;
}

/**
 * Class PcapNG
 * https://www.winpcap.org/ntar/draft/PCAP-DumpFileFormat.html
 *
 * 0                   1                   2                   3
 * 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
 * +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 * |                          Block Type                           |
 * +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 * |                      Block Total Length                       |
 * +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 * /                          Block Body                           /
 * /          // variable length, aligned to 32 bits //            /
 * +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 * |                      Block Total Length                       |
 * +-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+
 */
class PcapNG
{
    /**
     * Section Header Block
     * https://www.winpcap.org/ntar/draft/PCAP-DumpFileFormat.html#appendixBlockCodes
     */
    const BLOCK_TYPE_SHB =  0x0A0D0D0A;

    /**
     * Minimum block size = size of block header + size of block trailer.
     *((guint32)(sizeof(pcapng_block_header_t) + sizeof(guint32)))
     *
     * the length of a block that does not have body is 12 bytes.
     */
    const MIN_BLOCK_SIZE = 12;

    const MAX_BLOCK_SIZE = 16 * 1024 * 1024;

    /**
     * Minimum SHB size = minimum block size + size of fixed length portion of SHB.
     * ((guint32)(MIN_BLOCK_SIZE + sizeof(pcapng_section_header_block_t)))
     */
    const MIN_SHB_SIZE = 12 + 16;


    public $buffer;

    public $u32;
    public $u16;
    public $u8;

    public function __construct(MemoryBuffer $buffer)
    {
        $this->buffer = $buffer;

        $this->pcapng_read_block();
    }

    public function pcapng_open()
    {

    }

    /**
     * pcapng: common block header file encoding for every block type
     *
     * typedef struct pcapng_block_header_s {
     *      guint32 block_type;
     *      guint32 block_total_length;
     *      // x bytes block_body
     *      // guint32 block_total_length
     * } pcapng_block_header_t;
     */
    public function pcapng_read_block()
    {
        if ($this->buffer->readableBytes() < 8) {
            return false;
        }

        // block_header
        $bh_u32 = "V";
        $bh_fmt = [
            $bh_u32 . "block_type/",
            $bh_u32 . "block_total_length/",
        ];
        $bh = unpack(implode($bh_fmt), $this->buffer->read(8));

        if ($bh["block_type"] === static::BLOCK_TYPE_SHB) {
            /*
             * BLOCK_TYPE_SHB has the same value regardless of byte order,
             * so we don't need to byte-swap it.
             */

            $this->read_section_header_block($bh);


        } else {
            // TODO
        }

    }

    /**
     * pcapng: section header block file encoding
     * 4 + 2 + 2 + 8
     *
     * typedef struct pcapng_section_header_block_s {
     *      // pcapng_block_header_t
     *      guint32 magic;
     *      guint16 version_major;
     *      guint16 version_minor;
     *      guint64 section_length; // might be -1 for unknown
     *      // ... Options ...
     * } pcapng_section_header_block_t;
     *
     * Section Length: 64-bit value specifying the length in bytes of the following section,
     *                  excluding the Section Header Block itself.
     *                  This field can be used to skip the section, for faster navigation inside large files.
     *                  Section Length equal -1 (0xFFFFFFFFFFFFFFFF) means that the size of the section is not specified,
     *                  and the only way to skip the section is to parse the blocks that it contains.
     *                   Please note that if this field is valid (i.e. not -1), its value is always aligned to 32 bits,
     *                  as all the blocks are aligned to 32-bit boundaries.
     *                  Also, special care should be taken in accessing this field: since the alignment of all the blocks in the file is 32-bit,
     *                  this field is not guaranteed to be aligned to a 64-bit boundary.
     *                  This could be a problem on 64-bit workstations.
     *
     * @param array $bh   block_type block_total_length
     *
     * @return bool
     */
    public function read_section_header_block(array &$bh)
    {
        if ($this->buffer->readableBytes() < 16) {
            return false;
        }


        $u32 = "V";
        $magic = unpack("{$u32}magic", $this->buffer->read(4))["magic"];
        // $magic = sprintf("%x", $magic); // dechex()

        switch ($magic) {
            case 0x1A2B3C4D:
                // le
                $this->u32 = "V";
                $this->u16 = "v";
                $this->u8 = "C";

                break;

            case 0x4D3C2B1A:
                // be
                // byte_swapped
                $this->u32 = "N";
                $this->u16 = "n";
                $this->u8 = "C";
                break;

            default:
                // sys_abort("unsupport pcapng magic");
                break;
        }

        $shb_u32 = $this->u32;
        $shb_u16 = $this->u16;
        $shb_fmt = [
            $shb_u16 . "version_major/",
            $shb_u16 . "version_minor/",
        ];
        $shb = unpack(implode($shb_fmt), $this->buffer->read(4));
        $shb["magic"] = $magic;

        $sec_len_bin = $this->buffer->read(8);
        if (bin2hex($sec_len_bin) === "ffffffffffffffff") {
            $shb["section_length"] = -1;
        } else {
            $a = unpack("{$shb_u32}low/{$shb_u32}hi", $sec_len_bin);
            $shb["section_length"] = $s_len = bcadd(bcmul($a["hi"], "4294967296", 0), $a["low"]);


            $bh["block_total_length"];
            if ($s_len < self::MIN_SHB_SIZE) {
                // "pcapng_read_section_header_block: total block length %u of an SHB is less than the minimum SHB size %u"
                exit;
            }

            if ($s_len > self::MAX_BLOCK_SIZE) {
                // "pcapng_read_section_header_block: total block length %u is too large (> %u)"
                exit;
            }

            /* Options */
            $to_read = $s_len - MIN_SHB_SIZE;
        }

        if ($shb["version_major"] !== 1 || $shb["version_minor"] > 0) {
            // pcapng_read_section_header_block: unknown SHB version %u.%u"
            exit;
        }

        var_dump($shb);
    }


}


/**
 * Class MemoryBuffer
 *
 * 基于 swoole_buffer read,write,expand 接口
 *
 * @author xiaofeng
 *
 * 自动扩容, 从尾部写入数据，从头部读出数据
 *
 * +-------------------+------------------+------------------+
 * | prependable bytes |  readable bytes  |  writable bytes  |
 * |                   |     (CONTENT)    |                  |
 * +-------------------+------------------+------------------+
 * |                   |                  |                  |
 * V                   V                  V                  V
 * 0      <=      readerIndex   <=   writerIndex    <=     size
 *
 */
class MemoryBuffer
{
    private $buffer;

    private $readerIndex;

    private $writerIndex;

    /**
     * @param $bytes
     * @return static
     */
    public static function ofBytes($bytes)
    {
        $self = new static;
        $self->write($bytes);
        return $self;
    }

    public function __construct($size = 1024)
    {
        $this->buffer = new \swoole_buffer($size);
        $this->readerIndex = 0;
        $this->writerIndex = 0;
    }

    public function readableBytes()
    {
        return $this->writerIndex - $this->readerIndex;
    }

    public function writableBytes()
    {
        return $this->buffer->capacity - $this->writerIndex;
    }

    public function prependableBytes()
    {
        return $this->readerIndex;
    }

    public function capacity()
    {
        return $this->buffer->capacity;
    }

    public function get($len)
    {
        if ($len <= 0) {
            return "";
        }

        $len = min($len, $this->readableBytes());
        return $this->rawRead($this->readerIndex, $len);
    }

    public function read($len)
    {
        if ($len <= 0) {
            return "";
        }

        $len = min($len, $this->readableBytes());
        $read = $this->rawRead($this->readerIndex, $len);
        $this->readerIndex += $len;
        if ($this->readerIndex === $this->writerIndex) {
            $this->reset();
        }
        return $read;
    }

    public function readLine()
    {
        if ($this->readableBytes() <= 0) {
            return false;
        }

        $read = "";
        while ($this->readableBytes()) {
            $char = $this->read(1);
            $read .= $char;
            if ($char === "\r" && $this->get(1) === "\n") {
                $read .= $this->read(1);
                return $read;
            }
        }
        return $read;
    }

    public function skip($len)
    {
        $len = min($len, $this->readableBytes());
        $this->readerIndex += $len;
        if ($this->readerIndex === $this->writerIndex) {
            $this->reset();
        }
        return $len;
    }

    public function readFull()
    {
        return $this->read($this->readableBytes());
    }

    public function write($bytes)
    {
        if ($bytes === "") {
            return false;
        }

        $len = strlen($bytes);

        if ($len <= $this->writableBytes()) {

            write:
            $this->rawWrite($this->writerIndex, $bytes);
            $this->writerIndex += $len;
            return true;
        }

        // expand
        if ($len > ($this->prependableBytes() + $this->writableBytes())) {
            $this->expand(($this->readableBytes() + $len) * 2);
        }

        // copy-move
        if ($this->readerIndex !== 0) {
            $this->rawWrite(0, $this->rawRead($this->readerIndex, $this->writerIndex - $this->readerIndex));
            $this->writerIndex -= $this->readerIndex;
            $this->readerIndex = 0;
        }

        goto write;
    }

    public function reset()
    {
        $this->readerIndex = 0;
        $this->writerIndex = 0;
    }

    public function __toString()
    {
        return $this->rawRead($this->readerIndex, $this->writerIndex - $this->readerIndex);
    }

    // NOTICE: 影响 IDE Debugger
    public function __debugInfo()
    {
        return [
            "string" => $this->__toString(),
            "capacity" => $this->capacity(),
            "readerIndex" => $this->readerIndex,
            "writerIndex" => $this->writerIndex,
            "prependableBytes" => $this->prependableBytes(),
            "readableBytes" => $this->readableBytes(),
            "writableBytes" => $this->writableBytes(),
        ];
    }

    private function rawRead($offset, $len)
    {
        if ($offset < 0 || $offset + $len > $this->buffer->capacity) {
            throw new \InvalidArgumentException(__METHOD__ . ": offset=$offset, len=$len, capacity={$this->buffer->capacity}");
        }
        return $this->buffer->read($offset, $len);
    }

    private function rawWrite($offset, $bytes)
    {
        $len = strlen($bytes);
        if ($offset < 0 || $offset + $len > $this->buffer->capacity) {
            throw new \InvalidArgumentException(__METHOD__ . ": offset=$offset, len=$len, capacity={$this->buffer->capacity}");
        }
        return $this->buffer->write($offset, $bytes);
    }

    private function expand($size)
    {
        if ($size <= $this->buffer->capacity) {
            throw new \InvalidArgumentException(__METHOD__ . ": size=$size, capacity={$this->buffer->capacity}");
        }
        return $this->buffer->expand($size);
    }
}

function is_big_endian()
{
    // return bin2hex(pack("L", 0x12345678)[0]) === "12";
    // L ulong 32，machine byte order
    return ord(pack("H2", bin2hex(pack("L", 0x12345678)))) === 0x12;
}