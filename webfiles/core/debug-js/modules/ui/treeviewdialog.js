YUI.add("RightNowTreeViewDialog", function (Y) {
    /**
    * Creates and displays a dialog consisting of an accessible list of hierarchy items.
    * Events emitted:
    *
    *   - selectionMade: Supplied with an array of values for the selection chain
    *   - close: Dialog closed without making a selection
    *
    */
    Y.RightNowTreeViewDialog = Y.Base.create('RightNowTreeViewDialog', Y.Widget, [], {
        /**
         * Shows the dialog.
         * @return {Object} Chainable
         */
        show: function() {
            if (!this._dialog) {
                this._createDialog();
            }
            if (this._hierarchyDataReset) {
                this._renderContent();
                this._hierarchyDataReset = false;
            }
            this._updateDialogForSelection();
            this._dialog.show();

            return this;
        },

        /**
         * Hides the dialog.
         * @return {Object} Chainable
         */
        hide: function () {
            if (this._dialog) {
                this._dialog.hide();
            }

            return this;
        },

        /**
         * Whether the dialog is showing.
         * @return {Boolean} Whether the dialog is showing
         */
        isVisible: function () {
            return this._dialog && this._dialog.get('visible');
        },

        /**
         * Adds a validation error message to the dialog.
         * @param {string} message Error message
         * @param {string|number} value   ID / value of the element to
         *                                focus on when the error message is clicked
         */
        addErrorMessageForValue: function (message, value) {
            var errorLocation = this.get('contentBox').one('#' + this.get('id') + '_AccessibleErrorLocation');
            if (errorLocation) {
                errorLocation.setHTML("<div><b><a data-value='" + value + "' href='javascript:void(0);'>" + message + "</a></b></div>")
                             .addClass('rn_MessageBox')
                             .addClass('.rn_ErrorMessage')
                             .removeClass('rn_Hidden');

                RightNow.UI.updateVirtualBuffer();
                errorLocation.one('a').focus();
            }
        },

        /**
         * Renders the HTML contents in the content box.
         * Hook method that YUI.Widget calls.
         * @private
         */
        renderUI: function () {
            this._renderContent();
        },

        /**
         * Sets up event listeners.
         * Hook method that YUI.Widget calls.
         * @private
         */
        syncUI: function () {
            var contentBox = this.get('contentBox');
            contentBox.delegate('click', this._onLinkClick, 'a.rn_AccessibleHierLink', this);
            contentBox.delegate('click', this._focusOnSelectedItem, '.rn_Intro > a, .rn_ValidationErrors a', this);
        },

        /**
         * Hook method that YUI.Base calls during
         * the destroy phase.
         * @private
         */
        destructor: function () {
            this._dialog.destroy();
            Y.Event.purgeElement(this.get('contentBox'));
        },

        /**
         * Sets the constructed hierarchy list
         * as the content of the contentBox.
         * @return {Object} The contentBox node
         * @private
         */
        _renderContent: function () {
            return this.get('contentBox').setHTML(this._buildHTML(this.get('hierarchyData')));
        },

        /**
         * Focuses on the selected item's link.
         * @param {Object} e Click event
         * @private
         */
        _focusOnSelectedItem: function (e) {
            document.getElementById(this.get('id') + "_AccessibleLink_" + (e.currentTarget.getData('value') || this.get('selectedValue'))).focus();
        },

        /**
         * Updates the screen reader affordances
         * that indicate the current selection.
         * @private
         */
        _updateDialogForSelection: function() {
            var id = this.get('id'),
                currentlySelectedSpan = Y.one('#' + id + "_IntroCurrentSelection");

            if (currentlySelectedSpan) {
                var selectedNodes = this.get('selectedLabels');
                selectedNodes = selectedNodes[0] ? selectedNodes.join(", ") : this.get('noItemSelectedLabel');
                currentlySelectedSpan.setHTML(RightNow.Text.sprintf(this.get('selectionPlaceholderLabel'), selectedNodes));
            }
        },

        /**
         * Creates an actionDialog.
         * @private
         */
        _createDialog: function () {
            var buttons = [ {text: this.get('dismissLabel'), handler: function () { this.hide(); }, isDefault: false} ];
            RightNow.UI.show(this.get('contentBox'));

            this._dialog = RightNow.UI.Dialog.actionDialog(this.get('titleLabel'), this.get('contentBox'), {buttons: buttons});
            this._dialog.after('visibleChange', function(e) {
                if (!e.newVal) {
                    this.fire('close');
                }
            }, this);
        },

        /**
         * Builds the nested list HTML for the hierarchy data.
         * @param  {Array} data Hierarchy data
         * @return {string}      List
         * @private
         */
        _buildHTML: function (data) {
            var html = '',
                previousLevel = -1;

            Y.Array.each(data, function (item, i) {
                item = {
                    value: item[1],
                    level: item.level + 1,
                    label: item[0],
                    hierList: item.hier_list
                };

                if (i === 0) {
                    html = this._headerHTML(item);
                }

                //print down html
                if(item.level > previousLevel)
                    html += "<ol>";

                //print up html
                while(item.level < previousLevel) {
                    html += "</li></ol>";
                    previousLevel--;
                }
                //print across html
                if(item.level === previousLevel)
                    html += "</li>";

                html += this._itemHTML(item);
                previousLevel = item.level;
            }, this);

            //close list
            for(var i = previousLevel; i > 0; --i)
                html += "</li></ol>";

            html += "<div id='" + this.get('id') + "_AccessibleErrorLocation' class='rn_ValidationErrors'></div>";

            return html;
        },

        /**
         * Returns the list item HTML for each
         * hierarchy item.
         * @param  {Object} item Hierarchy item
         * @return {string}      HTML
         * @private
         */
        _itemHTML: function (item) {
            item.id = this.get('id');
            item.levelLabel = this.get('levelLabel');
            return Y.Lang.sub('<li><a href="javascript:void(0)" id="{id}_AccessibleLink_{value}" class="rn_AccessibleHierLink" data-hierList="{hierList}">' +
                '{label}<span class="rn_ScreenReaderOnly">{levelLabel} {level}</span></a>', item);
        },

        /**
         * Returns the heading HTML for the dialog.
         * @param  {Object} firstItem First hierarchy item
         * @return {string}           HTML
         * @private
         */
        _headerHTML: function (firstItem) {
            firstItem.id = this.get('id');
            firstItem.introLabel = this.get('introLabel');
            firstItem.selection = RightNow.Text.sprintf(this.get('selectionPlaceholderLabel'), firstItem.label);
            return Y.Lang.sub("<p class='rn_Intro'><a href='javascript:void(0)'>{introLabel}" +
                " <span id='{id}_IntroCurrentSelection'>{selection}</span></a></p>", firstItem);
        },

        /**
         * Event handler for click event.
         * @param  {Object} e Click event
         * @private
         */
        _onLinkClick: function (e) {
            e.halt();

            this.fire('selectionMade', {
                valueChain: e.currentTarget.getAttribute('data-hierList').split(',')
            });
        },

        /**
         * Setter for hierarchy data. Sets a flag
         * so that #show knows to re-render the content.
         * @param {Array} newData Hierarchy data
         * @private
         */
        _setHierarchyData: function (newData) {
            this._hierarchyDataReset = true;
            return newData;
        },

        /**
         * Setter for dismiss label.
         * @param {string} label Dismiss label
         * @private
         */
        _setDismissLabel: function (label) {
            if (this._dialog) {
                this._dialog.getButton(0).setHTML(label);
            }

            return label;
        },

        /**
         * Setter for title label.
         * @param {string} label Title label
         * @private
         */
        _setTitleLabel: function (label) {
            if (this._dialog) {
                this._dialog.getStdModNode(Y.WidgetStdMod.HEADER).one('.rn_DialogTitle').setHTML(label);
            }

            return label;
        }
    }, {
        ATTRS: {
            /**
             * ID prefix to use for elements that need to be referenced in JS
             */
            id: {
                value: 'rn_RightNowTreeViewDialog',
                validator: Y.Lang.isString
            },

            /**
             * Selected hierarchy item's id value
             */
            selectedValue: {
                value: 0,
                validator: Y.Lang.isInt
            },

            /**
             * Array of labels for the selected item's hierarchy
             */
            selectedLabels: {
                value: [],
                validator: Y.Lang.isArray
            },

            /**
             * Array of data to use for the hierarchy. Each item:
             *
             *      0: string label
             *      1: int value
             *      level: int (zero-based) level
             *      hier_list: Array hierarchy chain
             *
             */
            hierarchyData: {
                value: [],
                validator: Y.Lang.isArray,
                setter: '_setHierarchyData'
            },

            /**
             * String label indicating the current selection.
             * Should contain a %s for substitution.
             */
            selectionPlaceholderLabel: {
                value: '%s',
                validator: Y.Lang.isString
            },

            /**
             * Dialog's intro label.
             */
            introLabel: {
                value: '',
                validator: Y.Lang.isString
            },

            /**
             * Cancel button label.
             */
            dismissLabel: {
                value: '',
                validator: Y.Lang.isString,
                setter: '_setDismissLabel'
            },

            /**
             * Label to use to indicate levels.
             */
            levelLabel: {
                value: '',
                validator: Y.Lang.isString
            },

            /**
             * Label to use to indicate that nothing is selected.
             */
            noItemSelectedLabel: {
                value: '',
                validator: Y.Lang.isString
            },

            /**
             * Dialog's title.
             * @type {Object}
             */
            titleLabel: {
                value: '',
                validator: Y.Lang.isString,
                setter: '_setTitleLabel'
            }
        }
    });
}, "1.0.1", {
    requires: [
        "panel"
    ]
});
