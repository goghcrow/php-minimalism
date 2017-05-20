<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/5/13
 * Time: 下午2:22
 */

$secret = "EQZGCJBRGASGU422GBXW4ZLDJVXEQT3FJNKWMUSHHFAVSZKJMY2TQWKDO4ZC43TVI5TEWOLCJUYVQZSJMI4XM22XKF4U43LF1";
$argv[1] = $secret;

if (!isset($argv[1])) {
    echo <<<USAGE
Usage
    $argv[0] \$secret
    secret参见 https://cas.qima-inc.com/ 动态密码secret
USAGE;
    exit(1);
}

$secret = $argv[1];
$otp = new OTP(base32_decode($secret));
$pwd = $otp->TOTP();

while (true) {
    $ttl = $otp->getTTL();
    if ($ttl <= 0) {
        $pwd = $otp->TOTP();
        $ttl = $otp->getTTL();
    }
    echo "$pwd 有效时间剩余 $ttl 秒\n";
    sleep(1);
}

/**
 * Class OTP
 * One-Time Password
 */
final class OTP
{
    private $secret;
    private $expire;

    private $initialTime = 0;
    private $winSeconds; // 时间窗口
    private $digits;     // 密码位数

    public function __construct($secret, $digits = 6, $winSeconds = 30)
    {
        $this->secret = $secret;
        $this->digits = $digits;
        $this->winSeconds = $winSeconds;
        $this->expire = 0;
    }

    /**
     * HMAC-Based One-Time Password
     * @param int $counter
     * @return string
     */
    public function HOTP($counter)
    {
        $counter = pack("J", $counter); // uInt64 大端
        $sha1 = hash_hmac("sha1", $counter, $this->secret, true); // binary
        $pwd = self::truncate($sha1) % 10 ** $this->digits;
        return sprintf("%0{$this->digits}s", $pwd);
    }

    /**
     * Time-based One-time Password
     * @return string
     */
    public function TOTP()
    {
        $win = $this->winSeconds;
        $now = time();
        $time = $now - $this->initialTime;
        $steps = intval($time / $win);
        $this->expire = $win - $now % $win + $now;
        return self::HOTP($steps);
    }

    /**
     * 获取上一次计算的TOTP剩余有效时间
     * @return int
     */
    public function getTTL()
    {
        return $this->expire - time();
    }

    private static function truncate($sha1)
    {
        $offset = ord($sha1[strlen($sha1)-1]) & 0xf; // sha1散列值最后一字节低4位做偏移
        $partial = substr($sha1, $offset, 4); // 偏移4字节，小端转大端取后31位(本地字节序为小端)
        return unpack("N", $partial)[1] & 0x7fffffff; // 31bits
    }
}

function base32_decode($base32)
{
    static $alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ234567=";

    $binStr = "";
    foreach (str_split($base32) as $char) {
        if ($char === '=') {
            continue;
        }
        $binStr .= sprintf("%05b", strpos($alphabet, $char));
    }
    return rtrim(pack("C*", ...array_map("bindec", str_split($binStr, 8))), "\0");
}
