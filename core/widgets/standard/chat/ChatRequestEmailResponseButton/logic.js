RightNow.Widgets.ChatRequestEmailResponseButton = RightNow.Widgets.extend({
    constructor: function(){
        // Local member variables section
        this._container = this.Y.one(this.baseSelector);
        this._requestEmailResponseButton = this.Y.one(this.baseSelector + "_Button");
        this._currentState = RightNow.Chat.Model.SEARCHING;

        // Event subscription and listener section. If no UI object exists (requestEmailResponseButton), don't subscribe to any of the events since there's no UI object to update.
        if(this._container && this._requestEmailResponseButton)
        {
            this._requestEmailResponseButton.on("click", this._onButtonClick, this);
            RightNow.Event.subscribe("evt_chatStateChangeResponse", this._onChatStateChangeResponse, this);
        }
    },

    /**
     * Listener for Chat State Change notifications.
     * @param type string Event name
     * @param args object Event arguments
     */
    _onChatStateChangeResponse: function(type, args){
        var reason = args[0].data.reason;
        var currentState = args[0].data.currentState;
        if((currentState === RightNow.Chat.Model.ChatState.CANCELLED &&
            (reason === "FAIL_NO_AGENTS_AVAIL" || reason === "QUEUE_TIMEOUT")) ||
            currentState === RightNow.Chat.Model.ChatState.DEQUEUED ||
            currentState === RightNow.Chat.Model.ChatState.DISCONNECTED && reason === "NO_AGENTS_AVAILABLE")
            RightNow.UI.show(this._container);
        else
            RightNow.UI.hide(this._container);
    },

    /**
     * Handles when user clicks request email response button.
     * @param type string Event name
     * @param args object Event arguments
     */
    _onButtonClick: function(type, args)
    {
        //if we don't have a page_url attribute, we will take the user to the
        //ask a question page
        var pageToDisplay = this.Y.Lang.trim(this.data.attrs.page_url);
        if(pageToDisplay === ''){
            pageToDisplay = this.data.js.baseUrl + "/app/ask";
        }

        //display the page
        window.open(pageToDisplay);
    }
});
