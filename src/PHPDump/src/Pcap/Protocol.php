<?php

namespace Minimalism\PHPDump\Pcap;


use Minimalism\PHPDump\Buffer\Buffer;

interface Protocol
{
    const DETECTED = 1;
    const UNDETECTED = 2;
    const DETECT_WAIT = 3;

    public function getName();

    /**
     * @param Buffer $recordBuffer
     * @param Connection $connection
     * @return int DETECTED|UNDETECTED|DETECT_WAIT
     */
    public function detect(Buffer $recordBuffer, Connection $connection);

    /**
     * @param Connection $connection
     * @return bool
     */
    public function isReceiveCompleted(Connection $connection);

    /**
     * @param Connection $connection
     * @return Packet
     */
    public function unpack(Connection $connection);
}