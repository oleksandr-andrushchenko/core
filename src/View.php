<?php

namespace SNOWGIRL_CORE;

use SNOWGIRL_CORE\Script\Css;
use SNOWGIRL_CORE\Script\Js;
use SNOWGIRL_CORE\View\Layout;
use SNOWGIRL_CORE\View\Node;
use stdClass;
use Throwable;

/**
 * Class View
 *
 * @property AbstractApp app
 * @package SNOWGIRL_CORE
 */
class View extends stdClass
{
    /** @var AbstractApp */
    protected $app;
    protected $template;

    /** @var View|Layout */
    protected $parent;

    /** @var Css[] */
    protected $css = [];
    /** @var Js[] */
    protected $js = [];

    protected $stringifyPrepared;
    protected $content;

    public function __construct(AbstractApp $app, string $template, array $params = [], View $parent = null)
    {
        $this->setApp($app)
            ->setParent($parent)
            ->setTemplate($template)
            ->setParams($params);
    }

    public function __get($key)
    {
        return $this->$key = null;
    }

    public function setContentByTemplate(string $template, array $params = []): View
    {
        return $this->content = new self($this->app, $template, $params, $this);
    }

    public function setContentByView(View $view): View
    {
        $this->content = $view;

        return $this;
    }


    public function setContentByTemplateKeepContext(string $template, array $params = []): View
    {
        $this->addParams($params);
        $this->content = $this->stringifyContent($template);

        return $this;
    }

    public function setContent($content): View
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return View|null
     */
    public function getContent()
    {
        return $this->content;
    }

    public function addParams(array $params): View
    {
        return $this->setParams($params);
    }

    protected function setApp(AbstractApp $app): View
    {
        $this->app = $app;

        return $this;
    }

    public function setParent(View $parent = null): View
    {
        if ($parent instanceof View) {
            $this->parent = $parent;
        }

        return $this;
    }

    public function makeLink(string $route, $params = []): string
    {
        return $this->app->router->makeLink($route, $params);
    }

    public function makeText(string $key): string
    {
        return $this->app->trans->makeText(...func_get_args());
    }

    public function setTemplate(string $template): View
    {
        $this->template = $template;

        return $this;
    }

    public function getTemplate(string $template = null): ?string
    {
        return $template ?: $this->template;
    }

    public function setParam(string $key, $value): View
    {
        if (in_array($key, ['template', 'css', 'js', 'parent', 'stringifyPrepared', 'app'])) {
            $this->app->container->logger->notice('restricted "' . $key . '" param');

            return $this;
        }

        $this->$key = $value;

        return $this;
    }

    public function getParam(string $key)
    {
        return $this->$key;
    }

    public function setParams(array $params): View
    {
        foreach ($params as $k => $v) {
            $this->setParam($k, $v);
        }

        return $this;
    }

    public function __isset($k)
    {
        return property_exists($this, $k);
    }

    public static function makeNode($tag, $attr = []): Node
    {
        return new Node($tag, $attr);
    }

    public function noIndexOpen()
    {
        echo '<!--noindex-->';
        echo '<noindex class="robots-nocontent">';
        echo '<!--googleoff: all-->';
    }

    public function noIndexClose()
    {
        echo '<!--googleon: all-->';
        echo '</noindex>';
        echo '<!--/noindex-->';
    }

    public function addJs(string $js, bool $raw = false, bool $cache = false, bool $global = false, string $domain = 'master'): View
    {
        if ($global && $layout = $this->getLayout()) {
            $layout->addJs($js, $raw, $cache, false, $domain);
        } else {
            $this->js[] = $this->makeJs($js, $raw, $cache, $domain);
        }

        return $this;
    }

    protected function makeCss(string $css, bool $raw = false, bool $cache = false, string $domain = 'master'): Css
    {
        return new Css(
            $css,
            $this->app->dirs,
            array_keys($this->app->namespaces),
            $this->app->config('client.css_counter'),
            $this->app->container->logger,
            $raw,
            $cache,
            $this->app->config('domains.' . $domain)
        );
    }

    protected function makeJs(string $js, bool $raw = false, bool $cache = false, string $domain = 'master'): Js
    {
        return new Js(
            $js,
            $this->app->dirs,
            array_keys($this->app->namespaces),
            $this->app->config('client.js_counter'),
            $this->app->container->logger,
            $raw,
            $cache,
            $this->app->config('domains.' . $domain)
        );
    }

