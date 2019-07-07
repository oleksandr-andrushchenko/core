<?php

namespace SNOWGIRL_CORE\View\Widget;

use SNOWGIRL_CORE\App;
use SNOWGIRL_CORE\Script\Js;
use SNOWGIRL_CORE\View;
use SNOWGIRL_CORE\View\Widget;
use SNOWGIRL_CORE\Ad as Provider;
use SNOWGIRL_CORE\Helper\Arrays;

abstract class Ad extends Widget
{
    /** @var Provider */
    protected $provider;

    public function __construct(App $app, Provider $provider, View $parent = null)
    {
        parent::__construct($app, ['provider' => $provider], $parent);
    }

    abstract protected function getStyle();

    protected function getNode()
    {
        return $this->makeNode($this->provider->getContainerTag(), Arrays::filterByLength(array_merge([
            'id' => $this->getDomId(),
            'class' => $this->getDomClass(),
            'style' => $this->getStyle()
        ], $this->provider->getContainerAttrs($this))));
    }

    protected function getInner($template = null)
    {
        return null;
    }

    protected function addScripts()
    {
        parent::addScripts();

        if ($this->checkScript($this->provider->getCheckCoreScriptKey())) {
            $this->addJs(new Js($this->provider->getCoreScript()), true);
        }

        $this->addJs(new Js($this->provider->getScript($this), true));

        return $this;
    }

    protected function stringifyPrepare()
    {
        foreach (Arrays::filterByLength($this->provider->getContainerClasses()) as $class) {
            $this->addDomClass($class);
        }

        $this->addDomClass('widget-ad');

        return parent::stringifyPrepare();
    }

    public function isOk()
    {
        return $this->provider->isOk();
    }
}