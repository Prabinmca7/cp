RightNow.Widgets.DiscussionSubscriptionManager = RightNow.Widgets.Multiline.extend({
    overrides: {
        constructor: function() {
            this.parent();
            this.bottomDiv = this.Y.one(this.baseSelector + '_Bottom');
            this._errorDisplay = this.Y.one(this.baseSelector + "_ErrorMessage");
            this.Y.one(this.baseSelector).delegate('click', this._deleteSubscription, 'button[class="rn_Discussion_Delete"]', this);
            this.Y.one(this.baseSelector + '_UnsubscribeAll').delegate('click', this._confirmAndSubmitUnsubscribeAllRequest, 'a', this);
            var addNotifButton = this.Y.one(this.baseSelector + '_AddButton');
            if (addNotifButton) {
                addNotifButton.on('click', this._openDialog, this);
                RightNow.Event.subscribe('evt_productCategorySelected', this._getProdCatID, this);
            }

            RightNow.Widgets.formTokenRegistration(this);
        },

        /**
         * Event handler received when report data is changed.
         * @param {String} type Event name
         * @param {Array} args Arguments passed with event
         */
        _onReportChanged: function(type, args) {
            args[0].data.label_unsubscribe = this.data.attrs.label_unsubscribe;
            this.parent(type, args);

            if (args[0].data.total_num === 0) {
                this.Y.one(this.baseSelector + "_Content").set("innerHTML", this.data.attrs.label_no_notification);
                this.Y.one(this.baseSelector + '_UnsubscribeAll').addClass('rn_Hidden');
            }
            else {
                this.Y.one(this.baseSelector + '_UnsubscribeAll').removeClass('rn_Hidden');
            }
            RightNow.UI.displayBanner(this.successMessage, { focusElement: this.Y.one(this.baseSelector + '_AddButton'), baseClass: this.baseSelector + '_Content' });
            this.bottomDiv.show();
        },

        /**
         * Event handler received when search data is changing.
         * Shows progress icon during searches.
         * @param {String} evt Event name
         * @param {Array} args Arguments provided from event fire
         */
        _searchInProgress: function(evt, args) {
            this.bottomDiv.hide();
            this.parent(evt, args);
        }
    },

    /**
     * Creates and displays a warning dialog to the user asking whether they would like
     * to continue unsubscribe all action
     */
    _confirmAndSubmitUnsubscribeAllRequest: function() {
        if (!this._confirmDialog) {
            var dialogBody = this.Y.Node.create("<div>").set("innerHTML", this.data.attrs.label_unsubscribe_all_confirm);
            var buttons = [
                {text: RightNow.Interface.getMessage("YES_LBL"), handler: {fn: this._deleteAllSubscriptions, scope: this}},
                {text: RightNow.Interface.getMessage("NO_LBL"), handler: {fn: this._hideConfirmDialog, scope: this}, isDefault: true}
            ];
            this._confirmDialog = RightNow.UI.Dialog.actionDialog(RightNow.Interface.getMessage("WARNING_LBL"), dialogBody, {"buttons": buttons});
            this.Y.DOM.addClass(this._confirmDialog.id, 'rn_dialog');
            RightNow.UI.Dialog.addDialogEnterKeyListener(this._confirmDialog, this._deleteAllSubscriptions, this);
        }
        this._confirmDialog.show();
    },

    /**
     * Hide confirm box when user click on No / Confirmed to perform an action.
     */
    _hideConfirmDialog: function () {
        if (this._confirmDialog) {
            this._confirmDialog.hide();
        }
    },

    /**
     * Create event object and make AJAX request for unsubscribing to a question or product/category
     * @param {Object} e Current event object
     */
    _deleteSubscription: function(e) {
        var id = e.target.ancestor('.rn_Discussions').getData('id');
        this._makeAjaxRequest(this.data.attrs.delete_social_subscription_ajax, id, 'unsubscribe');
    },

    /**
     * Create event object and make AJAX request for unsubscribing to all questions or products/categories
     */
    _deleteAllSubscriptions: function() {
        this._hideConfirmDialog();
        this._makeAjaxRequest(this.data.attrs.delete_social_subscription_ajax, "-1", 'unsubscribe');
    },

    /**
     * Create event object and make AJAX request for subscribing to a question or product/category
     */
    _addSubscription: function() {
        if(this.prodCatID) {
            this._closeDialog();
            this._makeAjaxRequest(this.data.attrs.add_social_subscription_ajax, this.prodCatID, 'subscribe');
            return;
        }

        var mapping = {
                Product: RightNow.Interface.getMessage('PRODUCT_LC_LBL'),
                Category: RightNow.Interface.getMessage('CATEGORY_LWR_LBL'),
                Question: RightNow.Interface.getMessage('QUESTION_LC_LBL')
            },
            label = this.data.attrs.label_select_option,
            message = label.indexOf('%s') > -1 ? RightNow.Text.sprintf(label, mapping[this.data.attrs.subscription_type]) : label;

        var focusElement = this.Y.one(this.baseSelector + '_Dialog  button[id^="rn_ProductCategoryInput_"][id$="_Button"]')
            || this.Y.one(this.baseSelector + '_AddButton') 
            || this.Y.one(this.baseSelector + '_Content .rn_Discussions a');
        this._addErrorMessage(message, focusElement);
    },

    /**
     * Event handler executed when a product/category is selected from menu
     * @param {Object} evt Current event object
     * @param {Array} eventData Event data
     */
    _getProdCatID: function(evt, eventData) {
        this.prodCatID = eventData[0].data.hierChain[eventData[0].data.hierChain.length - 1];
    },

    /**
     * Creates and opens a dialog allowing the user to add product or category notifications
     */
    _openDialog: function() {
        this.prodCatID = 0;
        var dialogBody = this.Y.one(this.baseSelector + "_Dialog");
        if (dialogBody && !this._dialog)
        {
            dialogBody = this.Y.Node.getDOMNode(dialogBody.removeClass('rn_Hidden'));
            var buttons = [
                {text: this.data.attrs.label_prodcat_dialog_add_button, handler: {fn: this._addSubscription, scope: this}, isDefault: false},
                {text: this.data.attrs.label_prodcat_dialog_cancel_button, handler: {fn: this._closeDialog, scope: this}, isDefault: true}
            ];
            this._dialog = RightNow.UI.Dialog.actionDialog(this.data.attrs.label_add_prodcat_notification_dialog, dialogBody, {"buttons": buttons});
            RightNow.UI.show(dialogBody);
        }
        this.Y.one("#" + this._dialog.get('id')).addClass("rn_DiscussionSubscriptionManager_Dialog");
        this._dialog.show();
        this._dialog.bodyNode.one("a").focus();
        this._dialog.hideEvent.subscribe(this._closeDialog, null, this);
    },

    /**
     * Closes the product/category dialog
     * @param evt {String|Object} Event name or node that was clicked
     */
    _closeDialog: function(evt) {
        if (this._dialog) {
            RightNow.Event.fire("evt_resetProductCategoryMenu", new RightNow.Event.EventObject(this));
            this._clearErrorMessage();
            this._dialog.hide();
        }
    },

    /**
     * Makes an AJAX request to the enpoint
     * @param {String} requestEndpoint Ajax request url
     * @param {String} objectID ID of the social question or product/category
     */
    _makeAjaxRequest: function(requestEndpoint, objectID, action) {
        var eventObject = new RightNow.Event.EventObject(this, {data: {id: objectID, type: this.data.attrs.subscription_type, action:action, f_tok: this.data.js.f_tok}});
        RightNow.Ajax.makeRequest(requestEndpoint, eventObject.data, {
            successHandler: this._onResponse, scope: this, data: eventObject, json: true
        });
    },

    /**
     * On successful subscribing/unsubscribing clear the cache and fire search
     * else display an error message
     * @param {Object} response Object which has success or error message to display
     * @param {Object} eventObject Object used for AJAX Request
     */
    _onResponse: function(response, eventObject) {
        var textToDisplay = [],
            focusElement = this.Y.one(this.baseSelector + '_AddButton') || this.Y.one(this.baseSelector + '_Content .rn_Discussions a');
        if(response != null && response.newFormToken) {
            RightNow.Event.fire("evt_formTokenUpdate", new RightNow.Event.EventObject(this, {data: {newToken: response.newFormToken}}));
        }
        if (response.success) {
            this.successMessage = (eventObject.data.action === 'subscribe') ? this.data.attrs.label_on_subscription_success_banner : this.data.attrs.label_on_deleting_subscription_success_banner;
            this.searchSource().clearCache();
            this.searchSource().fire("search", eventObject);
        }
        else if(!RightNow.Ajax.indicatesSocialUserError(response)) {
            if (response.errors) {
                this.Y.Object.each(response.errors, function(message) {
                    if (message.hasOwnProperty('externalMessage'))
                        textToDisplay.push(message.externalMessage);
                });
            }
            RightNow.UI.displayBanner((textToDisplay.length !== 0) ? textToDisplay.join('<br>') :
                this.data.attrs.label_on_failure_banner, { type: 'ERROR', focusElement: focusElement, baseClass: this.baseSelector + '_Content' });
        }
    },

    /**
     * Clears out the error message divs and their classes.
     */
    _clearErrorMessage: function() {
        if(this._errorDisplay) {
            this._errorDisplay.removeClass('rn_MessageBox rn_ErrorMessage').set('innerHTML', "");
        }
    },

    /**
     * Adds an error message to the page and adds the correct CSS classes
     * @param {string} message The error message to display
     * @param {HTMLElement} focusElement The HTML element to focus on when the error message link is clicked
     */
    _addErrorMessage: function(message, focusElement)
    {
        if(this._errorDisplay) {
            var errorMessage = '<a href="javascript:void(0);" onclick="document.getElementById(\'' + focusElement.get('id') + '\').focus(); return false;">' + message + '</a>';
            this._errorDisplay.addClass('rn_MessageBox rn_ErrorMessage').set("innerHTML", errorMessage);
            this._errorDisplay.get('children').item(0).focus();
            this._errorDisplay.one("h2") ? this._errorDisplay.one("h2").setHTML(RightNow.Interface.getMessage("ERRORS_LBL")) : this._errorDisplay.prepend("<h2>" + RightNow.Interface.getMessage("ERROR_LBL") + "</h2>");
            this._errorDisplay.one("h2").setAttribute('role', 'alert');
        }
    }
});
