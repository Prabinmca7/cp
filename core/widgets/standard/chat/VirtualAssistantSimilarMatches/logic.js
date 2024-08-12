RightNow.Widgets.VirtualAssistantSimilarMatches = RightNow.Widgets.extend({
    constructor: function()
    {
        RightNow.Event.subscribe("evt_chatStateChangeResponse", this._onChatStateChangeResponse, this);
        RightNow.Event.subscribe("evt_chatPostResponse", this._onChatPostResponse, this);
        RightNow.Event.subscribe("evt_chatEngagementParticipantAddedResponse", this._onChatEngagementParticipantAddedResponse, this);

        this.baseElement = this.Y.one(this.baseSelector);
        this.matchesElement = this.Y.one(this.baseSelector + "_Matches");
        this.matchesElement.setAttribute('aria-live', 'polite');
        this.matchesElement.delegate('click', this._onMatchClick, 'a', this);

        this._clickable = true;
        this._vaMode = false;
    },

    /**
     * Send the match back to Chat/VA
     * @param {object} type
     * @param {array} args
     */
    _onMatchClick: function(type, args)
    {
        if (this._clickable === false)
            return;

        this._clickable = false;
        type.preventDefault();
        var text = type.currentTarget.get("innerHTML");
        if(text.replace(/^\s*/, "").length == 0 || text.length > 349525) // max message size Chat server will accept
            return;

        var eo = new RightNow.Event.EventObject(this, {data: {
            messageBody: text,
            isEndUserPost: true,
            isOffTheRecord: this.data.attrs.all_posts_off_the_record
        }});

        RightNow.Event.fire("evt_chatPostMessageRequest", eo);
    },

    /**
     * Handles the state of the chat has changed. Hides button if disconnected.
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
                this._clickable = true;
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
     * Display/Hide the similar matches based on what's returned by the VA
     * @param type string Event name
     * @param args object Event arguments
     */
    _onChatPostResponse: function(type, args)
    {
        if(!args[0].data.isEndUserPost && args[0].data.vaResponse !== undefined && args[0].data.vaResponse !== null)
        {
            this._clickable = true;
            var vaResponse = args[0].data.vaResponse;
            if (vaResponse.questionlist && vaResponse.questionlist.length > 0)
            {
                var templateData = {
                        records: vaResponse.questionlist,
                        label_also: RightNow.Interface.getMessage("ALSO_MATCHES_WITH_COLON_LBL"),
                        length: (this.data.attrs.max_items_to_show > 0 && this.data.attrs.max_items_to_show < vaResponse.questionlist.length) ? this.data.attrs.max_items_to_show : vaResponse.questionlist.length
                    },
                    template = new EJS({text: this.getStatic().templates.view}).render(templateData);
                this.matchesElement.set("innerHTML", template);
            }
            else
            {
                this.matchesElement.empty();
            }
        }
    },

    /**
     * Listener for participant joining the engagement.
     * @param type string Event name
     * @param args object Event arguments
     */
    _onChatEngagementParticipantAddedResponse: function(type, args)
    {
        this._vaMode = (args[0].data.virtualAgent === undefined) ? false : args[0].data.virtualAgent;

        if (this._vaMode === false)
        {
            this.baseElement.addClass("rn_Hidden");
        }
        else
        {
            this.baseElement.removeClass("rn_Hidden");
        }
    }
});
