RightNow.Widgets.ChatPostMessage = RightNow.Widgets.extend({
    constructor: function(){
        // Local member variables section
        this.container = this.Y.one(this.baseSelector);
        this.input = this.Y.one(this.baseSelector + "_Input");
        this.isOffTheRecord = this.data.attrs.all_posts_off_the_record;
        this._errorDialog = null;
        this._vaMode = false;

        // Event subscription section. If no UI object exists (input), don't subscribe to any of the events since there's no UI object to update.
        if(this.input)
        {
            RightNow.Event.subscribe("evt_chatStateChangeResponse", this._onChatStateChangeResponse, this);
            RightNow.Event.subscribe("evt_chatSendButtonClickResponse", this.sendText, this);
            RightNow.Event.subscribe("evt_chatPostLengthExceededResponse", this._onChatPostLengthExceededResponse, this);
            RightNow.Event.subscribe("evt_chatEngagementParticipantAddedResponse", this._onChatEngagementParticipantAddedResponse, this);
            RightNow.Event.subscribe("evt_chatPostResponse", this._onChatPostResponse, this);
            this.input.on("valueChange", this._onValueChange, this);
            this.input.on("key", this._onEnterKey, "enter", this);
        }
    },

    /**
     * Handles the state of the chat has changed. Hides button if disconnected.
     * @param type string Event name
     * @param args object Event arguments
     */
    _onChatStateChangeResponse: function(type, args)
    {
        if(!RightNow.Event.fire("evt_handleChatStateChange", new RightNow.Event.EventObject(this, {data: args[0].data})))
            return;

        var currentState = args[0].data.currentState;
        var ChatState = RightNow.Chat.Model.ChatState;

        if(currentState === ChatState.CONNECTED)
        {
            this.input.set('disabled', false);
            RightNow.UI.show(this.container);
            if(this.data.attrs.initial_focus && this.input.focus)
            {
                top.window.focus();
                this.input.focus();
            }
        }
        else if(currentState === ChatState.CANCELLED || currentState === ChatState.DISCONNECTED
            || currentState === ChatState.REQUEUED)
        {
            this.input.set('disabled', true);
            RightNow.UI.hide(this.container);
        }
        else if(currentState === ChatState.RECONNECTING)
        {
            this.input.set('disabled', true);
        }
    },

    /**
     * valueChange event handler. Triggered when the value of the input box changes
     * as a result of a keystroke, mouse operation, or input method editor (IME)
     * input event. We use this event to send a potential activity signal request
     */
    _onValueChange: function(e)
    {
        var eo = new RightNow.Event.EventObject(this, {data: {
            keyEvent: e,
            inputValue: e.newVal,
            inputValueBeforeChange: e.prevVal,
            isOffTheRecord: this.isOffTheRecord
        }});

        RightNow.Event.fire("evt_chatPostMessageKeyUpRequest", eo);
    },

    /**
    * key event handler. Triggered when ENTER key is pressed .
    * We use this to determine if we should send the text
    * to the agent.
    * @param e object event object
    */
    _onEnterKey: function(e)
    {
        //if SHIFT key was depressed we just let it thru,
        //so user can insert a new line
        if(e.shiftKey)
            return;

        if(this.input.get('value') !== '\r\n')
            this.sendText();
        else
            this.input.set('value', "");

        // Input line placed incorrectly after enter key without this.
        e.preventDefault();

        if(this.data.attrs.mobile_mode)
            this.input.blur();
    },

    /**
    * Event that handles when the user has added text beyond allowable boundaries. Sets input to previous value before
    * offending keypress/paste.
    * @param type string Event name
    * @param eventObject object Event arguments
    */
    _onChatPostLengthExceededResponse: function(type, eventObject)
    {
        if(eventObject[0].w_id !== this.instanceID)
            return;

        this.input.set('value', eventObject[0].data.inputValueBeforeChange)
                    .set('disabled', true);
        if(this._errorDialog){
            this._errorDialog.show();
        }
        else{
            this._errorDialog = RightNow.UI.Dialog.messageDialog(RightNow.Interface.getMessage("THE_INPUT_IS_TOO_LONG_MSG"), {icon: "WARN", exitCallback: {fn: this._enableControls, scope: this}});
        }
    },

    _enableControls: function()
    {
        this.input.set('disabled', false).focus();
    },

    /**
    * Handle submit (send)
    */
    sendText: function()
    {
        var text = this.input.get('value');

        if(text.replace(/^\s*/, "").length == 0 || text.length > 349525)
            return;

        // need to encode ASCII 0-32 to avoid xml deserialization problems on the client
        // this shouldn't break UTF8 since no multi-byte UTF8 chars contain bytes without the high order bits set
        // also moved '>' and '<' handling here
        var ch, c, newText = "";
        for (i = 0; i < text.length; i++)
        {
            ch = text[i];
            c = text.charCodeAt(i);
            if (c == RightNow.UI.KeyMap.VTAB)
                newText += "\n";

            else if (c < 32 /* ASCII Control characters */ && c !== RightNow.UI.KeyMap.LINEFEED && c !== RightNow.UI.KeyMap.RETURN && c !== RightNow.UI.KeyMap.TAB)
            {
                newText += "&#00";
                newText += (c < 10) ? "0" + c.toString() : c.toString();
            }
            else
                newText += text.substr(i, 1);
        }
        text = newText;

        this.input.set('value', "");

        if(this._vaMode)
            this.input.set('disabled', true);

        var eo = new RightNow.Event.EventObject(this, {data: {
            messageBody: text,
            isEndUserPost: true,
            isOffTheRecord: this.isOffTheRecord
        }});

        RightNow.Event.fire("evt_chatPostMessageRequest", eo);

        if(!this.data.attrs.mobile_mode)
            this.input.focus();
    },

    /**
     * Makes the chat window the active window and puts
     * focus on the input control on incoming message posts.
     * @param type string Event name
     * @param args object Event arguments
     */
    _onChatPostResponse: function(type, args)
    {
        if(!args[0].data.isEndUserPost)
        {
            this.input.set('disabled', false);

            if(this.data.attrs.focus_on_incoming_messages)
            {
                top.window.focus();
                this.input.focus();
            }
            else if(this._vaMode)
            {
                this.input.focus();
            }
        }
    },

    /**
     * Sets whether the chat is in "virtual agent mode".
     * If in VA mode, only one post should be sent for each response.
     * @param {string} type Event name
     * @param {array} args Event arguments
     */
    _onChatEngagementParticipantAddedResponse: function(type, args)
    {
        this._vaMode = args[0].data.virtualAgent === undefined ? false : args[0].data.virtualAgent;
    }
});
