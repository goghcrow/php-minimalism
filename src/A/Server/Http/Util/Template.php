<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/3/4
 * Time: 下午11:35
 */

namespace Minimalism\A\Server\Http\Util;

use function Minimalism\A\Client\async_read;

/**
 * Class Template
 * @package Minimalism\A\Server\Http\Util
 *
 * 重写代码, 使用eval执行
 * 适用于swoole 长生命周期模板渲染工具
 */
class Template
{
    public $file;
    public $source;
    public $code;

    public static $cache;

    public static function render($file, array $ctx = [])
    {
        if (!isset(static::$cache[$file])) {
            static::$cache[$file] = new static($file);
        }
        /** @var static $self */
        $self = static::$cache[$file];
        yield $self->execute($ctx);
    }

    public function __construct($file)
    {
        if (!is_readable($file)) {
            throw new \InvalidArgumentException("$file not found", 500);
        }
        $this->file = $file;
    }

    public function load()
    {
        if ($this->source === null) {
            $this->source = (yield async_read($this->file));
        }
    }

    public function execute(array $ctx)
    {
        if ($this->code === null) {
            yield $this->load();
            $this->code = static::compile($this->source);
        }

        extract($ctx, EXTR_SKIP);
        // TODO eval 安全性与错误处理
        yield eval($this->code);
    }

    /**
     * @param $source
     * @return string
     */
    public static function compile($source)
    {
        // TODO 处理更多T_*
        // TODO 检查危险函数调用token

        $code = "ob_start();";

        // @see http://php.net/manual/en/function.token-get-all.php
        // Each individual token identifier is either a single character (i.e.: ;, ., >, !, etc...),
        // or a three element array containing the token index in element 0,
        // the string content of the original token in element 1 and the line number in element 2
        $tokens = token_get_all($source);
        foreach ($tokens as $token) {
            if (is_array($token)) {
                list($value, $content, $line) = $token;

                switch ($value) {
                    case T_INLINE_HTML:
                        $code .= <<<RAW
echo <<<'λ'
$content
λ;

RAW;
                        break;

                    case T_OPEN_TAG_WITH_ECHO:
                        $code .= "echo ";
                        break;

                    case T_OPEN_TAG:
                    case T_CLOSE_TAG:
                        $code .= "\n";
                        break;


                    default:
                        $code .= $content;
                }
            } else {
                $char = $token;
                $code .= $char;
            }
        }

        $code .= "return ob_get_clean();";
        return $code;
    }
}