# Sources are in order such that sequentially loading them fully loads the
# library without triggering the autoloading mechanism
SOURCES = \
    src/Util/Terminal.php \
    src/Util/T.php \
    src/Util/FileAppender.php \
    src/functions.php \
    src/Buffer/Buffer.php \
    src/Buffer/StringBuffer.php \
    src/Buffer/MemoryBuffer.php \
    src/Buffer/BinaryStream.php \
    src/Buffer/BufferFactory.php \
    src/Buffer/Hex.php \
    src/Pcap/Protocol.php \
    src/Pcap/Packet.php \
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
    src/Nova/NovaProtocol.php \
    src/Nova/NovaPacket.php \
    src/Nova/NovaLocalCodec.php \
    src/Nova/NovaCopy.php \
    src/Nova/NovaPacketFilter.php \
    src/Http/HttpProtocol.php \
    src/Http/HttpPacket.php \
    src/PHPDump.php \
    src/novadump.php