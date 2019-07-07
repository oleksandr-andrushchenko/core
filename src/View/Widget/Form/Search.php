<?php

namespace SNOWGIRL_CORE\View\Widget\Form;

use SNOWGIRL_CORE\View\Widget\Form;

/**
 * Class Search
 * @package SNOWGIRL_CORE\View\Widget\Form
 * @see https://twitter.github.io/typeahead.js/
 * @see https://github.com/twitter/typeahead.js
 * @see https://github.com/twitter/typeahead.js/blob/master/doc/jquery_typeahead.md
 */
class Search extends Form
{
    protected $inline = true;
    protected $large = false;
    protected $method = 'get';
    protected $queryParam = 'query';
    protected $query;
    protected $microdata = true;
    protected $suggestions = false;
    protected $suggestionsAction = 'get-search-suggestions';
    protected $suggestionsMinLength = 3;
    protected $suggestionsTypes;
    protected $suggestionsLimit = 10;
    protected $events = true;
    protected $eventCategory = 'search';
    protected $submit = false;
    protected $submitButtonText = false;

    public function getSuggestionsTypes()
    {
        if ($tmp = $this->app->config->site->search_in('')) {
            return array_filter(array_map('trim', explode(',', $tmp)), function ($manager) {
                return !!$manager;
            });
        }

        return ['pages'];
    }

    protected function makeTemplate()
    {
        return '@core/widget/form/search.phtml';
    }

    protected function makeParams(array $params = [])
    {
        return array_merge(parent::makeParams($params), [
            'suggestionsTypes' => $this->getSuggestionsTypes(),
            'query' => $this->app->request->get($this->queryParam)
        ]);
    }

    protected function addTexts()
    {
        return parent::addTexts()
            ->addText('widget.form.search');
    }

    protected function addScripts()
    {
        return parent::addScripts()
            ->addCoreScripts()
            ->addJsScript('@core/widget/search.js')
            ->addClientScript('search', $this->getClientOptions([
                'queryParam',
                'large',
                'suggestions',
                'suggestionsAction',
                'suggestionsMinLength',
                'suggestionsTypes',
                'suggestionsLimit',
                'events',
                'eventCategory',
                'submit'
            ], [
                'texts' => $this->texts
            ]));
    }

    protected function stringifyPrepare()
    {
        if ($this->microdata) {
            $this->addNodeAttr('itemscope')
                ->addNodeAttr('itemtype', 'http://schema.org/SearchAction');
        }

        return parent::stringifyPrepare();
    }

    public function isOk()
    {
        return $this->suggestions || $this->submit;
    }
}