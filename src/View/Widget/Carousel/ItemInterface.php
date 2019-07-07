<?php

namespace SNOWGIRL_CORE\View\Widget\Carousel;

use SNOWGIRL_CORE\App;

interface ItemInterface
{
    public function getHref(App $app);

    public function getImageHash();

    public function getCaption();
}