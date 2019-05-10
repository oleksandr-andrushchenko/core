<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 11/7/17
 * Time: 2:17 PM
 */
namespace SNOWGIRL_CORE\Exception\EntityAttr;

use SNOWGIRL_CORE\Exception\EntityAttr;

/**
 * Class MD5
 * @package SNOWGIRL_CORE\Exception\EntityAttr
 */
class MD5 extends EntityAttr
{
    protected function getTypeName()
    {
        return 'md5';
    }
}