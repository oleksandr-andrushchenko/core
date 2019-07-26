<?php

namespace SNOWGIRL_CORE\View;

use SNOWGIRL_CORE\App;
use SNOWGIRL_CORE\Request\Client;
use SNOWGIRL_CORE\Helper;
use SNOWGIRL_CORE\Script;
use SNOWGIRL_CORE\Script\Js;
use SNOWGIRL_CORE\Script\Css;
use SNOWGIRL_CORE\View;

/**
 * Class Layout
 * @method Layout addJs($js)
 * @method Layout addParams(array $params)
 *
 * @package SNOWGIRL_CORE\View
 */
abstract class Layout extends View
{
    protected $site;
    protected $baseHref;
    protected $currentUri;
    protected $lang;
    protected $phone;
    protected $footerLinks = [];
    protected $sign;

    protected $head;
    protected $banner;
    protected $header;
    protected $messages;
    protected $footer;
    protected $bottom;

    /** @var Client */
    protected $client;

    public function __construct(App $app, array $params = [])
    {
        parent::__construct($app, '@core/layout.phtml', $params);
    }

    protected function initialize()
    {
        parent::initialize();

        $this->baseHref = $this->app->config->domains->master;
        $this->currentUri = $this->app->request->getLink();
        $this->site = $this->app->getSite();
        $this->client = $this->app->request->getClient();
//        $this->mobileBackBtn = $this->app->request->isWeAreReferer();
        $this->lang = $this->app->trans->getLang();

        $this->addMenuNodes()
            ->addCssNodes()
            ->addJsNodes();
    }

    protected $error;

    public function setError($error)
    {
        $this->error = $error;
        return $this;
    }

    protected $widgets = [];

    public function addWidget($widget, $params = [])
    {
        /** @var Widget $widget */
        $this->widgets[] = $this->app->views->getWidget($widget, $params, $this)->stringify();
        return $this;
    }

    protected $title;

