<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 2/1/18
 * Time: 6:44 PM
 */
namespace SNOWGIRL_CORE\View\Widget\Ad;

use SNOWGIRL_CORE\View\Widget\Ad;

/**
 * Class LargeRectangle
 * @package SNOWGIRL_CORE\View\Widget\Ad
 */
class LargeRectangle extends Ad
{
    protected function getStyle()
    {
        return 'display:inline-block;width:336px;height:280px';
    }

    public function getCoreDomClass()
    {
        return 'widget-ad-large-rectangle';
    }
}