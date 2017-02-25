PHP 7.0 升级指南

TODO 对比一下google翻译
TODO 新特性RFC

翻译自 https://github.com/php/php-src/blob/PHP-7.0.16/UPGRADING

1. 不向后兼容的变更
2. 新特性
3. SAPI模块变更
4. 废弃的功能
5. 变更的函数
6. 新的函数
7. 新的类与接口
8. 移除的扩展与SAPIs
9. 扩展的其他变更
10. 新的全局常量
11. INI文件处理变更
12. Windows支持
13. 其他变更


========================================
1. 不向后兼容的变更
========================================

语言变更
================

变量处理变更
----------------------------

* 可变变量, 属性与方法引用 现在按照从左到右的语义进行解释. 一些例子:

      $$foo['bar']['baz'] // interpreted as ($$foo)['bar']['baz']
      $foo->$bar['baz']   // interpreted as ($foo->$bar)['baz']
      $foo->$bar['baz']() // interpreted as ($foo->$bar)['baz']()
      Foo::$bar['baz']()  // interpreted as (Foo::$bar)['baz']()

  恢复之前的语义需显式加入花括号:

      ${$foo['bar']['baz']}
      $foo->{$bar['baz']}
      $foo->{$bar['baz']}()
      Foo::{$bar['baz']}()

* global关键词现在只接受简单变量. 形如

      global $$foo->bar;

  现在需要改写:

      global ${$foo->bar};

* 变量或者函数调用被圆括号围绕不再影响语义行为. 
  以下代码示例, 传递函数调用结果给by-reference函数

      function getArray() { return [1, 2, 3]; }

      $last = array_pop(getArray());
      // Strict Standards: Only variables should be passed by reference
      $last = array_pop((getArray()));
      // Strict Standards: Only variables should be passed by reference

  现在无论圆括号是否使用都将将抛出strict standards错误. 之前第二种情况没有提示.

* 引用赋值时, 数组元素或对象属性的创建顺序发生变更.

      $array = [];
      $array["a"] =& $array["b"];
      $array["b"] = 1;
      var_dump($array);

  $array 现在是 ["a" => 1, "b" => 1], 之前是 ["b" => 1, "a" => 1];

Relevant RFCs:
* https://wiki.php.net/rfc/uniform_variable_syntax
* https://wiki.php.net/rfc/abstract_syntax_tree

list()变更
-----------------

* list() 不再逆序赋值:

      list($array[], $array[], $array[]) = [1, 2, 3];
      var_dump($array);

  现在 $array == [1, 2, 3], 而不是 [3, 2, 1]. 
  注意, 只有赋值**顺序**变更, 而值不变. 常规用法:

      list($a, $b, $c) = [1, 2, 3];
      // $a = 1; $b = 2; $c = 3;

  行为不变.

* 不再允许空的 list() 赋值. 以下表达式无效:

      list() = $a;
      list(,,) = $a;
      list($x, list(), $y) = $a;

* list() 不再支持字符串解构(之前只在部分情景支持). 代码如下:

      $string = "xy";
      list($x, $y) = $string;

  现在 $x == null , $y == null 而不是 $x == "x" , $y == "y" (没有提示).
  并且 list() 现在可以工作在实现了ArrayAccess接口的对象上.

      list($a, $b) = (object) new ArrayObject([0, 1]);

  现在 $a == 0 , $b == 1, 之前 $a == null , $b == null

Relevant RFCs:
* https://wiki.php.net/rfc/abstract_syntax_tree#changes_to_list
* https://wiki.php.net/rfc/fix_list_behavior_inconsistency

foreach变更
------------------

* foreach()迭代不再影响数组内部指针(可被current()/next()等一族函数访问). 例如,

      $array = [0, 1, 2];
      foreach ($array as &$val) {
          var_dump(current($array));
      }

  现在打印三次int(0). 之前依次输出int(1), int(2), bool(false).

