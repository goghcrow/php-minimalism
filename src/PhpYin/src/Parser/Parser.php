<?php
/**
 * Created by PhpStorm.
 * User: chuxiaofeng
 * Date: 17/1/15
 * Time: 下午8:49
 */

namespace Minimalism\Scheme\Parser;


use Minimalism\Scheme\Ast\Argument;
use Minimalism\Scheme\Ast\Assign;
use Minimalism\Scheme\Ast\Attribute;
use Minimalism\Scheme\Ast\Block;
use Minimalism\Scheme\Ast\Call;
use Minimalism\Scheme\Ast\Declare_;
use Minimalism\Scheme\Ast\Define;
use Minimalism\Scheme\Ast\Delimiter;
use Minimalism\Scheme\Ast\Fun;
use Minimalism\Scheme\Ast\If_;
use Minimalism\Scheme\Ast\Keyword;
use Minimalism\Scheme\Ast\Name;
use Minimalism\Scheme\Ast\Node;
use Minimalism\Scheme\Ast\RecordDefine;
use Minimalism\Scheme\Ast\RecordLiteral;
use Minimalism\Scheme\Ast\Tuple;
use Minimalism\Scheme\Ast\VectorLiteral;
use Minimalism\Scheme\Constants;
use Minimalism\Scheme\Interpreter;
use Minimalism\Scheme\Scope;

class Parser
{
    /**
     * @param string $file
     * @return Node
     */
    public static function parse($file)
    {
        $preparser = new PreParser();
        $preparser->loadFile($file);
        $prenode = $preparser->parse();
        $grouped = self::groupAttr($prenode);
        $program = self::parseNode($grouped);
        assert($program instanceof Block);
        // fprintf(STDERR, "AST: $program\n\n\n"); // for debug
        return $program;
    }

