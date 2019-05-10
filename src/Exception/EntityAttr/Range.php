<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 4/9/18
 * Time: 2:35 PM
 */
namespace SNOWGIRL_CORE\Exception\EntityAttr;

use SNOWGIRL_CORE\Exception\EntityAttr;

/**
 * Class Range
 * @package SNOWGIRL_CORE\Exception\EntityAttr
 */
class Range extends EntityAttr
{
    protected function getTypeName()
    {
        return 'range';
    }
}