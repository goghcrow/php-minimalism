(define length 
  (lambda (ls)
    (if (null? ls) 
      0
      (+ 1 (length (cdr ls))))))

; First, notice we can't really define a recursive function without binding it to name
; Second, answer this question:
;   "where we can bind something to a name?"
; The answer is: λx.t    x
; Lambda, the ultimate binder

(lambda (length)
  (lambda (ls)
    (if (null? ls) 
      0
      (+ 1 (length (cdr ls))))))


; Copy
((lambda (length)
  (lambda (ls)
    (if (null? ls) 
      0
      (+ 1 (length (cdr ls))))))
(lambda (length)
  (lambda (ls)
    (if (null? ls) 
      0
      (+ 1 (length (cdr ls)))))))


; Small fix
; self-application
((lambda (length)
  (lambda (ls)
    (if (null? ls) 
      0
      (+ 1 ((length length) (cdr ls))))))
(lambda (length)
  (lambda (ls)
    (if (null? ls) 
      0
      (+ 1 ((length length) (cdr ls)))))))


;Abstract Outer Self-application
;(length length)
;Now we have only on self-application left(why not two?)
((lambda (u) (u u))
  (lambda (length)
    (lambda (ls)
      (if (null? ls) 
        0
        (+ 1 ((length length) (cdr ls)))))))




; Abstract Inner Self-application
; Done in a very similar way as the outer one.
; We may call it "factor out"
; ((lambda (u) (u u))
;   (lambda (length)
;     ; Notice that this part is exactly the defination of ""length
;     ; (module alpha-equivalence)
;     ((lambda (g)
;       (lambda (ls)
;         (if (null? ls) 
;           0
;           (+ 1 (g (cdr ls))))))
;     ;;;;;;;;;;;;;;;;;;;;;;;;;;
;       (length length)))) ; Non-termination call-by-value


; Eta-expansion
; Eta-expansion will prevent the non-termination while preserving the semantics
; (length length)
; (lambda (v) ((length length) v))
((lambda (u) (u u))
  (lambda (length)
    ((lambda (g)
      (lambda (ls)
        (if (null? ls) 
          0
          (+ 1 (g (cdr ls))))))
      (lambda (v) ((length length) v)))))


; Abstract out the function
; Now we can factor out the function "length"
; Notice that we can now substitute f for any function
; and get recursive definition!
(lambda (f) 
    ((lambda (u) (u u))
      (lambda (length)
        (f
        #|
        (lambda (g)
          (lambda (ls)
            (if (null? ls) 
              0
              (+ 1 (g (cdr ls))))))
        |#
          (lambda (v) ((length length) v))))))
; This is Y combinator!

; Renaming
; name "length" obvisously does not matter, renaming it
(lambda (f) 
  ((lambda (u) (u u))
    (lambda (x)
      (f
        (lambda (v) ((x x) v))))))

; Expanding
; If you woule like self-application expanded out, this is just another form
(lambda (f) 
  ((lambda (x) (f (lambda (v) ((x x) v))))
   (lambda (x) (f (lambda (v) ((x x) v))))))



; CBV and CBN
; Y-combinator (call-by-value)
(lambda (f) 
  ((lambda (x) (f (lambda (v) ((x x) v))))
   (lambda (x) (f (lambda (v) ((x x) v))))))
; Y-combinator (call-by-name)
(lambda (f) 
  ((lambda (x) (f (x x)))
   (lambda (x) (f (x x)))))


; Test(length)
(((lambda (f) 
  ((lambda (x) (f (lambda (v) ((x x) v))))
   (lambda (x) (f (lambda (v) ((x x) v))))))
  (lambda (length)
  (lambda (ls)
    (if (null? ls) 
      0
      (+ 1 (length (cdr ls))))))) 
  '(a b c))


; Test(factorial)
(((lambda (f) 
  ((lambda (x) (f (lambda (v) ((x x) v))))
   (lambda (x) (f (lambda (v) ((x x) v))))))
  (lambda (fact)
  (lambda (n)
    (if (zero? n) 
      1
      (* n (fact (- n 1))) )))  ) 
  5)




;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
(define Y
  (lambda (f) 
    ((lambda (x) (f (lambda (v) ((x x) v))))
     (lambda (x) (f (lambda (v) ((x x) v)))))))

((Y (lambda (fact)
     (lambda (n)
        (if (zero? n)
            1
            (* n (fact (- n 1)))))))
 5)
