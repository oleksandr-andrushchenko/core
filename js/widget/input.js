/**
 * Created by snowgirl on 11/20/17.
 */

var widget = {};

widget.options = {
    name: '',
    value: [],
    multi: false
};

widget._create = function () {
    this.input = this.element.closestDown('[name]');
//    this._value = this.options.value;

    this._superApply(arguments);
};

widget._promise = function (triggerName, triggerArgs, fn) {
    //@todo add dynamic promises count..

    return Promise.resolve(null)
        .then($.proxy(function () {
            if ($.isFunction(this.options[triggerName])) {
                triggerArgs = $.makeArray(triggerArgs);
//                triggerArgs.unshift(ev);

                return this.options[triggerName].apply(null, triggerArgs);
            }

            return null;
        }, this))
        .then($.proxy(fn, this));
};

//widget.value = function () {
//    return this.options.value;
//};

widget.value = function (forceArray) {
    var output = this.options.value;

    if (this.options.multi || forceArray) {
        return output;
    }

    if (output.length) {
        return output[output.length - 1];
    }

    return null;
};

widget.inputValue = function () {
    return this.input.val();
};

snowgirlCore.registerWidget('input', 'core', widget);