<?php
/**
 * Created by JetBrains PhpStorm.
 * User: snowgirl
 * Date: 14.06.15
 * Time: 19:48
 * To change this template use File | Settings | File Templates.
 */
namespace SNOWGIRL_CORE\View\Widget\Form\Input;

use SNOWGIRL_CORE\DateTime as DateTimeValue;
use SNOWGIRL_CORE\Exception;
use SNOWGIRL_CORE\View\Widget\Form;
use SNOWGIRL_CORE\View\Widget\Form\Input;

/**
 * Class DateTime
 * @package SNOWGIRL_CORE\View\Widget\Form\Input
 * @property DateTimeValue value
 */
class DateTime extends Input
{
    protected $date = true;
    protected $time = true;

    protected function makeTemplate()
    {
        return '@snowgirl-core/widget/datetime/input.phtml';
    }

    protected function getFormat()
    {
        if (!$this->date && !$this->time) {
            throw new Exception('invalid "value" datetime input param');
        }

        if (!$this->date) {
            return T('invalid "date" datetime input param');
        }

        if (!$this->time) {
            return T('invalid "time" datetime input param');
        }

        return T('invalid "format" datetime input param');
    }

    protected function getNode()
    {
        return $this->makeNode('div', [
            'id' => $this->getDomId(),
            'class' => implode(' ', [$this->getDOMClass(), 'input-group'])
        ]);
    }

    protected function getInner($template = null)
    {
        return implode(' ', [
            $this->makeNode('span', ['class' => 'input-group-btn'])
                ->append($this->makeNode('button', ['class' => 'btn btn-default', 'type' => 'button'])
                    ->append($this->makeNode('span', ['class' => 'fa fa-fw fa-calendar-o'])))
                ->stringify(),
            $this->getForm()->getInput($this->name)
                ->stringify()
        ]);
    }

    protected function addScripts()
    {
        return parent::addScripts()
            ->addCoreScripts()
            //@todo add datetime.js
            ->addClientScript('datetime', $this->getClientOptions([
                'name',
                'date',
                'time'
            ], [
                'value' => $this->value ? $this->value->getTimestamp() : null,
                'format' => $this->getFormat(),
            ]));
    }
}