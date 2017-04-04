这篇文章是来解释某 PL 界巨擘引以为豪的 40 篇代码的来龙去脉的。

Fischer 和 Plotkin 奠基性的文章里，CPS 的结果语句是非常冗繁的。它们的方法会把表达式

lambda f. lambda x. lambda y. (f~y)~xλf. λx. λy.(f y) x
变成

lambda k. k "(" lambda f. lambda k. k "(" lambda x. lambda k. k "(" lambda y. lambda k. (lambda k. (lambda k. k~f) (lambda m. (lambda k. k~y) (lambda n. (m~n)~k))) (lambda m. (lambda k. k~x) (lambda n. (m~n)~k)) ")" ")" ")"λk. k(λf. λk. k(λx. λk. k(λy. λk.(λk.(λk. k f)(λm.(λk. k y)(λn.(m n) k)))(λm.(λk. k x)(λn.(m n) k)))))
这显然过于冗繁。尽管一些简化——比如betaβ规约——可以消除冗余的东西，但这种在变换后的规约也会抹掉 CPS 的痕迹。因此，最合适的方法是在变换的时候一步完成规约，来简化最终代码同时保持 CPS 的特质。

下面是 Fischer 与 Plotkin 的原版 CPS（函数调用用中点cdot⋅表示）：

lBrack x rBrack⟦x⟧	==	lambda^1 kappa. kappa cdot^1 xλ{1}κ. κ ⋅{1}x
lBrack {lambda x.M} rBrack⟦λx. M⟧	==	lambda^2 kappa. kappa cdot^2 (lambda^3 x. lBrack M rBrack)λ{2}κ. κ ⋅{2}(λ{3}x. ⟦M⟧)
lBrack {M cdot N} rBrack⟦M ⋅ N⟧	==	lambda^4 kappa. lBrack M rBrack cdot^3 (lambda^5 m. lBrack N rBrack cdot^4 (lambda^6 n. (m cdot^6 n) cdot^5 kappa))λ{4}κ. ⟦M⟧ ⋅{3}(λ{5}m. ⟦N⟧ ⋅{4}(λ{6}n.(m ⋅{6}n) ⋅{5}κ))
我们把这里所有的lambdaλ和cdot⋅都编号以进行分析。