* 通过by-value方式迭代数组, foreach现在总是操作数组的拷贝, 
  所以数组迭代期间数据修改不会影响迭代行为.

      $array = [0, 1, 2];
      $ref =& $array; // Necessary to trigger the old behavior
      foreach ($array as $val) {
          var_dump($val);
          unset($array[1]);
      }

  现在会打印所有元素(0 1 2), 之前第二个元素1会被跳过.

* 通过by-reference方式迭代数组, 对数组元素的修改会继而影响迭代.
  但在多数情况下, PHP现在会更精确的维护正确的迭代位置.
  例如, by-reference方式迭代期间向数组追加元素

      $array = [0];
      foreach ($array as &$val) {
          var_dump($val);
          $array[1] = 1;
      }

  迭代现在会遍历追加元素, 所以以上示例将会输出"int(0) int(1)", 之前只会输出"int(0)".

* 以 by-value 或 by-reference 方式迭代普通对象(非Traversable)
  都与以 by-reference 方式迭代数组行为一致. 
  除了上一点提到的精确迭代位置维护.

* Traversable对象迭代行为不变.

Relevant RFC: https://wiki.php.net/rfc/php7_foreach

形参处理变更
-----------------------------

* 不再允许定义同名参数. 以下方法会触发一个编译器错误:

      public function foo($a, $b, $unused, $unused) {
          // ...
      }

  此种代码应该对参数名作区分:

      public function foo($a, $b, $unused1, $unused2) {
          // ...
      }

* func_get_arg() 与 func_get_args() 不再返回绑定到形参的原始值, 而是返回可能被修改过的当前值:

      function foo($x) {
          $x++;
          var_dump(func_get_arg(0));
      }
      foo(1);

  现在会打印"2"而不是"1". 代码应该调整为调用func_get_arg(s)之后再修改参数

      function foo($x) {
          var_dump(func_get_arg(0));
          $x++;
      }

  或者完全避免修改任何参数:

      function foo($x) {
          $newX = $x + 1;
          var_dump(func_get_arg(0));
      }

* 相似的, 异常backtraces不限展示参数原始值, 而是展示修改后的值:

      function foo($x) {
          $x = 42;
          throw new Exception;
      }
      foo("string");

  stack trace将会展示

      Stack trace:
      #0 file.php(4): foo(42)
      #1 {main}

  而之前是:

      Stack trace:
      #0 file.php(4): foo('string')
      #1 {main}

  这虽然不会对代码的运行时行为造成影响, 但是你仍然指的以调试的目的了解其中差异.

  debug_backtrace() 与 其他涉及检查函数参数的函数 同样会被这个限制所影响.

Relevant RFC: https://wiki.php.net/phpng

整型处理变更
---------------------------

* 无效的八进制字面量线程将产生编译错误. 比如, 以下表达式不再合法:

      $i = 0781; // 8 is not a valid octal digit!

  之前不合法的数字(与后继的所有合法数字)会被简单的忽略. 
  $i 之前值是7, 因为后两位数字会被静默丢弃.

* 位移负数现在会抛出ArithmeticError:

      var_dump(1 >> -1);
      // ArithmeticError: Bit shift by negative number

* 左移超过整形比特宽度的位数总是返回0:

      var_dump(1 << 64); // int(0)

  之前以上代码的行为取决于CPU架构. 
  例如, x86(包括x86-64)架构下返回int(1), 因为位移操作会回绕.
  
* 类似的, 右移超过整形比特宽度的位数总是返回0或者-1(取决于正负):

      var_dump(1 >> 64);  // int(0)
      var_dump(-1 >> 64); // int(-1)

Relevant RFC: https://wiki.php.net/rfc/integer_semantics

字符串处理变更
--------------------------

