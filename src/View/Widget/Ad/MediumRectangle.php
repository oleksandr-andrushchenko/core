<?php

namespace SNOWGIRL_CORE\View\Widget\Ad;

use SNOWGIRL_CORE\View\Widget\Ad;

class MediumRectangle extends Ad
{
    protected function getStyle(): string
    {
        return 'display:inline-block;width:300px;height:250px';
    }

    public function getCoreDomClass(): string
    {
        return 'widget-ad-medium-rectangle';
    }
}