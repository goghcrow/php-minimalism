<?php

namespace Blue\Test;


use Blue\Terminal as T;

require __DIR__ . "/../src/Terminal.php";

T::put("hello", T::FG_GREEN, T::BRIGHT);