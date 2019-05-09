<?php
/**
 * Created by JetBrains PhpStorm.
 * User: snowgirl
 * Date: 24.03.15
 * Time: 15:19
 * To change this template use File | Settings | File Templates.
 */
namespace SNOWGIRL_CORE\View\Widget\Form\Input;

use SNOWGIRL_CORE\View\Widget\Form;
use SNOWGIRL_CORE\View\Widget\Form\Input;

/**
 * Class Media
 * @package SNOWGIRL_CORE\View\Widget\Form\Input
 */
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
            T('attach')
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