    public function jsOpen(): View
    {
        ob_start();

        return $this;
    }

    public function jsClose(bool $cache = false, bool $global = false): View
    {
        $this->addJs(preg_replace("/<\\/?script(.|\\s)*?>/", '', ob_get_clean()), true, $cache, $global);

        return $this;
    }

    public function addCss(string $css, bool $raw = false, bool $cache = false, bool $global = false, string $domain = 'master'): View
    {
        if ($global && $layout = $this->getLayout()) {
            $layout->addHeadCss($css, $raw, $cache, $domain);
        } else {
            $this->css[] = $this->makeCss($css, $raw, $cache, $domain);
        }

        return $this;
    }

    public function cssOpen(): View
    {
        ob_start();

        return $this;
    }

    public function cssClose(bool $cache = false, bool $global = false): View
    {
        $this->addCss(strip_tags(ob_get_clean()), true, $cache, $global);

        return $this;
    }

    //@todo create setter with safe flag... (for html vars - header, breadcrumbs, footer etc..) then uncomment this... then remove all custom htmlspecialchars...
//    public function __set($k, $v)
//    {
//        if (is_string($v)) {
//            $v = nl2br(htmlspecialchars($v));
//        }
//
//        $this->$k = $v;
//    }


    public function getLayout(): ?Layout
    {
        if ($this instanceof Layout) {
            return $this;
        }

        if (null === $this->parent) {
            return null;
        }

        return $this->parent->getLayout();
    }

    public function getFile(string $template = null)
    {
        $template = $this->getTemplate($template);

        $aliases = array_keys($this->app->namespaces);

        if (0 === strpos($template, '@')) {
            foreach ($aliases as $alias) {
                if (0 === strpos($template, $alias)) {
                    if (file_exists($tmp = str_replace($alias, $this->app->dirs[$alias] . '/view', $template))) {
                        return $tmp;
                        break;
                    }
                }
            }
        }

        foreach ($aliases as $alias) {
            if (file_exists($tmp = $this->app->dirs[$alias] . '/view/' . $template)) {
                return $tmp;
                break;
            }
        }

        return false;
    }

    protected function stringifyCss(): string
    {
        return implode('', $this->css);
    }

    protected function stringifyJs(): string
    {
        return implode('', $this->js);
    }

    protected function echoContent(string $template = null)
    {
        $template = $this->getTemplate($template);

        if ($file = $this->getFile($template)) {
            /** @noinspection PhpIncludeInspection */
            include $file;
        } else {
            $msg = 'View template file [' . $template . '] not found';
            echo $this->app->isDev() ? $msg : '';
            $this->app->container->logger->debug($msg);
            echo $msg;
        }
    }

    public function stringifyContent(string $template = null): string
    {
        return $this->stringifyWithClosure(function () use ($template) {
            $this->echoContent($template);
        });
    }

    protected function stringifyException(Throwable $e)
    {
        return $this->app->isDev() ? $e->getTraceAsString() : $this->makeText('error.general');
    }

    protected function stringifyPrepare()
    {
//        $params = (array)$this;
//        unset($params['template']);
//
//        foreach ($params as $k => $v) {
//            if (is_string($v)) {
//                $this->$k = htmlspecialchars($v);
//            }
//        }
        return true;
    }

    protected function stringifyInner(string $template = null): string
    {
        return implode('', [
            $this->stringifyCss(),
            $this->stringifyContent($template),
            $this->stringifyJs()
        ]);
    }

    public function stringifyWithClosure(callable $echo): string
    {
        $level = ob_get_level();
        ob_start();

        try {
            $echo();
        } catch (Throwable $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            $this->app->container->logger->error($e);
            echo $this->stringifyException($e);
        }

        return ob_get_clean();
    }

    public function stringify(string $template = null): string
    {
        return $this->stringifyWithClosure(function () use ($template) {
            if (!$this->stringifyPrepared) {
                $this->stringifyPrepared = true;
                $this->stringifyPrepare();
            }

            echo $this->stringifyInner($template);
        });
    }

    public function __toString()
    {
        return $this->stringify();
    }
}