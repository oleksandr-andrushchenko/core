<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 9/23/17
 * Time: 8:28 PM
 */
namespace SNOWGIRL_CORE\View;

use SNOWGIRL_CORE\Entity;
use SNOWGIRL_CORE\Exception;
use SNOWGIRL_CORE\Image;
use SNOWGIRL_CORE\Service\Logger;
use SNOWGIRL_CORE\Video;
use SNOWGIRL_CORE\View;
use SNOWGIRL_CORE\View\Widget\Form;

use SNOWGIRL_CORE\View\Widget\Pager;

use SNOWGIRL_CORE\View\Widget\Form\Input;
use SNOWGIRL_CORE\View\Widget\Form\Input\File\Image as ImageInput;
use SNOWGIRL_CORE\View\Widget\Form\Input\DateTime as DateTimeInput;
use SNOWGIRL_CORE\View\Widget\Form\Input\Tag as TagInput;
use SNOWGIRL_CORE\View\Widget\Form\Input\Media as MediaInput;
use SNOWGIRL_CORE\View\Widget\Form\Input\Place as PlaceInput;
use SNOWGIRL_CORE\View\Widget\Form\Input\Tinymce;

use SNOWGIRL_CORE\View\Widget\Vkontakte\Like as VkontakteLike;
use SNOWGIRL_CORE\View\Widget\Vkontakte\Page as VkontaktePage;

use SNOWGIRL_CORE\View\Widget\Facebook\Like as FacebookLike;
use SNOWGIRL_CORE\View\Widget\Facebook\Page as FacebookPage;
use SNOWGIRL_CORE\View\Widget\Facebook\Comments as FacebookComments;

use SNOWGIRL_CORE\View\Widget\Google\Captcha as GoogleCaptcha;
use SNOWGIRL_CORE\View\Widget\Google\Map as GoogleMap;

use SNOWGIRL_CORE\View\Widget\AddThisSharer as Sharer;

use SNOWGIRL_CORE\View\Widget\Form\Subscribe as SubscribeForm;
use SNOWGIRL_CORE\View\Widget\Form\Search as SearchForm;
use SNOWGIRL_CORE\View\Widget\Form\Contact as ContactForm;

use SNOWGIRL_CORE\View\Widget\Email\Contact as ContactEmail;
use SNOWGIRL_CORE\View\Widget\Email\ContactNotify as ContactNotifyEmail;
use SNOWGIRL_CORE\View\Widget\Email\ErrorLog as ErrorLogEmail;
use SNOWGIRL_CORE\View\Widget\Email\Subscribe as SubscribeEmail;

use SNOWGIRL_CORE\View\Widget\Popup;
use SNOWGIRL_CORE\View\Widget\Carousel;

/**
 * Class Builder
 * @package SNOWGIRL_CORE\View
 * @method Pager pager(array $params = [], $parent = null)
 * @method Sharer sharer($parent = null, $ukraine = false)
 * @method string image($image, $format = Image::FORMAT_NONE, $param = 0, $attrs = [])
 * @method View video($video, $attrs = [])
 * @method Widget widget($class, array $params = [], $parent = null)
 * @method GoogleCaptcha googleCaptcha($parent = null)
 * @method GoogleMap googleMap(array $params = [], View $parent = null)
 * @method FacebookComments facebookComments($parent = null)
 * @method FacebookLike facebookLike($parent = null)
 * @method FacebookPage facebookPage($parent = null)
 * @method VkontakteLike vkontakteLike($parent = null)
 * @method VkontaktePage vkontaktePage($parent = null)
 * @method View entity($entity, $template = null, array $params = [], $parent = null)
 * @method Input input(array $params = [], $parent = null)
 * @method ImageInput imageInput(array $params = [], $parent = null)
 * @method Tinymce tinymce(array $params = [], $parent = null)
 * @method DateTimeInput dateTimeInput(array $params = [], $parent = null)
 * @method TagInput tagInput(array $params = [], $parent = null)
 * @method MediaInput mediaInput(array $params = [], $parent = null)
 * @method PlaceInput placeInput(array $params = [], $parent = null)
 * @method string rating($rating, $max, $cost)
 * @method Popup popup(array $params = [], $parent = null)
 * @method Carousel carousel(array $params = [], $parent = null)
 * @method SubscribeForm subscribeForm(array $params = [], $parent = null)
 * @method SearchForm searchForm($parent = null)
 * @method ContactForm contactForm(array $params = [], $parent = null)
 * @method ContactEmail contactEmail(array $params = [])
 * @method ContactNotifyEmail contactNotifyEmail(array $params = [])
 * @method ErrorLogEmail errorLogEmail($error)
 * @method SubscribeEmail subscribeEmail(array $params = [])
 */
