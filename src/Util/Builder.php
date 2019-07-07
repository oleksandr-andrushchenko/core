<?php

namespace SNOWGIRL_CORE\Util;

/**
 * Class Builder
 *
 * @property Database database
 * @property Image    images
 * @property Sphinx   sphinx
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
                return $this->app->getObject('Util\Image', $this->app);
            case 'sphinx':
                return $this->app->getObject('Util\Sphinx', $this->app);
            default:
                return parent::_get($k);
        }
    }

    public function get($class)
    {
        return new $class($this->app);
    }
}