package DBIx::MyServer;

use warnings;
use strict;
use Socket;
use Carp qw(cluck carp croak);

use Digest::SHA1;

our $VERSION = '0.42';

use constant MYSERVER_PACKET_COUNT	=> 0;
use constant MYSERVER_SOCKET		=> 1;
use constant MYSERVER_DATABASE		=> 4;
use constant MYSERVER_THREAD_ID		=> 5;
use constant MYSERVER_SCRAMBLE		=> 6;
use constant MYSERVER_DBH		=> 7;
use constant MYSERVER_PARSER		=> 8;
use constant MYSERVER_BANNER		=> 9;
use constant MYSERVER_SERVER_CHARSET	=> 10;
use constant MYSERVER_CLIENT_CHARSET	=> 11;
use constant MYSERVER_SALT		=> 12;

use constant FIELD_CATALOG		=> 0;
use constant FIELD_DB			=> 1;
use constant FIELD_TABLE		=> 2;
use constant FIELD_ORG_TABLE		=> 3;
use constant FIELD_NAME			=> 4;
use constant FIELD_ORG_NAME		=> 5;
use constant FIELD_LENGTH		=> 6;
use constant FIELD_TYPE			=> 7;
use constant FIELD_FLAGS		=> 8;
use constant FIELD_DECIMALS		=> 9;
use constant FIELD_DEFAULT		=> 10;


#
# This comes from include/mysql_com.h of the MySQL source
#

use constant CLIENT_LONG_PASSWORD	=> 1;
use constant CLIENT_FOUND_ROWS		=> 2;
use constant CLIENT_LONG_FLAG		=> 4;
use constant CLIENT_CONNECT_WITH_DB	=> 8;
use constant CLIENT_NO_SCHEMA		=> 16;
use constant CLIENT_COMPRESS		=> 32;		# Must implement that one
use constant CLIENT_ODBC		=> 64;
use constant CLIENT_LOCAL_FILES		=> 128;
use constant CLIENT_IGNORE_SPACE	=> 256;
use constant CLIENT_PROTOCOL_41		=> 512;
use constant CLIENT_INTERACTIVE		=> 1024;
use constant CLIENT_SSL			=> 2048;	# Must implement that one
use constant CLIENT_IGNORE_SIGPIPE	=> 4096;
use constant CLIENT_TRANSACTIONS	=> 8192;
use constant CLIENT_RESERVED 		=> 16384;
use constant CLIENT_SECURE_CONNECTION	=> 32768;
use constant CLIENT_MULTI_STATEMENTS	=> 1 << 16;
use constant CLIENT_MULTI_RESULTS	=> 1 << 17;
use constant CLIENT_SSL_VERIFY_SERVER_CERT	=> 1 << 30;
use constant CLIENT_REMEMBER_OPTIONS		=> 1 << 31;

use constant SERVER_STATUS_IN_TRANS		=> 1;
use constant SERVER_STATUS_AUTOCOMMIT		=> 2;
use constant SERVER_MORE_RESULTS_EXISTS		=> 8;
use constant SERVER_QUERY_NO_GOOD_INDEX_USED	=> 16;
use constant SERVER_QUERY_NO_INDEX_USED		=> 32;
use constant SERVER_STATUS_CURSOR_EXISTS	=> 64;
use constant SERVER_STATUS_LAST_ROW_SENT	=> 128;
use constant SERVER_STATUS_DB_DROPPED		=> 256;
use constant SERVER_STATUS_NO_BACKSLASH_ESCAPES => 512;

