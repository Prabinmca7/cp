RightNow.Widgets.ChatCobrowsePremium = RightNow.Widgets.extend({ 
    /**
     * Widget constructor.
     */
    constructor: function() {
        this._inCoBrowse = false;
        RightNow.Event.subscribe("evt_chatStateChangeResponse", this._onChatStateChangeResponse, this);
        RightNow.Event.subscribe("evt_chatCoBrowsePremiumInvitationResponse", this._coBrowsePremiumInvitationResponse, this);
        RightNow.Event.subscribe("evt_chatCoBrowsePremiumAcceptResponse", this._coBrowsePremiumAcceptResponse, this);
        RightNow.Event.subscribe("evt_chatCobrowseStatusResponse", this._coBrowseStatusResponse, this);
    },

    /**
     * Handles the state of the chat has changed. Closes cobrowse session if disconnected.
     * @param {string} type Event name
     * @param {object} args Event arguments
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
                RightNow.Event.fire("evt_chatCobrowseStatusResponse", eo);
            }
        }
    },

    /**
    * Event received when a cobrowse premium session is being offered. Records to ACS.
    * @param type string Event name
    * @param args object Event arguments
    */
    _coBrowsePremiumInvitationResponse: function(type, args)
    {
        RightNow.ActionCapture.record('chatCobrowsePremium', 'invite', args[0].data.coBrowseSessionId);
    },

    /**
     * Event for managing the state of the cobrowse session. Records to ACS.
     * @param {string} type Event name
     * @param {object} args Event arguments
     */
    _coBrowseStatusResponse: function(type, args)
    {
        var coBrowseStatus = args[0].data.coBrowseStatus;

        if(coBrowseStatus === RightNow.Chat.Model.ChatCoBrowseStatusCode.STARTED)
        {
            this._inCoBrowse = true;
            RightNow.ActionCapture.record('chatCobrowsePremium', 'sessionStart', args[0].data.coBrowseData);
        }
        else if(coBrowseStatus === RightNow.Chat.Model.ChatCoBrowseStatusCode.STOPPED)
        {
            this._inCoBrowse = false;
            CoBrowseLauncher.stopCoBrowse(args[0].data.coBrowseData);
            RightNow.ActionCapture.record('chatCobrowsePremium', 'sessionEnd', args[0].data.coBrowseData);
        }
    },

    /**
     * Initializes the cobrowse session. Received on user accept.
     * @param {string} type Event name
     * @param {object} args Event arguments
     */
    _coBrowsePremiumAcceptResponse: function(type, args)
    {
        if(args[0].data.accepted)
        {
            if(!args[0].data.test)
            {
                CoBrowseLauncher.startCoBrowse(args[0].data.coBrowseSessionId, args[0].data.agentEnvironment);
            }
            RightNow.ActionCapture.record('chatCobrowsePremium', 'accept', args[0].data.coBrowseSessionId);
        }
        else
        {
            RightNow.ActionCapture.record('chatCobrowsePremium', 'decline', args[0].data.coBrowseSessionId);
        }
    }
});
