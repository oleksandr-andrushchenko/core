<?php

namespace SNOWGIRL_CORE\View\Widget\Carousel;

use SNOWGIRL_CORE\AbstractApp;

interface ItemInterface
{
    public function getHref(AbstractApp $app);

    public function getImageHash();

    public function getCaption();
}