<?php

namespace SNOWGIRL_CORE\View\Widget\Ad;

use SNOWGIRL_CORE\View\Widget\Ad;

class Adaptive extends Ad
{
    protected function getStyle()
    {
        return 'display:block';
    }

    public function getCoreDomClass()
    {
        return 'widget-ad-adaptive';
    }
}