class Builder extends \SNOWGIRL_CORE\Builder
{
    public function _call($fn, array $args)
    {
        switch ($fn) {
            case 'pager':
                return $this->getWidget(Pager::class, $args[0] ?? [], $args[1] ?? null);
            case 'sharer':
                return $this->getWidget(Sharer::class, ['ukraine' => $args[1] ?? false], $args[0] ?? null);
            case 'googleCaptcha':
                return $this->getWidget(GoogleCaptcha::class, [], $args[0] ?? null);
            case 'googleMap':
                return $this->getWidget(GoogleMap::class, $args[0] ?? [], $args[1] ?? null);
            case 'image':
                return $this->getImage(...$args);
            case 'video':
                return $this->getVideo(...$args);
            case 'widget':
                return $this->getWidget(...$args);
            case 'facebookComments':
                return $this->getWidget(FacebookComments::class, [], $args[0] ?? null);
            case 'vkontakteLike':
                return $this->getWidget(VkontakteLike::class, [], $args[0] ?? null);
            case 'vkontaktePage':
                return $this->getWidget(VkontaktePage::class, [], $args[0] ?? null);
            case 'facebookLike':
                return $this->getWidget(FacebookLike::class, [], $args[0] ?? null);
            case 'facebookPage':
                return $this->getWidget(FacebookPage::class, [], $args[0] ?? null);
            case 'input':
                return $this->getWidget(Input::class, $args[0] ?? [], $args[1] ?? null);
            case 'imageInput':
                return $this->getWidget(ImageInput::class, $args[0] ?? [], $args[1] ?? null);
            case 'tinymce':
                return $this->getWidget(Tinymce::class, $args[0] ?? [], $args[1] ?? null);
            case 'dateTimeInput':
                return $this->getWidget(DateTimeInput::class, $args[0] ?? [], $args[1] ?? null);
            case 'tagInput':
                return $this->getWidget(TagInput::class, $args[0] ?? [], $args[1] ?? null);
            case 'mediaInput':
                return $this->getWidget(MediaInput::class, $args[0] ?? [], $args[1] ?? null);
            case 'placeInput':
                return $this->getWidget(PlaceInput::class, $args[0] ?? [], $args[1] ?? null);
            case 'entity':
                return $this->getEntity(...$args);
            case 'rating':
                return $this->getRating(...$args);
            case 'popup':
                return $this->getWidget(Popup::class, $args[0] ?? [], $args[1] ?? null);
            case 'carousel':
                return $this->getWidget(Carousel::class, $args[0] ?? [], $args[1] ?? null);
            case 'subscribeForm':
                return $this->getWidget(SubscribeForm::class, $args[0] ?? null, $args[1] ?? null);
            case 'searchForm':
                return $this->getWidget(SearchForm::class, [], $args[0] ?? null);
            case 'contactForm':
                return $this->getWidget(ContactForm::class, $args[0] ?? [], $args[1] ?? null);
            case 'contactEmail':
                return $this->getWidget(ContactEmail::class, $args[0] ?? []);
            case 'contactNotifyEmail':
                return $this->getWidget(ContactNotifyEmail::class, $args[0] ?? []);
            case 'errorLogEmail':
                return $this->getWidget(ErrorLogEmail::class, ['error' => $args[0]]);
            case 'subscribeEmail':
                return $this->getWidget(SubscribeEmail::class, $args[0] ?? []);
            default:
                return parent::_call($fn, $args);
        }
    }