use constant COM_SLEEP			=> 0;
use constant COM_QUIT			=> 1;
use constant COM_INIT_DB		=> 2;
use constant COM_QUERY			=> 3;
use constant COM_FIELD_LIST		=> 4;
use constant COM_CREATE_DB		=> 5;
use constant COM_DROP_DB		=> 6;
use constant COM_REFRESH		=> 7;
use constant COM_SHUTDOWN		=> 8;
use constant COM_STATISTICS		=> 9;
use constant COM_PROCESS_INFO		=> 10;
use constant COM_CONNECT		=> 11;
use constant COM_PROCESS_KILL		=> 12;
use constant COM_DEBUG			=> 13;
use constant COM_PING			=> 14;
use constant COM_TIME			=> 15;
use constant COM_DELAYED_INSERT		=> 16;
use constant COM_CHANGE_USER		=> 17;
use constant COM_BINLOG_DUMP		=> 18;
use constant COM_TABLE_DUMP		=> 19;
use constant COM_CONNECT_OUT		=> 20;
use constant COM_REGISTER_SLAVE		=> 21;
use constant COM_STMT_PREPARE		=> 22;
use constant COM_STMT_EXECUTE		=> 23;
use constant COM_STMT_SEND_LONG_DATA	=> 24;
use constant COM_STMT_CLOSE		=> 25;
use constant COM_STMT_RESET		=> 26;
use constant COM_SET_OPTION		=> 27;
use constant COM_STMT_FETCH		=> 28;
use constant COM_END			=> 29;

# This is taken from include/mysql_com.h

use constant MYSQL_TYPE_DECIMAL		=> 0;
use constant MYSQL_TYPE_TINY		=> 1;
use constant MYSQL_TYPE_SHORT		=> 2;
use constant MYSQL_TYPE_LONG		=> 3;
use constant MYSQL_TYPE_FLOAT		=> 4;
use constant MYSQL_TYPE_DOUBLE		=> 5;
use constant MYSQL_TYPE_NULL		=> 6;
use constant MYSQL_TYPE_TIMESTAMP	=> 7;
use constant MYSQL_TYPE_LONGLONG	=> 8;
use constant MYSQL_TYPE_INT24		=> 9;
use constant MYSQL_TYPE_DATE		=> 10;
use constant MYSQL_TYPE_TIME		=> 11;
use constant MYSQL_TYPE_DATETIME	=> 12;
use constant MYSQL_TYPE_YEAR		=> 13;
use constant MYSQL_TYPE_NEWDATE		=> 14;
use constant MYSQL_TYPE_VARCHAR		=> 15;
use constant MYSQL_TYPE_BIT		=> 16;
use constant MYSQL_TYPE_NEWDECIMAL	=> 246;
use constant MYSQL_TYPE_ENUM		=> 247;
use constant MYSQL_TYPE_SET		=> 248;
use constant MYSQL_TYPE_TINY_BLOB	=> 249;
use constant MYSQL_TYPE_MEDIUM_BLOB	=> 250;
use constant MYSQL_TYPE_LONG_BLOB	=> 251;
use constant MYSQL_TYPE_BLOB		=> 252;
use constant MYSQL_TYPE_VAR_STRING	=> 253;
use constant MYSQL_TYPE_STRING		=> 254;
use constant MYSQL_TYPE_GEOMETRY	=> 255;

use constant NOT_NULL_FLAG		=> 1;
use constant PRI_KEY_FLAG		=> 2;
use constant UNIQUE_KEY_FLAG		=> 4;
use constant MULTIPLE_KEY_FLAG		=> 8;
use constant BLOB_FLAG			=> 16;
use constant UNSIGNED_FLAG		=> 32;
use constant ZEROFILL_FLAG		=> 64;
use constant BINARY_FLAG		=> 128;
use constant ENUM_FLAG			=> 256;
use constant AUTO_INCREMENT_FLAG	=> 512;
use constant TIMESTAMP_FLAG		=> 1024;
use constant SET_FLAG			=> 2048;
use constant NO_DEFAULT_VALUE_FLAG	=> 4096;
use constant NUM_FLAG			=> 32768;

=head1 NAME

DBIx::MyServer - Server-side implementation of the MySQL network protocol

=head1 SYNOPSIS

Please see the scripts in the C<examples> directory.
C<examples/dbi.pl> along with C<DBIx::MyParse::DBI> shows how to use this module by subclassing it.
C<examples/echo.pl> shows how to use this module directly.

=head1 DESCRIPTION

This module emulates the server side of the MySQL protocol. This allows you to run
your own faux-MySQL servers which can accept commands and queries and reply accordingly.

Please see C<examples/myserver.pl> for a system that allows building functional mysql servers that rewrite queries
or return arbitary data.

=head1 CONSTRUCTOR

