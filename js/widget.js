/**
 * Created by snowgirl on 11/20/17.
 */

var widget = {};

widget.options = {};

widget._create = function () {
    this._superApply(arguments);
    this._trigger('.ready');
};

snowgirlCore.registerWidget('core', widget);
