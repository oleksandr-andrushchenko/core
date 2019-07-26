<?php

namespace SNOWGIRL_CORE\View\Widget\Email;

use SNOWGIRL_CORE\View\Widget\Email;

class Contact extends Email
{
    protected $body;

    protected function makeTemplate()
    {
        return '@core/widget/email/contact.phtml';
    }

    protected function addTexts()
    {
        return parent::addTexts()->addText('widget.email.contact');
    }
}