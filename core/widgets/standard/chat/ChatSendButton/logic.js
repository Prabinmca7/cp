RightNow.Widgets.ChatSendButton = RightNow.Widgets.extend({
    constructor: function(){
        // Local member variables section
        this._container = this.Y.one(this.baseSelector);
        var sendButton = this.Y.one(this.baseSelector + "_Button");

        // Event subscription and listener section. If no UI object exists (sendButton), don't subscribe to any of the events since there's no UI object to update.
        if(sendButton)
        {
            sendButton.on("click", this._onButtonClick, this);
            RightNow.Event.subscribe("evt_chatStateChangeResponse", this._onChatStateChangeResponse, this);
        }
    },

    /**
     * Handles when user clicks send button. Sends disconnect and hides button.
     * @param type string Event name
     * @param args object Event arguments
     */
    _onButtonClick: function(type, args)
    {
        RightNow.Event.fire("evt_chatSendButtonClickRequest", new RightNow.Event.EventObject(this));
    },

    /**
     * Handles the state of the chat has changed. Hides button if disconnected.
     * @param type string Event name
     * @param args object Event arguments
     */
    _onChatStateChangeResponse: function(type, args)
    {
        var currentState = args[0].data.currentState;
        var ChatState = RightNow.Chat.Model.ChatState;

        if(currentState === ChatState.CONNECTED)
        {
            this._container.addClass("rn_ChatSendButtonShown");
            RightNow.UI.show(this._container);
        }
        else if(currentState === ChatState.REQUEUED || currentState === ChatState.DISCONNECTED || currentState === ChatState.RECONNECTING)
        {
            this._container.removeClass("rn_ChatSendButtonShown");
            RightNow.UI.hide(this._container);
        }
    }
});
