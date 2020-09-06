<?php

namespace SNOWGIRL_CORE\Util;

use SNOWGIRL_SHOP\Util\Sitemap;

/**
 * Class Builder
 * @property Database database
 * @property Image images
 * @property Sitemap sitemap
 * @property RobotsTxt robotsTxt
 * @package SNOWGIRL_CORE\Util
 */
class Builder extends \SNOWGIRL_CORE\Builder
{
    protected function _get($k)
    {
        switch ($k) {
            case 'database':
                return $this->get(Database::class);
            case 'images':
                return $this->app->container->getObject('Util\Image', $this->app);
            case 'sitemap':
                return $this->get(Sitemap::class);
            case 'robotsTxt':
                return $this->get(RobotsTxt::class);
            default:
                return parent::_get($k);
        }
    }

    public function get($class, $debug = null)
    {
        return new $class($this->app, $debug);
    }
}