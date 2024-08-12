RightNow.Widgets.OkcsEmailAnswerLink = RightNow.SearchConsumer.extend({
    overrides: {
        constructor: function() {
            this._dialogElement = this.Y.one(this.baseSelector + '_EmailAnswerLinkForm');

            if(this._dialogElement) {
                this.Y.Event.attach("click", this._onEmailLinkClick, this.baseSelector + "_Link", this);
            }
        }
    },

    /**
     * Event handler for when email-link control is clicked
     * @param  {Object} e Click event
     */
    _onEmailLinkClick: function(e) {
        if(!this._dialog) {
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

        if(this._errorDisplay) {
            this._errorDisplay.set("innerHTML", "").removeClass('rn_MessageBox rn_ErrorMessage');
        }

        this._dialog.show();
        RightNow.UI.Dialog.enableDialogControls(this._dialog, this._keyListener, this._recipientEmailElement);
    },

    /**
     * Close dialog. Leave cancel button enabled.
     * If user opens a second time, do not allow email send, but do allow cancel.
    */
    _closeDialog: function() {
        // Get rid of any existing error message so it's gone if the user opens the dialog again.
        if(this._errorDisplay) {
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
    _submitClicked: function(type, args) {
        //Don't submit if they are using the enter key on certain elements
        if (this._shouldIgnoreEvent(type, args)) return;

        RightNow.UI.Dialog.disableDialogControls(this._dialog, this._keyListener);
        if(this._validateFormData()) {
            this._submitRequest();
        }
        else {
            RightNow.UI.Dialog.enableDialogControls(this._dialog, this._keyListener);
        }
    },

    /**
     * Validates form data.
     * @return Boolean if the form validated successfully
     */
    _validateFormData: function() {
        if(this._errorDisplay) {
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
     _validateEmailAddress: function(emailField, label) {
        if(emailField) {
            var emailFieldValue = this.Y.Lang.trim(emailField.get("value")),
                id = emailField.get("id");

            // Make sure the value is not empty.
            if (emailFieldValue === "") {
                this._addErrorMessage(RightNow.Text.sprintf(RightNow.Interface.getMessage("PCT_S_IS_REQUIRED_MSG"), label), id);
                return false;
            }
            //make sure a single email address is entered
            if(emailFieldValue.indexOf(";") >= 0 || emailFieldValue.indexOf(",") >= 0 || emailFieldValue.indexOf(" ") >= 0) {
                this._addErrorMessage(RightNow.Interface.getMessage("PLEASE_ENTER_SINGLE_EMAIL_ADDRESS_MSG"), id);
                return false;
            }
            // Make sure it has valid email format
            if(!RightNow.Text.isValidEmailAddress(emailFieldValue)) {
                this._addErrorMessage(RightNow.Text.sprintf(RightNow.Interface.getMessage("PCT_S_IS_INVALID_MSG"), label), id);
                return false;
            }
            // Test the length
            if(emailFieldValue.length > 255) {
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
    _submitRequest: function() {
        var eventObject, requestURL;
        eventObject = new RightNow.Event.EventObject(this, {data: {
            w_id: this.data.info.w_id,
            to: this._recipientEmailElement.get("value"),
            a_id: this.data.js.objectID,
            emailAnswerToken: this.data.js.emailAnswerToken,
            from: ((this._senderEmailElement) ? this._senderEmailElement.get("value") : "") || this.data.js.senderEmail,
            name: ((this._senderNameElement) ? this._senderNameElement.get("value") : "") || this.data.js.senderName,
            title: this.data.js.title,
            aId: this.data.js.docId,
            emailHeader: this.data.attrs.label_email_header,
            emailSender: this.data.attrs.label_email_sender_message,
            summaryLabel: this.data.attrs.label_summary,
            answerViewLabel: this.data.attrs.label_answer_view_message,
            databaseEmailTemplate: this.data.attrs.use_database_template,
            emailAnswerToken: this.data.js.emailAnswerToken
        }});
        requestURL = this.data.attrs.send_okcs_email_ajax;

        if (this.data.js.hasOwnProperty('isSenderEmailSecurityRequired') && this.data.js.isSenderEmailSecurityRequired == "1" && this.data.js.allowedSenderEmails !== null && this.data.js.allowedSenderEmails !== '') {
            
            var allowedEmails  = this.data.js.allowedSenderEmails.split(',');
            var isValidEmail = false;
            
            for(var i = 0; i < allowedEmails.length; i++){
                var emailId = allowedEmails[i].trim();
                if(eventObject.data.from === emailId){
                    isValidEmail = true;
                    break;
                }
            }
            if(isValidEmail){
                if (RightNow.Event.fire("evt_okcsEmailLinkRequest", eventObject)) {
                    RightNow.Ajax.makeRequest(requestURL, eventObject.data, {successHandler: this._onResponseReceived, scope: this, data: eventObject, json: true});
               } 
            }else {  
                messageDialog = RightNow.UI.Dialog.messageDialog(RightNow.Interface.getMessage("TH_SENDER_NOT_AUTHORIZED_SEND_EMAIL_MSG"),  {icon: "WARN", title: RightNow.Interface.getMessage("ERROR_LBL")});
            }
        }else if(this.data.js.hasOwnProperty('isSenderEmailSecurityRequired') && this.data.js.isSenderEmailSecurityRequired == "1" && this.data.js.allowedSenderEmails == null){
            if(this.data.js.isProfile !== null && !this.data.js.isProfile){
                messageDialog = RightNow.UI.Dialog.messageDialog(RightNow.Interface.getMessage("PLEASE_LOGIN_TO_SEND_EMAIL_LBL"),  {icon: "WARN", title: RightNow.Interface.getMessage("ERROR_LBL")});
            }else if(this.data.js.isProfile !== null && this.data.js.isProfile){
                if (RightNow.Event.fire("evt_okcsEmailLinkRequest", eventObject)) {
                    RightNow.Ajax.makeRequest(requestURL, eventObject.data, {successHandler: this._onResponseReceived, scope: this, data: eventObject, json: true});
               }
            }else {            
            messageDialog = RightNow.UI.Dialog.messageDialog(RightNow.Interface.getMessage("TH_SENDER_NOT_AUTHORIZED_SEND_EMAIL_MSG"),  {icon: "WARN", title: RightNow.Interface.getMessage("ERROR_LBL")});
            }
        }else{
            if (RightNow.Event.fire("evt_okcsEmailLinkRequest", eventObject)) {
                RightNow.Ajax.makeRequest(requestURL, eventObject.data, {successHandler: this._onResponseReceived, scope: this, data: eventObject, json: true});
           }
        }
    },
    
    /**
     * Event handler for response received from server.  Show a confirmation dialog.
     * @param type String Event name
     * @param arg Object Event arguments
     */
    _onResponseReceived: function(response, originalEventObj) {
        if(RightNow.Event.fire("evt_okcsEmailLinkSubmitResponse", {data: originalEventObj, response: response})) {
            if(response.ajaxError){
                // RightNow.Ajax has already displayed a message if there was an AJAX error
                this._closeDialog();
            }
            else {
                var dialogParameters = {exitCallback: {fn: function() { messageDialog.hide(); this._closeDialog(); }, scope: this}},
                    messageDialog,
                    message;
                if (typeof response === "string") {
                    message = response;
                }
                else if (response.errors) {
                    if(!RightNow.Ajax.indicatesSocialUserError(response)) {
                        message = response.errors[0].externalMessage || RightNow.Interface.getMessage('ERROR_REQUEST_ACTION_COMPLETED_MSG');
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
    }
});
