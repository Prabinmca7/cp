RightNow.Widgets.CobrowsePremium = RightNow.Widgets.extend({
    /**
     * Widget constructor.
     */
    constructor: function() {
        this._siteID = RightNow.Interface.getConfig("COBROWSE_SITE_ID");
        var scope = this;
        var timer = setInterval(function() {
            if (typeof LL_Cobrowse_Manager !== "undefined" && LL_Cobrowse_Manager)
            {
                LL_Cobrowse_Manager.Events.NumberGenerated.listen(scope._siteID, scope._onSessionStartedResponse);
                clearInterval(timer);
            }
        }, 300);
        RightNow.Event.subscribe("evt_sessionIDAvail", this._onSessionIDAvailable, this);
    },

    /**
    * Event received when sessionID is available.
     * @param {string} type Event name
     * @param {object} args Event arguments
    */
    _onSessionIDAvailable: function(type, args)
    {
        if(!args[0].data.test)
        {
            LL_Cobrowse_Manager.Events.SessionDisconnected.listen(args[0].data.sessionID, this._onSessionDisconnectedResponse);
        }
    },
    /**
    * Event received when a cobrowse premium session is being started. Records to ACS.
    * @param {string} sessionIdentifier LiveLOOK session identifier used to uniquely identify a co-browsing session. You will need this identifier for SessionDisconnected event
    * @param {object} sessionDescriptor Session object containing properties: SID, presentationToken, presentationCode.
    */
    _onSessionStartedResponse: function(sessionIdentifier, sessionDescriptor) 
    {
        RightNow.ActionCapture.record('CobrowsePremium', 'sessionStart', sessionIdentifier);
        if (sessionDescriptor !== null && sessionDescriptor.presentationCode !== null)
        {
            RightNow.ActionCapture.record('CobrowsePremium', 'sessionStart', sessionDescriptor.presentationCode);
        }
        var eo = new RightNow.Event.EventObject(this, {data: {sessionID: sessionIdentifier}});
        RightNow.Event.fire("evt_sessionIDAvail", eo);
    },
    
    /**
    * Event received when a cobrowse premium session is disconnected. Records to ACS.
    * @param {string} sessionIdentifier LiveLOOK session identifier used to uniquely identify a co-browsing session.
    * @param {object} sessionDescriptor Session object containing properties: SID, presentationToken, presentationCode.
    */
    _onSessionDisconnectedResponse: function(sessionIdentifier, sessionDescriptor) 
    {
        RightNow.ActionCapture.record('CobrowsePremium', 'sessionDisconnect', sessionIdentifier);
        if (sessionDescriptor !== null && sessionDescriptor.presentationCode !== null)
        {
            RightNow.ActionCapture.record('CobrowsePremium', 'sessionDisconnect', sessionDescriptor.presentationCode);
        }
    }
});
