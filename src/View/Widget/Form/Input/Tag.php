<?php

namespace SNOWGIRL_CORE\View\Widget\Form\Input;

use SNOWGIRL_CORE\Exception;
use SNOWGIRL_CORE\View\Widget;
use SNOWGIRL_CORE\View\Widget\Form\Input;

/**
 * Class Tag
 *
 * @package SNOWGIRL_CORE\View\Widget\Form\Input
 * @see     https://bootstrap-tagsinput.github.io/bootstrap-tagsinput/examples/
 */
class Tag extends Input
{
    public const WILDCARD = '%query';

    protected $uri;
    protected $multiple = false;
    protected $valueKey = 'id';
    protected $labelKey = 'name';
    protected $template = '{value}-{label}';
    protected $placeholder;

    protected function makeParams(array $params = [])
    {
        if (isset($params['uri']) && $params['uri'] && !$params['uri'] = $this->getUri($params['uri'])) {
            throw new Exception('invalid "uri" tag input param');
        }

        return $params;
    }

    public function getCoreDomClass()
    {
        return 'widget-tag';
    }

    protected function addScripts()
    {
        return parent::addScripts()
//            ->addCoreScripts()
            ->addJsScript('@core/widget/tag.js')
            ->addClientScript('tag', $this->getClientOptions([
                'name',
                'uri',
                'multiple',
                'valueKey',
                'labelKey'
            ], [
                'wildcard' => self::WILDCARD,
                'value' => array_map(function ($v) {
                    if ($v instanceof Value) {
                        return [
                            $this->valueKey => $v->value,
                            $this->labelKey => $v->label ?: $v->value
                        ];
                    }

                    return [
                        $this->valueKey => $v,
                        $this->labelKey => $v
                    ];
                }, $this->getValue(true)),
                'trimValue' => true
            ]));
    }

    protected function getUri($uri)
    {
        if (!$tmp = parse_url($uri)) {
            return false;
        }

        $uri .= isset($tmp['query']) ? '&' : '?';

        if (false === strpos($this->uri, self::WILDCARD)) {
            $uri .= 'query=' . self::WILDCARD;
        }

        return $uri;
    }

    protected function getNode()
    {
        return Widget::getNode();
    }

    protected function getInner($template = null)
    {
        return $this->makeNode('input', array_merge([
            'autocomplete' => 'off',
            'class' => 'form-control',
            'name' => $this->name,
            'value' => $this->makeValue(),
            'placeholder' => $this->placeholder ?: trans($this->name . '_placeholder'),
            'aria-label' => trans($this->name),
            'type' => $this->type
        ], $this->attrs));
    }
}