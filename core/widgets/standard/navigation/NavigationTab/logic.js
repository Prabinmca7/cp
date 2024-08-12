RightNow.Widgets.NavigationTab = RightNow.Widgets.extend({
    constructor: function() {
        this._tabElement = this.Y.one(this.baseSelector);
        if(this.data.attrs.subpages) {
            this._toggleElement = this.Y.one(this.baseSelector + "_DropdownButton");
            if(!this._toggleElement) return;

            this._dropdownElement = this.Y.one(this.baseSelector + "_SubNavigation");
            this._linkElements = this._dropdownElement.get('children');

            this._toggleElement.on('click', this._toggleDropdown, this);
            this._linkElements.item(this._linkElements.size() - 1).on('focus', this._toggleDropdown, this);
            this._linkElements.item(0).on('focus', this._toggleDropdown, this);
            // Close the dropdown when shift-tabbing off the tab
            this._listenToTab(this.baseSelector + "_Link", true);
        }
        if(this.data.attrs.searches_done > 0 && this.data.js.searches < this.data.attrs.searches_done)
            RightNow.Event.on("evt_searchRequest", this._onSearchCountChanged, this);
    },

    /**
    * Stops the default event and toggles between
    * displaying and hiding the dropdown list of links.
    * @param {Object} evt Click or Focus event.
    */
    _toggleDropdown: function(evt, args) {
        evt.halt();
        if(this._dropdownOpen && evt.target === this._toggleElement){
            this._closeDropdown({type:"click"});
        }
        else{
            this._openDropdown();
        }
    },

    /**
    * Displays the dropdown list of sublinks and subscribes
    * to appropriate events that dictate the proper closing of
    * the dropdown.
    */
    _openDropdown: function() {
        if(!this._dropdownOpen) {
            var tabRegion = this._tabElement.get('region');
            this._dropdownElement
                .setStyles({
                    top: tabRegion.bottom + "px",
                    left: tabRegion.left + "px"
                })
                .removeClass("rn_ScreenReaderOnly");
            this._dropdownOpen = true;

            if (!this._initializedEvents) {
                this.Y.all([this._tabElement, this._dropdownElement]).on('mouseout', this._closeDropdown, this);
                this.Y.all([this._dropdownElement, document.body]).on('click', this._closeDropdown, this);
                this.Y.one(document.body).on('click', this._closeDropdown, this);
                // Close the dropdown when tabbing off of its last link
                this._listenToTab(this._linkElements.item(this._linkElements.size() - 1), false);
                this._initializedEvents = true;
            }
        }
    },

    /**
    * Hides the dropdown list of sublinks and optionally
    * purges the element that triggered the event.
    * @param {Object} evt Click, Blur, or Mouseout event
    */
    _closeDropdown: function(evt) {
        if (this._dropdownOpen) {
            if (evt.type !== "keydown" && evt.type !== "click" &&
                (this._tabElement.contains(evt.relatedTarget) || this._dropdownElement.contains(evt.relatedTarget))) {
                    return;
            }
            this.Y.Event.purgeElement(document, false, "click");
            this._dropdownElement
                .setStyles({
                    top: "auto",
                    left: "-10000px"
                })
                .addClass("rn_ScreenReaderOnly");
            this._dropdownOpen = false;
        }
    },

    /**
     * Updates the number of searches performed to determine if we need to show the tab
     */
    _onSearchCountChanged: function() {
        this.data.js.searches++;
        if(this.data.js.searches >= this.data.attrs.searches_done) {
            RightNow.Event.unsubscribe("evt_searchRequest", this._onSearchCountChanged);
            RightNow.UI.show(this._tabElement);
        }
    },

    /**
     * Subscribes to the tab keydown event on the given element.
     * @param {Object|String} target The element to subscribe to the event for
     * @param {Boolean} shiftKey Whether the shift key should be pressed as well
     */
    _listenToTab: function(target, shiftKey) {
        if (typeof target === "string") {
            target = this.Y.one(target);
        }
        if (target) {
            target.on("keydown", function(evt) {
                if(evt.keyCode === RightNow.UI.KeyMap.TAB && shiftKey === evt.shiftKey) {
                    this._closeDropdown(evt);
                }
            }, this);
        }
    }
});
