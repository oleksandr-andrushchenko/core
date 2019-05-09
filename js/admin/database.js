var snowgirlApp = function (snowgirlCore) {
    this.core = snowgirlCore;

    this.initArgs();
//    this.initDOM();
    this.initCallbacks();

//    return this.export();
};
//snowgirlApp.prototype.export = function () {
//    return $.extend({}, this.core, {
//    });
//};
snowgirlApp.prototype.initArgs = function () {
    this.tableSelector = '.table';
    this.table = this.core.getConfig('table');
    this.columns = this.core.getConfig('columns');
    this.forbiddenColumns = this.core.getConfig('forbiddenColumns');
    this.tagInputWidgetSelector = '.widget-tag';
    this.imageInputWidgetSelector = '.widget-image';
    this.insertTrClass = 'insert';
    this.insertTrSelector = this.tableSelector + ' tr.' + this.insertTrClass;
};
snowgirlApp.prototype.initDOM = function () {
//    $('td[data-key=image]').add($('td[data-key=images]')).each($.proxy(function (i, el) {
//        this.showImage($(el));
//    }, this));
};
snowgirlApp.prototype.initCallbacks = function () {
    var td = this.tableSelector + ' tr:not(' + this.insertTrSelector + ') td[data-key]';

    this.core.$document
        .on('change', '[name=table]', $.proxy(this.onTableSelectChange, this))
        .on('click', '.btn-insert', $.proxy(this.onInsertButtonClick, this))
        .on('click', td + ':not(:has([class*=widget]))', $.proxy(this.onTableCellClick, this))
        .on('tag.ready', td + ' ' + this.tagInputWidgetSelector, $.proxy(function (ev) {
            $(ev.target).tag('option', 'itemAdded', $.proxy(this.onTableCellTagAdded, this))
                .tag('option', 'itemRemoved', $.proxy(this.onTableCellTagRemoved, this));
        }, this))
        .on('image.ready', td + ' ' + this.imageInputWidgetSelector, $.proxy(function (ev) {
            $(ev.target).image('option', 'itemAdded', $.proxy(this.onTableCellImageAdded, this))
                .image('option', 'itemBeforeRemoved', $.proxy(this.onTableCellBeforeImageRemoved, this));
        }, this))
        .on('click', this.tableSelector + ' .btn-save', $.proxy(this.onSaveButtonClick, this))
        .on('click', this.tableSelector + ' .btn-delete', $.proxy(this.onDeleteButtonClick, this))
        .on('click', this.tableSelector + ' .btn-copy', $.proxy(this.onCopyButtonClick, this));
};

