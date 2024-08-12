RightNow.Widgets.ChatTranscript = RightNow.Widgets.extend({
    constructor: function(){
        // Local member variables section
        this._transcriptContainer = this.Y.one(this.baseSelector);
        this._transcript = this.Y.one(this.baseSelector + '_Transcript');
        this._anchorRE = new RegExp(/(<a .*?>(.*?)<\/a>)/i);
        this._hrefRE = new RegExp(/href\s*=\s*['"](.+?)['"]/i);
        this._titleRE = new RegExp(/title\s*=\s*['"](.+?)['"]/i);
        this._styleRE = new RegExp(/style\s*=\s*('(.+?)'|"(.+?)")/i);
        this._urlRE = new RegExp(/((http[s]?:\/\/|ftp:\/\/)|(www\.)|(ftp\.))([^\s<>\.\/^{^}]+)\.([^\s<>^{^}]+)/i);
        this._quotedUrlRE = new RegExp(/['"]+((http[s]?:\/\/|ftp:\/\/)|(www\.)|(ftp\.))([^\s<>\.\/]+)\.([^\s<>]+)['"]+/i);
        this._tagRE = new RegExp(/(<\/?[\w]+[^>]*>)/i);
        this._tagBR = new RegExp(/(<\/?br\s*>)/ig);
        this._endUserName = '';
        this._messageIds = {};
        this._active = true;

        // Event subscription section. If no UI object exists (transcript), don't subscribe to any of the events since there's no UI object to update.
        if(this._transcript)
        {
            if(!this.data.attrs.mobile_mode)
            {
                RightNow.Event.subscribe("evt_chatCobrowseAcceptResponse", this._coBrowseAcceptResponse, this);
            }

            RightNow.Event.subscribe("evt_fileUploadUpdateResponse", this._fileUploadResponse, this);
            RightNow.Event.subscribe("evt_chatNotifyFattachUpdateResponse", this._fileNotifyResponse, this);
            RightNow.Event.subscribe("evt_chatCobrowseStatusResponse", this._coBrowseStatusResponse, this);
            RightNow.Event.subscribe("evt_chatStateChangeResponse", this._onChatStateChangeResponse, this);
            RightNow.Event.subscribe("evt_chatPostResponse", this._onChatPostResponse, this);
            RightNow.Event.subscribe("evt_chatEngagementParticipantAddedResponse", this._onChatEngagementParticipantAddedResponse, this);
            RightNow.Event.subscribe("evt_chatEngagementParticipantRemovedResponse", this._onChatEngagementParticipantRemovedResponse, this);
            RightNow.Event.subscribe("evt_chatEngagementConcludedResponse", this._onChatEngagementConcludedResponse, this);
            RightNow.Event.subscribe("evt_chatCobrowseInvitationResponse", this._coBrowseInvitationResponse, this);
            RightNow.Event.subscribe("evt_chatCoBrowsePremiumInvitationResponse", this._coBrowsePremiumInvitationResponse, this);
            RightNow.Event.subscribe("evt_chatReconnectUpdateResponse", this._reconnectUpdateResponse, this);
            RightNow.Event.subscribe("evt_chatAgentAbsentUpdateResponse", this._agentAbsentUpdateResponse, this);
            RightNow.Event.subscribe("evt_chatAgentStatusChangeResponse", this._onAgentStatusChangeResponse, this);
            RightNow.Event.subscribe("evt_chatPostCompletion", this._onChatPostCompletion, this);
            RightNow.Event.subscribe("evt_chatCoBrowsePremiumAcceptResponse", this._coBrowseAcceptResponse, this);
            RightNow.Event.subscribe("evt_chatDisconnectNotification", this._onChatDisconnect, this);

            RightNow.Event.subscribe("evt_chatVideoChatStatusResponse", this._videoChatStatusResponse, this);
            RightNow.Event.subscribe("evt_chatVideoChatInvitationResponse", this._videoChatInvitationResponse, this);
            RightNow.Event.subscribe("evt_chatVideoChatAcceptResponse", this._videoChatAcceptResponse, this);

            this._preloadImages();
            this._transcript.setAttribute("aria-live", "polite").setAttribute("role", "log");

            var videoInlay = this.Y.one(".inlay-video-container") ? this.Y.one(".inlay-video-container").ancestor() : null;
            if(window.oit && window.oit.allInlaysAreLoaded()) {
                this._onOITLoaded();
            } else {
                document.addEventListener('inlay-oracle-chat-video-loaded', this._onOITLoaded.bind(this));
            }
        }

        if(this.data.attrs.unread_messages_titlebar_enabled)
        {
            this._unreadCount = 0;
            this._windowFocused = document.hasFocus ? document.hasFocus() : false;
            this._baseTitle = document.title;
            var Event = this.Y.Event;
            if(this.Y.UA.ie > 0)
            {
                Event.attach("focusin", this._onApplicationFocus, document, this);
                Event.attach("focusout", this._onApplicationBlur, document, this);
            }
            else
            {
                Event.attach("focus", this._onApplicationFocus, window, this);
                Event.attach("blur", this._onApplicationBlur, window, this);
            }
        }

        if(this.data.attrs.is_persistent_chat)
        {
            this._ls = RightNow.Chat.LS;
            if(this._ls.isSupported)
            {
                this._ls.attachStoreEvent();
            }
            RightNow.Event.subscribe("evt_addChat", this._appendEJSToOtherChatWindow, this);
            RightNow.Event.subscribe("evt_notifyChatDisconnect", this._appendEJSToOtherChatWindow, this);
        }
    },

    /**
     * Adds a listener video inlay notifications.
     */
    _onOITLoaded: function(args) {
        document.addEventListener('inlay-oracle-chat-video-statusNotification', this._videoChatStatusResponseInlay.bind(this));
    },

    /**
     * Handles when chat session is terminated.
     * @param type string Event name
     * @param args object Event arguments
     */
    _onChatDisconnect: function(type, args) {
        if(this.data.attrs.is_persistent_chat)
        {
            this._active = false;
            var messages = [];
            var context = null;
            if(args[0].data.isUserDisconnect)
            {
                if(args[0].data.reason === 'IDLE_TIMEOUT')
                {
                    messages.push(RightNow.Interface.getMessage("DISCONNECTED_CHAT_DUE_INACTIVITY_MSG"));
                }
                else
                {
                    messages.push(this.data.attrs.label_you);
                    messages.push(this.data.attrs.label_have_disconnected);
                    context = args[0].data;
                }
            }
            else
            {
                var agent = args[0].data.agent;
                messages.push(agent.name);
                messages.push(this.data.attrs.label_has_disconnected);
            }
            messages.push(this.data.attrs.label_restart_chat_text);
            var postData = {
                attrs: null,
                messages: messages,
                context: context
            };
            var _postData = JSON.parse(JSON.stringify(postData));
            var key = this._ls._disconnectPrefix + new Date().getTime();
            _postData.chatWindowId = this._ls._thisWindowId;
            _postData.type = 'CHAT_DISCONNECT';
            this._ls.setItem(key, _postData);
            setTimeout(function() {
              this._ls.removeItem(key);
            }, 5000);
        }
    },

    /**
     * Handles the state of the chat has changed.
     * @param type string Event name
     * @param args object Event arguments
     */
    _onChatStateChangeResponse: function(type, args)
    {
        var currentState = args[0].data.currentState;
        var previousState = args[0].data.previousState;
        var ChatState = RightNow.Chat.Model.ChatState;
        var newMessage = null;

        if(currentState === ChatState.CONNECTED)
            RightNow.UI.show(this._transcriptContainer);
        else if(currentState === ChatState.RECONNECTING)
        {
            this._stateBeforeReconnect = previousState;
            if(previousState === ChatState.CONNECTED)
                newMessage = RightNow.Interface.getMessage("COMM_RN_LIVE_SERV_LOST_PLS_WAIT_MSG");
        }
        else if(currentState === ChatState.DISCONNECTED && (args[0].data.reason === 'RECONNECT_FAILED' || args[0].data.reason === 'ERROR'))
        {
            newMessage = RightNow.Interface.getMessage("COMM_RN_LIVE_SERV_LOST_CHAT_SESS_MSG");
            if(this.data.attrs.is_persistent_chat)
            {
                newMessage = null;
            }
        }

        if (currentState === ChatState.DISCONNECTED || currentState === ChatState.REQUEUED) {
            // notify videochat widget to end video chat
            this._transcript.all('.rn_VideoChatAction').remove();
        }

        if(currentState === ChatState.CONNECTED && previousState === ChatState.RECONNECTING)
            newMessage = RightNow.Interface.getMessage("CONNECTION_RESUMED_MSG");

        if(newMessage !== null)
        {
            this._appendEJSToChat(this.getStatic().templates.systemMessage, {
                attrs: this.data.attrs,
                messages: [newMessage],
                context: null
            });
        }
    },

    /**
    * Event received when a participant joins the chat. Adds note to transcript.
    * @param type string Event name
    * @param args object Event arguments
    */
    _onChatEngagementParticipantAddedResponse: function(type, args)
    {
        var agent = args[0].data.agent;
        var role = args[0].data.role;
        var message = "";

        if(role === "LEAD")
        {
            if(RightNow.Chat.UI.Util.hasLeaveScreenIssues())
            {
                this._appendEJSToChat(this.getStatic().templates.systemMessage, {
                    attrs: this.data.attrs,
                    messages: [this.data.attrs.label_leave_screen_warning],
                    context: null
                });
            }

            this._transcript.all('.rn_VideoChatAction').remove();
            message = ': ' + agent.greeting;
        }
        else
        {
            message = ' ' + this.data.attrs.label_has_joined_chat;
        }

        this._appendEJSToChat(this.getStatic().templates.participantAddedResponse, {
            template: 'participantAddedResponse',
            attrs: this.data.attrs,
            agentName: this._getAgentIdString(args[0].data.agent.name),
            role: role,
            message: message,
            createdTime: args[0].data.createdTime
        });
    },

    /**
    * Event received when participant leaves the chat. Adds note to transcript.
    * @param type string Event name
    * @param args object Event arguments
    */
    _onChatEngagementParticipantRemovedResponse: function(type, args)
    {
        var reason = args[0].data.reason;
        var agent = args[0].data.agent;

        if(!agent)
            return;

        this._transcript.all('.rn_VideoChatAction').remove();

        this._appendEJSToChat(this.getStatic().templates.systemMessage, {
            attrs: this.data.attrs,
            messages: [this._getAgentIdString(agent.name), (args[0].data.reason === RightNow.Chat.Model.ChatDisconnectReason.TRANSFERRED_TO_QUEUE ? this.data.attrs.label_has_disconnected : this.data.attrs.label_has_left_chat)],
            context: null
        });
    },
    /**
     * Handles adding a new chat post to the transcript.
     * @param type string Event name
     * @param args object Event arguments
     */
    _onChatPostResponse: function(type, args)
    {
        var message = args[0].data.messageBody;
        var messageId = args[0].data.messageId;
        var isEndUserPost = args[0].data.isEndUserPost;
        var postID;
        var name;
        if (isEndUserPost)
        {
            args[0].data.richText = true;
        }

        var escapeMessage = function (inputString, _this) {
            //making message text HTML safe, ensuring none of the characters are double escaped
            var txt = document.createElement("textarea");
            txt.innerHTML = inputString;
            return _this.Y.Escape.html(txt.value);
        };

        // Convert newlines into breaks and spaces into nonbreaking spaces
        var plainTextToRichText = function (input, _this) {
          var containsHtml = (input.indexOf('<') > -1);

          if (! containsHtml)
          {
            input = escapeMessage(input, _this);
          }
          return input;
        };

        // ignore duplicate messages
        if (messageId !== undefined && typeof(this._messageIds) !== "undefined") {
            if (this._messageIds[messageId] !== undefined)
                return;

            this._messageIds[messageId] = 1;
        }

        if (!this.data.attrs.mobile_mode && isEndUserPost === true)
        {
            var escapedMessage = this._escapeUnbalancedLessThanSignsAndTags(message);
            var escapingSuccessful = (escapedMessage.length > 0);

            if (args[0].data.richText)
            {
                message = escapingSuccessful ? escapedMessage : escapeMessage(message, this);
            }
            else
            {
                message = escapeMessage(message, this);
            }
            message = this._formatLinks(message);
        }
        else if (args[0].data.richText === undefined || args[0].data.richText === false)
        {
            message = escapeMessage(message.replace(/&/g, "&amp;"), this);
        }
        else if(!this.data.attrs.mobile_mode)
        {
            message = this._formatLinks(message);
        }

        // Just in case richText doesn't contain any markup...
        if (args[0].data.richText === true)
        {
            message = plainTextToRichText(message, this);
        }

        if(args[0].data.isOffTheRecord)
            message = this.data.attrs.label_off_the_record + ' ' + message;

        if(args[0].data.isEndUserPost)
        {
            postID = 'eup_' + messageId;
            this._setEndUserName(args);
            name = this._endUserName;
        }
        else
        {
            postID = args[0].data.serviceFinishTime;
            name = this._getAgentIdString(args[0].data.agent.name);
        }

        this._appendEJSToChat(this.getStatic().templates.chatPostResponse, {
                template: 'chatPostResponse',
                attrs: this.data.attrs,
                endUserName: name,
                agentName: name,
                message: this._convertNonBreakSpaces(this._convertNewlineCharToLineBreak(message)),
                createdTime: args[0].data.createdTime,
                context: args[0].data
        }, postID);
    },

    _setEndUserName: function(args)
    {
        // Only parse enduser name once; it won't change, and this function is hit often, so this tweak is worthwhile.
        if(!this._endUserName || this._endUserName === '')
        {
            var endUser = args[0].data.endUser;

            if(endUser.firstName === null && endUser.lastName === null)
            {
                if(endUser.email === null)
                    this._endUserName = this.data.attrs.label_enduser_name_default_prefix;
                else
                    this._endUserName = endUser.email;
            }
            else
            {
                if(endUser.firstName !== null && endUser.lastName !== null)
                {
                    var internationalNameOrder = RightNow.Interface.getConfig("intl_nameorder", "COMMON");
                    this._endUserName = internationalNameOrder ? endUser.lastName + " " + endUser.firstName : endUser.firstName + " " + endUser.lastName;
                }
                else if(endUser.firstName !== null)
                {
                    this._endUserName = endUser.firstName;
                }
                else
                {
                    this._endUserName = endUser.lastName;
                }

                this._endUserName += RightNow.Interface.getMessage("NAME_SUFFIX_LBL");

                // Ensure that any < or > characters are escaped to avoid vulnerabilities
                this._endUserName = this._endUserName.replace(/</g, "&lt;");
                this._endUserName = this._endUserName.replace(/>/g, "&gt;");
            }
        }
    },
    /**
    * Event received when a post has been successfuly sent
    * @param response object Event response object
    */
    _onChatPostCompletion: function(type, args)
    {
        var messageId = args[0];
        var timestamp = args[1];
        var post = this.Y.one('#eup_' + messageId);
        var insertNode;

        if(post)
        {
            post.set('id', timestamp);

            if(post.previous() && post.previous().get('id') > timestamp)
            {
                insertNode = post.previous();
                post.remove();
                insertNode.insert(post, "before");
            }
            else if(post.next() && post.next().id < timestamp)
            {
                insertNode = post.next();
                post.remove();
                insertNode.insert(post, "after");
            }
        }
    },
    /**
    * Event received when the engagement has been concluded. Adds note to transcript.
    * @param type string Event name
    * @param args object Event arguments
    */
    _onChatEngagementConcludedResponse: function(type, args)
    {
        var agent = args[0].data.agent;
        var messages = [];
        var context = null;

        this._transcript.all('.rn_CoBrowseAction').remove();
        this._transcript.all('.rn_VideoChatAction').remove();
        // agent should never be null if isUserDisconnect is null or false.
        if(args[0].data.isUserDisconnect)
        {
            if(args[0].data.reason === 'IDLE_TIMEOUT')
                messages.push(RightNow.Interface.getMessage("DISCONNECTED_CHAT_DUE_INACTIVITY_MSG"));
            else
            {
                messages.push(this.data.attrs.label_you);
                messages.push(this.data.attrs.label_have_disconnected);
                context = args[0].data;
            }
        }
        else
        {
            messages.push(this._getAgentIdString(agent.name));
            messages.push(this.data.attrs.label_has_disconnected);
        }

        if(this.data.attrs.is_persistent_chat)
        {
            messages.push(this.data.attrs.label_restart_chat_text);
        }

        this._appendEJSToChat(this.getStatic().templates.systemMessage, {
            attrs: this.data.attrs,
            messages: messages,
            context: context
        });
    },
    /**
    * Event received when a file attachment status update is received. Adds note to transcript.
    * @param type string Event name
    * @param args object Event arguments
    */
    _fileNotifyResponse: function(type, args)
    {
        this._appendEJSToChat(this.getStatic().templates.systemMessage, {
            attrs: this.data.attrs,
            messages: [this.data.attrs.label_file_attachment_started],
            context: null
        });
    },
    /**
    * Event received when a file attachment upload is completed or errored. Adds note to transcript.
    * @param type string Event name
    * @param args object Event arguments
    */
    _fileUploadResponse: function(type, args)
    {
        var attachmentInfo = args[0];
        var message = "";

        if(attachmentInfo.error !== 0 || attachmentInfo.errorMessage)
        {
            message = this.data.attrs.label_file_attachment_error;
        }
        else
        {
            var fileName = attachmentInfo.name;
            var fileSize = Math.round((attachmentInfo.size / 1024) * 100) / 100;
            message = this.data.attrs.label_file_attachment_received
                                .replace("{0}", fileName)
                                .replace("{1}", fileSize + 'KB');
        }

        this._appendEJSToChat(this.getStatic().templates.systemMessage, {
            attrs: this.data.attrs,
            messages: [message],
            context: null
        });
    },
    /**
    * Event received when an agent has gone absent. Adds note to transcript.
    * @param type string Event name
    * @param args object Event arguments
    */
    _agentAbsentUpdateResponse: function(type, args)
    {
        if(args[0].data.requeueSeconds)
            this._appendEJSToChat(this.getStatic().templates.systemMessage, {
                attrs: this.data.attrs,
                messages: [RightNow.Interface.getMessage("REQUEUED_APPROXIMATELY_0_MSG").
                                    replace("{0}", args[0].data.requeueSeconds)],
                context: null
            });
    },
    /**
    * Event received when a cobrowse session is being offered. Adds note with clickable links to transcript.
    * @param type string Event name
    * @param args object Event arguments
    */
    _coBrowseInvitationResponse: function(type, args)
    {
        var CoBrowseTypes = RightNow.Chat.Model.ChatCoBrowseType;
        type = args[0].data.modeType;
        var coBrowseUrl = args[0].data.coBrowseUrl;
        var agent = args[0].data.agent;

        if(this.data.attrs.mobile_mode)
        {
            RightNow.Widgets.ChatTranscript.sendCoBrowseResponse(false);
        }
        else
        {
            var message = "";
            if(type === CoBrowseTypes.SCREEN || type === CoBrowseTypes.SCREEN_POINTER)
                message = this.data.attrs.label_agent_requesting_view_desktop;
            else
                message = this.data.attrs.label_agent_requesting_control_desktop;

            this._appendEJSToChat(this.getStatic().templates.cobrowseInvitationResponse, {
                template: 'cobrowseInvitationResponse',
                attrs: this.data.attrs,
                message: message,
                agentName: agent.name,
                url: coBrowseUrl
            });
        }
    },
    /**
    * Event received when a cobrowse premium session is being offered. Adds note with clickable links to transcript.
    * @param type string Event name
    * @param args object Event arguments
    */
    _coBrowsePremiumInvitationResponse: function(type, args)
    {
        var agentEnvironment = args[0].data.agentEnvironment;
        var coBrowseSessionId = args[0].data.coBrowseSessionId;
        var agent = args[0].data.agent;

        this._appendEJSToChat(this.getStatic().templates.CoBrowsePremiumInvitationResponse, {
            template: 'CoBrowsePremiumInvitationResponse',
            attrs: this.data.attrs,
            message: this.data.attrs.label_agent_requesting_view_desktop,
            agentName: agent.name,
            agentEnvironment: agentEnvironment,
            coBrowseSessionId: coBrowseSessionId
        });
        this.Y.one(this.baseSelector).delegate('click', this.onAllowCoBrowsePremiumClick, 'a.rn_CoBrowsePremiumAllow', this);
        this.Y.one(this.baseSelector).delegate('click', this.onDeclineCoBrowsePremiumClick, 'a.rn_CoBrowsePremiumDecline', this);
    },
    /**
    * Event received when a cobrowse offer has been accepted. Adds note to transcript.
    * @param type string Event name
    * @param args object Event arguments
    */
    _coBrowseAcceptResponse: function(type, args)
    {
        var accepted = args[0].data.accepted;
        var message = "";

        this._transcript.all('.rn_CoBrowseAction').remove();

        if(accepted)
            message = this.data.attrs.label_initializing_screen_sharing_session;
        else
            message = this.data.attrs.label_screen_sharing_session_declined;

        this._appendEJSToChat(this.getStatic().templates.systemMessage, {
            attrs: this.data.attrs,
            messages: [message],
            context: null
        });
    },
    /**
    * Event received when the status of a cobrowse session has changed. Adds note regarding new status to transcript.
    * @param type string Event name
    * @param args object Event arguments
    */
    _coBrowseStatusResponse: function(type, args)
    {
        var ChatCoBrowseStatusCode = RightNow.Chat.Model.ChatCoBrowseStatusCode;
        var coBrowseStatus = args[0].data.coBrowseStatus;
        var message = "";

        switch(coBrowseStatus)
        {
            case ChatCoBrowseStatusCode.STARTED:
                message = this.data.attrs.label_screen_sharing_session_started;
                break;
            case ChatCoBrowseStatusCode.STOPPED:
                message = this.data.attrs.label_screen_sharing_session_ended;
                break;
            case ChatCoBrowseStatusCode.ERROR:
                // LiveLOOK recently changed the error data to: <code> - <string>. We want to use our own string.
                // Parse out the preceding <code> integer; since only values are 0 and 1, safely assume first char.
                var errorCode = parseInt(args[0].data.coBrowseData[0], 10);

                if(errorCode === 0)
                    message = this.data.attrs.label_java_not_detected;
                else if(errorCode === 1)
                    message = this.data.attrs.label_java_cert_rejected;
                break;
        }

        this._appendEJSToChat(this.getStatic().templates.systemMessage, {
            attrs: this.data.attrs,
            messages: [message],
            context: null
        });
    },
    /**
     * Event received when a video chat session is being offered. Adds note with clickable links to transcript.
     * @param type string Event name
     * @param args object Event arguments
     */
    _videoChatInvitationResponse: function(type, args)
    {
        var agent = args[0].data.agent;
        this.engagementID = args[0].data.engagementID;

        this._appendEJSToChat(this.getStatic().templates.VideoChatInvitationResponse, {
            attrs: this.data.attrs,
            message: RightNow.Interface.getMessage('AGENT_0_IS_OFFERING_VIDEO_CHAT_MSG'),
            agentName: agent.name
        });

        if(window.oit) {
            window.oit.fire(new CustomEvent('inlay-oracle-chat-video-offer', { detail: { offer: true} }));
        }
        this.Y.one(this.baseSelector).delegate('click', this.onAllowVideoChatClick.bind(this), 'a.rn_VideoChatAllow', this);
        this.Y.one(this.baseSelector).delegate('click', this.onDeclineVideoChatClick.bind(this), 'a.rn_VideoChatDecline', this);
    },
    /**
     * Event received when a videochat offer has been accepted. Adds note to transcript.
     * @param type string Event name
     * @param args object Event arguments
     */
    _videoChatAcceptResponse: function(type, args)
    {
        var accepted = args[0].data.accepted,
            message = "";

        this._transcript.all('.rn_VideoChatAction').remove();

        if(accepted)
            message = RightNow.Interface.getMessage("THE_VIDEO_CHAT_INVITATION_WAS_ACCEPTED_LBL");
        else
            message = RightNow.Interface.getMessage("THE_VIDEO_CHAT_INVITATION_WAS_DECLINED_LBL");

        this._appendEJSToChat(this.getStatic().templates.systemMessage, {
            attrs: this.data.attrs,
            messages: [message],
            context: null
        });
    },
    /**
     * Event received when the status of a videoChat session has changed. Adds note regarding new status to transcript.
     * @param type string Event name
     * @param args object Event arguments
     */
    _videoChatStatusResponse: function(type, args)
    {
        var chatVideoChatStatusCode = RightNow.Chat.Model.ChatVideoChatStatusCode;
        var videoChatStatus = args[0].data.videoChatStatus;
        var isRestoringTranscript = args[0].data.isRestoringTranscript;
        var message = "";

        switch(videoChatStatus)
        {
            case chatVideoChatStatusCode.ACCEPTED:
                if (isRestoringTranscript)
                {
                    this._transcript.all('.rn_VideoChatAction').remove();
                    return;
                }
                break;
            case chatVideoChatStatusCode.STARTED:
                message = RightNow.Interface.getMessage("THE_VIDEO_CHAT_SESSION_HAS_STARTED_LBL");
                if (!isRestoringTranscript)
                {
                    RightNow.Widgets.ChatTranscript.sendVideoChatStartedResponse();
                }
                break;
            case chatVideoChatStatusCode.STOPPED:
                message = RightNow.Interface.getMessage("THE_VIDEO_CHAT_SESSION_HAS_ENDED_LBL");
                if (((args[0].data.hasOwnProperty('senderType') && args[0].data.senderType !== 'AGENT') || ! args[0].data.hasOwnProperty('senderType')) && !isRestoringTranscript) {
                    RightNow.Widgets.ChatTranscript.sendVideoChatStoppedResponse();
                }
                break;
            case chatVideoChatStatusCode.ABORTED:
                message = RightNow.Interface.getMessage("VIDEO_ABORTED_DUE_CONNECTIVITY_ISSUES_LBL");
                if (!isRestoringTranscript)
                {
                    RightNow.Widgets.ChatTranscript.sendVideoChatAbortedResponse();
                }
                break;
            case chatVideoChatStatusCode.ERROR:
                message = RightNow.Interface.getMessage("VIDEO_ENCOUNTERED_DUE_LOCAL_PROBLEM_LBL");
                if (((args[0].data.hasOwnProperty('senderType') && args[0].data.senderType !== 'AGENT') || ! args[0].data.hasOwnProperty('senderType')) && !isRestoringTranscript) {
                  message = RightNow.Interface.getMessage("VIDEO_ENCOUNTERED_DUE_REMOTE_PROBLEM_LBL");
                  RightNow.Widgets.ChatTranscript.sendVideoChatErrorResponse();
                }
                break;
        }

        this._appendEJSToChat(this.getStatic().templates.systemMessage, {
            attrs: this.data.attrs,
            messages: [message],
            context: null
        });
    },

    /**
     * Event received from the video inlay when the status of a videoChat session has changed. Adds note regarding new status to transcript.
     * @param args object Event arguments
     */
    _videoChatStatusResponseInlay: function(args)
    {
        var chatVideoChatStatusCode = RightNow.Chat.Model.ChatVideoChatStatusCode;
        var videoChatStatus = args.detail.videoChatStatus;
        var isRestoringTranscript = args.detail.isRestoringTranscript;
        var clientResponsible = args.detail.clientResponsible;
        var fromAgent = args.detail.fromAgent;
        var logoff = args.detail.logoff;
        var message = "";

        switch(videoChatStatus)
        {
            case chatVideoChatStatusCode.ACCEPTED:
                if (isRestoringTranscript)
                {
                    this._transcript.all('.rn_VideoChatAction').remove();
                    return;
                }
                break;
            case chatVideoChatStatusCode.STARTED:
                if(clientResponsible && !isRestoringTranscript)
                    message = RightNow.Interface.getMessage("THE_VIDEO_CHAT_SESSION_HAS_STARTED_LBL");
                break;
            case chatVideoChatStatusCode.STOPPED:
                if(!fromAgent && !logoff)
                    message = RightNow.Interface.getMessage("THE_VIDEO_CHAT_SESSION_HAS_ENDED_LBL");
                break;
            case chatVideoChatStatusCode.ABORTED:
                message = RightNow.Interface.getMessage("VIDEO_ABORTED_DUE_CONNECTIVITY_ISSUES_LBL");
                break;
            case chatVideoChatStatusCode.ERROR:
                message = RightNow.Interface.getMessage("VIDEO_ENCOUNTERED_DUE_LOCAL_PROBLEM_LBL");
                if (clientResponsible && !isRestoringTranscript)
                  message = RightNow.Interface.getMessage("VIDEO_ENCOUNTERED_DUE_REMOTE_PROBLEM_LBL");
                break;
        }
        if(message !== "") {
            this._appendEJSToChat(this.getStatic().templates.systemMessage, {
                attrs: this.data.attrs,
                messages: [message],
                context: null
            });
        }
    },
    /**
    * Event received when a reconnect status update is triggered. Adds a note about the reconnection attempt status to the transcript.
    * @param type string Event name
    * @param args object Event arguments
    */
    _reconnectUpdateResponse: function(type, args)
    {
        if(this._stateBeforeReconnect === RightNow.Chat.Model.ChatState.CONNECTED)
            this._appendEJSToChat(this.getStatic().templates.systemMessage, {
                attrs: this.data.attrs,
                messages: [RightNow.Interface.getMessage("DISCONNECTION_IN_0_SECONDS_MSG")
                                           .replace("{0}", args[0].data.secondsLeft)],
                context: null
            });
    },
    /**
    * Event received when an agent's status is changed. Notes about the status change are added to the transcript.
    * @param type string Event name
    * @param args object Event arguments
    */
    _onAgentStatusChangeResponse: function(type, args)
    {
        var agent = args[0].data.agent;
        if(!agent)
            return;

        var message = null;
        if(agent.activityStatus === RightNow.Chat.Model.ChatActivityState.ABSENT)
            message = RightNow.Interface.getMessage("COMM_DISP_NAME_LOST_PLS_WAIT_MSG");
        else if(args[0].data.previousState === RightNow.Chat.Model.ChatActivityState.ABSENT)
            message = RightNow.Interface.getMessage("COMM_DISPLAY_NAME_RESTORED_MSG");

        if(message !== null)
            this._appendEJSToChat(this.getStatic().templates.agentStatusChangeResponse, {
                template: 'agentStatusChangeResponse',
                attrs: this.data.attrs,
                message: message,
                agentName: agent.name
            });
    },
    /**
    * Function is used to pre-load all images that could be used in the chat transcript. Avoids issues delays showing new images in transcript.
    * Also avoids a bug where connection is lost and an icon is used in the transcript.
    */
    _preloadImages: function()
    {
        var imageArray = [];

        imageArray.push(this.data.attrs.alert_icon_path);
        imageArray.push(this.data.attrs.agent_message_icon_path);
        imageArray.push(this.data.attrs.off_the_record_icon_path);
        imageArray.push(this.data.attrs.video_chat_icon_path);

        if(!this.data.attrs.mobile_mode)
            imageArray.push(this.data.attrs.cobrowse_icon_path);

        if(this.data.attrs.enduser_message_icon_path)
            imageArray.push(this.data.attrs.enduser_message_icon_path);

        for(var x = 0; x < imageArray.length; x++)
            eval("var imageObject" + x + " = new Image(); imageObject" + x + ".src = imageArray[x];");
    },

    /**
     * Handles adding a new chat post to the transcript.
     * @param postText object EJS object
     * @param postData object JSON object containing data to be passed to EJS
     * @param postID string (optional) If desired, an ID can be assigned to the created EJS by specifying this attribute
     */
    _appendEJSToChat: function(postText, postData, postID)
    {
        var isVaResponse = postData && postData.context && postData.context.hasOwnProperty('vaResponse');

        if (postData.hasOwnProperty('message') && !isVaResponse)
        {
            postData.message = this._sanitizeInput(postData.message);
        }
        var newEntry = this.Y.Node.create(new EJS({text: postText}).render(postData));
        var _postData;

        if(postID !== undefined)
        {
            newEntry.set("id", postID);
        }

        this._transcript.appendChild(newEntry);
        if(this.data.attrs.is_persistent_chat && this._ls.isSupported && this.Y.Cookie.get("CHAT_SESSION_ID"))
        {
            _postData = JSON.parse(JSON.stringify(postData));
            //don't store these properties in local storage
            delete _postData.attrs;
            if(_postData.context)
            {
                delete _postData.context.w_id;
                delete _postData.context.rn_contextData;
                delete _postData.context.rn_contextToken;
                delete _postData.context.rn_timestamp;
                delete _postData.context.rn_formToken;
            }
            var key = this._ls._transcriptPrefix + new Date().getTime(),
                chatSessionID = this.Y.Cookie.get("CHAT_SESSION_ID");

            _postData.chatWindowId = this._ls._thisWindowId;
            _postData.type = 'CHAT_TRANSCRIPT';
            if(_postData && _postData.createdTime)
            {
                var lastUpdatedKey = this._ls._lastUpdateKey + chatSessionID,
                    lastUpdated = this._ls.getItem(lastUpdatedKey);

                if(!lastUpdated || lastUpdated < _postData.createdTime)
                {
                    this._ls.setItem(key, _postData);
                    this._ls.bufferItem(chatSessionID, _postData);
                    this._ls.setItem(lastUpdatedKey, _postData.createdTime);
                }
            }
            else
            {
                this._ls.bufferItem(chatSessionID, _postData);
                this._ls.setItem(key, _postData);
            }

            var ls = this._ls;
            setTimeout(function() {
              ls.removeItem(key);
            }, 2500);
        }

        // Using scrollIntoView doesn't have consistent behavior across browsers (particularly IE). Use YUI Anim instead.
        var scrollAnim = new this.Y.Anim({
            node: this._transcriptContainer,
            to: {
                scroll: function(node) { return [0, node.get('scrollHeight')]; }
            }
        }).run();

        if(this.data.attrs.unread_messages_titlebar_enabled && !this._windowFocused)
        {
            document.title = '(' + (++this._unreadCount) + ') ' + this._baseTitle;
        }
    },

    /**
     * Handles adding a new chat post to the transcript when chat post is stored in the local storage.
     * @param type string Event name
     * @param args object Event arguments
     */
    _appendEJSToOtherChatWindow: function(type, args)
    {
        if((!this.Y.Cookie.get("CHAT_SESSION_ID") || this._active === false) && type !== 'evt_notifyChatDisconnect')
        {
            return;
        }
        if(type === 'evt_notifyChatDisconnect')
        {
           this._active = false;
        }
        var postText,
            postData = args[0].data,
            template = postData.template ? postData.template : '';

        postData.attrs = this.data.attrs;
        switch(template)
        {
            case 'chatPostResponse':
                postText = this.getStatic().templates.chatPostResponse;
                break;
            case 'participantAddedResponse':
                postText = this.getStatic().templates.participantAddedResponse;
                break;
            case 'agentStatusChangeResponse':
                postText = this.getStatic().templates.agentStatusChangeResponse;
                break;
            case 'CoBrowsePremiumInvitationResponse':
                postText = this.getStatic().templates.CoBrowsePremiumInvitationResponse;
                break;
            case 'systemMessage':
                postText = this.getStatic().templates.systemMessage;
                break;
        }
        if(postText === undefined)
        {
            postText = this.getStatic().templates.systemMessage;
            postData.messages = postData.messages || [];
        }

        var newEntry;
        if(postData && postData.context && postData.context.isEndUserPost && this._transcript.one('.rn_AgentTextPrefix'))
        {
            newEntry = this.Y.Node.create(new EJS({text: postText}).render(postData));
            this._transcript.appendChild(newEntry);
        }
        else
        {
            newEntry = this.Y.Node.create(new EJS({text: postText}).render(postData));
            this._transcript.appendChild(newEntry);
        }

        // Using scrollIntoView doesn't have consistent behavior across browsers (particularly IE). Use YUI Anim instead.
        var scrollAnim = new this.Y.Anim({
            node: this._transcriptContainer,
            to: {
                scroll: function(node) { return [0, node.get('scrollHeight')]; }
            }
        }).run();

        if(this.data.attrs.unread_messages_titlebar_enabled && !this._windowFocused)
        {
            document.title = '(' + (++this._unreadCount) + ') ' + this._baseTitle;
        }
    },

    /**
    * Format anchor tags and optionally text URLs into a clickable link in transcript.
    * @param text string Input text to process
    */
    _formatLinks: function(text)
    {
        var newText = '';
        var stringArray;
        var tempString = text;
        var titles = {};
        var hrefs = {};
        var descs = {};
        var tags = {};
        var styles = {};
        var quotedUrls = {};
        var aMatches = 0;
        var qMatches = 0;
        var tMatches = 0;
        var anchorMatch = "";

        while((anchorMatch = tempString.match(this._anchorRE)))
        {
            descs[aMatches] = anchorMatch[2];
            stringArray = tempString.split(anchorMatch[0]);

            var title = anchorMatch[0].match(this._titleRE);
            if(title != null)
                titles[aMatches] = title[1];

            var style = anchorMatch[0].match(this._styleRE);
            if(style != null)
                styles[aMatches] = style[2] || style[3];

            match = anchorMatch[0].match(new RegExp(/href\s*=\s*\S*javascript:/i));
            if (match)
            {
                hrefs[aMatches] = null;
                newText += '<a>' + descs[aMatches] + '</a>';
                aMatches++;
            }
            else {
                var href = hrefs[aMatches] = anchorMatch[0].match(this._hrefRE);
                if(href != null)
                {
                    hrefs[aMatches] = href[1];
                    if(!hrefs[aMatches].match(/^(http(s)?)/i) && !hrefs[aMatches].match(/^(mailto:)/i) && !hrefs[aMatches].match(/^(ftp(s)?)/i))
                        hrefs[aMatches] = "http://" + hrefs[aMatches];

                    newText += stringArray[0] + "{RNTAMATCH" + aMatches + "}";
                    aMatches++;
                }
                else
                {
                   hrefs[aMatches] = null;
                   newText += stringArray[0] + anchorMatch[0];
                   aMatches++;
                }
            }

            if(stringArray.length > 0)
            {
                stringArray.shift();
                tempString = stringArray.join(anchorMatch[0]);
            }
        }

        if(aMatches !== 0)
        {
            newText += tempString;
            tempString = newText;
            newText = "";
        }
        tempString = tempString.replace(this._tagBR,'{BR}');
        //Replace tags with tokens so we don't end up with tags inside tags
        while((urlMatch = tempString.match(this._tagRE)))
        {
             tags[tMatches] = urlMatch[0];
             tempString = tempString.replace(urlMatch[0], "{RNTTMATCH" + tMatches + "}");
             tMatches++;
        }

        //Replace quoted URL's so we don't modify those
        var urlMatch = "";
        while((urlMatch = tempString.match(this._quotedUrlRE)))
        {
            quotedUrls[qMatches] = urlMatch[0];
            tempString = tempString.replace(urlMatch[0], "{RNTQMATCH" + qMatches + "}");
            qMatches++;
        }

        while((urlMatch = tempString.match(this._urlRE)))
        {
            var url = urlMatch[0];
            stringArray = tempString.split(urlMatch[0]);

            if(urlMatch[0].match(/^ftp\./i))
                url = "ftp://" + urlMatch[0];
            else if(!urlMatch[0].match(/^(http(s)?|ftp)/i))
                url = "http://" + urlMatch[0];

            var replace = "<a href='" + url + "' target='_blank'>" + urlMatch[0] + "</a>";
            newText += stringArray[0] + replace;

            if(stringArray.length > 0)
            {
                stringArray.shift();
                tempString = stringArray.join(urlMatch[0]);
            }
        }

        newText += tempString;

        //Return the quoted URLs
        if(qMatches > 0)
        {
            for(var x = 0; x < qMatches; x++)
                newText = newText.replace("{RNTQMATCH" + x + "}", quotedUrls[x]);
        }

        if(tMatches > 0)
        {
            for(var y = 0; y < tMatches; y++)
                newText = newText.replace("{RNTTMATCH" + y + "}", tags[y]);
        }

        if(aMatches > 0)
        {
            for(var z = 0; z < aMatches; z++)
            {
                if(this.data.attrs.mobile_mode)
                {
                    newText = newText.replace("{RNTAMATCH" + z + "}", descs[z] == null ? hrefs[z] : descs[z] + ' (' + hrefs[z] + ')');
                }
                else
                {
                    if(style && style[3])
                    {
                        newText = newText.replace("{RNTAMATCH" + z + "}", '<a ' + (hrefs[z] === null ? '' : 'href="' + hrefs[z] + '" target="_blank" ') + (titles[z] == null ? '' : 'title="' + titles[z] + '" ') + (styles[z] === null ? '' : ' style="' + styles[z] + '"') + '>' + (descs[z] == null ? hrefs[z] : descs[z]) + '</a>');
                    }
                    else
                    {
                        newText = newText.replace("{RNTAMATCH" + z + "}", "<a " + (hrefs[z] === null ? "" : "href='" + hrefs[z] + "' target='_blank' ") + (titles[z] == null ? "" : "title='" + titles[z] + "' ") + (styles[z] === null ? "" : " style='" + styles[z] + "'") + ">" + (descs[z] == null ? hrefs[z] : descs[z]) + "</a>");
                    }
                }
            }
        }

        newText = newText.replace(/{BR}/g,"</br>");
        return newText;
    },
    _escapeUnbalancedLessThanSignsAndTags: function(inputString)
    {
        var bracketStack = [];
        var bracketIndexStack = [];
        var outputString = inputString;

        var startTagRegExp = new RegExp(/<([A-Za-z][A-Za-z0-9]*)\b/gi);
        var endTagRegExp = new RegExp(/<\/([A-Za-z][A-Za-z0-9]*)\b/i);
        var htmlTagsRegExp = new RegExp(/^<([A-Za-z][A-Za-z0-9]*)\b[\s\S]*<\/(\1)[\b]?\>/i);
        var emptyTagRegExp = new RegExp(/^<(area|base|basefont|br|col|embed|frame|hr|img|input|isindex|keygen|link|meta|param|source|track|wbr)\b/i);

        var substringReplace = function (outputString, replacementString, startIndex, endIndex)
        {
            return outputString.substring(0, startIndex) + replacementString + outputString.substring(endIndex);
        };

        try
        {
            for (var currentIndex = outputString.length; currentIndex >= 0; currentIndex--)
            {
                if (outputString[currentIndex] === ">")
                {
                    bracketStack.push(outputString[currentIndex]);
                    bracketIndexStack.push(currentIndex);
                }
                else if (outputString[currentIndex] === "<")
                {
                    if (bracketStack.length === 0)
                    {
                        outputString = substringReplace(outputString, "&lt;", currentIndex, currentIndex + 1);
                    }
                    else if (bracketStack[bracketStack.length - 1] === ">")
                    {
                        var closingBracketIndex = bracketIndexStack[bracketIndexStack.length - 1];

                        if (outputString.substr(currentIndex, 2) === "</")
                        {
                            var startTagMatches = outputString.substring(0, currentIndex).match(startTagRegExp);
                            var endTagMatches = outputString.substring(currentIndex, closingBracketIndex + 1).match(endTagRegExp);
                            var matchFound = false;
                            if (endTagMatches !== null && startTagMatches !== null)
                            {
                                for (var currentMatch = 0; currentMatch < startTagMatches.length; currentMatch++)
                                {
                                    if (startTagMatches[currentMatch].slice(1).toLowerCase() === endTagMatches[1].toLowerCase())
                                    {
                                        matchFound = true;
                                        break;
                                    }
                                }
                                if (matchFound)
                                {
                                    bracketStack.pop();
                                    bracketIndexStack.pop();
                                }
                                else
                                {
                                    outputString = substringReplace(outputString, "&lt;", currentIndex, currentIndex + 1);
                                }
                            }
                        }
                        else if (outputString.substr(currentIndex, 4) === '<!--')
                        {
                            if (outputString.substr(closingBracketIndex - 2, 3) === '-->')
                            {
                                bracketStack.pop();
                                bracketIndexStack.pop();
                            }
                            else
                            {
                                outputString = substringReplace(outputString, '&lt;', currentIndex, currentIndex + 1);
                            }
                        }
                        else if (outputString.substr(currentIndex, 2) === "<\?")
                        {
                            if (outputString.substr(closingBracketIndex - 1, 2) === "\?>")
                            {
                                bracketStack.pop();
                                bracketIndexStack.pop();
                            }
                            else
                            {
                                outputString = substringReplace(outputString, '&lt;', currentIndex, currentIndex + 1);
                            }
                        }
                        else if (outputString.substr(currentIndex, 9) === '<!DOCTYPE')
                        {
                            bracketStack.pop();
                            bracketIndexStack.pop();
                        }
                        else if (outputString.substr(closingBracketIndex - 1, 2) !== "/>")
                        {
                            var htmlTagsMatches = outputString.substring(currentIndex).match(htmlTagsRegExp);
                            var emptyTagMatch = outputString.substring(currentIndex).match(emptyTagRegExp);

                            if (htmlTagsMatches === null && emptyTagMatch == null)
                            {
                                outputString = substringReplace(outputString, "&lt;", currentIndex, currentIndex + 1);
                            }
                            else
                            {
                                bracketStack.pop();
                                bracketIndexStack.pop();
                            }
                        }
                        else
                        {
                            bracketStack.pop();
                            bracketIndexStack.pop();
                        }
                    }
                }
            }
        }
        catch (exception)
        {
            console.log("Something went wrong while escaping input '" + inputString + "'. Returning empty string.", exception);
        }
        return outputString;
    },
    _sanitizeInput: function (inputString) {
        var eventAttributeList = ['onabort', 'onactivate', 'onafterprint', 'onafterscriptexecute', 'onalerting', 'onanimationcancel',
            'onanimationend', 'onanimationiteration', 'onanimationstart', 'onaudioprocess', 'onauxclick', 'onbeforeactivate', 'onbeforedeactivate',
            'onbeforeprint', 'onbeforescriptexecute', 'onbeforeunload', 'onblocked', 'onblur', 'onbroadcast', 'onbusy', 'oncallschanged', 'oncanplay',
            'oncanplaythrough', 'oncardstatechange', 'oncfstatechange', 'onchange', 'onchargingchange', 'onchargingtimechange', 'onchecking', 'onclick',
            'onclose', 'oncompassneedscalibration', 'oncomplete', 'oncompositionend', 'oncompositionstart', 'oncompositionupdate', 'onconnected',
            'onconnecting', 'oncontextmenu', 'oncopy', 'oncut', 'ondblclick', 'ondeactivate', 'ondelivered', 'ondevicemotion', 'ondeviceorientation',
            'ondialing', 'ondischargingtimechange', 'ondisconnected', 'ondisconnecting', 'ondownloading', 'ondrag', 'ondragend', 'ondragenter',
            'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'ondurationchange', 'onemptied', 'onended', 'onerror', 'onfocus', 'onfocusin',
            'onfocusout', 'onfullscreenchange', 'onfullscreenerror', 'ongotpointercapture', 'onhashchange', 'onheld', 'onholding', 'onicccardlockerror',
            'oniccinfochange', 'onincoming', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onlevelchange', 'onload', 'onloadeddata',
            'onloadedmetadata', 'onloadend', 'onloadstart', 'onlostpointercapture', 'onmessage', 'onmousedown', 'onmouseenter', 'onmouseleave',
            'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onnoupdate', 'onobsolete', 'onoffline', 'ononline', 'onopen', 'onorientationchange',
            'onpagehide', 'onpageshow', 'onpaste', 'onpause', 'onplay', 'onplaying', 'onpointercancel', 'onpointerdown', 'onpointerenter', 'onpointerleave',
            'onpointerlockchange', 'onpointerlockerror', 'onpointermove', 'onpointerout', 'onpointerover', 'onpointerup', 'onpopstate', 'onpopuphidden',
            'onpopuphiding', 'onpopupshowing', 'onpopupshown', 'onprogress', 'onratechange', 'onreadystatechange', 'onreceived', 'onreset', 'onresize',
            'onresuming', 'onscroll', 'onseeked', 'onseeking', 'onselect', 'onsent', 'onshow', 'onstalled', 'onstatechange', 'onstkcommand', 'onstksessionend',
            'onstorage', 'onsubmit', 'onsuccess', 'onsuspend', 'ontimeout', 'ontimeupdate', 'ontouchcancel', 'ontouchend', 'ontouchmove', 'ontouchstart',
            'ontransitioncancel', 'ontransitionend', 'ontransitionrun', 'ontransitionstart', 'onunload', 'onupdateready', 'onupgradeneeded', 'onussdreceived',
            'onversionchange', 'onvisibilitychange', 'onvoicechange', 'onvolumechange', 'onvrdisplayactivate', 'onvrdisplayblur', 'onvrdisplayconnect',
             'ontoggle', 'oncancel', 'oncuechange', 'ondragexit', 'onformdata', 'onlanguagechange', 'onmessageerror', 'onrejectionhandled', 'onsecuritypolicyviolation', 'onslotchange', 'onunhandledrejection', 'onrepeat',
            'onvrdisplaydeactivate', 'onvrdisplaydisconnect', 'onvrdisplayfocus', 'onvrdisplaypresentchange', 'onwaiting', 'onwheel', 'xlink:href'];
        var blacklistedElementList = ['body', 'dialog', 'embed', 'form', 'iframe', 'input', 'link', 'object', 'marquee', 'script', 'style', 'math', 'meta'];
        var inputDOM = document.createElement('span');
        inputDOM.innerHTML = inputString;

        var sanitizeAttributes = function (element) {
            for (var attributeIndex = eventAttributeList.length - 1; attributeIndex >= 0; attributeIndex--) {
                if (element.hasAttribute(eventAttributeList[attributeIndex])) {
                    element.removeAttribute(eventAttributeList[attributeIndex]);
                }
            }
            if (element.hasAttribute("style")) {
               element.style.position = "";
            }
        };

        var sanitizeNode = function (rootElement) {
            if (rootElement.nodeType === 1) {
                var currentTagName = rootElement.tagName.toLowerCase();
                if (blacklistedElementList.indexOf(currentTagName) !== -1) {
                    if (['script', 'style'].indexOf(currentTagName) === -1) {
                        while (rootElement.hasChildNodes()) {
                            sanitizeNode(rootElement.firstChild);
                            // Extra check just in case the previous statement removed the first child node...
                            if (rootElement.firstChild) {
                                rootElement.parentElement.insertBefore(rootElement.firstChild, rootElement);
                            }
                        }
                    }
                    rootElement.parentElement.removeChild(rootElement);
                } else {
                    if (rootElement.hasAttributes()) {
                        sanitizeAttributes(rootElement);
                    }
                    if (rootElement.hasChildNodes()) {
                        for (var nodeIndex = rootElement.childNodes.length - 1; nodeIndex >= 0; nodeIndex--) {
                            sanitizeNode(rootElement.childNodes[nodeIndex]);
                        }
                    }
                }
            }
        };

        if (inputDOM.hasChildNodes()) {
            for (var nodeIndex = inputDOM.childNodes.length - 1; nodeIndex >= 0; nodeIndex--) {
                sanitizeNode(inputDOM.childNodes[nodeIndex]);
            }
        }
        return inputDOM.innerHTML;
    },
    _convertNonBreakSpaces: function (text) {
      return text.replace(/[ \t](?=[ \t])/g, '&nbsp;');
    },
    _convertNewlineCharToLineBreak: function (text) {
        // Remove newlines immediately following block-level elements (and html, head, title and body (and other elements that preferably should not be followed by newlines))
        // as their content already starts on a new line
        // See: https://developer.mozilla.org/en-US/docs/Web/HTML/Block-level_elements
        var blockLevelElementOpenRE = new RegExp(/\s*(<(address|article|aside|blockquote|body|br|col|colgroup|details|dialog|dd|div|dl|dt|fieldset|figcaption|figure|footer|h[1-6]|head|header|hgroup|hr|html|li|main|meta|nav|ol|p|pre|section|table|tbody|td|th|thead|title|tr|ul)[\b]*[^>]*>)\s*\n?/gi);
        var blockLevelElementCloseRE = new RegExp(/\s*(<\/(address|article|aside|blockquote|body|br|col|colgroup|details|dialog|dd|div|dl|dt|fieldset|figcaption|figure|footer|h[1-6]|head|header|hgroup|hr|html|li|main|meta|nav|ol|p|pre|section|table|tbody|td|th|thead|title|tr|ul)[\b]*>)\s*\n?/gi);

        return text.replace(blockLevelElementOpenRE, "$1").
            replace(blockLevelElementCloseRE, "$1").
            replace(/\n/g, '<br />');
     },
    _getAgentIdString: function(agentName)
    {
        return this.data.attrs.agent_id.replace(/{display_name}/g, agentName);
    },

    /**
    * Handles when the window gains focus.
    */
    _onApplicationFocus: function()
    {
        this._windowFocused = true;
        this._unreadCount = 0;

        document.title = this._baseTitle;
    },

    /**
    * Handles when the window loses focus.
    */
    _onApplicationBlur: function()
    {
        this._windowFocused = false;
    },
    /**
    * Event handler when CobrowsePremium Allow is clicked in the transcript.
    * @param e boolean event
    */
    onAllowCoBrowsePremiumClick: function (e)
    {
        e.halt();
        var target = e.currentTarget,
            agentEnvironment = target.getAttribute('data-agentEnvironment'),
            coBrowseSessionId = target.getAttribute('data-coBrowseSessionId');
        RightNow.Widgets.ChatTranscript.sendCoBrowsePremiumResponse(true, agentEnvironment, coBrowseSessionId);
    },
    /**
    * Event handler when CobrowsePremium Decline is clicked in the transcript.
    */
    onDeclineCoBrowsePremiumClick: function ()
    {
        RightNow.Widgets.ChatTranscript.sendCoBrowsePremiumResponse(false);
    },
    /**
    * Event handler when video chat Allow is clicked in the transcript.
    * @param e boolean event
    */
    onAllowVideoChatClick: function (e)
    {
        e.halt();
        var target = e.currentTarget;
        RightNow.Event.fire("evt_chatVideoChatAllowClicked",  e);

        target = e.currentTarget;
        var videoChatSessionId = target.getAttribute('data-videoChatSessionId');

        RightNow.Widgets.ChatTranscript.sendVideoChatResponse(this, true, videoChatSessionId);
    },
    /**
    * Event handler when Video Chat Decline is clicked in the transcript.
    */
    onDeclineVideoChatClick: function ()
    {
        RightNow.Widgets.ChatTranscript.sendVideoChatResponse(this, false);
    }
},
{
    /**
    * Static function called when user clicks a cobrowse action in the transcript.
    * @param accepted boolean Flag for whether cobrowse was accepted
    * @param coBrowseUrl string The URL for the cobrowse session
    */
    sendCoBrowseResponse: function(accepted, coBrowseUrl)
    {
        var eo = new RightNow.Event.EventObject(this, {data: {}});

        if(accepted)
        {
            eo.data = {coBrowseUrl: coBrowseUrl};
            RightNow.Event.fire('evt_chatCoBrowseAcceptRequest', eo);
        }
        else
        {
            RightNow.Event.fire('evt_chatCoBrowseDenyRequest', eo);
        }
    },
    /**
    * Static function called when user clicks a cobrowse action in the transcript.
    * @param accepted boolean Flag for whether cobrowse was accepted
    * @param agentEnvironment string The agentEnvironment for the cobrowse session
    * @param coBrowseSessionId string The coBrowseSessionId for the cobrowse session
    */
    sendCoBrowsePremiumResponse: function(accepted, agentEnvironment, coBrowseSessionId)
    {
        var eo = new RightNow.Event.EventObject(this, {data: {}});

        if(accepted)
        {
            eo.data = {agentEnvironment: agentEnvironment, coBrowseSessionId: coBrowseSessionId};
            RightNow.Event.fire('evt_chatCoBrowsePremiumAcceptRequest', eo);
        }
        else
        {
            RightNow.Event.fire('evt_chatCoBrowsePremiumDenyRequest', eo);
        }
    },
   /**
    * Static function called when user clicks a video chat action in the transcript.
    * @param accepted boolean Flag for whether video chat was accepted
    * @param videoChatSessionId string The URL for the video chat session
    */
    sendVideoChatResponse: function(scope, accepted, videoChatSessionId)
    {
        var eo = new RightNow.Event.EventObject(this, {data: {}});
        var chatSessionID = scope.Y.Cookie.get("CHAT_SESSION_ID");

        if(accepted)
        {
            eo.data = {videoChatSessionId: videoChatSessionId};
            RightNow.Event.fire('evt_chatVideoChatAcceptRequest', eo);
            if(window.oit) {
                window.oit.fire(new CustomEvent('inlay-oracle-chat-video-invitationResponse', { detail: { accepted: true, chatSessionId: chatSessionID, engagementID: scope.engagementID } }));
            }
        }
        else
        {
            RightNow.Event.fire('evt_chatVideoChatDenyRequest', eo);
            if(window.oit) {
                window.oit.fire(new CustomEvent('inlay-oracle-chat-video-invitationResponse', { detail: { accepted: false, chatSessionId: chatSessionID, engagementID: scope.engagementID } }));
            }
        }
    },
    /**
     * Static function called when a video chat starts.
    */
    sendVideoChatStartedResponse: function()
    {
        var eo = new RightNow.Event.EventObject(this, {data: {}});
        RightNow.Event.fire('evt_chatVideoChatStart', eo);
    },
    /**
     * Static function called when a video chat ends.
    */
    sendVideoChatStoppedResponse: function()
    {
        var eo = new RightNow.Event.EventObject(this, {data: {}});
        RightNow.Event.fire('evt_chatVideoChatStop', eo);
    },
    /**
     * Static function called when a video chat ends abnormally.
    */
    sendVideoChatAbortedResponse: function()
    {
        var eo = new RightNow.Event.EventObject(this, {data: {}});
        RightNow.Event.fire('evt_chatVideoChatAbort', eo);
    },
    /**
     * Static function called when a video chat receives error.
     * @param error string the error information.
     */
    sendVideoChatErrorResponse: function(error)
    {
        var eo = new RightNow.Event.EventObject(this, {data: {videoChatStatus: error}});
        RightNow.Event.fire('evt_chatVideoChatError', eo);
    }
});
