<?php

namespace Minimalism\Test\MapCluster;

use Minimalism\MapCluster\Bounds;
use Minimalism\MapCluster\LngLat;
use Minimalism\MapCluster\LngLatCluster;

require __DIR__ . "/../../src/MapCluster/LngLat.php";
require __DIR__ . "/../../src/MapCluster/Bounds.php";
require __DIR__ . "/../../src/MapCluster/Cluster.php";
require __DIR__ . "/../../src/MapCluster/LngLatCluster.php";




$markers = [
    new LngLat(120.001, 30.001),
    new LngLat(120.001, 30.000),
    new LngLat(120.003, 30.003),
    new LngLat(121.004, 30.004),
    new LngLat(120.005, 30.005),
    new LngLat(120.006, 30.006),
];
$option = [
    'gridMeters' => 1000,
    'isAverageCenter' => false
];

$bounds = new Bounds(new LngLat(120.001, 30.000), new LngLat(120.003, 30.003));
$markerCluster = new LngLatCluster($markers, $bounds, $option);

$markerCluster->getResult();