YUI().use('event', 'cookie', 'io-xdr', 'querystring-stringify-simple', 'json-parse', function(Y) {
RightNow.namespace("RightNow.Chat.UI");
/**
 * Contains chat user interface functionality
 * @namespace
 */
RightNow.Chat.UI = RightNow.Chat.UI || {};

/**
 * Defines the CP Chat UI Event Bus. This is the middle layer between the Chat
 * widgets and the communications library. Widgets raise events when they need
 * to communicate to the Chat Server, they are subscribed to here. An action is
 * invoked on the communications controller. It then subscribes to responses
 * from the communications library and routes them back to the widgets.
 *
 * @constructor
 */
RightNow.Chat.UI.EventBus = function()
{
    var _communicationsController = null;
    var _fileUploadHandle = {transactionID:-1};
    var _surveyBaseUrl = null;
    var _terminateSessionString = null;
    var _eventBusInitialized = false;

    /**
     * Initializes the event bus
     */
    this.initializeEventBus = function()
    {
        var Event = RightNow.Event,
            Controller = RightNow.Chat.Controller,
            s = 'subscribe';

        // Create and initialize Communications Controller
        Controller.ChatCommunicationsController = new Controller.ChatCommunicationsController(Y);
        _communicationsController = Controller.ChatCommunicationsController;
        _communicationsController.initializeCommunications();
        // Chat Server Connect events
        Event[s]("evt_chatSetParametersRequest", onChatSetParametersRequest);
        Event[s]("evt_chatValidateParametersRequest", onChatValidateParametersRequest);
        Event[s]("evt_chatCheckAnonymousRequest", onChatCheckAnonymousRequest);
        Event[s]("evt_chatConnectRequest", onChatConnectRequest);
        Event[s]("evt_chatConnectRestRequest", onChatConnectRestRequest);
        Event[s]("evt_chatConnectUpdate", onChatConnectResponse);

        // Fetch Update events
        Event[s]("evt_chatFetchUpdateRequest", onChatFetchUpdateRequest);
        Event[s]("evt_chatFetchUpdateComplete", onChatFetchUpdateResponse);

        // Chat State Change events
        Event[s]("evt_chatStateChange", onChatStateChangeResponse);

        // Engagement participant added events
        Event[s]("evt_chatEngagementParticipantAdded", onEngagementParticipantAddedResponse);

        // Agent Status Change events
        Event[s]("evt_chatAgentStatusChange", onAgentStatusChangeResponse);

        // Handle chat disconnection event
        Event[s]("evt_chatDisconnectNotification", onChatDisconnectResponse);

        // Handle end user hangup or cancel request
        Event[s]("evt_chatHangupRequest", onChatHangupRequest);

        // Handle received chat posts
        Event[s]("evt_chatPostMessageRequest", onChatPostRequest);

        // Handle Queue Position Notifications
        Event[s]("evt_chatQueuePositionNotification", onQueuePositionNotificationResponse);

        // Used for handling activity status signals
        Event[s]("evt_chatPostMessageKeyUpRequest", onChatPostMessageKeyUpRequest);

        // Handle the ChatSendButton click event
        Event[s]("evt_chatSendButtonClickRequest", onChatSendButtonClickRequest);

        // Handle ChatAttachFile upload events
        Event[s]("evt_chatNotifyFattachLocalRequest", onChatNotifyFattachLocalRequest);
        Event[s]("evt_chatNotifyFattachUpdate", onChatNotifyFattachResponse);
        Event[s]("evt_chatNotifyFileUploadRequest", onChatNotifyFileUpload);
        Event[s]("evt_chatNotifyFileUploadUpdate", onChatNotifyFileUploadResponse);
        Event[s]("evt_fileUploadCancelRequest", onFileUploadCancelRequest);

        // CoBrowse events
        Event[s]("evt_chatCobrowseInvitation", onChatCoBrowseInvitation);
        Event[s]("evt_chatCoBrowseAcceptRequest", onChatCoBrowseAcceptRequest);
        Event[s]("evt_chatCoBrowseDenyRequest", onChatCoBrowseDenyRequest);
        Event[s]("evt_chatCobrowseStatusNotification", onChatCoBrowseStatusNotification);
        // new Cobrowse Premium events
        Event[s]("evt_chatCoBrowsePremiumInvitation", onChatCoBrowsePremiumInvitation);
        Event[s]("evt_chatCoBrowsePremiumAcceptRequest", onChatCoBrowsePremiumAcceptRequest);
        Event[s]("evt_chatCoBrowsePremiumDenyRequest", onChatCoBrowsePremiumDenyRequest);

        // VideoChat events
        Event[s]("evt_chatVideoChatInvitation", onChatVideoChatInvitation);
        Event[s]("evt_chatVideoChatAcceptRequest", onChatVideoChatAcceptRequest, this);
        Event[s]("evt_chatVideoChatDenyRequest", onChatVideoChatDenyRequest, this);
        Event[s]("evt_chatVideoChatStart", onChatVideoChatStart);
        Event[s]("evt_chatVideoChatStop", onChatVideoChatStop);
        Event[s]("evt_chatVideoChatError", onChatVideoChatError);
        Event[s]("evt_chatVideoChatAbort", onChatVideoChatAbort);

        Event[s]("evt_chatVideoChatStatusNotification", onChatVideoChatStatusNotification, this);

        // Off the record events
        Event[s]("evt_chatOffTheRecordButtonClickRequest", onChatOffTheRecordButtonClickRequest);

        // Chat close button click event
        Event[s]("evt_chatCloseButtonClickRequest", onChatCloseButtonClickRequest);

        // Search event
        Event[s]("evt_searchRequest", onSearchPerformedRequest);

        // Reconnect events
        Event[s]("evt_chatReconnectUpdate", onChatReconnectUpdate);

        // Agent absent events
        Event[s]("evt_chatAgentAbsentUpdate", onAgentAbsentUpdate);

        // Out-of-band data event
        Event[s]("evt_chatPostOutOfBandDataRequest", onChatPostOutOfBandDataRequest);

        // VA Passthrough Message event
        Event[s]("evt_chatVaPassthroughMessage", onChatVaPassthroughMessage);

        // Fire event bus init signal
        Event.fire("evt_chatEventBusInitializedResponse", null);

        if(window.oit && window.oit.allInlaysAreLoaded()) {
            this._onOITLoaded();
        } else {
            document.addEventListener('inlay-oracle-chat-video-loaded', this._onOITLoaded.bind(this));
        }

        this._eventBusInitialized = true;
    };

    /**
     * Sets the videoInlayLoaded flag to true
     */
    this._onOITLoaded = function() {
        this.videoInlayLoaded = true;
    }
    /**
     * Returns true if the event bus is intialized
     */
    this.isEventBusInitialized = function()
    {
        return this._eventBusInitialized;
    };

    /**
     * Handler for on window close
     */
    this.onWindowClose = function()
    {
        if(_communicationsController.isConnected())
            return _terminateSessionString;
        else
            return;
    };

    /**
     * Handler for on window unload
     */
    this.onWindowUnload = function()
    {
        if (Y.Cookie.get("pc_chat_state")) {
            _communicationsController.setNavigationType(RightNow.Chat.Model.NavigationType.NAVIGATE_AWAY);
            return;
        }

        // send the disconnect signal
        _communicationsController.logoff(true);
    };

    /**
     * This function used to set parameters that don't need to exist in the URL.
     * @param {string} type
     * @param {Object} eventObject
     */
    function onChatSetParametersRequest(type, eventObject)
    {
        eventObject = eventObject[0];

        _communicationsController.setConnectionParameters(eventObject.data.connectionData);

        var completedSurveyID = eventObject.data.surveyCompID;
        var cancelledSurveyID = eventObject.data.surveyTermID;
        var completedSurveyAuth = eventObject.data.surveyCompAuth;
        var cancelledSurveyAuth = eventObject.data.surveyTermAuth;

        _surveyBaseUrl = eventObject.data.surveyBaseUrl;

        if(completedSurveyID)
            _communicationsController.setCompletedSurveyID(completedSurveyID);

        if(cancelledSurveyID)
            _communicationsController.setCancelledSurveyID(cancelledSurveyID);

        if(completedSurveyAuth)
            _communicationsController.setCompletedSurveyAuth(completedSurveyAuth);

        if(cancelledSurveyAuth)
            _communicationsController.setCancelledSurveyAuth(cancelledSurveyAuth);

        _terminateSessionString = eventObject.data.terminateChatSessionString;

        RightNow.Event.fire("evt_chatSetParametersResponse", eventObject);
    }

    /**
     * This function is used to validate chat parameters before a logon operation
     * @param {string} type
     * @param {Object} eventObject
     */
    function onChatValidateParametersRequest(type, eventObject)
    {
        eventObject = eventObject[0];
        eventObject.data.valid = RightNow.Chat.UI.Validator.validate(eventObject.data);

        RightNow.Event.fire("evt_chatValidateParametersResponse", eventObject);
    }

    /**
     * Determines whether a chat request is deemed 'Anonymous'
     * @param {string} type
     * @param {Object} eventObject
     */
    function onChatCheckAnonymousRequest(type, eventObject)
    {
        eventObject = eventObject[0];
        var anonymousRequest = false;
        if((eventObject.data.firstNameRequired && !eventObject.data.firstName) ||
           (eventObject.data.lastNameRequired && !eventObject.data.lastName) ||
           (eventObject.data.emailRequired && !eventObject.data.email))
            anonymousRequest = true;

        eventObject.data.anonymousRequest = anonymousRequest;
        RightNow.Event.fire("evt_chatCheckAnonymousResponse", eventObject);
    }

    /**
     * Handler called after a chat connect request
     * @param {string} type
     * @param {Array} eventData
     */
    function onChatConnectRequest(type, eventData)
    {
        _communicationsController.logon(eventData[0]);
    }

    /**
     * Handler called after a chat connect request
     * @param {string} type
     * @param {Array} eventData
     */
    function onChatConnectRestRequest(type, eventData)
    {
        _communicationsController.logonRest(eventData[0]);
    }

    /**
     * Handler called after a chat connect response
     * @param {string} type
     * @param {Array} eventData
     */
    function onChatConnectResponse(type, eventData)
    {
        RightNow.Event.fire("evt_chatConnectResponse", eventData[0]);
    }

    /**
     * Handler called after a chat fetch update request
     * @param {string} type
     * @param {Array} eventData
     */
    function onChatFetchUpdateRequest(type, eventData)
    {
        _communicationsController.fetchUpdate();
    }

    /**
     * Handler called after a chat fetch update response
     * @param {string} type
     * @param {Array} eventData
     */
    function onChatFetchUpdateResponse(type, eventData)
    {
        RightNow.Event.fire("evt_chatFetchUpdateResponse", eventData[0]);
    }

    /**
     * Handler called after a chat state change response
     * @param {string} type
     * @param {Array} eventData
     */
    function onChatStateChangeResponse(type, eventData)
    {
        RightNow.Event.fire("evt_chatStateChangeResponse", eventData[0]);
        if(window.oit) {
            window.oit.fire(new CustomEvent('inlay-oracle-chat-stateChange', { detail: eventData[0].data }));
        }

        // If this is a disconnect event and the chat isn't running in a popup, consider showing the "completed" survey
        // NOTE: This is blocked by popup blockers on agent disconnect since it's not user initiated. May be the best
        // we're able to do, unfortunately.
        if(eventData[0].data.currentState === RightNow.Chat.Model.ChatState.DISCONNECTED && !window.opener)
        {
            var completedSurveyID = _communicationsController.getCompletedSurveyID();

            if(completedSurveyID !== 0)
            {
                var surveyUrl = _surveyBaseUrl + '/5/' + completedSurveyID + '/7/' + _communicationsController.getEngagementID() + '/12/' + _communicationsController.getCompletedSurveyAuth() + '/15/' + _communicationsController.getEncodedAccountID();

                // Let's wait 3 seconds before showing the survey if the agent disconnected. Feels less abrupt to the enduser that way.
                if(eventData[0].data.reason === 'AGENT_CONCLUDED')
                    setTimeout(function() { showUrl(surveyUrl, eventData[0].data.openInWindow)}, 3000);
                else
                    showUrl(surveyUrl, eventData[0].data.openInWindow);
            }
        }
    }

    /**
     * Handler called after an agent status change response
     * @param {string} type
     * @param {Array} eventData
     */
    function onAgentStatusChangeResponse(type, eventData)
    {
        RightNow.Event.fire("evt_chatAgentStatusChangeResponse", eventData[0]);
    }

    /**
     * Handler called after an engagement participant added response
     * @param {string} type
     * @param {Array} eventData
     */
    function onEngagementParticipantAddedResponse(type, eventData)
    {
        RightNow.Event.fire("evt_chatEngagementParticipantAddedResponse", eventData[0]);
    }

    /**
     * Handler called after a chat disconnected response
     * @param {string} type
     * @param {Array} eventData
     */
    function onChatDisconnectResponse(type, eventData)
    {
        var reason = eventData[0].data.reason;
        var reasons = RightNow.Chat.Model.ChatDisconnectReason;

        if (reason === reasons.PARTICIPANT_LEFT || reason === reasons.TRANSFERRED_TO_QUEUE || reason === reasons.CONFREE_DISCONNECTED)
            RightNow.Event.fire("evt_chatEngagementParticipantRemovedResponse", eventData[0]);
        else if(reason === reasons.AGENT_CONCLUDED || eventData[0].data.isUserDisconnect)
            RightNow.Event.fire("evt_chatEngagementConcludedResponse", eventData[0]);
    }

    /**
     * Handler called after a chat hangup request
     * @param {string} type
     * @param {Array} eventData
     */
    function onChatHangupRequest(type, eventData)
    {
        _communicationsController.logoff(false, eventData[0].data.isCancelled);

        if(eventData[0].data.isCancelled)
        {
            var cancelledSurveyID = _communicationsController.getCancelledSurveyID();
            var cancelingUrl = Y.Lang.trim(eventData[0].data.cancelingUrl);
            if(cancelledSurveyID !== 0)
            {
                showUrl(_surveyBaseUrl + '/5/' + cancelledSurveyID + '/7/' + _communicationsController.getEngagementID() + '/12/' + _communicationsController.getCancelledSurveyAuth());
                setTimeout("window.close()", 2000); // Allow time for the logoff request to process.
            }
            else if(cancelingUrl !== "")
            {
                showUrl(cancelingUrl);
                setTimeout("window.close()", 2000); // Allow time for the logoff request to process.
            }
        }
    }

    /**
     * Handler called after a chat post request
     * @param {string} type
     * @param {Array} eventData
     */
    function onChatPostRequest(type, eventData)
    {
        if(eventData[0].data.isOffTheRecord == null)
            eventData[0].data.isOffTheRecord = false;

        if(eventData[0].data.isEndUserPost)
        {
            eventData[0].data.endUser = _communicationsController.getEndUser();

            if(eventData[0].data.postMessage !== false)
                eventData[0].data.messageId = _communicationsController.postMessage(eventData[0].data.messageBody, eventData[0].data.isOffTheRecord);
        }

        RightNow.Event.fire("evt_chatPostResponse", eventData[0]);
    }

    /**
     * Handler called after a chat post message keyup request
     * @param {string} type
     * @param {Array} eventData
     */
    function onChatPostMessageKeyUpRequest(type, eventData)
    {
        // Don't bother checking length if the length of the current text is less than the previous
        if(eventData[0].data.inputValue.length > eventData[0].data.inputValueBeforeChange.length
            && (!_communicationsController.isMessageAllowed(eventData[0].data.inputValue, eventData[0].data.isOffTheRecord)))
        {
            RightNow.Event.fire("evt_chatPostLengthExceededResponse", eventData[0]);
        }

        _communicationsController.handleChatPostMessageKeyUp(eventData[0].data.keyEvent, eventData[0].data.inputValue);
    }

    /**
     * Handler for out-of-band user data event
     * @param {string} type
     * @param {Array} eventData
     */
    function onChatPostOutOfBandDataRequest(type, eventData)
    {
        _communicationsController.postOutOfBandData(eventData[0].data);
    }

    /**
     * Handler called when the client receives a Virtual Agent passthrough message (e.g. pick list)
     * @param {string} type
     * @param {Array} eventData
     */
    function onChatVaPassthroughMessage(type, eventData)
    {
        RightNow.Event.fire("evt_chatVaPassthroughMessageResponse", eventData[0]);
    }

    /**
     * Handler called after a queue position notification response
     * @param {string} type
     * @param {Array} eventData
     */
    function onQueuePositionNotificationResponse(type, eventData)
    {
        RightNow.Event.fire("evt_chatQueuePositionNotificationResponse", eventData[0]);
    }

    /**
     * Handler called after a chat send button click request
     * @param {string} type
     * @param {Array} eventData
     */
    function onChatSendButtonClickRequest(type, eventData)
    {
        RightNow.Event.fire("evt_chatSendButtonClickResponse", eventData[0]);
    }

    /**
     * Handler called after a chat notify file attachment local request
     * @param {string} type
     * @param {Array} eventData
     */
    function onChatNotifyFattachLocalRequest(type, eventData)
    {
        //we will assign a unique transaction id to the following set of
        //file attachment requests
        var transactionID = ++_fileUploadHandle.transactionID;
        _fileUploadHandle[transactionID] = {};

        _communicationsController.notifyFileAttach();
    }

    /**
     * Handler called after a chat notify file attachment response
     * @param {string} type
     * @param {Array} eventData
     */
    function onChatNotifyFattachResponse(type, eventData)
    {
        //notify listeners the transaction id assigned for the ongoing set of
        //file ttachment transactions
        eventData[0].transactionID = _fileUploadHandle.transactionID;
        RightNow.Event.fire("evt_chatNotifyFattachUpdateResponse", eventData[0]);
    }

    /**
     * Notifies chat service that a file has been uploaded
     * to the RNW server and is available for consumption
     * @param {string} type
     * @param {Array} eventData
     */
    function onChatNotifyFileUpload(type, eventData)
    {
        var transactionID = eventData[0].data.transactionID;
        if(_fileUploadHandle[transactionID].canceled)
            eventData[0].data.error = 2;
        else if(_fileUploadHandle[transactionID].timedOut)
            eventData[0].data.error = 20; //Magic number; 20 is unused

        if (!_fileUploadHandle[transactionID].notified)
        {
            _fileUploadHandle[transactionID].notified = true;
            _communicationsController.notifyFileUploaded(eventData[0]);
        }
    }

    /**
     * Response from chat service notification of file upload
     * @param {string} type
     * @param {Array} eventData
     */
    function onChatNotifyFileUploadResponse(type, eventData)
    {
        //Notify listeners with data such as transactionID, file name, size etc
        RightNow.Event.fire("evt_fileUploadUpdateResponse", eventData[1]);
    }

    /**
     * Handler called after a file upload cancel request
     * @param {string} type
     * @param {Array} eventData
     */
    function onFileUploadCancelRequest(type, eventData)
    {
        //Just mark the request as canceled. This is only useful if we haven't
        //yet fired off the chat notify request yet.
        //WARNING: Any attempts at trying to abort either the RNW file upload request
        //or the chat notify file upload request will be un-fruitful!
        if(_fileUploadHandle[_fileUploadHandle.transactionID])
        {
            _fileUploadHandle[_fileUploadHandle.transactionID].canceled = true;
            eventData[0].data.transactionID = _fileUploadHandle.transactionID;
            onChatNotifyFileUpload(type, eventData);
        }
    }

    /**
     * Handler called after a chat cobrowse invitation response
     * @param {string} type
     * @param {Array} eventData
     */
    function onChatCoBrowseInvitation(type, eventData)
    {
        RightNow.Event.fire("evt_chatCobrowseInvitationResponse", eventData[0]);
    }

    /**
     * Handler called after a chat cobrowse premium invitation response
     * @param {string} type
     * @param {Array} eventData
     */
    function onChatCoBrowsePremiumInvitation(type, eventData)
    {
        RightNow.Event.fire("evt_chatCoBrowsePremiumInvitationResponse", eventData[0]);
    }

    /**
     * Handler called after a chat cobrowse accept request
     * @param {string} type
     * @param {Array} eventData
     */
    function onChatCoBrowseAcceptRequest(type, eventData)
    {
        eventData[0].data.accepted = true;

        RightNow.Event.fire("evt_chatCobrowseAcceptResponse", eventData[0]);

        _communicationsController.sendCoBrowseAction(RightNow.Chat.Model.ChatCoBrowseStatusCode.ACCEPTED);
    }

    /**
     * Handler called after a chat cobrowse premium accept request
     * @param {string} type
     * @param {Array} eventData
     */
    function onChatCoBrowsePremiumAcceptRequest(type, eventData)
    {
        eventData[0].data.accepted = true;

        RightNow.Event.fire("evt_chatCoBrowsePremiumAcceptResponse", eventData[0]);

        _communicationsController.sendCoBrowseAction(RightNow.Chat.Model.ChatCoBrowseStatusCode.ACCEPTED);
    }

    /**
     * Handler called after a chat cobrowse deny request
     * @param {string} type
     * @param {Array} eventData
     */
    function onChatCoBrowseDenyRequest(type, eventData)
    {
        eventData[0].data.accepted = false;

        RightNow.Event.fire("evt_chatCobrowseAcceptResponse", eventData[0]);

        _communicationsController.sendCoBrowseAction(RightNow.Chat.Model.ChatCoBrowseStatusCode.DECLINED);
    }

    /**
     * Handler called after a chat cobrowse deny request
     * @param {string} type
     * @param {Array} eventData
     */
    function onChatCoBrowsePremiumDenyRequest(type, eventData)
    {
        eventData[0].data.accepted = false;

        RightNow.Event.fire("evt_chatCoBrowsePremiumAcceptResponse", eventData[0]);

        _communicationsController.sendCoBrowseAction(RightNow.Chat.Model.ChatCoBrowseStatusCode.DECLINED);
    }

    /**
     * Handler called after a chat cobrowse status notification response
     * @param {string} type
     * @param {Array} eventData
     */
    function onChatCoBrowseStatusNotification(type, eventData)
    {
        RightNow.Event.fire("evt_chatCobrowseStatusResponse", eventData[0]);
    }

    /**
     * Handler called after a videoChat invitation response
     * @param {string} type
     * @param {Array} eventData
    */
    function onChatVideoChatInvitation(type, eventData)
    {
        RightNow.Event.fire("evt_chatVideoChatInvitationResponse", eventData[0]);
    }

    /**
     * Handler called after a video chat accept request
     * @param {string} type
     * @param {Array} eventData
     */
    function onChatVideoChatAcceptRequest(type, eventData)
    {
        eventData[0].data.accepted = true;

        RightNow.Event.fire("evt_chatVideoChatAcceptResponse", eventData[0]);

        if(!this.videoInlayLoaded) {
            _communicationsController.sendVideoChatAction(RightNow.Chat.Model.ChatVideoChatStatusCode.ACCEPTED, eventData[0].data.videoChatSessionId);
        }
    }

    /**
     * Handler called after a video chat deny request
     * @param {string} type
     * @param {Array} eventData
     */
    function onChatVideoChatDenyRequest(type, eventData)
    {
        eventData[0].data.accepted = false;

        RightNow.Event.fire("evt_chatVideoChatAcceptResponse", eventData[0]);

        if(!this.videoInlayLoaded) {
            _communicationsController.sendVideoChatAction(RightNow.Chat.Model.ChatVideoChatStatusCode.DECLINED);
        }
    }

    /**
     * Handler called after a video chat status notification response
     * @param {string} type
     * @param {Array} eventData
    */
    function onChatVideoChatStatusNotification(type, eventData)
    {
        if(this.videoInlayLoaded && window.oit) {
            window.oit.fire(new CustomEvent('inlay-oracle-chat-video-statusNotification', { detail: { videoChatStatus: eventData[0].data.videoChatStatus, clientResponsible: false, fromAgent: true, isRestoringTranscript: eventData[0].data.isRestoringTranscript  } }));
        } else {
            RightNow.Event.fire("evt_chatVideoChatStatusResponse", eventData[0]);
        }
    }

    /**
     * Handler called after a video chat starts
     * @param {string} type
     * @param {Array} eventData
     */
    function onChatVideoChatStart(type, eventData)
    {
        _communicationsController.sendVideoChatAction(RightNow.Chat.Model.ChatVideoChatStatusCode.STARTED);
    }

    /**
     * Handler called after a video chat stops
     * @param {string} type
     * @param {Array} eventData
     */
    function onChatVideoChatStop(type, eventData)
    {
        _communicationsController.sendVideoChatAction(RightNow.Chat.Model.ChatVideoChatStatusCode.STOPPED);
    }

    /**
     * Handler called after a video chat stops abnormally
     * @param {string} type
     * @param {Array} eventData
     */
    function onChatVideoChatAbort(type, eventData)
    {
        _communicationsController.sendVideoChatAction(RightNow.Chat.Model.ChatVideoChatStatusCode.ABORTED);
    }

    /**
     * Handler called after a video chat receives error
     * @param {string} type
     * @param {Array} eventData
     */
    function onChatVideoChatError(type, eventData)
    {
        _communicationsController.sendVideoChatAction(RightNow.Chat.Model.ChatVideoChatStatusCode.ERROR);
    }

    /**
     * Handler called after a chat off-the-record button click request
     * @param {string} type
     * @param {Array} eventData
     */
    function onChatOffTheRecordButtonClickRequest(type, eventData)
    {
        RightNow.Event.fire("evt_chatOffTheRecordButtonClickResponse", eventData[0]);
    }

    /**
     * Handler called after a chat close button click request
     * @param {string} type
     * @param {Array} eventData
     */
    function onChatCloseButtonClickRequest(type, eventData)
    {
        var completedSurveyID = _communicationsController.getCompletedSurveyID();
        var windowPopped = false;

        if(completedSurveyID !== 0)
        {
            windowPopped = true;
            showUrl(_surveyBaseUrl + '/5/' + completedSurveyID + '/7/' + _communicationsController.getEngagementID() + '/12/' + _communicationsController.getCompletedSurveyAuth() + '/15/' + _communicationsController.getEncodedAccountID());
        }
        else if(eventData[0].data.closingUrl && eventData[0].data.closingUrl !== "")
        {
            showUrl(eventData[0].data.closingUrl, eventData[0].data.openInWindow);
        }

        if(windowPopped)
            setTimeout("window.close()", 500); // Google Chrome has a bug where it can't close a window immediately after opening one. Setting a timeout works around this.
        else
            window.close();
    }

    /**
     * Handler called after a search performed request
     * @param {string} type
     * @param {Array} eventData
     */
    function onSearchPerformedRequest(type, eventData)
    {
        _communicationsController.setSearchPerformed(true);
    }

    /**
     * Handler called after a chat reconnect update response
     * @param {string} type
     * @param {Array} eventData
     */
    function onChatReconnectUpdate(type, eventData)
    {
        RightNow.Event.fire("evt_chatReconnectUpdateResponse", eventData[0]);
    }

    /**
     * Handler called after an agent absent update response
     * @param {string} type
     * @param {Array} eventData
     */
    function onAgentAbsentUpdate(type, eventData)
    {
        RightNow.Event.fire("evt_chatAgentAbsentUpdateResponse", eventData[0]);
        if(window.oit) {
            window.oit.fire(new CustomEvent('inlay-oracle-chat-agentAbsentUpdate', { detail: eventData[0] }));
        }
    }

    /**
     * Displays the url
     * @param {string} url
     * @param {string=} openInWindow
     */
    function showUrl(url, openInWindow)
    {
        if(!url.match(/^(http[s]?|ftp):\/\//i))
            if(url.match(/^ftp\./i))
                url = "ftp://" + url;
            else
                url = "http://" + url;
        if(!openInWindow || openInWindow === 'new_window' || (openInWindow === 'parent_window' && (!window.opener || window.opener.closed)))
            window.open(url);
        else
            window.opener.location = url;
    }
};

if(!RightNow.Chat.UI.Util) {
/**
 * Provide utility methods to be used with the RightNow.Chat.UI namespace
 * @namespace
 */
RightNow.Chat.UI.Util = {
    _leaveScreenIssues : undefined,

    /**
     * Replaces queue position, estimated wait time, and average wait time
     * variables in a message with span elements giving them individual id's
     *
     * @param {string} message The message containing any pre-defined variables.
     * The pre-defined variables are ${queue-position} for Queue Position,
     * {wait-time-samples} for Estimated Wait Time Samples, {estimated-wait-time}
     * for Estimated Wait Time, and ${average-wait-time} for Average Wait Time.
     *
     * @param {string} instanceID The instance ID of the widget to which the
     * created elements belong. This is used to give the span elements created
     * their own id's
     *
     * @param {string} estimatedWaitTimeSamples The value of the config verb that
     * defined the number of estimated wait time samples to use
     *
     * @return {string} String with the variables replaced with span elements
     * that can be used by the widget to display the actual values of the variables
     */
    doPositionAndWaitTimeVariableSubstitution : function(message, instanceID, estimatedWaitTimeSamples)
    {
        message = this.replaceStringAndSurroundingSpace(message, "${queue-position}", "<span id='rn_" + instanceID + "_QueuePosition'></span>");
        message = message.replace("${wait-time-samples}", estimatedWaitTimeSamples);
        message = this.replaceStringAndSurroundingSpace(message, "${estimated-wait-time}", "<span id='rn_" + instanceID + "_EstimatedWaitTime'></span>");
        message = this.replaceStringAndSurroundingSpace(message, "${average-wait-time}", "<span id='rn_" + instanceID + "_AverageWaitTime'></span>");
        return message;
    },

    /**
     * Performs a replacement as well as replacing immediately surrounding spaces
     * with &nbsp; This is in order to appease the inline display for various browsers.
     * Some browsers will not display the space immediately after the span.
     *
     * @param {string} message The message on which to do the replacement
     * @param {string} stringToReplace The string to replace
     * @param {string} stringToInsert The string to insert in place of stringToReplace
     * @return {?string} String with replacements done and surrounding spaces converted
     * into &nbsp;
     */
    replaceStringAndSurroundingSpace : function(message, stringToReplace, stringToInsert)
    {
        message = message.replace(" " + stringToReplace + " ", "&nbsp;" + stringToInsert + "&nbsp;");
        message = message.replace(" " + stringToReplace, "&nbsp;" + stringToInsert);
        message = message.replace(stringToReplace + " ", stringToInsert + "&nbsp;");
        message = message.replace(stringToReplace, stringToInsert);
        return message;
    },

    /**
     * Converts given total number of seconds to an ISO time format hh:mm:ss
     *
     * @param {number} totalSeconds The total number of seconds to convert
     *
     * @return {(string|number)} A string containing the duration in the ISO time format
     * as hh:mm:SS
     */
    toIso8601Time : function(totalSeconds)
    {
        if(totalSeconds <= 0)
            return totalSeconds;

        var hours, mins, secs = 0;
        hours = Math.floor(totalSeconds / 3600);
        mins = totalSeconds % 3600;
        mins = Math.floor(mins / 60);
        secs = totalSeconds % 60;

        var iso8601Time = "";
        if(hours > 0)
        {
            if(hours < 10)
                iso8601Time = "0" + hours;
            else
                iso8601Time = hours;

            iso8601Time = iso8601Time + ":";
        }

        if(mins < 10)
            iso8601Time = iso8601Time + "0" + mins;
        else
            iso8601Time = iso8601Time + mins;

        iso8601Time = iso8601Time + ":";
        if(secs < 10)
            iso8601Time = iso8601Time + "0" + secs;
        else
            iso8601Time = iso8601Time + secs;

        return iso8601Time;
    },

    /**
     * Return whether the browser has "leave screen issues"
     * @return {boolean}
     */
    hasLeaveScreenIssues : function()
    {
        // QA 130301-000090. IE Metro mode stops communicating when app is switched. Set flag so widgets can handle it.
        // It's currently impossible to detect Metro mode with 100% accuracy, since it doesn't identify itself
        // as Metro in the user-agent. We'll take our best guess by using feature detection.
        if(this._leaveScreenIssues === undefined)
        {
            this._leaveScreenIssues = false;

            if(Y.UA.ie >= 10)
            {
                // Metro mode ALWAYS has ActiveX disabled. Can be disabled on desktop, but isn't by default
                var activeXSupport = false;
                try
                {
                    activeXSupport = !!new ActiveXObject("htmlfile");
                }
                catch(e)
                {}

                if(!activeXSupport)
                {
                    // Metro mode is always full screen. Desktop can be, but usually isn't
                    if(window.innerWidth === screen.width && window.innerHeight === screen.height)
                    {
                        // No ActiveX support and is full screen. Could be Metro mode. Set flag.
                        this._leaveScreenIssues = true;
                    }
                }
            }
        }

        return this._leaveScreenIssues;
    }
};
}

if(!RightNow.Chat.UI.Validator) {
/**
 * Namespace that is responsible for validation of necessary chat parameters prior to a LOGON operation
 * @namespace
 */
RightNow.Chat.UI.Validator = {

    /**
     * Validates the chat data
     * @param {Object} chatData
     * @return {boolean} True if chatData is valid
     */
    validate : function(chatData)
    {
        //validate email address
        if(chatData.email && !RightNow.Text.isValidEmailAddress(chatData.email))
            return false;

        //validate product field
        if(chatData.prod && !this.isInteger(chatData.prod))
            return false;

        //validate category field
        if(chatData.cat && !this.isInteger(chatData.cat))
            return false;

        //validate custom fields
        if(!this.validateCustomFields(chatData.miscellaneousData, chatData.customFields))
            return false;

        return true;
    },

    /**
     * Validates the custom fields
     * @param {Object} customFieldsFromUrl
     * @param {Object} allCustomFields
     * @return {boolean} True if custom fields are valid
     */
    validateCustomFields : function (customFieldsFromUrl, allCustomFields)
    {
        if(!customFieldsFromUrl)
            return true;
        for(var customField in customFieldsFromUrl)
        {
            if(!this.validateCustomField(customField, customFieldsFromUrl[customField], allCustomFields))
            {
                return false;
            }
        }
        return true;
    },

    /**
     * Validates the custom field
     * @param {string} customFieldName
     * @param {number} customFieldValue
     * @param {Array} allCustomFields
     * @return {boolean} True if custom field is valid
     */
    validateCustomField : function(customFieldName, customFieldValue, allCustomFields)
    {
        var customFieldID = customFieldName.substring(customFieldName.indexOf(".c.") + 1).replace(".", "$");
        var customField = false;

        for(var id in allCustomFields)
        {
            if(allCustomFields[id].col_name === customFieldID)
            {
                customField = allCustomFields[id];
                break;
            }
        }

        if(!customField)
            return false;

        switch(customField.data_type)
        {
            case RightNow.Interface.Constants.EUF_DT_DATE:
            case RightNow.Interface.Constants.EUF_DT_DATETIME:
            {
                if(!this.isValidDateTime(customFieldValue))
                    return false;
                break;
            }
            case RightNow.Interface.Constants.EUF_DT_INT:
            {
                if(!this.isInteger(customFieldValue))
                    return false;
                customFieldValue = parseInt(customFieldValue, 10);
                if(customField.min_val && customFieldValue < parseInt(customField.min_val, 10))
                    return false;
                if(customField.max_val && customFieldValue > parseInt(customField.max_val, 10))
                    return false;
                break;
            }
            case RightNow.Interface.Constants.EUF_DT_RADIO:
            {
                if(!this.isInteger(customFieldValue))
                    return false;
                if(customFieldValue != "0" && customFieldValue != "1")
                    return false;
                break;
            }
        }
        return true;
    },

    /**
     * Return true if value is an integer
     * @param {string} value
     * @return {boolean}
     */
    isInteger : function(value)
    {
        var integerRegExp = /^-?\d+$/;
        return integerRegExp.test(value);
    },

    /**
     * Return true if value is a valid date/time
     * @param {string} value
     * @return {boolean}
     */
    isValidDateTime : function(value)
    {
        var date = null;

        if (/(\d{4})-(\d{2})-(\d{2})T(\d{2})\:(\d{2})\:(\d{2})[+-](\d{2})\:(\d{2})/.test(value))
        {
            date = new Date(value);
        }
        else
        {
            //first validate using simple regular expressions
            var dateRegExp = /^\d{4}-\d{1,2}-\d{1,2}$/;
            var timeRegExp = /^\d{1,2}(:\d{1,2}){1,2}$/;
            var dateTime = value.split(" ");
            if (!dateRegExp.test(dateTime[0]) ||
                dateTime[1] && !timeRegExp.test(dateTime[1])) {
                return false;
            }

            //create a Date object
            var dateArray = dateTime[0].split("-");
            var timeArray = dateTime[1] ? dateTime[1].split(":") : null;
            if (timeArray)
                date = new Date(dateArray[0], dateArray[1] - 1, dateArray[2], timeArray[0], timeArray[1]);
            else
                date = new Date(dateArray[0], dateArray[1] - 1, dateArray[2]);

            //validate what we set as year, month and date remains same on the created Date
            if (date.getFullYear() != dateArray[0] || date.getMonth() + 1 != dateArray[1] || date.getDate() != dateArray[2])
                return false;

            //validate what we set as hours and minutes on the time remains same on the created Date
            if (timeArray != null && (date.getHours() != timeArray[0] || date.getMinutes() != timeArray[1]))
                return false;
        }

        //validate date range
        var minDate = new Date(1970, 0, 2);
        var maxDate = new Date(2038, 0, 18);
        if (date < minDate || date >= maxDate)
            return false;

        return true;
    }
};
}

//Initialize the EventBus after onDomReady so the session parameter is set
Y.on('domready', function() {
    var isPersistentChat = false;
    try
    {
        isPersistentChat = Y.Cookie.get("pc_chat_state") && (Y.Cookie.get("pc_chat_state") === RightNow.Text.Encoding.base64Encode('max') || Y.Cookie.get("pc_chat_state") === RightNow.Text.Encoding.base64Encode('min')) ? true : false;
    } catch (e) {}

    if(isPersistentChat && !Y.Cookie.get("CHAT_SESSION_ID") && Y.Cookie.get('pc_ls_support') == 'y')
    {
        return;
    }

    var UI = RightNow.Chat.UI;
    UI.EventBus = new UI.EventBus();

    //Under some instances YUI.domready fires before DOMContentLoaded is thereby leaving some scripts
    //still loading such as the Livelook one's. Hookup an event listener that'd initialize the event bus
    //after all scripts are loaded.
    document.readyState !== "complete"
        ? document.onreadystatechange = function () {
            if (document.readyState === "complete") {
                UI.EventBus.initializeEventBus();
            }
        }
        : UI.EventBus.initializeEventBus();

    // check for mobile.
    if (Y.UA.ipad || Y.UA.iphone) {
        window.onpagehide = UI.EventBus.onWindowUnload;
    }
    else if (Y.UA.android) {
        window.onbeforeunload = UI.EventBus.onWindowUnload;
        window.onunload = UI.EventBus.onWindowUnload;
    }
    // desktop for all safari/chrome/ff/edge
    else {
        window.onbeforeunload = UI.EventBus.onWindowUnload;
        window.onpagehide = UI.EventBus.onWindowUnload;
        window.onunload = UI.EventBus.onWindowUnload;
    }
    window.ondragover = function(event){
        (event.preventDefault) ? event.preventDefault() : event.returnValue = false;
        return false;
    }
    window.ondrop = function(event){
        var element = event.target;
        if(!(element.tagName === 'INPUT' && element.type === 'file')) {
            (event.preventDefault) ? event.preventDefault() : event.returnValue = false;
        return false;
        }
    }
    //disallow ESC key as it cancels any outstanding AJAX requests.
    //otherwise the get loop will die.
    Y.on("key", function(e){
        e.preventDefault();
    }, Y.one(document), "down:27");
}, this);
});
