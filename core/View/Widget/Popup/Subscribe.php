<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 12/30/17
 * Time: 12:41 AM
 */
namespace SNOWGIRL_CORE\View\Widget\Popup;

use SNOWGIRL_CORE\View\Widget\Popup;

/**
 * Class Subscribe
 * @package SNOWGIRL_CORE\View\Widget\Popup
 */
class Subscribe extends Popup
{
    protected $title = 'Подписка';
    //@todo lazy loading... do not stringify widgets in case of already showed
    protected $body = '';
    protected $modal = true;
    protected $showIn;
    protected $showedCookie = 'subscriber_popup_showed';

    protected function makeParams(array $params = [])
    {
        return parent::makeParams([
            'showIn' => (int)$this->app->config->app->show_subscribe_popup_in(20)
        ]);
    }

    protected function getInner($template = null)
    {
        return implode($this->makeNode('div', ['class' => 'header', 'text' => '-или-']), array_filter([
            $this->app->views->subscribeForm([], $this->getLayout())->stringify(),
            $this->app->views->vkontaktePage($this->getLayout())->stringify(),
            $this->app->views->facebookPage($this->getLayout())->stringify()
        ], function ($v) {
            return 0 < strlen($v);
        }));
    }

    protected function addScripts()
    {
        return parent::addScripts()
            ->addCssScript('@snowgirl-core/widget/popup.subscribe.css');
    }

    protected function stringifyPrepare()
    {
        $this->addDomClass('subscribe');
        return parent::stringifyPrepare();
    }
}