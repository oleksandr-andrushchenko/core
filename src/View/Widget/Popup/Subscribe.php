<?php

namespace SNOWGIRL_CORE\View\Widget\Popup;

use SNOWGIRL_CORE\View\Widget;
use SNOWGIRL_CORE\View\Widget\Popup;

class Subscribe extends Popup
{
    protected $title = 'Подписка';
    //@todo lazy loading... do not stringify widgets in case of already showed
    protected $body = '';
    protected $modal = true;
    protected $showIn;
    protected $showedCookie = 'subscriber_popup_showed';

    protected function makeParams(array $params = []): array
    {
        return parent::makeParams([
            'showIn' => (int)$this->app->config('app.show_subscribe_popup_in', 20)
        ]);
    }

    protected function getInner(string $template = null): ?string
    {
        return implode($this->makeNode('div', ['class' => 'header', 'text' => '-или-']), array_filter([
            $this->app->views->subscribeForm([], $this->getLayout())->stringify(),
            $this->app->views->vkontaktePage($this->getLayout())->stringify(),
            $this->app->views->facebookPage($this->getLayout())->stringify()
        ], function ($v) {
            return 0 < strlen($v);
        }));
    }

    protected function addScripts(): Widget
    {
        return parent::addScripts()
            ->addCssScript('@core/widget/popup.subscribe.css');
    }

    protected function stringifyPrepare()
    {
        $this->addDomClass('subscribe');

        return parent::stringifyPrepare();
    }
}