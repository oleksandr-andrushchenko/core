<?php

namespace SNOWGIRL_CORE\View\Widget\Ad;

use SNOWGIRL_CORE\View\Widget\Ad;

class Adaptive extends Ad
{
    protected function getStyle(): string
    {
        return 'display:block';
    }

    public function getCoreDomClass(): string
    {
        return 'widget-ad-adaptive';
    }

    protected function getContainerAttrs(): array
    {
        return [
            'data-ad-format' => 'auto',
            'data-full-width-responsive' => 'true',
        ];
    }
}