    /**
     * @param Node $prenode
     * @return Node
     */
    public static function parseNode(Node $prenode)
    {
        // 程序自身是一个seq，构成一个Block
        // initial program is in a block
        // Block -> Block
        if ($prenode instanceof Block) {
            /* @var Node[] $parsed */
            $parsed = self::parseList($prenode->statements);
            return new Block($parsed, $prenode->file, $prenode->start, $prenode->end, $prenode->line, $prenode->col);
        }

        if ($prenode instanceof Attribute) {
            $a = $prenode;
            return new Attribute(self::parseNode($a->value), $a->attr, $a->file, $a->start, $a->end, $a->line, $a->col);
        }

        // most structures are encoded in a tuple
        // (if t c a) (+ 1 2) (f x y) ...
        // decode them by their first map
        if ($prenode instanceof Tuple) {
            $tuple = $prenode;
            /* @var Node[] $elements */
            $elements = $tuple->elements;

            if (empty($elements)) {
                Interpreter::abort("syntax error", $tuple);
            }

            if (self::delimType($tuple->open, Constants::RECORD_BEGIN)) {
                return new RecordLiteral(self::parseList($elements),
                    $tuple->file, $tuple->start, $tuple->end, $tuple->line, $tuple->col);
            }

            if (self::delimType($tuple->open, Constants::VECTOR_BEGIN)) {
                return new VectorLiteral(self::parseList($elements),
                    $tuple->file, $tuple->start, $tuple->end, $tuple->line, $tuple->col);
            }

            /* @var Node */
            $keyNode = $elements[0];

            if ($keyNode instanceof Name) {
                $keyword = $keyNode->id;

                // -------------------- quote --------------------
//                if ($keyword === Constants::QUOTE_KEYWORD) {
//                    $tElems = $elements;
//                    $tElems[0] = new Name(Constants::SEQ_KEYWORD, $prenode->file, 0, 0, 0, 0);
//                    return new Quote($tElems,
//                        $prenode->open, $prenode->close,
//                        $prenode->file, $prenode->start, $prenode->end, $prenode->line, $prenode->col);
//                }

                // -------------------- sequence --------------------
                if ($keyword === Constants::SEQ_KEYWORD) {
                    /* @var Node[] $statements List<Node> */
                    $statements = self::parseList(array_slice($elements, 1));
                    return new Block($statements,
                        $prenode->file, $prenode->start, $prenode->end, $prenode->line, $prenode->col);
                }

                // -------------------- if --------------------
                if ($keyword === Constants::IF_KEYWORD) {
                    if (count($elements) === 4) {
                        $test = self::parseNode($elements[1]);
                        $conseq = self::parseNode($elements[2]);
                        $alter = self::parseNode($elements[3]);
                        return new If_($test, $conseq, $alter,
                            $prenode->file, $prenode->start, $prenode->end, $prenode->line, $prenode->col);
                    } else {
                        // TODO
                        Interpreter::abort("incorrect format of if", $tuple);
                    }
                }

                // -------------------- definition --------------------
                if ($keyword === Constants::DEF_KEYWORD) {
                    if (count($elements) === 3) {
                        $pattern = self::parseNode($elements[1]);
                        $value = self::parseNode($elements[2]);
                        return new Define($pattern, $value,
                            $prenode->file, $prenode->start, $prenode->end, $prenode->line, $prenode->col);
                    } else {
                        Interpreter::abort("incorrect format of definition", $tuple);
                    }
                }

                // -------------------- assignment --------------------
                if ($keyword === Constants::ASSIGN_KEYWORD) {
                    if (count($elements) === 3) {
                        $pattern = self::parseNode($elements[1]);
                        $value = self::parseNode($elements[2]);
                        return new Assign($pattern, $value,
                            $prenode->file, $prenode->start, $prenode->end, $prenode->line, $prenode->col);
                    } else {
                        Interpreter::abort("incorrect format of definition", $tuple);
                    }
                }

                // -------------------- declare --------------------
                if ($keyword === Constants::DECLARE_KEYWORD) {
                    if (count($elements) < 2) {
                        Interpreter::abort("syntax error in record type definition", $tuple);
                    }
                    /* @var Scope $properties */
                    $properties = self::parseProperties(array_slice($elements, 1));
                    return new Declare_($properties,
                        $prenode->file, $prenode->start, $prenode->end, $prenode->line, $prenode->col);
                }

                // -------------------- anonymous function --------------------
                if ($keyword === Constants::FUN_KEYWORD) {
                    if (count($elements) < 3) {
                        Interpreter::abort("syntax error in function definition", $tuple);
                    }

                    // 形参必须是tuple
                    // construct parameter list
                    $preParams = $elements[1];
                    if (!($preParams instanceof Tuple)) {
                        Interpreter::abort("incorrect format of parameters: preParams", $preParams);
                    }

                    // (op (para-list) )
                    // 支持两种参数声明方式
                    // 1. all names 2. all tuples
                    /*
                    (fun (a b) (+ a b))
                    (fun ([a Int :default 42]
                          [b Int :default 0])
                       (+ a b)))
                    (fun ([a Int]
                          [b Int])
                       (+ a b)))
                    */

                    // parse the parameters, test whether it's all names or all tuples
                    $hasName = false;
                    $hasTuple = false;
                    /* @var Name[] $paramNames List<Name> */
                    $paramNames = [];
                    /* @var Node[] $paramTuples List<Node>  */
                    $paramTuples = [];

                    foreach ($preParams->elements as $p) {
                        if ($p instanceof Name) {
                            $hasName = true;
                            $paramNames[] = $p;
                        } else if ($p instanceof Tuple) {
                            $hasTuple = true;
                            /* @var Node[] $argElements List<Node>*/
                            $argElements = $p->elements;
                            if (count($argElements) === 0) {
                                Interpreter::abort("illegal argument format: $p", $p);
                            }
                            if (!($argElements[0] instanceof Name)) {
                                Interpreter::abort("illegal argument name : $argElements[0]", $p);
                            }

                            /* @var Name $name */
                            $name = $argElements[0];
                            if (!$name->id !== Constants::RETURN_ARROW) {
                                $paramNames[] = $name;
                            }
                            $paramTuples[] = $p;
                        }
                    }

                    if ($hasName && $hasTuple) {
                        Interpreter::abort("parameters must be either all names or all tuples: $preParams", $preParams);
                        return null;
                    }

                    /* @var Scope $properties */
                    $properties = $hasTuple ? self::parseProperties($paramTuples) : new Scope;

                    // construct body
                    /* @var Node[] $statements List<Node>*/
                    $statements = self::parseList(array_slice($elements, 2));
                    $start = $statements[0]->start;
                    $end = end($statements)->end;
                    $body = new Block($statements, $prenode->file, $start, $end, $prenode->line, $prenode->col);

                    return new Fun($paramNames, $properties, $body,
                        $prenode->file, $prenode->start, $prenode->end, $prenode->line, $prenode->col);
                }

                // -------------------- record type definition --------------------
                // (record name fields)
                // (record name parent fields)
                // (name [name type :k v] [name type :k v])
                // (name (name1 name2) [name type :k v] [name type :k v])
                // (record A :x 1 :y 2)
                // (record A [a Int :x 1] [b Int :y 2])
                if ($keyword === Constants::RECORD_KEYWORD) {
                    if (count($elements) < 2) {
                        Interpreter::abort("syntax error in record type definition", $tuple);
                    }

                    $name = $elements[1];
                    $maybeParents = $elements[2];

                    if (!($name instanceof Name)) {
                        Interpreter::abort("syntax error in record name: name", $name);
                        return null;
                    }


                    /* @var Name[] $parents List<Name> */
                    $parents = [];
                    /* @var Node[] List<Node>  */
                    $fields = [];

                    // check if there are parents (record A (B C) ...)
                    if ($maybeParents instanceof Tuple && self::delimType($maybeParents->open, Constants::TUPLE_BEGIN))
                    {
                        /* @var Node[] $parentNodes List<Node> */
                        $parentNodes = $maybeParents->elements;
                        $parents = [];
                        foreach ($parentNodes as $p) {
                            if (!($p instanceof Name)) {
                                Interpreter::abort("parents can only be names", $p);
                            }
                            $parents[] = $p;
                        }
                        $fields = array_slice($elements, 3);
                    } else {
                        $fields = array_slice($elements, 2);
                    }

                    /* @var Scope $properties */
                    $properties = self::parseProperties($fields);
                    return new RecordDefine($name, $parents, $properties,
                        $prenode->file, $prenode->start, $prenode->end, $prenode->line, $prenode->col);
                }
            }

            // -------------------- application --------------------
            // must go after others because it has no keywords
            /* @var Node $func */
            $func = self::parseNode($elements[0]);
            /* @var Node[] $parsedArgs List<Node> */
            $parsedArgs = self::parseList(array_slice($elements, 1));
            /* @var Argument $args */
            $args = new Argument($parsedArgs);
            return new Call($func, $args, $prenode->file, $prenode->start, $prenode->end, $prenode->line, $prenode->col);
        }

        // 非tuple attr block直接返回
        // defaut return the node untouched
        return $prenode;
    }


