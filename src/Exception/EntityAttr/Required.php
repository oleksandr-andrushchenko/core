<?php

namespace SNOWGIRL_CORE\Exception\EntityAttr;

use SNOWGIRL_CORE\Exception\EntityAttr;

class Required extends EntityAttr
{
    protected function getTypeName()
    {
        return 'required';
    }
}