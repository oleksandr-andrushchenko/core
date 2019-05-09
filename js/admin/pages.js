/**
 * Created by snowgirl on 2/1/18.
 */

var snowgirlApp = function (snowgirlCore) {
    this.core = snowgirlCore;

//    this.initArgs();
//    this.initDOM();
    this.initCallbacks();
};
snowgirlApp.prototype.initCallbacks = function () {
    this.core.$document
        .on('change', '.form-page [name]', $.proxy(this.onPageActiveChange, this));
};
snowgirlApp.prototype.onPageActiveChange = function (ev) {
    var $this = $(ev.target);
    var $form = $this.parents('form');

    if (confirm('Также, все обращения к этой странице будут отдавать 404 код, продолжить?')) {
        new this.core.getLoadingObject();
        $form.submit();
    }
};

new snowgirlApp(snowgirlCore);
