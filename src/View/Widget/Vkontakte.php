<?php

namespace SNOWGIRL_CORE\View\Widget;

use SNOWGIRL_CORE\View\Node;
use SNOWGIRL_CORE\View\Widget;

class Vkontakte extends Widget
{
    protected $page;
    protected $pageId;
    protected $appId;

    protected function makeParams(array $params = []): array
    {
        return array_merge(parent::makeParams($params), [
            'page' => $this->app->config('keys.vkontakte_page', false),
            'pageId' => $this->app->config('keys.vkontakte_page_id', false),
            'appId' => $this->app->config('keys.vkontakte_app_id', false)
        ]);
    }

    protected function getNode(): ?Node
    {
        return null;
    }

    protected function getInner(string $template = null): ?string
    {
        return null;
    }

    protected function addScripts(): Widget
    {
        parent::addScripts();

        if (self::checkScript('vk')) {
            $this->addJs('https://vk.com/js/api/openapi.js?151', false, false, true);
            $this->addJs('VK.init({apiId: ' . $this->appId . ', onlyWidgets: true});', true, false, true);
        }

        return $this;
    }

    /**
     * @todo take into account client's country (e.g. for ukraine - return false)
     * @return mixed
     */
    public function isOk(): bool
    {
        return !!$this->appId;
    }
}