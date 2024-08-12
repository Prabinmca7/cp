RightNow.Widgets.DiscussionSubscriptionIcon = RightNow.Widgets.extend({
    constructor: function() {
        this.subscriptionDiv = this.Y.one(this.baseSelector + '_Subscription');
        this.subscribeDiv = this.Y.one(this.baseSelector + '_Subscribe');
        this.unsubscribeDiv = this.Y.one(this.baseSelector + '_Unsubscribe');
        this.subscribedToProdDiv = this.Y.one(this.baseSelector + '_ProdSubscribed');
        this.loadingIcon = this.Y.one(this.baseSelector + '_LoadingIcon');
        this.subscribedToCatDiv = this.Y.one(this.baseSelector + '_CatSubscribed');

        if(this.subscribeDiv) {
            this.subscribeDiv.on('click', this._createSubscription, this);
        }
        this.unsubscribeDiv.on('click', this._deleteSubscription, this);

        // subscribe to the event for update in status to a social question
        RightNow.Event.subscribe('evt_inlineModerationStatusUpdate', this._onStatusUpdate, this);

        RightNow.Widgets.formTokenRegistration(this);
    },

    /**
     *  Create event object and make AJAX request for subscribing to a question
     */
    _createSubscription: function() {
        this._makeAjaxRequest(this.data.attrs.add_social_subscription_ajax, 'subscribe');
    },

    /**
     * Create event object and make AJAX request for unsubscribing to a question
     */
    _deleteSubscription: function() {
        this._makeAjaxRequest(this.data.attrs.delete_social_subscription_ajax, 'unsubscribe');
    },

    /**
     * Makes an AJAX request to the enpoint
     * @param {String} requestEndpoint Ajax request url
     * @param {String} action Type of requested action e.g. subscribe/unsubscribe
     */
    _makeAjaxRequest: function(requestEndpoint, action) {
        var eventObject = new RightNow.Event.EventObject(this, {data: {id: this.data.js.objectID, type: this.data.attrs.subscription_type, action: action, f_tok: this.data.js.f_tok}});
        this.loadingIcon.toggleClass("rn_Hidden");
        this.subscriptionDiv.toggleClass("rn_Hidden");
        RightNow.Ajax.makeRequest(requestEndpoint, eventObject.data, {
            successHandler: this._onResponse, scope: this, data: eventObject, json: true
        });
    },

   /**
    * On successful subscription toggles subscribe/subscribed to product and unsubscribe link
    * else display an error message
    * @param {Object} response Object which has success or error message to display
    * @param {Object} eventObject Object used for AJAX Request
    */
    _onResponse: function (response, eventObject) {
        var textToDisplay = [];
        this.loadingIcon.toggleClass("rn_Hidden");
        this.subscriptionDiv.toggleClass("rn_Hidden");
        if (response.success) {
            if(response.newFormToken) {
                RightNow.Event.fire("evt_formTokenUpdate", new RightNow.Event.EventObject(this, {data: {newToken: response.newFormToken}}));
            }
            this.unsubscribeDiv.toggleClass("rn_Hidden").toggleClass("rn_Show");
            var message;
            if(eventObject.data.action === 'subscribe') {
                message = this.data.attrs.label_on_subscription_success_banner;
                this.subscribeDiv.toggleClass("rn_Hidden").toggleClass("rn_Show");
            }
            else {
                message = this.data.attrs.label_on_unsubscription_success_banner;
                if(this.data.js.prodSubscriptionID) {
                    this.subscribedToProdDiv.toggleClass("rn_Hidden").toggleClass("rn_Show");
                }
                else if (this.data.js.catSubscriptionID) {
                    this.subscribedToCatDiv.toggleClass("rn_Hidden").toggleClass("rn_Show");
                }
                else {
                    this.subscribeDiv.toggleClass("rn_Hidden").toggleClass("rn_Show");
                }
            }
            RightNow.UI.displayBanner(message, { focusElement: this.subscriptionDiv.one('.rn_Show')});
        }
        else {
            if(!RightNow.Ajax.indicatesSocialUserError(response)) {
                if (response.errors) {
                    this.Y.Object.each(response.errors, function(message) {
                        if (message.hasOwnProperty('externalMessage'))
                            textToDisplay.push(message.externalMessage);
                    });
                }
                RightNow.UI.displayBanner((textToDisplay.length !== 0) ? textToDisplay.join('<br>') : this.data.attrs.label_on_failure_banner, {type: 'ERROR'});
            }
        }
    },

    /**
     * Event handler for when social question status update event is received from server.
     * @param {Object} evt Current event object
     * @param {Array} args Data passed from widget which trigger this function call
     */
    _onStatusUpdate: function(evt, args) {
        // if the question's status changes to active then show subscribe/unsubscribe link else hide it
        if (args[0].data.object_data.updatedObject.objectType === 'CommunityQuestion') {
            if (parseInt(args[0].data.object_data.updatedObject.statusWithTypeID, 10) !== this.data.js.activeStatusWithTypeID) {
                RightNow.UI.hide(this.baseSelector);
            }
            else {
                RightNow.UI.show(this.baseSelector);
            }
        }
    }
});
