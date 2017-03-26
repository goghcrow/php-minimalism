<?php



function crust($next)
{
    echo "到达<地壳>\n";
    $next();
    echo "离开<地壳>\n";
}

function upperMantle($next)
{
    echo "到达<上地幔>\n";
    $next();
    echo "离开<上地幔>\n";
}

function mantle($next)
{
    echo "到达<下地幔>\n";
    $next();
    echo "离开<下地幔>\n";
}

function outerCore($next)
{
    echo "到达<外核>\n";
    $next();
    echo "离开<外核>\n";
}

function innerCore($next)
{
    echo "到达<内核>\n";
}


// left reduce
// right reduce

//function array_right_reduce(array $input, callable $function, $initial = null)
//{
//    return array_reduce(array_reverse($input, true), $function, $initial);
//}
//
//function compose(...$fns)
//{
//    return array_right_reduce($fns, function($carry, $fn) {
//        return function() use($carry, $fn) {
//            $fn($carry);
//        };
//    });
//}
//

// 我们途径 crust -> upperMantle -> mantle -> outerCore -> innerCore 到达地心
// 然后穿越另一半球  -> outerCore -> mantle -> upperMantle -> crust

//$travel = compose("crust", "upperMantle", "mantle", "outerCore", "innerCore");
//$travel(); // output:
//exit;


function makeTravel(...$layers)
{
    $next = null;
    $i = count($layers);
    while ($i--) {
        $layer = $layers[$i];
        $next = function() use($layer, $next) {
            $layer($next);
        };
    }
    return $next;
}


// 我们途径 crust -> upperMantle -> mantle -> outerCore -> innerCore 到达地心
// 然后穿越另一半球  -> outerCore -> mantle -> upperMantle -> crust

$travel = makeTravel("crust", "upperMantle", "mantle", "outerCore", "innerCore");
//$travel(); // output:
/*
到达<地壳>
到达<上地幔>
到达<下地幔>
到达<外核>
到达<内核>
离开<外核>
离开<下地幔>
离开<上地幔>
离开<地壳>
*/




function upperMantle1($next)
{
    // 我们放弃对去程上地幔的暂留
    // echo "到达<上地幔>\n";
    $next();
    // 只在返程时暂留
    echo "离开<上地幔>\n";

}

function outerCore2($next)
{
//    return function() use($next) {
        // 我们决定只在去程考察外核
        echo "到达<外核>\n";
        $next();
        // 因为温度过高,去程匆匆离开外壳
        // echo "离开<外核>\n";
//    };
}

$travel = makeTravel("crust", "upperMantle1", "mantle1", "outerCore2", "innerCore1");
//$travel(); // output:
/*
到达<地壳>
到达<上地幔>
到达<下地幔>
到达<外核>
遇到岩浆
离开<下地幔>
离开<上地幔>
离开<地壳>
*/