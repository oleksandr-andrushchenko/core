<?php

namespace SNOWGIRL_CORE;

use SNOWGIRL_CORE\Script\Css;
use SNOWGIRL_CORE\Script\Js;
use SNOWGIRL_CORE\View\Layout;
use SNOWGIRL_CORE\View\Node;

/**
 * Class View
 *
 * @property App app
 * @package SNOWGIRL_CORE
 */
class View extends \stdClass
{
    /** @var App */
    protected $app;
    protected $template;

    /**
     * View constructor.
     *
     * @param App       $app
     * @param           $template
     * @param array     $params
     * @param View|null $parent
     *
     * @throws Exception
     */
    public function __construct(App $app, $template, array $params = [], View $parent = null)
    {
        $this->setApp($app)
            ->setParent($parent)
            ->setTemplate($template)
            ->setParams($params)
            ->initialize();
    }

    protected function initialize()
    {
        return $this;
    }

    public function __get($k)
    {
        return $this->$k = null;
    }

    protected $content;

    /**
     * @param       $template
     * @param array $params
     *
     * @return View
     */
    public function setContentByTemplate($template, array $params = [])
    {
        return $this->content = new self($this->app, $template, $params, $this);
    }

    public function setContentByView(View $view)
    {
        $this->content = $view;
        return $this;
    }

    /**
     * @param       $template
     * @param array $params
     *
     * @return $this
     * @throws Exception
     */
    public function setContentByTemplateKeepContext($template, array $params = [])
    {
        $this->addParams($params);
        $this->content = $this->stringifyContent($template);
        return $this;
    }

    public function setContent($content)
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

    /**
     * @param array $params
     *
     * @return View
     * @throws void
     */
    public function addParams(array $params)
    {
        return $this->setParams($params);
    }

    /**
     * @param App $app
     *
     * @return View
     */
    protected function setApp(App $app)
    {
        $this->app = $app;
        return $this;
    }

    /** @var View|Layout */
    protected $parent;

    public function setParent(View $v = null)
    {
        if ($v instanceof View) {
            $this->parent = $v;
        }

        return $this;
    }

    public function makeLink($route, $params = [])
    {
        return $this->app->router->makeLink($route, $params);
    }

    public function makeText($key)
    {
        return $this->app->trans->makeText($key);
    }

    /**
     * @param $template
     *
     * @return View
     */
    public function setTemplate($template)
    {
        $this->template = $template;
        return $this;
    }

    public function getTemplate($template = null)
    {
        return $template ?: $this->template;
    }

    /**
     * @param $k
     * @param $v
     *
     * @return $this
     * @throws Exception
     */
    public function setParam($k, $v)
    {
        if (in_array($k, ['template', 'css', 'js', 'parent', 'stringifyPrepared', 'app'])) {
            throw new Exception('restricted "' . $k . '" param');
        }

        $this->$k = $v;
        return $this;
    }

    public function getParam($k)
    {
        return $this->$k;
    }

    /**
     * @param array $params
     *
     * @return $this
     * @throws Exception
     */
    public function setParams(array $params)
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

    public static function makeNode($tag, $attr = [])
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

    /** @var Js[] */
    protected $js = [];

    /**
     * @param Js         $js
     * @param bool|false $global
     *
     * @return View
     */
    public function addJs(Js $js, $global = false)
    {
        if ($global && $view = $this->getLayout()) {
            $view->addJs($js);
        } else {
            $this->js[] = $js;
        }

        return $this;
    }

    public function jsOpen()
    {
        ob_start();
        return $this;
    }

    public function jsClose($global = false, $cache = false)
    {
        $this->addJs(new Js(preg_replace("/<\\/?script(.|\\s)*?>/", '', ob_get_clean()), true, $cache), $global);
        return $this;
    }

    /** @var Css[] */
    protected $css = [];

    /**
     * @param Css        $css
     * @param bool|false $global
     *
     * @return View|$this
     */
    public function addCss(Css $css, $global = false)
    {
        if ($global && $view = $this->getLayout()) {
            $view->addHeadCss($css);
        } else {
            $this->css[] = $css;
        }

        return $this;
    }

    public function cssOpen()
    {
        ob_start();
        return $this;
    }

    public function cssClose($global = false, $cache = false)
    {
        $this->addCss(new Css(strip_tags(ob_get_clean()), true, $cache), $global);
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

    /**
     * @return Layout|null
     */
    public function getLayout()
    {
        if ($this instanceof Layout) {
            return $this;
        }

        if (null === $this->parent) {
            return null;
        }

        return $this->parent->getLayout();
    }

    public function getFile($template = null)
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

    protected function stringifyCss()
    {
        return implode('', $this->css);
    }

    protected function stringifyJs()
    {
        return implode('', $this->js);
    }

    protected function echoContent($template)
    {
        $template = $this->getTemplate($template);

        if ($file = $this->getFile($template)) {
            /** @noinspection PhpIncludeInspection */
            include $file;
        } else {
            $msg = 'View template file[' . $template . '] not found';
            echo $this->app->isDev() ? $msg : '';
            $this->app->services->logger->make($msg);
            echo $msg;
        }
    }

    public function stringifyContent($template)
    {
        return $this->stringifyWithClosure(function () use ($template) {
            $this->echoContent($template);
        });
    }

    protected function stringifyException(\Exception $ex)
    {
        return $this->app->isDev() ? $ex->getTraceAsString() : $this->makeText('error.general');
    }

    protected $stringifyPrepared;

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

    protected function stringifyInner($template)
    {
        return implode('', [
            $this->stringifyCss(),
            $this->stringifyContent($template),
            $this->stringifyJs()
        ]);
    }

    public function stringifyWithClosure(\Closure $echo)
    {
        $level = ob_get_level();
        ob_start();

        try {
            $echo();
        } catch (\Exception $ex) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            $this->app->services->logger->makeException($ex);
            echo $this->stringifyException($ex);
        }

        return ob_get_clean();
    }

    /**
     * @param null $template
     *
     * @return string
     */
    public function stringify($template = null)
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