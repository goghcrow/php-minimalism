<?php

$game = new Ants(40, 150);
$game->run();


/**
 * Class Ants
 * @ref https://zh.wikipedia.org/wiki/%E5%85%B0%E9%A1%BF%E8%9A%82%E8%9A%81
 */
class Ants
{
    const ANT = "\033[1;32m+\033[0m";
    const WHITE_CELL = " ";
    const BALCK_CELL = "^";

    const WHITE = 0;
    const BLACK = 1;

    const UP = 0;
    const RIGHT = 1;
    const DOWN = 2;
    const LEFT = 3;

    // row, column, direction
    private $ant;

    private $height, $width;
    private $matrix;

    public function __construct($height, $width)
    {
        $this->height = intval(rtrim(`tput lines`, PHP_EOL)) - 1;
        $this->width = intval(rtrim(`tput cols`, PHP_EOL)) - 1;

        echo "\033[2J\033[1;1H";

        for ($r = 0; $r < $this->height; $r++) {
            for ($c = 0; $c < $this->width; $c++) {
                $this->matrix[$r][$c] = static::WHITE;
            }
        }

        $this->ant = [rand(0, $this->height), rand(0, $this->width), rand(0, 3)];
    }

    private function next()
    {
        list($antR, $antC, $direction) = $this->ant;
        $cell = $this->matrix[$antR][$antC];

        if ($cell === static::WHITE) {
            $this->ant[2] = ($direction + 1) % 4; // 右转
            $this->matrix[$antR][$antC] = static::BLACK;
        } else if ($cell === static::BLACK) {
            $this->ant[2] = ($direction + 3) % 4; // 左转
            $this->matrix[$antR][$antC] = static::WHITE;
        }

        // 前进一步
        switch ($this->ant[2]) {
            case static::UP:
                $this->ant[0] = ($this->ant[0] - 1 + $this->height) % $this->height;
                break;
            case static::RIGHT:
                $this->ant[1] = ($this->ant[1] + 1) % $this->width;
                break;
            case static::DOWN:
                $this->ant[0] = ($this->ant[0] + 1) % $this->height;
                break;
            case static::LEFT:
                $this->ant[1] = ($this->ant[1] - 1 + $this->width) % $this->width;
                break;
        }
    }

    private function draw()
    {
        ob_start();

        list($antR, $antC, ) = $this->ant;

        echo "\033[0G";
        for ($r = 0; $r < $this->height; $r++) {
            for ($c = 0; $c < $this->width; $c++) {
                if ($r === $antR && $c === $antC) {
                    echo static::ANT;
                } else {
                    $cell = $this->matrix[$r][$c];
                    if ($cell === static::BLACK) {
                        echo static::BALCK_CELL;
                    } else if ($cell === static::WHITE) {
                        echo static::WHITE_CELL;
                    }
                }
            }
            echo "\n";
        }
        echo "\033[{$this->height}A";

        ob_end_flush();
    }

    public function run()
    {
        while (true) {
            $this->draw();
            $this->next();
        }
    }
}