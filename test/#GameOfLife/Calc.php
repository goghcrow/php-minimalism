<?php


function idx($i, $limit)
{
    while ($i < 0) {
        $i += $limit;
    }
    $i %= $limit;
    return $i;
}