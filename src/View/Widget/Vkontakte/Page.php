<?php

namespace SNOWGIRL_CORE\View\Widget\Vkontakte;

use SNOWGIRL_CORE\View\Widget;
use SNOWGIRL_CORE\View\Widget\Vkontakte;

/**
 * Class Page
 *
 * @package SNOWGIRL_CORE\View\Widget\Vkontakte
 * @see     https://vk.com/dev/Community
 */
class Page extends Vkontakte
{
    protected $width = 260;
    protected $height;

    public const MODE_NAME_ONLY = 1;
    public const MODE_PARTICIPANTS = 3;
    public const MODE_WALL = 4;

    protected $mode = self::MODE_PARTICIPANTS;
    protected $cover;

    public const WIDE_DISABLED = 0;
    public const WIDE_ENABLED = 1;
    public const WIDE_LIKE_AND_PHOTO = 2;

    protected $wide = self::WIDE_DISABLED;
    protected $color1;
    protected $color2;
    protected $color3;

    //@todo fix in case of multiple instances on the same page...
    protected $domId = 'vk_groups';

    protected function addScripts(): Widget
    {
        return parent::addScripts()
            ->addJs('VK.Widgets.Group("' . $this->getDomId() . '", ' . json_encode($this->getClientOptions([
                    'width',
                    'height',
                    'mode',
                    'wide',
                    'color1',
                    'color2',
                    'color3'
                ], ['no_cover' => $this->cover ? 0 : 1])) . ', ' . $this->pageId . ');', true, false, true);
    }

    public function isOk(): bool
    {
        return parent::isOk() && $this->appId;
    }
}