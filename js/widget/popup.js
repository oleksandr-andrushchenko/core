/**
 * Created by snowgirl on 12/24/17.
 */

'use strict';

/**
 * @class popup
 */
var widget = {};

widget.options = {
    modal: false,
    showIn: false,
    title: null,
    width: 300
};

widget._create = function () {
    this.element.dialog({
        modal: this.options.modal,
        autoOpen: 0 === typeof this.options.showIn,
        title: this.options.title,
        width: this.options.width,
        focus: $.proxy(function () {
            this.element.find('input').blur();
        }, this),
        buttons: [
            {
                text: 'ОК',
                click: $.proxy(function () {
                    this.element.dialog('close');
                }, this)
            }
        ]
    });

    if (('number' == typeof this.options.showIn) && this.options.showIn > 0) {
        setTimeout($.proxy(function () {
            this.show();
        }, this), 1000 * this.options.showIn);
    }

//    this.dialog = this.element.dialog('instance');

    this._superApply(arguments);
};

widget._destroy = function () {
    this.element.dialog('destroy');
};

widget.show = function () {
    this.element.dialog('open');
};

widget.hide = function () {
    this.element.dialog('close');
};

snowgirlCore.registerWidget('popup', widget);