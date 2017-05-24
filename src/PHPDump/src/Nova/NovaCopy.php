<?php

namespace Minimalism\PHPDump\Nova;


use Minimalism\PHPDump\Thrift\TMessageType;


class NovaCopy
{
    public $file;

    const GENERIC_SERVICE = "com.youzan.nova.framework.generic.service.GenericService";

    public function __construct($file)
    {
        $this->file = $file;
    }

    public function __invoke(NovaPDU $novaPacket)
    {
        $thriftPacket = $novaPacket->thriftPacket;
        $args = $novaPacket->thriftLocalArgs;

        $type = $thriftPacket->type;
        $ip = $novaPacket->dstIp;
        $port = $novaPacket->dstPort;
        $service = $novaPacket->service;
        $method = $novaPacket->method;

        if ($this->filter($type, $service, $method)) {
            $className = "\\" . str_replace('.', '\\', ucwords($service, '.'));
            $names = $this->getParamNames($className, $method);
            if ($names === null) {

            } else {
                if ($service === self::GENERIC_SERVICE) {
                    $args = array_combine($names, $args);
                    /* @var \Com\Youzan\Nova\Framework\Generic\Service\GenericRequest */
                    $request = $args["request"];
                    $service = $request->serviceName;
                    $method = $request->methodName;
                    $jsonArgs = $request->methodParams; // TODO 重新打包, 复杂参数被打包两次，这里解开打一次
                } else {
                    $jsonArgs = json_encode(array_combine($names, $args));
                }
                $novaCmd = "nova -h=$ip -p=$port -m=$service.$method -a '$jsonArgs'\n";
                echo $novaCmd;
                swoole_async_write($this->file, $novaCmd, -1);

            }
        }
    }

    private function filter($type, $service, $method)
    {
        if ($type !== TMessageType::CALL) {
            return false;
        }

        if (NovaPacketFilter::isHeartbeat($service, $method)) {
            return false;
        }

        return true;
    }

    private function getParamNames($className, $methodName)
    {
        static $cache = [];

        $k = "$className:$methodName";
        if (!isset($cache[$k])) {
            try {
                $method = new \ReflectionMethod($className, $methodName);
                $params = $method->getParameters();
                $names = [];
                foreach ($params as $param) {
                    $names[] = $param->getName();
                }
                $cache[$k] = $names;
            } catch (\Exception $ex) {
                echo $ex;
                $cache[$k] = null;
            }
        }

        return $cache[$k];
    }
}