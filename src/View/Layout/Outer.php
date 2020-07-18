<?php

namespace SNOWGIRL_CORE\View\Layout;

use SNOWGIRL_CORE\View\Layout;

use SNOWGIRL_CORE\View\Widget\Popup\Subscribe as SubscribePopup;
use SNOWGIRL_CORE\View\Widget\Google\Analytics as GoogleAnalytics;
use SNOWGIRL_CORE\View\Widget\Yandex\Metrika as YandexMetrika;

class Outer extends Layout
{
    protected function addMenuNodes(): Layout
    {
        $pages = $this->app->managers->pages;

        foreach ($pages->getMenu() as $page) {
            $this->addNav($page->getMenuTitle(), $pages->getLink($page));
        }

        return $this;
    }

    protected function addSubscriberPopupWidget()
    {
        if (!$this->app->request->getDevice()->isMobile()) {
            $this->addWidget(SubscribePopup::class);
        }

        return $this;
    }

    protected function getSubscriberWidget()
    {
        return $this->app->views->subscribeForm([], $this)->stringify();
    }

    protected function stringifyPrepare()
    {
        $this->prepareContent();

        $this->addJsConfig('doNotShowLoadingOnRequests', true);

        $this->addMetaProperty('og:site_name', $this->site);
//        $this->addMetaProperty('og:locale:locale', strtolower($this->locale));

//        $this->addSubscriberPopupWidget();

        $this->addWidget(GoogleAnalytics::class)
            ->addWidget(YandexMetrika::class);

        return parent::stringifyPrepare();
    }

    protected function makeBreadcrumbs(): string
    {
        return $this->stringifyContent('layout/breadcrumbs.phtml', [
            'referer' => $this->mobileBackBtn ? $this->app->request->getReferer() : false,
        ]);
    }
}