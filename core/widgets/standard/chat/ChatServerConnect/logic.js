RightNow.Widgets.ChatServerConnect = RightNow.Widgets.extend({
    constructor: function() {
        this._resumeSessionDialog = null;
        this._miscellaneousData = null;
        this._validChatRequest = true;
        this._connectionStatus = this.Y.one(this.baseSelector + "_ConnectionStatus");
        this._connectingIconElem = this.Y.one(this.baseSelector + "_ConnectingIcon");
        this._errorMessageDiv = this.Y.one(this.baseSelector + "_ErrorLocation");
        this._startPersistentChat = false;
        this._maxAbsentInterval = 600;
        this._maxAgentAbsentRetryCount = 18;
        this._maxUserAbsentRetryCount = 18;

        if(this.Y.one(this.baseSelector + "_Connector")) {
            RightNow.Event.subscribe("evt_chatEventBusInitializedResponse", this._validateChatParameters, this); // Note: This will only be received if IE and in IFRAME
            RightNow.Event.subscribe("evt_chatConnectResponse", this._onChatConnectResponse, this);
            RightNow.Event.subscribe("evt_chatFetchUpdateResponse", this._onFetchUpdateResponse, this);
            RightNow.Event.subscribe("evt_chatStateChangeResponse", this._onChatStateChangeResponse, this);
            RightNow.Event.subscribe("evt_chatSetParametersResponse", this._onChatSetParametersResponse, this);
            RightNow.Event.subscribe("evt_startPersistentChatRequest", this._onStartPersistentChatRequest, this);
            RightNow.Event.subscribe("evt_chatValidateParametersResponse", this._onChatValidateParametersResponse, this);
            RightNow.Event.subscribe("evt_chatCheckAnonymousResponse", this._onChatCheckAnonymousResponse, this);
        }

        if(this.data.attrs.is_persistent_chat) {
            this._ls = RightNow.Chat.LS;
        }
        // Fix for race condition where the widgets load before UI bus
        if(RightNow.Chat && RightNow.Chat.UI && RightNow.Chat.UI.EventBus !== null && RightNow.Chat.UI.EventBus.isEventBusInitialized !== undefined && RightNow.Chat.UI.EventBus.isEventBusInitialized()) {
            this._validateChatParameters();
        }
    },

    /**
    * Validates chat parameters.
    */
    _validateChatParameters: function() {
        var eventObject;

        this._setMiscellaneousData();

        eventObject = new RightNow.Event.EventObject(this, {data: {
                email: this.data.js.contactEmail || RightNow.Url.getParameter("Contact.Email.0.Address"),
                prod: RightNow.Url.getParameter("p"),
                cat: RightNow.Url.getParameter("c"),
                miscellaneousData: this._miscellaneousData,
                customFields: this.data.js.customFields
            }});

        RightNow.Event.fire("evt_chatValidateParametersRequest", eventObject);

        this._checkAnonymousRequest();
        this._displayErrors();
        this._setChatParameters();
    },

    /**
     * Event handler for when a response is received from a Chat Parameters
     * validation request
     * @param type string Event name
     * @param args object Event arguments
     */
    _onChatValidateParametersResponse: function(type, args) {
        var eventObject = args[0];
        this._validChatRequest = eventObject.data.valid;
        if(this._validChatRequest) {
            return;
        }

        if(!this._errorMessages)
            this._errorMessages = [];
        this._errorMessages.push(this.data.attrs.label_validation_fail);
    },

    /**
    * Checks anonymous chat requests.
    */
    _checkAnonymousRequest: function() {
        if(!this.data.attrs.first_name_required && !this.data.attrs.last_name_required && !this.data.attrs.email_required)
            return;

        var eventObject = new RightNow.Event.EventObject(this, {data: {
                firstName: this.data.js.contactFirstName || RightNow.Url.getParameter("Contact.Name.First") || RightNow.Url.getParameter("contacts.first_name"),
                firstNameRequired: this.data.attrs.first_name_required,
                lastName: this.data.js.contactLastName || RightNow.Url.getParameter("Contact.Name.Last") || RightNow.Url.getParameter("contacts.last_name"),
                lastNameRequired: this.data.attrs.last_name_required,
                email: this.data.js.contactEmail || RightNow.Url.getParameter("Contact.Email.0.Address") || RightNow.Url.getParameter("contacts.email"),
                emailRequired: this.data.attrs.email_required
        }});

        RightNow.Event.fire("evt_chatCheckAnonymousRequest", eventObject);
    },

    /**
     * Event handler for when a response is received from a Check Anonymous Chat Request
     * @param type string Event name
     * @param args object Event arguments
     */
    _onChatCheckAnonymousResponse: function(type, args) {
        var eventObject = args[0];
        if(!eventObject.data.anonymousRequest)
            return;

        if(!this._errorMessages)
            this._errorMessages = [];
        this._errorMessages.push(this.data.attrs.label_prevent_anonymous_chat);

        this._validChatRequest = false;
    },

    /**
     * Displays any errors we built up so far
     */
    _displayErrors: function()
    {
        if(this._validChatRequest || !this._errorMessages || this._errorMessages.length <= 0)
            return;

        //create a new node that will contain the html for displaying the errors
        var errorMessageList = this.Y.Node.create(new EJS({text: this.getStatic().templates.errorMessageList}).render({
                    errors: this._errorMessages,
                    attrs: this.data.attrs
        }));

        //append the node as a child to the containing div and display the parent
        this._errorMessageDiv.appendChild(errorMessageList);
        this._errorMessageDiv.addClass("rn_MessageBox").addClass("rn_ErrorMessage").removeClass("rn_Hidden").scrollIntoView();

        RightNow.UI.hide(this._connectionStatus);
    },

    /**
    * Some chat connection parameters need to be given to the chat javascript. This function accomplishes that.
    */
    _setChatParameters: function() {
        if(!this._validChatRequest)
            return;

        var surveyCompID = RightNow.Url.getParameter("survey_comp_id");
        var surveyTermID = RightNow.Url.getParameter("survey_term_id");
        var surveyCompAuth = RightNow.Url.getParameter("survey_comp_auth");
        var surveyTermAuth = RightNow.Url.getParameter("survey_term_auth");

        var eventObject = new RightNow.Event.EventObject(this, {data: {
                    connectionData: {
                        absentInterval: Math.min(RightNow.Interface.getConfig("ABSENT_INTERVAL", "RNL"), this._maxAbsentInterval),
                        absentRetryCount: Math.min(RightNow.Interface.getConfig("USER_ABSENT_RETRY_COUNT", "RNL"), this._maxUserAbsentRetryCount),
                        chatServerHost: RightNow.Interface.getConfig("SRV_CHAT_HOST", "RNL"),
                        chatServerPort: RightNow.Interface.getConfig("SERVLET_HTTP_PORT", "RNL"),
                        dbName: RightNow.Interface.getConfig("DB_NAME", "COMMON"),
                        useHttps: window.location.protocol.indexOf('https:') === 0
                    },
                    surveyBaseUrl: this.data.js.maUrl,
                    agentAbsentRetryCount: Math.min(RightNow.Interface.getConfig("AGENT_ABSENT_RETRY_COUNT", "RNL"), this._maxAgentAbsentRetryCount),
                    terminateChatSessionString: this.data.attrs.label_terminate_session
        }});

        if(surveyCompID)
            eventObject.data.surveyCompID = surveyCompID;

        if(surveyTermID)
            eventObject.data.surveyTermID = surveyTermID;

        if(surveyCompAuth)
            eventObject.data.surveyCompAuth = surveyCompAuth;

        if(surveyTermAuth)
            eventObject.data.surveyTermAuth = surveyTermAuth;

        RightNow.Event.fire("evt_chatSetParametersRequest", eventObject);
    },

    _onStartPersistentChatRequest: function(type, args) {
        this._startPersistentChat = true;
    },

    _onChatSetParametersResponse: function(type, args) {
        var that = this;
        var boundConnect = function () {
            that._connect();
        };
        if(!RightNow.Interface.getConfig("MOD_VIDEO_CHAT_ENABLED") || window.MCServiceAPI !== undefined) {
            this._connect();
        } else if(window.oit && window.oit.allInlaysAreLoaded()){
            this._connect();
        } else {
            this.authenticateChatDelayHandle = this.Y.later(5000, this, function() {
                that._connect();
                document.removeEventListener('inlay-oracle-chat-video-loaded', boundConnect);
            });
            document.addEventListener('inlay-oracle-chat-video-loaded', boundConnect);
        }
    },

    /**
    * Initiates connection to the Chat Service.
    * @param resume boolean Determines whether to resume an existing end user session
    */
    _connect: function(resume, type) {
        if(this.authenticateChatDelayHandle) {
            this.authenticateChatDelayHandle.cancel();
        }
        var subject = RightNow.Url.getParameter('Incident.Subject') || RightNow.Url.getParameter("incidents.subject");

        // Subject can come from the URL or from posted parameter. Check for both.
        if(subject === null || subject === '') {
            subject = this.data.js.postedSubject;
        }
        if(this.data.attrs.is_persistent_chat) {
            var udata = {};
            if(this._startPersistentChat) {
                udata.s = subject = this.Y.one('#rn_PersistentChatLaunchForm input[name="Incident.Subject"]').get('value');
                if(!this.data.attrs.is_anonymous && !RightNow.Profile.isLoggedIn()) {
                    udata.fn = this.data.js.contactFirstName = this.Y.one('#rn_PersistentChatLaunchForm input[name="Contact.Name.First"]').get('value');
                    udata.ln = this.data.js.contactLastName = this.Y.one('#rn_PersistentChatLaunchForm input[name="Contact.Name.Last"]').get('value');
                    udata.em = this.data.js.contactEmail = this.Y.one('#rn_PersistentChatLaunchForm input[name="Contact.Emails.PRIMARY.Address"]').get('value');
                }
                if(RightNow.Profile.isLoggedIn()) {
                    udata.fn = this.data.js.contactFirstName;
                    udata.ln = this.data.js.contactLastName;
                    udata.em = this.data.js.contactEmail;
                }
                this._ls.setItem(this._ls._udataKey, udata);
            }
            else {
                try {
                    udata = this._ls.getItem(this._ls._udataKey)
                    subject = udata.s;
                    this.data.js.contactFirstName = udata.fn ? udata.fn : undefined;
                    this.data.js.contactLastName = udata.ln ? udata.ln : undefined;
                    this.data.js.contactEmail = udata.em ? udata.em : undefined;
                } catch(e) {}
            }
        }

        var mediaInfo = [];
        if(typeof MCServiceAPI !== "undefined" || (window.oit && window.oit.MCServiceAPI)) {
            if(!window.MCServiceAPI) {
                MCServiceAPI = window.oit.MCServiceAPI; //attaching the MCServiceAPI global from the inlay iframe to the host window
            }
            var envError = MCServiceAPI.Channels.webrtc_video.environmentErrorReason;
            if(envError && envError.length > 0) {
                mediaInfo.push({ "type" : "VIDEO", "environmentErrorList" : MCServiceAPI.Channels.webrtc_video.environmentErrorReason });
            }
            else {
                mediaInfo.push({ "type" : "VIDEO", "environmentErrorList" : [] });
            }
        }

        // convert custom fields to a type that will be sent over POST
        var customFields = {};
        for (var key in this._miscellaneousData) customFields[key] = this._miscellaneousData[key];

        var isCoBrowsePremiumSupported = typeof CoBrowseLauncher !== "undefined" && CoBrowseLauncher.isEnvironmentSupported(CoBrowseLauncher.getEnvironment());
        // AJAX call to authenticate with the chat server - need to do this for both logged-in and anonymous users
        // since it gives us the JWT we need to pass to the chat server
        var eventObject = new RightNow.Event.EventObject(this, {data: {
            w_id: this.data.info.w_id,
            interfaceID: this.data.js.interfaceID,
            firstName: this.data.js.contactFirstName || RightNow.Url.getParameter("Contact.Name.First") || RightNow.Url.getParameter("contacts.first_name"),
            lastName: this.data.js.contactLastName || RightNow.Url.getParameter("Contact.Name.Last") || RightNow.Url.getParameter("contacts.last_name"),
            email: this.data.js.contactEmail || RightNow.Url.getParameter("Contact.Email.0.Address") || RightNow.Url.getParameter("contacts.email"),
            subject: subject,
            prod: this.data.js.postedProduct || RightNow.Url.getParameter("p") || RightNow.Url.getParameter("incidents.prod"),
            cat: this.data.js.postedCategory || RightNow.Url.getParameter("c") || RightNow.Url.getParameter("incidents.cat"),
            resume: resume,
            queueID: RightNow.Url.getParameter("q_id"),
            requestSource: this.data.js.requestSource,
            surveySendID: RightNow.Url.getParameter("survey_send_id"),
            surveySendDelay: RightNow.Url.getParameter("survey_send_delay"),
            surveySendAuth: RightNow.Url.getParameter("survey_send_auth"),
            miscellaneousData: this._miscellaneousData,
            customFields: JSON.stringify(customFields),
            incidentID: RightNow.Url.getParameter("i_id"),
            routingData: this.data.js.chat_data || RightNow.Url.getParameter("chat_data"),
            referrerUrl: this._getReferrerUrl(),
            coBrowsePremiumSupported: typeof CoBrowseLauncher !== "undefined" && CoBrowseLauncher.isEnvironmentSupported(CoBrowseLauncher.getEnvironment()) ? 0 : 1,
            mediaList: JSON.stringify(mediaInfo)
        }});
    
        if(this.data.js.f_tok){
            RightNow.Event.subscribe("evt_formTokenUpdate", RightNow.Widgets.onFormTokenUpdate, this);
            // Get a new f_tok value on each ajax request
            RightNow.Event.fire("evt_formTokenRequest", new RightNow.Event.EventObject(this, {data:{formToken:this.data.js.f_tok}}));

            eventObject.data.f_tok = this.data.js.f_tok;
        }

        if(!this.data.js.isSpider) {
            if(RightNow.Event.fire("evt_chatConnectAuthenticate", eventObject)) {
                RightNow.Ajax.makeRequest(this.data.attrs.authenticate_chat_ajax, eventObject.data, {
                    scope: this,
                    data: eventObject,
                    json: true,
                    successHandler: function (response) {
                        // after successful authentication, we just need to pass the JWT to the chat server
                        var connectEventObject = new RightNow.Event.EventObject(this, { data: {
                            jwt: response.jwt,
                            pool: response.pool,
                            // the user info is not passed to the chat server, but is used internally by RightNow.Chat.Controller
                            firstName: eventObject.data.firstName,
                            lastName: eventObject.data.lastName,
                            email: eventObject.data.email
                        }});
                        RightNow.Event.fire("evt_chatConnectRestRequest", connectEventObject);
                    }
                });
            }
        }
    },

    /**
    * Parses POST and URL parameters and sets the miscellaneous data variables
    * such as incident custom fields. URL parameters will override POST parameters.
    */
    _setMiscellaneousData: function() {
        if(this._miscellaneousData)
            return;

        this._miscellaneousData = [];
        for(var customFieldID in this.data.js.customFields) {
            var customField = this.data.js.customFields[customFieldID],
                columnName = customField.col_name.split("c$")[1],
                postedCustomFieldName = "Incident_CustomFields_c_" + columnName,
                urlCustomFieldName = "Incident.CustomFields.c." + columnName,
                url2CustomFieldName = "incidents.c$" + columnName,
                postedCustomFields = this.data.js.postedCustomFields || {},
                Url = RightNow.Url,
                customFieldValue = postedCustomFields[postedCustomFieldName] || Url.getParameter(urlCustomFieldName) || Url.getParameter(url2CustomFieldName);

            //if this is a date/date time, we have to assemble all moving parts.
            //that's how CONNECT coupled with native form POST works
            if(customField.data_type === this.data.js.dateField || customField.data_type === this.data.js.dateTimeField) {
                if(customFieldValue === null) {
                    var year = postedCustomFields[postedCustomFieldName + "_year"] || Url.getParameter(urlCustomFieldName + "_year"),
                        month = postedCustomFields[postedCustomFieldName + "_month"] || Url.getParameter(urlCustomFieldName + "_month"),
                        day = postedCustomFields[postedCustomFieldName + "_day"] || Url.getParameter(urlCustomFieldName + "_day"),
                        hour = postedCustomFields[postedCustomFieldName + "_hour"] || Url.getParameter(urlCustomFieldName + "_hour"),
                        minute = postedCustomFields[postedCustomFieldName + "_minute"] || Url.getParameter(urlCustomFieldName + "_minute");

                    // year is required for both date datatypes. If year is null, then the user didn't add a value.
                    if (year !== null)
                        customFieldValue = year || month || day ? [[year, month, day].join("-"), hour || minute ? [hour, minute].join(":") : ""].join(" ") : null;
                }

                if(customFieldValue !== null) {
                    this._miscellaneousData[urlCustomFieldName] = moment(customFieldValue, 'YYYY-MM-DD hh:mm:ss', false).format();
                }

                continue;
            }
            else if(customField.data_type === this.data.js.radioField) {
                // If "display_as_checkbox" attribute on widget is true, this will be the string "true" or "false". Adjust.
                customFieldValue = customFieldValue === "true" ? "1" : customFieldValue === "false" ? "0" : customFieldValue;
            }

            if(customFieldValue !== null)
                this._miscellaneousData[urlCustomFieldName] = customFieldValue;
        }
    },

    /**
     * get the ReferrerUrl
     */
    _getReferrerUrl: function() {
        var referrerUrl;
        if (this.data.js.referrerUrl != null) {
            referrerUrl = this.data.js.referrerUrl;
        }
        else {
            var chatData = RightNow.Text.Encoding.base64Decode(RightNow.Url.getParameter("chat_data"));
            var dataValues = chatData.split('&');
            for(var index = 0; index < dataValues.length; index++) {
                var value = dataValues[index].split('=');
                if(value[0] === "referrerUrl") {
                    referrerUrl = decodeURIComponent(value[1]);
                    break;
                }
            }
            if (!referrerUrl) {
                referrerUrl = document.referrer;
                // Try document.referrer first. If that's undefined, try window.opener.location (pop-up). If that fails, just leave undefined.
                if (!referrerUrl && window.opener && window.opener.location) {
                    try {
                        referrerUrl = window.opener.location.href;
                    } catch(e) {
                    }
                }
            }
        }
        return referrerUrl;
    },

    /**
     * Event handler for when a response is received from a Chat Connect Request
     * @param type string Event name
     * @param args object Event arguments
     */
    _onChatConnectResponse: function(type, args) {
        var eventObject = args[0];
        var messageElement = this.Y.one(this.baseSelector + "_Message");

        RightNow.UI.hide(this._connectingIconElem);

        if(eventObject.data.connected) {
            if(messageElement)
                messageElement.set("innerHTML", this.data.attrs.label_connection_success);
        }
        else if(messageElement) {
            messageElement.set("innerHTML", this.data.attrs.label_connection_fail);
        }

        if(eventObject.data.connected && eventObject.data.existingSession) {
            //existing session detected.
            if(this.data.attrs.is_persistent_chat) {
                this._connect(true);
            }
            else {
                this._displayResumeSessionDialog();
                return;
            }
        }

        if(eventObject.data.connected) {
            //fire GETUPDATE request
            this._fetchUpdate();
        }
    },

    /*
     * Creates and displays a dialog to the user asking whether they would like
     * to resume existing session
     */
    _displayResumeSessionDialog: function() {
        //set up buttons and event handlers
        var buttons = [ { text: RightNow.Interface.getMessage("OK_LBL"), handler: {fn: this._resumeSession, scope: this}, isDefault: true },
                        { text: RightNow.Interface.getMessage("CANCEL_LBL"), handler: {fn: this._startNewSession, scope: this}, isDefault: false } ];
        var dialogBody = this.Y.Node.create("<div>")
                                    .addClass("rn_dialogLeftAlign")
                                    .addClass("rn_ChatResumeSessionDialog")
                                    .set("innerHTML", RightNow.Interface.getMessage("EXISTING_CHAT_SESS_FND_RESUME_SESS_MSG"));
        this._resumeSessionDialog = RightNow.UI.Dialog.actionDialog(RightNow.Interface.getMessage("EXISTING_CHAT_SESSION_LBL"), dialogBody, {"buttons" : buttons});
        RightNow.UI.Dialog.addDialogEnterKeyListener(this._resumeSessionDialog, this._resumeSession, this);
        this._resumeSessionDialog.show();
    },

    /*
     * Resumes an existing end user chat session
     */
    _resumeSession: function() {
        this._resumeSessionDialog.hide();
        this._connect(true);
    },

    /*
     * Cancels existing session and starts a new end user chat session
     */
    _startNewSession: function() {
        this._resumeSessionDialog.hide();
        this._connect(false);
    },

    /*
     * Fires GETUPDATE requests
     */
    _fetchUpdate: function() {
        RightNow.Event.fire("evt_chatFetchUpdateRequest", new RightNow.Event.EventObject(this, {data: {}}));
    },

    /*
     * Handler for Fetch Update Response. Fires the next GETUPDATE request
     */
    _onFetchUpdateResponse: function() {
        this._fetchUpdate();
    },

    /**
    * Listener for Chat State Change notifications.
    * @param type string Event name
    * @param args object Event arguments
    */
    _onChatStateChangeResponse: function(type, args) {
        RightNow.UI.hide(this.baseSelector);
    }
});
