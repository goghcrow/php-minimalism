<?php

namespace Minimalism;


class Backoff
{
    private $min;
    private $max;
    private $factor;
    private $jitter;
    private $attempts = 0;

    public function __construct($min = 100, $max = 10000, $factor = 2, $jitter = 0)
    {
        $this->min = $min;
        $this->max = $max;
        $this->factor = $factor;
        $this->jitter = max(0, min(1, $jitter));
    }

    public function duration($attempts)
    {
        return static::calculate($attempts, $this->min, $this->max, $this->factor, $this->jitter);
    }

    public function nextDuration()
    {
        return $this->duration($this->attempts++);
    }

    public function reset()
    {
        $this->attempts = 0;
    }

    public static function calculate($attempts, $min, $max, $factor = 2, $jitter = 0)
    {
        $jitter = max(0, min(1, $jitter));
        $ms = $min * pow($factor, $attempts);
        if ($jitter > 0) {
            $rand = (float)rand() / (float)getrandmax();
            $deviation = floor($rand * $jitter * $ms);
            $ms = (floor($rand * 10) & 1) === 0 ? $ms - $deviation : $ms + $deviation;
        }
        return intval(min($ms, $max));
    }
}