    /**
     * @param Node[] $prenodes
     * @return Node[]
     */
    public static function parseList(array $prenodes)
    {
        /* @var Node[] $parsed */
        $parsed = [];
        foreach ($prenodes as $s) {
            $parsed[] = self::parseNode($s);
        }
        return $parsed;
    }

    /**
     * treat the list of nodes as key-value pairs like (:x 1 :y 2)
     * @param Node[] $prenodes
     * @return Node[] Map<String, Node>
     *
     * 将形如(:x 1 :y 2 :keyword value)的列表解析为kv序对， 用于属性
     */
    public static function parseMap(array $prenodes)
    {
        /* @var Node[] $ret Map<String, Node> */
        $ret = [];
        $size = count($prenodes);
        if ($size % 2 !== 0) {
            Interpreter::abort("must be of the form (:key1 value1 :key2 value2), but got: " . implode(" ", $prenodes), $prenodes[0]);
            return null;
        }

        for ($i = 0; $i < $size; $i += 2) {
            /* @var Node $key */
            $key = $prenodes[$i];
            /* @var Node $value */
            $value = $prenodes[$i + 1];
            if (!($key instanceof Keyword)) {
                Interpreter::abort("key must be a keyword, but got: $key", $key);
            }
            $ret[$key->id] = $value;
        }
        return $ret;
    }

