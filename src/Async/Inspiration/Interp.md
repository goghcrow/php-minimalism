# Theoretical and Practical Code Generation
 
 Belleve Invis - Patrisika

https://typeof.net/2014/m/trailer-from-a-interpreter-to-abstract-interpretation.html

https://typeof.net/2014/m/patrisika-how-to-implement-cps-transform.html

trailer from a interpreter to abstract interpretation

how to implement cps transform

从解释器到抽象解释

## 最简单的程序语言

只有3种构造，变量，函数调用，lambda抽象/函数抽象/lambda语义

所有函数都是一元的

1. x
2. [F X]
3. [lambda [x] E]


## 最简单的程序语言的解释器

### x

```
Interpret(x) = Value of x
```

解释变量的过程的就是读变量的值

```
Interpret(x, env) = Value of x in env
```

变量是有作用域(scope)的，解释过程需要传入变量名(name)与环境(env)


### [lambda [x] E]

```
Interpret([lambda [x] E], env) = λp.Interpret(E, withvar(env, x=p))
```

**函数抽象**或者说**lambda**语义实际上是指可以被调用的东西

当lambda被调动时，会建立一个新的作用域 withvar(env, x=p)，变量x关联到实数p上，

然后在新的作用域中解释表达式E

此处作用域是指 传统词法作用域(静态作用域)

(E:body，x:parameter p:argument)

如果是动态作用域.....

```
https://www.zhihu.com/question/20032419/answer/49183240
动态作用域: 程序运行只有一个env
env: 一组binding
binding: identifer到value的映射
dynamic scope: 每次函数求值的时都会在这唯一的一个env里查询或更新
static scope: 每次函数求值的时都创建一个新的env，包含了函数定义时候的所能访问到的各种binding
这个新的env连同那个函数一起，俗称闭包Closure
```


### [F X]

```
Interpret([F X], env) = 
    let f be Interpret(F, env) 
    let x be Interpret(X, env)
    Return f(x)
```

解释函数调用的过程实际上是递归定义的，先解释F得到f，再解释X得到x，在进行f(x)

两个子表达式的顺序完全是由let来表达的

函数式语言中，let本质上是调用，

可以将上述展开：

```
Interpret([F X], env) = 
    Pass Interpret(F, env) into λf.
        Pass Interpret(X, env) into λx.
            Return Invoke(f,x)
```

let即调用，展开成两个相互嵌套的函数, F与X的解释顺序是由嵌套函数的层次决定的


以下是 continuation pass style 的 一个 continuation

```
                                λf.
        Pass Interpret(X, env) into λx.
            Return Invoke(f,x)
```

### 

利用continuation的嵌套顺序来表达lambda解释的先后顺序

从解释器到代码生成语义、抽象解释的关键步骤：

把continuation做成Interpret的一个参数，解释器return的步骤变成对continuation的调用


```
Interpret([F X], env, k) = 
    Interpret(F, env, λf.
        Interpret(X, env, λx.
            k(f(x))))
```

此时 Interpret函数实际上变成了，将程序运行结果传递给continuation的过程

Program, env -> Continuation k

用同样的方法改造对**单个变量**与**lambda抽象**的解释之后会得到如下形式解释器

```
Interpret(x, env, k) = k(seek(env, x))

Interpret([lambda [x] E], env, k) = k(
    λp.Return Interpret(E, withvar(env,x=p), λx.x))

Interpret([F X], env, k) = 
    Interpret(F, env, λf.
        Interpret(X, env, λx.
            k(f(x))))
```

id = λx.x 

发现Interpret([lambda [x] E]...中还有Return

```
Interpret(x, env, k) = k(seek(env, x))

# 修改lambda表达式定义，一元函数变为两元，添加k'参数
Interpret([lambda [x] E], env, k) = k(
    λ(p, k').Interpret(E, withvar(env,x=p), k')) # 注意此处 k'

# 同时修改函数调用，解释调用时传入k
Interpret([F X], env, k) = 
    Interpret(F, env, λf.
        Interpret(X, env, λx.
            f(x, k)))         # 注意此处k
```

这种模式实际上就是：Continuation-Pass-Style


### 

```
function(x) { return x }
function(x, k) { k(x) }
```

传统编程语言中，return语句就是一个永远不会返回的函数

完全可以把return全部用continuation k来代替

由于continuation k永远不会返回， 因此这种替代是安全的


综合起来：

Interpret(c, e, k)，通过三个参数返回解释的结果

1. C: **Control** 程序本身
2. E: **Environment** 程序所在的环境
3. K: **Kontinuation** 具体continuation

(k 估计是因为发音的原因被用来表示continuation, 同时为了和controle的c区分)


CEK三大件，抽象机器模型

CEK能做到的不仅仅是解释一个程序得到他的值而已

解释是从一个程序返回它的值，然后有时候我们关注的并不是程序的值，比如说获取程序其他方面的信息，变量的类型，变量的取值范围等

尽管解释程序可以，但是完整的解释成本是非常高昂的，所以不能这么做

那么能否对解释器做一些修改，让他获得我们想要的信息呢？

**抽象解释**

我们只需要明确，程序的语义不仅仅是唯一的，通过不同语义定义的一段程序，可以得到这段程序在不同侧面的结果，这就是抽象解释

抽象解释的本质就是对程序进行部分执行，从而获得它在语义上的信息；比如说控制流，类型等等；

得到这些信息，从而对程序进行变换，从而得到有意义的结果


### 

```
cps(x, env, k) = k(x)


cps([lambda [x] E], env, k) = k(
    [lambda [x' k'] cps(E, withvar(env, x), λv.[k'v])]

cps([F X], env, k) =
    cps(F, env, λf.[let [tf f]
        cps(X, env, λx.[let [tx x]
            [tf tx [lambda [a] k(a)]]])])
```


基于CEK框架的抽象解释