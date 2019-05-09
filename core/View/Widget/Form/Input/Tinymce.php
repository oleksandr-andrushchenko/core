<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 2/4/18
 * Time: 4:23 AM
 */
namespace SNOWGIRL_CORE\View\Widget\Form\Input;

use SNOWGIRL_CORE\View\Widget;
use SNOWGIRL_CORE\View\Widget\Form\Input;

/**
 * Class Tinymce
 * @package SNOWGIRL_CORE\View\Widget\Form\Input
 */
class Tinymce extends Input
{
    protected $imageUploadUri;

    protected function makeParams(array $params = [])
    {
        if (!isset($params['imageUploadUri'])) {
            $params['imageUploadUri'] = $this->app->router->makeLink('admin', ['action' => 'img']);
        }

        return $params;
    }

    public function getCoreDomClass()
    {
        return 'widget-tinymce';
    }

    protected function addScripts()
    {
        parent::addScripts();

        return parent::addScripts()
            ->addCoreScripts()
            ->addJsScript('//cdn.tinymce.com/4/tinymce.min.js')
//            ->addJsScript('/js/snowgirl-core/bootstrap-validator.min.js')
            ->addJsScript('@snowgirl-core/widget/tinymce.js')
            ->addClientScript('tinymce', $this->getClientOptions([
                'name',
                'imageUploadUri',
                'required'
            ]));
    }

    protected function getNode()
    {
        return $this->makeNode('div', [
            'class' => 'form-control ' . $this->getDomClass(),
            'id' => $this->getDomId(),
            'style' => 'height:auto!important;padding:0!important'
        ]);
    }

    protected function getInner($template = null)
    {
        return $this->makeNode('textarea', array_merge([
            'autocomplete' => 'off',
            'class' => 'form-control ' . $this->getDomClass(),
            'name' => $this->name,
            'id' => $this->getDomId(),
            'placeholder' => T($this->name . '_placeholder'),
            'aria-label' => T($this->name),
            'type' => $this->type
        ], $this->attrs))
            ->append($this->makeValue());
    }
}