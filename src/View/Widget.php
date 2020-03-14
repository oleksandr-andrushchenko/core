<?php

namespace SNOWGIRL_CORE\View;

use SNOWGIRL_CORE\AbstractApp;
use SNOWGIRL_CORE\View;

abstract class Widget extends View
{
    protected $ns;
    protected $nameNs;
    protected $classes = [];
    protected static $index = 0;

    protected $domId;
    protected $texts = [];

    protected static $addedScripts = [];

    protected $attrs = [];

    public function __construct(AbstractApp $app, array $params = [], View $parent = null)
    {
        $this->setApp($app)
            ->setParent($parent)
            ->setTemplate($this->makeTemplate())
            ->setParams($this->makeParams($params));

        parent::__construct($app, $this->template, [], $parent);

        $this->addTexts();
    }

    public static function factory(AbstractApp $app, Layout $view): Widget
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

    protected function getNs(): array
    {
        return $this->ns ?: $this->ns = explode('\\', get_called_class());
    }

    protected function getNameNs(): array
    {
        return $this->nameNs ?: $this->nameNs = array_slice($this->getNs(), 3);
    }

    public function getDomId(): string
    {
        return $this->domId ?: $this->domId = ($this->getCoreDomClass() . '-' . $this->app->request->getServer('REQUEST_TIME') . '-' . self::$index++);
    }

    public function getCoreDomClass(): string
    {
        return 'widget-' . strtolower(implode('-', $this->getNameNs()));
    }

    public function addDomClass($class): Widget
    {
        $this->classes[] = $class;

        return $this;
    }

    public function getDomClass(): string
    {
        return $this->getCoreDomClass() . ' ' . implode(' ', $this->classes);
    }

    protected function makeTemplate(): string
    {
        return implode('', [
            array_flip($this->app->namespaces)[$this->getNs()[0]],
            '/widget/',
            strtolower(implode('.', $this->getNameNs())),
            '.phtml'
        ]);
    }

    protected function makeParams(array $params = []): array
    {
        return $params;
    }

    protected function addText($vocabulary): Widget
    {
        $this->texts = array_merge($this->texts, $this->app->trans->getVocabulary($vocabulary));

        return $this;
    }

    public static function checkScript(string $script): bool
    {
        if (in_array($script, static::$addedScripts)) {
            return false;
        }

        static::$addedScripts[] = $script;

        return true;
    }

    protected function addCssScript(string $script): Widget
    {
        if (self::checkScript($script)) {
            $this->addCss($script, false, false, true);
        }

        return $this;
    }

    protected function addJsScript(string $script): Widget
    {
        if (self::checkScript($script)) {
            $this->addJs($script, false, false, true);
        }

        return $this;
    }

    protected function addClientScript(string $widget, array $options = []): Widget
    {
        $this->addJs("$('#{$this->getDomId()}')." . $widget . "(" . json_encode($options) . ");", true, false, true);

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
    public function getClientOptions(array $paramsMap = [], array $params = []): array
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

    protected function addCoreScripts(): Widget
    {
        return $this->addJsScript('//code.jquery.com/ui/1.12.1/jquery-ui.js')
            ->addJsScript('@core/widget.js');
    }

    protected function addScripts(): Widget
    {
        return $this;
    }

    protected function addTexts(): Widget
    {
        return $this;
    }

    public function addNodeAttr($k, $v = null): Widget
    {
        $this->attrs[$k] = $v;

        return $this;
    }

    protected function getNode(): ?Node
    {
        return $this->makeNode('div', array_merge($this->attrs, [
            'class' => $this->getDomClass(),
            'id' => $this->getDomId()
        ]));
    }

    protected function getInner(string $template = null): ?string
    {
        return $this->stringifyContent($template);
    }

    public function isOk(): bool
    {
        return true;
    }

    protected function stringifyInner(string $template = null): string
    {
        if (!$this->isOk()) {
            return '';
        }

        return $this->stringifyWidget($template);
    }

    protected function stringifyWidget(string $template = null): string
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

    public function stringifyPartial($template): string
    {
//        return $this->stringifyContent(str_replace('.phtml', '.' . $template . '.phtml', $this->template));
        return $this->stringify(str_replace('.phtml', '.' . $template . '.phtml', $this->template));
    }
}