    /**
     * @param Node[] $fields
     * @return Scope
     *
     * id type 必须，kv序对可选
     * property likes
     * [id type :k1 v1 :k2 v2]
     *
     * properties likes
     * (property1 property2)
     * ([id type] [])
     *
     * 1. declare
     * 2. 函数参数
     * 3. record 声明
     */
    public static function parseProperties(array $fields)
    {
        /* @var $properties Scope */
        $properties = new Scope;

        foreach ($fields as $field) {
            if ($field instanceof Tuple && self::delimType($field->open, Constants::VECTOR_BEGIN)) {
                /* @var Node[] $elements List<Node> */
                $elements = self::parseList($field->elements);
                if (count($elements) < 2) {
                    Interpreter::abort("empty record slot not allowed", $field);
                }

                /* @var Node $nameNode */
                $nameNode = $elements[0];
                if (!($nameNode instanceof Name)) {
                    Interpreter::abort("expect field name, but got: $nameNode", $nameNode);
                }
                $id = $nameNode->id;
                if ($properties->containsKey($id)) {
                    Interpreter::abort("duplicated field name: $nameNode", $nameNode);
                }

                /* @var Node $typeNode */
                $typeNode = $elements[1];
                $properties->putProperty($id, "type", $typeNode);
                // $properties->putType($id, $typeNode); // TODO 这里为什么不用putType


                /* @var Node[] $props Map<String, Node>*/
                $props = self::parseMap(array_slice($elements, 2));
                $properties->putProperties($nameNode->id, $props);
            } else {
                // TODO
                Interpreter::abort("property must be vector: $field", $field);
            }
        }

        return $properties;
    }

    /**
     * @param Node $prenode
     * @return Node
     */
    public static function groupAttr(Node $prenode)
    {
        if ($prenode instanceof Tuple) {
            /* @var $t Tuple*/
            $t = $prenode;
            /* @var Node[] $elements List<Node> */
            $elements = $t->elements;
            /* @var Node[] $newElems List<Node> */
            $newElems = [];

            if (!empty($elements)) {

                // 所有tuple不能以 ATTRIBUTE_ACCESS 作为第一个元素
                /* @var Node $grouped */
                $grouped = $elements[0];
                if (self::delimType($grouped, Constants::ATTRIBUTE_ACCESS)) {
                    Interpreter::abort("illegal keyword: $grouped", $grouped);
                }

                // 递归处理 tuple 第一个元素仍旧是tuple的情况
                $grouped = self::groupAttr($grouped);

                // 从第二个元素开始遍历
                for ($c = count($elements), $i = 1; $i < $c; $i++) {
                    /* @var Node $node1 */
                    $node1 = $elements[$i];
                    if (self::delimType($node1, Constants::ATTRIBUTE_ACCESS)) {
                        if ($i + 1 >= $c) {
                            // tuple的最后一个元素不允许为 .
                            Interpreter::abort("illegal position for .", $node1);
                        }

                        /* @var Node $node2 */
                        $node2 = $elements[$i + 1];
                        // if (self::delimType($node1, Constants::ATTRIBUTE_ACCESS)) {
                        if (!($node2 instanceof Name)) {
                            Interpreter::abort("attribute is not a name", $node2);
                        }

                        // $node2 instanceof Name
                        $grouped = new Attribute($grouped, $node2, $grouped->file,
                            $grouped->start, $node2->end, $grouped->line, $grouped->col);
                        $i++;   // skip
                        // }

                    } else {
                        $newElems[] = $grouped;
                        $grouped = self::groupAttr($node1);
                    }
                }
                $newElems[] = $grouped;
            }
            return new Tuple($newElems, $t->open, $t->close, $t->file, $t->start, $t->end, $t->line, $t->col);
        } else {
            return $prenode;
        }
    }

    public static function delimType(Node $c, $d)
    {
        // TODO $c null
        return $c instanceof Delimiter && $c->shape === $d;
    }
}