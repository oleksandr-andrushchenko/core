<?php

namespace SNOWGIRL_CORE\View\Widget\Google;

use SNOWGIRL_CORE\View\Widget;
use SNOWGIRL_CORE\View\Widget\Google;

class Analytics extends Google
{
    protected function addScripts(): Widget
    {
        parent::addScripts();

        if (self::checkScript('gt')) {
            $this->addJs('https://www.googletagmanager.com/gtag/js?id=' . $this->tagId, false, false, true);

            $this->addJs(implode('', [
                'window.dataLayer = window.dataLayer || [];',
                'function gtag() {dataLayer.push(arguments);}',
                'gtag("js", new Date());'
            ]), true, false, true);
        }

        $this->addJs('gtag("config", "' . $this->tagId . '");', true, false, true);

        return $this;
    }
}