RightNow.Widgets.EmailAnswerLink = RightNow.Widgets.extend({
    constructor: function() {
        this._dialogElement = this.Y.one(this.baseSelector + '_EmailAnswerLinkForm');

        if(this._dialogElement)
        {
            this.Y.Event.attach("click", this._onEmailLinkClick, this.baseSelector + "_Link", this);
        }

        // subscribe to the event for update in status to a social question
        RightNow.Event.subscribe('evt_inlineModerationStatusUpdate', this._onStatusUpdate, this);
    },

    /**
     * Event handler for when email-link control is clicked
     * @param  {Object} e Click event
     */
    _onEmailLinkClick: function(e)
    {
        if(this.data.attrs.object_type === "question" && !RightNow.Profile.isSocialUser())
        {
            this.requestAuthentication(e);
        }
        else
        {
            if(!this._dialog)
            {
                var buttons = [{text: this.data.attrs.label_send_button, handler: {fn: this._submitClicked, scope: this}, isDefault: true},
                               {text: this.data.attrs.label_cancel_button, handler: {fn: this._closeDialog, scope: this}, isDefault: false}];
                this._dialog = RightNow.UI.Dialog.actionDialog(this.data.attrs.label_dialog_title, this._dialogElement, {"buttons": buttons});
                this._dialogElement.removeClass("rn_Hidden").addClass("rn_EmailLinkDialog");
                this._keyListener = RightNow.UI.Dialog.addDialogEnterKeyListener(this._dialog, this._submitClicked, this);
            }
            this._recipientEmailElement = this._recipientEmailElement || this.Y.one(this.baseSelector + "_InputRecipientEmail");
            this._senderEmailElement = this._senderEmailElement || this.Y.one(this.baseSelector + "_InputSenderEmail");
            this._senderNameElement = this._senderNameElement || this.Y.one(this.baseSelector + "_InputSenderName");
            this._errorDisplay = this._errorDisplay || this.Y.one(this.baseSelector + "_ErrorMessage");

            if(this._errorDisplay)
            {
                this._errorDisplay.set("innerHTML", "").removeClass('rn_MessageBox rn_ErrorMessage');
            }

            this._dialog.show();
            RightNow.UI.Dialog.enableDialogControls(this._dialog, this._keyListener, this._recipientEmailElement);
        }
    },

    /**
     * Triggers a dialog to ask the user to either log in or add a CommunityUser to their account
     * @param {Object} e Click event
     */
    requestAuthentication: function (e) {
        if(!RightNow.Profile.isLoggedIn()){
            RightNow.Event.fire('evt_requireLogin', new RightNow.Event.EventObject(this, {data:{
                isSocialAction: true,
                title: RightNow.Interface.getMessage("PLEASE_LOG_CREATE_AN_ACCOUNT_CONTINUE_LBL")
            }}));
        }
        else{
            RightNow.Event.fire("evt_userInfoRequired");
        }
        e.halt();
    },

    /**
     * Close dialog. Leave cancel button enabled.
     * If user opens a second time, do not allow email send, but do allow cancel.
    */
    _closeDialog: function()
    {
        // Get rid of any existing error message so it's gone if the user opens the dialog again.
        if(this._errorDisplay)
        {
            this._errorDisplay.set("innerHTML", "").removeClass('rn_MessageBox rn_ErrorMessage');
        }
        RightNow.UI.Dialog.disableDialogControls(this._dialog, this._keyListener);
        this._dialog.hide();
    },

    _shouldIgnoreEvent: function(name, event) {
        if (name === "keyPressed") {
            //IE 7 and 8 don't populate target but do populate srcElement
            var target = (event && event[1]) ? (event[1].target || event[1].srcElement) : null,
                targetContents = target ? target.getHTML() : null;

            return (!target || target.get('tagName') === 'A' ||
                    targetContents === this.data.attrs.label_send_button ||
                    targetContents === this.data.attrs.label_cancel_button
            );
        }

        return false;
    },

    /**
     * Event handler for click of submit button
    */
    _submitClicked: function(type, args)
    {
        //Don't submit if they are using the enter key on certain elements
        if (this._shouldIgnoreEvent(type, args)) return;

        RightNow.UI.Dialog.disableDialogControls(this._dialog, this._keyListener);
        if(this._validateFormData())
        {
            this._submitRequest();
        }
        else
        {
            RightNow.UI.Dialog.enableDialogControls(this._dialog, this._keyListener);
        }
    },

     /**
     * Validates form data.
     * @return Boolean if the form validated successfully
     */
    _validateFormData: function()
    {
        if(this._errorDisplay)
        {
            this._errorDisplay.set("innerHTML", "").removeClass('rn_MessageBox rn_ErrorMessage');
        }

        var toEmailIsValid = this._validateEmailAddress(this._recipientEmailElement, this.data.attrs.label_to),
            fromEmailIsValid = !this._senderEmailElement || this._validateEmailAddress(this._senderEmailElement, this.data.attrs.label_sender_email),
            senderNameIsValid = !this._senderNameElement || this._validateSenderName(this._senderNameElement, this.data.attrs.label_sender_name);

        return toEmailIsValid && fromEmailIsValid && senderNameIsValid;
    },

     /**
      * Validates sender's name.
      * Adds error message(s) to the form's error div if there are errors.
      * @param {Object} senderInput YUI input Node
      * @param {String} label Label for the input field
      * @return Boolean if the field validated successfully
      */
    _validateSenderName: function(senderInput, label) {
        var nameValue = this.Y.Lang.trim(senderInput.get("value")),
            id = senderInput.get("id"),
            errors = [];

        if (nameValue === "") {
            errors.push(RightNow.Interface.getMessage("PCT_S_IS_REQUIRED_MSG"));
        }
        else {
            if (nameValue.indexOf("<") > -1 || nameValue.indexOf(">") > -1) {
                errors.push(RightNow.Interface.getMessage("PCT_S_CONTAIN_THAN_MSG"));
            }
            if (nameValue.indexOf("'") > -1 || nameValue.indexOf('"') > -1) {
                errors.push(RightNow.Interface.getMessage("PCT_S_MUST_NOT_CONTAIN_QUOTES_MSG"));
            }
            if (nameValue.indexOf("&") > -1) {
                errors.push(RightNow.Interface.getMessage("PCT_S_MUST_NOT_CONTAIN_MSG"));
            }
        }

        this.Y.Array.each(errors, function(message) {
            this._addErrorMessage(RightNow.Text.sprintf(message, label), id);
        }, this);

        return !errors.length;
    },

    /**
     * Utility function to validate a given email address field.
     * @param emailField HTMLElement the email field to validate
     * @param label String the field's label that is used within error messages.
     * @return Boolean if the email field validated successfully
     */
     _validateEmailAddress: function(emailField, label)
    {
        if(emailField)
        {
            var emailFieldValue = this.Y.Lang.trim(emailField.get("value")),
                id = emailField.get("id");

            // Make sure the value is not empty.
            if (emailFieldValue === "")
            {
                this._addErrorMessage(RightNow.Text.sprintf(RightNow.Interface.getMessage("PCT_S_IS_REQUIRED_MSG"), label), id);
                return false;
            }
            //make sure a single email address is entered
            if(emailFieldValue.indexOf(";") >= 0 || emailFieldValue.indexOf(",") >= 0 || emailFieldValue.indexOf(" ") >= 0)
            {
                this._addErrorMessage(RightNow.Interface.getMessage("PLEASE_ENTER_SINGLE_EMAIL_ADDRESS_MSG"), id);
                return false;
            }
            // Make sure it has valid email format
            if(!RightNow.Text.isValidEmailAddress(emailFieldValue))
            {
                this._addErrorMessage(RightNow.Text.sprintf(RightNow.Interface.getMessage("PCT_S_IS_INVALID_MSG"), label), id);
                return false;
            }
            // Test the length
            if(emailFieldValue.length > 255)
            {
                this._addErrorMessage(RightNow.Text.sprintf(RightNow.Interface.getMessage("PCT_S_IS_TOO_LONG_MSG"), label), id);
                return false;
            }
            return true;
        }
        return false;
    },

    /**
    * Submits request to server.  Assumes that the this.data has been validated.
    */
    _submitRequest: function()
    {
        var eventObject, requestURL;
        if(this.data.attrs.object_type === "question") {
            // Format an event object
            eventObject = new RightNow.Event.EventObject(this, {data: {
                w_id: this.data.info.w_id,
                to: this._recipientEmailElement.get("value"),
                qid: this.data.js.objectID
            }});
            requestURL = this.data.attrs.send_discussion_email_ajax;
        }
        else {
            var name = ((this._senderNameElement) ? this._senderNameElement.get("value") : "") || this.data.js.senderName;
            eventObject = new RightNow.Event.EventObject(this, {data: {
                w_id: this.data.info.w_id,
                to: this._recipientEmailElement.get("value"),
                a_id: this.data.js.objectID,
                from: ((this._senderEmailElement) ? this._senderEmailElement.get("value") : "") || this.data.js.senderEmail,
                name: name.substring(0, 80)
            }});
            requestURL = this.data.attrs.send_email_ajax;
        }
        
        if(this.data.js.f_tok){
            RightNow.Event.subscribe("evt_formTokenUpdate", RightNow.Widgets.onFormTokenUpdate, this);
            // Get a new f_tok value on each uploads
            RightNow.Event.fire("evt_formTokenRequest", new RightNow.Event.EventObject(this, {data:{formToken:this.data.js.f_tok}}));

            eventObject.data.f_tok = this.data.js.f_tok;
        }

        if (RightNow.Event.fire("evt_emailLinkRequest", eventObject)) {
            RightNow.Ajax.makeRequest(requestURL, eventObject.data, {successHandler: this._onResponseReceived, scope: this, data: eventObject, json: true});
        }
    },

    /**
     * Event handler for response received from server.  Show a confirmation dialog.
     * @param type String Event name
     * @param arg Object Event arguments
     */
    _onResponseReceived: function(response, originalEventObj)
    {
        if(RightNow.Event.fire("evt_emailLinkSubmitResponse", {data: originalEventObj, response: response})) {
            if(response.ajaxError){
                // RightNow.Ajax has already displayed a message if there was an AJAX error
                this._closeDialog();
            }
            else{
                var dialogParameters = {exitCallback: {fn: function() { messageDialog.hide(); this._closeDialog(); }, scope: this}},
                    messageDialog,
                    message;
                if (typeof response === "string") {
                    message = response;
                }
                else if (response.errors) {
                    if(!RightNow.Ajax.indicatesSocialUserError(response)) {
                        message = response.errors[0].externalMessage || response.suggestedErrorMessage || RightNow.Interface.getMessage("THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG");
                        dialogParameters.icon = 'WARN';
                    }
                }
                else {
                    message = this.data.attrs.label_email_sent;
                }
                messageDialog = RightNow.UI.Dialog.messageDialog(message, dialogParameters);
            }
        }
    },

    /**
     * Adds an error message to the page and adds the correct CSS classes
     * @param message String The error message to display
     * @param focusElement HTMLElement The HTML element to focus on when the error message link is clicked
     */
    _addErrorMessage: function(message, focusElement) {
        if(this._errorDisplay) {
            this._errorDisplay.addClass('rn_MessageBox rn_ErrorMessage');
            //add link to message so that it can receive focus for accessibility reasons
            var newMessage = '<a href="javascript:void(0);" onclick="document.getElementById(\'' + focusElement + '\').focus(); return false;">' + message + '</a>';
            var oldMessage = this._errorDisplay.get("innerHTML");
            if (oldMessage !== "")
                newMessage = oldMessage + '<br/>' + newMessage;
            this._errorDisplay.set("innerHTML", newMessage);
            this._errorDisplay.one('a').focus();
            this._errorDisplay.one("h2") ? this._errorDisplay.one("h2").setHTML(RightNow.Interface.getMessage("ERRORS_LBL")) : this._errorDisplay.prepend("<h2>" + RightNow.Interface.getMessage("ERROR_LBL") + "</h2>");
            this._errorDisplay.one("h2").setAttribute('role', 'alert');
        }
    },

    /**
     * Event handler for when social question status update event is received from server.
     * @param {Object} evt Current event object
     * @param {Array} args Data passed from widget which trigger this function call
     */
    _onStatusUpdate: function(evt, args) {
        // if the question's status changes to active then show email link else hide it
        if (args[0].data.object_data.updatedObject.objectType === 'CommunityQuestion') {
            if (parseInt(args[0].data.object_data.updatedObject.statusWithTypeID, 10) !== this.data.js.activeStatusWithTypeID) {
                RightNow.UI.hide(this.baseSelector);
            }
            else {
                RightNow.UI.show(this.baseSelector);
            }
        }
    }
});
