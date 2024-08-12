YUI.add("RightNowTreeViewDropdown", function (Y) {
    /**
     * Creates a dropdown panel for displaying the RightNowTreeView component within.
     * Events emitted:
     *
     *      - show: Panel is shown
     *      - hide: Panel is hidden
     *      - confirm: Confirm button is clicked (if confirmButton is available)
     *
     */
    Y.RightNowTreeViewDropdown = Y.Base.create('RightNowTreeViewDropdown', Y.Widget, [], {
        /**
         * Shows the panel.
         * @return {Object} Chainable
         */
        show: function () {
            this._show();

            return this;
        },

        /**
         * Hides the panel.
         * @return {Object} Chainable
         */
        hide: function () {
            this._panel.hide();

            return this;
        },

        /**
         * Toggles the display of the panel.
         * @return {Object} Chainable
         */
        toggle: function () {
            this._show() || this.hide();

            return this;
        },

        /**
         * Whether the panel is showing.
         * @return {Boolean} Whether the panel is showing
         */
        isVisible: function () {
            return this._panel.get('visible');
        },

        /**
         * Renders the HTML contents in the content box.
         * Hook method that YUI.Widget calls.
         * @private
         */
        renderUI: function () {
            this._panel || (this._panel = new Y.Panel({
                width: this.get('width'),
                srcNode: this.get('srcNode'),
                headerContent: '',
                hideOn: [{eventName: 'clickoutside'}],
                align: {
                    node: this.get('trigger'),
                    points: [Y.WidgetPositionAlign.TL, Y.WidgetPositionAlign.BL]
                },
                visible: this.get('visible'),
                zIndex: 1000,
                render: this.get('render')
            }));
        },

        /**
         * Sets up event listeners.
         * Hook method that YUI.Widget calls.
         * @private
         */
        syncUI: function () {
            var trigger = this.get('trigger');

            if (!trigger) {
                throw new Error("trigger must be provided!");
            }

            // The panel's `clickoutside` event takes care of hiding the panel.
            trigger.on('click', this._show, this);

            this._panel.after('visibleChange', function (e) {
                this.fire(e.newVal ? 'show' : 'hide');
            }, this);

            this.get('srcNode').delegate('key', this._onTab, 'tab', 'a', this);
        },

        /**
         * Hook method that YUI.Base calls during
         * the destroy phase.
         * @private
         */
        destructor: function () {
            this._panel.destroy();
            Y.Event.purgeElement(this.get('srcNode'));
            Y.Event.purgeElement(this.get('trigger'));
            Y.Event.purgeElement(this.get('cancelButton'));
            Y.Event.purgeElement(this.get('confirmButton'));
        },

        /**
         * Shows the panel.
         * @return {boolean} Whether the panel was shown
         * @private
         */
        _show: function () {
            if (!this.isVisible()) {
                this._panel.align().show();
                return true;
            }

            return false;
        },

        /**
         * Tab keypress handler when the tab key is pressed
         * while on any <a> element.
         * Closes the panel if no confirm buttons are being used.
         * @param  {Object} e Key event
         * @private
         */
        _onTab: function (e) {
            if (!e.shiftKey && !this.get('cancelButton') && !this.get('confirmButton')) {
                this.hide();
            }
        },

        /**
         * Setter for the confirm button.
         * @param {Object} button Y.Node
         * @return {Object} Y.Node button
         * @private
         */
        _setConfirmButton: function (button) {
            button.detach('click');
            button.on('click', function () {
                this.fire('confirm');
            }, this);

            return button;
        },

        /**
         * Setter for the cancel button.
         * @param {Object} button Y.Node
         * @return {Object} Y.Node button
         * @private
         */
        _setCancelButton: function (button) {
            button.detach('click', this._panel.hide, this._panel);
            button.detach('key');
            button.on('click', this._panel.hide, this._panel);
            button.on('key', function (e) {
                if (!e.shiftKey) {
                    this.hide();
                }
            }, 'tab', this);

            return button;
        },

        /**
         * Setter for trigger text.
         * @param {string} text New text
         * @private
         */
        _setTriggerText: function (text) {
            var trigger = this.get('trigger');

            if (trigger.all('*').size()) {
                // Trigger element has children.
                // Select the first one that's not
                // screenreader-only.
                trigger = trigger.all('*:not(.rn_ScreenReaderOnly)').item(0);
            }

            if (trigger) {
                trigger.setAttribute('aria-busy', 'true')
                       .setHTML(text)
                       .setAttribute('aria-busy', 'false');
            }
        },

        /**
         * Setter for width.
         * @param {string|number} width Width
         * @private
         */
        _setWidth: function (width) {
            if (this._panel) {
                this._panel.set('width', width);
            }
        }
    }, {
        ATTRS: {
            /**
             * Y.Node trigger element that toggles the panel.
             */
            trigger: {
                value: null,
                validator: Y.Lang.isObject
            },

            /**
             * Y.Node confirm button for the panel (assumed to be inside `srcNode`)
             */
            confirmButton: {
                setter: '_setConfirmButton',
                validator: Y.Lang.isObject
            },

            /**
             * Y.Node cancel button for the panel (assumed to be inside `srcNode`)
             */
            cancelButton: {
                setter: '_setCancelButton',
                validator: Y.Lang.isObject
            },

            /**
             * String text to set in the trigger element.
             */
            triggerText: {
                setter: '_setTriggerText',
                validator: Y.Lang.isString
            }
        }
    });
}, "1.0.1", {
    requires: [
        "panel"
    ]
});
