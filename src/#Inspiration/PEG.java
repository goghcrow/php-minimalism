//package com.youzan.zanphp;


import java.util.Arrays;
import java.util.Objects;
import java.util.function.Consumer;

// 限制 无法处理左递归 !!!!!
public class PEG {
    private Rule rule;

    public PEG(Rule rule) {
        this.rule = rule;
    }

    public class Pair {
        public Object car;
        public Object cdr;

        Pair(Object car, Object cdr) {
            this.car = car;
            this.cdr = cdr;
        }

        @Override
        public String toString() {
            return String.format("%s, %s", car, cdr == null ? "null" : cdr.toString());
        }
    }

    private static class MatchResult {
        boolean matched;
        Object value;
        String newState;
    }

    @FunctionalInterface
    private interface OnMatch {
        void apply(String value, Object result);
    }

    static class Rule { }

    public static class SequenceRule extends Rule {
        public Rule front;
        public Rule rear;
    }

    public static class ChooseRule extends Rule {
        public Rule superior;
        public Rule inferior;
    }

    public static class FinalRule extends Rule {
        public String pattern;

        MatchResult match(String state) {
            MatchResult ret = new MatchResult();
            int len = pattern.length();
            if (state.length() >= len && state.substring(0, len).equals(pattern)) {
                ret.matched = true;
                ret.newState = state.substring(len);
                ret.value = pattern;
            } else {
                ret.matched = false;
                ret.value = "Expected " + pattern + ", but got " + state;
            }
            return ret;
        }
    }

    public static FinalRule sym(String pattern) {
        FinalRule fRule = new FinalRule();
        fRule.pattern = pattern;
        return fRule;
    }

    public static SequenceRule seq(Rule front, Rule rear) {
        SequenceRule seq = new SequenceRule();
        seq.front = front;
        seq.rear = rear;
        return seq;
    }

    public static ChooseRule choose(Rule superior, Rule inferior) {
        ChooseRule choose = new ChooseRule();
        choose.superior = superior;
        choose.inferior = inferior;
        return choose;
    }

    public static void match(Rule rule, String state, Consumer<Object> onMatch, Consumer<String> onFail)
    {
        Objects.requireNonNull(rule);
        new PEG(rule).match(state, onMatch, onFail);
    }

    public void match(String state, Consumer<Object> onMatch, Consumer<String> onFail)
    {
        Objects.requireNonNull(state);
        Objects.requireNonNull(onMatch);
        match1(rule, state, (s, r) -> {
            if (s.isEmpty()) {
                onMatch.accept(r);
            } else {
                onFail.accept("Unexpected " + s + ", result is " + r);
            }
        }, onFail);
    }

    private void match1(Rule rule, String state, OnMatch onMatch, Consumer<String> onFail) {
        if (FinalRule.class.isInstance(rule)) {
            MatchResult result = ((FinalRule) rule).match(state);
            if (result.matched) {
                onMatch.apply(result.newState, result.value);
            } else {
                onFail.accept(((String) result.value));
            }
        } else if (SequenceRule.class.isInstance(rule)) {
            SequenceRule sRule = ((SequenceRule) rule);
            match1(sRule.front, state, (state1, ret1) -> {
                match1(sRule.rear, state1, (state2, ret2) -> {
                    onMatch.apply(state2, new Pair(ret1, ret2));
                }, onFail);
            }, onFail);
        } else if (ChooseRule.class.isInstance(rule)) {
            ChooseRule cRule = ((ChooseRule) rule);
            match1(cRule.superior, state, onMatch, (e1) -> {
                match1(cRule.inferior, state, onMatch, (e2) -> {
                    onFail.accept(e1 + " or " + e2);
                });
            });
        } else if(rule == null) {
            onMatch.apply(state, null);
        } else {
            throw new RuntimeException(state);
        }
    }







    // 不消耗, 匹配且返回NULL
    public final static Rule NULL = new FinalRule() {
        MatchResult match(String state) {
            MatchResult result = new MatchResult();
            result.matched = true;
            result.newState = state;
            result.value = null;
            return result;
        }
    };
    public final static Rule EMPTY = sym("");
    public final static Rule num = any(Arrays.stream("0123456789".split("")).toArray(String[]::new));
    public final static Rule az = any(Arrays.stream("abcdefghijklmnopqrstuvwxyz".split("")).toArray(String[]::new));
    public final static Rule AZ = any(Arrays.stream("ABCDEFGHIJKLMNOPQRSTUVWXYZ".split("")).toArray(String[]::new));
    public final static Rule alpha = choose(az, AZ);
    public final static Rule alphaNum = choose(alpha, num);
    public final static Rule s = choose(sym(" "), sym("\t"));
    public static Rule orEmpty(Rule rule)
    {
        return choose(
                rule,
                EMPTY
        );
    }
    public static Rule plus(Rule rule) {
        rule = seq(
                rule,
                choose(
                        null,
                        EMPTY
                )
        );
        ((ChooseRule)((SequenceRule)rule).rear).superior = rule;
        return rule;
    }
    public static Rule star(Rule rule) {
        return orEmpty(plus(rule));
    }
    public static Rule repeat(Rule rule, int n)
    {
        if (n < 0) {
            throw new RuntimeException();
        }
        if (n == 0) {
            return EMPTY;
        }
        Rule nRule = rule;
        for (int i = 1; i < n; i++) {
            nRule = seq(
                    nRule,
                    rule
            );
        }
        return nRule;
    }
    public static Rule repeat(Rule rule, int min, int max)
    {
        if (max < min) {
            throw new RuntimeException();
        }
        if (max == min) {
            return repeat(rule, min);
        }
        return seq(
                repeat(rule, min),
                repeat(orEmpty(rule), max - min)
        );
    }
    public static Rule any(String[] symbols) {
        int l = symbols.length;
        if (l < 0) {
            throw new RuntimeException();
        }
        if (l == 0) {
            return EMPTY;
        }
        if (l == 1) {
            return sym(symbols[0]);
        }
        ChooseRule rule = choose(
                sym(symbols[l - 1]),
                sym(symbols[l - 2])
        );
        for (int i = l - 3; i >= 0; i--) {
            rule = choose(
                    sym(symbols[i]),
                    rule
            );
        }
        return rule;
    }
    public static Rule wrapperS(Rule rule) {
        return seq(
                star(s),
                seq(
                        rule,
                        star(s)
                )
        );
    }


