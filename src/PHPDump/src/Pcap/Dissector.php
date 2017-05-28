<?php

namespace Minimalism\PHPDump\Pcap;



interface Dissector
{
    const DETECTED = 1;
    const UNDETECTED = 2;
    const DETECT_WAIT = 3;

    public function getName();

    /**
     * @param Connection $connection
     * @return int DETECTED|UNDETECTED|DETECT_WAIT
     */
    public function detect(Connection $connection);

    /**
     * @param Connection $connection
     * @return bool
     */
    public function isReceiveCompleted(Connection $connection);

    /**
     * @param Connection $connection
     * @return PDU|null
     */
    public function dissect(Connection $connection);
}