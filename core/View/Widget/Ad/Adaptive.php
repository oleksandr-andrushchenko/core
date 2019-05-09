<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 9/23/17
 * Time: 3:45 PM
 */
namespace SNOWGIRL_CORE\View\Widget\Ad;

use SNOWGIRL_CORE\View\Widget\Ad;

/**
 * Class Adaptive
 * @package SNOWGIRL_CORE\View\Widget\Ad
 */
class Adaptive extends Ad
{
    protected function getStyle()
    {
        return 'display:block';
    }

    public function getCoreDomClass()
    {
        return 'widget-ad-adaptive';
    }
}