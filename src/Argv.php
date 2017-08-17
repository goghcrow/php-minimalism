<?php

namespace Minimalism;



class CmdBuilder
{

}

class YArgs implements \ArrayAccess
{
    private $map;

    public function usage()
    {

    }

    public function help()
    {

    }

    public function alias($origin, $alias)
    {

    }

    public function count()
    {

    }

    public function demand(array $args)
    {

    }

    public function default($var, $def)
    {

    }


    public function __get($name)
    {
        if (isset($this->map[$name])) {
            return $this->map[$name];
        } else {
            return null;
        }
    }

    public function offsetExists($offset)
    {
        // TODO: Implement offsetExists() method.
    }

    public function offsetGet($offset)
    {
        // TODO: Implement offsetGet() method.
    }

    public function offsetSet($offset, $value)
    {
        // TODO: Implement offsetSet() method.
    }

    public function offsetUnset($offset)
    {
        // TODO: Implement offsetUnset() method.
    }
}




class Argv
{
    private $opts = [];
    private $usage;

    public function get($argName = null, $default = null)
    {
        if ($argName === null) {
            return $this->opts;
        } else {
            return isset($this->opts[$argName]) ? $this->opts[$argName] : $default;
        }
    }

    public function usage($desc = null)
    {
        if ($desc) {
            echo $desc, "\n";
        }
        exit("$this->usage\n");
    }

    public static function parse(array $opts)
    {
        global $argv;

        $self = new static;

        $shortOpts = "";
        $longOpts = [];
        $usage = [];

        $optMap = [];
        foreach ($opts as list($short, $long, $info)) {
            $shortOpts .= "$short:";
            $longOpts[] = "$long:";
            $optMap[$short] = $long;
            $optMap[$long] = $short;
            $usage[] = "-$short --$long $info";
        }

        $self->usage = "Usage: {$argv[0]}\n\t" . implode(PHP_EOL . "\t", $usage);

        /**
         * Individual characters (do not accept values)
         * Characters followed by a colon (parameter requires value)
         * Characters followed by two colons (optional value)
         */
        $opts = getopt($shortOpts, $longOpts);
        if ($opts === false) {
            exit($usage);
        }

        foreach ($opts as $k => $opt) {
            $opts[$optMap[$k]] = $opt;
        }

        $self->opts = $opts;

        return $self;
    }
}