=item C<< my $myserver = DBIx::MyServer->new( socket => $socket, parser => $parser, dbh => $dbh ... ) >>

The following parameters are accepted:

C<socket> - the socket that will be used for communication with the client. The socket must be created in advance with
L<socket()> and an incoming connection must be already established using L<accept()>

C<dbh> - a L<DBI> handle. L<DBIx::MyServer> does not use it however it can be used by modules inheriting from L<DBIx::MyServer>

C<parser> - a L<DBIx::MyParser> handle. If you do not override the C<comQuery()> method, this handle will be used to parse the
SQL in order to call the appropriate C<sqlcom...> method.

C<banner> - the server version string that is announced to the client. If not specified, the name and the version of the
L<DBIx::MyServer> module is sent. Some clients, e.g. L<DBD::mysql> attempt to parse that string as a version number in order
to determine the server capabilities. Therefore, it may be a good idea to start your C<banner> with a string in the form
C<5.0.37>. For example, the C<examples/dbi.pl> example takes the C<banner> from the actual MySQL server the connection is
forwarded to. Please note that the MySQL client may isssue a C<select @@version_comment limit 1> command and display the
result to the user.

C<server_charset> - the character set (and collation) the server announces to the client. A number is expected, not
a character set or collation name. To onvert between numbers and names, please consult the C<sql/share/charsets/Index.xml>
file from your MySQL source tree. The numbers are found under the C<id> property of each XML leaf.

Generally you do not need to override the constructor. If you do, please keep in mind that it returns
a blessed C<@ARRAY>, not a blessed C<%HASH>, even though new() accepts an argument hash. This is
done for performance reasons. The first 20 items from the array are reserved for the parent module,
so please put your object properties into the array members from 20 onwards.

=head1 CONNECTION ESTABLISHMENT

You are responsible for opening your listening socket and accepting a connection on it (and possibly forking
a child process). Once the connection has been accepted, you create a C<DBIx::MyServer> object and pass the socket
to it. From then on, you have two options:

=head2 Procedural Connection Establishment

If you want to handle connection establishment yourself, you will need to call those two functions consequtively.
Please see C<examples/echo.pl> for a script that uses procedural connection establishment.

=item C<< $myserver->sendServerHello() >>

The server is the one that initiates the handshake by sending his greeting to the client.

=item C<< my ($username, $database) = $myserver->readClientHello() >>

The client provides his username and the database it wants to connect to. If C<$username> is C<undef>, the client
disconnected before authenticating. To check the password, use C<$myserver->passwordMatches($correct_password)
which will return C<1> if the password provided by the client is correct and C<undef> otherwise.

If you need to know the IP of the client, you need to extract it from the socket that you established yourself.
Please check out the implementation of C<DBIx::MyParse::handshake()> for the correct way to call C<getpeername()>.
The socket being serviced by the current C<DBIx::MyParse> object can be obtained by calling C<getSocket()>.

If you want to let the client in, do a C<< $myserver->sendOK() >>. Otherwise, use C<< $myserver->sendError() >> as described below.

=head2 Connection Establishment with Subclassing

If you are subclassing C<DBIx::MyParse>, the way C<DBIx::MyParse::DBI> does, to establish a connection you call:

=item C<< $myserver->handshake() >>

which completes the handshake between the two parties. The return value will be C<undef> if some I/O error
occured, or the result of the client authorization routine. When the client sends its credentials, the
module will call:

=item C<< $myserver->authorize($remote_host, $username, $database) >>

whose default action is to accept only localhost connections regardless of username or password. You should
override this method to install your own security requirements. If your C<authorize()> returns C<undef>, the
connection will be rejected, if it returns anything else, the connection will be completed and the return
value will be passed back to the caller of C<handshake()>. You can use this return value to communicate the
access rights the particular user is entitled to, so that your script can know them and enforce them.

In case you want to reject the connection, you need to send your own error message via C<sendError()>. If
you accept the connection, the module will send the C<OK> message back to the client for you.

The password supplied by the client is irreversibly encrypted, therefore to verify it, you need to use:

=item C<< $myserver->passwordMatches($expected_password) >>

which will return C<undef> if the password does not match and C<1> otherwise.

If the client supplied a database name on connect, C<comInitDb($database)> will be called.

