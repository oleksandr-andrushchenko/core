<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 7/4/17
 * Time: 12:46 AM
 */
namespace SNOWGIRL_CORE\Exception\EntityAttr;

use SNOWGIRL_CORE\Exception\EntityAttr;

/**
 * Class Required
 * @package SNOWGIRL_CORE\Exception\EntityAttr
 */
class Required extends EntityAttr
{
    protected function getTypeName()
    {
        return 'required';
    }
}