<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 9/23/17
 * Time: 9:35 PM
 */
namespace SNOWGIRL_CORE\Image;

use SNOWGIRL_CORE\Image;

/**
 * Class Builder
 * @package SNOWGIRL_CORE\Image
 */
class Builder extends \SNOWGIRL_CORE\Builder
{
    public function get($file)
    {
        return new Image($file);
    }
}