<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 2/27/17
 * Time: 12:36 AM
 */

namespace SNOWGIRL_CORE\Entity;

/**
 * Interface UserInterface
 * @package SNOWGIRL_CORE\Entity
 */
interface UserInterface
{
    public static function getPk();

    public function getId();

//    public function isRole();

//    public function getCountryIso();

    public function isRole($role1, $role2 = null);
}