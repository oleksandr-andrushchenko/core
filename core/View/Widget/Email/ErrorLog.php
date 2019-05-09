<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 3/4/18
 * Time: 8:45 PM
 */
namespace SNOWGIRL_CORE\View\Widget\Email;

use SNOWGIRL_CORE\Exception;
use SNOWGIRL_CORE\View\Widget\Email;

/**
 * Class ErrorLog
 * @package SNOWGIRL_CORE\View\Widget\Email
 */
class ErrorLog extends Email
{
    protected $request;
    protected $referer;
    protected $agent;
    protected $error;

    protected function makeTemplate()
    {
        return '@snowgirl-core/widget/email/error-log.phtml';
    }

    protected function makeParams(array $params = [])
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
        return parent::addTexts()
            ->addText('widget.email.error-log');
    }
}