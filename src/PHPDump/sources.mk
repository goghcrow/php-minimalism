# Sources are in order such that sequentially loading them fully loads the
# library without triggering the autoloading mechanism
SOURCES = \
	../Buffer/Buffer.php \
	../Buffer/StringBuffer.php \
	../Buffer/MemoryBuffer.php \
	../Buffer/BinaryStream.php \
	../Buffer/Hex.php \
	src/Thrift/TType.php \
	src/Thrift/TMessageType.php \
	src/Thrift/TCodec.php \
