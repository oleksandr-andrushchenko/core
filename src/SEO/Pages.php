<?php

namespace SNOWGIRL_CORE\SEO;

use SNOWGIRL_CORE\SEO;
use SNOWGIRL_CORE\Service\Logger;

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

    protected function log($msg, $type = Logger::TYPE_DEBUG)
    {
        $this->seo->getApp()->services->logger->make('seo-pages: ' . $msg, $type);
    }
}