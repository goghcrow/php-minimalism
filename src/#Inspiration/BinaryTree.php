<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/3/31
 * Time: 上午1:39
 */

// @https://gist.github.com/nikic/5dfad67c409dce354ea6
// Tree traversal performance: yield from vs. naive implementation


class BinaryTree {
    private $content, $left, $right;
    public function __construct($content, BinaryTree $left = null, BinaryTree $right = null) {
        $this->content = $content;
        $this->left = $left;
        $this->right = $right;
    }
    public function inOrder() : Traversable {
        if ($this->left) {
            yield from $this->left->inOrder();
        }
        yield $this->content;
        if ($this->right) {
            yield from $this->right->inOrder();
        }
    }
    public function inOrderNaive() : Traversable {
        if ($this->left) {
            foreach ($this->left->inOrderNaive() as $elem) {
                yield $elem;
            }
        }
        yield $this->content;
        if ($this->right) {
            foreach ($this->right->inOrderNaive() as $elem) {
                yield $elem;
            }
        }
    }
}
function makeLinearTree(int $depth) {
    if ($depth === 0) {
        return null;
    }
    return new BinaryTree($depth, makeLinearTree($depth - 1), null);
};
$tree = makeLinearTree(10000);
// 0.01 seconds
$time = microtime(true);
foreach ($tree->inOrder() as $elem);
var_dump(microtime(true) - $time);
// 10 seconds
$time = microtime(true);
foreach ($tree->inOrderNaive() as $elem);
var_dump(microtime(true) - $time);