    /**
     * @param $template
     * @param array $params
     * @param View|null $parent
     * @return View
     */
    public function get($template, array $params = [], View $parent = null)
    {
        return new View($this->app, $template, $params, $parent);
    }

    /**
     * @param $class
     * @param array $params
     * @param View|null $parent
     * @return Widget|Form
     */
    public function getWidget($class, array $params = [], View $parent = null)
    {
        return new $class($this->app, $params, $parent);
    }

    /**
     * @param Entity|string $entity
     * @param null $template
     * @param array $params
     * @param View|null $parent
     * @return View
     */
    public function getEntity(Entity $entity, $template = null, array $params = [], View $parent = null)
    {
        $params['entity'] = $entity;

        $rawTemplate = $template;
        $template = 'entity/' . str_replace('_', '.', $entity->getTable()) . ($template ? ('.' . $template) : '') . '.phtml';
        $view = $this->get($template, $params, $parent);

        $entity->stringifyPrepare($rawTemplate);

        return $view;
    }

    /**
     * @param $image
     * @param int $format
     * @param int $param
     * @param array $attrs
     * @return string
     * @throws Exception
     */
    public function getImage($image, $format = Image::FORMAT_NONE, $param = 0, array $attrs = [])
    {
        if (is_string($image)) {
            $image = $this->app->images->get($image);
        }

        if (!$image instanceof Image) {
            throw new Exception('invalid image object');
        }

        if (!isset($attrs['alt'])) {
            $msg = 'invalid "alt" attr';

            if ($this->app->isDev()) {
                throw new Exception($msg);
            } else {
                $this->app->services->logger->make($msg, Logger::TYPE_ERROR);
            }
        }

        foreach ($attrs as $k => &$v) {
            $v = is_int($k) ? $v : ($k . '="' . $v . '"');
        }

        return '<img src="' . $image->stringify($format, $param) . '" ' . implode(' ', $attrs) . '>';
    }

    /**
     * @param $video
     * @param array $attrs
     * @return string
     * @throws Exception
     */
    public function getVideo($video, array $attrs = [])
    {
        if (is_string($video)) {
            $video = new Video($video);
        }

        if (!$video instanceof Video) {
            throw new Exception('invalid video object');
        }

        $attrs = array_merge([
            'frameborder' => '0',
            'width' => '100%',
            'height' => '100%',
            'scrolling' => 'no',
            'allowfullscreen' => ''
        ], $attrs);

        foreach ($attrs as $k => &$v) {
            $v = is_int($k) ? $v : ($k . '="' . $v . '"');
        }

        return '<iframe src="' . $video->stringify() . '" ' . implode(' ', $attrs) . '></iframe>';
    }

    /**
     * @param bool|false $admin
     * @param array $params
     * @return Layout
     */
    public function getLayout($admin = false, array $params = [])
    {
        return $this->app->getObject('View\Layout\\' . ($admin ? 'Admin' : 'Outer'), $this->app, $params);
    }

    public function _getRating($rating, $max, $cost)
    {
        $output = [];
        $output[] = '<div class="rating">';

        for ($i = 0, $div = min($rating, $max * $cost) / $cost, $full = (int)$div; $i < $full; $i++) {
            $output[] = '<span class="star-full"></span>';
        }

        if ($half = is_float($div) ? 1 : 0) {
            $output[] = '<span class="star-half"></span>';
        }

        for ($i = 0, $s = $max - $full - $half; $i < $s; $i++) {
            $output[] = '<span class="star-empty"></span>';
        }

        $output[] = '</div>';
        return implode('', $output);
    }

    public function getRating($rating, $max, $cost)
    {
        $rating = 14;
        $max = 5;
        $cost = 5;


        $output = [];
        $output[] = '<div class="rating">';

        for ($i = 0, $div = min($rating, $max * $cost) / $cost, $full = ceil($div); $i < $full; $i++) {
            $output[] = '<span class="fa fa-star star-full"></span>';
        }

        for ($i = 0, $s = $max - $full; $i < $s; $i++) {
            $output[] = '<span class="fa fa-star star-empty"></span>';
        }

        $output[] = '</div>';
        return implode('', $output);
    }
}