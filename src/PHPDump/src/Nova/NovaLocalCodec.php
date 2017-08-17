<?php

namespace Minimalism\PHPDump\Nova;


use Kdt\Iron\Nova\Nova;
use Kdt\Iron\Nova\Protocol\Packer;
use Kdt\Iron\Nova\Service\Scanner;

use Minimalism\PHPDump\Buffer\Hex;
use Minimalism\PHPDump\Thrift\ThriftPacket;
use Minimalism\PHPDump\Thrift\TMessageType;
use Minimalism\PHPDump\Util\T;

class NovaLocalCodec
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

    public static function dumpThrift(NovaPDU $novaPacket, ThriftPacket $thriftPacket)
    {
        $type = $thriftPacket->type;
        $service = $novaPacket->service;
        $method = $novaPacket->method;
        $thriftBin =$thriftPacket->thriftBin;

        try {
            switch ($type) {
                case TMessageType::CALL:
                    sys_echo(T::format("CALL", T::FG_YELLOW, T::BRIGHT));
                    $args = self::decodeServiceArgs($service, $method, $thriftBin);
                    sys_echo(T::format(json_encode($args), T::DIM));
                    return $args;

                case TMessageType::REPLY:
                    sys_echo(T::format("REPLY", T::FG_YELLOW, T::BRIGHT));
                    $resp = self::decodeResponse($service, $method, $thriftBin);
                    sys_echo(T::format(json_encode($resp), T::DIM));
                    return $resp;

                case TMessageType::EXCEPTION:
                    sys_echo(T::format("EXCEPTION", T::FG_RED, T::BRIGHT));
                    break;

                case TMessageType::ONEWAY:
                default:
                    sys_echo(T::format("ONEWAT", T::FG_YELLOW, T::BRIGHT));
                    break;
            }
        } catch (\Exception $ex) {
            sys_error(get_class($ex) . ":" . $ex->getMessage());
        }

        return null;
    }

    public static function decodeServiceArgs($service, $method, $thriftBin)
    {
        $service = str_replace('.', '\\', ucwords($service, '.'));
        return Nova::decodeServiceArgs($service, $method, $thriftBin);
    }

    public static function decodeResponse($service, $method, $thriftBin)
    {
        $service = str_replace('.', '\\', ucwords($service, '.'));
        if (class_exists($service)) {
            $serv = new $service(); /** @var \_ $serv */
            $outspec = $serv->getOutputStructSpec($method);
            $exspec = $serv->getExceptionStructSpec($method);
            $packer = Packer::getInstance();
            return $packer->decode($thriftBin, $packer->struct($outspec, $exspec), 0);
        } else {
            sys_error("$service class not found");
            Hex::dump($thriftBin, "vvv/8/6");
        }
        return null;
    }

    public function loadNovaService($novaServicePath)
    {
        $scanner = Scanner::getInstance();
        $scanSpec = new \ReflectionMethod(Scanner::class, "scanSpec");
        $scanSpec->setAccessible(true);

        foreach (new \DirectoryIterator($novaServicePath) as $fileInfo) {
            if($fileInfo->isDot()) {
                continue;
            }
            if (!$fileInfo->isDir())  {
                continue;
            }

            $composerJson = $fileInfo->getRealPath() . "/composer.json";
            if (!is_readable($composerJson)) {
                continue;
            }

            $composer = json_decode(file_get_contents($composerJson), true);
            if (!isset($composer["autoload"]["psr-4"])) {
                continue;
            }

            foreach ($composer["autoload"]["psr-4"] as $ns => $path) {
                if ($path === "Framework/Generic") {
                    continue;
                }

                if (!is_readable($fileInfo->getRealPath() . "/$path")) {
                    continue;
                }

                $path = $fileInfo->getRealPath() . "/$path/";
                $scanSpec->invoke($scanner, "_", "com.youzan.service", $path, $ns);
                break;
            }
        }
    }

    public function scanSpec()
    {
        $autoload = "$this->appPath/vendor/autoload.php";
        if (is_readable($autoload)) {
            /** @noinspection PhpIncludeInspection */
            require $autoload;
            $vendor = "$this->appPath/vendor";
            $aliasLoader = \ZanPHP\SPI\AliasLoader::getInstance();
            $aliasLoader->scan($vendor);
            $serviceLoader = \ZanPHP\SPI\ServiceLoader::getInstance();
            $serviceLoader->scan($vendor);
            $novaService = $this->appPath . "/vendor/nova-service";
            $this->loadNovaService($novaService);
        } else {
            sys_abort("$autoload is not readable");
        }
    }
}