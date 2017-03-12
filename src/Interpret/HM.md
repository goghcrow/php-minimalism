# Hindley-Milner类型系统

α	β	γ	δ	ε	ζ	η	θ	ι	κ	λ	μ	ν	ξ	ο  π	 ρ	ς	σ	τ	υ	φ	χ	ψ	ω

## 语法构造

HM类型系统 一切语法构造都是表达式 

e =  χ
    | e1 e2
    | λχ.e
    | let χ = e1 in e2

变量：χ
函数调用：e1 e2
函数定义：λχ.e
let绑定：let χ = e1 in e2

## 类型

τ = α
   | ι
   | τ -> τ

σ = τ
   | ∀α.σ

∀ forall

τ 类型
原生类型：ι
类型变量：α
函数类型：τ -> τ

而 σ 被称做type scheme。它即可以是一个 τ，即type；也可以是一个 ∀α.σ。而后者，不是type。

之所以说 ∀α.σ 不是type，是因为它事实上代表的是一组type的集合。比如：

```
id :: ∀ a. a -> a
id x = x
```

如果没有 ∀ 的存在，程序员就必须为每种type都要定义一个特定版本的id函数。比如:

```
id_Int :: Int -> Int
id_Int x = x
id_Bool :: Bool -> Bool
id_Bool x = x
id_F :: (Int -> Bool) -> (Int -> Bool)
id_F x = x
-- a lot more others ...
```


为了理解的语义，我们可以观察下面的两个表达式，然后就会发现它们的相似性: 
```
∀α . α -> α
λχ . χ -> χ
```
你猜的没错， ∀正是类型系统里的 λ 。

