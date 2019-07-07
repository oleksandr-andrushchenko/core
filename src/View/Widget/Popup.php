<?php

namespace SNOWGIRL_CORE\View\Widget;

use SNOWGIRL_CORE\View\Widget;

class Popup extends Widget
{
    protected $title;
    protected $body;
    protected $modal = true;
    protected $showIn;
    protected $showedCookie;
    protected $width = 300;

    protected function makeParams(array $params = [])
    {
        if (isset($params['showIn']) && !is_int($params['showIn'])) {
            unset($params['showIn']);
        }

        return $params;
    }

    protected function addScripts()
    {
        return parent::addScripts()
            ->addCoreScripts()
            ->addCssScript('@core/jquery-ui/jquery-ui.min.css')
            ->addJsScript('@core/widget/popup.js')
            ->addClientScript('popup', $this->getClientOptions([
                'modal',
                'showIn',
                'title',
                'width'
            ]));
    }

    protected function getInner($template = null)
    {
        return $this->body;
    }

    protected function stringifyWidget($template)
    {
        if ($this->showedCookie) {
            $this->app->request->getCookie()->set($this->showedCookie, 3600 * 24 * 7);
        }

        return parent::stringifyWidget($template);
    }

    public function isOk()
    {
        if ($this->showedCookie) {
            return !$this->app->request->getCookie()->_isset($this->showedCookie);
        }

        return true;
    }
}