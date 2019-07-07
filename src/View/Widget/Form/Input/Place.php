<?php

namespace SNOWGIRL_CORE\View\Widget\Form\Input;

use SNOWGIRL_CORE\View\Widget\Form\Input;

class Place extends Input
{
    protected function getInner($template = null)
    {
        return implode(' ', [
            $this->makeNode('div', ['class' => 'input-group'])
                ->append($this->makeNode('span', ['class' => 'input-group-btn'])
                    ->append($this->makeNode('a', ['class' => 'btn btn-default'])
                        ->append($this->makeNode('span', ['class' => 'fa fa-fw fa-map-marker']))))
                ->append($this->getForm()->getInput($this->getOption('name'), $this->getOption('value')[$this->getOption('name')]))
                ->stringify(),
            $this->makeNode('div', ['class' => 'collapse', 'style' => 'margin-top:3px'])
                ->append($this->makeNode('div', ['class' => 'map thumbnail', 'style' => 'height:200px']))
                ->stringify()
        ]);
    }
}