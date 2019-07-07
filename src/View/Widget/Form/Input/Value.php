<?php

namespace SNOWGIRL_CORE\View\Widget\Form\Input;

class Value
{
    public $value;
    public $label;

    public function __construct($value, $label = null)
    {
        $this->value = $value;
        $this->label = $label;
    }

    public function __toString()
    {
        return (string)$this->value;
    }
}