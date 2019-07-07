<?php

namespace SNOWGIRL_CORE\Image;

use SNOWGIRL_CORE\Image;

class Builder extends \SNOWGIRL_CORE\Builder
{
    public function get($file)
    {
        return new Image($file);
    }
}