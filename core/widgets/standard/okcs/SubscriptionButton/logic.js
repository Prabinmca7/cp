RightNow.Widgets.SubscriptionButton = RightNow.Widgets.extend({
    constructor: function() {
        this._subscribeButton = this.Y.one(this.baseSelector + '_SubscribeButton');
        if(this._subscribeButton) {
            this._subscribeButton.on('click', this._onSubscribeClick, this);
        }
    },

    /**
    * Event handler executed when the subscription button is clicked
    * @param {Object} e Event
    */
    _onSubscribeClick: function(e) {
        e.halt();
        var eventObject;
        if(this.data.js.subscriptionID && this.data.js.subscriptionID !== null){
            eventObject = new RightNow.Event.EventObject(this, {data: {
               answerID: this.data.js.answerID,
               subscriptionID: this.data.js.subscriptionID,
               action: 'Unsubscribe'
            }});
            RightNow.Ajax.makeRequest(this.data.attrs.get_okcs_data_ajax, eventObject.data, {
                successHandler: this._displayUnsubscribeMessage,
                failureHandler: this._displayErrorOnFailure,
                json: true, scope: this
            });
        }
        else {
            eventObject = new RightNow.Event.EventObject(this, {data: {
               answerID: this.data.js.answerID,
               docId: this.data.js.docId,
               versionID: this.data.js.versionID,
               active: true,
               action: 'Subscribe'
            }});
            RightNow.Ajax.makeRequest(this.data.attrs.get_okcs_data_ajax, eventObject.data, {
                successHandler: this._displaySubscribeMessage,
                failureHandler: this._displayErrorOnFailure,
                json: true, scope: this
            });
        }
    },

    /**
    * Displays Unsubscription message from the ajax response success.
    * @param response Object response.
    */
    _displayUnsubscribeMessage: function(response) {
        if(response.result === 'OKDOM-USER0014') {
            RightNow.UI.displayBanner(this.data.js.failureMsgOnUnsub, { type: 'ERROR', focus: true });
            this._subscribeButton.set("innerHTML", this.data.attrs.label_sub_button, { focusElement: this._subscribeButton });
            this.data.js.subscriptionID = null;
            return;
        }
        if(response.failure){
            RightNow.UI.displayBanner(RightNow.Interface.ASTRgetMessage(response.failure), { type: 'ERROR', focusElement: this._subscribeButton });
        }
        else{
            this.data.js.subscriptionID = null;
            this._updateAriaAlert(this.data.attrs.label_unsubscribe_msg);
            RightNow.UI.displayBanner(this.data.attrs.label_unsubscribe_msg);
            this._subscribeButton.set("innerHTML", this.data.attrs.label_sub_button, { focusElement: this._subscribeButton });
        }
    },

    /**
    * Displays error message from the ajax response failure.
    * @param response Object response.
    */
    _displayErrorOnFailure: function(response) {
        var message = response.suggestedErrorMessage || RightNow.Interface.getMessage('THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG');
        if(response.status === 403 && response.responseText !== undefined){
            message = response.responseText;
        }
        RightNow.UI.Dialog.messageDialog(message, {"icon": "WARN", exitCallback: function(){window.location.reload(true);}});
    },

    /**
    * Displays Subscription message from the ajax response success.
    * @param response Object response.
    */
    _displaySubscribeMessage: function(response) {
        if(response.result === 'OKDOM-USER0019') {
            RightNow.UI.displayBanner(this.data.attrs.label_failure_dup_notif, { type: 'ERROR', focus: true });
            return;
        }
        if(response.failure){
            RightNow.UI.displayBanner(RightNow.Interface.ASTRgetMessage(response.failure), { type: 'ERROR', focusElement: this._subscribeButton });
        }
        else{
            this.data.js.answerID = response.content.answerId;
            this.data.js.subscriptionID = response.recordId;
            this.data.js.versionID = response.content.versionId;
            this._updateAriaAlert(this.data.attrs.label_subscribe_msg);
            RightNow.UI.displayBanner(this.data.attrs.label_subscribe_msg, { focusElement: this._subscribeButton });
            this._subscribeButton.set("innerHTML", this.data.attrs.label_unsub_button);
        }
    },

    /**
     * Updates the text for the ARIA alert div that appears above search rating
     * @param {String} text The text to update the div with
     */
    _updateAriaAlert: function(text) {
        this._ariaAlert = this._ariaAlert || this.Y.one(this.baseSelector + '_Alert');
        if(this._ariaAlert) {
            this._ariaAlert.set('innerHTML', text);
        }
    }
});
