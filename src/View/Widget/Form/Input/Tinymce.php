<?php

namespace SNOWGIRL_CORE\View\Widget\Form\Input;

use SNOWGIRL_CORE\View\Node;
use SNOWGIRL_CORE\View\Widget;
use SNOWGIRL_CORE\View\Widget\Form\Input;

class Tinymce extends Input
{
    protected $imageUploadUri;
    protected $language = 'ru';

    protected function makeParams(array $params = []): array
    {
        if (!isset($params['imageUploadUri'])) {
            $params['imageUploadUri'] = $this->app->router->makeLink('admin', ['action' => 'img']);
        }

        return $params;
    }

    public function getCoreDomClass(): string
    {
        return 'widget-tinymce';
    }

    protected function addScripts(): Widget
    {
        parent::addScripts();

        return parent::addScripts()
            ->addCoreScripts()
            ->addJsScript('/js/core/tinymce/tinymce.min.js')
//            ->addJsScript('/js/core/bootstrap-validator.min.js')
            ->addJsScript('@core/widget/tinymce.js')
            ->addClientScript('tinymce', $this->getClientOptions([
                'name',
                'language',
                'imageUploadUri',
                'required'
            ]));
    }

    protected function getNode(): ?Node
    {
        return $this->makeNode('div', [
            'class' => 'form-control ' . $this->getDomClass(),
            'id' => $this->getDomId(),
            'style' => 'height:auto!important;padding:0!important'
        ]);
    }

    protected function getInner(string $template = null): ?string
    {
        return $this->makeNode('textarea', array_merge([
            'autocomplete' => 'off',
            'class' => 'form-control ' . $this->getDomClass(),
            'name' => $this->name,
            'id' => $this->getDomId(),
            'placeholder' => $this->makeText($this->name . '_placeholder'),
            'aria-label' => $this->makeText($this->name),
            'type' => $this->type
        ], $this->attrs))
            ->append($this->makeValue());
    }
}