<?php

namespace SNOWGIRL_CORE\View\Widget;

use SNOWGIRL_CORE\Script\Js;
use SNOWGIRL_CORE\View\Widget;

class Vkontakte extends Widget
{
    protected $page;
    protected $pageId;
    protected $appId;

    protected function makeParams(array $params = [])
    {
        return array_merge(parent::makeParams($params), [
            'page' => $this->app->config->keys->vkontakte_page(false),
            'pageId' => $this->app->config->keys->vkontakte_page_id(false),
            'appId' => $this->app->config->keys->vkontakte_app_id(false)
        ]);
    }

    protected function getNode()
    {
        return null;
    }

    protected function getInner($template = null)
    {
        return null;
    }

    protected function addScripts()
    {
        parent::addScripts();

        if (self::checkScript('vk')) {
            $this->addJs(new Js('https://vk.com/js/api/openapi.js?151'), true);
            $this->addJs(new Js('VK.init({apiId: ' . $this->appId . ', onlyWidgets: true});', true), true);
        }

        return $this;
    }

    /**
     * @todo take into account client's country (e.g. for ukraine - return false)
     * @return mixed
     */
    public function isOk()
    {
        return $this->appId;
    }
}