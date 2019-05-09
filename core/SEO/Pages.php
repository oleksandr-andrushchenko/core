<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 3/5/18
 * Time: 11:59 PM
 */
namespace SNOWGIRL_CORE\SEO;

use SNOWGIRL_CORE\SEO;
use SNOWGIRL_CORE\Service\Logger;

/**
 * Class Pages
 * @package SNOWGIRL_CORE\SEO
 */
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