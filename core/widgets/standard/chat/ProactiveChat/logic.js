RightNow.Widgets.ProactiveChat = RightNow.Widgets.extend({
    constructor: function(){
        this._confirmDialog = null;
        this._searchDone;
        this._secondsDone;
        this._profileDone;
        this._waiting;
        this._visitorId;
        this._engagementEngineId;
        this._engagementEngineSessionId = null;
        this._estaraId;
        this._eeWidgetId = null;

        // If cookies are not enabled, return immediately. Cookies are required for proactive chat, and we don't need to needlessly hit API/Chat Service.
        if(!this._cookiesEnabled())
            return;

        this._eo = new RightNow.Event.EventObject(this, {data: {
            w_id: this.data.info.w_id,
            wait_threshold: this.data.attrs.wait_threshold,
            min_agents_avail: this.data.attrs.min_agents_avail,
            interface_id: this.data.js.interface_id,
            contact_email: this.data.js.contact_email,
            contact_fname: this.data.js.contact_fname,
            contact_lname: this.data.js.contact_lname,
            prod: this.data.js.prod,
            cat: this.data.js.cat,
            c_id: this.data.js.c_id,
            org_id: this.data.js.org_id,
            test: this.data.attrs.test,
            avail_type: this.data.attrs.min_agents_avail_type
        }});

        if(this.Y.one(this.baseSelector + '_ProactiveChatBox'))
        {
            if(this.data.attrs.initiate_by_event)
            {
                RightNow.Event.subscribe("evt_customProactiveInitialization", this._start, this);
                // There could be race condition where the widget loads after the page referencing it loads
                RightNow.Event.fire("evt_ProactiveReady");
                RightNow.Event.subscribe("evt_isProactiveReady", function(){
                    RightNow.Event.fire("evt_ProactiveReady");
                }, this);
            }
            else if(this.data.js.searches_to_do || this.data.js.profile_to_do || this.data.js.seconds_to_do)
            {
                this._start(null, [{data: { ignoreCookie: false, ignoreTriggers: false }}]);
            }
        }
    },

    /**
     * Initiates polling for availability and subsequent prompt if agents become available.
     * @param {String} type Event name
     * @param {Object} args Event arguments
     */
    _start: function(type, args){
        // 1. Verify that we have the minimum required args data.
        // 2. Don't offer chat if the end user is already in a chat session.
        // 3. Don't offer chat if the 'noChat' cookie is set and we are not ignoring it and not in test mode.
        // 4. Don't offer chat more than once if the custom launch event says so.
        if(!args[0] || !args[0].data || this.Y.Cookie.get("CHAT_SESSION_ID") ||
          (this.Y.Cookie.get('noChat') && !args[0].data.ignoreCookie && !this.data.attrs.test) ||
          (this._confirmDialog && args[0].data.offerOnlyOnce))
            return;

        // Subscribe to event fired on prod/cat change on pages such as the answer's "Advanced Search"
        RightNow.Event.subscribe("evt_productCategoryFilterSelected", this._onProdCatChanged, this);

        // Check searches trigger if available. If unavailable or if told to ignore triggers from
        // the custom launch event, just mark the searches check as done.
        if(this.data.js.searches_to_do && !args[0].data.ignoreTriggers)
        {
            if (this.data.js.searches >= this.data.attrs.searches)
            {
                this._searchDone = true;
            }
            else
            {
                this._searchDone = false;
                RightNow.Event.subscribe("evt_searchRequest", this._onSearchCountChanged, this);
            }
        }
        else
        {
            this._searchDone = true;
        }

        // Check profile trigger if available. If unavailable or if told to ignore triggers from
        // the custom launch event, just mark the profile check as done.
        if(this.data.js.profile_to_do && !args[0].data.ignoreTriggers)
        {
            if (this.data.js.profile)
            {
                this._profileDone = true;
            }
            else
            {
                this._profileDone = false;
            }
        }
        else
        {
            this._profileDone = true;
        }

        // Check delay trigger if available. If unavailable or if told to ignore triggers from
        // the custom launch event, just mark the delay check as done.
        if(this.data.js.seconds_to_do && !args[0].data.ignoreTriggers)
        {
            this._secondsDone = false;
            setTimeout("RightNow.Widgets.getWidgetInstance('" + this.instanceID + "').onSeconds()", this.data.attrs.seconds * 1000);
        }
        else
        {
            this._secondsDone = true;
        }

        this._visitorId = args[0].data.visitor_id;
        this._engagementEngineId = args[0].data.ee_id;
        this._estaraId = args[0].data.estara_id;
        this._engagementEngineSessionId = args[0].data.ee_session_id;
        this._eeWidgetId = args[0].data.instance_id;

        this._checkDoneStatus();
    },

    /**
     *Publishes PAC stats to DQA
     */
    _publishStats: function(statAction){
        RightNow.Ajax.CT.submitAction(RightNow.Ajax.CT.WIDGET_STATS, statAction);
    },

    /*
    * fire event if all status types are done
    *
    */
    _checkDoneStatus: function()
    {
        if(this._profileDone && this._searchDone && this._secondsDone)
        {
            this._waiting = true;
            if (this.data.attrs.test == 'true')
            {
                this._onQueueReceived(null, new Array(""));
            }
            else if(RightNow.Event.fire("evt_chatQueueRequest", this._eo))
            {
                delete this._eo.data.contact_fname;
                delete this._eo.data.contact_lname;
                delete this._eo.data.contact_email;
                RightNow.Ajax.makeRequest(this.data.attrs.get_chat_info_ajax, this._eo.data, {successHandler: this._onQueueReceived, scope: this, json: true, data: this._eo});
            }
        }
    },

    /**
     * Event handler for when the number of searches has been incremented
     * @param {String} type Event name
     * @param {Object} args Event arguments
     */
     _onSearchCountChanged: function(type, args)
    {
        this.data.js.searches++;
        if(this.data.js.searches >= this.data.attrs.searches)
        {
            RightNow.Event.unsubscribe("evt_searchRequest", this._onSearchCountChanged);
            this._searchDone = true;
            this._checkDoneStatus();
        }
    },

    /**
     * Event handler for when the number of seconds has been completed
     */
     onSeconds: function()
    {
        this._secondsDone = true;
        this._checkDoneStatus();
    },

    /**
     * Event handler for server sends response.
    * @param {Object} response Server response
    * @param {Object} origEventObject original event object
     */
     _onQueueReceived: function(response, origEventObject)
    {
        if(RightNow.Event.fire("evt_chatQueueResponse", {response: response, data: origEventObject}) && this._waiting)
        {
            if(!response && this.data.attrs.test === "true")
            {
                response = {
                    q_id: 1,
                    stats: {
                        availableSessionCount: this.data.attrs.min_agents_avail,
                        expectedWaitSeconds: this.data.attrs.wait_threshold
                    }
                };
            }
            else if(!response)
            {
                return;
            }

            this._waiting = false;

            //analyze the queue id and the chat stats
            if((response.q_id > 0 && response.stats.availableSessionCount >= this.data.attrs.min_agents_avail && response.stats.expectedWaitSeconds <= this.data.attrs.wait_threshold))
            {
                this._publishStats({w:this.data.js.dqaWidgetType.toString(), offers:1});

                var eo = new RightNow.Event.EventObject(this, {data: {
                    id: this._eeWidgetId,
                    name: 'ProactiveChat'
                }});
                RightNow.Event.fire("evt_PACChatOffered", eo);

                var handleYes = function()
                {
                    var pageUrl = this.data.attrs.chat_login_page,
                        Url = RightNow.Url;

                    pageUrl = Url.addParameter(pageUrl, 'pac', 1);
                    pageUrl = Url.addParameter(pageUrl, 'request_source', this.data.js.request_source);
                    pageUrl = Url.addParameter(pageUrl, 'p', this._eo.data.prod);
                    pageUrl = Url.addParameter(pageUrl, 'c', this._eo.data.cat);

                    var chatData = '';
                    chatData = this._addChatDataParam(chatData, 'referrerUrl', encodeURIComponent(window.location.href));
                    // Send the visitor ID and engagement engine ID obfuscated in the chat_data URL parameter (if available)
                    chatData = this._addChatDataParam(chatData, 'v_id', this._visitorId);
                    chatData = this._addChatDataParam(chatData, 'ee_id', this._engagementEngineId);
                    chatData = this._addChatDataParam(chatData, 'es_id', this._estaraId);
                    chatData = this._addChatDataParam(chatData, 'ee_s_id', this._engagementEngineSessionId);

                    if(response.rules !== undefined)
                    {
                        chatData = this._addChatDataParam(chatData, 'state', response.rules.state);
                        chatData = this._addChatDataParam(chatData, 'escalation', response.rules.escalation);
                    }

                    chatData = this._addChatDataParam(chatData, 'q_id', response.q_id);

                    if(chatData.length !== 0)
                        pageUrl = Url.addParameter(pageUrl, 'chat_data', RightNow.Text.Encoding.base64Encode(chatData));

                    if(this.data.attrs.auto_detect_incident)
                        pageUrl = Url.addParameter(pageUrl, 'i_id', Url.getParameter('i_id'));

                    // If any survey information exists, set it for the Chat control here
                    if(response.survey_data)
                    {
                        if(response.survey_data.send_id)
                        {
                            pageUrl = Url.addParameter(pageUrl, 'survey_send_id', response.survey_data.send_id);
                            pageUrl = Url.addParameter(pageUrl, 'survey_send_delay', response.survey_data.send_delay);

                            if(response.survey_data.send_auth)
                                pageUrl = Url.addParameter(pageUrl, 'survey_send_auth', response.survey_data.send_auth);
                        }

                        if(response.survey_data.comp_id && response.survey_data.comp_id != 0)
                        {
                            pageUrl = Url.addParameter(pageUrl, 'survey_comp_id', response.survey_data.comp_id);

                            if(response.survey_data.comp_auth)
                                pageUrl = Url.addParameter(pageUrl, 'survey_comp_auth', response.survey_data.comp_auth);
                        }

                        if(response.survey_data.term_id && response.survey_data.term_id != 0)
                        {
                            pageUrl = Url.addParameter(pageUrl, 'survey_term_id', response.survey_data.term_id);

                            if(response.survey_data.term_auth)
                                pageUrl = Url.addParameter(pageUrl, 'survey_term_auth', response.survey_data.term_auth);
                        };
                    }

                    var eo = new RightNow.Event.EventObject(this, {data: {
                        id: this._eeWidgetId,
                        name: 'ProactiveChat'
                    }});
                    RightNow.Event.fire("evt_PACChatAccepted", eo);

                    this._confirmDialog.hide();
                    this._publishStats({w:this.data.js.dqaWidgetType.toString(), accepts:1});
                    if(this.data.attrs.open_in_new_window)
                        window.open(pageUrl, 'chatLauncher','width=' + this.data.attrs.chat_login_page_width + ',height=' + this.data.attrs.chat_login_page_height + ',scrollbars=1,resizable=1');
                    else
                        Url.navigate(pageUrl);
                };

                var handleNo = function()
                {
                    this._confirmDialog.hide();
                    this._publishStats({w:this.data.js.dqaWidgetType.toString(), rejects:1});
                };

                var buttons = [
                        { text: RightNow.Interface.getMessage("YES_LBL"), handler: {fn: handleYes, scope: this}},
                        { text: RightNow.Interface.getMessage("NO_LBL"), handler: {fn: handleNo, scope: this}, isDefault: true }
                ];

                if(!this._confirmDialog) {
                    var dialogBody = this.Y.Node.create("<div>")
                        .addClass("rn_ProactiveChatConfirm")
                        .set("innerHTML", this.data.attrs.label_chat_question);
                    this._confirmDialog = RightNow.UI.Dialog.actionDialog(RightNow.Interface.getMessage("INFORMATION_LBL"), dialogBody, {"buttons" : buttons, 'dialogDescription' : 'rn_' + this.instanceID + '_ProactiveChatBoxDescription'});
                    this.Y.DOM.addClass(this._confirmDialog.id, 'rn_dialog');
                    RightNow.UI.Dialog.addDialogEnterKeyListener(this._confirmDialog, handleYes, this);
                }

                this.Y.Cookie.set("noChat","RNTLIVE", {path: "/"});
                this._confirmDialog.show();
            }
        }
    },

    _cookiesEnabled: function()
    {
        var cookieEnabled = (navigator.cookieEnabled === true) ? true : false;
        if (typeof navigator.cookieEnabled === "undefined" && !cookieEnabled){
            this.Y.Cookie.set("COOKIE_TEST", "RNT", {path: "/"});
            cookieEnabled = (this.Y.Cookie.get("COOKIE_TEST") !== null) ? true : false;
            this.Y.Cookie.remove("COOKIE_TEST", {path: "/"});
        }
        return cookieEnabled;
    },

    /**
     * Event handler for when the prod/cat search items on a page are changed
     * @param {String} type Event name
     * @param {Object} args Event arguments
     */
     _onProdCatChanged: function(type, args)
    {
        var prodCatType = args[0].data.data_type;
        var value = args[0].data.value;

        if (prodCatType.indexOf("Category") > -1)
            this._eo.data.cat = value;
        else // assume prod
            this._eo.data.prod = value;
    },

    /**
     * Adds key/value pair to the chatData parameter that's sent in the chat URL
     * @param {String} chatData The existing chatData string
     * @param {String} key The key to add
     * @param {String} value The value that corresponds to the key
     * @return {String}
     */
    _addChatDataParam: function(chatData, key, value)
    {
        // Make sure the chatData var at least exists. Set to empty string if not.
        if(chatData === undefined)
            chatData = '';

        // Check that value is set, and not empty
        if(value === undefined || value === null || value.length === 0)
            return chatData;

        if(chatData.length !== 0)
            chatData += '&';

        chatData += key + '=' + value;

        return chatData;
    }
});
