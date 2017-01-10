<?php

namespace Minimalism\SQL\Builder;


interface SQLBuilder
{
    public function getSQL();
    public function getBound();
}