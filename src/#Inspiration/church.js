// http://matt.might.net/articles/js-church/

/*
 exp ::= variable                    [references]
      |  exp(exp)                    [function application]
      |  function (variable) exp     [anonymous functions]
      |  (exp)                       [precedence]
*/
// church encoding
// 使用最小语言子集
// 变量引用, 函数调用, 单参匿名函数

// ## 1. curry
// 创建多参数函数
const curry = f => x => y => f (x, y)
const uncurry = f => (x, y) => f (x) (y)

// ## 2. void value
const VOID = x => x


// Encoding data as computation
// To add data structures to the language, we need encode them as functions.
// Thus, the key is to encode data according to how it will be used.

// ## 3. Booleans and conditionals
// Booleans should be encoding as branching.

// 根据true与false如何使用来创造true与false
// 作为test, 接受onTrue 与 onFalse 两个参数
const TRUE = onTrue => onFalse => onTrue (VOID)
const FALSE = onTrue => onFalse => onFalse (VOID)

// IF becomes
const IF = test => onTrue => onFalse => test(onTrue)(onFalse)

// TEST
// console.log( IF(TRUE)(_ => true)(_ => false) )

// convert Church Booleans back into JavaScript Booleans at the end of a computation
const boolify = churchBoolean => churchBoolean (_ => true) (_ => false)

// TEST
// console.log( boolify(TRUE) )
// console.log( boolify(FALSE))

// ## 4. Numerals
// There are several reasonable ways to encode numerals in the lambda calculus
// ---because there are several ways to use numbers.
// One of the most common uses for numbers is iteration---to peform a computation n times.
// Church numerals take this iterative interpretation.
// A Church numeral takes a function f and zero element z upon which to iterate the application of that function.
// That is, the nth Church numeral computes fn(z), where fn is iterated function application:
// f 0 (x) = x
// f i (x) = f(f i-1 (x))
// If we allow ourselves to cheat, we can write a function that creates the nth Church numeral:
const numeral = n => f => z => {
    for (var i = 0; i < n; i++)
        z = f(z)
    return z
}
// TODO
// const numeral = n => f => z => [...Array(n).keys()].reduce(z => f(z), z)

const ZERO = f => z => z
// And, we can create SUCC, a function that adds one to a Church numeral,
// by adding one more round of iteration:
const SUCC = n => f => z => f (n (f) (z))
const ONE = SUCC (ZERO)
const TWO = SUCC (ONE)

// To extract Church numerals at the end of a computation, we execute them:
ZERO (x => x + 1) (0) === 0;
(SUCC (SUCC (ZERO))) (x => x + 1) (0) === 2

// Or, to make things more convenient, we can write a function to do this:
const numerify = n => n (x => x + 1) (0)

// Remarkably, we can perform the standard arithmetic operations directly in Church numeral form:

const PLUS = n => m => f => z => n (f) (m (f) (z))
const MULT = n => m => f => z => n (m (f)) (z)
// Substract 1:
// TODO
const PRED = n => f => z => ( (n (g => h => h(g(f))) ) (u => z) ) (u => u)
const SUB = n => m => (m (PRED)) (n)

// And, we can create a Church predicate that checks for 0:
const ZEROP = n => n (_ => FALSE) (TRUE)

// TEST
const FOUR = numeral(4)
const SIX = numeral(6)
numerify(PLUS (FOUR) (SIX)) === 10
numerify(MULT (FOUR) (SIX)) === 24
// TODO console.log(numerify(SUB (FOUR) (SIX)))
numerify(SUB (SIX) (FOUR)) === 2



// 数据是靠Closure的env保存的
// ## 5. Lists
// there are several ways to encode lists.

// The empty list:
const NIL = onEmpty => onPair => onEmpty(VOID)
// Construct a new list:
const CONS = hd => tl => onEmpty => onPair => onPair(hd)(tl)
// Get the head of a list: onPair操作是 传入hd tl 取出hd
const HEAD = list => list(VOID)(hd => tl => hd)
// Get the tail of a list:
const TAIL = list => list(VOID)(hd => tl => tl)
// A predicate to test for the empty list:
const NILP = list => list(_ => TRUE)(_ => _ => FALSE)

// TEST
boolify(NILP (NIL)) === true
boolify(NILP (CONS (VOID) (VOID))) === false
var list = CONS (ZERO) (CONS (ONE) (NIL))
numerify(HEAD (list)) === 0
numerify(HEAD (TAIL (list) )) === 1
boolify(TAIL (TAIL (list))) === true;



// ## 6. Let-binding
// js 缺少词法作用域, 使用立即执行函数模拟
/*
{
    var x = 10
    {
        var x = 3
        console.log(x)
    }
    console.log(x)
}
*/
// (x => (x => console.log(x)) (3))(10)


// ## 7. The U combinaitor
// The Y combinator gets a lot of attention as the way to do recursion in the lambda calculus.
// But, it's not the only way to do it.
// The U combinator is much simpler, and it gets the job done too.
// To get a sense of how it works, let's examine the smallest non-terminating program:

// Maximum call stack size exceeded
// (f => f (f)) (f => f (f))

// This program, known as Ω, is applying the same function to itself, indefinitely.

// If we factor out the function that applies its argument to itself as U,
// it's much easier to write Ω:
const U = f => f (f)
// U(U) // Never terminates (or it stack overflows)

// Self-application of a function to itself creates a way to get a handle on that function.
// Once a function can get a handle on itself, recursion is easy.
// For example, here's a self-contained non-recursive expression that computes factorial:
// TODO
// U(h => n => n  <= 1 ? 1 : n * (h(h)) (n-1))
// When a function wrapped in self-application needs to generate another instance of itself,
// it uses self-application once more
// --hence the internal call to h(h) where you would expect to see fact.
U(h => n => n  <= 1 ? 1 : n * (h(h)) (n-1)) (5) === 120


// ## 8. The Y combinator
// The Y combinator is the more popular way to realize recursion in the lambda-calculus.
// The short version is that the Y combinator computes the fixed point of a functional
// -- a function that consumes (and in this case, produces) another function.
// The trick is to define recursive functions as the fixed points of non-recursive functions,
// and then to write a fixed-point finder -- the Y combinator -- without using recursion.
// For a function f, x is a fixed point of f if x = f(x).
// var F = f => n => n <= 1 ? 1 : n * f (n-1)

//var Y = F => F (x => Y(F)(x))
//const yfact = Y (f => n => n <= 1 ? 1 : n * f (n-1))
//yfact(5) === 120

const Y = U (f => F => F (x => f (f)(F)(x)))
// var Y = (f => F => x => f (f)(F)(x)) (f => F => x => f (f)(F)(x))


// ## 9. Example
const CFACT = Y (fact => n => IF (ZEROP (n)) (_ => ONE) (_ => MULT (n) (fact (PRED (n)))) )

console.log(numerify( CFACT (numeral(5)) ))