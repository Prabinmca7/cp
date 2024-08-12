RightNow.Widgets.ChatEngagementStatus = RightNow.Widgets.extend({
    constructor: function() {
        this._currentState = null;
        this._previousState = null;
        this._reason = null;
        this._widgetElement = this.Y.one(this.baseSelector);

        // IE7 is no longer supported since it doesn't support native CORS
        // Widget load timing issues force us to detect and handle this here instead of in event bus.
        // IE8 is no longer supported starting 14.5+
        // Create mock eventObject and just call the state change function to handle UI updates.
        if(this.Y.UA.ie > 0 && this.Y.UA.ie <= 8)
        {
            this._onChatStateChangeResponse(null, [{'data': { 'currentState': RightNow.Chat.Model.ChatState.DISCONNECTED, 'reason': RightNow.Chat.Model.ChatDisconnectReason.BROWSER_UNSUPPORTED}}]);
            return;
        }

        if(this._widgetElement)
        {
            RightNow.Event.subscribe("evt_chatStateChangeResponse", this._onChatStateChangeResponse, this);
        }

        if(this.data.attrs.is_persistent_chat)
        {
            this._ls = RightNow.Chat.LS;
            if(this._ls.isSupported)
            {
                this._ls.attachStoreEvent();
            }
        }
    },

    /**
     * Listener for Chat State Change notifications.
     * @param type string Event name
     * @param args object Event arguments
     */
    _onChatStateChangeResponse: function(type, args)
    {
        this._currentState = args[0].data.currentState;
        this._previousState = args[0].data.previousState;
        this._reason = args[0].data.reason;
        if(this.data.attrs.is_persistent_chat && this._ls.isSupported)
        {
            var data = {currentState: this._currentState, previousState: this._previousState, reason: this._reason, type: 'CHAT_CONNECT_STATUS', chatWindowId: this._ls._thisWindowId};
            this._ls.setItem(this._ls._connectStatusKey, data);
        }
        this._updateStatus();

        //show it to the world
        RightNow.UI.show(this._widgetElement);

        this._updateSearchingDetail();
        this._updateRequeuedDetail();
        this._updateCanceledDetail();
    },

    /**
     * Updates the Status Element
     */
    _updateStatus: function()
    {
        var statusElement = this.Y.one(this.baseSelector + "_Status");
        if(!statusElement)
            return;

        //update status
        var ChatState = RightNow.Chat.Model.ChatState;
        switch(this._currentState)
        {
            case ChatState.SEARCHING:
            case ChatState.REQUEUED:
            {
                statusElement.set("innerHTML", this.data.attrs.label_status_searching);
                break;
            }
            case ChatState.CONNECTED:
            {
                statusElement.set("innerHTML", this.data.attrs.label_status_connected);
                break;
            }
            case ChatState.RECONNECTING:
            {
                if(this._previousState === RightNow.Chat.Model.CONNECTED)
                    statusElement.set("innerHTML", this.data.attrs.label_status_reconnecting);
                break;
            }
            case ChatState.CANCELLED:
            case ChatState.DEQUEUED:
            {
                statusElement.set("innerHTML", this.data.attrs.label_status_canceled);
                break;
            }
            case ChatState.DISCONNECTED:
            {
                if(this._reason && this._reason === 'RECONNECT_FAILED')
                    statusElement.set("innerHTML", RightNow.Interface.getMessage("COMM_RN_LIVE_SERV_LOST_CHAT_SESS_MSG"));
                else
                    statusElement.set("innerHTML", this.data.attrs.label_status_disconnected);
                break;
            }
        }
    },

    /**
     * Updates the Searching Detail Element
     */
    _updateSearchingDetail: function()
    {
        var searchingDetailElement = this.Y.one(this.baseSelector + "_Searching");
        if(!searchingDetailElement)
            return;

        var ChatState = RightNow.Chat.Model.ChatState;
        if(this._currentState === ChatState.RECONNECTING)
            return;

        if(this._currentState == ChatState.SEARCHING ||
           this._currentState == ChatState.REQUEUED)
        {
            RightNow.UI.show(searchingDetailElement);
        }
        else
        {
            RightNow.UI.hide(searchingDetailElement);
            RightNow.Event.fire('evt_chatQueueSearchEnd', new RightNow.Event.EventObject(this, {}));
        }
    },

    /**
     * Updates the Requeued Detail Element
     */
    _updateRequeuedDetail: function()
    {
        var requeuedDetailElement = this.Y.one(this.baseSelector + "_Requeued");
        if(!requeuedDetailElement)
            return;

        var ChatState = RightNow.Chat.Model.ChatState;
        if(this._currentState === ChatState.RECONNECTING)
            return;

        if(this._currentState == ChatState.REQUEUED)
            RightNow.UI.show(requeuedDetailElement);
        else
            RightNow.UI.hide(requeuedDetailElement);
    },

    /**
     * Updates the Canceled Detail Elements
     */
    _updateCanceledDetail: function()
    {
        var canceledUserDetailElement = this.Y.one(this.baseSelector + "_Canceled_User");
        var canceledSelfServiceDetailElement = this.Y.one(this.baseSelector + "_Canceled_Self_Service");
        var canceledNoAgentsAvailDetailElement = this.Y.one(this.baseSelector + "_Canceled_NoAgentsAvail");
        var canceledQueueTimeoutDetailElement = this.Y.one(this.baseSelector + "_Canceled_Queue_Timeout");
        var canceledDequeuedDetailElement = this.Y.one(this.baseSelector + "_Canceled_Dequeued");
        var canceledBrowserDetailElement = this.Y.one(this.baseSelector + "_Canceled_Browser");

        var ChatState = RightNow.Chat.Model.ChatState;
        var ChatDisconnectReason = RightNow.Chat.Model.ChatDisconnectReason;
        if(this._currentState === ChatState.RECONNECTING)
            return;

        if(this._currentState == ChatState.CANCELLED)
        {
            if(canceledUserDetailElement && this._reason === ChatDisconnectReason.ENDED_USER_CANCEL)
                RightNow.UI.show(canceledUserDetailElement);
            else if(canceledSelfServiceDetailElement && this._reason === ChatDisconnectReason.ENDED_USER_DEFLECTED)
                RightNow.UI.show(canceledSelfServiceDetailElement);
            else if(canceledNoAgentsAvailDetailElement && this._reason === ChatDisconnectReason.FAIL_NO_AGENTS_AVAIL)
                RightNow.UI.show(canceledNoAgentsAvailDetailElement);
            else if(canceledQueueTimeoutDetailElement && this._reason === ChatDisconnectReason.QUEUE_TIMEOUT)
                RightNow.UI.show(canceledQueueTimeoutDetailElement);
            }
        else if(this._currentState == ChatState.DEQUEUED && canceledDequeuedDetailElement)
        {
            RightNow.UI.show(canceledDequeuedDetailElement);
        }
        else if(this._currentState === ChatState.DISCONNECTED && this._reason === ChatDisconnectReason.NO_AGENTS_AVAILABLE && canceledUserDetailElement)
        {
                RightNow.UI.show(canceledNoAgentsAvailDetailElement);
        }
        else if(this._currentState === ChatState.DISCONNECTED && typeof ChatDisconnectReason.BROWSER_UNSUPPORTED !== 'undefined' && this._reason === ChatDisconnectReason.BROWSER_UNSUPPORTED && canceledBrowserDetailElement)
        {
                RightNow.UI.show(canceledBrowserDetailElement);
        }
        else
        {
            if(canceledUserDetailElement)
                RightNow.UI.hide(canceledUserDetailElement);
            if(canceledSelfServiceDetailElement)
                RightNow.UI.hide(canceledSelfServiceDetailElement);
            if(canceledNoAgentsAvailDetailElement)
                RightNow.UI.hide(canceledNoAgentsAvailDetailElement);
            if(canceledQueueTimeoutDetailElement)
                RightNow.UI.hide(canceledQueueTimeoutDetailElement);
            if(canceledDequeuedDetailElement)
                RightNow.UI.hide(canceledDequeuedDetailElement);
        }
    }
});
