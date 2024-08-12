RightNow.Widgets.ProdCatNotificationManager = RightNow.Widgets.extend({
    constructor: function()
    {
        this._widgetContainer = this.Y.one(this.baseSelector + '_List');
        this._numberOfNotifs = this.data.js.notifications.length;
        this.displayRenewButton = this.data.js.duration;
        this._messageDiv = (this.data.attrs.message_element) ? this.Y.one('#' + this.data.attrs.message_element) : null;

        if(!this._widgetContainer) return;

        if(this._numberOfNotifs)
        {
            for(var i = 0; i < this._numberOfNotifs; i++)
            {
                this.Y.one(this.baseSelector + '_Delete_' + i).on('click', this._onButtonClick, this, {"index" : i, "delete" : true});
                if (this.displayRenewButton) {
                    this.Y.one(this.baseSelector + '_Renew_' + i).on('click', this._onButtonClick, this, {"index" : i, "renew" : true});
                }
            }
        }

        this.Y.one(this.baseSelector + '_AddButton').on('click', this._openDialog, this);
        RightNow.Event.subscribe("evt_menuFilterSelectRequest", this._closeDialog, this);
        RightNow.Event.subscribe("evt_menuFilterSelectResponse", this._onNewProdCatAdded, this);

        RightNow.Widgets.formTokenRegistration(this);
    },

    /**
    * Creates and opens a dialog allowing the user to add product or category notifications
    */
    _openDialog: function()
    {
        var dialogBody = this.Y.one(this.baseSelector + "_Dialog");
        if(dialogBody && !this._dialog)
        {
            dialogBody = this.Y.Node.getDOMNode(dialogBody.removeClass('rn_Hidden'));
            var buttons = [ {text: this.data.attrs.label_dialog_cancel, handler: {fn: this._closeDialog, scope: this}, isDefault: true} ];
            this._dialog = RightNow.UI.Dialog.actionDialog(this.data.attrs.label_add_notif_dialog, dialogBody, {"buttons": buttons});
            RightNow.UI.show(dialogBody);
        }
        this._dialog.show();
        this._dialog.bodyNode.one("a").focus();
    },

    /**
    * Closes the dialog and resets the widget to display a loading icon if data was requested from the server
    * @param evt {String|Object} Event name or node that was clicked
    */
    _closeDialog: function(evt)
    {
        //only display loading icon when coming from evt_menuFilterSelectRequest (not cancelling)
        if(typeof evt === 'string'){
            this._widgetContainer.set('innerHTML', "").addClass('rn_Loading');
            if(this._messageDiv) {
                this._messageDiv.removeClass('rn_MessageBox').set('innerHTML', "");
            }
        }
        if(this._dialog) {
            this._dialog.hide();
            this._clearErrorMessage();
        }
    },

    /**
     * Event handler for when renew button is clicked
     * @param evt String Event name
     * @param notifElementIndex Object Contains notification index
     *      and type of operation to perform (delete, renew)
     */
    _onButtonClick: function(evt, notifElement)
    {
        this._buttonClicked = evt.target;
        this._buttonClicked.disabled = true;
        this._currentNotif = this.Y.one(this.baseSelector + "_Notification_" + notifElement.index);

        var notifValue = this.data.js.notifications[notifElement.index],
            eventObject = new RightNow.Event.EventObject(this, {data: {
                filter_type: notifValue.filter_type || (notifValue.type === 'product' ? this.data.js.productsTable : this.data.js.categoriesTable),
                id: notifValue.id,
                f_tok: this.data.js.f_tok
            }}), event, successHandler, endpoint;

        if(notifElement.renew) {
            event = 'evt_prodCatRenewRequest';
            successHandler = this._onRenewResponse;
            endpoint = this.data.attrs.renew_notification_ajax;
        }
        else {
            event = 'evt_prodCatDeleteRequest';
            successHandler = this._onDeleteResponse;
            endpoint = this.data.attrs.delete_notification_ajax;
        }
        if(RightNow.Event.fire(event, eventObject)) {
            RightNow.Ajax.makeRequest(endpoint, eventObject.data, {
                successHandler: successHandler, scope: this, data: eventObject, json: true
            });
        }
    },

    /**
     * Event handler for when product or category notification was renewed
     * @param type String Event name
     * @param args Object Event arguments
     */
    _onRenewResponse: function(response, originalEventObj)
    {
        if (RightNow.Event.fire('evt_prodCatRenewResponse', {data: originalEventObj, response: response})) {
            if(response != null && response.newFormToken) {
                RightNow.Event.fire("evt_formTokenUpdate", new RightNow.Event.EventObject(this, {data: {newToken: response.newFormToken}}));
            }
            if (response.error) {
                this._displayMessage(response.error, 'ERROR');
            }
            else {
                this._displayMessage(this.data.attrs.label_notif_renewed, 'SUCCESS');
                this._buttonClicked.set('disabled', false);
                this._widgetContainer.set('innerHTML', "");

                //refresh entire list
                this._onProdCatAdded(null, [response]);
            }
        }
    },

    /**
     * Event handler for when product category notification was deleted
     * @param type String Event name
     * @param args Object Event arguments
     */
    _onDeleteResponse: function(response, originalEventObj)
    {
        if (RightNow.Event.fire("evt_prodCatDeleteResponse", {data: originalEventObj, response: response})) {
            if(response != null && response.newFormToken) {
                RightNow.Event.fire("evt_formTokenUpdate", new RightNow.Event.EventObject(this, {data: {newToken: response.newFormToken}}));
            }
            if(response.error) {
                this._displayMessage(response.error, 'ERROR');
            }
            else {
                this._displayMessage(this.data.attrs.label_notif_deleted, 'SUCCESS');
                //remove notification DOM element
                if(this._currentNotif) {
                    this._currentNotif.transition({
                        opacity: 0,
                        duration: 0.4
                    }, function(){
                        this.remove();
                    });
                }
                //display empty message if all notifs have been removed
                if(--this._numberOfNotifs === 0) {
                    this._widgetContainer.set('innerHTML', this.data.attrs.label_no_notifs);
                }
                this._buttonClicked.set('disabled', false);
            }
        }
    },

    /**
     * Event handler for when a new prod/cat notification was added
     * @param {String} evt Event name
     * @param {Array} response Event arguments
     */
    _onNewProdCatAdded: function(evt, response)
    {
        if(response != null && response.newFormToken) {
            RightNow.Event.fire("evt_formTokenUpdate", new RightNow.Event.EventObject(this, {data: {newToken: response.newFormToken}}));
        }
        if(response[0].error) {
            this._displayMessage(response[0].error, 'ERROR');
        }
        else {
            this._displayMessage(this.data.attrs.label_notif_added, 'SUCCESS');
        }
        this._onProdCatAdded(evt, response);
    },

    /**
     * Event handler for when prod/cat notification was added
     * @param {String} evt Event name
     * @param {Array} response Event arguments
     */
    _onProdCatAdded: function(evt, response)
    {
        response = response[0];
        if(response != null && response.newFormToken) {
            RightNow.Event.fire("evt_formTokenUpdate", new RightNow.Event.EventObject(this, {data: {newToken: response.newFormToken}}));
        }
        var notifications = response.notifications,
            notification, hierValue, deleteButtonID, renewButtonID;

        this._widgetContainer.removeClass('rn_Loading');
        //remove listeners from any pre-existing notifications : we just removed those elements
        //and we'll re-attach listeners to new elements
        //Refresh the prod/cat list
        this._numberOfNotifs = notifications.length;

        for(var i = 0; i < this._numberOfNotifs; i++) {
            renewButtonID = (this.displayRenewButton) ? this.baseDomID + '_Renew_' + i : null;
            deleteButtonID = this.baseDomID + '_Delete_' + i;
            notification = notifications[i];
            this._widgetContainer.append(this.Y.Node.create(new EJS({text: this.getStatic().templates.view}).render({
                divID: this.baseDomID + '_Notification_' + i,
                href: RightNow.Url.addParameter(((notification.type === 'product') ? this.data.attrs.prod_page_url + "/p/" : this.data.attrs.cat_page_url + "/c/") + notification.chain, 'session', RightNow.Url.getSession()),
                label: notification.label + ' - ' + this.Y.Escape.html(notification.summary),
                startDate: RightNow.Interface.getMessage('SUBSCRIBED_ON_PCT_S_LBL').replace("%s", notification.startDate),
                expirationDate: notification.expiration,
                renewButtonID: renewButtonID,
                labelRenewButton: this.data.attrs.label_renew_button,
                deleteButtonID: deleteButtonID,
                labelDeleteButton: this.data.attrs.label_delete_button
            })));

            this.Y.one('#' + deleteButtonID).on('click', this._onButtonClick, this, {"index" : i, "delete" : true});
            if (this.displayRenewButton) {
                this.Y.one('#' + renewButtonID).on('click', this._onButtonClick, this, {"index" : i, "renew" : true});
            }
        }
        this.data.js.notifications = notifications;
    },

    /**
     * Clears out the error message divs and their classes.
     */
    _clearErrorMessage: function() {
        this.Y.all('.rn_ErrorMessage').each(RightNow.UI.hide);
    },

    /**
    * Displays success message in message box above widget or in user-specified div.
    * @param message String Message to display.
    * @param type String Type of Message to display
    */
    _displayMessage: function(message, type)
    {
        if(this._messageDiv) {
            this._messageDiv.setStyle('opacity', 0)
                            .addClass('rn_MessageBox');
            this._messageDiv.transition({
                opacity: 1,
                duration: 0.4
            });
            this._messageDiv.set('innerHTML', message);
            RightNow.UI.updateVirtualBuffer();
            this._messageDiv.set('tabIndex', 0).focus();
        }
        else {
            RightNow.UI.displayBanner(message, { type: type, focusElement: this.Y.one(this.baseSelector + '_AddButton') });
        }
    }
});
