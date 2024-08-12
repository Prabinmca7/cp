RightNow.Widgets.DiscussionAuthorSubscription = RightNow.Field.extend({
    overrides: {
        constructor: function() {
            this.parent();
            this.subscribedDiv = this.Y.one(this.baseSelector + '_Subscribed');
            this.subscribeMeDiv = this.Y.one(this.baseSelector + '_SubscribeMe');
            this.subscribeMeCheckbox = this.Y.one(this.baseSelector + '_SubscribeMe_Check');
            this.loadingIcon = this.Y.one(this.baseSelector + '_LoadingIcon');
            this.authorSubscriptionDiv = this.Y.one(this.baseSelector + '_AuthorSubscription');
            RightNow.Event.subscribe('evt_productCategorySelected', this._getProdCatID, this);
            this.parentForm().on('submit', this._onSubmit, this);
        }
    },

    /**
     * Event handler executed when a product/category is selected from product menu
     * @param {Object} evt Current event object
     * @param {Array} eventData Event data
     */
     _getProdCatID: function(evt, eventData) {
        this.loadingIcon.toggleClass("rn_Hidden");
        this.authorSubscriptionDiv.toggleClass("rn_Hidden");
        RightNow.Event.fire("evt_formToggleButton", false);
        var eventObject = new RightNow.Event.EventObject(this, {data: {prodCatID: eventData[0].data.hierChain[eventData[0].data.hierChain.length - 1], w_id: this.data.info.w_id}});
        if(this.data.js.f_tok){
            RightNow.Event.subscribe("evt_formTokenUpdate", RightNow.Widgets.onFormTokenUpdate, this);
            // Get a new f_tok value on each ajax request
            RightNow.Event.fire("evt_formTokenRequest", new RightNow.Event.EventObject(this, {data:{formToken:this.data.js.f_tok}}));

            eventObject.data.f_tok = this.data.js.f_tok;
        }
        RightNow.Ajax.makeRequest(this.data.attrs.fetch_prodcat_subscription_ajax, eventObject.data, {
            successHandler: this._onResponse, scope: this, data: eventObject, json: true
        });
    },

   /**
    * On fetching product subscription show/hide subscribe me checkbox
    * @param {Object} response Object which has success or error message to display
    * @param {Object} eventObject Object used for AJAX Request
    */
    _onResponse: function (response, eventObject) {
        this.loadingIcon.toggleClass("rn_Hidden");
        RightNow.Event.fire("evt_formToggleButton", true);
        this.authorSubscriptionDiv.toggleClass("rn_Hidden");
        if (response) {
            this.subscribedDiv.removeClass("rn_Hidden");
            this.subscribeMeDiv.addClass("rn_Hidden");
            this.subscribeMeCheckbox.set('checked', false);
        }
        else {
            this.subscribedDiv.addClass("rn_Hidden");
            this.subscribeMeDiv.removeClass("rn_Hidden");
            if (this.data.attrs.subscribe_me_default) {
                this.subscribeMeCheckbox.set('checked', true);
            }
        }
    },

    /**
     * Event handler executed when form is being submitted.
     * @param {String} type Event name
     * @param {Array} args Event arguments
     * @return {object|boolean} Event object if requested for subscription, true otherwise
     */
    _onSubmit: function(type, args) {
        var eventObject = this.createEventObject();

        if(this.subscribeMeCheckbox.get('checked')) {
            eventObject.data.value = this.subscribeMeCheckbox.get('value');
            eventObject.data.name = this.subscribeMeCheckbox.get('name');
            RightNow.Event.fire('evt_formFieldValidatePass', eventObject);
            return eventObject;
        }
        return true;
    }
});
