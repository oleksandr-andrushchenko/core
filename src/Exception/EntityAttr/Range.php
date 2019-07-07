<?php

namespace SNOWGIRL_CORE\Exception\EntityAttr;

use SNOWGIRL_CORE\Exception\EntityAttr;

class Range extends EntityAttr
{
    protected function getTypeName()
    {
        return 'range';
    }
}