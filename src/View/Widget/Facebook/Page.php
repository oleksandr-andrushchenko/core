<?php

namespace SNOWGIRL_CORE\View\Widget\Facebook;

use SNOWGIRL_CORE\View\Node;
use SNOWGIRL_CORE\View\Widget\Facebook;

/**
 * Class Page
 *
 * @package SNOWGIRL_CORE\View\Widget\Facebook
 * @see     https://developers.facebook.com/docs/plugins/page-plugin
 */
class Page extends Facebook
{
    public const TAB_TIMELINE = 'timeline';
    public const TAB_EVENTS = 'events';
    public const TAB_MESSAGES = 'messages';

    protected $width = 250;
    protected $height;
    protected $tabs = [];
    protected $cover = true;
    protected $faces = true;
    protected $small = true;
    protected $adapt = false;
    protected $href;

    protected function getNode(): ?Node
    {
        $attr = [];

        if (is_int($this->width)) {
            $attr['data-width'] = $this->width;
        }

        if (is_int($this->height)) {
            $attr['data-height'] = $this->height;
        }

        return $this->makeNode('div', array_merge($attr, [
            'class' => $this->getDomClass() . ' fb-page',
            'data-href' => $this->href ?: ('https://www.facebook.com/' . $this->page),
            'data-tabs' => is_array($this->tabs) ? implode(',', $this->tabs) : self::TAB_TIMELINE,
            'data-hide-cover' => $this->cover ? 'false' : 'true',
            'data-show-facepile' => $this->faces ? 'true' : 'false',
            'data-small-header' => $this->small ? 'true' : 'false',
            'data-adapt-container-width' => $this->adapt ? 'true' : 'false'
        ]));
    }

    public function isOk(): bool
    {
        return ($this->href || $this->page) && $this->appId;
    }
}