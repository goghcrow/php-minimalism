<?php

namespace {
    if (!function_exists("nova_decode")) {
        /**
         * nova协议解包
         *
         * @since 2.0.0
         *
         * @param string $buf 二进制字符串
         * @param string &$service_name 服务名
         * @param string &$method_name 方法名
         * @param string &$ip
         * @param int &$port
         * @param int &$seq_no
         * @param string &$attach 附加字段 通常为json编码字符串
         * @param string &$data nova body
         * @return bool
         */
        function nova_decode($buf, &$service_name, &$method_name, &$ip, &$port, &$seq_no, &$attach, &$data) { return false; }
    }

    class _ {
        public function getOutputStructSpec($method) { return []; }
        public function getExceptionStructSpec($method) { return []; }
    }
}

namespace Kdt\Iron\Nova {
    class Nova {
        public static function decodeServiceArgs($service, $method, $thriftBin) { return []; }
    }
}

namespace Kdt\Iron\Nova\Service {
    class Scanner {
        /**
         * @return self
         */
        public static function getInstance() {}
    }
}

namespace Kdt\Iron\Nova\Protocol {
    class Packer {
        /**
         * @return self
         */
        public static function getInstance() { }
        public function struct($outspec, $exspec) { return []; }
        public function decode($thriftBin, $_, $_) { return []; }
    }
}
