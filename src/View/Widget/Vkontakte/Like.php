<?php

namespace SNOWGIRL_CORE\View\Widget\Vkontakte;

use SNOWGIRL_CORE\View\Node;
use SNOWGIRL_CORE\View\Widget;
use SNOWGIRL_CORE\View\Widget\Vkontakte;

/**
 * Class Like
 *
 * @package SNOWGIRL_CORE\View\Widget\Vkontakte
 * @see     https://vk.com/dev/Like
 */
class Like extends Vkontakte
{
    public const TYPE_FULL = 'full';
    public const TYPE_BUTTON = 'button';
    public const TYPE_MINI = 'mini';
    public const TYPE_VERTICAL = 'vertical';

    protected $type = self::TYPE_FULL;

    public const HEIGHT_18 = 18;
    public const HEIGHT_20 = 20;
    public const HEIGHT_22 = 22;
    public const HEIGHT_24 = 24;
    public const HEIGHT_30 = 30;

    protected $height = self::HEIGHT_30;

    protected $width;

    public const VERB_LIKE = 0;
    public const VERB_INTERESTING = 1;

    protected $verb = self::VERB_LIKE;
    protected $title;
    protected $href;
    protected $image;

    //@todo fix in case of multiple instances on the same page...
    protected $domId = 'vk_like';

    protected function getNode(): ?Node
    {
        return $this->makeNode('div', [
            'id' => $this->getDomId(),
            'class' => $this->getDomClass(),
            'style' => 'display:inline-block;max-width:100%;overflow:hidden'
        ]);
    }

    protected function addScripts(): Widget
    {
        return parent::addScripts()
            ->addJs('VK.Widgets.Like("' . $this->getDomId() . '", ' . json_encode($this->getClientOptions([
                    'type',
                    'width',
                    'height',
                    'verb',
                    'pageTitle' => 'title',
                    'pageImage' => 'image'
                ], ['pageUrl' => $this->href ?: ('https://vk.com/' . $this->page)])) . ');', true, false, true);
    }

    public function isOk(): bool
    {
        return parent::isOk() && ($this->href || $this->page);
    }
}