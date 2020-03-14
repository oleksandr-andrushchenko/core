<?php

namespace SNOWGIRL_CORE\View\Widget\Ad;

use SNOWGIRL_CORE\View\Widget\Ad;

class LongHorizontal extends Ad
{
    protected function getStyle(): string
    {
        return 'display:inline-block;width:728px;height:90px';
    }

    public function getCoreDomClass(): string
    {
        return 'widget-ad-long-horizontal';
    }
}