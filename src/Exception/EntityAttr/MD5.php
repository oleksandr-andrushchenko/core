<?php

namespace SNOWGIRL_CORE\Exception\EntityAttr;

use SNOWGIRL_CORE\Exception\EntityAttr;

class MD5 extends EntityAttr
{
    protected function getTypeName()
    {
        return 'md5';
    }
}