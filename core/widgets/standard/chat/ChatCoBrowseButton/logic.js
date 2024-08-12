RightNow.Widgets.ChatCoBrowseButton = RightNow.Widgets.extend({
    constructor: function(){
        if (!RightNow.Interface.getConfig("MOD_COBROWSE_ENABLED"))
            return;

        // Local member variables section
        this._hangupCoBrowseButton = this.Y.one(this.baseSelector + '_Button');
        this._coBrowseIFrame = this.Y.one(this.baseSelector + '_IFrame');
        this._inCoBrowse = false;

        // Event subscription and listener section.
        if(this._hangupCoBrowseButton && this._coBrowseIFrame)
        {
            this._hangupCoBrowseButton.on("click", this._onButtonClick, this);
            RightNow.Event.subscribe("evt_chatStateChangeResponse", this._onChatStateChangeResponse, this);
            RightNow.Event.subscribe("evt_chatCobrowseAcceptResponse", this._coBrowseAcceptResponse, this);
            RightNow.Event.subscribe("evt_chatCobrowseStatusResponse", this._coBrowseStatusResponse, this);
        }
    },

    /**
     * Handles when user clicks hangup cobrowse button.
     * @param type string Event name
     * @param args object Event arguments
     */
    _onButtonClick: function(type, args)
    {
        this._stopCoBrowseSession();
    },

    /**
     * Handles the state of the chat has changed. Hides button and closes cobrowse session if disconnected.
     * @param type string Event name
     * @param args object Event arguments
     */
    _onChatStateChangeResponse: function(type, args)
    {
        if(this._inCoBrowse)
        {
            var currentState = args[0].data.currentState;
            if(currentState !== RightNow.Chat.Model.ChatState.CONNECTED)
            {
                // If disconnected during chat, the STOPPED event won't come from chat service. Generate here.
                var eo = new RightNow.Event.EventObject(this, {data: {coBrowseStatus: RightNow.Chat.Model.ChatCoBrowseStatusCode.STOPPED}});
                this._stopCoBrowseSession();
                RightNow.Event.fire("evt_chatCobrowseStatusResponse", eo);
            }
        }
    },

    /**
     * Event for managing the state of the cobrowse session. Shows "end session" button if connected. Ends session if halted.
     * @param type string Event name
     * @param args object Event arguments
     */
    _coBrowseStatusResponse: function(type, args)
    {
        var coBrowseStatus = args[0].data.coBrowseStatus;

        if(coBrowseStatus === RightNow.Chat.Model.ChatCoBrowseStatusCode.STARTED)
        {
            this._inCoBrowse = true;
            RightNow.UI.show(this._hangupCoBrowseButton);
            RightNow.UI.hide(this._coBrowseIFrame);
        }
        else if(this._inCoBrowse && coBrowseStatus === RightNow.Chat.Model.ChatCoBrowseStatusCode.STOPPED)
        {
            this._stopCoBrowseSession();
        }
    },

    /**
     * Initializes the cobrowse session. Received on user accept.
     * @param type string Event name
     * @param args object Event arguments
     */
    _coBrowseAcceptResponse: function(type, args)
    {
        if(args[0].data.accepted)
        {
            this._inCoBrowse = true;
            RightNow.UI.show(this._coBrowseIFrame);
            this._coBrowseIFrame.set('src', args[0].data.coBrowseUrl);
        }
    },

    /**
     * Stops the cobrowse session by resetting the hidden iframe. Hides hangup cobrowse button.
     */
    _stopCoBrowseSession: function()
    {
        RightNow.UI.hide(this._hangupCoBrowseButton);
        this._coBrowseIFrame.set('src', 'about:blank');
        this._inCoBrowse = false;
    }
});