For an example of a custom C<authorize()>, please see C<DBIx::MyParse::DBI> which does some extra connection setup.

=head1 COMMAND PROCESSING

=head2 Procedural Command Processing

If you want to handle each command individually on your own, you need to call

=item C<< my ($command, $data) = $myserver->readCommand() >>

in a loop and process each command. Sending result sets and errors and terminating the connection is entirely up to you.
The C<examples/odbc.pl> script uses this approach to process queries in the simples possible way without reflecting much
on their contents.

=head2 Command Processing with Subclassing

If you are subclassing C<DBIx::MyParse>, your main script needs to call:

=item C<< $myserver->processCommand() >>

in a loop. The default C<processCommand()> handler will obtain the next command from the client using C<readCommand()>
and will call the appropriate individual command handler, described below. You can override C<processCommand()> if
you want to process the entire packet yourself, in which case you are responsible for calling C<readCommand()> yourself.

C<processCommand()> will return whatever the individual command handler returned. C<undef> is reserved for I/O errors,
which will allow you to conveniently exit your loop. Therefore it is recommended that any handlers that you
override return C<1> to indicate correct operation or non-fatal errors.

=head1 INDIVIDUAL COMMAND HANDLERS

If you do not override the generic C<< processCommand() >> method, the following individual handlers will
be called depending on the actual MySQL command received by the server. The default action of those handlers is
to send a "command unsupported" error back to the client, unless specified otherwise below.

If you want to send an error or an OK message to the client as a response to a command or query, you need to use
C<sendOK()> and C<sendError()> yourself. The parent module will not send any of those for you under no circumstances.

=item C<< $myserver->comSleep($data) >>

The meaning of this command is not clear.

=item C<< $myserver->comQuit() >>

The default action is to C<return undef>, meaning that C<processCommand()> will return C<undef>, which you can use
to exit your command loop. Alternatively, you can override this to call C<die()> or C<exit()>, if you are forking individual
child processes for each client.

=item C<< $myserver->comInitDb($database) >>

This command is used by the client to select a default working database. The default action is to set
the default database for the parser object if one has been specified. This enables the parser to parse
some SQL statements that require a default database, such as SHOW TABLE STATUS. The command is then converted into a
C<USE $database> SQL statement which in turn will trigger C<comQuery("USE $database")> or C<sqlcomChangeDB()>

=item C<< $myserver->comQuery($query_text) >>

This handler is called for all SQL queries received by the server. The action of the default handler is to parse
the query using L<DBIx::MyParse> and evoke a more specific handler. If the parsing results in an error, C<sqlcomError()>
is called which returns the parser error message to the client.

If you override this handler, your implementation must return an array of three items. The first item is the value that
will be returned to your main loop as the return value of C<processCommand()>. You can return anything you please, including
references to complex objects. Returning C<undef> can be used to conveniently terminate the command loop, so you are
generally encouraged to return some true and definied value to indicate proper operation.

The second item you can return is a reference to an array of field definitions created with C<newDefinition()>.
If you provide it, your field definitions will be sent to the client using C<sendDefinitions()>. If you do not provide
a reference, you are responsible for calling C<sendDefinitions()> yourself before you send any data.

The third item you can return is a reference to an array of values that is the actual data to be sent to the client in
response to the query. If you do not provide a reference, you are responsible for sending the data yourself by using the
functions described elsewhere in this document.

=item C<< $myserver->comFieldList($table) >>

This handler is called if the mysql client requests the field list for the specified table. The handler must create a
set of field definitions using C<newDefinition()> and then send them to the client using
C<sendDefinitions(\@definitions, 1)>. It is strongly reccomended that you provide your own working
implementation for C<comFieldList()> because this MySQL command is often issued by the various MySQL connectors and
is essential for the functioning of the C<FEDERATED> table handler.

For an example of easily handling C<comFieldList()>, see L<DBIx::MyServer::DBI> which uses L<DBI>'s C<column_info> to
return the definition of an actual MySQL table from another server.

=item C<< $myserver->comCreateDb($database_name) >>

