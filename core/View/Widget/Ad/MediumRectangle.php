<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 1/26/18
 * Time: 2:32 PM
 */
namespace SNOWGIRL_CORE\View\Widget\Ad;

use SNOWGIRL_CORE\View\Widget\Ad;

/**
 * Class MediumRectangle
 * @package SNOWGIRL_CORE\View\Widget\Ad
 */
class MediumRectangle extends Ad
{
    protected function getStyle()
    {
        return 'display:inline-block;width:300px;height:250px';
    }

    public function getCoreDomClass()
    {
        return 'widget-ad-medium-rectangle';
    }
}