'use strict';

/**
 * @class $.sv.mediainput
 */
var widget = {};

var mediaPk = 'media_id';

widget.options = {
    mediaListWidgetSelector: null,
    progressSelector: null,
    formKey: mediaPk,
    items: [],
    isMultiple: false,
    default: null,
    isCover: false
};

widget._create = function () {
    var $wrapper = this.element.parent();
//        console.log(this.options);

    if (this.options.items && !$.isArray(this.options.items)) {
        this.options.items = [this.options.items];
    }
//        this.options.items = this.options.items || [];

//        if (!this.options.items.length && this.options.default) {
//            this.options.items = this.options.default;
//        }

    if (this.options.mediaListWidgetSelector) {
        this.list = $(this.options.mediaListWidgetSelector);
    } else if ($wrapper.find('.widget-media-list').length) {
        this.list = $wrapper.find('.widget-media-list');
    } else {
        this.list = $('<div/>', {class: 'media-list-widget'}).appendTo($wrapper);
    }

    if (this.list.data('svMedialist')) {
        this.list = this.list.data('svMedialist');
    }

    if (this.options.progressSelector) {
        this.progress = $(this.options.progressSelector);
    } else if ($wrapper.find('.progress').length) {
        this.progress = $wrapper.find('.progress');
    } else {
        this.progress = $('<div/>', {class: 'progress'})
            .append($('<div/>', {
                class: 'progress-bar progress-bar-info',
                role: 'progressbar',
                'aria-valuenow': 0,
                'aria-valuemin': 0,
                'aria-valuemax': 100
            }))
            .hide()
            .insertBefore(this.list);
    }

    if (this.options.formKey == null) {
        if (this.element.attr('name')) {
            this._setOption('formKey', this.element.attr('name'));
            this.element.attr('name', null);
        } else {
            this._setOption('formKey', mediaPk);
        }
    }

    if (this.options.items.length) {
//            console.log(this.options.items);
        if (typeof this.options.items == 'string') {
            this._setOption('items', [new sovpalo.models.Media({'media_id': this.options.items})]);
        } else if (!$.isArray(this.options.items)) {
            this._setOption('items', [this.options.items]);
        }
        this._initList();
    }

    this._on({
        'mouseover': this._mouseOver
    });
    this._trigger('mouseover');
//        console.log(this.list);
};
widget._initList = function (o, fn) {
    if (this.list instanceof jQuery) {
        snowgirlCore.loadWidget('CatalogMedia', $.proxy(function () {
            this.list = this.list
                .medialist($.extend(true, {}, {items: this.options.items, formKey: this.options.formKey}, o || {}))
                .medialist('instance');
            this._on({
                'medialist-update': this._update
            });
            fn && fn();
            this._trigger('-list-ready');
        }, this));
    } else if (fn) {
        fn();
    }
};
widget._update = function () {
    var items = this.list.option('items');
    if (this.options.default && !items.length) {
        this.list.prepend(this.options.default);
    }
    if (this.options.isCover) {
        $('.cover').css({'background-image': 'url(' + (new sovpalo.models.Media(items.length ? items[0] : this.options.default)).getSrc() + ')'});
    }
};
widget._mouseOver = function () {
    this._off(this.element, 'mouseover');
    __sv.loader.get([
//            '/jQuery-File-Upload-9.8.1/js/vendor/jquery.ui.widget.js',
        '/jQuery-File-Upload-9.8.1/js/jquery.iframe-transport.js',
        '/jQuery-File-Upload-9.8.1/js/jquery.fileupload.js'
    ], $.proxy(function () {
        this.element.css({position: 'relative', overflow: 'hidden'});
        var $upload = $('<input/>', {
            type: 'file',
            name: 'file[]',
            multiple: this.options.isMultiple ? 'multiple' : null
        }).css({
            position: 'absolute',
            top: 0,
            right: 0,
            opacity: 0,
            cursor: 'pointer',
            'font-size': '40px'
        });
        this.element.append($upload);
        $upload
            .fileupload({
                url: sovpalo.getUriByRoute('default', {controller: 'media', action: 'upload'}),
                dataType: 'json',
                dropZone: this.element,
                singleFileUploads: false,
                sequentialUploads: true,
                processData: false,
                contentType: false,
                cache: false,
                multiple: this.options.isMultiple
            })
            .on('fileuploaddone', $.proxy(function (ev, data) {
//                    console.log('fileupload-done', data);
                if (data.multiple) {
                    if (this.list instanceof jQuery) {
                        this._initList({items: data.result.data});
                    } else {
                        this.list.prepend(data.result.data);
                    }
                } else {
                    if (this.list instanceof jQuery) {
                        this._initList({items: data.result.data});
                    } else {
                        this.list.set(data.result.data);
                    }
                }
            }, this))
            .on('fileuploadprogressall', $.proxy(function (e, data) {
                this.progress.progress(parseInt(data.loaded / data.total * 100, 10));
            }, this))
            .prop('disabled', !$.support.fileInput);
        this.element.addClass($.support.fileInput ? undefined : 'disabled');
    }, this));
};
widget.getList = function () {
    return this.list;
};

widget.getItemCount = function () {
    if (this.list instanceof jQuery) {
        return 0;
    } else {
        return this.list.get().length;
    }
};

snowgirlCore.registerWidget('FormInputMedia', widget);


