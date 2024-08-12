RightNow.Widgets.AnswerNotificationManager = RightNow.Widgets.extend({
    constructor: function() {
        this.messageBox = this.Y.one(this.data.attrs.message_element ? this.Y.one('#' + this.data.attrs.message_element) : null);
        this.notificationList = this.Y.one(this.baseSelector + '_List');

        if(this.notificationList) {
            this.notificationList.delegate('click', this.onDeleteClick, '.rn_Notification .rn_Notification_Delete', this);
            this.notificationList.delegate('click', this.onRenewClick, '.rn_Notification .rn_Notification_Renew', this);
        }

        RightNow.Widgets.formTokenRegistration(this);
    },

    /**
     * Event handler for the delete button
     * @param {Object} e The event object
     */
    onDeleteClick: function(e) {
        this.handleClickEvent(e.target, 'evt_answerNotificationDeleteRequest', this.data.attrs.delete_notification_ajax, this.onDeleteResponse);
    },

    /**
     * Event handler for the delete button
     * @param {Object} e The event object
     */
    onRenewClick: function(e) {
        this.handleClickEvent(e.target, 'evt_answerNotificationUpdateRequest', this.data.attrs.renew_notification_ajax, this.onRenewResponse);
    },

    /**
     * Method for creating an event object and making a request to the server
     * @param {Object} button The button node that was clicked
     * @param {String} event The name of the event to fire before making the request
     * @param {String} endpoint The AJAX endpoint receiving the request
     * @param {function} handler The handler function called on success
     */
    handleClickEvent: function(button, event, endpoint, handler) {
        var id = button.ancestor('.rn_Notification').getData('id'),
            eo = new RightNow.Event.EventObject(this, {
                data: {
                    filter_type: 'answer',
                    id: parseInt(id, 10),
                    f_tok: this.data.js.f_tok
                }
            });

        if(this.lockedItem !== id && RightNow.Event.fire(event, eo)) {
            RightNow.Ajax.makeRequest(endpoint, eo.data, {
                successHandler: handler,
                scope: this,
                data: eo,
                json: true
            });
            this.lockedItem = id;
        }
    },

    /**
     * Event handler when a delete response returns from the server
     * @param {Object} response Response object
     * @param {Object} eo The original Event object
     */
    onDeleteResponse: function(response, eo) {
        this.lockedItem = null;
        if(response != null && response.newFormToken) {
            RightNow.Event.fire("evt_formTokenUpdate", new RightNow.Event.EventObject(this, {data: {newToken: response.newFormToken}}));
        }
        if(RightNow.Event.fire('evt_answerNotificationDeleteResponse', {data: eo, response: response})) {
            if(response.error) {
                RightNow.UI.Dialog.messageDialog(RightNow.Interface.getMessage("THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG"), {icon: "WARN"});
            }
            else {
                this.displayMessage(this.data.attrs.label_notif_deleted);

                var item = this.notificationList.one('.rn_Notification[data-id="' + eo.data.id + '"]'), scope = this;
                if(item) {
                    item.transition({
                        opacity: 0,
                        duration: 0.4
                    }, function() {
                        this.remove();
                        if(!scope.notificationList.all('.rn_Notification').size()) {
                            scope.notificationList.append(scope.data.attrs.label_no_notifs);
                        }
                    });
                }
            }
        }
    },

    /**
     * Event handler when renew response returns from server
     * @param {Object} response Response object
     * @param {Object} eo The original Event object
     */
    onRenewResponse: function(response, eo) {
        this.lockedItem = null;
        if(response != null && response.newFormToken) {
            RightNow.Event.fire("evt_formTokenUpdate", new RightNow.Event.EventObject(this, {data: {newToken: response.newFormToken}}));
        }
        if(RightNow.Event.fire('evt_answerNotificationRenewResponse', {data: eo, response: response})) {
            if(response.error) {
                RightNow.UI.Dialog.messageDialog(RightNow.Interface.getMessage("THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG"), {icon: "WARN"});
            }
            else {
                this.displayMessage(this.data.attrs.label_notif_renewed);

                this.notificationList.get('childNodes').remove();
                this.Y.Array.each(response.notifications, function(notification) {
                    this.notificationList.append(new EJS({text: this.getStatic().templates.view}).render({
                        id: notification.id,
                        url: RightNow.Url.addParameter(RightNow.Url.addParameter(this.data.attrs.url, 'a_id', notification.id), 'session', RightNow.Url.getSession()),
                        summary: notification.summary,
                        subscribedLabel: RightNow.Interface.getMessage('SUBSCRIBED_ON_PCT_S_LBL').replace("%s", notification.startDate),
                        expiresLabel: notification.expiration || RightNow.Interface.getMessage('NO_EXPIRATION_DATE_LBL'),
                        includeRenew: !!this.data.js.duration,
                        renewLabel: this.data.attrs.label_renew_button,
                        deleteLabel: this.data.attrs.label_delete_button
                    }));
                }, this);
            }
        }
    },

    /**
    * Displays success message in message box above widget or as user specified div.
    * @param message String Message to display.
    */
    displayMessage: function(message) {
        if(this.messageBox) {
            this.messageBox.setStyle("opacity", 0).addClass("rn_MessageBox");
            this.messageBox.transition({
                opacity: 1,
                duration: 0.4
            });
            this.messageBox.set('innerHTML', message);
            RightNow.UI.updateVirtualBuffer();
            this.messageBox.set('tabIndex', 0).focus();
        }
        else {
            RightNow.UI.displayBanner(message, {baseClass: this.baseSelector});
        }
    }
});
