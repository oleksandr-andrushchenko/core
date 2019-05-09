<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 1/1/18
 * Time: 5:10 PM
 */
namespace SNOWGIRL_CORE\View\Widget;

use SNOWGIRL_CORE\Image;
use SNOWGIRL_CORE\View\Widget;
use SNOWGIRL_CORE\View\Widget\Carousel\ItemInterface as Item;

/**
 * Class Carousel
 * @package SNOWGIRL_CORE\View\Widget
 */
class Carousel extends Widget
{
    /** @var Item[]|array|mixed[] */
    protected $items = [];
    protected $autoplay = true;
    protected $autoplayTimeout = 5000;
    protected $autoplaySpeed = 1000;
    protected $autoplayHoverPause = true;
    protected $autoHeight = false;
    protected $dots = true;
    protected $nav = false;
    protected $loop = true;
    protected $center = true;
    protected $count = 1;
    protected $imageFormat = Image::FORMAT_NONE;
    protected $imageParam = 0;
    protected $captionTag = 'h6';
    protected $itemBuilderClosure;
    protected $height;

    protected function addScripts()
    {
        return parent::addScripts()
            ->addCssScript('@snowgirl-core/owl.carousel-2.2.1.min.css')
            ->addCssScript('@snowgirl-core/owl.theme.default-2.2.1.min.css')
            ->addCssScript('@snowgirl-core/widget/carousel.css')
            ->addJsScript('@snowgirl-core/owl.carousel-2.2.1.min.js')
            ->addClientScript('owlCarousel', $this->getClientOptions([
                'autoplay',
                'autoplayTimeout',
                'autoplaySpeed',
                'autoplayHoverPause',
                'autoHeight',
                'dots',
                'nav',
                'loop',
                'center',
                'items' => 'count'
            ]));
    }

    protected function getInner($template = null)
    {
        $nodes = [];

        foreach ($this->items as $item) {
            if ($item instanceof Item) {
                $attrs = [];

                $attrs['class'] = 'item';

                if (is_int($this->height)) {
                    $attrs['style'] = 'display:block;height:' . $this->height . 'px';
                }

                if ($href = $item->getHref($this->app)) {
                    $attrs['href'] = $href;
                    $node = $this->makeNode('a', $attrs);
                } else {
                    $node = $this->makeNode('div', $attrs);
                }

                $caption = $item->getCaption();

                if ($image = $item->getImageHash()) {
                    $attrs = [];

                    $attrs['alt'] = T('layout.photo');

                    if (0 < strlen($caption)) {
                        $attrs['alt'] .= ' ' . $caption;
                        $attrs['title'] = $caption;
                    }

                    $attrs['class'] = 'item-image';

                    $node->append($this->app->views->image(
                        $item->getImageHash(),
                        $this->imageFormat,
                        $this->imageParam,
                        $attrs
                    ));
                }

                if (0 < strlen($caption)) {
                    $node->append($this->makeNode('div', ['class' => 'item-caption'])
                        ->append($this->makeNode($this->captionTag, ['class' => 'item-caption-inner'])
                            ->append($caption)));
                }
            } elseif (is_callable($this->itemBuilderClosure)) {
                $node = call_user_func($this->itemBuilderClosure, $item);
            } else {
                $node = '';
            }

            $nodes[] = $node;
        }

        return implode('', $nodes);
    }

    protected function stringifyPrepare()
    {
        $this->addDomClass('carousel')
            ->addDomClass('owl-carousel')
            ->addDomClass('owl-theme');

        return parent::stringifyPrepare();
    }

    public function isOk()
    {
        return 0 < count($this->items);
    }
}