* 含有十六进制数字的字符串不再被当做数字处理, 不再会受到特殊对待: 

      var_dump("0x123" == "291");     // bool(false)     (previously true)
      var_dump(is_numeric("0x123"));  // bool(false)     (previously true)
      var_dump("0xe" + "0x1");        // int(0)          (previously 16)

      var_dump(substr("foo", "0x1")); // string(3) "foo" (previously "oo")
      // Notice: A non well formed numeric value encountered

  可以使用filter_var()检测字符串是否包含十六进制数字并转型成整形:

    $str = "0xffff";
    $int = filter_var($str, FILTER_VALIDATE_INT, FILTER_FLAG_ALLOW_HEX);
    if (false === $int) {
        throw new Exception("Invalid integer!");
    }
    var_dump($int); // int(65535)

* 由于双引号字符串与heredocs中添加了Unicode Codepoint Escape语法, 
  "\u{"如果跟随无效字符序列(sequence)将会触发fatal error.

      $str = "\u{xyz}"; // Fatal error: Invalid UTF-8 codepoint escape sequence

  为了避免这种情况情况, 应当对前导反斜线转义:

      $str = "\\u{xyz}"; // Works fine

  "\u"无后继 { 则不受影响. 以下代码行为不变, 不会触发错误.

      $str = "\u202e"; // Works fine

Relevant RFCs:
* https://wiki.php.net/rfc/remove_hex_support_in_numeric_strings
* https://wiki.php.net/rfc/unicode_escape

错误处理变更
-------------------------

* 现在有两类异常: Exception 与 Error, 都实现了新接口 Throwable.
  异常处理代码中的类型提示可能需要变更以应对这种变化.

* 部分 fatal errors 与 recoverable fatal errors 现在通过 Error 抛出.
  由于 Error 是一种不同于 Exception 的类, 所以这一部分异常不会被现有的try/catch代码块捕获.

  现在不能在错误处理函数中静默忽略那些可被转换成异常的recoverable fatal errors.
  特别的, 不能忽略类型错误.

* 解析错误现在会抛出继承于Error的ParseError.
  对于eval()有潜在不合法代码的错误处理应该变更为捕获ParseError, 
  以及基于上一个返回值 / error_get_last()来判断.

* 现在内部类构造失败总是抛出异常. 之前部分构造函数返回NULL或不可用对象.

* 部分E_STRICT提示的错误级别发生变更.

Relevant RFCs:
* https://wiki.php.net/rfc/engine_exceptions_for_php7
* https://wiki.php.net/rfc/throwable-interface
* https://wiki.php.net/rfc/internal_constructor_behaviour
* https://wiki.php.net/rfc/reclassify_e_strict

其他语言变更
----------------------

* 移除对$this产生歧义的上下文静态调用非静态方法的支持. 
  以下情景, $this不再被定义, 虽然允许调用但会产生一个deprecation notice错误:

      class A {
          public function test() { var_dump($this); }
      }

      // Note: Does NOT extend A
      class B {
          public function callNonStaticMethodOfA() { A::test(); }
      }

      (new B)->callNonStaticMethodOfA();

      // Deprecated: Non-static method A::test() should not be called statically
      // Notice: Undefined variable $this
      NULL

  注意, 这里只适用于有歧义的上下文. 如果class B继承于A, 则允许调用且没有任何提示.

* 不再允使用以下类/接口/trait的名称(大小写不敏感):

      bool
      int
      float
      string
      null
      false
      true

  适用于类/接口/trait声明, class_alias() 与 use语句. 

  并且, 以下类/接口/trait名称被留待未来使用, 当前使用时尚不会抛出错误:

      resource
      object
      mixed
      numeric

* yield语言结构变更为右关联运算符, 优先级介于 "print" 与 "=>". 表达式中的yield不再需要圆括号.
  变更会导致某些情况与之前不一致的行为:

      echo yield -1;
      // Was previously interpreted as
      echo (yield) - 1;
      // And is now interpreted as
      echo yield (-1);

      yield $foo or die;
      // Was previously interpreted as
      yield ($foo or die);
      // And is now interpreted as
      (yield $foo) or die;

  以上cases都可以通过添加额外的圆括号解决行为不一致的问题.

  . 移除ASP (<%) 与 script (<script language=php>) 标签.
    (RFC: https://wiki.php.net/rfc/remove_alternative_php_tags)
  . Removed support for assigning the result of new by reference.
  . 移除$this出现歧义上下文中, 静态调用非静态方法. 详情参考 https://wiki.php.net/rfc/incompat_ctx.
  . 移除ini文件中#-风格注释支持, 使用;-风格注释替代.
  . 移除$HTTP_RAW_POST_DATA, 使用 php://input 流替代.

标准库变更
========================

  . 当截断发生在边界时, substr() 现在返回空字符串而不是FALSE.
    (译者: var_dump(substr("a", 1, 1)); PHP5.x bool(false), PHP7.x string(0) "")
  . call_user_method() 与 call_user_method_array() 被移除.
  . ob_start() 不再触发E_ERROR, 在但是输出缓冲区处理器中再次创建输出缓冲区会触发E_RECOVERABLE_ERROR.
  . 优化内部排序算法, 相等元素的排序结果不再发生变动.
    (译者: 不稳定排序修改为稳定排序)
  . fpm-fcgi移除dl()函数.
  . 调用setcookie()传递空的cookie名称会产生一个WARNING, 而不再发送一个空的set-cookie header line.

其他
=====

- Curl:
  . CURLOPT_SAFE_UPLOAD不能被关闭. Curl文件上传必须使用curl_file / CURLFile接口.
    (译者: 不再能使用@file这种非安全方式上传文件)
  . curl_getinfo($ch, CURLINFO_CERTINFO) returns certificate Subject and Issuer
    as a string (PHP >= 5.6.25)

- Date:
  . Removed $is_dst parameter from mktime() and gmmktime().

- DBA
  . dba_delete() now returns false if the key was not found for the inifile
    handler, too.

- GMP
  . Requires libgmp version 4.2 or newer now.
  . gmp_setbit() and gmp_clrbit() now return FALSE for negative indices, making
    them consistent with other GMP functions.

- Intl:
  . Removed deprecated aliases datefmt_set_timezone_id() and
    IntlDateFormatter::setTimeZoneID(). Use datefmt_set_timezone() and
    IntlDateFormatter::setTimeZone() instead.

- libxml:
  . Added LIBXML_BIGLINES parser option. It's available starting with libxml 2.9.0
    and adds suppport for line numbers >16-bit in the error reporting.

- Mcrypt
  . Removed deprecated mcrypt_generic_end() alias in favor of
    mcrypt_generic_deinit().
  . Removed deprecated mcrypt_ecb(), mcrypt_cbc(), mcrypt_cfb() and mcrypt_ofb()
    functions in favor of mcrypt_encrypt() and mcrypt_decrypt() with an
    MCRYPT_MODE_* flag.

- Session
  . session_start() accepts all INI settings as array. e.g. ['cache_limiter'=>'private']
    sets session.cache_limiter=private. It also supports 'read_and_close' which closes
    session data immediately after read data.
  . Save handler accepts validate_sid(), update_timestamp() which validates session
    ID existence, updates timestamp of session data. Compatibility of old user defined
    save handler is retained.
  . SessionUpdateTimestampHandlerInterface is added. validateSid(), updateTimestamp()
    is defined in the interface.
  . session.lazy_write(default=On) INI setting enables only write session data when
    session data is updated.
  . session_regenerate_id() saves current $_SESSION before creating new session ID.

- Opcache
  . 移除opcache.load_comments配置项. 载入文档注释不再有性能消耗, 因此总被开启.

- OpenSSL:
  . Removed the "rsa_key_size" SSL context option in favor of automatically
    setting the appropriate size given the negotiated crypto algorithm.
  . Removed "CN_match" and "SNI_server_name" SSL context options. Use automatic
    detection or the "peer_name" option instead.

- PCRE:
  . Removed support for /e (PREG_REPLACE_EVAL) modifier. Use
    preg_replace_callback() instead.

- PDO_pgsql:
  . Removed PGSQL_ATTR_DISABLE_NATIVE_PREPARED_STATEMENT attribute in favor of
    ATTR_EMULATE_PREPARES.

- Standard:
  . Removed string category support in setlocale(). Use the LC_* constants
    instead.
  . 移除 set_magic_quotes_runtime() 与 别名 magic_quotes_runtime().

- JSON:
  . Rejected RFC 7159 incompatible number formats in json_decode string -
        top level (07, 0xff, .1, -.1) and all levels ([1.], [1.e1])
  . 第一个参数是空字符串或者转型之后是空字符(NULL, FALSE)时, 调用json_decode会导致JSON syntax错误.

- Stream:
  . 移除 set_socket_blocking(), 请使用其别名函数 stream_set_blocking().

- XML:
  . xml_set_object() now requires to manually unset the $parser when finished,
    to avoid memory leaks.

- XSL:
  . Removed xsl.security_prefs ini option. Use XsltProcessor::setSecurityPrefs()
    instead.

========================================
2. 新特性
========================================

- Core
  . 添加 use group 声明.
    (RFC: https://wiki.php.net/rfc/group_use_declarations)
  . 添加 null coalesce 运算符(??).
    (RFC: https://wiki.php.net/rfc/isset_ternary)
  . 64位版本支持 >= 2^31字节字符串.
  . 添加 Closure::call() 方法(仅对用户类有效).
  . 双引号字符串与heredocs中添加\u{xxxxxx} Unicode Codepoint Escape 语法.
  . define() 现在支持数组作为常量值, 修复const支持数组但define不支持的问题.
  . 添加比较操作符 (<=>), 又名太空船操作符.
    (RFC: https://wiki.php.net/rfc/combined-comparison-operator)
  . 添加用于Generators(比如coroutines)的 yield from 操作符.
    (RFC: https://wiki.php.net/rfc/generator-delegation)
  . 允许在各种新的上下文中使用保留字.
    (RFC: https://wiki.php.net/rfc/context_sensitive_lexer)
  . 添加标量类型声明与严格模式(declare(strict_types=1)) 
    (RFC: https://wiki.php.net/rfc/scalar_type_hints_v5)
  . Added support for cryptographically secure user land RNG
    (RFC: https://wiki.php.net/rfc/easy_userland_csprng)

- Opcache
  . Added second level file based opcode cache. It may be enabled by setting
    opcache.file_cache=<DIR> configuration directive in php.ini. The second
    level cache may improve performance when SHM is full, at server restart or
    SHM reset. In addition, it's possibe to use file cache without SHM at all,
    using opcache.file_cache_only=1 (this may be useful for sharing hosting),
    and disable file cache consistency check, to speedup loading at the cost of
    safety, using opcache.file_cache_consistency_checks=0.
  . Added ability to move PHP code pages (PHP TEXT segment) into HUGE pages.
    It's possible to enable/disable this feature in php.ini through
    opcache.huge_code_pages=0/1. OS should be configured to provide huge pages.
  . Added Windows only opcache.file_cache_fallback=1 ini option, which implies
    the implemented fallback mechanism. When OPcache was not able to reattach
    the shared memory segment to the desired address and opcache.file_cache
    is on, opcache.file_cache_only=1 will be automatically enforced.

- OpenSSL
  . Added "alpn_protocols" SSL context option allowing encrypted client/server
    streams to negotiate alternative protocols using the ALPN TLS extension when
    built against OpenSSL 1.0.2 or newer. Negotiated protocol information is
    accessible through stream_get_meta_data() output.

- Reflection
  . 添加 ReflectionGenerator 类 (yield from Traces, current file/line, etc.)
  . 添加ReflectionType类以更好支持返回值类型与标量类型声明特性.
    新的 ReflectionParameter::getType() 与 ReflectionFunctionAbstract::getReturnType() 
    方法均返回 ReflectionType实例.

- Stream:
  . New Windows only stream context options was added to allow blocking reads
    on pipes. To enable it, pass array("pipe" => array("blocking" => true))
    when creating the stream context. Be aware, that this option can under
    circumstances cause dead locks on the pipe buffer. However it can be useful
    in several CLI use case scenarios.

========================================
3. SAPI模块变更
========================================

- FPM
  . Fixed bug #65933 (Cannot specify config lines longer than 1024 bytes).
  . Listen = port now listen on all addresses (IPv6 and IPv4-mapped).

========================================
4. 废弃的功能
========================================

- Core
  . 废弃PHP 4 风格构造函数: 构造函数与类同名.
  . 废弃静态调用非静态方法.

- OpenSSL
  . The "capture_session_meta" SSL context option is now deprecated. Meta
    data concerning active crypto on a stream resource is now accessible
    through the return result from stream_get_meta_data().

========================================
5. 变更的函数
========================================

- unserialize():
  . 添加第二个参数设置允许被反序列化的类:
    (RFC: https://wiki.php.net/rfc/secure_unserialize)
    
    unserialize($foo, ["allowed_classes" => ["MyClass", "MyClass2"]]);

- proc_open():
  . The maximum number of pipes used by proc_open() was previously limited by
  hardcoded value of 16. This limit is now removed and the number of pipes is
  effectively limited by the amount of memory available to PHP.
  . New Windows only configuration option "blocking_pipes" can be used to
  force blocking reads on child process pipes. This covers several
  edge cases in CLI usage however can lead to dead locks. Also, this
  correlates with the new stream context options for pipes.

- array_column():
  The function now supports an array of objects as well as two-dimensional
  arrays. Only public properties are considered, and objects that make use of
  __get() for dynamic properties must also implement __isset().

- stream_context_create()
  It accepts now a Windows only configuration
  array("pipe" => array("blocking" => <boolean>))  which forces blocking reads
  on pipes. This option should be used carefully because due to the
  platform restrictions dead locks on pipe buffers are possible.

- dirname()
  添加可选参数($levels)允许向上获取多次目录名称.
  dirname(dirname($foo)) => dirname($foo, 2);

- debug_zval_dump
  打印结果中使用 "int" 代替 "long",  "float" 代替 "double".

- getenv()
  自7.0.9起, getenv()添加可选的第二个参数, 如果为true, getenv() 仅考虑本地环境变量
  而排除SAPI环境变量.

- fopen()
  自7.0.16起, 加入'e'模式, 为打开的文件描述符设置close-on-exec flag.
  只当PHP在POSIX.1-2008标准操作系统中编译时可用.

========================================
6. 新的函数
========================================

- GMP
  . Added gmp_random_seed().

- PCRE:
  . Added preg_replace_callback_array function
    (RFC: https://wiki.php.net/rfc/preg_replace_callback_array)

- Standard
  . 添加 intdiv() 处理整形相除.
  . 添加 error_clear_last() 重置错误状态.

- Zip:
  . Added ZipArchive::setCompressionIndex() and ZipArchive::setCompressionName()
    for setting the compression method.

- Zlib:
  . Added deflate_init(), deflate_add(), inflate_init(), inflate_add()
    functions allowing incremental/streaming compression/decompression.

========================================
7. 新的类与接口
========================================

- ReflectionGenerator
- ReflectionType

========================================
8. 移除的扩展与SAPIs
========================================

- sapi/aolserver
- sapi/apache
- sapi/apache_hooks
- sapi/apache2filter
- sapi/caudium
- sapi/continuity
- sapi/isapi
- sapi/milter
- sapi/nsapi
- sapi/phttpd
- sapi/pi3web
- sapi/roxen
- sapi/thttpd
- sapi/tux
- sapi/webjames
- ext/mssql
- ext/mysql
- ext/sybase_ct
- ext/ereg

详情参考

https://wiki.php.net/rfc/removal_of_dead_sapis_and_exts
https://wiki.php.net/rfc/remove_deprecated_functionality_in_php7

NOTE: NSAPI was not voted in the RFC, however it was removed afterwards. It turned
out, that the corresponding SDK isn't available anymore.

========================================
9. 扩展的其他变更
========================================

- Mhash
  Mhash is not an extension anymore, use function_exists("mhash") to check whether
  it is avaliable.

- PDO_Firebird
  As of PHP 7.0.16, the fetched data for integer fields is aware of the Firebird
  datatypes. Previously all integers was fetched as strings, starting with the
  aforementioned PHP version integer fields are translated to the PHP integer
  datatype. The 64-bit integers are still fetched as strings in 32-bit PHP
  builds.

- GD
  The bundled libgd requires libwebp instead of libvpx for the WebP functionality.

- Openssl
  minimum supported OpenSSL version series was raised to 0.9.8

- Shmop
  The shmop identifiers have been changed from ints to resources of type shmop.

========================================
10. 新的全局常量
========================================

- Core
  . 加入PHP_INT_MIN.

- PCRE
  . This error constant is added to signal errors due to stack size limitations
    when PCRE JIT support is enabled:
  . PREG_JIT_STACKLIMIT_ERROR

- Zlib
  . These constants are added to control flush behavior with the new
    incremental deflate_add() and inflate_add() functions:
  . ZLIB_NO_FLUSH
  . ZLIB_PARTIAL_FLUSH
  . ZLIB_SYNC_FLUSH
  . ZLIB_FULL_FLUSH
  . ZLIB_BLOCK
  . ZLIB_FINISH

- GD
  . IMG_WEBP (>= 7.0.10)

  . T1Lib support removed, thus lifting the optional dependency on T1Lib, the
    following is therefore not available anymore:

    Functions:
      - imagepsbbox()
      - imagepsencodefont()
      - imagepsextendedfont()
      - imagepsfreefont()
      - imagepsloadfont()
      - imagepsslantfont()
      - imagepstext()

    Resources:
      - 'gd PS font'
      - 'gd PS encoding'

- Zip
  . Filename encoding flags, as of 7.0.8
    - ZipArchive::FL_ENC_GUESS
    - ZipArchive::FL_ENC_RAW
    - ZipArchive::FL_ENC_STRICT
    - ZipArchive::FL_ENC_UTF_8
    - ZipArchive::FL_ENC_CP437

========================================
11. INI文件处理变更
========================================

- Core
  . 移除 asp_tags ini 配置项. 尝试开启将导致fatal error.
  . 移除 always_populate_raw_post_data ini 配置项.
  . realpath_cache_size 默认值变更为4096k.

========================================
12. Windows支持
========================================

- Core
  . Support for native 64 bit integers in 64 bit builds.
  . Support for large files in 64 bit builds.
  . Support for getrusage()

- ftp
  . The ftp extension is always shipped shared
  . For SSL support, the dependency on the openssl extension was abolished. Instead
    it depends alone on the openssl library. If it's present at the compile time,
    ftp_ssl_connect() is enabled automatically.

- imap
  . Static building of ext/imap is disabled

- odbc
  . The odbc extension is always shipped shared

========================================
13. 其他变更
========================================

- Core
  . Instead of being undefined and platform-dependent, NaN and Infinity will
    always be zero when cast to integer.
  . Calling a method on a non-object now raises a catchable error instead of a
    fatal error; see: https://wiki.php.net/rfc/catchable-call-to-member-of-non-object
  . Error messages for zend_parse_parameters, type hints and conversions now
    always say "integer" and "float" instead of "long" and "double".
  . Output buffering now continues to work for an aborted connection if
    ignore_user_abort is set to true.
  . Zend Extensions API was extended with zend_extension.op_array_persist_calc()
    and zend_extensions.op_array_persist() handlers. They allow to store (or
    reset) associated with op_array addition information in Opcache Shared
    Memory.
  . zend_internal_function.reserved[] array was introduced to allow association
    of aditional information with internal functions. In PHP-5 it was possible
    to use zend_function.op_array.reserved[] even for internal functions, but
    now we don't allocate extra space.

- CURL
  . curl_getinfo($ch, CURLINFO_CERTINFO) returns certificate Subject and Issuer
    as a string (PHP >= 7.0.10)