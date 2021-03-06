<?php

namespace SNOWGIRL_CORE\View\Widget\Email;

use SNOWGIRL_CORE\View\Widget;
use SNOWGIRL_CORE\View\Widget\Email;

class Subscribe extends Email
{
    protected function makeTemplate(): string
    {
        return '@core/widget/email/subscribe.phtml';
    }

    protected function addTexts(): Widget
    {
        return parent::addTexts()->addText('widget.email.subscribe');
    }
}