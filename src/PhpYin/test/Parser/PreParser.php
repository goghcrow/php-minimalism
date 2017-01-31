<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/15
 * Time: 下午11:30
 */

namespace Minimalism\Scheme\Test\PreParser;

use Minimalism\Scheme\Parser\PreParser;

require_once __DIR__ . "/../../vendor/autoload.php";

//$preParser = new PreParser($argv[1]);
$preParser = new PreParser();
$preParser->loadFile(__DIR__ . "/test1.phps");
$tuple = $preParser->parse();
echo $tuple, "\n";