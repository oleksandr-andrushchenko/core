<?php

namespace SNOWGIRL_CORE\View\Widget;

use SNOWGIRL_CORE\Images;
use SNOWGIRL_CORE\View\Widget;
use SNOWGIRL_CORE\View\Widget\Carousel\ItemInterface as Item;

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
    protected $imageFormat = Images::FORMAT_NONE;
    protected $imageParam = 0;
    protected $captionTag = 'h6';
    protected $itemBuilderClosure;
    protected $height;

    protected function addScripts(): Widget
    {
        return parent::addScripts()
            ->addCssScript('@core/owl.carousel-2.2.1.min.css')
            ->addCssScript('@core/owl.theme.default-2.2.1.min.css')
            ->addCssScript('@core/widget/carousel.css')
            ->addJsScript('@core/owl.carousel-2.2.1.min.js')
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

    protected function getInner(string $template = null): ?string
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

                    $attrs['alt'] = $this->makeText('layout.photo');

                    if (0 < strlen($caption)) {
                        $attrs['alt'] .= ' ' . $caption;
                        $attrs['title'] = $caption;
                    }

                    $attrs['class'] = 'item-image';
                    $attrs['height'] = false;
                    $attrs['width'] = false;

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

    public function isOk(): bool
    {
        return 0 < count($this->items);
    }
}