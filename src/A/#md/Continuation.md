`任何异步模型，最终都是基于异步回调的`

`异步回调本质上是 Continuation`

`异步回调是手写的CPS程序`



`Continuation 可以用来实现各种控制结构，比如Coroutine Generator Exception ...`

`Continuation 的具体实现 First-class Continuation 和 Continuation Passing Style`

`First-class Continuation: call/cc， call-with-current-continuatio`

`call/cc: 捕捉当前的执行上下文到一个 Continuation 对象 中，以它为参数调用传递给 call/cc 的函数`

`CPS: 函数接受一个额外的函数作为参数，当函数计算出结果的时候，会以 结果为参数调用参数所指定的函数（Continuation）`

`对某个语法树节点进行 CPS 变换需要两个参数：节点，以及等待其值的上下文（Context）。Context 即 Continuation，CPS 变换就是利用某种方法让他们联姻`

` 通俗点说 continuation 实际就是代表接下来要做的事或要进行的 操作, 也就是所谓的 the rest of computation` 

`continuation 本质上对应于栈, 是一种control context, 而environment是一种 data context.`

`tail call(尾调用): 如果在函数p内调用了q, 而且q的返回值也是p的返回值, 那么 我们就说q是一个尾调用, 尾调用是不会增加栈的, 因为它本质上就是一个goto语句`


`针对控制流的抽象`

`用递归代替维护状态`

`new 新对象(immutable) 代替维护状态`

`zhaojie: 维护状态实在是一件麻烦的事情，远不如创建并返回新对象来的简单`




`只有λ才能bind变量 -> bound`


`C# AsyncEnumerator`
`C# await`
`JS await`
`JS windjs`
`JS co`



`yield 内部会保存vm stack`