This handler is called when C<mysqladmin create $database_name> is used. The default is to convert the command into a
C<CREATE DATABASE $database_name> SQL statement, so that C<comQuery("CREATE DATABASE $database_name")> is triggered,
which in turn will call C<sqlcomCreateDb()>.

=item C<< $myserver->comDropDb($database_name) >>

This handler is called when C<mysqladmin drop $database_name> is used. The default is to convert the command into a
C<DROP DATABASE $database_name> SQL statement so that C<comQuery("DROP DATABASE $database_name")> is triggered, which
in turn will call C<sqlcomDropDb()>.

=item C<< $myserver->comShutdown() >>

This handler is called when C<mysqladmin shutdown> is used. The default action is to die(), in other words,
any authorized client can shut down its own child, or the entire server, if the server is not forking.

=item C<< $myserver->comStatistics() >>

The default action is to send our PID to the client. If you want to override that, you can use C<_sendPacket($string)>
(rather than C<sendOK()>) to deliver a single string to your client. Please note that C<mysql> and C<mysqladmin>
may attempt to parse this string before displaying it, so you may wish to keep it identical to the one sent by real MySQL servers:

C<Uptime: 10659  Threads: 1  Questions: 756  Slow queries: 0  Opens: 109  Flush tables: 1  Open tables: 30  Queries per second avg: 0.071>

=item C<< $myserver->comProcessInfo() >>

This handler is called when C<mysqladmin processlist> is called from the command line.

=item C<< $myserver->comProcessKill($thread_id) >>

This handler is called as a response by the command issued by C<< mysqladmin kill >>. In C<DBIx::MyServer>,
the MySQL thread ID is equal to the PID of the server process. The default action will L<kill()> the PID however
you are only allowed to kill your own PID.

=item C<< $myserver->comPing() >>

This is used to check if the server is reachable and running. The default action is to do a C<sendOK()>. If you are using
that in a conjunction with an automated monitoring and alert system, you may wish to do a C<sendOK()> only after doing
extra health checks on your own, e.g. if you are reading data from an external data source, do not send an OK unless that
source is reachable.

=head1 INDIVIDUAL QUERY HANDLERS

If you do not override the generic C<< comQuery() >> method, the incoming MySQL query will be parsed to a
L<DBIx::MyParse::Query> object and the object will be dispatched to one of the C<sqlcom...> handlers based on the
type of the query.

The default handlers all return "query unsupported". If you override any of the default handlers, your handler must
return one two or three items as described under C<comQuery()>.

Each C<sqlcom...> handler receives at least two arguments. The first one is the L<DBIx::MyParse::Query> object produced
by L<DBIx::MyParse>. The second is the text of the query. C<sqlcom...> handlers dealing with databases will get the
database name in question as their third argument.

If the parsing resulted in an error, C<sqlcomError($query, $query_string)> is called. The default action of this handler
is to send the parser error message as is to the client.

=head1 RETURNING MESSAGES TO CLIENT

=item C<< $myserver->sendOK($message, $affected_rows, $insert_id, $warning_count) >>

Returns a simple OK response, which can contain a custom message, the number of rows affected by the query, etc.

=item C<< $myserver->sendError($message, $errno, $sqlstate) >>

Returns an error response. IF no C<errno> and C<sqlstate> are specified, generic values will be sent to client.

=head1 RETURNING DATA TO CLIENT

=item C<< my $definition = $myserver->newDefinition(name => 'field_name') >>

Prepares a new field definition that can then be sent to the client. Apart from C<name>, the following attributes are
supported:

	catalog, db, table, org_table
	name, org_name, length, type,
	flags, decimals, default

The default C<type> is C<MYSQL_TYPE_STRING>. The complete list of type constants is available from C<MyServer.pm>.

=item C<< $myserver->sendDefinitions( \@definitions, $skip_envelope ) >>

Sends the previously prepared field definitions to the client. You need to do that before sending the first data row.
You also need to use C<sendDefinitions()> to send a table definition in response to C<comFieldList()> command,
however due to an inconsistency in the MySQL protocol, C<$skip_envelope> must be true.

=item C<sendRow($array_ref)> and C<sendRows($array_ref)>

Those two functions are used to send the actual data to the client. C<sendRow()> expect a reference to the array
containing the values you wish to send. C<sendRows()> expects a reference to an array containing references to arrays.

