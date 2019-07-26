<?php

namespace SNOWGIRL_CORE\View\Widget;

use SNOWGIRL_CORE\View\Widget;
use SNOWGIRL_CORE\Helper\Data as DataHelper;

use SNOWGIRL_CORE\Service\Transport\Email as EmailTransport;

abstract class Email extends Widget
{
    protected $user;
    protected $site;
    protected $siteLink;

    protected function makeParams(array $params = [])
    {
        $params = array_merge([
            'site' => $this->app->getSite(),
            'siteLink' => $siteLink = $this->app->router->makeLink('default', [], 'master')
        ], parent::makeParams($params));

        if (isset($params['user'])) {
            $params['user'] = DataHelper::ucWords($params['user']);
        }

        return $params;
    }

    protected function addTexts()
    {
        return parent::addTexts()->addText('widget.email');
    }

    protected function stringifyPrepare()
    {
        if (!$this->user) {
            $this->user = $this->texts['user'];
        }

        $this->content = $this->stringifyContent(null);
        $this->template = 'widget/email.phtml';

        return parent::stringifyPrepare();
    }

    public function process($address)
    {
        $transport = $this->app->services->transport;

        if (!$transport instanceof EmailTransport) {
            $transport = $this->app->services->transport('email');
        }

        /** @var EmailTransport $transport */

        return $transport
            ->setReceiver($address)
            ->transfer($this->texts['subject'], $this->stringify());
    }

    public function processNotifiers()
    {
        foreach ($this->app->config->app->notify_email([]) as $notifyEmail) {
            $this->setParam('user', 'Notifier')
                ->process($notifyEmail);
        }

        return $this;
    }
}