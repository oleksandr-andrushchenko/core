<?php

namespace SNOWGIRL_CORE\View;

use SNOWGIRL_CORE\App;
use SNOWGIRL_CORE\Script\Css;
use SNOWGIRL_CORE\Script\Js;
use SNOWGIRL_CORE\View;

abstract class Widget extends View
{
    public function __construct(App $app, array $params = [], View $parent = null)
    {
        $this->setApp($app)
            ->setParent($parent)
            ->setTemplate($this->makeTemplate())
            ->setParams($this->makeParams($params));

        parent::__construct($app, $this->template, [], $parent);
    }

    protected function initialize()
    {
        $this->addTexts();
        return parent::initialize();
    }

    public static function factory(App $app, Layout $view)
    {
        return new static($app, [], $view);
    }

    public function triggerCloneCallback()
    {
        $this->domId = null;
    }

    public function __clone()
    {
        $this->triggerCloneCallback();
    }

    protected $ns;

    protected function getNs()
    {
        return $this->ns ?: $this->ns = explode('\\', get_called_class());
    }

    protected $nameNs;

    protected function getNameNs()
    {
        return $this->nameNs ?: $this->nameNs = array_slice($this->getNs(), 3);
    }

    protected static $index = 0;

    protected $domId;

    public function getDomId()
    {
        return $this->domId ?: $this->domId = ($this->getCoreDomClass() . '-' . $this->app->request->getServer('REQUEST_TIME') . '-' . self::$index++);
    }

    public function getCoreDomClass()
    {
        return 'widget-' . strtolower(implode('-', $this->getNameNs()));
    }

    protected $classes = [];

    public function addDomClass($class)
    {
        $this->classes[] = $class;
        return $this;
    }

    public function getDomClass()
    {
        return $this->getCoreDomClass() . ' ' . implode(' ', $this->classes);
    }

    protected function makeTemplate()
    {
        return implode('', [
            array_flip($this->app->namespaces)[$this->getNs()[0]],
            '/widget/',
            strtolower(implode('.', $this->getNameNs())),
            '.phtml'
        ]);
    }

    protected function makeParams(array $params = [])
    {
        return $params;
    }

    protected $texts = [];

    protected function addText($vocabulary)
    {
        $this->texts = array_merge($this->texts, $this->app->trans->getVocabulary($vocabulary));
        return $this;
    }

    protected static $addedScripts = [];

    public static function checkScript($script)
    {
        if (in_array($script, static::$addedScripts)) {
            return false;
        }

        static::$addedScripts[] = $script;
        return true;
    }

    protected function addCssScript($script)
    {
        if (self::checkScript($script)) {
            $this->addCss(new Css($script), true);
        }

        return $this;
    }

    protected function addJsScript($script, $priority = 9)
    {
        if (self::checkScript($script)) {
            $this->addJs((new Js($script))->setPriority($priority), true);
        }

        return $this;
    }

    protected function addClientScript($widget, array $options = [])
    {
        $this->addJs(new Js("$('#{$this->getDomId()}')." . $widget . "(" . json_encode($options) . ");", true), true);
        return $this;
    }

    /**
     * @todo remove when used in one single place only
     *
     * @param array $paramsMap
     * @param array $params
     *
     * @return array
     */
    public function getClientOptions(array $paramsMap = [], array $params = [])
    {
        $output = [];

        foreach ($paramsMap as $clientParamName => $paramName) {
            if (null !== $this->$paramName) {
                $output[is_string($clientParamName) ? $clientParamName : $paramName] = $this->$paramName;
            }
        }

        $output = array_merge($output, $params);

        return $output;
    }

    protected function addCoreScripts()
    {
        return $this->addJsScript('//code.jquery.com/ui/1.12.1/jquery-ui.js')
            ->addJsScript('@core/widget.js');
    }

    /**
     * @return Widget
     */
    protected function addScripts()
    {
        return $this;
    }

    /**
     * @return Widget
     */
    protected function addTexts()
    {
        return $this;
    }

    protected $attrs = [];

    public function addNodeAttr($k, $v = null)
    {
        $this->attrs[$k] = $v;
        return $this;
    }

    protected function getNode()
    {
        return $this->makeNode('div', array_merge($this->attrs, [
            'class' => $this->getDomClass(),
            'id' => $this->getDomId()
        ]));
    }

    protected function getInner($template = null)
    {
        return $this->stringifyContent($template);
    }

    public function isOk()
    {
        return true;
    }

    protected function stringifyInner($template)
    {
        if (!$this->isOk()) {
            return null;
        }

        return $this->stringifyWidget($template);
    }

    protected function stringifyWidget($template)
    {
        $this->addScripts();

        $node = $this->getNode();
        $inner = $this->getInner($template);

        return implode('', [
            $this->stringifyCss(),
            $node ? ($inner ? $node->append($inner, true) : $node) : $inner,
            $this->stringifyJs()
        ]);
    }

    public function stringifyPartial($template)
    {
//        return $this->stringifyContent(str_replace('.phtml', '.' . $template . '.phtml', $this->template));
        return $this->stringify(str_replace('.phtml', '.' . $template . '.phtml', $this->template));
    }
}