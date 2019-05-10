<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 9/26/17
 * Time: 2:47 PM
 */
namespace SNOWGIRL_CORE\View\Widget\Email;

use SNOWGIRL_CORE\View\Widget\Email;

/**
 * Class ContactNotify
 * @package SNOWGIRL_CORE\View\Widget\Email
 */
class ContactNotify extends Email
{
    protected $name;
    protected $email;
    protected $body;

    protected function makeTemplate()
    {
        return '@core/widget/email/contact.notify.phtml';
    }

    protected function addTexts()
    {
        return parent::addTexts()
            ->addText('widget.email.contact.notify');
    }
}