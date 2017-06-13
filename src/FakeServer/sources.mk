# Sources are in order such that sequentially loading them fully loads the
# library without triggering the autoloading mechanism
SOURCES = \
    src/functions.php \
    src/Buffer/Buffer.php \
    src/Buffer/StringBuffer.php \
    src/Buffer/MemoryBuffer.php \
    src/Buffer/BinaryStream.php \
    src/Buffer/BufferFactory.php \
    scr/MySQL/MySQLState.php \
    src/MySQL/MySQLCommand.php \
    src/MySQL/MySQLField.php \
    src/MySQL/MySQLBinaryStream.php \
    src/MySQL/MySQLConnection.php \
    src/MySQL/FakeMySQLServer.php \
    src/main.php