Please note in the MySQL protocol, all values are sent consequtively without row boundaries. The number of columns in
each row is determined by how many field definitions you send before you start sending the actual data.

=item C<sendEOF()>

Indicates the end of the data from the record set.

=head1 PREPARED STATEMENTS

L<DBIx::MyServer> does not currently support the prepared statement protocol. By default, the C<comStmtPrepare()> handler
will return ER_UNSUPPORTED_PS back to the client, which should instruct a wise client to retry the query using
conventional statemements.

As of Nov 7th, 2006, the ODBC Driver version 3.51.12 does not use prepared statements at all. The FEDERATED
database engine does not use them. DBD::Mysql version 3.0008_1 will attempt a prepared statement and if
ER_UNSUPPORTED_PS is received, it will fall back to normal statements. To force the use of normal statements,
add C<mysql_emulated_prepare=1> to your DSN string.

=head1 SECURITY CONSIDERATIONS

The defaults of this module are meant to allow quick prototyping of MySQL servers by subclassing. Therefore, by default:

=item

Only localhost connections are allowed if you use C<handshake()>. Beyond checking that, no other access checks are
made and all usernames are accepted. No password checks are made. After the handshake, any access restrictions on
individual commands or queriesare entirely up to you.

=item

The default action for comQuit() and comShutdown() is to die() which allows anyone to bring down a server that is not
forking child processes. The default action of C<comProcessKill()> only allows you to kill your own server process via SIGTERM.

=item

A single cryptographic salt is used throughout the lifetime of the process, which may be a security risk.

=cut

sub new {
	my $class = shift;
	my $myserver = bless([], $class);

	my %args = (
		socket => MYSERVER_SOCKET,
		dbh => MYSERVER_DBH,
		parser => MYSERVER_PARSER,
		banner => MYSERVER_BANNER,
		server_charset => MYSERVER_SERVER_CHARSET
	);

        my $max_arg = (scalar(@_) / 2) - 1;

        foreach my $i (0..$max_arg) {
                if (exists $args{$_[$i * 2]}) {
                        $myserver->[$args{$_[$i * 2]}] = $_[$i * 2 + 1];
                } else {
			carp "Unkown argument $_[$i * 2] to $class";
                }
        }

	carp "Argument 'socket' to $class must be present" if not defined $myserver->[MYSERVER_SOCKET];

	$myserver->[MYSERVER_PACKET_COUNT] = 0;
	$myserver->[MYSERVER_THREAD_ID] = $$;
	$myserver->[MYSERVER_SALT] = join('',map { chr(int(33 + rand(94))) } (1..20));

	return $myserver;
}

sub getSocket {
	my $myserver = shift;
	return $myserver->[MYSERVER_SOCKET];
}

sub getDbh {
	my $myserver = shift;
	return $myserver->[MYSERVER_DBH];
}

sub setDbh {
	$_[0]->[MYSERVER_DBH] = $_[1];
}

sub getParser {
	my $myserver = shift;
	return $myserver->[MYSERVER_PARSER];
}

sub getClientCharset {
	my $myserver = shift;
	return $myserver->[MYSERVER_CLIENT_CHARSET];
}





sub newDefinition {
	my $myserver = shift;

	my %params = @_;

	my $definition = bless([], 'DBIx::MyServer::Definition');
	$definition->[FIELD_CATALOG] = $params{catalog};
	$definition->[FIELD_DB] = $params{db} ? $params{db} : $params{database};
	$definition->[FIELD_TABLE] = $params{table};
	$definition->[FIELD_ORG_TABLE] = $params{org_table};
	$definition->[FIELD_NAME] = $params{name};
	$definition->[FIELD_ORG_NAME] = $params{org_name};
	$definition->[FIELD_LENGTH] = defined $params{length} ? $params{length} : 0;
	$definition->[FIELD_TYPE] = defined $params{type} ? $params{type} : MYSQL_TYPE_STRING;
	$definition->[FIELD_FLAGS] = defined $params{flags} ? $params{flags} : 0;
	$definition->[FIELD_DECIMALS] = $params{decimals};
	$definition->[FIELD_DEFAULT] = $params{default};
	return $definition;
}

