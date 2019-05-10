<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 2/21/17
 * Time: 5:34 AM
 */
namespace SNOWGIRL_CORE\Exception;

use SNOWGIRL_CORE\Exception;

/**
 * Class Form
 * @package SNOWGIRL_CORE\Exception
 */
class Form extends Exception
{
    protected $field;
    protected $message;
    protected $form;

    public function __construct($field, $message, \SNOWGIRL_CORE\View\Widget\Form $form = null)
    {
        parent::__construct($message);

        $this->field = $field;
        $this->form = $form;
    }

    public function getField()
    {
        return $this->field;
    }

    public function getForm()
    {
        return $this->form;
    }
}