<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 2/13/18
 * Time: 4:08 PM
 */
namespace SNOWGIRL_CORE\View\Widget\Carousel;
use SNOWGIRL_CORE\App;

/**
 * Interface ItemInterface
 * @package SNOWGIRL_CORE\View\Widget\Carousel
 */
interface ItemInterface
{
    public function getHref(App $app);

    public function getImageHash();

    public function getCaption();
}