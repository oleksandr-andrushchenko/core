/**
 * Created by snowgirl on 2/4/18.
 */

'use strict';

/**
 * @class tinymce
 * @see https://www.tinymce.com/docs
 */
var widget = {};

widget.options = {
    name: '',
    value: [],
    imageUploadUri: '',
    height: 450,
    language: 'ru'
};

widget._create = function () {
    this.input = this.element.closestDown('[name]');

    var scripts = [];

    scripts.push('/js/snowgirl-core/tinymce/tinymce.min.js');

    snowgirlCore.getScriptLoader().get(scripts, $.proxy(function () {
        tinymce.init({
            selector: '#' + this.input.attr('id'),
            language: this.options.language,
            language_url: '/js/snowgirl-core/tinymce/langs/' + this.options.language + '.js',
            height: this.options.height,
            plugins: [
                "advlist autolink autosave link image lists charmap print preview hr anchor pagebreak",
                "searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking",
                "table contextmenu directionality emoticons template textcolor paste textcolor colorpicker textpattern"
            ],

            toolbar1: "newdocument undo redo cut copy paste searchreplace print | removeformat fullscreen code preview",
            toolbar2: "formatselect forecolor backcolor | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | outdent indent",
            toolbar3: "bullist numlist | table link unlink anchor image media insertdatetime | visualchars visualblocks",
            menubar: false,
            toolbar_items_size: 'small',
            autoresize_bottom_margin: 15,
            contextmenu_never_use_native: true,
            image_advtab: true,
            content_css: [
                //taken from View/Layout::addCssNodes
                '//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css',
                '/css/snowgirl-core/core.fonts.css',
                '/css/snowgirl-core/core.grid.css',
                '/css/snowgirl-core/core.css',
                '/css/snowgirl-core/core.header.css',
                '/css/snowgirl-core/core.breadcrumbs.css',
                '//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css'
            ],
            invalid_elements: 'script',
            relative_urls: true,
            document_base_url: snowgirlCore.getConfig('domains')['master'],
            browser_spellcheck: true,
            forced_root_block: false,
            force_br_newlines: true,
            force_p_newlines: false,
            images_upload_url: this.options.imageUploadUri,
            images_upload_base_path: '',
            images_upload_credentials: true,
            images_upload_handler: $.proxy(function (blobInfo, success, failure) {
                var data = new FormData();
//                data.append('file', blobInfo.blob(), fileName(blobInfo));
                data.append('file', blobInfo.blob());

                return jQuery
                    .ajax({
                        url: this.options.imageUploadUri,
                        type: 'post',
                        data: data,
                        contentType: false,
                        cache: false,
                        processData: false
                    }).always(function () {
                    }).done(function (data, status) {
                        if ('success' == status && data.link) {
                            success(data.link);
                        } else {
                            failure('HTTP Error: ' + status);
                        }
                    }).fail(function (xhr, status) {
                        failure('HTTP Error: ' + status);
                    });
            }, this),
            init_instance_callback: $.proxy(function () {
                this.element.find('.mce-tinymce').css({
                    'border': '0',
                    'box-shadow': 'none'
                });
            }, this)
        });
    }, this));

    this._superApply(arguments);
};

snowgirlCore.registerWidget('tinymce', 'input', widget);
