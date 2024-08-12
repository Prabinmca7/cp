RightNow.Widgets.ChatOffTheRecordButton = RightNow.Widgets.extend({
    constructor: function(){
        // Local member variables section
        this._container = this.Y.one(this.baseSelector);
        this._offTheRecordButton = this.Y.one(this.baseSelector + '_Button');

        // Event subscription and listener section.
        if(this._offTheRecordButton && this._container)
        {
            this._offTheRecordButton.on("click", this._onButtonClick, this);
            RightNow.Event.subscribe("evt_chatStateChangeResponse", this._onChatStateChangeResponse, this);
        }
    },

    /**
     * Handles when user clicks off the record button.
     * @param type string Event name
     * @param args object Event arguments
     */
    _onButtonClick: function(type, args)
    {
        RightNow.Event.fire("evt_chatOffTheRecordButtonClickRequest", new RightNow.Event.EventObject(this));
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
            RightNow.UI.show(this._container);
        else if(currentState === ChatState.REQUEUED || currentState === ChatState.DISCONNECTED || currentState === ChatState.RECONNECTING)
            RightNow.UI.hide(this._container);
    }
});
