RightNow.Widgets.ChatAgentStatus = RightNow.Widgets.extend({
    constructor: function(){
        // Local member variables section
        this._container = this.Y.one(this.baseSelector);
        this._roster = this.Y.one(this.baseSelector + "_Roster");

        // Event subscription section. If no UI object exists (roster), don't subscribe to any of the events since there's no UI object to update.
        if(this._container && this._roster)
        {
            RightNow.Event.subscribe("evt_chatAgentStatusChangeResponse", this._onChatAgentStatusChangeResponse, this);
            RightNow.Event.subscribe("evt_chatEngagementParticipantAddedResponse", this._onChatEngagementParticipantAddedResponse, this);
            RightNow.Event.subscribe("evt_chatEngagementParticipantRemovedResponse", this._onChatEngagementParticipantRemovedResponse, this);
            RightNow.Event.subscribe("evt_chatStateChangeResponse", this._onChatStateChangeResponse, this);
        }
    },

    /**
     * Listener for participant joining the engagement.
     * @param type string Event name
     * @param args object Event arguments
    */
    _onChatEngagementParticipantAddedResponse: function(type, args)
    {
        if(!args[0].data.agent)
            return;

        var agent = args[0].data.agent;

        //create HTML, create Node, and append to roster
        this._roster.appendChild(this.Y.Node.create(new EJS({text: this.getStatic().templates.participantAddedResponse}).render({
            attrs: this.data.attrs,
            instanceID: this.instanceID,
            agentName: this.data.attrs.agent_id.replace(/{display_name}/g, agent.name),
            clientID: agent.clientID})));
        this._roster.setAttribute('aria-live', 'polite');

        RightNow.UI.show(this._container);
    },

    /**
     * Listener for participant leaving the engagement.
     * @param type string Event name
     * @param args object Event arguments
    */
    _onChatEngagementParticipantRemovedResponse: function(type, args)
    {
        if(!args[0].data.agent)
            return;

        var agent = args[0].data.agent;
        var element = this.Y.one(this.baseSelector + '_Agent_' + agent.clientID);

        if(element)
            element.remove();
    },

    /**
     * Listener for Agent Status Change notifications.
     * @param type string Event name
     * @param args object Event arguments
     */
    _onChatAgentStatusChangeResponse: function(type, args)
    {
        if(!args[0].data.agent)
            return;

        var agent = args[0].data.agent;
        var newStatusString = "";

        switch(agent.activityStatus)
        {
            case RightNow.Chat.Model.ChatActivityState.RESPONDING:
                newStatusString = this.data.attrs.label_status_responding;
                break;

            case RightNow.Chat.Model.ChatActivityState.LISTENING:
                newStatusString = this.data.attrs.label_status_listening;
                break;

            case RightNow.Chat.Model.ChatActivityState.ABSENT:
                newStatusString = this.data.attrs.label_status_absent;
                break;
        }

        var statusElement = this.Y.one(this.baseSelector + '_AgentStatus_' + agent.clientID);

        if(statusElement)
        {
            statusElement.setHTML(this.data.attrs.agent_id.replace(/{display_name}/g, agent.name) + "&nbsp;(" + newStatusString + ")");
        }
    },

    /**
     * Handles chat state change. Hides roster if disconnected.
     * @param type string Event name
     * @param args object Event arguments
     */
    _onChatStateChangeResponse: function(type, args)
    {
        if(!args[0].data.currentState)
            return;

        var currentState = args[0].data.currentState;
        var ChatState = RightNow.Chat.Model.ChatState;

        if(currentState === ChatState.CANCELLED || currentState === ChatState.DISCONNECTED
            || currentState === ChatState.REQUEUED)
            RightNow.UI.hide(this._container);
    }
});
