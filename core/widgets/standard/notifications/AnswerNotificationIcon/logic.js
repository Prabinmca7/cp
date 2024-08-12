RightNow.Widgets.AnswerNotificationIcon = RightNow.Widgets.extend({
    constructor: function()
    {
        if(this.data.js.pta)
            return false;
        if(!RightNow.Interface.getConfig("ANS_NOTIF_ENABLED"))
            return false;
        this._submitting = false;
        if(RightNow.Profile.isLoggedIn())
            this.Y.one(this.baseSelector + '_Trigger').on('click', this._onTriggerClick, this, null);

        //If logged in by previous invocation, go ahead and do the notification.
        if(this.data.js.autoOpen == 1)
            this._onTriggerClick();

        RightNow.Widgets.formTokenRegistration(this);
    },

    /**
     * Event handler when user clicks our trigger element.
     * @param type {String} Event name
     * @param args {Object} Event arguments
     */
    _onTriggerClick: function(type, args)
    {
        if(!this._submitting)
        {
            this._submitting = true;
            this._createNotification();
        }
        return false;
    },

    /**
     * Packages and fires event to create notification
     */
    _createNotification: function()
    {
        var eventObject = new RightNow.Event.EventObject(this, {data: {filter_type: 'answer', id: this.data.js.answerID, cid: this.data.js.contactID, f_tok: this.data.js.f_tok}});
        if (RightNow.Event.fire('evt_answerNotificationUpdateRequest', eventObject)) {
            RightNow.Ajax.makeRequest(this.data.attrs.add_or_renew_notification_ajax, eventObject.data, {
                successHandler: this._onNotificationResponse, scope: this, data: eventObject, json: true
            });
        }
    },

    /**
     * Event handler for when notification update event is received from server.
     * @param response {mixed}
     * @param originalEventObj {Object}
     */
    _onNotificationResponse: function(response, originalEventObj)
    {
        var triggerLink = this.Y.one(this.baseSelector + '_Trigger');
        if (RightNow.Event.fire('evt_answerNotificationResponse', {data: originalEventObj, response: response})) {
            if(response != null && response.newFormToken) {
                RightNow.Event.fire("evt_formTokenUpdate", new RightNow.Event.EventObject(this, {data: {newToken: response.newFormToken}}));
            }
            if(response.error) {
                RightNow.UI.displayBanner(this.data.attrs.label_error, { type: 'ERROR', focusElement: triggerLink });
            }
            else {
                RightNow.UI.displayBanner(response.action === 'renew' ? this.data.attrs.label_renewed : this.data.attrs.label_subscribed, {focusElement: triggerLink});
            }
            this._submitting = false;
            return false;
        }
    }
});
