<?php

namespace Minimalism\PHPDump\Pcap;


use Minimalism\PHPDump\Buffer\Buffer;

interface Protocol
{
    public function getName();

    /**
     * @param Buffer $buffer
     * @return bool
     */
    public function isReceiveCompleted(Buffer $buffer);

    /**
     * @param Buffer $buffer
     * @return bool
     */
    public function detect(Buffer $buffer);

    /**
     * @param Buffer $buffer
     * @return Packet
     */
    public function unpack(Buffer $buffer);
}