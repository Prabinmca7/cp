RightNow.Widgets.ChatCancelButton = RightNow.Widgets.extend({
    constructor: function(){
        // Local member variables section
        this._container = this.Y.one(this.baseSelector);
        var cancelButton = this.Y.one(this.baseSelector + "_Button");
        // Event subscription and listener section. If no UI object exists (hangupButton), don't subscribe to any of the events since there's no UI object to update.
        if(cancelButton)
        {
            cancelButton.on("click", this._onButtonClick, this);
            RightNow.Event.subscribe("evt_chatStateChangeResponse", this._onChatStateChangeResponse, this);
        }
    },

    /**
     * Handles when user clicks hangup button. Sends disconnect and hides button.
     * @param type string Event name
     * @param args object Event arguments
     */
    _onButtonClick: function(type, args)
    {
        RightNow.Event.fire("evt_chatHangupRequest",
                new RightNow.Event.EventObject(this, {data: {isCancelled: true, cancelingUrl: this.data.attrs.canceling_url}}));
    },

    /**
     * Handles the state of the chat has changed. Hides button if not in queue.
     * @param type string Event name
     * @param args object Event arguments
     */
    _onChatStateChangeResponse: function(type, args)
    {
        var currentState = args[0].data.currentState;
        var previousState = args[0].data.previousState;
        var ChatState = RightNow.Chat.Model.ChatState;

        if(currentState === ChatState.RECONNECTING)
            return;

        if(currentState === ChatState.SEARCHING)
            RightNow.UI.show(this._container);
        else
            RightNow.UI.hide(this._container);
    }
});
