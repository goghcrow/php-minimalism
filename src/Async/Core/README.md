`任何异步模型，最终都是基于异步回调的`

`异步回调本质上是 Continuation`

`异步回调是手写的CPS程序`



`Continuation 可以用来实现各种控制结构，比如Coroutine Generator Exception ...`

`Continuation 的具体实现 First-class Continuation 和 Continuation Passing Style`

`First-class Continuation: call/cc， Call with Current Continuation`

`call/cc: 捕捉当前的执行上下文到一个 Continuation 对象 中，以它为参数调用传递给 call/cc 的函数`

`CPS: 函数接受一个额外的函数作为参数，当函数计算出结果的时候，会以 结果为参数调用参数所指定的函数（Continuation）`



`用递归代替维护状态`

`new 新对象(immutable) 代替维护状态`

`zhaojie: 维护状态实在是一件麻烦的事情，远不如创建并返回新对象来的简单`





`C# AsyncEnumerator`
`C# await`
`JS await`
`JS windjs`
`JS co`



`yield 内部会保存vm stack`

