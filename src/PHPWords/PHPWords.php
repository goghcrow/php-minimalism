<?php

namespace Minimalism\PHPWords;


$classNames = [];
$functionNames = [];
$variables = [];

$words = [];

function addWords($varName)
{
    global $words;
    foreach (splitWords($varName) as $word) {
        if (isset($words[$word])) {
            $words[$word]++;
        } else {
            $words[$word] = 1;
        }
    }
}

$iter = scanPHPFiles($argv[1]);
//$iter = scanPHPFiles("/Users/chuxiaofeng/Documents/tmp/laravel");
foreach ($iter as $path) {
    $phpCodes = file_get_contents($path);
    $parser = new PHPParser($phpCodes);
    while (($token = $parser->nextToken()) != null) {
        switch ($token->type) {
            case T_VARIABLE:
                $varName = ltrim($token->value, "\$");
                $variables[strtolower($varName)] = $varName;

                addWords($varName);
                break;

            case T_CLASS:
                $className = $parser->getNextString();
                if ($className) {
                    $classNames[strtolower($className)] = $className;

                    addWords($className);
                }
                break;

            case T_FUNCTION:
                $functionName = $parser->getFunctionName();
                if ($functionName) {
                    $functionNames[strtolower($functionName)] = $functionName;

                    addWords($functionName);
                }
                break;

            // case T_NAMESPACE:
        }
    }
}

//asort($classNames);
//asort($functionNames);
//asort($variables);

//print_r($classNames);
//print_r($functionNames);
//print_r($variables);

//krsort($words);
arsort($words);
print_r($words);


function scanPHPFiles($dir)
{
    $regex = '/.*\.php$/';
    $iter = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
    $iter = new \RecursiveIteratorIterator($iter, \RecursiveIteratorIterator::LEAVES_ONLY);
    $iter = new \RegexIterator($iter, $regex, \RegexIterator::GET_MATCH);

    foreach ($iter as $file) {
        $realPath = realpath($file[0]);
        if (strpos($realPath, "vendor/composer") === false) {
            yield $realPath;
        }
    }
}


function splitWords($str)
{
    $excepts = [
        "this" => true,
    ];

    $r = [];
    $words = preg_split('/(?=[A-Z_0-9])/', $str, -1, PREG_SPLIT_NO_EMPTY);
    foreach ($words as $word) {
        $word = lcfirst(trim($word, '_'));
        $word = preg_replace('/[[:^print:]]/', '', $word);
        $l = strlen($word);

        if ($l > 1 && !isset($excepts[$word])) {
            $r[] = $word;
        }
    }
    return $r;
}

class PHPToken
{
    public $line;
    public $type;
    public $value;
    public $name;

    public function __construct(...$args)
    {
        if (count($args) === 1) {
            $this->value = $args[0];
        } else {
            list($this->type, $this->value, $this->line) = $args;
            $this->name = token_name($this->type);
        }
    }

    public function __toString()
    {
        if ($this->type) {
            return "[$this->name $this->value]";

        } else {
            return "[char $this->value]";
        }
    }
}

class PHPParser
{
    private $tokens;
    private $i;
    private $c;

    public function __construct($codes)
    {
        $this->tokens = token_get_all($codes);
        $this->i = 0;
        $this->c = count($this->tokens);
    }

    public function nextToken()
    {
        if (++$this->i <= $this->c) {
            return new PHPToken(...(array)($this->tokens[$this->i - 1]));
        } else {
            return null;
        }
    }

    public function getToken($type)
    {
        $token = $this->nextToken();
        if ($token && $token->type === $type) {
            return $token;
        } else {
            return null;
        }
    }

    public function getNextString()
    {
        start:
        if ($token = $this->nextToken()) {
            if ($token->type === T_WHITESPACE) {
                goto start;
            } else if ($token->type === T_STRING) {
                return $token->value;
            }
        }

        return null;
    }

    public function getFunctionName()
    {
        $magics = [
            '__construct' => true,
            '__destruct' => true,
            '__call' => true,
            '__callStatic' => true,
            '__get' => true,
            '__set' => true,
            '__isset' => true,
            '__unset' => true,
            '__sleep' => true,
            '__wakeup' => true,
            '__toString' => true,
            '__invoke' => true,
            '__set_state' => true,
            '__clone' => true,
            '__debugInfo' => true,
        ];

        $name = $this->getNextString();
        if ($name && !isset($magics[strtolower($name)])) {
            return $name;
        }
        return null;
    }
}