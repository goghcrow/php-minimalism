<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/6/13
 * Time: 上午12:55
 */

namespace __;

$x = gzencode("HELLO");
var_dump($x);
var_dump(bin2hex($x));


var_dump(isGzip($x));

exit;

abstract class Node
{
    public function eval()
    {
    }

    abstract public function __toString();
}

class Symbol extends Node
{
    public $name;
    public function __construct($name)
    {
        $this->name = $name;
    }

    public function __toString()
    {
        return "\$$this->name";
    }
}

class Add extends Node
{
    public function __toString()
    {
        return "+";
    }
}

class Mul extends Node
{
    public function __toString()
    {
        return "*";
    }
}

class Assign extends Node
{
    public function __toString()
    {
        return "=";
    }
}

class Num extends Node
{
    public $value;
    public function __construct($value)
    {
        $this->value = $value;
    }

    public function __toString()
    {
        return strval($this->value);
    }
}

class BinaryTree
{
    private $data, $left, $right;

    public function __construct(Node $node, self $left = null, self $right = null)
    {
        $this->data = $node;
        $this->left = $left;
        $this->right = $right;
    }

    public function postOrder(): \Traversable
    {
        if ($this->left) {
            yield from $this->left->postOrder();
        }

        if ($this->right) {
            yield from $this->right->postOrder();
        }

        yield $this->data;
    }
}

//function makeBTree(int $depth)
//{
//    if ($depth === 0) {
//        return null;
//    } else {
//        return new BinaryTree($depth, makeBTree($depth - 1), null);
//    }
//}
//
//foreach (makeBTree(10)->postOrder() as $node) {
//    echo $node, "\n";
//}

/*
i = (1 + 2) * 5
    =
i       *
     +     5
   1   2
*/


$ast =
    new BinaryTree(new Assign(),
        new BinaryTree(new Symbol("i")),
        new BinaryTree(new Mul(),
            new BinaryTree(new Add(),
                new BinaryTree(new Num(1)),
                new BinaryTree(new Num(2))),
            new BinaryTree(new Num(5))));

foreach ($ast->postOrder() as $node) {
    echo strval($node), "\n";
}