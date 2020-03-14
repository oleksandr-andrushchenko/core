<?php

namespace SNOWGIRL_CORE\View\Widget\Form\Input;

use DateTime as DateTimeValue;
use SNOWGIRL_CORE\Exception;
use SNOWGIRL_CORE\View\Node;
use SNOWGIRL_CORE\View\Widget;
use SNOWGIRL_CORE\View\Widget\Form\Input;

/**
 * Class DateTime
 *
 * @package SNOWGIRL_CORE\View\Widget\Form\Input
 * @property DateTimeValue value
 */
class DateTime extends Input
{
    protected $date = true;
    protected $time = true;

    protected function makeTemplate(): string
    {
        return '@core/widget/datetime/input.phtml';
    }

    protected function getFormat()
    {
        if (!$this->date && !$this->time) {
            throw new Exception('invalid "value" datetime input param');
        }

        if (!$this->date) {
            return $this->makeText('invalid "date" datetime input param');
        }

        if (!$this->time) {
            return $this->makeText('invalid "time" datetime input param');
        }

        return $this->makeText('invalid "format" datetime input param');
    }

    protected function getNode(): ?Node
    {
        return $this->makeNode('div', [
            'id' => $this->getDomId(),
            'class' => implode(' ', [$this->getDOMClass(), 'input-group'])
        ]);
    }

    protected function getInner(string $template = null): ?string
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

    protected function addScripts(): Widget
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