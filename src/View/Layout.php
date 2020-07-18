<?php

namespace SNOWGIRL_CORE\View;

use SNOWGIRL_CORE\Http\HttpApp as App;
use SNOWGIRL_CORE\Http\HttpApp;
use SNOWGIRL_CORE\Http\HttpClient;
use SNOWGIRL_CORE\Helper;
use SNOWGIRL_CORE\Script;
use SNOWGIRL_CORE\Script\Css;
use SNOWGIRL_CORE\Script\Js;
use SNOWGIRL_CORE\View;
use SNOWGIRL_CORE\View\Widget\Form;
use Throwable;

/**
 * Class Layout
 * @property HttpApp app
 * @method Layout addParams(array $params)
 * @package SNOWGIRL_CORE\View
 */
abstract class Layout extends View
{
    public const MESSAGE_SUCCESS = 'success';
    public const MESSAGE_INFO = 'info';
    public const MESSAGE_WARNING = 'warning';
    public const MESSAGE_ERROR = 'danger';

    public const SESSION_MESSAGE_KEY = 'messages';

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

    protected $error;

    /**
     * @var HttpClient
     */
    protected $client;

    /**
     * @var Css[]
     */
    protected $headCss = [];

    /**
     * @var Css[]
     */
    protected $lazyCss = [];

    protected $widgets = [];

    protected $title;
    protected $meta = [];
    protected $h1;
    protected $metaProperties = [];
    protected $headPrefix;
    protected $headLinks = [];

    /**
     * @var array
     */
    protected $jsConfig = [];
    protected $headerSearch;
    protected $headerNav = [];
    protected $breadcrumbs = [];
    protected $domains = [];

    public function __construct(App $app, array $params = [])
    {
        parent::__construct($app, '@core/layout.phtml', $params);

        $this->baseHref = $this->app->config('domains.master');
        $this->currentUri = $this->app->request->getLink();
        $this->site = $this->app->getSite();
        $this->client = $this->app->request->getClient();
        $this->mobileBackBtn = $this->app->request->isWeAreReferer();
        $this->lang = $this->app->trans->getLang();

        try {
            $this->addMenuNodes();
        } catch (Throwable $e) {

        }

        $this->addCssNodes()
            ->addJsNodes();
    }

    public function addWidget(string $widget, array $params = []): Layout
    {
        $this->widgets[] = $this->app->views->getWidget($widget, $params, $this)->stringify();

        return $this;
    }

