RightNow.Widgets.ChatVideoChat = RightNow.Widgets.extend({
    /**
     * Widget constructor.
     */
    constructor: function() {
        this._inVideoChat = false;
        this._lastUpdatedChatState = RightNow.Chat.Model.ChatState.UNDEFINED;
        RightNow.Event.subscribe("evt_chatStateChangeResponse", this._onChatStateChangeResponse, this);
        RightNow.Event.subscribe("evt_chatVideoChatInvitationResponse", this._videoChatInvitationResponse, this);
        RightNow.Event.subscribe("evt_chatVideoChatAcceptResponse", this._videoChatAcceptResponse, this);
        RightNow.Event.subscribe("evt_chatVideoChatStatusResponse", this._videoChatStatusResponse, this);
        RightNow.Event.subscribe("evt_chatVideoChatAllowClicked", this._videoChatAllow, this);
        RightNow.Event.subscribe("evt_chatVideoChatInitiate", this._videoChatInitiate, this);
        RightNow.Event.subscribe("evt_chatAgentAbsentUpdateResponse", this._agentAbsentUpdateResponse, this);
        RightNow.Event.subscribe("evt_chatVideoChatAbort", this._videoChatDisconnect, this);
    },
    /**
     * Handles this stats of the chat has changed.
     * @param {string} type Event name
     * @param {object} args Event arguments
     */
    _onChatStateChangeResponse: function(type, args) {
        this._lastUpdatedChatState = args[0].data.currentState;
        if (this._inVideoChat) {
            var vcElement = document.getElementById('videoSectionWrapper');
            var currentState = args[0].data.currentState;
            var endSessionTriggered = false;

            if(currentState !== RightNow.Chat.Model.ChatState.CONNECTED) {
                if(MCServiceAPI && vcElement) {
                    try{
                        //this._inVideoChat = false;
                        MCServiceAPI.Site.endSession();
                        endSessionTriggered = true;
                    }
                    catch(e) {
                        console.log("Failed in endSession");
                    }
                    if (!endSessionTriggered) {
                        try{
                            if (currentState === RightNow.Chat.Model.ChatState.RECONNECTING) {
                                var eo = new RightNow.Event.EventObject(this, {data: {videoChatStatus: RightNow.Chat.Model.ChatVideoChatStatusCode.ABORTED}});
                            } else {
                                var eo = new RightNow.Event.EventObject(this, {data: {videoChatStatus: RightNow.Chat.Model.ChatVideoChatStatusCode.STOPPED}});
                            }
                            this._closeVideoPanel();
                            RightNow.Event.fire('evt_chatVideoChatStatusResponse', eo);
                        }
                        catch(e) {
                            console.log("Failed to fire videochatstatusrespose event.");
                        }
                    }
                }
            }
        }
    },
    /**
     * Event received when a video session is being offered. Records to ACS.
     * @param type string Event name
     * @param args object Event arguments
     */
    _videoChatInvitationResponse: function(type, args) {
        var isRestoringTranscript = args[0].data.isRestoringTranscript;

        if (!isRestoringTranscript)
        {
            RightNow.ActionCapture.record('chatVideoChat', 'invite', args[0].data.videoChatSessionId);
        }
    },
    /**
     * Event for managing the stats of the video chat session. Records to ACS.
     * @param type string Event name
     * @param args object Event arguments
     */
    _videoChatStatusResponse: function(type, args) {
        var videoChatStatus = args[0].data.videoChatStatus;
        var isRestoringTranscript = args[0].data.isRestoringTranscript;

        if (isRestoringTranscript) {
            return;
        }

        switch (videoChatStatus) {
            case RightNow.Chat.Model.ChatVideoChatStatusCode.STARTED:
                RightNow.ActionCapture.record('chatVideoChat', 'sessionStart', args[0].data.videoChatStatus);
                break;

            case RightNow.Chat.Model.ChatVideoChatStatusCode.STOPPED:
            case RightNow.Chat.Model.ChatVideoChatStatusCode.ABORTED:
            case RightNow.Chat.Model.ChatVideoChatStatusCode.ERROR:
                //this._inVideoChat = false;
                this._closeVideoPanel();
                RightNow.ActionCapture.record('chatVideoChat', 'sessionEnd', args[0].data.videoChatStatus);
                break;
        }
    },
    /**
     * Initializes the video chat session. Received on user accept.
     * @param {string} type Event name
     * @param {object} args Event arguments
     */
    _videoChatAcceptResponse: function(type, args) {
        if(args[0].data.accepted) {
            this._inVideoChat = true;
        }
    },
    _videoChatAllow: function(type, args) {
    },
    _videoChatInitiate: function(type, args) {
        var thisWidget = this,
            videoChatSessionId = args[0].videoChatSessionId;

        var vWidth  = this.data.attrs.launch_width;
        var vHeight = this.data.attrs.launch_height;
       
        if(vWidth < this.data.attrs.minimum_width || vHeight < this.data.attrs.minimum_height) {
            vWidth = this.data.attrs.minimum_width;
            vHeight = this.data.attrs.minimum_height;
        }
        else if(vWidth > this.data.attrs.maximum_width || vHeight > this.data.attrs.maximum_height) {
            vWidth = this.data.attrs.maximum_width;
            vHeight = this.data.attrs.maximum_height;
        }
        var jwt = args[0].mediaToken;
        if (!jwt || jwt.length < 1 || !videoChatSessionId || videoChatSessionId.length < 1) {
           console.log('Video chat initiate failed. Video Chat Session Id = ' + videoChatSessionId );
           thisWidget._inVideoChat = false;
           RightNow.Event.fire('evt_chatVideoChatStatusResponse', new RightNow.Event.EventObject( this, { data: { videoChatStatus: RightNow.Chat.Model.ChatVideoChatStatusCode.ERROR } } ) );
           return;
        }
        var channels = { "webrtc_video" : {
                               'videoWidth' : vWidth,
                               'videoHeight': vHeight,
                               'isFullScreenEnabled': false,
                               'container': document.getElementById('rn_' + this.instanceID)
                               }
                        }; 

        if(typeof MCServiceAPI !== 'undefined') {
           //supported
           if(MCServiceAPI.Channels && MCServiceAPI.Channels.webrtc_video && MCServiceAPI.Channels.webrtc_video.environmentErrorReason && MCServiceAPI.Channels.webrtc_video.environmentErrorReason.length > 0) {
              console.log("Can not perform video chat, Reason: " + MCServiceAPI.Channels.webrtc_video.environmentErrorReason[0]);
              thisWidget._inVideoChat = false;
              var eo = new RightNow.Event.EventObject(this, {data: {videoChatStatus: RightNow.Chat.Model.ChatVideoChatStatusCode.ERROR, videoChatData: MCServiceAPI.Channels.webrtc_video.environmentErrorReason}});
              RightNow.Event.fire('evt_chatVideoChatStatusResponse', eo);
              return;
           }

           var connectingCallback = function() {
              console.log('Video is connecting.');
           };

           var hasBeenCalled = false;
           var connectedCallback = function(evt) {
              if(thisWidget._inVideoChat && thisWidget._lastUpdatedChatState === RightNow.Chat.Model.ChatState.CONNECTED) {
                if ((! evt.hasOwnProperty('agent') || (evt.hasOwnProperty('agent') && (evt.agent === false || evt.agent === undefined))) && !hasBeenCalled) {
                    hasBeenCalled = true;
                    var eo = new RightNow.Event.EventObject(this, {data: {videoChatStatus: RightNow.Chat.Model.ChatVideoChatStatusCode.STARTED}});
                    RightNow.Event.fire('evt_chatVideoChatStatusResponse', eo);
                }
                console.log('Video is connected. Remote Audio : ' + evt.remoteAudio + ' Remote Video : ' + evt.remoteVideo);
              }
              else {
                //QA 170906-000026: Presumably EU disconnected after accepting the video, but the Video is now in a connected state.
                //We will just end the session
                MCServiceAPI.Site.endSession();
              }
           };

           var disconnectingCallback = function(evt) {
              if(thisWidget._inVideoChat) {
                 var isVisitor = !evt.hasOwnProperty('agent') || (evt.hasOwnProperty('agent') && (evt.agent === false || evt.agent === undefined));
                 console.log('Video disconnecting. Reason ' + evt.reason);
                 if (isVisitor){
                    var response = { data: {
                                         videoChatStatus: RightNow.Chat.Model.ChatVideoChatStatusCode.STOPPED,
                                         senderType: isVisitor ? '' : 'AGENT'
                                      }
                                   }
                    var eo = new RightNow.Event.EventObject(this, response);
                    RightNow.Event.fire('evt_chatVideoChatStatusResponse', eo);
                    thisWidget._inVideoChat = false;
                 }
                 removeVideoChatListeners();
              }
           };

           var disconnectedCallback = function(evt) {
              if(thisWidget._inVideoChat) {
                  var videoPanel = document.getElementById('ll_webrtc_video');
                  var isVisitor = !evt.hasOwnProperty('agent') || (evt.hasOwnProperty('agent') && (evt.agent === false || evt.agent === undefined));
                  console.log('Video Chat Session Ended. Reason ' + evt.reason);
                  if (isVisitor) {
                     var response = { 
                         data: {
                             videoChatStatus: RightNow.Chat.Model.ChatVideoChatStatusCode.STOPPED,
                             senderType: isVisitor ? '' : 'AGENT'
                         }
                     };
                     var eo = new RightNow.Event.EventObject(this, response);
                     RightNow.Event.fire('evt_chatVideoChatStatusResponse', eo);
                     thisWidget._inVideoChat = false;
                  } else if (videoPanel) {
                     var response = { 
                         data: {
                             videoChatStatus: RightNow.Chat.Model.ChatVideoChatStatusCode.ABORTED,
                             senderType: isVisitor ? '' : 'AGENT'
                         }
                     };
                     var eo = new RightNow.Event.EventObject(this, response);
                     RightNow.Event.fire('evt_chatVideoChatStatusResponse', eo);
                     thisWidget._inVideoChat = false;
                  }
                  removeVideoChatListeners();
              }
           };

           var failedCallback = function(evt) {
              if(thisWidget._inVideoChat) {
                  var isVisitor = !evt.hasOwnProperty('agent') || (evt.hasOwnProperty('agent') && (evt.agent === false || evt.agent === undefined));
                  console.log('Video Chat encountered an error.' + evt.reason);
                  var response = { data: {
                                       videoChatStatus: RightNow.Chat.Model.ChatVideoChatStatusCode.ERROR,
                                       senderType: isVisitor ? '' : 'AGENT'
                                    }
                                 }
                  var eo = new RightNow.Event.EventObject(this, response);
                  RightNow.Event.fire('evt_chatVideoChatStatusResponse', eo);
                  removeVideoChatListeners();
                  //removing the control with video, need to test if I need to still disconnect before this.
              }
           };
                
           var audioStateChangeCallback = function(evt) {
              console.log('Video Chat Audio state changed to ' + evt.state);
           };

           var videoStateChangeCallback = function(evt) {
              console.log("Video State changed to " + evt.state);
           };

           var removeVideoChatListeners = function() {
              console.log("Removing Video Chat listeners");
              try {
                  MCServiceAPI.Channels.webrtc_video.Events.Connecting.removeListener(connectingCallback);
                  MCServiceAPI.Channels.webrtc_video.Events.Connected.removeListener(connectedCallback);
                  MCServiceAPI.Channels.webrtc_video.Events.Disconnecting.removeListener(disconnectingCallback);
                  MCServiceAPI.Channels.webrtc_video.Events.Disconnected.removeListener(disconnectedCallback);
                  MCServiceAPI.Channels.webrtc_video.Events.Failed.removeListener(failedCallback);
                  MCServiceAPI.Channels.webrtc_video.Events.Error.removeListener(failedCallback);
                  MCServiceAPI.Channels.webrtc_video.Events.AudioStateChange.removeListener(audioStateChangeCallback);
                  MCServiceAPI.Channels.webrtc_video.Events.VideoStateChange.removeListener(videoStateChangeCallback);
                  thisWidget._inVideoChat = false;
              } catch (err) {
                console.log('Something went wrong removing video chat listeners: ' + err);
              }
           };
           
           //connecting
           MCServiceAPI.Channels.webrtc_video.Events.Connecting.listen(connectingCallback);
           //connected
           MCServiceAPI.Channels.webrtc_video.Events.Connected.listen(connectedCallback);
           //disconnecting
           MCServiceAPI.Channels.webrtc_video.Events.Disconnecting.listen(disconnectingCallback);
           //disconnected
           MCServiceAPI.Channels.webrtc_video.Events.Disconnected.listen(disconnectedCallback);
           //error (document says Error, while code has .Failed
           MCServiceAPI.Channels.webrtc_video.Events.Failed.listen(failedCallback);
           MCServiceAPI.Channels.webrtc_video.Events.Error.listen(failedCallback);
           //Audio State changed
           MCServiceAPI.Channels.webrtc_video.Events.AudioStateChange.listen(audioStateChangeCallback);
           //Video state changed
           MCServiceAPI.Channels.webrtc_video.Events.VideoStateChange.listen(videoStateChangeCallback);
           var apiKey = args[0].multiChannelSiteId;
           var session = videoChatSessionId;

           var startingCallback = function() {
               console.log('Video Chat Session Initialized.');
               /*if(MCServiceAPI.Site.isAudioVideoSupported()) {
                  console.log('Video Chat Audio/Video is supported');
               }*/
               console.log('Initiated Video Chat');
           };
           var startingErrorCallback = function(error) {
                console.log('Initialization failed with error, Code ' + error.code + ' Reason ' + error.reason);
                var eo = new RightNow.Event.EventObject(this, {data: {videoChatStatus: RightNow.Chat.Model.ChatVideoChatStatusCode.ERROR}});
                RightNow.Event.fire('evt_chatVideoChatStatusResponse', eo);
                removeVideoChatListeners();
           };
           //start
           if (this._lastUpdatedChatState === RightNow.Chat.Model.ChatState.CONNECTED) {
               MCServiceAPI.Site.start({ "apiKey": apiKey, "authToken": jwt, "session": session}, channels)
                  .then(startingCallback)
                  .catch(startingErrorCallback);
           }
        }
    },
    _videoChatDisconnect: function (type, args) {
        if (MCServiceAPI) {
            MCServiceAPI.Site.endSession({reason: 'AGENT_CRASHED'});
        }
    },
    _agentAbsentUpdateResponse: function (type, args) {
        if(this._inVideoChat){
            if (MCServiceAPI) {
                MCServiceAPI.Site.endSession({reason: 'AGENT_ABSENT'});
            }
            try {
                var eo = new RightNow.Event.EventObject(this, {data: {videoChatStatus: RightNow.Chat.Model.ChatVideoChatStatusCode.ABORTED}});
                RightNow.Event.fire('evt_chatVideoChatStatusResponse', eo);
            }
            catch(e) {
                console.log("Failed to fire videochatstatusresponse event.");
            }
        }
    },
    /*
     * Removes the video div from the DOM
     */
    _closeVideoPanel: function () {
        var videoPanel = document.getElementById('ll_webrtc_video');
        if(videoPanel) {
            var vcElement = videoPanel.parentNode;
            vcElement.parentNode.removeChild(vcElement);
        }
    }
});
