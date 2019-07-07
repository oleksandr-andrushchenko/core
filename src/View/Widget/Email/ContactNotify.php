<?php

namespace SNOWGIRL_CORE\View\Widget\Email;

use SNOWGIRL_CORE\View\Widget\Email;

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