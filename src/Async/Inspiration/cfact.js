const VOID = x => x
const TRUE = onTrue => onFalse => onTrue (VOID)
const FALSE = onTrue => onFalse => onFalse (VOID)
const IF = test => onTrue => onFalse => test(onTrue)(onFalse)


const ZERO = f => z => z
const SUCC = n => f => z => f (n (f) (z))
const ONE = SUCC (ZERO)
const PRED = n => f => z => ( (n (g => h => h(g(f))) ) (u => z) ) (u => u)
const PLUS = n => m => f => z => n (f) (m (f) (z))
const MULT = n => m => f => z => n (m (f)) (z)
const SUB = n => m => (m (PRED)) (n)
const ZEROP = n => n (_ => FALSE) (TRUE)

const U = f => f (f)
const Y = U (f => F => F (x => f (f)(F)(x)))

const CFACT = Y (fact => n => IF (ZEROP (n)) (_ => ONE) (_ => MULT (n) (fact (PRED (n)))) )







const numeral = n => f => z => [...Array(n).keys()].reduce(z => f(z), z)
// church number to number
const numerify = n => n (x => x + 1 /*+1*/) (0 /*zero*/)
//console.log(numerify( CFACT (numeral(5)) ))



// data struct
const NIL = onEmpty => onPair => onEmpty(VOID)
const CONS = hd => tl => onEmpty => onPair => onPair(hd)(tl)
const HEAD = list => list(VOID)(hd => tl => hd)
const TAIL = list => list(VOID)(hd => tl => tl)
const NILP = list => list(_ => TRUE)(_ => _ => FALSE)




var fact = function(n) {
    if (n <= 1) {
        return 1;
    } else {
        return n * fact(n - 1);
    }
};


(function (fact) {
    return function(n) {
        if (n <= 1) {
            return 1;
        } else {
            return n * fact(fact)(n - 1);
        }
    }
}) (function (fact) {
    return function(n) {
        if (n <= 1) {
            return 1;
        } else {
            return n * fact(fact)(n - 1);
        }
    }
}) (5);