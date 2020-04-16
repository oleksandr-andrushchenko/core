<?php

namespace SNOWGIRL_CORE\View\Widget;

use SNOWGIRL_CORE\View\Widget;
use SNOWGIRL_CORE\Helper\Data as DataHelper;

abstract class Email extends Widget
{
    protected $user;
    protected $site;
    protected $siteLink;

    protected function makeParams(array $params = []): array
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

    protected function addTexts(): Widget
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
        return $this->app->container->mailer->send($address, $this->texts['subject'], $this->stringify());
    }

    public function processNotifiers()
    {
        foreach ($this->app->config('app.notify_email', []) as $notifyEmail) {
            $this->setParam('user', 'Notifier')
                ->process($notifyEmail);
        }

        return $this;
    }
}