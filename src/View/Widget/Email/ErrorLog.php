<?php

namespace SNOWGIRL_CORE\View\Widget\Email;

use SNOWGIRL_CORE\Exception;
use SNOWGIRL_CORE\View\Widget\Email;

class ErrorLog extends Email
{
    protected $request;
    protected $referer;
    protected $agent;
    protected $error;

    protected function makeTemplate(): string
    {
        return '@core/widget/email/error-log.phtml';
    }

    protected function makeParams(array $params = []): array
    {
        $params = array_merge(parent::makeParams($params), [
            'referer' => $this->app->request->getReferer(),
            'request' => $this->app->request->getUri(),
            'agent' => $this->app->request->getUserAgent()
        ]);

        if (!isset($params['error'])) {
            throw new Exception('invalid "error" param');
        }

        return $params;
    }

    protected function addTexts()
    {
        return parent::addTexts()->addText('widget.email.error-log');
    }
}