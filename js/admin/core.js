var snowgirlAdminCore = function (snowgirlCore) {
    this.core = snowgirlCore;

    return this.export();
};

snowgirlAdminCore.prototype.export = function () {
    return $.extend({}, this.core, {
        insertRow: $.proxy(this.insertRow, this),
        getRow: $.proxy(this.getRow, this),
        updateRow: $.proxy(this.updateRow, this),
        deleteRow: $.proxy(this.deleteRow, this),
        findTagWidget: $.proxy(this.findTagWidget, this),
        findImgWidget: $.proxy(this.findImgWidget, this)
    });
};

snowgirlAdminCore.prototype.insertRow = function (table, data) {
    return this.core.makeRequestByRoute('admin', {action: 'row', table: table}, 'post', data);
};
snowgirlAdminCore.prototype.getRow = function (table, id) {
    return this.core.makeRequestByRoute('admin', {action: 'row', table: table, id: id});
};
snowgirlAdminCore.prototype.updateRow = function (table, id, key, value) {
    var data = {id: id};
    data[key] = 'undefined' === typeof value ? null : value;
    return this.core.makeRequestByRoute('admin', {action: 'row', table: table}, 'patch', data);
};
snowgirlAdminCore.prototype.deleteRow = function (table, id) {
    return this.core.makeRequestByRoute('admin', {action: 'row', table: table, id: id}, 'delete');
};
/**
 * @param el
 * @param name
 * @returns {tag}
 */
snowgirlAdminCore.prototype.findTagWidget = function (el, name) {
    return $(el).closestDown('.widget-tag' + (name ? (':has([name=' + name + '])') : '')).data('snowgirl-tag');
};
/**
 * @param el
 * @param name
 * @returns {image}
 */
snowgirlAdminCore.prototype.findImgWidget = function (el, name) {
    return $(el).closestDown('.widget-image' + (name ? (':has([name=' + name + '])') : '')).data('snowgirl-image');
};

snowgirlCore = new snowgirlAdminCore(snowgirlCore);