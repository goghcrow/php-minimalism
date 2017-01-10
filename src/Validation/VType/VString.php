<?php

namespace Minimalism\Validation\VType;


use Minimalism\Validation\V;

class VString extends VType
{

    public function orElse($else = "") {
        return parent::orElse($else);
    }

    /**
     * @param $needle
     * @return VBool
     */
    public function contains($needle) {
        return VBool::of(strpos($this->var, $needle) !== false);
    }

    /**
     * @param $needle
     * @return VBool
     */
    public function startWith($needle) {
        if($needle === "") {
            return V::ofBool(true);
        }
        return V::ofBool(substr($this->var, 0, strlen($needle)) === $needle);
    }

    /**
     * @param $needle
     * @return VBool
     */
    public function endWith($needle) {
        if($needle === "") {
            return V::ofBool(true);
        }
        return V::ofBool(substr($this->var, -strlen($needle)) === $needle);
    }

    /**
     * @return VBool
     */
    public function isEmpty() {
        return VBool::of(strlen($this->var) === 0);
    }


    
    /**
     * @return $this|VNil
     *
     * 冗余方法, 可以通过 ->vAssert(P::isEmpty()) 做断言
     */
    public function beEmpty() {
        if (strlen($this->var) === 0) {
            return $this;
        }
        return V::ofNil("Not Empty String");
    }

    /**
     * @return $this|VNil
     *
     * 冗余方法, 可以通过 ->vAssert(P::notEmpty()) 做断言
     */
    public function notEmpty() {
        if (strlen($this->var) === 0) {
            return V::ofNil("Empty String");
        }
        return $this;
    }

    /**
     * @param string $regex
     * @return $this|VNil
     */
    public function match($regex) {
        $this->var = filter_var($this->var, FILTER_VALIDATE_REGEXP, ["options" => ["regexp"=>$regex]]);
        if ($this->var === false) {
            return V::ofNil("Regexp Match Fail");
        }
        return $this;
    }

    /**
     * @param string $charlist
     * @return $this
     *
     * 冗余方法,可通过 ->vMap("trim", [" \t\n\r\0\x0B"]) 实现
     */
    public function trim($charlist = " \t\n\r\0\x0B") {
        $this->var = trim($this->var, $charlist);
        return $this;
    }

    /**
     * @return VInt
     */
    public function sizeof() {
        return V::ofInt(strlen($this->var));
    }

    /**
     * @return VInt|VNil
     */
    public function len() {
        $this->var = mb_strlen($this->var, "UTF-8");
        if ($this->var === false) {
            return V::ofNil("mb_strlen Fail");
        }
        return V::ofInt($this->var);
    }

    /**
     * @param $sep
     * @return VArray
     */
    public function explode($sep) {
        return VArray::of(explode($sep, $this->var));
    }


    /**
     * HTML-escape '"<>& and characters with ASCII value less than 32, optionally strip or encode other special characters.
     * @param null $opts
     * @return VHtml
     * FILTER_FLAG_STRIP_LOW, FILTER_FLAG_STRIP_HIGH, FILTER_FLAG_STRIP_BACKTICK, FILTER_FLAG_ENCODE_HIGH
     */
    public function htmlEscape($opts = null) {
        $this->var = filter_var($this->var, FILTER_SANITIZE_SPECIAL_CHARS, $opts);
        if ($this->var === false) {
            return V::ofNil("Sanitize Special Chars Fail");
        }
        return VHtml::of($this->var);
    }

    /**
     * Equivalent to calling htmlspecialchars() with ENT_QUOTES set.
     * @param $opts
     * @return VHtml
     * FILTER_FLAG_NO_ENCODE_QUOTES
     */
    public function htmlSpecialchars($opts = null) {
        $this->var = filter_var($this->var, FILTER_SANITIZE_FULL_SPECIAL_CHARS, $opts);
        if ($this->var === false) {
            return V::ofNil("Sanitize Full Special Chars Fail");
        }
        return VHtml::of($this->var);
    }

    /**
     * Strip tags, optionally strip or encode special characters.
     * @param $opts
     * @return VHtml
     * FILTER_FLAG_NO_ENCODE_QUOTES, FILTER_FLAG_STRIP_LOW, FILTER_FLAG_STRIP_HIGH, FILTER_FLAG_STRIP_BACKTICK, FILTER_FLAG_ENCODE_LOW, FILTER_FLAG_ENCODE_HIGH, FILTER_FLAG_ENCODE_AMP
     */
    public function stripTags($opts = null) {
        $this->var = filter_var($this->var, FILTER_SANITIZE_STRIPPED, $opts);
        if ($this->var === false) {
            return V::ofNil("Sanitize Stripped Fail");
        }
        return VHtml::of($this->var);
    }



    /**
     * @return VInt
     */
    public function sanitizeToInt() {
        $this->var = filter_var($this->var, FILTER_SANITIZE_NUMBER_INT);
        if ($this->var === false) {
            return V::ofNil("Sanitize To Int Fail");
        }
        return VInt::of($this->var);
    }

    /**
     * @return VFloat
     */
    public function sanitizeToFloat() {
        $this->var = filter_var($this->var, FILTER_SANITIZE_NUMBER_FLOAT);
        if ($this->var === false) {
            return V::ofNil("Sanitize To Float Fail");
        }
        return VFloat::of($this->var);
    }

    /**
     * Remove all characters except letters, digits and $-_.+!*'(),{}|\\^~[]`<>#%";/?:@&=.
     * @return VUrl
     */
    public function sanitizeToUrl() {
        $this->var = filter_var($this->var, FILTER_SANITIZE_URL);
        if ($this->var === false) {
            return V::ofNil("Sanitize To Url Fail");
        }
        return VUrl::of($this->var);
    }



    /**
     * URL-encode string, optionally strip or encode special characters.
     * @param $opts
     * @return VUrl
     * FILTER_FLAG_STRIP_LOW, FILTER_FLAG_STRIP_HIGH, FILTER_FLAG_STRIP_BACKTICK, FILTER_FLAG_ENCODE_LOW, FILTER_FLAG_ENCODE_HIGH
     */
    public function encodeUrl($opts = null) {
        $this->var = filter_var($this->var, FILTER_SANITIZE_ENCODED, $opts);
        if ($this->var === false) {
            return V::ofNil("Sanitize Encoded Fail");
        }
        return VUrl::of($this->var);
    }

    /**
     * @return VString
     */
    public function addSlashes() {
        $this->var = filter_var($this->var, FILTER_SANITIZE_MAGIC_QUOTES);
        if ($this->var === false) {
            return V::ofNil("Sanitize Magic Quotes Fail");
        }
        return $this;
    }

    /**
     * Remove all characters except letters, digits and !#$%&'*+-=?^_`{|}~@.[].
     * @return VEmail
     */
    public function sanitizeToEmail() {
        $this->var = filter_var($this->var, FILTER_SANITIZE_EMAIL);
        if ($this->var === false) {
            return V::ofNil("Sanitize To Email Fail");
        }
        return VEmail::of($this->var);
    }
}