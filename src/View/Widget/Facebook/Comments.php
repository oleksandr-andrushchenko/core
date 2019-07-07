<?php

namespace SNOWGIRL_CORE\View\Widget\Facebook;

use SNOWGIRL_CORE\View\Widget\Facebook;

/**
 * Class Comments
 *
 * @package SNOWGIRL_CORE\View\Widget\Facebook
 * @see     https://developers.facebook.com/docs/plugins/comments
 */
class Comments extends Facebook
{
    protected $href;
    protected $width;
    protected $size = 5;

    protected function getNode()
    {
        $attr = [];

        if ($this->href) {
            $attr['data-href'] = $this->href;
        }

        if ($this->width) {
            $attr['data-width'] = $this->width;
        }

        if (is_int($this->size)) {
            $attr['data-numposts'] = $this->size;
        }

        return $this->makeNode('div', array_merge($attr, [
            'class' => $this->getDomClass() . ' fb-comments'
        ]));
    }
}