/*
This file extends existing API's with new methods, like Prototype, jQuery, etc.

@since 0.1.0
 */

/**
 * Move items around in an array by indices.
 *
 * @since 0.1.0
 *
 * @author Reid
 * @link http://stackoverflow.com/questions/5306680/move-an-array-element-from-one-array-position-to-another
 *
 * @param old_index The item to move.
 * @param new_index The place to move the new item.
 * @returns {Array}
 */
Array.prototype.move = function (old_index, new_index) {
    if (new_index >= this.length) {
        var k = new_index - this.length;
        while ((k--) + 1) {
            this.push(undefined);
        }
    }
    this.splice(new_index, 0, this.splice(old_index, 1)[0]);
    return this; // for testing purposes
};