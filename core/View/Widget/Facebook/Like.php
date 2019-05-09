<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 10/10/17
 * Time: 5:06 PM
 */
namespace SNOWGIRL_CORE\View\Widget\Facebook;

use SNOWGIRL_CORE\Exception;
use SNOWGIRL_CORE\View;
use SNOWGIRL_CORE\View\Widget;
use SNOWGIRL_CORE\View\Widget\Facebook;

/**
 * Class Like
 * @package SNOWGIRL_CORE\View\Widget\Facebook
 * @see https://developers.facebook.com/docs/plugins/like-button
 */
class Like extends Facebook
{
    protected $height;
    protected $width;
    protected $layout = 'standard';
    protected $size = 'small';
    protected $faces = true;
    protected $href;

    protected function getNode()
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

    public function isOk()
    {
        return ($this->href || $this->page) && $this->appId;
    }
}