    /**
     * @param $title
     *
     * @return Layout
     */
    public function setTitle($title)
    {
        $this->title = Helper::getNiceSemanticText($title);
        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    protected $meta = [];

    public function setMeta($k, $v)
    {
        if (in_array($k, ['description', 'keywords'])) {
            $v = Helper::getNiceSemanticText($v);
        }

        $this->meta[$k] = $v;
        return $this;
    }

    /**
     * @param $k
     * @param $v
     *
     * @return Layout
     */
    public function addMeta($k, $v)
    {
        return $this->setMeta($k, $v);
    }

    public function getMeta($k, $d = null)
    {
        return $this->meta[$k] ?? $d;
    }

    protected $h1;

    public function setH1($h1)
    {
        $this->h1 = Helper::getNiceSemanticText($h1);
        return $this;
    }

    public function getH1()
    {
        return $this->h1;
    }

    protected $metaProperties = [];

    public function setMetaProperty($k, $v)
    {
        if (in_array($v, ['og:title', 'og:description'])) {
            $v = Helper::getNiceSemanticText($v);
        }

        $this->metaProperties[$k] = $v;
        return $this;
    }

    /**
     * @todo re-factor coz, for example, multiple og:image could be present...
     *
     * @param $k
     * @param $v
     *
     * @return Layout
     */
    public function addMetaProperty($k, $v)
    {
        return $this->setMetaProperty($k, $v);
    }

    protected $headPrefix;

    /**
     * @param $prefix
     *
     * @return Layout
     */
    public function setHeadPrefix($prefix)
    {
        $this->headPrefix = $prefix;
        return $this;
    }

    protected $headLinks = [];

    public function setHeadLink($k, $v)
    {
        $this->headLinks[$k] = $v;
        return $this;
    }

    public function addHeadLink($k, $v)
    {
        return $this->setHeadLink($k, $v);
    }

    public const MESSAGE_SUCCESS = 'success';
    public const MESSAGE_INFO = 'info';
    public const MESSAGE_WARNING = 'warning';
    public const MESSAGE_ERROR = 'danger';

    public const SESSION_MESSAGE_KEY = 'messages';

    public function addMessage($text, $type = self::MESSAGE_INFO)
    {
        $tmp = $this->getMessages();
        $tmp[] = [$text, $type];

        $this->app->request->getSession()->set(self::SESSION_MESSAGE_KEY, $tmp);

        return $this;
    }

    public function clearMessages($type = null)
    {
        if (null === $type) {
            $tmp = [];
        } else {
            $tmp = $this->getMessages();

            foreach ($tmp as $k => $message) {
                if ($type == $message[1]) {
                    unset($tmp[$k]);
                }
            }
        }

        $this->app->request->getSession()->set(self::SESSION_MESSAGE_KEY, $tmp);
        return $this;
    }

    /**
     * @param bool|false $delete
     *
     * @return null
     */
    public function getMessages($delete = false)
    {
        $tmp = $this->app->request->getSession()->get(self::SESSION_MESSAGE_KEY, []);

        if ($delete) {
            $this->app->request->getSession()->_unset(self::SESSION_MESSAGE_KEY);
        }

        return $tmp;
    }

    /** @var Css[]|Css|string */
    protected $headCss = [];

    /**
     * @param Css $css
     *
     * @return Layout
     */
    public function addHeadCss(Css $css)
    {
        $this->headCss[] = $css;
        return $this;
    }

    /** @var Css[] */
    protected $lazyCss = [];

    /**
     * @param Css $css
     *
     * @return Layout
     */
    public function addLazyCss(Css $css)
    {
        $this->lazyCss[] = $css;
        return $this;
    }

    /** @var array|string */
    protected $jsConfig = [];

    /**
     * @param $k
     * @param $v
     *
     * @return Layout
     */
    public function addJsConfig($k, $v)
    {
        $this->jsConfig[$k] = $v;
        return $this;
    }

    protected $headerSearch;
    protected $headerNav = [];

    public function addMenu($text, $link)
    {
        $this->headerNav[$text] = $link;
        return $this;
    }

    public function addNav($text, $link)
    {
        return $this->addMenu($text, $link);
    }

    /**
     * @return Layout
     */
    public function setNoIndexNoFollow()
    {
        $this->addMeta('robots', 'noindex,nofollow');
        return $this;
    }

    /**
     * @param $link
     *
     * @return $this
     */
    public function setCanonical($link)
    {
        $this->addHeadLink('canonical', $link);
        return $this;
    }

    protected $breadcrumbs = [];

    /**
     * @param      $text
     * @param null $link
     *
     * @return Layout
     */
    public function addBreadcrumb($text, $link = null)
    {
        $this->breadcrumbs[] = [$text, $link];
        return $this;
    }

    protected $domains = [];

    protected function addMenuNodes()
    {
        return $this;
    }

    /**
     * Nice Fonts:
     * https://fonts.google.com/?selection.family=Della+Respira|Marcellus|Marcellus+SC|Lalezar|Questrial|Shadows+Into+Light+Two
     * https://fonts.google.com/?selection.family=Comfortaa|Didact+Gothic|Forum|Montserrat|Montserrat+Alternates|Open+Sans+Condensed:300|Philosopher|Poiret+One|Prosto+One
     *
     * @return Layout
     */
    protected function addCssNodes()
    {
        return $this->addHeadCss(new Css('//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css'))
            ->addHeadCss(new Css('https://fonts.googleapis.com/css?family=Montserrat&display=swap'))
            ->addHeadCss(new Css('@core/core.css'))
            ->addHeadCss(new Css('@core/core.grid.css'))
            ->addHeadCss(new Css('@core/core.fonts.css'))
            ->addHeadCss(new Css('@core/core.header.css'))
            ->addHeadCss(new Css('@core/core.toggle.css'))
            ->addHeadCss(new Css('@core/core.nav.css'))
            ->addHeadCss(new Css('@core/core.rounded.css'))
            ->addHeadCss(new Css('@core/core.breadcrumbs.css'))
            ->addLazyCss(new Css('//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css'))
            ->addHeadCss(new Css('@app/core.css'));
    }

    protected function addJsNodes()
    {
        return $this->addJs(new Js('//ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js'))
            ->addJs(new Js('@core/core.js'));
    }

    protected function addJQuery()
    {
        $this->jquery = true;
        return $this;
    }

    protected function makeHead()
    {
        return $this->stringifyContent('@core/layout/head.phtml');
    }

    protected function makeHeader()
    {
        return $this->stringifyContent('layout/header.phtml');
    }

    protected function makeBreadcrumbs()
    {
        return $this->stringifyContent('layout/breadcrumbs.phtml');
    }

    protected function makeMessages()
    {
        if ($this->messages = $this->getMessages(true)) {
            return $this->stringifyContent('@core/layout/alerts.phtml');
        }

        return '';
    }

    protected function makeContent()
    {
        return $this->stringifyContent('layout/content.phtml');
    }

    protected function makeFooter()
    {
        return $this->stringifyContent('layout/footer.phtml');
    }

    protected function makeBottom()
    {
        return $this->stringifyContent('@core/layout/bottom.phtml');
    }

    /**
     * @param       $name - for example Form\Tag or Form\Input\Tag
     * @param array $params
     *
     * @return View
     */
    public function makeChildWidget($name, array $params = [])
    {
        return $this->app->getObject('View\\Widget\\' . $name, null, $params, $this);
    }

    public function makeChildForm($name, array $params = [])
    {
        return $this->makeChildWidget('Form\\' . $name, $params);
    }

    public function prepareContent()
    {
        if ($this->content instanceof View) {
            $this->content = $this->content->stringify();
        }
    }

    protected function stringifyInner($template)
    {
        return $this->stringifyContent($template);
    }

    protected function filterNonEmpty(array $params)
    {
        return array_filter($params, function ($param) {
            return 0 < strlen($param);
        });
    }

    protected function stringifyPrepare()
    {
        $this->prepareContent();

        $this->addMeta('viewport', 'width=device-width, initial-scale=1')
            ->addJsConfig('domains', $this->app->config->domains([]))
            ->addJsConfig('routes', $this->app->router->getRoutePatterns())
            ->addJsConfig('client', $this->app->config->client);

        foreach ($this->headCss as $k => $v) {
            $this->domains[] = $v->getDomainName();
        }

        if ($this->jsConfig) {
            $this->jsConfig = Js::addContentHtmlTag('window[\'snowgirl_config\'] = ' . json_encode($this->jsConfig) . ';');
        } else {
            $this->jsConfig = '';
        }

        if ($this->breadcrumbs) {
            array_unshift($this->breadcrumbs, [$this->makeText('layout.index'), $this->makeLink('index')]);
        }

        $this->headPrefix = $this->headPrefix ? (' prefix="' . $this->headPrefix . '"') : '';

        foreach (array_merge($this->css, $this->lazyCss, $this->js) as $script) {
            /** @var Script $script */
            $this->domains[] = $script->getDomainName();
        }

        $this->domains = array_unique($this->domains);

        $this->meta = $this->filterNonEmpty($this->meta);
        $this->metaProperties = $this->filterNonEmpty($this->metaProperties);
        $this->headLinks = $this->filterNonEmpty($this->headLinks);

        $this->header = $this->makeHeader();
        $this->breadcrumbs = $this->makeBreadcrumbs();
        $this->messages = $this->makeMessages();
        $this->content = $this->makeContent();

        if (false !== $this->footer) {
            $this->footer = $this->makeFooter();
        }

//        Arrays::userStableSort($this->js, function ($a, $b) {
//            /** @var Js $a */
//            /** @var Js $b */
//
//            $x = $a->isRaw();
//            $y = $b->isRaw();
//
//            if ($x == $y) {
//                $x = $a->getPriority();
//                $y = $b->getPriority();
//
//                if ($x == $y) {
//                    return 0;
//                }
//
//                return ($x < $y) ? -1 : 1;
//            }
//
//            return ($x < $y) ? -1 : 1;
//        });

        //in the end coz meta, scripts etc. could be sets inside templates...
        $this->head = $this->makeHead();
        $this->bottom = $this->makeBottom();

        return parent::stringifyPrepare();
    }
}
