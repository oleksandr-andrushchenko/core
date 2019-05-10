<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 9/24/17
 * Time: 9:40 AM
 */

namespace SNOWGIRL_CORE\Manager;

use SNOWGIRL_CORE\Entity;
use SNOWGIRL_CORE\Manager;
use SNOWGIRL_CORE\Manager\Page\Regular as RegularPages;
use SNOWGIRL_CORE\Manager\Page\Custom as CustomPages;

/**
 * Class Builder
 * @property User users
 * @property Contact contacts
 * @property Redirect redirects
 * @property Banner banners
 * @property Subscribe subscribes
 * @property RegularPages pagesRegular
 * @property CustomPages pagesCustom
 * @package SNOWGIRL_CORE\Manager
 */
class Builder extends \SNOWGIRL_CORE\Builder
{
    protected function _get($k)
    {
        switch ($k) {
            case 'users':
                return $this->get(User::class);
            case 'contacts':
                return $this->get(Contact::class);
            case 'redirects':
                return $this->get(Redirect::class);
            case 'banners':
                return $this->get(Banner::class);
            case 'subscribes':
                return $this->get(Subscribe::class);
            case 'pagesRegular':
                return $this->get(RegularPages::class);
            case 'pagesCustom':
                return $this->get(CustomPages::class);
            default:
                return parent::_get($k);
        }
    }

    /**
     * @param $class
     * @return Manager
     */
    public function get($class)
    {
        return new $class($this->app);
    }

    protected $classes = [];

    /**
     * @param $table
     * @return Manager
     */
    public function getByTable($table)
    {
        if (isset($this->classes[$table])) {
            $class = $this->classes[$table];
        } else {
            $class = implode('\\', array_merge(['Manager'], explode(' ', ucwords(str_replace('_', ' ', $table)))));
            $class = $this->app->findClass($class);
        }

        return $this->get($class);
    }

    /**
     * @param string|Entity $class
     * @return Manager
     */
    public function getByEntityClass($class)
    {
        return $this->getByTable($class::getTable());
    }

    /**
     * @param Entity $entity
     * @return Manager
     */
    public function getByEntity(Entity $entity)
    {
        return $this->getByTable($entity->getTable());
    }

    /**
     * @param $pk
     * @return Manager
     */
    public function getByEntityPk($pk)
    {
        return $this->getByTable(str_replace('_id', '', $pk));
    }
}