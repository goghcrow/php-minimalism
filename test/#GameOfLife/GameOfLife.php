<?php
$game = new Game();
$game->run();


/**
 * Class Game
 * @ref https://zh.wikipedia.org/wiki/%E7%94%9F%E5%91%BD%E6%B8%B8%E6%88%8F
 */
class Game
{
    const BLACK = "\033[1;m@\033[0m";
    const WHITE = ".";

    private $height, $width;
    private $matrix, $matrix_;

    public function __construct()
    {
        // stty size
        $this->height = intval(rtrim(`tput lines`, PHP_EOL)) - 1;
        $this->width = intval(rtrim(`tput cols`, PHP_EOL)) - 1;

        for ($r = 0; $r < $this->height; $r++) {
            for ($c = 0; $c < $this->width; $c++) {
                $this->matrix[$r][$c] = rand(0, 1);
            }
        }
    }

    private function next()
    {
        for ($r = 0; $r < $this->height; $r++) {
            for ($c = 0; $c < $this->width; $c++) {
                $rA1 = ($r + 1) % $this->height;
                $rM1 = ($r - 1 + $this->height) % $this->height;
                $cA1 = ($c + 1) % $this->width;
                $cM1 = ($c - 1 + $this->width) % $this->width;

                $n = $this->matrix[$rM1][$cM1] +
                    $this->matrix[$rM1][$c] +
                    $this->matrix[$rM1][$cA1] +
                    $this->matrix[$r][$cM1] +
                    $this->matrix[$r][$cA1] +
                    $this->matrix[$rA1][$cM1] +
                    $this->matrix[$rA1][$c] +
                    $this->matrix[$rA1][$cA1];

                if ($n === 3 || ($n === 2 && $this->matrix[$r][$c] === 1)) {
                    $this->matrix_[$r][$c] = 1;
                } else {
                    $this->matrix_[$r][$c] = 0;
                }
            }
        }

        $this->matrix = $this->matrix_;
    }

    private function draw()
    {
        ob_start();
        echo "\033[0G";
        for ($r = 0; $r < $this->height; $r++) {
            for ($c = 0; $c < $this->width; $c++) {
                if ($this->matrix[$r][$c] === 1) {
                    echo static::BLACK;
                } else {
                    echo static::WHITE;
                }
            }
            echo "\n";
        }
        echo "\033[{$this->height}A";
        ob_end_flush();
    }

    public function run()
    {
        echo "\033[2J\033[1;1H";

        while (true) {
            $this->draw();
            $this->next();
        }
    }
}