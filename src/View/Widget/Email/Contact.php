<?php

namespace SNOWGIRL_CORE\View\Widget\Email;

use SNOWGIRL_CORE\View\Widget;
use SNOWGIRL_CORE\View\Widget\Email;

class Contact extends Email
{
    protected $body;

    protected function makeTemplate(): string
    {
        return '@core/widget/email/contact.phtml';
    }

    protected function addTexts(): Widget
    {
        return parent::addTexts()->addText('widget.email.contact');
    }
}