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

    protected function getNode()
    {
        return $this->makeNode('button', [
            'id' => $this->getDomId(),
            'class' => 'btn btn-default ' . $this->getDOMClass(),
            'type' => 'button'
        ]);
    }

    protected function getInner($template = null)
    {
        return implode(' ', [
            $this->makeNode('span', ['class' => 'fa fa-paperclip']),
            trans('attach')
        ]);
    }

    protected function addScripts()
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