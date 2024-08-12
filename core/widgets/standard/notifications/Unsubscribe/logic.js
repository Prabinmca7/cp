RightNow.Widgets.Unsubscribe = RightNow.Widgets.extend({
    constructor: function() {
        if (!this.data.js.notifications) return;
        var action = (this.data.js.notifications.length === 1) ? 'subscribe' : 'unsubscribe';
        this.Y.all(this.baseSelector + "_NotificationList button").on('click', this._onClick, this, action);

        RightNow.Widgets.formTokenRegistration(this);
    },

    /**
     * Event handler for the response from a subscribe or unsubscribe event.
     * @param Object response Response data from the server
     * @param Object originalEventObject Event data send with the original request
     */
    _onResponse: function(response, originalEventObject) {
        if(response != null && response.newFormToken) {
            RightNow.Event.fire("evt_formTokenUpdate", new RightNow.Event.EventObject(this, {data: {newToken: response.newFormToken}}));
        }
        if (originalEventObject.data.responseEvent) {
            if (!RightNow.Event.fire(originalEventObject.data.responseEvent, {data: originalEventObject, response: response})){
                return;
            }
        }
        var fieldset = this.Y.one(this.baseSelector + '_' + originalEventObject.data.index),
            button = fieldset.one('button'),
            action, message, label, messageDiv;
        if (originalEventObject.data.action === 'subscribe') {
            action = response.error ? 'subscribe' : 'unsubscribe';
            message = response.error ? response.error : this.data.attrs.label_sub_success;
            label = response.error ? this.data.attrs.label_resub_button : this.data.attrs.label_unsub_button;
        }
        else {
            action = response.error ? 'unsubscrbe' : 'subscribe';
            message = response.error ? response.error : this.data.attrs.label_unsub_success;
            label = response.error ? this.data.attrs.label_unsub_button : this.data.attrs.label_resub_button;
        }
        if (button) {
            button.detach('click', this._onClick, this)
                  .set('innerHTML', label)
                  .set('disabled', false)
                  .on('click', this._onClick, this, action);
            if (messageDiv = fieldset.one('span')) {
                messageDiv.set('innerHTML', message);
            }
            else {
                messageDiv = this.Y.Node.create('<span>' + message + '</span>');
                button.insert(messageDiv, 'after');
            }
        }
    },

    /**
    * Event handler for when the subscribe/unsubscribe buttons are clicked
    * @param Object event Event Click event
    * @param String type The type of action to perform, one of 'subscribe' or 'unsubscribe'
    */
    _onClick: function(event, type) {
        var buttonElement = event.currentTarget,
            dataIndex = parseInt(buttonElement.getAttribute('data-index'), 10),
            eo = new RightNow.Event.EventObject(this, {data: {index: dataIndex, action: type}}),
            notificationData = this.data.js.notifications[dataIndex],
            successHandler, endpoint, eventName;

        if (!notificationData) {
            return;
        }

        if (type === 'subscribe') {
            endpoint = this.data.attrs.add_notification_ajax;
            if (notificationData.type === 'answer') {
                eo.data.responseEvent = 'evt_answerNotificationResponse';
                eventName = 'evt_answerNotificationUpdateRequest';
            }
            else {
                eo.data.responseEvent = 'evt_prodCatAddResponse';
                eventName = 'evt_prodCatAddRequest';
            }
        }
        else {
            endpoint = this.data.attrs.delete_notification_ajax;
            if (notificationData.type === 'answer') {
                eo.data.responseEvent = 'evt_answerNotificationDeleteResponse';
                eventName = 'evt_answerNotificationDeleteRequest';
            }
            else {
                eo.data.responseEvent = 'evt_prodCatDeleteResponse';
                eventName = 'evt_prodCatDeleteRequest';
            }
        }

        buttonElement.set('disabled', true);
        if (RightNow.Event.fire(eventName, eo)) {
            RightNow.Ajax.makeRequest(endpoint,
                {filter_type: notificationData.type, id: notificationData.id, cid: this.data.js.contactID, f_tok: this.data.js.f_tok},
                {successHandler: this._onResponse, scope: this, data: eo, json: true}
            );
        }
        return false;
    }
});