sub sendDefinitions {
### Sending Definitions...
	my ($myserver, $definitions, $skip_envelope) = @_;

	if (not defined $skip_envelope) {
		$myserver->_sendHeader(scalar(@{$definitions}));
	}

	my $last_send_result;

	foreach my $definition (@{$definitions}) {
		$last_send_result = $myserver->_sendDefinition($definition);
	};

	if (not defined $skip_envelope) {
		return $myserver->_sendDefinitionsEOF();
	} else {
		return $last_send_result;
	}
}

sub _sendDefinition {
### Sending Definition...
	my ($myserver, $definition) = @_;

	my (
		$field_catalog, $field_db, $field_table,
		$field_org_table, $field_name, $field_org_name,
		$field_length, $field_type, $field_flags,
		$field_decimals, $field_default
	) = (
		$definition->[FIELD_CATALOG], $definition->[FIELD_DB],
		$definition->[FIELD_TABLE], $definition->[FIELD_ORG_TABLE],
		$definition->[FIELD_NAME], $definition->[FIELD_ORG_NAME],
		$definition->[FIELD_LENGTH], $definition->[FIELD_TYPE],
		$definition->[FIELD_FLAGS], $definition->[FIELD_DECIMALS],
		$definition->[FIELD_DEFAULT]
	);

#### $field_catalog
#### $field_db
#### $field_table
#### $field_org_table
#### $field_name
#### $field_org_name
#### $field_length
#### $field_type

	my $payload = join('', map { defined $_ ? $myserver->_lengthCodedBinary(length($_)).$_ : chr(0) } (
		$field_catalog, $field_db, $field_table,
		$field_org_table, $field_name, $field_org_name
	));

	$payload .= chr(0x0c);	# Filler
	$payload .= pack('v', 11);		# US ASCII
	$payload .= pack('V', $field_length);
	$payload .= chr($field_type);
	$payload .= defined $field_flags ? pack('v', $field_flags) : pack('v', 0);
	$payload .= defined $field_decimals ? chr($field_decimals) : pack('v','0');
	$payload .= pack('v', 0);		# Filler
	$payload .= $myserver->_lengthCodedString($field_default);

	return $myserver->_sendPacket($payload);
}

sub sendRow {
### Sending Row...
	my ($myserver, $row) = @_;

	my $payload;
	foreach my $cell (@{$row}) {
		if (not defined $cell) {
			$payload .= chr(251);	# Meaning undef value
		} else {
			$payload .= $myserver->_lengthCodedString($cell);
		}
	}

	return $myserver->_sendPacket($payload);
}

sub sendRows {
### Sending Rows...
	my ($myserver, $rows) = @_;
	my $big_data;
	return $myserver->sendEOF() if not defined $rows;
	foreach my $row (@{$rows}) {
	# The data stream is constructed in-line for extra performance
		my $small_data;
		foreach (@{$row}) {
			if (not defined $_) {
				$small_data .= chr(251);	# Undef value
			} else {
				$small_data .= $myserver->_lengthCodedString($_);
			}
		}
		$big_data .= substr(pack('V',length($small_data)),0,3).chr($myserver->[MYSERVER_PACKET_COUNT]++ % 256).$small_data;
	}
	$myserver->_writeData($big_data) if defined $big_data;
	return $myserver->sendEOF();
}

sub _sendHeader {
### Sending Header...
	my ($myserver, $field_count) = @_;
#### $field_count
	return $myserver->_sendPacket($myserver->_lengthCodedBinary($field_count));
}

sub _sendDefinitionsEOF {
### Sending Definitions EOF...
	return $_[0]->_sendPacket(chr(0xfe));
}

sub sendEOF {
### Sending EOF
	my ($myserver, $warning_count, $server_status) = @_;
#### $warning_count
#### $server_status
	my $payload;

	$warning_count = 0 if not defined $warning_count;
	$server_status = SERVER_STATUS_AUTOCOMMIT if not defined $server_status;

	$payload .= chr(0xfe);
	$payload .= pack('v', $warning_count);
	$payload .= pack('v', $server_status);

	my $result = $myserver->_sendPacket($payload);
	$myserver->[MYSERVER_PACKET_COUNT] = 0;

	return $result;
}


