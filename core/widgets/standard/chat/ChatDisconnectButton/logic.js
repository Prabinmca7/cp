RightNow.Widgets.ChatDisconnectButton = RightNow.Widgets.extend({
    constructor: function(){
        // Local member variables section
        this._container = this.Y.one(this.baseSelector);
        this._disconnectButton = this.Y.one(this.baseSelector + "_Button");
        if(RightNow.Chat && RightNow.Chat.Model)
        {
            this._currentState = RightNow.Chat.Model.SEARCHING;
        }

        // Event subscription and listener section. If no UI object exists (disconnectButton), don't subscribe to any of the events since there's no UI object to update.
        if(this._container && this._disconnectButton)
        {
            this._disconnectButton.on("click", this._onButtonClick, this);
            RightNow.Event.subscribe("evt_chatStateChangeResponse", this._onChatStateChangeResponse, this);
        }
    },

    /**
     * Handles when user clicks disconnect button. Sends disconnect and hides button.
     * @param type string Event name
     * @param args object Event arguments
     */
    _onButtonClick: function(type, args)
    {
        if(this._currentState !== RightNow.Chat.Model.ChatState.DISCONNECTED && this._currentState !== RightNow.Chat.Model.ChatState.CANCELLED){
            RightNow.Event.fire("evt_chatHangupRequest", new RightNow.Event.EventObject(this, {data: {}}));
        }
        else{
            RightNow.Event.fire("evt_chatCloseButtonClickRequest", new RightNow.Event.EventObject(this, {data: {closingUrl: this.data.attrs.close_redirect_url, openInWindow: this.data.attrs.open_in_window}}));
        }
    },
    /**
     * Handles the state of the chat has changed. Hides button if disconnected.
     * @param type string Event name
     * @param args object Event arguments
     */
    _onChatStateChangeResponse: function(type, args)
    {
        var currentState = args[0].data.currentState;
        var previousState = args[0].data.previousState;
        var ChatState = RightNow.Chat.Model.ChatState;

        if(currentState === ChatState.CONNECTED && previousState !== ChatState.CONNECTED)
        {
            RightNow.UI.show(this._container);
        }
        else if(currentState === ChatState.CANCELLED || currentState === ChatState.DISCONNECTED)
        {
            if(this.data.attrs.mobile_mode || !window.opener)
            {
                RightNow.UI.hide(this._container);
            }
            else
            {
                this._disconnectButton.set('innerHTML', new EJS({text: this.getStatic().templates.closeButton}).render({attrs: this.data.attrs}))
                                       .set('title', this.data.attrs.label_tooltip_close);
            }
        }

        this._currentState = currentState;
    }
});
