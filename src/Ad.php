<?php

namespace SNOWGIRL_CORE;

use SNOWGIRL_CORE\View\Widget\Ad as Widget;

abstract class Ad
{
    protected $clientId;
    protected $adId;

    public function __construct($clientId, $adId = null)
    {
        $this->clientId = $clientId;
        $this->adId = $adId;
    }

    public function setAdId($adId)
    {
        $this->adId = $adId;
        return $this;
    }

    abstract public function getContainerTag();

    abstract public function getContainerClasses();

    /**
     * @param Widget $widget
     *
     * @return array
     */
    abstract public function getContainerAttrs(Widget $widget);

    abstract public function getCheckCoreScriptKey();

    abstract public function getCoreScript();

    abstract public function getScript(Widget $widget);

    abstract public function getAdsTxtDomain();

    abstract public function getAdsTxtAccountId();

    /**
     * @return string - DIRECT or RESELLER
     */
    abstract public function getAdsTxtRelationshipType();

    public function getAdsTxtTagId()
    {
        return null;
    }

    public function isOk()
    {
        return $this->clientId && $this->adId;
    }
}