RightNow.Widgets.SiteFeedback = RightNow.Widgets.extend({
    constructor: function() {
        this._dialog = this._keyListener = null;
        this._resetElements();

        if (!this._feedbackField)
        {
            //feedback text area is required; email input is optional.
            RightNow.UI.DevelopmentHeader.addJavascriptError(RightNow.Text.sprintf(RightNow.Interface.getMessage("SITEFEEDBACK_DIALOG_MISSING_REQD_MSG"), "rn_" + this.instanceID + "_FeedbackTextarea"));
            return;
        }

        this.Y.one(this.baseSelector + "_FeedbackLink").on("click", this._onGiveFeedbackClick, this);
        RightNow.Event.subscribe("evt_formTokenUpdate", RightNow.Widgets.onFormTokenUpdate, this);
    },

    /**
     * Reset elements when dialog is shown
     */
    _resetElements: function()
    {
        this._errorDisplay = this.Y.one(this.baseSelector + "_ErrorMessage");
        this._emailField = this.Y.one(this.baseSelector + "_EmailInput");
        this._feedbackField = this.Y.one(this.baseSelector + "_FeedbackTextarea");
    },

    /**
     * Event handler for when site feedback button is clicked
     */
    _onGiveFeedbackClick: function()
    {
        // Evaluate feedback_page_url
        if (this.data.attrs.feedback_page_url)
        {
            window.open(RightNow.Url.addParameter(this.data.attrs.feedback_page_url, "session", RightNow.Url.getSession()), "", "resizable, scrollbars, width=630, height=400");
        }
        else
        {
            this._showDialog();
        }
    },

    /**
     * Constructs and shows the dialog
     */
    _showDialog: function()
    {
        // get a new f_tok value each time the dialog is opened
        RightNow.Event.fire("evt_formTokenRequest",
            new RightNow.Event.EventObject(this, {data:{formToken:this.data.js.f_tok}}));

        // If the dialog doesn't exist, create it.  (Happens on first click).
        if (!this._dialog)
        {
            var buttons = [ { text: this.data.attrs.label_send_button, handler: {fn: this._onSubmit, scope: this}, isDefault: true},
                     { text: this.data.attrs.label_cancel_button, handler: {fn: this._onCancel, scope: this}, isDefault: false}];
            this._dialog = RightNow.UI.Dialog.actionDialog(this.data.attrs.label_dialog_title, this.Y.one(this.baseSelector + "_SiteFeedbackForm"), {buttons: buttons});
            //Set up keylistener for <enter> to run onSubmit()
            this._keyListener = RightNow.UI.Dialog.addDialogEnterKeyListener(this._dialog, this._onSubmit, this);
            // Give the dialog a specific css class
            this.Y.one("#" + this._dialog.id).addClass('rn_SiteFeedbackDialog');
        }
        if(this._errorDisplay)
        {
            this._errorDisplay.set("innerHTML", "").removeClass('rn_MessageBox rn_ErrorMessage');
        }
        RightNow.ActionCapture.record('siteFeedback', 'click');
        this._dialog.show();
        this._resetElements();

        var focusElement;
        if (this._emailField && this._emailField.get('value') === '')
            focusElement = this._emailField;
        else
            focusElement = this._feedbackField;
        focusElement.focus();
        RightNow.UI.Dialog.enableDialogControls(this._dialog, this._keyListener);
    },

    /**
     * Event handler for click of submit buttons.
     */
    _onSubmit: function(type, args)
    {
        var target = (args && args[1]) ? (args[1].target || args[1].srcElement) : null;

        //Don't submit if the user's pressing the enter key on certain elements or if validation fails.
        if((type === "keyPressed" && target && (target.get('tagName') === 'A' || target.get('tagName') === 'TEXTAREA' ||
             target.getHTML() === this.data.attrs.label_send_button || target.getHTML() === this.data.attrs.label_cancel_button)) ||
            !this._validateDialogData()) {
            return;
        }
        //Disable submit and cancel buttons
        RightNow.UI.Dialog.disableDialogControls(this._dialog, this._keyListener);
        this._submitFeedback();
    },

    /**
    * Event handler for click of cancel button.
    */
    _onCancel: function()
    {
        RightNow.UI.Dialog.disableDialogControls(this._dialog, this._keyListener);
        this._closeDialog(true);
    },

    /**
     * Validates dlg data.
     */
    _validateDialogData: function()
    {
        this._errorDisplay.removeClass('rn_MessageBox rn_ErrorMessage').set("innerHTML", "");

        var returnValue = true;
        if (this._emailField)
        {
            this._emailField.set('value', this.Y.Lang.trim(this._emailField.get('value')));
            //Email address can have a comma or semicolon at end if copied from outlook
            this._emailField.set('value', this._emailField.get('value').replace(/\s*[,;]$/g,''));
            if (this._emailField.get('value') === "")
            {
                this._addErrorMessage(RightNow.Text.sprintf(RightNow.Interface.getMessage("PCT_S_IS_REQUIRED_MSG"), this.data.attrs.label_email_address), this._emailField.get('id'));
                returnValue = false;
            }
            else if (!RightNow.Text.isValidEmailAddress(this._emailField.get('value')))
            {
                this._addErrorMessage(this.data.attrs.label_email_address + ' ' + RightNow.Interface.getMessage("FIELD_IS_NOT_A_VALID_EMAIL_ADDRESS_MSG"), this._emailField.get('id'));
                returnValue = false;
            }
            else
            {
                var email = this._emailField.get('value');
                var flag = false;
                for (var index = 0; index < email.length; index++) {
                    if((email[index] === ";") || (email[index] === ",")){
                        flag = true;
                        break;
                    }
                }
                if (flag && index != email.length - 1) {
                    for (var i = index + 1; i < email.length; i ++)
                    {
                        if(! ( email[i] === ";" || email[i] === "," )) {
                            this._addErrorMessage(RightNow.Interface.getMessage("PLEASE_ENTER_SINGLE_EMAIL_ADDRESS_LBL"), this._emailField.get('id'));
                            returnValue = false;
                            break;
                        }
                    }
                    if (i === email.length) {
                        this._emailField.set('value',email.substring(0,index));
                    }
                }
            }
        }
        // Examine feedback text.
        this._feedbackField.set('value', this.Y.Lang.trim(this._feedbackField.get('value')));
        if (this._feedbackField.get('value') === "")
        {
            this._addErrorMessage(RightNow.Text.sprintf(RightNow.Interface.getMessage("PCT_S_IS_REQUIRED_MSG"), this.data.attrs.label_comment_box), this._feedbackField.get('id'));
            returnValue = false;
        }
        return returnValue;
    },

    /**
     *  Close the dialog.
     * @param {bool} cancelled True if the dialog was canceled
    */
    _closeDialog: function(cancelled)
    {
        if(!cancelled)
        {
            //Feedback submitted: clear existing data if dialog is reopened
            this._feedbackField.set('value', "");
        }
        // Get rid of any existing error message, so it's gone if the user opens the dialog again.
        if(this._errorDisplay)
        {
            this._errorDisplay.set("innerHTML", "").removeClass('rn_MessageBox rn_ErrorMessage');
        }

        if (this._dialog)
            this._dialog.hide();
    },

    /**
     * Submit data to the server.
     */
    _submitFeedback: function()
    {
        var eventObject = new RightNow.Event.EventObject(this, {data: {
                "w_id"    : this.data.info.w_id,
                "a_id"    : null,
                "rate"    : 0,
                "f_tok"   : this.data.js.f_tok,
                "message" : this._feedbackField.get('value')
            }});

        if (this.data.js.isProfile)
            eventObject.data.email = this.data.js.email;
        else if (this._emailField)
            eventObject.data.email = this._emailField.get('value');

        if(RightNow.Event.fire("evt_siteFeedbackRequest", eventObject))
        {
            RightNow.Ajax.makeRequest(this.data.attrs.submit_site_feedback_ajax,
                eventObject.data,
                {successHandler: this._onResponseReceived, scope: this, data: eventObject, json: true});
        }
        return false;
    },

    /**
     * Event handler for server sends response.
     * @param {object} response Response object
     * @param {object} originalEventObj Original event object
     */
    _onResponseReceived: function(response, originalEventObj)
    {
        if(RightNow.Event.fire("evt_siteFeedbackSubmitResponse", {data: originalEventObj, response: response}))
        {
            this._closeDialog();

            if (!response) {
                return; // RightNow.Ajax has already displayed a message if there is no response.
            }

            var error;
            if((error = response.error) || !response.ID) {
                RightNow.UI.Dialog.messageDialog(
                    error || RightNow.Interface.getMessage("SORRY_ERROR_SUBMISSION_LBL"),
                    {icon: "WARN", exitCallback: {fn: this._dialog.enableButtons, scope: this._dialog}}
                );
            }
            else {
                //Show a confirmation dialog to confirm that feedback was sent.
                RightNow.UI.Dialog.messageDialog(this.data.attrs.label_feedback_confirmation, {exitCallback: {fn: this._closeDialog, scope: this}});
            }
        }
    },

    /**
     * Adds an error message to the page and adds the correct CSS classes
     * @param {string} message The error message to display
     * @param {HTMLElement} focusElement The HTML element to focus on when the error message link is clicked
     */
    _addErrorMessage: function(message, focusElement)
    {
        if(this._errorDisplay)
        {
            var newMessage = '<a href="javascript:void(0);" onclick="document.getElementById(\'' + focusElement + '\').focus(); return false;">' + message + '</a>',
                oldMessage = this._errorDisplay.get("innerHTML");
            this._errorDisplay.addClass('rn_MessageBox rn_ErrorMessage').set("innerHTML", ((oldMessage === "") ? newMessage : oldMessage + '<br/>' + newMessage));
            this._errorDisplay.get('children').item(0).focus();
            this._errorDisplay.one("h2") ? this._errorDisplay.one("h2").setHTML(RightNow.Interface.getMessage("ERRORS_LBL")) : this._errorDisplay.prepend("<h2>" + RightNow.Interface.getMessage("ERROR_LBL") + "</h2>");
            this._errorDisplay.one("h2").setAttribute('role', 'alert');
        }
    }
});
