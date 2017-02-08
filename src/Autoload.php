<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/7
 * Time: 上午10:43
 */

namespace Minimalism;

/**
 * Class Autoload 支持远程文件的简易自用psr4 autoload
 * @package Minimalism
 */
final class Autoload
{
    const DS = "/"; // 兼容URL
    public $ext = ".php";
    public $vendor = "_";
    public $basedir;
    public $psr4;

    /**
     * Autoload constructor.
     * @param array $basedir 仓库地址，配置本地路径优先
     * @param array $psr4 与composer->autoload->psr-4 规则相同 [namespace prefix => path]
     */
    public function __construct(array $basedir, array $psr4)
    {
        $this->basedir = $basedir;
        $localdir = $basedir[0];
        $this->vendor = $localdir . self::DS . $this->vendor;
        $this->psr4 = $psr4;

        $r = spl_autoload_register([$this, "psr4Autoload"]);
        assert($r === true);
    }

    public function psr4Autoload($class)
    {
        if ("\\" === $class[0]) {
            $class = substr($class, 1);
        }
        $psr4path = strtr($class, '\\', self::DS) . $this->ext;
        foreach ($this->psr4 as $prefix => $path) {
            if ((strpos($class, $prefix) === 0)) {
                $relative = $path . self::DS . substr($psr4path, strlen($prefix));

                foreach ($this->basedir as $dir) {
                    $absolute = $dir . self::DS . $relative;
                    if ($this->import($absolute)) {
                        break;
                    }
                }
            }
        }

        // TODO: fallback
        return $this->import($this->vendor . self::DS . $psr4path);
    }

    public function import($file)
    {
        $isurl = strncasecmp($file, "http://", 4) === 0 ||
            strncasecmp($file, "https://", 4) === 0;

        if ($isurl) {
            $file = $this->download($file);
        }

        if (file_exists($file)) {
            require_once $file;
            return true;
        } else {
            return false;
        }
    }

    private function download($url)
    {
        $file = $this->vendor . parse_url($url, PHP_URL_PATH);

        if (file_exists($file)) {
            return $file;
        }

        @mkdir(dirname($file), 0777, true);
        $opts = [
            "http"=>["method"=>"GET","timeout" => 3],
            "ssl" =>["verify_peer"=>false,"verify_peer_name"=>false,]];
        $ctx  = stream_context_create($opts);
        $contents = @file_get_contents($url, false, $ctx);
        // $http_response_header;

        if ($contents === false || strpos($contents, "<?php") !== 0) {
            return false;
        }

        return file_put_contents($file, $contents) > 0 ? $file : false;
    }
}