<?php

namespace SNOWGIRL_CORE\Service;

use SNOWGIRL_CORE\App;
use SNOWGIRL_CORE\Service;

abstract class Transport extends Service
{
    use LogTrait;

    protected $sender;
    protected $receiver;
    protected $site;

    protected function initialize2(App $app = null)
    {
        parent::initialize2($app);

        $this->setOption('logger', $app->services->logger)
            ->setOption('site', $app->getSite());
    }

    public function setReceiver($v)
    {
        $this->receiver = $v;
        return $this;
    }

    /**
     * @param      $subject
     * @param null $body
     *
     * @return bool
     */
    public function transfer($subject, $body = null)
    {
        $this->log(implode(' ', [
            __FUNCTION__,
            'subject="' . $subject . '"',
            'body="' . $body . '"'
        ]));

        return $this->_transfer($subject, $body);
    }

    abstract protected function _transfer($subject, $body = null);
}