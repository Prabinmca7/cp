RightNow.Widgets.AccountDropdown = RightNow.Widgets.extend({
    /**
     * Widget constructor.
     */
    constructor: function() {
        this._dropdownElement = this.Y.one(this.baseSelector + "_SubNavigation");
        this._toggleElement = this.Y.one(this.baseSelector + "_DropdownButton");
        this._dropdownOpen = false;

        if(!this._toggleElement || !this._dropdownElement) return;

        this._toggleElement.on('click', this._toggleDropdown, this);
        this._keyEventTriggers = new this.Y.NodeList(this._toggleElement).concat(this._dropdownElement.all('a').setAttribute('tabindex', -1));
        this._keyEventTriggers.on('keydown', this._onKeydown, this);
    },

    /**
     * Handles keydown events for menuitems.
     * Tab: closes the menu
     * Esc: closes the menu and focuses on the trigger
     * ↑:   focuses on the previous element in the menu
     * ↓:   focuses on the next element in the menu
     * @param {object} e Keydown event
     */
    _onKeydown: function(e) {
        if (!this._closeDropdownKeypress(e)) {
            this._dropdownNavKeypress(e);
        }
    },

    /**
     * Closes the dropdown on TAB or ESC.
     * @param {object} e Keydown event
     * @return {boolean} True if the dropdown was closed; False if not
     */
    _closeDropdownKeypress: function (e) {
        if (this.Y.Array.indexOf([RightNow.UI.KeyMap.ESCAPE, RightNow.UI.KeyMap.TAB], e.keyCode) === -1) return false;

        // Close the dropdown on TAB or ESC.
        this._closeDropdown();

        if (e.keyCode === RightNow.UI.KeyMap.ESCAPE) {
            this._toggleElement.focus();
        }

        return true;
    },

    /**
     * Handles ↑ and ↓ key events.
     * @param {object} e Keydown event
     */
    _dropdownNavKeypress: function (e) {
        if (this.Y.Array.indexOf([RightNow.UI.KeyMap.UP, RightNow.UI.KeyMap.DOWN], e.keyCode) > -1) {
            e.halt();
            this._focusAdjacentElement(this._keyEventTriggers, e.keyCode === RightNow.UI.KeyMap.UP ? -1 : 1);
        }
    },

    /**
     * Focuses on the sibling of the currently-focused node
     * in the given nodelist specified by the given index.
     * @param {object} nodeList Y.NodeList
     * @param {number} adjacentIndex Either 1 (next sibling) or -1 (previous sibling)
     */
    _focusAdjacentElement: function (nodeList, adjacentIndex) {
        var index = this._indexOfActiveNode(nodeList) + adjacentIndex,
            adjacentEl = nodeList.item(Math.max(0, index));

        if (adjacentEl) {
            adjacentEl.focus();
        }
    },

    /**
     * Returns the index in nodeList of the actively-focused node.
     * @param {object} nodeList Y.NodeList
     * @return {number} Index in nodeList of the active element; -1 if not found
     */
    _indexOfActiveNode: function(nodeList) {
        var activeElement = document.activeElement,
            index = -1;

        nodeList.some(function (el, i) {
            if (el.compareTo(activeElement)) {
                index = i;
                return true;
            }
        });

        return index;
    },

    /**
     * Stops the default event and toggles between
     * displaying and hiding the dropdown list of links.
     * @param {Object} evt Click or Focus event.
     */
    _toggleDropdown: function(evt) {
        evt.halt();

        (this._closeDropdown() || this._openDropdown());
    },

    /**
     * Displays the dropdown list of sublinks and subscribes
     * to appropriate events that dictate the proper closing of
     * the dropdown.
     */
    _openDropdown: function() {
        if(!this._dropdownOpen) {
            RightNow.UI.show(this._dropdownElement);
            this._toggleElement.setAttribute('aria-expanded', 'true');

            // To fix JAWS menu reading issue, focus on parent tag of menu list first and then focus back on dropdown button.
            this.Y.one(this.baseSelector + "_SubNavigationParent").focus();
            this._toggleElement.focus();

            this.Y.one(document.body).on('click', this._closeDropdown, this);
            this._dropdownOpen = true;
            return true;
        }

        return false;
    },

    /**
     * Hides the dropdown list of sublinks and optionally
     * purges the element that triggered the event.
     */
    _closeDropdown: function() {
        if(this._dropdownOpen) {
            this.Y.one(document.body).detach("click", this._closeDropdown, this);
            RightNow.UI.hide(this._dropdownElement);
            this._toggleElement.setAttribute('aria-expanded', 'false');
            this._dropdownOpen = false;
            return true;
        }

        return false;
    }
});
