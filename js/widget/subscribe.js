/**
 * Created by snowgirl on 12/21/17.
 */

'use strict';

/**
 * @class subscribe
 */
var widget = {};

widget.options = {};

widget._create = function () {
//    this.input = this.element.closestDown('[name]');

    this._on(this.element, {
        'submit': this._submit
    });

    this._superApply(arguments);
};

widget._submit = function (ev) {
    ev.preventDefault();

    if (!this._validate(this.element.find('[name=email]').val())) {
        return false;
    }

    this.element.submitByAjax($.proxy(function (data) {
        this.element.empty().append(data.body);
    }, this));

    return false;
};

widget._validate = function (email) {
    return /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/.test(email.toLowerCase());
};

snowgirlCore.registerWidget('subscribe', widget);
