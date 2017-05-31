# Sources are in order such that sequentially loading them fully loads the
# library without triggering the autoloading mechanism
SOURCES = \
    src/Util/Terminal.php \
    src/Util/T.php \
    src/Util/FileAppender.php \
    src/Util/AsciiTable.php \
    src/functions.php \
    src/Buffer/Buffer.php \
    src/Buffer/StringBuffer.php \
    src/Buffer/MemoryBuffer.php \
    src/Buffer/BinaryStream.php \
    src/Buffer/BufferFactory.php \
    src/Buffer/Hex.php \
    src/Pcap/Dissector.php \
    src/Pcap/PDU.php \
    src/Pcap/PcapHdr.php \
    src/Pcap/RecordHdr.php \
    src/Pcap/EtherHdr.php \
    src/Pcap/LinuxSLLHdr.php \
    src/Pcap/IPHdr.php \
    src/Pcap/TCPHdr.php \
    src/Pcap/Connection.php \
    src/Pcap/Pcap.php \
    src/Thrift/Enum.php \
	src/Thrift/TType.php \
	src/Thrift/TMessageType.php \
	src/Thrift/TBinaryReader.php \
	src/Thrift/TCodec.php \
	src/Thrift/ThriftPacket.php \
    src/Nova/NovaDissector.php \
    src/Nova/NovaPDU.php \
    src/Nova/NovaLocalCodec.php \
    src/Nova/NovaCopy.php \
    src/Nova/NovaPacketFilter.php \
    src/Http/HttpDissector.php \
    src/Http/HttpPDU.php \
    src/Http/HttpCopy.php \
    src/MySQL/MySQLState.php \
    src/MySQL/MySQLCommand.php \
    src/MySQL/MySQLField.php \
    src/MySQL/MySQLBinaryStream.php \
    src/MySQL/MySQLDissector.php \
    src/MySQL/MySQLPDU.php \
    src/MySQL/MySQLCopy.php \
    src/PHPDump.php \
    src/novadump.php