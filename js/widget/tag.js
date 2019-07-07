'use strict';
//@todo merge adapter files with this widget! [/bootstrap-tagsinput/bootstrap-tagsinput.sv.js]
/**
 * @class tag
 * @see https://bootstrap-tagsinput.github.io/bootstrap-tagsinput/examples/
 */
var widget = {};

widget.options = {
    name: '',
    value: [],
    uri: '',
    multiple: false,
    wildcard: '%query',
    valueKey: 'id',
    labelKey: 'name',
    trimValue: true,
    template: '{value}-{label}'
};

widget._create = function () {
    this.input = this.element.closestDown('[name]');

    if (!this.input.is('select')) {
        var _$this = $('<select/>', {
            name: this.input.attr('name') + (this.options.multiple ? '[]' : ''),
            placeholder: this.input.attr('placeholder'),
            multiple: this.options.multiple ? 'multiple' : false
        }).hide();
        this.input.hide();
        this.input.replaceWith(_$this);
        this.input = _$this;
    }

    var scripts = [
        '/js/core/bootstrap-tagsinput/bootstrap-tagsinput.sv.css',
        '/js/core/bootstrap-tagsinput/bootstrap-tagsinput.sv.js'
    ];

    if (this.options.uri) {
        scripts.unshift('/js/core/typeahead.js/typeahead.sv.css');
        scripts.unshift('/js/core/typeahead.js/typeahead.bundle.min.js');
    }

    snowgirlCore.getScriptLoader().get(scripts, $.proxy(function () {
        var options = this.options;

        var display = $.proxy(function (item) {
            return this.options.template
                .replace('{value}', item[this.getValueKey()])
                .replace('{label}', item[this.getLabelKey()]);
        }, this);

        options.itemValue = options.valueKey;
//        options.itemText = options.labelKey;
        options.itemText = display;

        options.tagClass = function () {
            return 'label label-primary badge badge-info';
        };

        if (options.uri) {
            var engine = new Bloodhound({
                datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
                queryTokenizer: Bloodhound.tokenizers.whitespace,
                limit: 999,
                remote: {
                    url: options.uri,
                    wildcard: options.wildcard,
                    rateLimitBy: 'debounce',
                    rateLimitWait: 0,
                    filter: function (response) {
                        return response;
                    },
                    ajax: {}
                }
            });

            engine.initialize();

            options.typeahead = {
                source: engine.ttAdapter(),
                display: display,
                templates: {
                    suggestion: display
                }
            };
        }

        this.input.tagsinput(options);

        var i;

        if (options.value) {
            for (i = 0; i < options.value.length; i++) {
                this.addItem(options.value[i]);
            }
        }

        this.input.on('itemAdded', $.proxy(function (ev) {
            this._promise('itemAdded', [ev, ev.item], function () {

            });
        }, this));

        this.input.on('itemRemoved', $.proxy(function (ev) {
            this._promise('itemRemoved', [ev, ev.item], function () {

            });
        }, this));
    }, this));

    this._superApply(arguments);
};

widget.getItems = function () {
    return this.input.tagsinput('items');
};

widget.getValueKey = function () {
    return this.options.valueKey;
};
widget.getLabelKey = function () {
    return this.options.labelKey;
};

//an object with keys: this.options.valueKey and this.options.labelKey
widget.addItem = function (item) {
    this.input.tagsinput('add', item);
    return this;
};

widget.addValue = function (value, label) {
    var item = {};
    item[this.getValueKey()] = value;
    item[this.getLabelKey()] = label ? label : value;
    this.addItem(item);
    return this;
};

//@todo update this.options.value on changes and remove this method... (parent one is universal)
widget.value = function (forceArray) {
    var output = [];
    var key = this.getValueKey();

    $(this.getItems()).each(function (i, el) {
        output.push(el[key]);
    });

    if (this.options.multiple || forceArray) {
        return output;
    }

    if (output.length) {
        return output[output.length - 1];
    }

    return null;
};

widget.focus = function () {
    return this.element.find('input.tt-input').focus();
};

snowgirlCore.registerWidget('tag', 'input', widget);
