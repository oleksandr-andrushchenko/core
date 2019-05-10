/**
 * Created by snowgirl on 3/9/18.
 */

'use strict';

/**
 * @class search
 */
var widget = {};

widget.options = {
    queryParam: 'query',
    large: false,
    suggestions: false,
    suggestionsAction: 'get-search-suggestions',
    suggestionsMinLength: 3,
    suggestionsTypes: ['pages'],
    suggestionsLimit: 10,
    events: false,
    eventCategory: 'search',
    submit: true,
    texts: {}
};

widget._create = function () {
    this.input = this.element.closestDown('.input-query');

    this._on(this.input, {
        focus: this._focus
    });

    this._on(this.element, {
        submit: this._submit
    });

    this._superApply(arguments);
};

widget._focus = function () {
    this._off(this.input, 'focus');

    snowgirlCore.getScriptLoader().get([
        '/css/core/typeahead.css',
        '/js/core/typeahead.bundle.min.js'
    ], $.proxy(this._initialize, this));

    return false;
};
widget._initialize = function () {
    this._on(this.input, {
        focus: $.proxy(function () {
            this.input.trigger('keyup');
        }, this),
        keyup: this._keyup
    });

    var wildcard = '%' + this.options.queryParam;
    var args = [];
    var typesCount = this.options.suggestionsTypes.length;

    args.push({
        minLength: this.options.suggestionsMinLength,
        hint: true,
        highlight: true
    });

    for (var i = 0; i < typesCount; i++) {
        var type = this.options.suggestionsTypes[i];

        var source = new Bloodhound({
            datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
            queryTokenizer: Bloodhound.tokenizers.whitespace,
            identify: function (o) {
                return o.id;
            },
            remote: {
                url: snowgirlCore.getUriByRoute('default', {
                    action: this.options.suggestionsAction,
                    type: type,
                    //@todo fix multiple sources remotes (empty results)
//                    __limit: typesLimit,
                    query: wildcard
                }),
//                prepare: function (query, settings) {
//                    console.log(settings);
//                    settings['url'] = settings['url'].replace(wildcard, query) + '&limit=' + typesLimit;
//                    settings['cache'] = false;
//                    return settings;
//                },
//                replace: function (url, query) {
//                    return url + "#" + query + type;
//                },
                wildcard: wildcard
            }
        });

        var templates = {
            pending: $.proxy(this._getSuggestionsPendingNode, this),
            notFound: $.proxy(this._getSuggestionsNotFoundNode, this),
            suggestion: $.proxy(this._getSuggestionsValueNode, this)
        };

        if (1 < typesCount) {
            templates.header = '<div class="tt-header">' + this.options.texts[type + 'Header'] + '</b>';
        }

        args.push({
            name: type,
            display: 'value',
            source: source,
            templates: templates
        });
    }

    this.input.typeahead.apply(this.input, args);

    if (this.options.events) {
        var fnOnSelect = $.proxy(function (ev, suggestion) {
            ev.preventDefault();

            console.log(ev, suggestion);

            snowgirlCore.gtag.sendEvent('select_content', {
//                _category: 'engagement',
//                _action: 'select_content',
//                _label: 'content_type',
                _category: this.options.eventCategory,
                content_type: suggestion.type,
//                search_term:
//                items: '',
//                promotions: '',
                content_id: suggestion.id,
                content_url: suggestion.link,
                content_title: suggestion.value,
                _callback: function () {
                    window.location.href = suggestion.link;
                }
            });

            return false;
        }, this);

        this.input.on('typeahead:select', fnOnSelect)
            .on('typeahead:autocomplete', fnOnSelect);
    }

    if (1 < typesCount) {
        this.input.on('typeahead:active', $.proxy(function () {
            if (this.options.large) {
                this.input.addClass('input-lg');
            }
        }, this));

        this.input.on([
            'typeahead:active',
//            'typeahead:idle',
            'typeahead:open',
//            'typeahead:close',
            'typeahead:change',
            'typeahead:render'
//            'typeahead:select',
//            'typeahead:autocomplete',
//            'typeahead:cursorchange',
//            'typeahead:asyncrequest',
//            'typeahead:asynccancel',
//            'typeahead:asyncreceive'
        ].join(' '), $.proxy(this._hideDuplicates, this));
    }

    this.input.focus();
};
widget._hideDuplicates = function () {
    var $typeahead = this.input.closestUp('.twitter-typeahead');

    if (0 === $typeahead.find('.tt-suggestion').length) {
        $typeahead.find('.tt-not-found:not(:first)').hide();
    } else {
        $typeahead.find('.tt-not-found').hide();
    }

    $typeahead.find('.tt-pending:not(:first)').hide();
};
widget._keyup = function () {
    var $typeaheadMenu = this.input.closestUp('.twitter-typeahead').find('.tt-menu');
    var query = this.input.val();

    $typeaheadMenu.find('.tt-typing').remove();

    if (this.options.suggestionsMinLength > query.length) {
        $typeaheadMenu
            .removeClass('tt-empty')
            .addClass('tt-open')
            .css({display: 'block'})
            .prepend(this._getSuggestionsTypingNode(query));
    }

    if (this.options.events) {
        if (query.length > 2) {
            snowgirlCore.gtag.sendEvent('search', {
//            _category: 'engagement',
//            _action: 'search',
//            _label: 'search_term',
                _category: this.options.eventCategory,
                search_term: query
            });
        }
    }
};

