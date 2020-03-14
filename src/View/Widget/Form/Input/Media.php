<?php

namespace SNOWGIRL_CORE\View\Widget\Form\Input;

use SNOWGIRL_CORE\View\Widget\Form\Input;

class Media extends Input
{
    protected $items = [];
    protected $progressSelector;
    protected $mediaListWidgetSelector;
    protected $multiple = false;
    protected $default;
    protected $cover = false;

    protected function getNode(): ?Node
    {
        return $this->makeNode('button', [
            'id' => $this->getDomId(),
            'class' => 'btn btn-default ' . $this->getDOMClass(),
            'type' => 'button'
        ]);
    }

    protected function getInner(string $template = null): ?string
    {
        return implode(' ', [
            $this->makeNode('span', ['class' => 'fa fa-paperclip']),
            $this->makeText('attach')
        ]);
    }

    protected function addScripts(): Widget
    {
        return parent::addScripts()
            ->addCoreScripts()
            //@todo add media.js
            ->addClientScript('media', $this->getClientOptions([
                'mediaListWidgetSelector',
                'progressSelector',
                'name',
                'items',
                'multiple',
                'default',
                'cover'
            ]));
    }
}