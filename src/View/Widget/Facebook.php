<?php

namespace SNOWGIRL_CORE\View\Widget;

use SNOWGIRL_CORE\View\Widget;

class Facebook extends Widget
{
    protected $appId;
    protected $page;
    protected $locale;
    protected $domId = 'fb-root';

    protected function makeParams(array $params = []): array
    {
        return array_merge(parent::makeParams($params), [
            'appId' => $this->app->config('keys->facebook_app_id', false),
            'page' => $this->app->config('keys.facebook_page', false),
            'locale' => $this->app->trans->getLocale()
        ]);
    }

    protected function getInner(string $template = null): ?string
    {
        return null;
    }

    protected function addScripts(): Widget
    {
        parent::addScripts();

        if (self::checkScript('fb')) {
            $this->addJs(implode('', [
                '(function(d, s, id) {',
                'var js, fjs = d.getElementsByTagName(s)[0];',
                'if (d.getElementById(id)) return;',
                'js = d.createElement(s); js.id = id;',
                'js.src = "//connect.facebook.net/' . $this->locale . '/sdk.js#xfbml=1&version=v2.10&appId=' . $this->appId . '";',
                'fjs.parentNode.insertBefore(js, fjs);',
                '}(document, "script", "facebook-jssdk"));'
            ]), true, false, true);

            if ($view = $this->getLayout()) {
                $view->addMetaProperty('fb:app_id', $this->appId);
            }
        }

        return $this;
    }

    protected function stringifyPrepare()
    {
        $this->addNodeAttr('style', 'margin:0!important');
        return parent::stringifyPrepare();
    }

    public function isOk(): bool
    {
        return !!$this->appId;
    }
}