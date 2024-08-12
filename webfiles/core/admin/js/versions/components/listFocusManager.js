/**
 * Handles keyboard-handling behavior of list panels. When the user
 * tabs to the list's container, the selected list item (or the first
 * list item, if no list items are selected) is focused. After that,
 * the up and down keys can navigate the list and Enter selects an
 * item. This behavior works similarly to YUI's Tabview functionality.
 *
 * Instantiate, passing in the node to handle the behavior for.
 * The `select` event notifies when the ENTER key is pressed while
 * a list item is focused.
 *
 *      new Y.ListFocusManager(node).on('select', function (e) {
 *          // e.target is the selected node.
 *      });
 *
 */
YUI.add('ListFocusManager', function(Y) {
    'use strict';

    function ListFocusManager (list) {
        this.list = list;
        this._init();

        Y.augment(this, Y.EventTarget);

        this.on('newSelection', Y.bind(function () {
            this._focusItem(this._chooseItemToFocusOn());
        }, this));

        return this;
    }

    ListFocusManager.keys = {
        'enter':    13,
        'up':       38,
        'down':     40
    };

    ListFocusManager.prototype._init = function () {
        this._chooseItemToFocusOn().set('tabIndex', 0);
        this.list.on('keydown', this._onKeyDown, this);
        this.list.delegate('click', function (e) {
            e.currentTarget.set('tabIndex', 0).focus();
            this.resetSelectedElement = true;
        }, '[role="option"]', this);

        if (Y.UA.ie) {
            this.list.on('scroll', this._setFlagForSelectedElementOnScroll, this);
        }
    };

    ListFocusManager.prototype._onKeyDown = function (e) {
        if (!Y.Object.hasValue(ListFocusManager.keys, e.keyCode)) return;

        var selected = (this.focused || this._getSelectedItem()).removeAttribute('tabIndex'),
            next;

        if (e.keyCode === ListFocusManager.keys.enter) {
            this.fire('select', { target: selected });

            next = selected;
        }
        else if (e.keyCode === ListFocusManager.keys.up) {
            next = selected.previous(':not(.hide)');
        }
        else if (e.keyCode === ListFocusManager.keys.down) {
            next = selected.next(':not(.hide)');
        }

        this._focusItem(next || selected);
    };

    ListFocusManager.prototype._focusItem = function (node) {
        if (!node) return;

        this.list.all('.listing-item[tabIndex]').removeAttribute('tabIndex');
        node.set('tabIndex', 0);
        node.focus();
        this.focused = node;
    };

    ListFocusManager.prototype._chooseItemToFocusOn = function () {
        var candidate = this.list.one('.selected') || this.list.one('*');

        return (candidate.hasClass('hide'))
            ? this.list.one(':not(.hide)') || this.list.one('*')
            : candidate;
    };

    ListFocusManager.prototype._getSelectedItem = function () {
        return this.list.one('.listing-item.selected') || this.list.one('.listing-item');
    };

    /**
     * IE has some bizarre behavior that the delegate listener on list options doesn't
     * fire in the correct order when the currently-focused item is scrolled out of view.
     * When another option is clicked, the blur event fires prior to the click event, causing
     * the currently-selected, scrolled-out-of-view item to become re-selected. This method
     * sets a flag that tells the focus listener to skip doing its thing, allowing the click
     * listener behavior to take over. Fun.
     */
    ListFocusManager.prototype._setFlagForSelectedElementOnScroll = function () {
        // detect if selected item is still visible.
        var selected = this.list.one('.selected'),
            scrollPosition = this.list.get('scrollTop');

        this.resetSelectedElement = (selected &&
            (selected.get('offsetTop') < scrollPosition + 100 ||
                selected.get('offsetTop') > scrollPosition + parseInt(this.list.getComputedStyle('height'), 10)));
    };

    Y.ListFocusManager = ListFocusManager;
}, null, {
    requires: ['node', 'event-custom', 'Helpers']
});
