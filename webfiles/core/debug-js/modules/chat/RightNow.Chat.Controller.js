RightNow.namespace("RightNow.Chat.Controller");
/**
 * Defines the ChatCommunicationsController class and all of its helper functions
 * @namespace
 */
RightNow.Chat.Controller = RightNow.Chat.Controller || {};
/**
 * @constructor
 */
RightNow.Chat.Controller.ChatCommunicationsController = function(Y){
    this.Y = Y;
    this._absentIntervalMS = 120000;
    this._activitySignalResponseCheckInterval = 30000;
    this._activitySignalResponseLastCheckTime = Date.now();
    this._agents = RightNow.Chat.Model.Agents;
    this._beginProcessingTimestamp = null;
    this._cancelledSurveyID = 0;
    this._completedSurveyID = 0;
    this._cancelledSurveyAuth = null;
    this._completedSurveyAuth = null;
    this._chatCommunicator = null;
    this._connected = false;
    this._currentState = null;
    this._currentlySendingMessage = false;
    this._endUser = RightNow.Chat.Model.EndUser;
    this._engagementID = -1;
    this._accountID = -1;
    this._fetchUpdateFailures = 0;
    this._firstParticipant = true;
    this._postMessageResponseTimer = null;
    this._postMessageRetryCount = 0;
    this._lastPostedMessageID = -1;
    this._logonResponseTimer = null;
    this._maxAbsentIntervals = 2;
    this._myClientID = null;
    this._currentAbsentInterval = null;
    this._reconnectTimer = null;
    this._responseSentMilliseconds = null;
    this._retryReconnect = true;
    this._sneakPreviewFocus = false;
    this._sneakPreviewState = RightNow.Chat.Model.ChatSneakPreviewState.DISABLED;
    this._sneakPreviewInterval = 500;
    this._sneakPreviewLastSentTime = Date.now();
    this._stateBeforeReconnect = null;
    this._statusTimerId = 0;
    this._searchPerformed = false;
    this._timeUpdateRequested = null;
    this._messageSendQueue = [];
    this._xmlDateRE = new RegExp(/^([0-9]{4})-([0-9]{2})-([0-9]{2})T([0-9]{2}):([0-9]{2}):([0-9]{2})(\.[0-9]+)?.*$/);
    this._navigationType = null;
    this._exceptionCodeMap = {
        NO_AGENTS_AVAIL: 'FAIL_NO_AGENTS_AVAIL',
        OUT_OF_HOURS: 'FAIL_OUT_OF_HOURS',
        HOLIDAY: 'FAIL_HOLIDAY',
        SERVER_ERROR: 'FAIL_SERVER_ERROR',
        RULE_DEQUEUE: 'FAIL_RULE_DEQUEUE'
    }
    this._startingSequenceNumber = null;

    if(window.oit && window.oit.allInlaysAreLoaded()) {
        this._onOITLoaded();
    } else {
        document.addEventListener('inlay-oracle-chat-video-loaded', this._onOITLoaded.bind(this));
    }
};

