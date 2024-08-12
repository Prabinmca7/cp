RightNow.Widgets.ChatQueueWaitTime = RightNow.Widgets.extend({
    constructor: function(){
        RightNow.Event.subscribe("evt_chatEventBusInitializedResponse", this._initialize, this);
        this._estimatedWaitTimeDisplayed = false;
        this._queueWaitTimeContainer = this.Y.one(this.baseSelector);
        this._queuePositionElement = this.Y.one(this.baseSelector + "_QueuePosition");
        this._estimatedWaitTimeElement = this.Y.one(this.baseSelector + "_EstimatedWaitTime");
        this._averageWaitTimeElement = this.Y.one(this.baseSelector + "_AverageWaitTime");
        this._leaveScreenWarningElement = this.Y.one(this.baseSelector + "_BrowserWarning");
        if(this._queuePositionElement || this._estimatedWaitTimeElement || this._averageWaitTimeElement){
            RightNow.Event.subscribe("evt_chatStateChangeResponse", this._onChatStateChangeResponse, this);
            RightNow.Event.subscribe("evt_chatQueuePositionNotificationResponse", this._onChatQueuePositionNotificationResponse, this);
        }
        RightNow.Event.subscribe("evt_chatReconnectUpdateResponse", this._reconnectUpdateResponse, this);
        this._displayQueuePosition = this._queuePositionElement && (this.data.attrs.type === 'position' || this.data.attrs.type === 'all');
        this._displayEstimatedWaitTime = this._estimatedWaitTimeElement && (this.data.attrs.type === 'estimated' || this.data.attrs.type === 'all');
        this._displayAverageWaitTime = this._averageWaitTimeElement && (this.data.attrs.type === 'average' || this.data.attrs.type === 'all');

        // Fix for race condition where the widgets load before UI bus
        if(RightNow.Chat && RightNow.Chat.UI && RightNow.Chat.UI.EventBus !== null && RightNow.Chat.UI.EventBus.isEventBusInitialized !== undefined && RightNow.Chat.UI.EventBus.isEventBusInitialized())
        {
            this._initialize();
        }
    },

    _initialize: function()
    {
        var uiUtils = RightNow.Chat.UI.Util;
        this._queuePositionMsg = uiUtils.doPositionAndWaitTimeVariableSubstitution(this.data.attrs.label_queue_position, this.instanceID + "_QueuePosition", RightNow.Interface.getConfig("ESTIMATED_WAIT_TIME_SAMPLES", "RNL"));
        this._estimatedWaitTimeMsg = uiUtils.doPositionAndWaitTimeVariableSubstitution(this.data.attrs.label_estimated_wait_time, this.instanceID + "_EstimatedWaitTime", RightNow.Interface.getConfig("ESTIMATED_WAIT_TIME_SAMPLES", "RNL"));
        this._averageWaitTimeMsg = uiUtils.doPositionAndWaitTimeVariableSubstitution(this.data.attrs.label_average_wait_time, this.instanceID + "_AverageWaitTime", RightNow.Interface.getConfig("ESTIMATED_WAIT_TIME_SAMPLES", "RNL"));

        this._queuePositionElement.setAttribute("aria-live", "polite").setAttribute("aria-atomic", "true");
        this._estimatedWaitTimeElement.setAttribute("aria-live", "polite").setAttribute("aria-atomic", "true");
        this._averageWaitTimeElement.setAttribute("aria-live", "polite").setAttribute("aria-atomic", "true");
        if(uiUtils.hasLeaveScreenIssues())
        {
            RightNow.UI.show(this._leaveScreenWarningElement);
        }
    },

    /**
    * Listener for Chat State Change notifications.
    * @param type string Event name
    * @param args object Event arguments
     */
    _onChatStateChangeResponse: function(type, args)
    {
        //show or hide the widget
        if(args[0].data.currentState === RightNow.Chat.Model.ChatState.SEARCHING ||
           args[0].data.currentState === RightNow.Chat.Model.ChatState.REQUEUED)
        {
            this._estimatedWaitTimeDisplayed = false;

            //display queue postion element
            if(this._displayQueuePosition)
            {
                this._queuePositionElement.set('innerHTML', this.data.attrs.label_queue_position_not_available);
                RightNow.UI.show(this._queuePositionElement);
            }

            //display estimated wait time element
            if(this._displayEstimatedWaitTime)
            {
                this._estimatedWaitTimeElement.set('innerHTML', this.data.attrs.label_estimated_wait_time_not_available);
                RightNow.UI.show(this._estimatedWaitTimeElement);
            }

            //display average wait time element
            if(this._displayAverageWaitTime)
            {
                this._averageWaitTimeElement.set('innerHTML', this.data.attrs.label_average_wait_time_not_available);
                RightNow.UI.show(this._averageWaitTimeElement);
            }

            //finally display the container
            RightNow.UI.show(this._queueWaitTimeContainer);
        }
        else if(args[0].data.currentState === RightNow.Chat.Model.ChatState.RECONNECTING)
        {
            return;
        }
        else
        {
            //just hide the container
            RightNow.UI.hide(this._queueWaitTimeContainer);
        }
    },

    /**
    * Listener for Chat Queue Position Notifications.
    * @param type string Event name
    * @param args object Event arguments
     */
    _onChatQueuePositionNotificationResponse: function(type, args){
        this._updateQueuePosition(args[0].data.position);
        this._updateEstimatedWaitTime(args[0].data.expectedWaitSeconds);
        this._updateAverageWaitTime(args[0].data.averageWaitSeconds);
    },

    /**
     * Updates the Queue position Element
    * @param position integer Queue position
     */
    _updateQueuePosition: function(position)
    {
        if(this._displayQueuePosition)
        {
            this._updateQueuePositionMessage(position);
            this._updateQueuePositionValue(position);
        }
    },

    /**
     * Updates the Queue Position Element's Message
    * @param position integer Queue position
     */
    _updateQueuePositionMessage: function(position)
    {
        this._queuePositionElement.set('innerHTML', position > 0 ? this._queuePositionMsg : this.data.attrs.label_queue_position_not_available);
    },

    /**
     * Updates the queue position value
    * @param position integer Queue position
     */
    _updateQueuePositionValue: function(position)
    {
        var queuePositionValueElem = this.Y.one(this.baseSelector + "_QueuePosition_QueuePosition");
        if(!queuePositionValueElem)
            return;

        queuePositionValueElem.set('innerHTML', position > 0 ? position : "");
    },

    /**
     * Updates the estimated wait time information if present
    * @param estimatedWaitSeconds integer Estimated wait in seconds
     */
    _updateEstimatedWaitTime: function(estimatedWaitSeconds)
    {
        if(this._displayEstimatedWaitTime)
        {
            this._updateEstimatedWaitTimeMessage(estimatedWaitSeconds);
            this._updateEstimatedWaitTimeValue(estimatedWaitSeconds);
        }
    },

    /**
     * Updates the Estimated Wait Time Element's Message
    * @param estimatedWaitSeconds integer Estimated wait in seconds
     */
    _updateEstimatedWaitTimeMessage: function(estimatedWaitSeconds)
    {
        if(estimatedWaitSeconds > 0)
        {
            this._estimatedWaitTimeDisplayed = true;
            this._estimatedWaitTimeElement.set('innerHTML', this._estimatedWaitTimeMsg);
        }
        else
        {
            this._estimatedWaitTimeElement.set('innerHTML', (estimatedWaitSeconds == 0 && !this._estimatedWaitTimeDisplayed ? this.data.attrs.label_estimated_wait_time_not_available : this.data.attrs.label_estimated_wait_time_exceeded));
        }
    },

    /**
     * Updates the estimated wait time value
    * @param estimatedWaitSeconds integer Estimated wait in seconds
     */
    _updateEstimatedWaitTimeValue: function(estimatedWaitSeconds)
    {
        var estimatedWaitTimeValueElem = this.Y.one(this.baseSelector + "_EstimatedWaitTime_EstimatedWaitTime");
        if(!estimatedWaitTimeValueElem)
            return;

        estimatedWaitTimeValueElem.set('innerHTML', estimatedWaitSeconds > 0 ? RightNow.Chat.UI.Util.toIso8601Time(estimatedWaitSeconds) : "");
    },

    /**
     * Updates the average wait time element
    * @param averageWaitSeconds integer Average wait in seconds
     */
    _updateAverageWaitTime: function(averageWaitSeconds)
    {
        if(this._displayAverageWaitTime)
        {
            this._updateAverageWaitTimeMessage(averageWaitSeconds);
            this._updateAverageWaitTimeValue(averageWaitSeconds);
        }
    },

    /**
     * Updates the average wait time element's message
    * @param averageWaitSeconds integer Average wait in seconds
     */
    _updateAverageWaitTimeMessage: function(averageWaitSeconds)
    {
        this._averageWaitTimeElement.set('innerHTML', averageWaitSeconds > 0 ? this._averageWaitTimeMsg : this.data.attrs.label_average_wait_time_not_available);
    },

    /**
     * Updates the average wait time value
    * @param averageWaitSeconds integer Average wait in seconds
     */
    _updateAverageWaitTimeValue: function(averageWaitSeconds)
    {
        var averageWaitTimeValueElem = this.Y.one(this.baseSelector + "_AverageWaitTime_AverageWaitTime");
        if(!averageWaitTimeValueElem)
            return;

        averageWaitTimeValueElem.set('innerHTML', averageWaitSeconds > 0 ? RightNow.Chat.UI.Util.toIso8601Time(averageWaitSeconds) : "");
    },

    /**
     * Listener for network disconnect notifications
    * @param type string Event name
	* @param args object Event arguments
     */
    _reconnectUpdateResponse: function(type, args)
    {
        if(this._displayQueuePosition)
        {
            this._queuePositionElement.set('innerHTML', RightNow.Interface.getMessage("COMM_RN_LIVE_SERV_LOST_PLS_WAIT_MSG") + " " + RightNow.Interface.getMessage("DISCONNECTION_IN_0_SECONDS_MSG").replace("{0}", args[0].data.secondsLeft));
            RightNow.UI.show(this._queuePositionElement);
        }
    }
});
