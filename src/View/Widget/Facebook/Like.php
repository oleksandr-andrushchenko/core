<?php

namespace SNOWGIRL_CORE\View\Widget\Facebook;

use SNOWGIRL_CORE\View\Node;
use SNOWGIRL_CORE\View\Widget\Facebook;

/**
 * Class Like
 *
 * @package SNOWGIRL_CORE\View\Widget\Facebook
 * @see     https://developers.facebook.com/docs/plugins/like-button
 */
class Like extends Facebook
{
    protected $height;
    protected $width;
    protected $layout = 'standard';
    protected $size = 'small';
    protected $faces = true;
    protected $href;

    protected function getNode(): ?Node
    {
        return $this->makeNode('div', [
            'class' => $this->getDomClass() . ' fb-like',
            'data-href' => $this->href ?: ('https://www.facebook.com/' . $this->page),
            'data-layout' => $this->layout,
            'data-action' => 'like',
            'data-size' => $this->size,
            'data-show-faces' => $this->faces ? 'true' : 'false',
            'style' => 'display:inline-block;max-width:100%;overflow:hidden'
        ]);
    }

    public function isOk(): bool
    {
        return ($this->href || $this->page) && $this->appId;
    }
}