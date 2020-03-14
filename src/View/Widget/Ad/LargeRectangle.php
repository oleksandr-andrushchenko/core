<?php

namespace SNOWGIRL_CORE\View\Widget\Ad;

use SNOWGIRL_CORE\View\Widget\Ad;

class LargeRectangle extends Ad
{
    protected function getStyle(): string
    {
        return 'display:inline-block;width:336px;height:280px';
    }

    public function getCoreDomClass(): string
    {
        return 'widget-ad-large-rectangle';
    }
}