    public static void match(Rule rule, String state, Consumer<Object> onMatch)
    {
        Objects.requireNonNull(rule);
        new PEG(rule).match(state, onMatch, System.err::println);
    }
    public static void main(final String[] args) {
        Rule rule;
        Consumer<Object> echo = System.out::println;

//        rule = choose(
//                seq(
//                        sym("("),
//                        seq(
//                                null,
//                                sym(")"))
//                ),
//                sym("")
//        );
//        ((SequenceRule) ((SequenceRule) ((ChooseRule)rule).superior).rear).front = rule;
//
//        match(rule, "()", echo);
//        match(rule, "()(", echo);
//
//        rule = choose(
//                choose(
//                        sym("a"),
//                        choose(
//                                sym("b"),
//                                sym("c")
//                        )
//                ),
//                sym("")
//        );
//
//        match(rule, "", echo);
//        match(rule, "a", echo);
//        match(rule, "b", echo);
//
//
//
//        FinalRule a = sym("a");
//        FinalRule empty = sym("");
//        SequenceRule aplus = seq(
//                a,
//                choose(
//                        null,
//                        empty
//                )
//        );
//        ((ChooseRule) aplus.rear).superior = aplus;
//        rule = choose(
//                aplus,
//                empty
//        );
//
//        rule = plus(sym("a"));
//        match(rule, "a", echo);
//        match(rule, "aa", echo);
//        match(rule, "aaaaaaaa", echo);
//


//        rule = star(sym("a"));
//        match(rule, "", echo);
//        match(rule, "a", echo);
//        match(rule, "aa", echo);
//        match(rule, "aaaaaaaa", echo);
//
//
//        rule = star(sym("ab"));
//        match(rule, "", echo);
//        match(rule, "ab", echo);
//        match(rule, "abab", echo);
//        match(rule, "ababab", echo);
//
//        rule = star(choose(
//                sym("a"),
//                sym("b")
//        ));
//        match(rule, "", echo);
//        match(rule, "ab", echo);
//        match(rule, "aaab", echo);
//        match(rule, "aaabbb", echo);



//        Rule a0 = repeat(sym("a"), 0);
//        match(a0, "", echo);
//        // match(a0, "a", echo);

//        Rule a2 = repeat(sym("a"), 2);
//        match(a2, "aa", echo);
//        // match(a2, "", echo);
//        // match(a0, "a", echo);
//        // match(a0, "aaa", echo);

//        Rule a3 = repeat(sym("a"), 3);
//        match(a3, "aaa", echo);
//        match(a3, "", echo);
//        match(a3, "a", echo);

//        rule = repeat(sym("a"), 0, 3);
//        match(rule, "", echo);
//        match(rule, "a", echo);
//        match(rule, "aa", echo);
//        match(rule, "aaa", echo);
//        // match(rule, "aaaa", echo);
//
//        rule = repeat(sym("a"), 1, 2);
//        // match(rule, "", echo);
//        match(rule, "a", echo);
//        match(rule, "aa", echo);
//        // match(rule, "aaa", echo);

//        match(number, "1", echo);
//        match(number, "a", echo);
//        match(number, "", echo);

//        match(star(alphaNum), "", echo);
//        match(star(alphaNum), "0a1b", echo);
//        match(star(alphaNum), "0a1b!", echo);


//        T<T, T>



        Rule t = seq(
                alpha,
                star(alphaNum)
        );
        Rule type = seq(
                t,
                star(
                        seq(
                                sym("."),
                                t
                        )
                )
        );
        Rule genericType = seq(
                type,
                choose(
                        seq(
                                wrapperS(sym("<")),
                                seq(
                                        null, // genericTypeList
                                        wrapperS(sym(">"))
                                )
                        ),
                        EMPTY
                )
        );
        Rule genericTypeList = seq(
                genericType,
                star(
                        seq(
                                wrapperS(sym(",")),
                                genericType
                        )
                )
        );
        ((SequenceRule) ((SequenceRule) ((ChooseRule) ((SequenceRule) genericType).rear).superior).rear).front = genericTypeList;


        // FIXME 分组捕获
//        match(type, "a0", echo);
//        match(type, "a0.x1.yz3", echo);


        // javac -Xlint:unchecked PEG.java  && java -Xmixed -Xss10M PEG
        match(genericType, "A.B.Map<String, List<X.Y.Person>>", echo);

//        match(genericType, "A.B.Map", echo);
//        match(genericTypeList, "A.B.Map<>", echo);
    }
}
