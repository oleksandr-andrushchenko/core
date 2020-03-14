<?php

namespace SNOWGIRL_CORE\View\Widget\Google;

use ReCaptcha\ReCaptcha;
use SNOWGIRL_CORE\AbstractRequest;
use SNOWGIRL_CORE\View\Node;
use SNOWGIRL_CORE\View\Widget;
use SNOWGIRL_CORE\View\Widget\Google;

class Captcha extends Google
{
    protected $key;
    protected $secret;

    protected function makeParams(array $params = []): array
    {
        return array_merge(parent::makeParams($params), [
            'key' => $this->app->config('keys.google_recaptcha_key', false),
            'secret' => $this->app->config('keys.google_recaptcha_secret', false)
        ]);
    }

    protected function getNode(): ?Node
    {
        return $this->makeNode('div', [
            'class' => $this->getDomClass(),
            'data-sitekey' => $this->key
        ]);
    }

    protected function addScripts(): Widget
    {
        return parent::addScripts()
            ->addJsScript('https://www.google.com/recaptcha/api.js');
    }

    protected function stringifyPrepare()
    {
        $this->addDomClass('g-recaptcha');
        return parent::stringifyPrepare();
    }

    public function verify(AbstractRequest $request): bool
    {
        $captcha = new ReCaptcha($this->secret);

        $response = $captcha->verify(
            $request->get('g-recaptcha-response'),
            $request->getServer('REMOTE_ADDR')
        );

        return $response->isSuccess();
    }

    public function isOk(): bool
    {
        return $this->key && $this->secret;
    }
}