<?php

namespace SNOWGIRL_CORE\Ad;

use SNOWGIRL_CORE\Ad;
use SNOWGIRL_CORE\View\Widget\Ad as Widget;
use SNOWGIRL_CORE\Helper\Classes;

class Google extends Ad
{
    protected static $format = [
        'Adaptive' => 'auto'
    ];

    public function getContainerTag()
    {
        return 'ins';
    }

    public function getContainerClasses()
    {
        return [
            'adsbygoogle'
        ];
    }

    public function getContainerAttrs(Widget $widget)
    {
        $class = Classes::getShortName($widget);

        return [
            'data-ad-client' => 'ca-' . $this->getAdsTxtAccountId(),
            'data-ad-slot' => $this->adId,
            'data-ad-format' => self::$format[$class] ?? null
        ];
    }

    public function getCheckCoreScriptKey()
    {
        return 'g-ads';
    }

    public function getCoreScript()
    {
        return '//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js';
    }

    public function getScript(Widget $widget)
    {
        return '(adsbygoogle = window.adsbygoogle || []).push({});';
    }

    public function getAdsTxtDomain()
    {
        return 'google.com';
    }

    public function getAdsTxtAccountId()
    {
        return 'pub-' . $this->clientId;
    }

    public function getAdsTxtRelationshipType()
    {
        return 'DIRECT';
    }

    public function getAdsTxtTagId()
    {
        return 'f08c47fec0942fa0';
    }
}