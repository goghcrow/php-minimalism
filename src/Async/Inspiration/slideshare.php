<?php
/**
 * Created by IntelliJ IDEA.
 * User: chuxiaofeng
 * Date: 17/2/12
 * Time: 下午6:09
 */


$dir = "reinvent-y";
if (!file_exists($dir)) {
    mkdir($dir);
}

for ($i = 1; $i <= 25; $i++) {
    $url = "http://image.slidesharecdn.com/reinvent-y-120409000132-phpapp02/95/reinventing-the-y-combinator-{$i}-728.jpg";
    file_put_contents($dir . "/$i.jpg", file_get_contents($url));
}
