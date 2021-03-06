<?php

namespace SNOWGIRL_CORE\View\Widget\Form\Input\File;

use SNOWGIRL_CORE\View\Node;
use SNOWGIRL_CORE\View\Widget;
use SNOWGIRL_CORE\View\Widget\Form\Input;
use SNOWGIRL_CORE\Exception;

/**
 * @todo    multi...
 * Class Image
 * @package SNOWGIRL_CORE\View\Widget\Form\Input
 */
class Image extends Input
{
    protected $uri;
    protected $multiple = false;
    protected $formats = ['image/jpeg', 'image/png', 'image/jpg'];
    protected $delete = true;
    protected $addOnChange = true;
    protected $type = 'file';

    protected function makeParams(array $params = []): array
    {
        $params = parent::makeParams($params);

        if (isset($params['uri']) && $params['uri'] && !$params['uri'] = $this->getUri($params['uri'])) {
            throw new Exception('invalid "uri" tag input param');
        }

        if (isset($params['multiple']) && $params['multiple']) {
            throw new Exception('"multiple" param is not implemented yet');
        }

        if (!isset($params['uri']) || !$params['uri']) {
            $params['uri'] = $this->app->router->makeLink('admin', ['action' => 'img']);
        }

        return $params;
    }

    public function getCoreDomClass(): string
    {
        return 'widget-image';
    }

    protected function addScripts(): Widget
    {
        return parent::addScripts()
            ->addCoreScripts()
            ->addJsScript('@core/widget/image.js')
            ->addClientScript('image', $this->getClientOptions([
                'name',
                'uri',
                'multiple',
                'addOnChange',
                'formats',
                'required'
            ], [
                'value' => $this->getValue(true),
            ]));
    }

    protected function getUri($uri)
    {
        if (!$tmp = parse_url($uri)) {
            return false;
        }

        return $uri;
    }

    protected function getNode(): ?Node
    {
        return Widget::getNode();
    }

    protected function getInner(string $template = null): ?string
    {
        $value = $this->getValue(true);

        $input = $this->makeNode('input', array_merge([
            'autocomplete' => 'off',
            'class' => 'form-control',
            'name' => $this->name,
            'value' => $this->makeValue(),
            'placeholder' => $this->makeText($this->name . '_placeholder'),
            'aria-label' => $this->makeText($this->name),
            'type' => $this->type,
            'accept' => implode(',', $this->formats)
        ], $this->attrs));

        $buttons = $this->makeNode('span', ['class' => 'input-group-btn']);

        $buttons->append($this->makeNode('button', [
            'class' => 'btn btn-default btn-post',
            'type' => 'button',
            'html' => $this->makeNode('span', ['class' => 'fa fa-upload']),
            'style' => 'display:none'
        ]));

        if ($this->delete && !$this->required) {
            $buttons->append($this->makeNode('button', [
                'class' => 'btn btn-default btn-delete',
                'type' => 'button',
                'html' => $this->makeNode('span', ['class' => 'fa fa-trash-o']),
                'style' => $value ? '' : 'display:none'
            ]));
        }

        return implode('', [
            $this->makeNode('img', [
                'class' => 'preview',
                'src' => $value ? $this->app->images->getLinkByFile($value[0]) : '',
                'style' => 'max-width:100%' . ($value ? '' : ';display:none')
            ]),
            $this->makeNode('div', ['class' => 'message', 'style' => 'display:none']),
            $this->makeNode('div', ['class' => 'input-group'])
                ->append($input)
                ->append($buttons)
        ]);
    }
}