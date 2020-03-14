<?php

namespace SNOWGIRL_CORE\SEO;

use Monolog\Logger;
use SNOWGIRL_CORE\SEO;

class Pages
{
    /** @var SEO */
    protected $seo;

    public function __construct(SEO $seo)
    {
        $this->seo = $seo;

        $this->initialize();
    }

    /**
     * @return Pages
     */
    protected function initialize()
    {
        return $this;
    }

    public function update()
    {
        return true;
    }

    protected function log($msg, $type = Logger::DEBUG)
    {
        $this->seo->getApp()->container->logger->addRecord($type, 'seo-pages: ' . $msg);
    }
}