RightNow.Chat.Controller.ChatCommunicationsController.prototype = {
    /**
     * Initializes Communications
     */
    initializeCommunications: function()
    {
        var Chat = RightNow.Chat;
        Chat.Communicator = new Chat.Communicator(this.Y);
        this._chatCommunicator = Chat.Communicator;
    },

    _onOITLoaded: function() {
        this.videoInlayLoaded = true;
    },

    /**
     * Sets Connection Parameters
     * @param {Object} connectionData Connection properties
     */
    setConnectionParameters: function(connectionData)
    {
        if(connectionData.absentInterval){
            this._absentIntervalMS = connectionData.absentInterval * 1000;
        }
        if(connectionData.absentRetryCount){
            this._maxAbsentIntervals = connectionData.absentRetryCount;
        }

        this._chatCommunicator.initialize(connectionData);
    },

    /**
     * Sets the chat state
     * @param {number?} state Chat state code
     * @param {string=} reason Reason description
     */
    setState: function(state, reason)
    {
        if(this._currentState == RightNow.Chat.Model.ChatState.CANCELLED
           || this._currentState == RightNow.Chat.Model.ChatState.DISCONNECTED
           || this._currentState == RightNow.Chat.Model.ChatState.DEQUEUED)
            return;

        var eventObject = new RightNow.Event.EventObject();

        eventObject.data.previousState = this._currentState;
        eventObject.data.currentState = this._currentState = state;
        eventObject.data.reason = reason;
        RightNow.Event.fire("evt_chatStateChange", eventObject);
    },

    /**
     * Sets navigation type when user navigates or refreshes a page
     * @param {string} type of navigation
     */
     setNavigationType: function(type)
     {
         this._navigationType = type;
         return;
     },

    /**
     * Returns true if connected
     * @return {boolean}
     */
    isConnected: function()
    {
        return this._connected;
    },

    /**
     * Returns the chat state
     * @return {number|null}
     */
    getState: function()
    {
        return this._currentState;
    },

    /**
     * Sets the agent activity status
     * @param {number} clientID
     * @param {number} activityStatus
     */
    setAgentActivityStatus: function(clientID, activityStatus)
    {
        var eventObject = new RightNow.Event.EventObject();

        if(this._agents.getAgent(clientID) == null)
            return;

        eventObject.data.previousState = this._agents.getAgent(clientID).activityStatus;
        this._agents.setActivityStatus(clientID, activityStatus);

        eventObject.data.agent = this._agents.getAgent(clientID);
        RightNow.Event.fire("evt_chatAgentStatusChange", eventObject);
    },

    /**
     * Sets the end user activity status
     * @param {number} activityState
     * @param {string=} sneakPreview
     */
    setEndUserActivityStatus: function(activityState, sneakPreview)
    {
        this._endUser.activityStatus = activityState;

        var requestConfig = {
            data: {
                action: RightNow.Chat.Model.ChatEndUserAction.ACTIVITY_STATUS,
                mode: activityState,
                sneakPreview: sneakPreview,
                sneakPreviewState: this._sneakPreviewState,
                sneakPreviewFocus: this._sneakPreviewFocus
            }
        };

        if ((Date.now() - this._activitySignalResponseLastCheckTime) >= this._activitySignalResponseCheckInterval) {
            requestConfig.on = {
                success: this.onActivitySignalChangeSuccess,
                failure: this.onActivitySignalChangeFailure
            };

            requestConfig.scope = this;
            this._activitySignalResponseLastCheckTime = Date.now();
        }

        this._chatCommunicator.makeRequest(requestConfig);
    },

    /**
     * Handler for chat post message key up event
     * @param {Event} keyEvent
     * @param {string} inputValue
     */
    handleChatPostMessageKeyUp: function(keyEvent, inputValue)
    {
        var ActivityState = RightNow.Chat.Model.ChatActivityState;
        var SneakPreviewState = RightNow.Chat.Model.ChatSneakPreviewState;
        var sneakPreviewEnabledAndInFocus = (this._sneakPreviewState === SneakPreviewState.ENABLED) && this._sneakPreviewFocus;

        clearTimeout(this._statusTimerId);

        if(keyEvent && keyEvent.keyCode === 13)
            return;

        if(inputValue === "" && this._endUser.activityStatus !== ActivityState.LISTENING)
            this.setEndUserActivityStatus(ActivityState.LISTENING);
        else if(!sneakPreviewEnabledAndInFocus && inputValue !== "" && this._endUser.activityStatus !== ActivityState.RESPONDING)
            this.setEndUserActivityStatus(ActivityState.RESPONDING);
        else if(sneakPreviewEnabledAndInFocus && inputValue !== null && inputValue.length >= 0)
            this.sendSneakPreview(inputValue);

        if(this._endUser.activityStatus === ActivityState.RESPONDING) {
            if (sneakPreviewEnabledAndInFocus && inputValue) {
                inputValue = inputValue.replace(/\n/g, '\\n');

                this._statusTimerId = setTimeout(
                    'RightNow.Chat.Controller.ChatCommunicationsController.setEndUserActivityStatus("' + ActivityState.LISTENING + '", "' + inputValue + '")',
                    this._sneakPreviewInterval);
            } else {
                this._statusTimerId = setTimeout('RightNow.Chat.Controller.ChatCommunicationsController.setEndUserActivityStatus("' + ActivityState.LISTENING + '")', 5000);
            }
        }
    },

    /**
     * Send sneak preview if enabled
     * @param {string} inputValue the sneak preview text
     */
    sendSneakPreview: function(inputValue)
    {
        if (inputValue.length === 1) {
            this.setEndUserActivityStatus(RightNow.Chat.Model.ChatActivityState.RESPONDING);
            this._sneakPreviewLastSentTime = Date.now();
        }

        if ((Date.now() - this._sneakPreviewLastSentTime) >= this._sneakPreviewInterval) {
            this.setEndUserActivityStatus(RightNow.Chat.Model.ChatActivityState.RESPONDING, inputValue);
            this._sneakPreviewLastSentTime = Date.now();
        }
    },

    /**
     * Returns the request engagement result code corresponding to a REST API exception code
     * @param {string} exceptionCode REST API exception code
     * @return {string}
     */
    _getResultCodeFromExceptionCode: function(exceptionCode)
    {
        return (exceptionCode in this._exceptionCodeMap) ? this._exceptionCodeMap[exceptionCode] : exceptionCode;
    },

    /**
    * This defines the Callback for a successful LOGON operation
    * Fires RightNow.Chat.Events.evt_chatConnectUpdate with event data indicating
    * it is connected to the Server.
    * If response data contains chatMessageType:
    * ChatCreateEngagementResult, and resultCode is in {FAIL_OUT_OF_HOURS,
    * FAIL_HOLIDAY, FAIL_NO_AGENTS_AVAIL, FAIL_RULE_DEQUEUE}, calls
    * setStateByReasonCode() with reasonCode set to resultCode.
    * Else, sets the state of the Chat to SEARCHING Fires the
    * RightNow.Chat.Events.evt_chatStateChange
    * @param {Object} response JSON data returned from the chat server
    */
    onLogonSuccess: function(response)
    {
        if (response.responses && response.responses[0]) {
            // legacy logon success
            response = response.responses[0];
        } else {
            // convert the new-style response into the old style for REST requests
            response = {
                clientId: response.clientId,
                serviceStartTime: response.serviceStartTime,
                serviceFinishTime: response.serviceFinishTime,
                sessionId: response.sessionId,
                chatCreateEngagementResult: {
                    cancelledSurveyId: response.cancelledSurveyId,
                    cancelledSurveyAuth: response.cancelledSurveyAuth,
                    completedSurveyId: response.completedSurveyId,
                    completedSurveyAuth: response.completedSurveyAuth,
                    sneakPreviewInterval: response.sneakPreviewInterval,
                    engagementId: response.engagementId,
                    sneakPreviewState: {
                        value: response.sneakPreviewState
                    },
                    resultCode: {
                        value: response.resultType || this._getResultCodeFromExceptionCode(response.exceptionCode)
                    }
                }
            };
        }

        clearTimeout(this._logonResponseTimer);
        var result = null;

        if (response) {
            console.log(JSON.stringify(response.chatCreateEngagementResult));
            result = response.chatCreateEngagementResult;
        }

        if(result)
        {
            var resultCode = result.resultCode.value;

            this._chatCommunicator.setJavaSessionID(response.sessionId);
            this._myClientID = response.clientId;
            this._endUser.servletSessionID = response.sessionId;
            this._engagementID = result.engagementId;
            this._sneakPreviewState = result.sneakPreviewState.value;
            this._sneakPreviewInterval = result.sneakPreviewInterval;
            
            if(this._sneakPreviewState === RightNow.Chat.Model.ChatSneakPreviewState.ENABLED){
                this._sneakPreviewFocus = true;
            }

            if(response.sessionId)
            {
                // Set local cookie in case we need to resume chat later. Set cookie to expire 2 hours from now.
                this.Y.Cookie.set("CHAT_SESSION_ID", response.sessionId, { path: "/", expires: new Date(new Date().getTime() + 7200000), secure: true });
            }

            // Check for survey data; if not 0, launch param specifically stating survey. Don't overwrite.
            if(this._cancelledSurveyID === 0 && result.cancelledSurveyId)
            {
                this._cancelledSurveyID = result.cancelledSurveyId;
                this._cancelledSurveyAuth = result.cancelledSurveyAuth;
            }

            if(this._completedSurveyID === 0 && result.completedSurveyId)
            {
                this._completedSurveyID = result.completedSurveyId;
                this._completedSurveyAuth = result.completedSurveyAuth;
            }
        }

        //notify liseners that we are connected
        var eventObject = new RightNow.Event.EventObject();
        eventObject.data.connected = true;

        if(resultCode == 'EXISTING_SESSION')
            eventObject.data.existingSession = true;

        RightNow.Event.fire("evt_chatConnectUpdate", eventObject);

        if(eventObject.data.existingSession)
            return;

        if(resultCode === 'FAIL_OUT_OF_HOURS' || resultCode === 'FAIL_HOLIDAY' || resultCode === 'FAIL_NO_AGENTS_AVAIL' || resultCode === 'FAIL_RULE_DEQUEUE' || result == null)
        {
            this.setStateByReasonCode(resultCode);
        }
        else
        {
            this._connected = true;
            this.setState(RightNow.Chat.Model.ChatState.SEARCHING);
        }
    },

    /**
    * This defines the Callback for a failed LOGON operation.
    * Fires the RightNow.Chat.Events.evt_chatConnectUpdate with event data
    * indicating that it wasn’t able to connect to the server
    * @param {Object} o
    * @param {Object} evData JSON data returned from the chat server
    */
    onLogonFailure: function(o, evData)
    {
        var eventObject = new RightNow.Event.EventObject();
        eventObject.data.connected = false;
        if(evData && evData.response) {
            eventObject.data.response = JSON.parse(evData.response);
            if(eventObject.data.response.hasOwnProperty('exceptionCode')) {
                var resultCode = this._getResultCodeFromExceptionCode(eventObject.data.response.exceptionCode);
                if(resultCode === 'FAIL_OUT_OF_HOURS' || resultCode === 'FAIL_HOLIDAY' || resultCode === 'FAIL_NO_AGENTS_AVAIL' || resultCode === 'FAIL_RULE_DEQUEUE')
                {
                    this.setStateByReasonCode(resultCode);
                }
            }
            RightNow.Event.fire("evt_chatConnectUpdate", eventObject);
        } else {
            console.error('Response data is empty, possible data connection loss.');
            this.setStateByReasonCode('PARTICIPANT_LEFT');
            RightNow.Event.fire("evt_chatConnectUpdate", eventObject);
        }
    },

    /**
    * Makes a request to the ChatCommunicationsModel with
    * EndUserAction = LOGON. Also provides the callbacks.
    * @param {Object} eventObject
    */
    logon: function(eventObject){
        // Block connect attempt if coming from IE7; unsupported browser
        // IE8 is no longer supported starting 14.5+
        if(this.Y.UA.ie > 0 && this.Y.UA.ie <= 8)
            return;

        // Block connection attempts by spiders
        if(eventObject.data.isSpider)
            return;

        var data = eventObject.data;
        // Set the local enduser object. This request is the first that the communication controller class is exposed to this data.
        this._endUser.firstName = data.firstName;
        this._endUser.lastName = data.lastName;
        this._endUser.email = data.email;

        var requestConfig = {
            data: {
                action: RightNow.Chat.Model.ChatEndUserAction.LOGON,
                first_name: data.firstName,
                last_name: data.lastName,
                email: data.email,
                c_id: data.contactID,
                question: data.subject,
                org_id: data.organizationID,
                request_src: data.requestSource,
                prod_id: data.prod,
                cat_id: data.cat,
                survey_send_id: data.surveySendID,
                survey_send_delay: data.surveySendDelay,
                survey_send_auth: data.surveySendAuth,
                queue_id: data.queueID,
                intf_id: data.interfaceID,
                s_id: data.sessionID,
                i_id: data.incidentID,
                routing_data: data.routingData,
                referrer_url: data.referrerUrl,
                coBrowsePremiumSupported: data.coBrowsePremiumSupported,
                vaVersion: 1.0, // This may only be incremented on minor or major CP bumps
                mediaList: data.mediaList // list of mediatypes and there support information
            },
            on: {
                success: this.onLogonSuccess,
                failure: this.onLogonFailure
            },
            scope: this
        };

        if(data.resume != null)
            requestConfig.data.resume = data.resume ? RightNow.Chat.Model.ChatCreateEngagementResumeCode.RESUME : RightNow.Chat.Model.ChatCreateEngagementResumeCode.DO_NOT_RESUME;
        //media type
        for(var media in data.mediaList)
           requestConfig.data[data.mediaList[media].type] = data.mediaList[media];
        // Custom fields.
        for(var dataField in data.miscellaneousData)
            requestConfig.data[dataField] = data.miscellaneousData[dataField];

        this._chatCommunicator.makeRequest(requestConfig);
    },

    /**
    * Makes a request to initiate a chat using the Chat REST API
    * @param {Object} eventObject
    */
    logonRest: function(eventObject){
        // Block connect attempt if coming from IE7; unsupported browser
        // IE8 is no longer supported starting 14.5+
        if(this.Y.UA.ie > 0 && this.Y.UA.ie <= 8)
            return;

        // Block connection attempts by spiders
        if(eventObject.data.isSpider)
            return;

        var data = eventObject.data;

        // Set the local enduser object. This request is the first that the communication controller class is exposed to this data.
        this._endUser.firstName = data.firstName;
        this._endUser.lastName = data.lastName;
        this._endUser.email = data.email;

        var requestConfig = {
            data: {
                pool: data.pool
            },
            headers: {
                Authorization: "Bearer " + data.jwt,
                Accept: 'application/json; charset=utf-8',
                'Content-Type': 'application/json; charset=utf-8'
            },
            on: {
                success: this.onLogonSuccess,
                failure: this.onLogonFailure
            },
            scope: this
        };

        // make the REST request
        this._chatCommunicator.makeRequest(requestConfig, true);
    },

    /**
     * Sets the cancelled survey ID
     * @param {number} surveyID
     */
    setCancelledSurveyID: function(surveyID)
    {
        this._cancelledSurveyID = surveyID;
    },

    /**
     * Sets the cancelled survey auth
     * @param {string} surveyAuth
     */
    setCancelledSurveyAuth: function(surveyAuth)
    {
        this._cancelledSurveyAuth = surveyAuth;
    },

    /**
     * Sets the completed survey ID
     * @param {number} surveyID
     */
    setCompletedSurveyID: function(surveyID)
    {
        this._completedSurveyID = surveyID;
    },

    /**
     * Sets the completed survey auth
     * @param {string} surveyAuth
     */
    setCompletedSurveyAuth: function(surveyAuth)
    {
        this._completedSurveyAuth = surveyAuth;
    },

    /**
     * Returns the cancelled survey ID
     * @return {number}
     */
    getCancelledSurveyID: function()
    {
        return this._cancelledSurveyID;
    },

    /**
     * Returns the cancelled survey auth
     * @return {string}
     */
    getCancelledSurveyAuth: function()
    {
        return this._cancelledSurveyAuth;
    },

    /**
     * Returns the completed survey ID
     * @return {number}
     */
    getCompletedSurveyID: function()
    {
        return this._completedSurveyID;
    },

    /**
     * Returns the completed survey auth
     * @return {string}
     */
    getCompletedSurveyAuth: function()
    {
        return this._completedSurveyAuth;
    },

    /**
     * Returns the encoded account ID
     * @return {string}
     */
    getEncodedAccountID: function()
    {
        return RightNow.Text.Encoding.base64Encode(this._accountID.toString());
    },

    /**
     * Returns the enduser
     * @return {string}
     */
    getEndUser: function()
    {
        return this._endUser;
    },

    /**
     * Returns the engagement ID
     * @return {number}
     */
    getEngagementID: function()
    {
        return this._engagementID;
    },

    /**
     * Sets the search performed
     * @param {string} searched
     */
    setSearchPerformed: function(searched)
    {
        this._searchPerformed = searched;
    },

    /**
    * If reasonCode is {FAIL_OUT_OF_HOURS, FAIL_HOLIDAY, FAIL_NO_AGENTS_AVAI,
    * END_USER_CANCELLED}, sets the State of the Chat to CANCELLED
    * If reasonCode is FAIL_RULE_DEQUEUE, sets the State of the Chat to DEQUEUED
    * If reasonCode is END_USER_DISCONNECTED, set the State of the Chat to DISCONNECTED
    * Fires the events RightNow.Chat.Events.evt_chatStateChange
    * @param {string} reasonCode
    */
    setStateByReasonCode: function(reasonCode){
        var newState = RightNow.Chat.Model.ChatState.DISCONNECTED;
        if(reasonCode === 'FAIL_OUT_OF_HOURS' ||
           reasonCode === 'FAIL_HOLIDAY' ||
           reasonCode === 'FAIL_NO_AGENTS_AVAIL' ||
           reasonCode === 'ENDED_USER_CANCEL' ||
           reasonCode === 'QUEUE_TIMEOUT' ||
           reasonCode === 'ENDED_USER_DEFLECTED')
            newState = RightNow.Chat.Model.ChatState.CANCELLED;
        else if(reasonCode === 'FAIL_RULE_DEQUEUE' || reasonCode === 'EJECTED')
            newState = RightNow.Chat.Model.ChatState.DEQUEUED;
        else if(reasonCode == 'TRANSFERRED_TO_QUEUE')
            newState = RightNow.Chat.Model.ChatState.REQUEUED;

        this._firstParticipant = true;
        this.setState(newState, reasonCode);
    },

    /**
    * Aborts any outstanding GETUPDATE requests
    * Makes a request to the ChatCommunicationsModel with EndUserAction = LOGOFF.
    * Calls setStateByReasonCode()
    * @param {boolean} isFromBrowserClose
    * @param {boolean} isCancelled
    */
    logoff: function(isFromBrowserClose, isCancelled)
    {
        var reason;

        if(isCancelled)
        {
            if(this._searchPerformed)
            {
                reason = RightNow.Chat.Model.ChatConclusionReason.ENDED_USER_DEFLECTED;
            }
            else
            {
                reason = RightNow.Chat.Model.ChatConclusionReason.ENDED_USER_CANCEL;
            }
        }

        var requestConfig = {
            data: {
                action: RightNow.Chat.Model.ChatEndUserAction.LOGOFF,
                reason: reason
            },
            on: {
                success: this.onLogoffSuccess
            },
            scope: this
        };

        if(isFromBrowserClose)
        {
            requestConfig.synchronous = true;
        }

        // Make sure to end an active video chat before sending the logoff message
        var vcElement = document.getElementById('videoSectionWrapper');
        if(vcElement) {
            if(!this.videoInlayLoaded) {
                var videoChatRequestConfig = new RightNow.Event.EventObject(this, {data: {videoChatStatus: RightNow.Chat.Model.ChatVideoChatStatusCode.STOPPED}});
                RightNow.Event.fire('evt_chatVideoChatStatusResponse', videoChatRequestConfig);
            } else if(window.oit) {
                window.oit.fire(new CustomEvent('inlay-oracle-chat-video-statusNotification', { detail: { videoChatStatus: RightNow.Chat.Model.ChatVideoChatStatusCode.STOPPED, clientResponsible: true, logoff: true } }));
            }
        }

        this._chatCommunicator.makeRequest(requestConfig);

        this.setStateByReasonCode(reason);
    },

    /**
     * Handler called after a successful log off
     * @param {Object} response JSON data returned from the chat server
     */
    onLogoffSuccess: function(response)
    {
        this._connected = false;
        if(this.Y.Cookie.get("CHAT_SESSION_ID") === this._endUser.servletSessionID){
            this.Y.Cookie.remove("CHAT_SESSION_ID", {path: "/"});
        }
        RightNow.Event.fire('evt_endChatSession', new RightNow.Event.EventObject(this, {}));
    },

    /**
    * Makes a request to the ChatCommunicationsController with EndUserAction = GETUPDATE
    */
    fetchUpdate: function()
    {
        var scope = RightNow.Chat.Controller.ChatCommunicationsController; // scope correction needed for when called from timeout

        if(this._currentState == RightNow.Chat.Model.ChatState.CANCELLED ||
           this._currentState == RightNow.Chat.Model.ChatState.DEQUEUED ||
           this._currentState == RightNow.Chat.Model.ChatState.DISCONNECTED)
                return;

        var requestConfig = {
            data: {
                action: RightNow.Chat.Model.ChatEndUserAction.GETUPDATE
            },
            on: {
                success: this.onFetchUpdate,
                failure: scope.onFetchUpdateFailure
            },
            scope: this,
            useTransactionID: true
        };

        if(this._fetchUpdateFailures > 0)
            requestConfig.data.useContinuation = false;

        if(this._responseSentMilliseconds)
            requestConfig.data.lastGetRequestMilliseconds = this._responseSentMilliseconds - (new Date().getTime() - this._beginProcessingTimestamp);

        if(this._startingSequenceNumber)
            requestConfig.data.startingSequenceNumber = this._startingSequenceNumber;

        this.logDebug('Fetching update from chat service...');
        this._timeUpdateRequested = new Date().getTime();
        this._chatCommunicator.makeRequest(requestConfig);
    },

    /**
    * Takes the response from a get request and handles all messages within the response.
    * @param {Object} response JSON data returned from the chat server
    */
    onFetchUpdate: function(response)
    {
        var responses = response.responses;
        this._beginProcessingTimestamp = new Date().getTime();
        this.logDebug('Update fetched from chat service in ' + (this._beginProcessingTimestamp - this._timeUpdateRequested) + ' milliseconds');
        var mostRecentTransaction = false;
        var isRestoringTranscript = false;
        var videoChatInProgress = false;

        // If we've gotten a valid fetch update result after having had one or more failures
        // on previous fetch updates then we need to clear the slate.
        if(this._fetchUpdateFailures > 0)
        {
            // If the reconnect timer is running then we want to cancel it.
            if(this._reconnectTimer)
            {
                clearInterval(this._reconnectTimer);
                this._reconnectTimer = null;
            }
            // Just in case we've already determined we're beyond the reconnect interval, reset since the chat
            // server has just returned a valid response
            this._retryReconnect = true;
            // Reset the failure count.
            this._fetchUpdateFailures = 0;

            // If the result isn't an error then reset the state to its pre-reconnect value.
            if(!this.containsError(responses))
            {
                this.setState(this._stateBeforeReconnect);
            }

            // Check for messages that should have been posted while reconnecting and send them.
            if((this._messageSendQueue.length - 1) > this._lastPostedMessageID)
                this.postMessageHelper(this._lastPostedMessageID + 1);
        }

        if (responses && responses.length > 0)
        {
            // Iterate the responses. Currently, there would only be one response that is a container for
            // any of the messages that are being returned.
            for(var i = 0; i < responses.length; i++)
            {
                // Check to see if the response is for the most recent get request. If it is, flag this as
                // the most recent transaction and clear the timer that is watching for lost get requests.
                if(responses[i].sequenceNumber && !mostRecentTransaction)
                {
                    var transactionID = responses[0].sequenceNumber;
                    mostRecentTransaction = this._chatCommunicator.getLastTransactionID() === transactionID;
                }

                // Check to see if the response contains multiple messages while being
                // the first message in the sequence. This means the user is reconnecting and
                // the transcript is being reconstructed.
                isRestoringTranscript = (responses[i].sequenceNumber == 1 && responses[i].getResponseTypes.length > 1);

                // Now if there are items contained within the response, iterate through and handle
                // each item.
                if(responses[i].getResponseTypes)
                {
                    this._responseSentMilliseconds = responses[i].responseSentMilliseconds;

                    for(var x = 0; x < responses[i].getResponseTypes.length; x++)
                    {
                        var responseData = responses[i].getResponseTypes[x];
                        var eventObject = new RightNow.Event.EventObject();
                        var chatMessageType = responseData.chatMessageType;
                        if (responseData.sequenceNumber)
                            this._startingSequenceNumber = Math.max(responseData.sequenceNumber, this._startingSequenceNumber);

                        try {
                            // Attempt to process the individual message. If for some reason there is a failure, we will log it and
                            // attempt to continue.
                            switch(chatMessageType)
                            {
                                // For the queue position updates, just fire off a notification.
                                case 'ChatQueuePositionNotification':
                                    eventObject.data = {
                                        position: responseData.position,
                                        expectedWaitSeconds: responseData.expectedWaitSeconds,
                                        averageWaitSeconds: responseData.averageWaitSeconds
                                    };

                                    RightNow.Event.fire("evt_chatQueuePositionNotification", eventObject);
                                    break;
                                // ChatDisconnectNotification means that a participant has disconnected. It can also indicate
                                // that the chat is over.
                                case 'ChatDisconnectNotification':
                                    var clientId = responseData.clientId;
                                    var reason = responseData.reason.value;
                                    eventObject.data.createdTime = this.createServiceFinishTimestamp(responseData.createdTime);
                                    eventObject.data.reason = reason;
                                    eventObject.data.disconnectClientId = clientId;

                                    /*
                                     * Determine if this is an agent leaving or if this is the chat being ended by the agent or enduser.
                                     *
                                     * Note:
                                     * Chat service only returns clientId of agent for conferee (possibly a bug in server).
                                     * Otherwise always returns clientId of enduser. Therefore, we need to get agent entry for lead on
                                     * AGENT_CONCLUDED and TRANSFERRED_TO_QUEUE. If not client id of enduser and not agent
                                     *  concluded, assume enduser disconnection.
                                     */
                                    if(clientId !== this._myClientID)
                                        eventObject.data.agent = this._agents.getAgent(clientId);
                                    else if(reason === 'AGENT_CONCLUDED' || reason === 'TRANSFERRED_TO_QUEUE')
                                        eventObject.data.agent = this._agents.getAgent(this._agents.getLeadAgentID());
                                    else
                                        eventObject.data.isUserDisconnect = true;

                                    // Fire the notification event.
                                    RightNow.Event.fire("evt_chatDisconnectNotification", eventObject);

                                    // Now set the state based on information received.
                                    if(reason === 'QUEUE_TIMEOUT')
                                    {
                                        this.setStateByReasonCode(reason);
                                        this._connected = false;
                                    }
                                    else if(reason === 'TRANSFERRED_TO_QUEUE' || reason === 'EJECTED')
                                    {
                                        this.setStateByReasonCode(reason);
                                    }
                                    else if(clientId === this._myClientID || reason === 'AGENT_CONCLUDED' || reason === 'NO_AGENTS_AVAILABLE')
                                    {
                                        this.setState(RightNow.Chat.Model.ChatState.DISCONNECTED, reason);
                                        this._connected = false;
                                        // Compare the Cookie value(CHAT_SESSION_ID) with the session id (servletSessionID) for the this chat
                                        // to ensure that Cookie value for (CHAT_SESSION_ID) is not removed inadertently when another chat is
                                        // launched and the user chooses to not resume the existing chat session.
                                        // Seee QA #180214-000034 for more information.
                                        if(this.Y.Cookie.get("CHAT_SESSION_ID") === this._endUser.servletSessionID){
                                            this.Y.Cookie.remove("CHAT_SESSION_ID", {path: "/"});
                                        }
                                        RightNow.Event.fire('evt_endChatSession', new RightNow.Event.EventObject(this, {}));
                                    }
                                    break;
                                // ChatEngagementParticipantAdded indicates that a new participant has joined the chat. We need to add
                                // the participant to our list and if this is the first participant (besides the current user) switch
                                // to a connected state.
                                case 'ChatEngagementParticipantAdded':
                                    eventObject.data.createdTime = this.createServiceFinishTimestamp(responseData.createdTime);
                                    eventObject.data.role = responseData.role.value;
                                    eventObject.data.agent = this._agents.addAgent(responseData.clientId, responseData.name, responseData.greeting, responseData.role.value === 'LEAD');
                                    eventObject.data.virtualAgent = responseData.virtualAgent;

                                    RightNow.Event.fire("evt_chatEngagementParticipantAdded", eventObject);
                                    if(this._firstParticipant)
                                    {
                                        this._firstParticipant = false;
                                        this.setState(RightNow.Chat.Model.ChatState.CONNECTED);
                                    }
                                    this._accountID = responseData.accountId;
                                    break;
                                // Set a new lead agent.
                                case 'ChatRoleChangeNotification':
                                    this._agents.setLeadAgentID(responseData.leadClientId);
                                    break;
                                // Set the activity status (responding, listening, etc) and sneak preview state and interval.
                                case 'ChatActivitySignal':
                                    this._sneakPreviewState = responseData.sneakPreviewState.value;
                                    this._sneakPreviewInterval = responseData.sneakPreviewInterval;
                                    this.setAgentActivityStatus(responseData.clientId, responseData.mode.value);
                                    this._sneakPreviewFocus = responseData.sneakPreviewFocus;
                                    break;
                                // Handle a new message post from a participant.
                                case 'ChatPostMessage':
                                    var senderId = responseData.senderId;
                                    eventObject.data.createdTime = this.createServiceFinishTimestamp(responseData.createdTime);
                                    eventObject.data.messageBody = responseData.body;
                                    eventObject.data.isOffTheRecord = responseData.offTheRecord;
                                    eventObject.data.serviceFinishTime = this.createServiceFinishTimestamp(responses[i].serviceFinishTime);

                                    if(responseData.senderType.value === 'AGENT')
                                    {
                                        this.setAgentActivityStatus(senderId, RightNow.Chat.Model.ChatActivityState.LISTENING);
                                        eventObject.data.agent = this._agents.getAgent(responseData.senderId);
                                        eventObject.data.richText = responseData.richText;

                                        if(responseData.vaResponse)
                                            eventObject.data.vaResponse = this.Y.JSON.parse(responseData.vaResponse);

                                        if (responseData.messageId)
                                            eventObject.data.messageId = responseData.messageId;
                                    }
                                    else
                                    {
                                        eventObject.data.postMessage = false;
                                        eventObject.data.isEndUserPost = true;
                                        eventObject.data.endUser = this._endUser;
                                        eventObject.data.richText = false;
                                    }

                                    RightNow.Event.fire("evt_chatPostMessageRequest", eventObject);
                                    break;
                                // Handle a co-browse inviation from an agent.
                                case 'ChatCoBrowseInvitationMessage':
                                    eventObject.data = {
                                        modeType: responseData.modeType.value,
                                        coBrowseUrl: responseData.url,
                                        agent: this._agents.getAgent(responseData.senderId)
                                    };

                                    RightNow.Event.fire("evt_chatCobrowseInvitation", eventObject);
                                    break;
                                // Handle a ChatCobrowsePremium inviation from an agent.
                                case 'ChatCoBrowsePremiumInvitationMessage':
                                    eventObject.data = {
                                        agentEnvironment: responseData.agentEnvironmentString,
                                        coBrowseSessionId: responseData.cobrowseSessionId,
                                        agent: this._agents.getAgent(responseData.senderId)
                                    };

                                    RightNow.Event.fire("evt_chatCoBrowsePremiumInvitation", eventObject);
                                    break;
                                // Handle a co-browse status update. This would indicate that the co-browse session started,
                                // stopped, or had an error.
                                case 'ChatCoBrowseStatusNotification':
                                    eventObject.data.coBrowseStatus = responseData.status.value;
                                    eventObject.data.coBrowseData = responseData.data;

                                    RightNow.Event.fire("evt_chatCobrowseStatusNotification", eventObject);
                                    break;
                                /*****************   Video Chat ********************************/

                                case 'ChatMediaInvitationMessage':
                                    if(responseData.mediaType && responseData.mediaType.value === 'VIDEO'){
                                        eventObject.data = {
                                            videoChatSessionId: responseData.mediaSessionId,
                                            agent: this._agents.getAgent(responseData.senderId),
                                            isRestoringTranscript: isRestoringTranscript,
                                            engagementID: this.getEngagementID()
                                        };
                                        RightNow.Event.fire("evt_chatVideoChatInvitation", eventObject);
                                    }
                                    break;
                                case 'ChatMediaStatusNotification':
                                    if(responseData.mediaType && responseData.mediaType.value === 'VIDEO'){
                                        eventObject.data.videoChatStatus = responseData.status.value;
                                        eventObject.data.videoChatData = responseData.data;
                                        eventObject.data.senderType = responseData.senderType.value;
                                        eventObject.data.isRestoringTranscript = isRestoringTranscript;

                                        if (eventObject.data.videoChatStatus === 'STARTED') {
                                            videoChatInProgress = true;
                                        }
                                        else if (eventObject.data.videoChatStatus === 'STOPPED' || eventObject.data.videoChatStatus === 'ERROR' || eventObject.data.videoChatStatus === 'ABORTED') {
                                            videoChatInProgress = false;
                                        }

                                        RightNow.Event.fire("evt_chatVideoChatStatusNotification", eventObject);
                                    }
                                    break;
                                case 'ChatMediaTokenMessage':
                                    if(responseData.mediaType && responseData.mediaType.value === 'VIDEO'){
                                        responseData.isRestoringTranscript = isRestoringTranscript;

                                        this.notifyVideoJWTAction(responseData);
                                    }
                                    break;
                                /****************    Video Chat ********************************/
                                // ChatEngagementUpdateNotification notifies us that the connectivity status of a participant has
                                // changed.
                                case 'ChatEngagementUpdateNotification':
                                    var ChatParticipantConnectionState = RightNow.Chat.Model.ChatParticipantConnectionState;
                                    var clientID = responseData.clientId;
                                    var connectionState = responseData.connectionState.value;

                                    // If this is a participant going absent, record the participant's status and send out a notification.
                                    if(connectionState === ChatParticipantConnectionState.ABSENT && clientID !== this._myClientID)
                                    {
                                        if(this._agents.getAgent(clientID).activityStatus !== ChatParticipantConnectionState.ABSENT)
                                            this.setAgentActivityStatus(clientID, RightNow.Chat.Model.ChatActivityState.ABSENT);

                                        // If it is the lead agent send out a notification so the time remaining until the user is requeued can be displayed.
                                        if(this._agents.getLeadAgentID() === clientID)
                                        {
                                            eventObject.data.requeueSeconds = responseData.secondsToDisconnect;

                                            RightNow.Event.fire("evt_chatAgentAbsentUpdate", eventObject);
                                        }
                                    }
                                    // If this is a previously absent participant going active again, record the status, send out a
                                    // notification, and reset the absent intervals used in calculating time remaining if this is the
                                    // lead agent.
                                    else if(connectionState === ChatParticipantConnectionState.ACTIVE && clientID !== this._myClientID)
                                    {
                                        this.setAgentActivityStatus(clientID, RightNow.Chat.Model.ChatActivityState.LISTENING);
                                    }
                                    // Fire disconnected event when a CONFREE is forcefully logged out. Confree Disconnected event is fired when the
                                    // agent is not a lead and end user.
                                    else if (connectionState == ChatParticipantConnectionState.DISCONNECTED && clientID !== this._myClientID && clientID !== this._agents.getLeadAgentID())
                                    {
                                        eventObject.data.reason = RightNow.Chat.Model.ChatDisconnectReason.CONFREE_DISCONNECTED;
                                        eventObject.data.disconnectClientId = clientID;

                                        eventObject.data.agent = this._agents.getAgent(clientID);

                                        // Fire the notification event.
                                        RightNow.Event.fire("evt_chatDisconnectNotification", eventObject);
                                    }
                                    break;
                                // Notification that some configuration has changed. Currently just changes the pool id for single
                                // version chat.
                                case 'ChatConfigChangeNotification':
                                    var configName = responseData.name;

                                    if(configName === 'CHAT_CLUSTER_POOL_ID')
                                        this._chatCommunicator.setClusterPoolID(responseData.value);
                                    break;
                                // The Virtual Agent can send some data back to the enduser, such as a pick list.
                                // This data isn't necessarily a response to another request, so it's returned in the GETUPDATE.
                                case 'ChatVaPassthroughMessage':
                                    eventObject.data.content = this.Y.JSON.parse(responseData.content);
                                    RightNow.Event.fire("evt_chatVaPassthroughMessage", eventObject);
                                    break;
                                // Indicator that something bad has happened. Disconnect.
                                case 'ChatSystemError':
                                    this.setState(RightNow.Chat.Model.ChatState.DISCONNECTED, 'ERROR');
                                    this._connected = false;
                                    break;
                            }
                        }
                        catch (ex)
                        {
                            // Log the exception message
                            this.logDebug("Error processing " + chatMessageType + ": " + ex);
                        }
                    }
                }
            }
        }
        else
        {
            // Indicates that something bad has happened. Disconnect.
            this.setState(RightNow.Chat.Model.ChatState.DISCONNECTED, 'ERROR');
            this._connected = false;
        }

        if(videoChatInProgress && isRestoringTranscript) {
            var eo = new RightNow.Event.EventObject(this, {data: {videoChatStatus: RightNow.Chat.Model.ChatVideoChatStatusCode.ABORTED}});
            if(this.videoInlayLoaded && window.oit) {
                window.oit.fire(new CustomEvent('inlay-oracle-chat-video-statusNotification', { detail: { videoChatStatus: RightNow.Chat.Model.ChatVideoChatStatusCode.ABORTED, clientResponsible: true, isRestoringTranscript: true, chatSessionId: this.Y.Cookie.get("CHAT_SESSION_ID"), engagementID: this._engagementID  } }));
            } else {
                RightNow.Event.fire('evt_chatVideoChatStatusResponse', eo);
            }
        }

        if(mostRecentTransaction)
            RightNow.Event.fire("evt_chatFetchUpdateComplete", null);
    },

    /**
     * Handle communication failure/abort for get-loop. Retry until max retry time met.
     * @param {Object} response JSON data returned from the chat server
     */
    onFetchUpdateFailure: function(response)
    {
        this.logDebug('Fetch update failure after ' + (new Date().getTime() - this._timeUpdateRequested) + ' milliseconds');
        var scope = this;
        this._fetchUpdateFailures++;

        // Failed message posts will be resent on connection resume.
        // Cancel the post message response timer so duplicates aren't sent.
        if(this._postMessageResponseTimer)
        {
            clearTimeout(this._postMessageResponseTimer);
            this._postMessageResponseTimer = null;
        }

        // Check to see if we're still within the allowed reconnect time. If we
        // are, start a timer for the absent interval, set the current state if
        // necessary, and fire off a new fetchUpdate. If we aren't, disconnect
        // everything.
        if(this._retryReconnect)
        {
            // If there isn't already a reconnect timer scheduled, schedule it. This should only occur on the first failure (after having
            // had successful connections. After the initial one, the expiration handler should re-set the interval each time.
            if(this._reconnectTimer === null)
            {
                this._reconnectTimer = setInterval(function() {
                        RightNow.Chat.Controller.ChatCommunicationsController.reconnectIntervalExpired.apply(scope);
                    }, this._absentIntervalMS);
            }

            // If this is the first reconnect attempt, set the state to RECONNECTING and store
            // off the current state for when we get re-connected.
            if(this._fetchUpdateFailures === 1)
            {
                this._currentAbsentInterval = 0;
                this._stateBeforeReconnect = this._currentState;
                if(this.Y.Cookie.get("pc_chat_state") && this.Y.Cookie.get('pc_ls_support') == 'y') {
                    if(this._browserState == RightNow.Chat.Model.NavigationType.NAVIGATE_AWAY) {
                       this.setState(RightNow.Chat.Model.ChatState.RECONNECTING);
                    }
                }
                else {
                    this.setState(RightNow.Chat.Model.ChatState.RECONNECTING);
                }
                this.updateReconnectStatus();
            }

            if(response && response.status === -1)
                this.fetchUpdate();
            else
                setTimeout(function() { RightNow.Chat.Controller.ChatCommunicationsController.fetchUpdate.apply(scope); }, 5000);
        }
        else
        {
            this.setState(RightNow.Chat.Model.ChatState.DISCONNECTED, 'RECONNECT_FAILED');
            this._connected = false;
        }
    },

    /**
     * Returns true if response indicates an error.
     * @param {Object} responses JSON data returned from the chat server
     * @return {boolean}
     */
    containsError: function(responses)
    {
        for(var i = 0; i < responses.length; i++)
            if(responses[i].getResponseTypes != null)
                for(var x = 0; x < responses[i].getResponseTypes.length; x++)
                    if(responses[i].getResponseTypes[x].chatMessageType === 'ChatSystemError')
                        return true;

        return false;
    },

    /**
    * Handler for the expiration of a reconnect interval. On the first failure (after successful communications)
    * the reconnect timer will get created with this function. At that point this handler will cause that
    * loop to continually run until a successful response causes a clear timeout on the reconnect timer.
    */
    reconnectIntervalExpired: function()
    {
        this._currentAbsentInterval++;

        // If we're beyond the max intervals then we have to assume the chat server has booted us. Otherwise
        // we need to send out a status update and reset the timer for the next interval.
        if(this._currentAbsentInterval >= this._maxAbsentIntervals)
        {
            this._retryReconnect = false;
            // Make sure we don't have any reconnect timer still scheduled.
            if(this._reconnectTimer)
            {
                clearInterval(this._reconnectTimer);
                this._reconnectTimer = null;
            }
        }
        else
        {
            // Send out the status update.
            RightNow.Chat.Controller.ChatCommunicationsController.updateReconnectStatus();
        }
    },

    /**
    * Sends out an update on the reconnect status. Provides an estimate of the time remaining until the
    * user will be removed from the chat system.
    */
    updateReconnectStatus: function()
    {
        var eventObject = new RightNow.Event.EventObject();
        eventObject.data.secondsLeft = ((this._maxAbsentIntervals - this._currentAbsentInterval) * this._absentIntervalMS) / 1000;
        RightNow.Event.fire("evt_chatReconnectUpdate", eventObject);
    },

    /**
     * Returns true if message is allowed
     * @param {string} messageText
     * @param {boolean} isOffTheRecord
     */
    isMessageAllowed: function(messageText, isOffTheRecord)
    {
        var requestConfig = {
            data: {
                action: RightNow.Chat.Model.ChatEndUserAction.SEND_TEXT,
                msg: messageText,
                offTheRecord: isOffTheRecord
            },
            testAllowed: true
        };

        return this._chatCommunicator.makeRequest(requestConfig);
    },

    /**
     * Adds the end user message post to a Queue
     * Makes a request to the Chat Server to post the message (?action=SEND_TEXT)
     *     At a success response, sends the next message in the queue
     *     At a failure response, attempts to send the same message again
     * @param {string} messageText
     * @param {boolean} isOffTheRecord
     */
    postMessage: function(messageText, isOffTheRecord)
    {
        if(messageText != "")
        {
            var requestConfig = {
                data: {
                    action: RightNow.Chat.Model.ChatEndUserAction.SEND_TEXT,
                    msg: messageText,
                    offTheRecord: isOffTheRecord
                }
            };

            this._messageSendQueue[this._messageSendQueue.length] = requestConfig;

            if(this._currentlySendingMessage === false)
                this.postMessageHelper(this._messageSendQueue.length - 1);
        }

        return this._messageSendQueue.length - 1;
    },

    /**
     * Post message helper function
     * @param {number} id
     */
    postMessageHelper: function(id)
    {
        this._currentlySendingMessage = true;
        var requestConfig = this._messageSendQueue[id];
        var scope = this;

        requestConfig.callbackArgument = id;
        requestConfig.on = {
            failure: scope.onPostMessageFailure,
            success: this.onPostMessageSuccess
        };
        requestConfig.scope = this;

        this._chatCommunicator.makeRequest(requestConfig);

        // Agent assumes LISTENING after chat post sent; set enduser in same state locally
        this._endUser.activityStatus = RightNow.Chat.Model.ChatActivityState.LISTENING;
        clearTimeout(this._statusTimerId);
    },

    /**
     * Adds the out-of-band user data to a Queue
     * Makes a request to the Chat Server to post out-of-band (?action=OUT_OF_BAND_DATA)
     *     Any errors encountered are ignored
     *
     * @param {Object} oobData
     */
    postOutOfBandData: function(oobData)
    {
        if (oobData) {
            var data = ((typeof(oobData) === 'object') && oobData) ? RightNow.JSON.stringify(oobData) : oobData,
                requestConfig = {
                    data: {
                        action: RightNow.Chat.Model.ChatEndUserAction.OUT_OF_BAND_DATA,
                        msg: data
                    }
                };
            this._chatCommunicator.makeRequest(requestConfig);
        }
    },

    /**
     * Handler called after a successful message post
     * @param {Object} response JSON data returned from the chat server
     */
    onPostMessageSuccess: function(response)
    {
        clearTimeout(this._postMessageResponseTimer);
        var messageID = parseInt(response.data, 10);
        this._lastPostedMessageID = messageID;
        this._postMessageRetryCount = 0;

        if(response.error) {
            this.onPostMessageFailure();
            return;
        }

        //Parse the response and see if the reset flag is set to 2. This indicates that the server has received a post
        //when there has been no get loop running. If this condition is detected we will attempt to restart the get
        //loop. As a double sanity check, verify the last get request was over 30 seconds ago since we should never be
        //in this state if it was less than 30 seconds ago.
        if(response.responses && (new Date().getTime() - this._timeUpdateRequested) > 30000)
        {
            var chatPostResponse = response.responses[0];
            if(chatPostResponse && chatPostResponse.chatPostResult && chatPostResponse.chatPostResult.clientReset === 2)
            {
                this.fetchUpdate();
            }
        }

        RightNow.Event.fire("evt_chatPostCompletion", messageID, this.createServiceFinishTimestamp(response.responses[0].serviceFinishTime));

        if(this._messageSendQueue[messageID + 1])
            this.postMessageHelper(messageID + 1);
        else
            this._currentlySendingMessage = false;
    },

    /**
     * Handler called after a failed message post
     */
    onPostMessageFailure: function()
    {
        var scope = this;
        this._postMessageRetryCount++;

        // Retry up to 5 times.
        if(this._postMessageRetryCount <= 5)
        {
            this._postMessageResponseTimer = setTimeout(function() { RightNow.Chat.Controller.ChatCommunicationsController.postMessageHelper.apply(scope, [scope._lastPostedMessageID + 1]); }, 15000);
        }
        else
        {
            // Maximum number of post retries exceeded... transition to error/disconnected state.
            this.setState(RightNow.Chat.Model.ChatState.DISCONNECTED, 'ERROR');
            this._connected = false;
        }
    },

    /**
     * Handler called after a successful activity signal change
     * @param {Object} response JSON data returned from the chat server
     */
    onActivitySignalChangeSuccess: function(response)
    {
        if(response.responses)
        {
            var activitySignalChangeResponse = response.responses[0];

            if(activitySignalChangeResponse &&
                activitySignalChangeResponse.chatActivitySignalChangeResult &&
                activitySignalChangeResponse.chatActivitySignalChangeResult.sneakPreviewState)
            {
                this._sneakPreviewState = activitySignalChangeResponse.chatActivitySignalChangeResult.sneakPreviewState.value;
                this._sneakPreviewInterval = activitySignalChangeResponse.chatActivitySignalChangeResult.sneakPreviewInterval;
            }
        }
    },

    /**
     * Handler called after a failed activity signal change
     */
    onActivitySignalChangeFailure: function()
    {
        // ignore
    },

    /**
     * Fires the chat notify file attachment update event.
     * @param {Object} response JSON data returned from the chat server
     */
    notifyFileAttachSuccess: function(response)
    {
        if(response.responses != null)
            RightNow.Event.fire("evt_chatNotifyFattachUpdate", response.responses);
    },

    /**
     * Sends the chat notify file attachment request
     */
    notifyFileAttach: function()
    {
        var requestConfig = {
            data: {
                action: RightNow.Chat.Model.ChatEndUserAction.NOTIFY_FATTACH,
                engagementId: this._engagementID,
                sessionId: this._endUser.servletSessionID
            },
            on: {
                success: this.notifyFileAttachSuccess
            },
            scope: this
        };

        this._chatCommunicator.makeRequest(requestConfig);
    },

    /**
     * Fires the chat notify file upload event
     * @param {Object} response JSON data returned from the chat server
     */
    notifyFileUploadSuccess: function(response)
    {
        if(response.responses === null)
            return;

        var data = response.data;

        RightNow.Event.fire("evt_chatNotifyFileUploadUpdate", response.responses, RightNow.JSON.parse(data));
    },

    /**
     * Sends the file uploaded request
     * @param {Object} eventObject
     */
    notifyFileUploaded: function(eventObject)
    {
        var callbackArgument = {
            error: eventObject.data.error,
            name: eventObject.data.name,
            size: eventObject.data.size,
            transactionID: eventObject.data.transactionID
        };

        callbackArgument = RightNow.JSON.stringify(callbackArgument);

        var requestConfig = {
            data: {
                action: RightNow.Chat.Model.ChatEndUserAction.FATTACH_UPLOAD,
                engagementId: this._engagementID,
                sessionId: this._endUser.servletSessionID,
                status: eventObject.data.error === 0 ? 'RECEIVED' : 'ERROR',
                localFName: eventObject.data.tmp_name,
                userFName: eventObject.data.name,
                contentType: eventObject.data.type,
                fileSize: eventObject.data.size
            },
            callbackArgument: callbackArgument,
            on: {
                success: this.notifyFileUploadSuccess
            },
            scope: this
        };

        return this._chatCommunicator.makeRequest(requestConfig);
    },

    /**
     * Sends the co-browse action request
     * @param {string} action
     */
    sendCoBrowseAction: function(action)
    {
        var requestConfig = {
            data: {
                action: RightNow.Chat.Model.ChatEndUserAction.COBROWSE,
                cobrowse_action: action
            }
        };

        this._chatCommunicator.makeRequest(requestConfig);
    },
    /**
     * Sends the video chat action request
     * @param {string} action
     * @param {string} videoChatSessionId
     */
    sendVideoChatAction: function(action, videoChatSessionId)
    {
        var requestConfig = {
            data: {
                action: 'MEDIA',
                mediaType: 'VIDEO',
                mediaAction: action
            }
        };
        var callbackArgument = videoChatSessionId;
        if(action === 'ACCEPTED')
        {
            requestConfig = {
                data: {
                    action: 'MEDIA',
                    mediaType: 'VIDEO',
                    mediaAction: action
                },
                callbackArgument: callbackArgument,
                on: {
                    success: this.notifyVideoActionSuccess
                },
                scope: this
            };
        }
        var ret = this._chatCommunicator.makeRequest(requestConfig);
    },
    /**
     * Fires the video chat start event
     * @param {Object} response
     */
    notifyVideoActionSuccess: function(response)
    {
        if(response.responses === null)
            return;
    },
    /**
     * Fires the video chat initiate event
     */
    notifyVideoJWTAction: function(tokenMessage)
    {
        if(tokenMessage && tokenMessage.token && tokenMessage.mediaType && tokenMessage.mediaType.value === 'VIDEO' && !tokenMessage.isRestoringTranscript)
        {
            var eo = {
                mediaType: tokenMessage.mediaType,
                multiChannelSiteId: tokenMessage.multiChannelSiteId,
                mediaToken: tokenMessage.token,
                endPoint: tokenMessage.endpoint,
                videoChatSessionId: tokenMessage.mediaSessionId
            };
            RightNow.Event.fire('evt_chatVideoChatInitiate', eo);
            if(window.oit) {
                window.oit.fire(new CustomEvent('inlay-oracle-chat-video-initiate', { detail: eo }));
            }
        }
    },

    /**
     * Logs a message to the console if in development mode.
     * @param {string} message
     */
    logDebug: function(message)
    {
        if(window.console === undefined || window.console.log === undefined)
            return;

        var debugCookie = this.Y.Cookie.get('location');
        if (debugCookie)
            debugCookie = debugCookie.split('~');
        if (debugCookie && debugCookie[0])
            debugCookie = debugCookie[0];

        if(debugCookie === 'development')
            window.console.log(message);
    },

    /**
     * Returns the service finish timestamp
     * @param {Object} finishTime Date object
     * @return {number|null} timestamp
     */
    createServiceFinishTimestamp: function(finishTime)
    {
        var match = finishTime.match(this._xmlDateRE);
        var timestamp = null

        if (match)
        {
            timestamp = new Date(Date.UTC(match[1], (match[2] - 1), match[3], match[4], match[5], match[6], (match[7] ? match[7] : ''))).getTime();
            if (finishTime.slice(-1) !== 'Z')
            {
                var tzOffset = 0;

                if (['+', '-'].indexOf(finishTime.slice(-6).substring(0,1)) > -1)
                {
                    var timezoneMatch = finishTime.slice(-6).match(new RegExp(/^([+-])([0-9]{2}):([0-9]{2})$/));
                    if (timezoneMatch)
                    {
                       tzOffset = (timezoneMatch[1] === '-' ? '+' : '-') + ((parseInt(timezoneMatch[2], 10) * 60) + parseInt(timezoneMatch[3], 10));
                    }
                }
                else
                {
                    var currentDate = new Date();
                    tzOffset = currentDate.getTimezoneOffset();
                }
                timestamp = timestamp + (tzOffset * 60 * 1000);
            }
        }
        return timestamp;
    }
};
