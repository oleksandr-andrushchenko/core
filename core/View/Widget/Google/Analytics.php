<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 1/1/18
 * Time: 2:01 PM
 */
namespace SNOWGIRL_CORE\View\Widget\Google;

use SNOWGIRL_CORE\Script\Js;
use SNOWGIRL_CORE\View\Widget\Google;

/**
 * Class Analytics
 * @package SNOWGIRL_CORE\View\Widget\Google
 */
class Analytics extends Google
{
    protected function addScripts()
    {
        parent::addScripts();

        if (self::checkScript('gt')) {
            $this->addJs(new Js('https://www.googletagmanager.com/gtag/js?id=' . $this->tagId), true);

            $this->addJs((new Js(implode('', [
                'window.dataLayer = window.dataLayer || [];',
                'function gtag() {dataLayer.push(arguments);}',
                'gtag("js", new Date());'
            ]), true))->setPriority(1), true);
        }

        $this->addJs(new Js('gtag("config", "' . $this->tagId . '");', true), true);

        return $this;
    }
}