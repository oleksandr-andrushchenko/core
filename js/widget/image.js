/**
 * Created with JetBrains PhpStorm.
 * User: snowgirl
 * Date: 14.11.17
 * Time: 04:47
 * To change this template use File | Settings | File Templates.
 */

'use strict';

/**
 * @class image
 */
var widget = {};

widget.options = {
    name: '',
    value: [],
    multiple: false,
    addOnChange: false,
    formats: ['image/jpeg', 'image/png', 'image/jpg']
};

widget._create = function () {
    this.input = this.element.closestDown('[name]');
    this.preview = this.element.find('.preview');
    this.message = this.element.find('.message');
    this.group = this.element.find('.input-and-buttons');
    this.post = this.element.find('.btn-post');
    this.delete = this.element.find('.btn-delete');

    this._on(this.element, {
        'change [name]': this._change,
        'click .btn-post': this._add,
        'click .btn-delete': this._delete
    });

    this._superApply(arguments);
};

widget._change = function (ev) {
    this.message.empty().hide();

    this.file = ev.target.files[0];

    if (-1 == this.options.formats.indexOf(this.file.type)) {
        this.preview.attr('src', '').hide();
        this.message.text('please input image with valid format: ' + this.options.formats.join(', ')).show();
        return false;
    }

    var reader = new FileReader();

    reader.onload = $.proxy(function (ev) {
        this.preview.attr('src', ev.target.result).show();
        this.post.show();

        if (this.options.addOnChange) {
            this._add($.Event('click', {target: this.post[0]}));
        }
    }, this);

    reader.readAsDataURL(this.file);
};

widget._add = function (ev) {
    this.message.empty().hide();
    this.post.attr('disabled', true);
    this.delete.attr('disabled', true);

    this._promise('itemBeforeAdded', [ev], function () {
        var data = new FormData();
        data.append('file', this.file);

        var ajax = jQuery
            .ajax({
                url: this.options.uri,
                type: 'post',
                data: data,
                contentType: false,
                cache: false,
                processData: false
            });

        ajax.always($.proxy(function () {
            this.post.attr('disabled', false);
            this.delete.attr('disabled', false);
        }, this));

        ajax.done($.proxy(function (data, status) {
            this.options.value.push(data.hash);

            this._promise('itemAdded', [ev, data.hash, status], function () {
                this.post.hide();
                this.delete.show();
            });
        }, this));

        ajax.fail($.proxy(function (xhr, status) {
            this.message.text(status).show();
        }, this));
    });
};

widget._delete = function (ev) {
    this.message.empty().hide();
    this.post.attr('disabled', true);
    this.delete.attr('disabled', true);

    var file = this.value()[0];

    this._promise('itemBeforeRemoved', [ev], function () {
        var ajax = jQuery
            .ajax({
                url: this.options.uri,
                dataType: 'json',
                type: 'delete',
                data: {file: file}
            });

        ajax.always($.proxy(function () {
            this.post.attr('disabled', false);
            this.delete.attr('disabled', false);
        }, this));

        ajax.done($.proxy(function (data, status) {
            this._promise('itemRemoved', [ev, file, status], function () {
                this.preview.attr('src', '').hide();
                this.delete.hide();
            });
        }, this));

        ajax.fail($.proxy(function (xhr, status) {
            this.message.text(status).show();
        }, this));
    });
};

snowgirlCore.registerWidget('image', 'input', widget);
