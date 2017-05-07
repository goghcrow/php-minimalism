<?php

namespace Minimalism\PHPDump\Nova;


use Minimalism\PHPDump\Buffer\Hex;
use Minimalism\PHPDump\Thrift\TMessageType;
use Minimalism\PHPDump\Util\T;

class NovaApp
{
    public static $enable = false;

    public $appName;
    public $appPath;

    private function __construct($appName, $appPath)
    {
        $this->appName = $appName;
        $this->appPath = realpath($appPath);
        $this->scanSpec();
        static::$enable = true;
    }

    public static function init($appName, $appPath)
    {
        new static($appName, $appPath);
    }

    public static function dumpThrift($service, $method, $thriftBin)
    {
        $ver = unpack('N', substr($thriftBin, 0, 4))[1];
        $type = $ver & 0x000000ff;

        // THRIFT MESSAGE TYPE
        // CALL  = 1; REPLY = 2; EXCEPTION = 3; ONEWAY = 4;
        if ($type === TMessageType::CALL) {
            try {
                sys_echo(T::format("CALL", T::FG_YELLOW, T::BRIGHT));
                $args = self::decodeServiceArgs($service, $method, $thriftBin);
                sys_echo(T::format(json_encode($args), T::DIM));
                return [$type, $args];
            } catch (\Exception $ex) {
                // Hex::dump($thriftBin, "vvv/8/6");
                // sys_error("decode thrift CALL fail");

                sys_error(get_class($ex) . ":" . $ex->getMessage());
            }
        } else {
            try {
                sys_echo(T::format("REPLY", T::FG_YELLOW, T::BRIGHT));
                $resp = self::decodeResponse($service, $method, $thriftBin);
                sys_echo(T::format(json_encode($resp), T::DIM));
                return [$type, $resp];
            } catch (\Exception $ex) {
                // Hex::dump($thriftBin, "vvv/8/6");
                // sys_error("decode thrift REPLY fail");
                sys_error(get_class($ex) . ":" . $ex->getMessage());
            }
        }
        return [0, []];
    }

    public static function decodeServiceArgs($service, $method, $thriftBin)
    {
        $service = str_replace('.', '\\', ucwords($service, '.'));
        return \Kdt\Iron\Nova\Nova::decodeServiceArgs($service, $method, $thriftBin);
    }

    public static function decodeResponse($service, $method, $thriftBin)
    {
        $service = str_replace('.', '\\', ucwords($service, '.'));
        if (class_exists($service)) {
            $serv = new $service(); /** @var \_ $serv */
            $outspec = $serv->getOutputStructSpec($method);
            $exspec = $serv->getExceptionStructSpec($method);
            $packer = \Kdt\Iron\Nova\Protocol\Packer::getInstance();
            return $packer->decode($thriftBin, $packer->struct($outspec, $exspec), 0);
        } else {
            sys_error("$service class not found");
            Hex::dump($thriftBin, "vvv/8/6");
        }
    }

    public function loadNovaService($novaServicePath)
    {
        $scanner = \Kdt\Iron\Nova\Service\Scanner::getInstance();
        $scanSpec = new \ReflectionMethod(\Kdt\Iron\Nova\Service\Scanner::class, "scanSpec");
        $scanSpec->setAccessible(true);

        foreach (new \DirectoryIterator($novaServicePath) as $fileInfo) {
            if($fileInfo->isDot()) continue;
            if (!$fileInfo->isDir()) continue;

            $composerJson = $fileInfo->getRealPath() . "/composer.json";
            if (!is_readable($composerJson)) continue;

            $composer = json_decode(file_get_contents($composerJson), true);
            if (!isset($composer["autoload"]["psr-4"])) continue;

            foreach ($composer["autoload"]["psr-4"] as $ns => $path) {
                if ($path === "Framework/Generic") continue; // TODO

                if (!is_readable($fileInfo->getRealPath() . "/$path")) continue;

                $path = $fileInfo->getRealPath() . "/$path/";
                // scanSpec($appName, $domain, $path, $baseNamespace)
                $scanSpec->invoke($scanner, "_", "com.youzan.service", $path, $ns);

                break;
            }
        }
    }

    public function scanSpec()
    {
        $env = getenv('KDT_RUN_MODE') ?: get_cfg_var('kdt.RUN_MODE') ?: "online";
        $autoload = "$this->appPath/vendor/autoload.php";
        if (!is_readable($autoload)) {
            sys_abort("$autoload is not readable");
        }
        require $autoload;


        $novaService = $this->appPath . "/vendor/nova-service";
        $this->loadNovaService($novaService);
        return;

        /*
        $novaPath = "$this->appPath/resource/config/$env/nova.php";
        if (!is_readable($novaPath)) {
            sys_abort("$novaPath is not readable");
        }

        $novaConf = require $novaPath;

        $path = new \ReflectionClass(\Zan\Framework\Foundation\Core\Path::class);
        $propPath = $path->getProperty("rootPath");
        $propPath->setAccessible(true);
        $propPath->setValue($this->appPath . "/");


        if (isset($novaConf["novaApi"]) && is_array($novaConf["novaApi"])) {
            $novaApi = $novaConf["novaApi"];
            \Kdt\Iron\Nova\Nova::init($this->parserNovaConfig($novaApi));
        } else {
            $novaService = $this->appPath . "/vendor/nova-service";
            $this->loadNovaService($novaService);
        }
        */
    }

    /*
    public function parserNovaConfig(array $novaApi)
    {
        $rootPath = "$this->appPath/";
        if (isset($novaApi["path"])) {
            if (!isset($novaApi["appName"])) {
                $novaApi["appName"] = $this->appName;
            }
            $novaApi = [ $novaApi ];
        }

        foreach ($novaApi as &$item) {
            if (!isset($item["appName"])) {
                $item["appName"] = $this->appName;
            }
            $item["path"] = $rootPath . $item["path"];
            if(!isset($item["domain"])) {
                $item["domain"] = "com.youzan.service";
            }
            if(!isset($item["protocol"])) {
                $item["protocol"] = "nova";
            }
        }
        unset($item);
        return $novaApi;
    }
    */
}