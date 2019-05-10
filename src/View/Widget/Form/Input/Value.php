<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 11/1/17
 * Time: 4:32 PM
 */
namespace SNOWGIRL_CORE\View\Widget\Form\Input;

/**
 * Class Value
 * @package SNOWGIRL_CORE\View\Widget\Form\Input
 */
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