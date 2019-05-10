<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 9/26/17
 * Time: 2:46 PM
 */
namespace SNOWGIRL_CORE\View\Widget\Email;

use SNOWGIRL_CORE\View\Widget\Email;

/**
 * Class Contact
 * @package SNOWGIRL_CORE\View\Widget\Email
 */
class Contact extends Email
{
    protected $body;

    protected function makeTemplate()
    {
        return '@core/widget/email/contact.phtml';
    }

    protected function addTexts()
    {
        return parent::addTexts()
            ->addText('widget.email.contact');
    }
}