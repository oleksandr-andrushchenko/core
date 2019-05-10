<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 12/21/17
 * Time: 5:24 PM
 */
namespace SNOWGIRL_CORE\View\Widget\Email;

use SNOWGIRL_CORE\View\Widget\Email;

/**
 * Email subscription confirmation
 *
 * Class Subscribe
 * @package SNOWGIRL_CORE\View\Widget\Email
 */
class Subscribe extends Email
{
    protected function makeTemplate()
    {
        return '@core/widget/email/subscribe.phtml';
    }

    protected function addTexts()
    {
        return parent::addTexts()
            ->addText('widget.email.subscribe');
    }
}