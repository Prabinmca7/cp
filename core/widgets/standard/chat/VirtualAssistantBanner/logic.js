RightNow.Widgets.VirtualAssistantBanner = RightNow.Widgets.extend({
    constructor: function() {
        RightNow.Event.subscribe("evt_chatStateChangeResponse", this._onChatStateChangeResponse, this);
        RightNow.Event.subscribe("evt_chatPostResponse", this._onChatPostResponse, this);
        RightNow.Event.subscribe("evt_chatEngagementParticipantAddedResponse", this._onChatEngagementParticipantAddedResponse, this);

        this.baseElement = this.Y.one(this.baseSelector);
        this.bannerElement = this.Y.one(this.baseSelector + "_Banner");
        this.bannerElement.setAttribute('aria-live', 'polite');
        this._vaMode = false;
    },

    /**
     * Handles chat state changes. Hides widget if disconnected, canceled, requeued or re-connecting.
     * @param type string Event name
     * @param args object Event arguments
     */
    _onChatStateChangeResponse: function(type, args)
    {
        if(!RightNow.Event.fire("evt_handleChatStateChange", new RightNow.Event.EventObject(this, {data: args[0].data})))
            return;

        var currentState = args[0].data.currentState,
            ChatState = RightNow.Chat.Model.ChatState;

        switch (currentState) 
        {
            case ChatState.REQUEUED:
            case ChatState.CANCELED:
            case ChatState.DISCONNECTED:
            case ChatState.RECONNECTING:
                this.baseElement.addClass("rn_Hidden");
                break;

            case ChatState.CONNECTED:
                if (this._vaMode === true) {
                    this.baseElement.removeClass("rn_Hidden");
                }
                break;
        }
    },

    /**
     * Display/Hide the banner based on what's returned by the VA
     * @param type string Event name
     * @param args object Event arguments
     */
    _onChatPostResponse: function(type, args) {
        if(!args[0].data.isEndUserPost && args[0].data.vaResponse !== undefined && args[0].data.vaResponse !== null) {
            var vaResponse = args[0].data.vaResponse;
            if (vaResponse.banners && vaResponse.banners.length > 0) {
                var templateData = new EJS({text: this.getStatic().templates.view}).render(vaResponse.banners[0]);
                this.bannerElement.set("innerHTML", templateData);

                if (vaResponse.banners[0].targetUrl) {
                    this.bannerElement.detach('click', this._onClick); // Remove old banner events from the div.
                    this.bannerElement.on('click', this._onClick, this, vaResponse.banners[0].id);
                }
            }
            else {
                this.bannerElement.empty();
            }
        }
    },

    /**
     * Handler for when images or URL's are clicked
     * @param type string Event name
     * @param args object Event arguments
     */
    _onClick: function(type, args) {
        var eventData = {
            method: 'banner_click',
            package: {id: args}
        };

        RightNow.Event.fire('evt_chatPostOutOfBandDataRequest', new RightNow.Event.EventObject(this, {data: eventData}));
    },

    /**
     * Listener for participant joining the engagement.
     * @param type string Event name
     * @param args object Event arguments
     */
    _onChatEngagementParticipantAddedResponse: function(type, args) {
        this._vaMode = (args[0].data.virtualAgent === undefined) ? false : args[0].data.virtualAgent;
        
        if (this._vaMode === true) {
            this.baseElement.removeClass("rn_Hidden");
        }
        else {
            this.baseElement.addClass("rn_Hidden");
        }
    }

});
