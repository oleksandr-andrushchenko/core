<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 7/4/17
 * Time: 12:33 AM
 */
namespace SNOWGIRL_CORE\Exception\EntityAttr;

use SNOWGIRL_CORE\Exception\EntityAttr;

/**
 * Class Email
 * @package SNOWGIRL_CORE\Exception\EntityAttr
 */
class Email extends EntityAttr
{
    protected function getTypeName()
    {
        return 'email';
    }
}