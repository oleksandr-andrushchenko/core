<?php

namespace SNOWGIRL_CORE\Ad;

use SNOWGIRL_CORE\Ad;
use SNOWGIRL_CORE\View\Widget\Ad as Widget;

class Yandex extends Ad
{
    public function getContainerTag()
    {
        return 'div';
    }

    public function getContainerClasses()
    {
        return [];
    }

    protected function getContainerId($partial = false)
    {
        if ($partial) {
            return 'R-A-' . $this->clientId . '-' . $this->adId;
        }

        return 'yandex_rtb_' . $this->getContainerId(true);
    }

    public function getContainerAttrs(Widget $widget)
    {
        return [
            'id' => $this->getContainerId()
        ];
    }

    public function getCheckCoreScriptKey()
    {
        return 'y-ads';
    }

    public function getCoreScript()
    {
        return '//an.yandex.ru/system/context.js';
    }

    public function getScript(Widget $widget)
    {
        return '(function(w, n) {w[n] = w[n] || [];w[n].push(function() {Ya.Context.AdvManager.render(' . json_encode([
                'blockId' => $this->getContainerId(true),
//            'blockId' => $widget->getDomId(),
                'renderTo' => $this->getContainerId(),
                'async' => 'false'
            ]) . ');});})(this, "yandexContextSyncCallbacks");';
    }

    public function getAdsTxtDomain()
    {
        return null;
    }

    public function getAdsTxtAccountId()
    {
        return null;
    }

    public function getAdsTxtRelationshipType()
    {
        return null;
    }

    public function getAdsTxtTagId()
    {
        return null;
    }
}