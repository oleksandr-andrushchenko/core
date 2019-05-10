<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 11/2/16
 * Time: 1:20 AM
 */
namespace SNOWGIRL_CORE\Manager;

use SNOWGIRL_CORE\Manager;
use SNOWGIRL_CORE\Entity\User as UserEntity;

/**
 * Class User
 * @property UserEntity $entity
 * @method static UserEntity getItem($id)
 * @method User setWhere($where)
 * @method UserEntity getObject()
 * @method UserEntity[] getObjects($idAsKeyOrKey = null)
 * @method UserEntity find($id)
 * @package SNOWGIRL_CORE\Manager
 */
class User extends Manager
{
    protected $masterServices = false;
}