widget._getSuggestionsTypingNode = function (query) {
    var text;

    if (0 === query.length) {
        text = this.options.texts['startTyping'];
    } else {
        var tmp = this.options.suggestionsMinLength - query.length;

        if (1 === tmp) {
            tmp = tmp + ' ' + this.options.texts['symbol'];
        } else if (tmp < 5) {
            tmp = tmp + ' ' + this.options.texts['symbols'];
        }

        text = this.options.texts['typeMore'].replace('%s', tmp);
    }

    return '<div class="tt-menu-item tt-typing">' +
        '<div class="inner-table">' +
        '<div class="inner-cell"><span class="fa fa-search"></span></div>' +
        '<div class="inner-cell">' + text + '</div>' +
        '</div>' +
        '</div>';
};
widget._getSuggestionsPendingNode = function () {
    return '<div class="tt-menu-item tt-pending">' +
        '<div class="inner-table">' +
        '<div class="inner-cell"><span class="fa fa-circle-o-notch fa-spin fa-fw"></span></div>' +
        '<div class="inner-cell">' + this.options.texts['pending'] + '</div>' +
        '</div>' +
        '</div>';
};
widget._getSuggestionsValueNode = function (o) {
    var check = '<div class="inner-cell"><span class="fa fa-check"></span></div>';

    if (o.hasOwnProperty('view')) {
        var $view = $(o.view).addClass('tt-menu-item tt-suggestion tt-selectable');

        $view.find('.inner-table').prepend(check);

        return $view.prop('outerHTML');
    }

    return '<div class="tt-menu-item tt-suggestion tt-selectable">' +
        '<div class="inner-table">' +
        check +
        '<div class="inner-cell">' + o.value + '</div>' +
        '</div>' +
        '</div>';
};
widget._getSuggestionsNotFoundNode = function (o) {
    return '<div class="tt-menu-item tt-not-found">' +
        '<div class="inner-table">' +
        '<div class="inner-cell"><span class="fa fa-frown-o"></span></div>' +
        '<div class="inner-cell">' + this.options.texts['notFound'].replace('%s', '<span class="query">' + o.query + '</span>') + '</div>' +
        '</div>' +
        '</div>';
};

widget._submit = function (ev) {
    if (!this.options.submit) {
        ev.preventDefault();
        this.input.focus();
        return false;
    }
};

snowgirlCore.registerWidget('search', widget);