snowgirlApp.prototype.getTableCellDataByCellElement = function (element) {
    var $element = $(element);
    var $td = $element.closestUp('td');
    var $tr = $element.closestUp('tr');

    var id = $tr.data('id');
    var key = $td.data('key');

    return {
        $tr: $tr,
        $td: $td,
        id: id,
        key: key
    };
};
snowgirlApp.prototype._onTableCellTagUpdate = function (element, value) {
    var data = this.getTableCellDataByCellElement(element);

    return this.core.updateRow(this.table, data.id, data.key, value)
        .then(function (body) {
            for (var column in body.data) {
                if (body.data.hasOwnProperty(column)) {
                    data.$tr.find('td[data-key=' + column + ']:not(:has([class*=widget]))')
                        .html(null === body.data[column] ? '' : body.data[column]);
                }
            }

            data.$tr.attr('data-id', body.id).data('id', body.id);
            return true;
        })
        .catch(function (error) {
            console.log(error);
            return false;
        });
};
snowgirlApp.prototype.onTableCellTagAdded = function (ev, item) {
    return this._onTableCellTagUpdate(ev.target, item.id);
};
snowgirlApp.prototype.onTableCellTagRemoved = function (ev, item) {
    return this._onTableCellTagUpdate(ev.target, '');
};
snowgirlApp.prototype.onTableCellImageAdded = function (ev, hash) {
    return this._onTableCellTagUpdate(ev.target, hash);
};
snowgirlApp.prototype.onTableCellBeforeImageRemoved = function (ev) {
    return this._onTableCellTagUpdate(ev.target, '');
};
snowgirlApp.prototype.onTableSelectChange = function (ev) {
    $(ev.target).closestUp('form').submit();
};
snowgirlApp.prototype.onInsertButtonClick = function () {
    var $trInsert = $(this.insertTrSelector);

    if (!$trInsert.length) {
        $trInsert = $('<tr/>', {class: this.insertTrClass});
        $(this.columns).each($.proxy(function (index, column) {
            $trInsert.append($('<td/>', {'data-key': column}).append(
                this.forbiddenColumns.indexOf(column) == -1 ? this.$textarea(column) : ''
            ));
        }, this));
        $trInsert.append($('<td/>').append(
            $('<button/>', {
                class: 'btn btn-success btn-save',
                type: 'button'
            })
                .append($('<span/>', {class: 'fa fa-save'}))
                .append(' ')
                .append('Сохранить')
        ));
        $(this.tableSelector).find('tbody').prepend($trInsert);
        $trInsert.show().find('[name]:first').focus();
    } else if ($trInsert.is(':visible')) {
        $trInsert.hide();
    } else {
        $trInsert.show().find('[name]:first').focus();
    }
};
snowgirlApp.prototype.onSaveButtonClick = function (ev) {
    var data = {};

    $(ev.target).closestUp('tr').find('td[data-key]').each($.proxy(function (index, td) {
        var tmp = this.getKeyValueByTd(td);

        if (tmp[1]) {
            data[tmp[0]] = tmp[1];
        }
    }, this));

    this.core.insertRow(this.table, data)
        .then(function () {
            location.reload();
        })
        .catch(function () {
            alert('error on save');
        });
};
snowgirlApp.prototype.getKeyValueByTd = function (td) {
    var $td = $(td);

    var key = $td.data('key');
    var value;

    var $input = $td.find('> textarea');

    if ($input.length) {
        value = $input.val();
    } else {
        var $widget = $td.find('.widget-tag');

        if ($widget.length) {
            value = $widget.tag('value');
        } else {
            $widget = $td.find('.widget-image');

            if ($widget.length) {
                value = $widget.image('value');
            }
        }
    }

    if (value) {
        value = $.trim(value);
    }

    return [key, value];
};
snowgirlApp.prototype.onTableCellClick = function (ev) {
    var $this = $(ev.target).closestUp('td');

    if ($this.hasClass('editing')) {
        return true;
    }

    $this.addClass('editing');
    var key = $this.data('key');
    var value = $.trim($this.html());

    var $input = this.$textarea(key, value);

    $input.one('blur', $.proxy(function () {
        var newValue = $.trim($input.val());

        if (newValue != value) {
            this._onTableCellTagUpdate(ev.target, newValue);
        }

        $this.empty();
        $this.append(newValue);
        $this.off('blur');
        $this.removeClass('editing');
    }, this));

    $this.empty().append($input);
    $input.focus();

    return true;
};
snowgirlApp.prototype.onDeleteButtonClick = function (ev) {
    var $this = $(ev.target);
    var $tr = $this.closestUp('tr');
    var id = $tr.data('id');

    if (!id) {
        alert('Удаление записей из этой таблицы не доступно... Только добавление... @todo');
        return false;
    }

    this.core.deleteRow(this.table, id)
        .then(function () {
            location.reload();
        });
};
snowgirlApp.prototype.onCopyButtonClick = function (ev) {
    var $this = $(ev.target);
    var $tr = $this.closestUp('tr');
    var $trInsert = $(this.insertTrSelector);

    if ($trInsert.length) {
        if (!$trInsert.is(':visible')) {
            $trInsert.show();
        }
    } else {
        $('.btn-insert:first').trigger('click');
        $trInsert = $(this.insertTrSelector);
    }

    $(this.columns).each($.proxy(function (index, column) {
        if (this.forbiddenColumns.indexOf(column) == -1) {
            $trInsert.find('[name=' + column + ']').val($tr.find('[data-key=' + column + ']').text());
        }
    }, this));
};
snowgirlApp.prototype.showImage = function ($this, value) {
    value = value || $this.text();

    value = $.trim(value);

    if (!value) {
        return null;
    }

    $this.empty();

    $(value.split(' ')).each($.proxy(function (i, name) {
        $this.append(this.$image(name));
    }, this));
};
snowgirlApp.prototype.$textarea = function (column, value) {
    return $('<textarea/>', {
        name: column,
        class: 'form-control',
        placeholder: column,
        val: value || ''
    });
};
snowgirlApp.prototype.$image = function (name) {
    return $('<div/>', {class: 'img-wrapper'})
        .append($('<img/>', {src: this.core.getImageSource(name, 1, 100)}));
};
snowgirlApp.prototype.isImage = function (key) {
    return ['image', 'images'].indexOf(key) !== -1;
};

new snowgirlApp(snowgirlCore);