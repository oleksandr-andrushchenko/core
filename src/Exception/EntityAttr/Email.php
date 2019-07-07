<?php

namespace SNOWGIRL_CORE\Exception\EntityAttr;

use SNOWGIRL_CORE\Exception\EntityAttr;

class Email extends EntityAttr
{
    protected function getTypeName()
    {
        return 'email';
    }
}