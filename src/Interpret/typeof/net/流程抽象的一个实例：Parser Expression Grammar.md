流程抽象是近年程序界的热门话题。随着匿名函数渐渐被大众接受，针对控制流的抽象已经不算稀奇玩意。我们很容易写出这样的小东西：

function iif(condition, consequent, alternate){
	if(condition) return consequent()
	else return alternate()
}
不过人们很少知道控制流抽象的真正威力。我下面要分析的就是控制流抽象领域一个很有意义的应用：Parser Expression Grammar。

PEG 本质上是将递归下降分析器（Recursive descent parser）中控制流的一般模式抽象出来，然后用近似 BNF 的方法表示之。一般的递归下降分析器中，大量出现的模式无非是：

检测文本的某处是否是某个字符串（终结规则）
尝试解析一个规则，在其成功时尝试解出下一个规则（顺序组合）
尝试解析一个规则，如果失败，则尝试解析另一个规则（有序并）
而这三者的「接口」是一致的，都是一个表示解析状态的对象，解析成功时的回调，以及解析失败时的回调，这是因为在你人肉写 parser 的时候这种「尝试匹配」的形式是这样：

if((result = tryParseRule(state)) != FAILURE) {
	// <deal with result>
} else {
	// <deal with failure>
}
将它内部的代码块用回调表示之后就有这种接口：

function someRule(state, onmatch, onfail){
	......
}
现在我们就来将三种规则实现出来。「终结规则」是最简单的，代码如下：

function match(rule, state, onmatch, onfail){
	if(rule instanceof FinalRule) {
		if(!rule.pattern)
			return onmatch(state, '')
		else if(text.slice(0, rule.pattern.length) === pattern) 
			return onmatch(text.slice(rule.pattern.length), pattern)
		else 
			return onfail("Expected " + rule.pattern)			
	}
}
从这里可以看出，表示解析状态的state变量就是待分析文字的后缀，当解析终结符时，如果它开头是终结符模式则成功，否则失败。

接下来的规则是实现两个规则的顺序组合，对应 BNF 中的顺接。

function match(rule, state, onmatch, onfail){
	// ...
	if(rule instanceof SequenceRule) {
		return match(rule.front, state, function(state1, result1) {
			return match(rule.rear, state1, function(state2, result2) {
				return onmatch(state2, [result1, result2])
			}, onfail)
		}, onfail)
	}
	// ...
}
以及两个规则的有序并

function match(rule, state, onmatch, onfail){
	// ...
	if(rule instanceof ChooseRule) {
		return match(rule.superior, state, onmatch, function() {
			return match(rule.inferior, state, onmatch, onfail)
		})
	}
	// ...
}
好了，一个最简单的 Parser Generator 就完工了。你没看错，它确实是一个 Parser Generator，支持任意非左递归的文法，虽然速度很慢（因为没有使用预测分析，大量依赖回溯）。这个只有几十行的match提炼出了递归下降分析器中重复出现的流程，去掉重复之后的东西非常精简，但是能代表原来冗长的分析器的功能。你可以只用两行来构建一个匹配嵌套括弧的分析器：

var brackets = Choose(Seq('(', Seq(null, ')')), '')
brackets.superior.rear.front = brackets; // 递归
match(brackets, "((()))", function(s, result){ console.log(result) }, console.log)
而且，这两行的形式和 BNF 非常相似（除了收到赋值的制约，不能直接定义递归规则），这就是 Parser Expression Grammar。由于 PEG 本身是从递归下降分析器提炼得到，因此各种扩展的分析器都有对应的 PEG 文法（比如你可以用 PEG 定义两个文法的交集），PEG 的功能可以说异常强大。

而一切的起点只不过是match而已。