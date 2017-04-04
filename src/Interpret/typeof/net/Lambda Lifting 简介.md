Lambda Lifting 是函数式语言中一种相当高级的优化技术。简而言之，它可以减少函数的嵌套次数。

先为了简单起见，我们看个简单的例子。下面的函数

var addfive = function(n) {
	var x = 5
	var f = function(y) {
		return x + y
	}
	return f(n)
}
现在「f」是函数addfive的嵌套函数，显然，每次调用addfive都要创建一个闭包，这很不利于优化。为了解决这个问题，首先要分析f引用的自由变量（Free Variable）。在这个例子中，f的自由变量是x——因为它是在addfive里声明的。

接下来，给f加入一个新的参数x_2。因为f里没有对自由变量f的写入，所以替换是安全的。同时，每次调用f时都必须增加一个新的参数。

var addfive = function(n) {
	var x = 5
	var f_2 = function(x_2, y) {
		return x_2 + y
	}
	return f_2(x, n)
}
现在函数f_2已经没有引用自由变量了，放心地把它移动到外面吧！

var f_2 = function(x_2, y) {
	return x_2 + y
}
var addfive = function(n) {
	var x = 5
	return f_2(x, n)
}
到这里为止 Lambda Lifting 就完成了。当然你可以继续优化。应用 β 替换（别名：函数内联）可以把它优化到极限：

var addfive = function(n) {
	return n + 5
}
然而 Lambda Lifting 实际上不是个简单的工作。我们考虑函数foo：

var foo = function(n, x, y){
	var f = function(n_2){ 
		if(n_2 === 0) return x
		else return g(n_2 - 1)
	}
	var g = function(n_3){
		if(n_3 === 0) return y
		else return f(n_3 - 1)
	}
	return f(n)
}
按照惯常的思路，f引用了自由变量x和g，因此需要添加两个参数：

var f_4 = function(g_4, x_4, n_2){ 
	if(n_2 === 0) return x_4
	else return g_4(n_2 - 1)
}	
var foo = function(n, x, y){
	var g = function(n_3){
		if(n_3 === 0) return y
		else return f_4(g, x, n_3 - 1)
	}
	return f_4(g, x, n)
}
然而在希望外提g的时候却出了点问题。如果按照一般的办法再次外提g，这段程序就出了问题：

var f_4 = function(g_4, x_4, n_2){ 
	if(n_2 === 0) return x_4
	else return g_4(f_4, x_4, ???, n_2 - 1)
}	
var g_5 = function(f_5, x_5, y_5, n_3){
	if(n_3 === 0) return y_5
	else return f_5(g_5, x_5, n_3 - 1)
}
var foo = function(n, x, y){
	return f_4(g_5, x, n)
}
因为g有引用自由变量y，而刚才f给外提成f_4了，位于自由变量y所在作用域的外面 ，因此f现在没法给出什么东西来满足外提出来的g_5的参数y_5。这就告诫我们：Lambda Lifting 必须整体考虑，f和g要一次性地外提完成。在函数foo中f和g因为会相互调用，他们引用的自由变量就必须整体考虑。一个正确的 Lambda Lifting 结果是这样的：

var f_4 = function(g_4, x_4, y_4, n_2){
	if(n_2 === 0) return x_4
	else return g_4(x_4, y_4, n_2 - 1)
}
var g_4 = function(x_4, y_4, n_3){
	if(n_3 === 0) return y_4
	else return f_4(g_4, x_4, y_4, n_3 - 1)
}
var foo = function(n, x, y){
	return f_4(g_4, x, y, n)
}
那些嵌套函数所需要增加的变量名一个方程组定义。设V为我们感兴趣的变量名集，A_i为每个函数所需添加的变量，FV rm (f_i)为函数f_i引用的自由变量集，则A_i满足方程组：

A_i = (FV rm (f_i) intersect V) union (Union __ {f_i ~ "调用了" rm ~ f_j} ~ A_j)
解这个方程组的时间复杂度是O rm (m^3 {|V|}^2)，其中m是需要进行 Lambda Lifting 的函数总数。

Lambda Lifting 在那些高速的 Scheme、Haskell 等语言中是个很常用的技术，但是它现在仍然鲜见于动态语言中，这可能是动态语言的「运动性」太多的问题所致吧。