sub readCommand {
### Reading Client Command...
	my $data = $_[0]->_readPacket();
	return undef if not defined $data;
	return (
		unpack('C', substr($data, 0, 1)),		# Command
		length($data) > 1 ? substr($data, 1) : undef	# Extra arguments
	);
}



# ====================================

sub processCommand {
### Processing Command...
	my $myserver = shift;

	my ($command, $data) =  $myserver->readCommand();

#### $command
#### $data
	my ($outcome, $definitions, $rows);

	if ($command == COM_SLEEP) {
		($outcome, $definitions, $rows) = $myserver->comSleep($data);
	} elsif ($command == COM_QUIT) {
		($outcome, $definitions, $rows) = $myserver->comQuit();
	} elsif ($command == COM_INIT_DB) {
		($outcome, $definitions, $rows) = $myserver->comInitDb($data);
	} elsif ($command == COM_QUERY) {
		($outcome, $definitions, $rows) = $myserver->comQuery($data);
	} elsif ($command == COM_FIELD_LIST) {
		my $table_name = substr($data, 0, -1);		# Strip trailing \0
		($outcome, $definitions, $rows) = $myserver->comFieldList($table_name);
	} elsif ($command == COM_CREATE_DB) {
		($outcome, $definitions, $rows) = $myserver->comCreateDb($data);
	} elsif ($command == COM_DROP_DB) {
		($outcome, $definitions, $rows) = $myserver->comDropDb($data);
	} elsif ($command == COM_SHUTDOWN) {
		($outcome, $definitions, $rows) = $myserver->comShutdown($data);
	} elsif ($command == COM_STATISTICS) {
		($outcome, $definitions, $rows) = $myserver->comStatistics();
	} elsif ($command == COM_PROCESS_INFO) {
#		($outcome, $definitions, $rows) = $myserver->comProcessInfo($data);
#	} elsif ($command == COM_CONNECT) {
#		($outcome, $definitions, $rows) = $myserver->comConnect($data);
	} elsif ($command == COM_PROCESS_KILL) {
		($outcome, $definitions, $rows) = $myserver->comProcessKill($data);
	} elsif ($command == COM_DEBUG) {
		($outcome, $definitions, $rows) = $myserver->comDebug($data);
	} elsif ($command == COM_PING) {
		($outcome, $definitions, $rows) = $myserver->comPing($data);
#	} elsif ($command == COM_TIME) {
#		($outcome, $definitions, $rows) = $myserver->comTime($data);
#	} elsif ($command == COM_DELAYED_INSERT) {
#		($outcome, $definitions, $rows) = $myserver->comDelayedInsert($data);
#	} elsif ($command == COM_CHANGE_USER) {
#		($outcome, $definitions, $rows) = $myserver->comChangeUser($data);
	} elsif ($command == COM_STMT_PREPARE) {
		($outcome, $definitions, $rows) = $myserver->comStmtPrepare($data);
	} elsif ($command == COM_STMT_EXECUTE) {
		($outcome, $definitions, $rows) = $myserver->comStmtExecute($data);
	} elsif ($command == COM_STMT_SEND_LONG_DATA) {
		($outcome, $definitions, $rows) = $myserver->comStmtSendLongData($data);
	} elsif ($command == COM_STMT_CLOSE) {
		($outcome, $definitions, $rows) = $myserver->comStmtClose($data);
	} elsif ($command == COM_STMT_RESET) {
		($outcome, $definitions, $rows) = $myserver->comStmtReset($data);
#	} elsif ($command == COM_SET_OPTION) {
#		($outcome, $definitions, $rows) = $myserver->comSetOption($data);
	} elsif ($command == COM_STMT_FETCH) {
		($outcome, $definitions, $rows) = $myserver->comStmtFetch($data);
	} else {
		($outcome, $definitions, $rows) = $myserver->sendErrorUnsupported($command);
	}

	$myserver->sendDefinitions($definitions) if ref($definitions) eq 'ARRAY';
	$myserver->sendRows($rows) if ref($rows) eq 'ARRAY';
#### Outcome is $outcome; command is $command
	return $outcome;
}