第一步(#)

问题 1：lambda^1λ{1}、lambda^2λ{2}、lambda^4λ{4}这三个lambdaλ抽象能否在后期规约中消除掉？ lambda^1λ{1}、lambda^2λ{2}、lambda^4λ{4}可能出现在三个地方：lambda^3λ{3}内部、cdot^3⋅{3}前项、cdot^4⋅{4}前项。后两种情况里 CPS 变换会构造出两个可约项（Redex），它们会被很快消除；然而对于第一种情况，就没有那么简单了。结论：lambda^1λ{1}、lambda^2λ{2}和lambda^4λ{4}能否被消除取决于其所在的环境。

问题 2：那么这种环境依赖能够绕开吗？ 可以。通过向lambda^3λ{3}引入一个etaη可约项就可以了。上述表达式中第二条将会变为：

lBrack {lambda x.M} rBrack⟦λx. M⟧	==	lambda^2 kappa. kappa cdot^2 (lambda^3 x. lambda^7 k. lBrack M rBrack cdot^7 k)λ{2}κ. κ ⋅{2}(λ{3}x. λ{7}k. ⟦M⟧ ⋅{7}k)
由于lBrack M rBrack⟦M⟧总是一个lambdaλ抽象，上述etaη变换是安全的。

因此：

问题 3：现在lambda^1λ{1}、lambda^2λ{2}、lambda^4λ{4}这三个lambdaλ抽象能否在后期规约中消除掉？ 可以。现在它们是cdot^3⋅{3}、cdot^4⋅{4}、cdot^7⋅{7}的前项，都会构造出betaβ可约项，因此可以被消除。

第二步(#)

在进行第一步的修改之后我们发现了三处总是被消除掉的lambdaλ抽象，现在来更细致地分析它们。

问题 4：传入lambda^1λ{1}、lambda^2λ{2}、lambda^4λ{4}的参数kappaκ是什么？ 继续看三种情况cdot^3⋅{3}、cdot^4⋅{4}、cdot^7⋅{7}。在前两种情况里传入的是一个lambdaλ抽象，但是第三种情况中，传入的是符号kk。于是，再一次地，出现了不一致的情况。

问题 5：这种不一致性可以消除吗？ 可以。像问题 2 里那样再次做一次etaη膨胀，可以得到：

lBrack {lambda x.M} rBrack⟦λx. M⟧	==	lambda^2 kappa. kappa cdot^2 (lambda^3 x. lambda^7 k. lBrack M rBrack cdot^7 (lambda^8 m. k cdot^8 m))λ{2}κ. κ ⋅{2}(λ{3}x. λ{7}k. ⟦M⟧ ⋅{7}(λ{8}m. k ⋅{8}m))
然后重复问题 4：

问题 6：传入lambda^1λ{1}、lambda^2λ{2}、lambda^4λ{4}的参数kappaκ是什么？ lambda^5λ{5}、lambda^6λ{6}和lambda^8λ{8}，都是lambdaλ抽象。也就是说，kappaκ将永远是lambdaλ抽象，不会是别的。

第三步(#)

问题 7：kappaκ会出现在哪里？ 三个位置：cdot^1⋅{1}的前项、cdot^2⋅{2}的前项，与cdot^5⋅{5}的后项。从问题 6 的结论可知，cdot^1⋅{1}和cdot^2⋅{2}总会构造出betaβ可约项，而cdot^5⋅{5}就没有那么明显的规律了。我们又遇到了问题。

问题 8：那这个问题可以消除吗？ 也可以。用同样的方法，将变换 3 改写为

lBrack {M cdot N} rBrack⟦M ⋅ N⟧	==	lambda^4 kappa. lBrack M rBrack cdot^3 (lambda^5 m. lBrack N rBrack cdot^4 (lambda^6 n. (m cdot^6 n) cdot^5 (lambda^9 a. kappa cdot^9 a)))λ{4}κ. ⟦M⟧ ⋅{3}(λ{5}m. ⟦N⟧ ⋅{4}(λ{6}n.(m ⋅{6}n) ⋅{5}(λ{9}a. κ ⋅{9}a)))
问题 9：那么，现在kappaκ会出现在哪里？ 三个位置：cdot^1⋅{1}的前项、cdot^2⋅{2}的前项，与cdot^9⋅{9}的前项。从问题 6 的结论可知，cdot^1⋅{1}、cdot^2⋅{2}和cdot^9⋅{9}都会构造出betaβ可约项，故对kappaκ的调用也是完全可消除的。

结论(#)

CPS 变换引入的六个lambdaλ和六个调用（cdot⋅）都是可消除的，我们用红色标注它们：

lBrack x rBrack⟦x⟧	==	{lambda^1} red kappa. kappa {cdot^1} red xλ{1}κ. κ ⋅{1}x
lBrack {lambda x.M} rBrack⟦λx. M⟧	==	{lambda^2} red kappa. kappa {cdot^2} red (lambda^3 x. lambda^7 k. lBrack M rBrack {cdot^7} red ({lambda^8} red m. k cdot^8 m))λ{2}κ. κ ⋅{2}(λ{3}x. λ{7}k. ⟦M⟧ ⋅{7}(λ{8}m. k ⋅{8}m))
lBrack {M cdot N} rBrack⟦M ⋅ N⟧	==	{lambda^4} red kappa. lBrack M rBrack {cdot^3} red ({lambda^5} red m. lBrack N rBrack {cdot^4} red ({lambda^6} red n. (m cdot^6 n) cdot^5 (lambda^9 a. kappa {cdot^9} red a)))λ{4}κ. ⟦M⟧ ⋅{3}(λ{5}m. ⟦N⟧ ⋅{4}(λ{6}n.(m ⋅{6}n) ⋅{5}(λ{9}a. κ ⋅{9}a)))
使用lBrack M rBrack {cdot} red ({lambda} red x. x)⟦M⟧ ⋅ (λx. x)形式的调用之后，不会留下任何红色的lambdaλ和cdot⋅，黑色的lambdaλ与cdot⋅则会出现在结果中。只考虑红色符号的话，我们可以把lBrack … rBrack⟦…⟧看成类型为syntax -> (syntax -> syntax) -> syntaxsyntax → (syntax → syntax) → syntax的函数。可以证明，如下形式的表达式：

lambda k. lBrack M rBrack {cdot} red ({lambda} red m. k cdot m)λk. ⟦M⟧ ⋅ (λm. k ⋅ m)
所产生的「黑色」结果与 Fischer 和 Plotkin 的lBrack M rBrack⟦M⟧的结果beta etaβη等价。

将上面的「黑色」符号写成 Quasiquote，红色写成 Lambda 的话，你就写出了最简单的 CPS 变换器了。

尾递归的处理(#)

差点忘记说尾递归了。其实想处理尾递归并不困难，做法是，引入一个特殊的符号（蓝色{k_{return rm} blue}k{return}），以及一套转门处理它的变换lBrack M rBrack prime⟦M⟧′，区别对待下就可以了。

lBrack x rBrack⟦x⟧	==	{lambda} red kappa. kappa {cdot} red xλκ. κ ⋅ x
lBrack {lambda x.M} rBrack⟦λx. M⟧	==	{lambda} red kappa. kappa {cdot} red (lambda x. lambda k_{return rm} blue. lBrack M rBrack prime {cdot} red k_{return rm} blue)λκ. κ ⋅ (λx. λk{return}. ⟦M⟧′ ⋅ k{return})
lBrack {M cdot N} rBrack⟦M ⋅ N⟧	==	{lambda} red kappa. lBrack M rBrack {cdot} red ({lambda} red m. lBrack N rBrack {cdot} red ({lambda} red n. (m cdot n) cdot (lambda a. kappa {cdot} red a)))λκ. ⟦M⟧ ⋅ (λm. ⟦N⟧ ⋅ (λn.(m ⋅ n) ⋅ (λa. κ ⋅ a)))
lBrack x rBrack prime⟦x⟧′	==	{lambda} red k_{return rm} blue. k_{return rm} blue cdot xλk{return}. k{return} ⋅ x
lBrack {lambda x.M} rBrack prime⟦λx. M⟧′	==	{lambda} red k_{return rm} blue. k_{return rm} blue cdot (lambda x. lambda k_{return rm} blue. lBrack M rBrack prime {cdot} red k_{return rm} blue)λk{return}. k{return} ⋅ (λx. λk{return}. ⟦M⟧′ ⋅ k{return})
lBrack {M cdot N} rBrack prime⟦M ⋅ N⟧′	==	{lambda} red k_{return rm} blue. lBrack M rBrack {cdot} red ({lambda} red m. lBrack N rBrack {cdot} red ({lambda} red n. (m cdot n) cdot k_{return rm} blue))λk{return}. ⟦M⟧ ⋅ (λm. ⟦N⟧ ⋅ (λn.(m ⋅ n) ⋅ k{return}))