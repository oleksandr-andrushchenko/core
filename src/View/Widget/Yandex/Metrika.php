<?php

namespace SNOWGIRL_CORE\View\Widget\Yandex;

use SNOWGIRL_CORE\Script\Js;
use SNOWGIRL_CORE\View\Widget\Yandex;

class Metrika extends Yandex
{
    protected $id;

    protected function makeParams(array $params = [])
    {
        return array_merge(parent::makeParams($params), [
            'id' => $this->app->config->keys->yandex_metrika_id(false)
        ]);
    }

    protected function getNode()
    {
        return $this->makeNode('noscript')
            ->append($this->makeNode('div')
                ->append($this->makeNode('img', [
                    'src' => 'https://mc.yandex.ru/watch/' . $this->id,
                    'style' => 'position:absolute; left:-9999px;',
                    'alt' => ''
                ])));
    }

    protected function addScripts()
    {
        parent::addScripts();

        if (self::checkScript('ym')) {
            $this->addJs(new Js(implode('', [
                '(function (d, w, c) {',
                '(w[c] = w[c] || []).push(function () {',
                'try {',
                'w.yaCounter' . $this->id . ' = new Ya.Metrika({id: "' . $this->id . '", clickmap: true, trackLinks: true, accurateTrackBounce: true});',
                '} catch (e) {}',
                '});',
                'var n = d.getElementsByTagName("script")[0], s = d.createElement("script"), f = function () {n.parentNode.insertBefore(s, n);};',
                's.type = "text/javascript";',
                's.async = true;',
                's.src = "https://mc.yandex.ru/metrika/watch.js";',
                'if (w.opera == "[object Opera]") {d.addEventListener("DOMContentLoaded", f, false);} else {f();}',
                '})(document, window, "yandex_metrika_callbacks");'
            ]), true), true);
        }

        return $this;
    }

    public function isOk()
    {
        return $this->id;
    }
}