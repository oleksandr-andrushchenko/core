<?php

namespace SNOWGIRL_CORE\View\Widget;

use SNOWGIRL_CORE\View\Node;
use SNOWGIRL_CORE\View\Widget;

class AddThisSharer extends Widget
{
    protected $key;
    protected $ukraine;

    protected function makeTemplate(): string
    {
        return '@core/widget/add-this.sharer.phtml';
    }

    protected function makeParams(array $params = []): array
    {
        return array_merge(parent::makeParams($params), [
            'key' => $this->app->config('keys.addthis_key', false),
            'ukraine' => $params['ukraine'] ?? false
        ]);
    }

    protected function getNode(): ?Node
    {
        return $this->makeNode('div', [
            'class' => 'addthis_inline_share_toolbox' . ($this->ukraine ? '_51a7' : ''),
            'style' => 'display:inline-block;width:100%;padding:5px'
        ]);
    }

    protected function getInner(string $template = null): ?string
    {
        return null;
    }

    protected function addScripts(): Widget
    {
        return parent::addScripts()
            ->addJsScript('//s7.addthis.com/js/300/addthis_widget.js#pubid=ra-' . $this->key);
    }

    public function isOk(): bool
    {
        return !!$this->key;
    }
}