<?php

namespace SNOWGIRL_CORE\View\Widget;

use SNOWGIRL_CORE\AbstractApp;
use SNOWGIRL_CORE\View;
use SNOWGIRL_CORE\View\Widget;
use SNOWGIRL_CORE\Ad as Provider;
use SNOWGIRL_CORE\Helper\Arrays;
use SNOWGIRL_CORE\View\Node;

abstract class Ad extends Widget
{
    /** @var Provider */
    protected $provider;

    public function __construct(AbstractApp $app, Provider $provider, View $parent = null)
    {
        parent::__construct($app, ['provider' => $provider], $parent);
    }

    abstract protected function getStyle();

    protected function getNode(): ?Node
    {
        return $this->makeNode($this->provider->getContainerTag(), Arrays::filterByLength(array_merge([
            'id' => $this->getDomId(),
            'class' => $this->getDomClass(),
            'style' => $this->getStyle()
        ], $this->getContainerAttrs(), $this->provider->getContainerAttrs())));
    }

    protected function getContainerAttrs()
    {
        return [];
    }

    protected function getInner(string $template = null): ?string
    {
        return '';
    }

    protected function addScripts(): Widget
    {
        parent::addScripts();

        if ($this->checkScript($this->provider->getCheckCoreScriptKey())) {
            $this->addJs($this->provider->getCoreScript(), false, false, true);
        }

        $this->addJs($this->provider->getScript($this), true, false, true);

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

    public function isOk(): bool
    {
        return $this->provider->isOk();
    }
}