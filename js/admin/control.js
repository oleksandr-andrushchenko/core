var snowgirlApp = function (snowgirlCore) {
    this.core = snowgirlCore;

//    this.initArgs();
//    this.initDOM();
    this.initCallbacks();
};
snowgirlApp.prototype.initCallbacks = function () {
    this.core.$document
        .on('blur', '.transcript [name=src]', $.proxy(this.onTranscriptInputBlur, this))
        .on('blur', '.md5 [name=src]', $.proxy(this.onMd5InputBlur, this))
        .on('click', '.btn-container .btn', $.proxy(this.onContainerButtonClick, this));
};
snowgirlApp.prototype.onTranscriptInputBlur = function (ev) {
    var $this = $(ev.target);
    if ($this.val()) {
        this.core.makeRequestByRoute('admin', {action: 'transcript', src: $this.val()})
            .then(function (data) {
                $this.closestUp('form').find('[name=result]').val(data.body);
            });
    }
};
snowgirlApp.prototype.onMd5InputBlur = function (ev) {
    var $this = $(ev.target);
    if ($this.val()) {
        this.core.makeRequestByRoute('admin', {action: 'md5', src: $this.val()})
            .then(function (data) {
                $this.closestUp('form').find('[name=result]').val(data.body);
            });
    }
};
snowgirlApp.prototype.onContainerButtonClick = function (ev) {
    $(ev.target).toggleLoading();
    new this.core.getLoadingObject('Выполняю');
};

new snowgirlApp(snowgirlCore);