    public function setTitle(string $title): Layout
    {
        $this->title = Helper::getNiceSemanticText($title);

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setMeta(string $key, $value): Layout
    {
        if (in_array($key, ['description', 'keywords'])) {
            $value = Helper::getNiceSemanticText($value);
        }

        $this->meta[$key] = $value;

        return $this;
    }

    public function addMeta(string $key, $value): Layout
    {
        return $this->setMeta($key, $value);
    }

    public function getMeta(string $key, $default = null)
    {
        return $this->meta[$key] ?? $default;
    }

    public function setH1(string $h1): Layout
    {
        $this->h1 = Helper::getNiceSemanticText($h1);

        return $this;
    }

    public function getH1(): string
    {
        return $this->h1;
    }

    public function setMetaProperty(string $key, $value): Layout
    {
        if (in_array($value, ['og:title', 'og:description'])) {
            $value = Helper::getNiceSemanticText($value);
        }

        $this->metaProperties[$key] = $value;

        return $this;
    }

    /**
     * @todo re-factor coz, for example, multiple og:image could be present...
     * @param string $key
     * @param $value
     * @return Layout
     */
    public function addMetaProperty(string $key, $value): Layout
    {
        return $this->setMetaProperty($key, $value);
    }

    public function setHeadPrefix(string $prefix): Layout
    {
        $this->headPrefix = $prefix;

        return $this;
    }

    public function setHeadLink(string $key, $value): Layout
    {
        $this->headLinks[$key] = $value;

        return $this;
    }

    public function addHeadLink(string $key, $value): Layout
    {
        return $this->setHeadLink($key, $value);
    }

    public function addMessage(string $text, string $type = self::MESSAGE_INFO): Layout
    {
        $tmp = $this->getMessages();
        $tmp[] = [$text, $type];

        $this->app->request->getSession()->set(self::SESSION_MESSAGE_KEY, $tmp);

        return $this;
    }

    public function clearMessages(string $type = null): Layout
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

    public function getMessages(bool $delete = false): array
    {
        $tmp = $this->app->request->getSession()->get(self::SESSION_MESSAGE_KEY, []);

        if ($delete) {
            $this->app->request->getSession()->_unset(self::SESSION_MESSAGE_KEY);
        }

        return $tmp;
    }

    public function addHeadCss(string $css, bool $raw = false, bool $cache = false, string $domain = 'master'): Layout
    {
        $this->headCss[] = $this->makeCss($css, $raw, $cache, $domain);

        return $this;
    }

    public function addLazyCss(string $css, bool $raw = false, bool $cache = false, string $domain = 'master'): Layout
    {
        $this->lazyCss[] = $this->makeCss($css, $raw, $cache, $domain);

        return $this;
    }

    public function addJsConfig(string $key, $value): Layout
    {
        $this->jsConfig[$key] = $value;

        return $this;
    }

    public function addMenu(string $text, string $link): Layout
    {
        $this->headerNav[$text] = $link;

        return $this;
    }

    public function addNav(string $text, string $link): Layout
    {
        return $this->addMenu($text, $link);
    }

    public function setNoIndexNoFollow(): Layout
    {
        $this->addMeta('robots', 'noindex,nofollow');

        return $this;
    }

    public function setCanonical(string $link): Layout
    {
        $this->addHeadLink('canonical', $link);

        return $this;
    }

    public function addBreadcrumb(string $text, string $link = null): Layout
    {
        $this->breadcrumbs[] = [$text, $link];

        return $this;
    }

    protected function addMenuNodes(): Layout
    {
        return $this;
    }

    /**
     * @return Layout
     */
    protected function addCssNodes(): Layout
    {
        return $this->addHeadCss('//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css')
            ->addHeadCss('https://fonts.googleapis.com/css2?family=Fira+Sans+Condensed:wght@400;500&family=Montserrat:wght@400&display=swap')
            ->addHeadCss('@core/core.css')
            ->addHeadCss('@core/core.grid.css')
            ->addHeadCss('@core/core.fonts.css')
            ->addHeadCss('@core/core.header.css')
            ->addHeadCss('@core/core.toggle.css')
            ->addHeadCss('@core/core.nav.css')
            ->addHeadCss('@core/core.corners.css')
            ->addHeadCss('@core/core.breadcrumbs.css')
            ->addLazyCss('//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css')
            ->addHeadCss('@app/core.css');
    }

    protected function addJsNodes(): Layout
    {
        return $this->addJs('//ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js')
            ->addJs('@core/core.js');
    }

    protected function makeHead(): string
    {
        return $this->stringifyContent('@core/layout/head.phtml');
    }

    protected function makeHeader(): string
    {
        return $this->stringifyContent('layout/header.phtml');
    }

    protected function makeBreadcrumbs(): string
    {
        return $this->stringifyContent('layout/breadcrumbs.phtml');
    }

    protected function makeMessages(): string
    {
        if ($this->messages = $this->getMessages(true)) {
            return $this->stringifyContent('@core/layout/alerts.phtml');
        }

        return '';
    }

    protected function makeContent(): string
    {
        return $this->stringifyContent('layout/content.phtml');
    }

    protected function makeFooter(): string
    {
        return $this->stringifyContent('layout/footer.phtml');
    }

    protected function makeBottom(): string
    {
        return $this->stringifyContent('@core/layout/bottom.phtml');
    }

    /**
     * @param       $name - for example Form\Tag or Form\Input\Tag
     * @param array $params
     * @return Widget
     */
    public function makeChildWidget(string $name, array $params = []): Widget
    {
        return $this->app->container->getObject('View\\Widget\\' . $name, null, $params, $this);
    }

    public function makeChildForm(string $name, array $params = []): Form
    {
        return $this->makeChildWidget('Form\\' . $name, $params);
    }

    public function prepareContent()
    {
        if ($this->content instanceof View) {
            $this->content = $this->content->stringify();
        }
    }

    protected function stringifyInner(string $template = null): string
    {
        return $this->stringifyContent($template);
    }

    protected function filterNonEmpty(array $params): array
    {
        return array_filter($params, function ($param) {
            return 0 < strlen($param);
        });
    }

    protected function stringifyPrepare()
    {
        $this->prepareContent();

        $this->addMeta('viewport', 'width=device-width, initial-scale=1')
            ->addJsConfig('domains', $this->app->config('domains', []))
            ->addJsConfig('routes', $this->app->router->getRoutePatterns())
            ->addJsConfig('client', $this->app->config('client'));

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

        //in the end coz meta, scripts etc. could be sets inside templates...
        $this->head = $this->makeHead();
        $this->bottom = $this->makeBottom();

        return parent::stringifyPrepare();
    }
}
