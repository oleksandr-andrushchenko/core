<?php

namespace SNOWGIRL_CORE\View\Widget;

use SNOWGIRL_CORE\View\Widget;

class AddThisSharer extends Widget
{
    protected $key;
    protected $ukraine;

    protected function makeTemplate()
    {
        return '@core/widget/add-this.sharer.phtml';
    }

    protected function makeParams(array $params = [])
    {
        return array_merge(parent::makeParams($params), [
            'key' => $this->app->config->keys->addthis_key(false),
            'ukraine' => $params['ukraine'] ?? false
        ]);
    }

    protected function getNode()
    {
        return $this->makeNode('div', [
            'class' => 'addthis_inline_share_toolbox' . ($this->ukraine ? '_51a7' : ''),
            'style' => 'display:inline-block;width:100%;padding:5px'
        ]);
    }

    protected function getInner($template = null)
    {
        return null;
    }

    protected function addScripts()
    {
        return parent::addScripts()
            ->addJsScript('//s7.addthis.com/js/300/addthis_widget.js#pubid=ra-' . $this->key);
    }

    public function isOk()
    {
        return $this->key;
    }
}