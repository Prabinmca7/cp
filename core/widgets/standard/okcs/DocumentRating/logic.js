RightNow.Widgets.DocumentRating = RightNow.Widgets.extend({
    constructor: function() {
        this._rating = '';
        this._ratingIndex = '';
        this._dialog = this._keyListener = null;
        this.maxRating = this.ratingValue = '';
        var submitButton = this.Y.one(this.baseSelector + '_SubmitButton');
        if(submitButton)
            submitButton.on('click', this._submitRating, this);
        this.Y.all(this.baseSelector + ' .rn_RatingInput').on('click', this._updateRating, this);
        this.Y.all(this.baseSelector + ' .rn_StarRatingInput').on('click', this._updateRating, this);
        this.Y.one(this.baseSelector).delegate('click', this._selectRating, 'button.rn_Rating', this);
        this.Y.all(this.baseSelector + ' .rn_StarRatingInput').on({mouseenter : this._onMouseOver, mouseleave: this._onMouseOut}, this, this);
    },

    /**
    * Event handler executed when the channel is clicked
    * @param {Object} evt Event
    */
    _submitRating: function(evt) {
        this._toggleLoadingIndicators();
        this.Y.one(this.baseSelector + '_ErrorMessage').addClass("rn_Hidden");
        this.Y.one(this.baseSelector + '_SubmitButton').set("disabled", "true");
        var ratingData = this._rating.split(':');
        var eventObject = new RightNow.Event.EventObject(this, {data: {
            surveyRecordID: ratingData[0],
            answerRecordID: ratingData[1],
            contentRecordID: ratingData[2],
            localeRecordID: this.data.js.locale,
            answerID: this.data.js.answerID,
            ratingPercentage: this._ratingPercentage,
            answerComment: this.Y.one(this.baseSelector + '_FeedbackMessage').get('value')
        }});

        RightNow.Ajax.makeRequest(this.data.attrs.get_okcs_data_ajax, eventObject.data, {
            successHandler: this._displayRatingSubmissionMessage,
            json: true, scope: this
        });
       
        this._thanksLabel = this.Y.Node.create('<div id="rn_' + this.instanceID + '_ThanksLabel" class="rn_ThanksLabel">');
        // Show feedback dialog if indicated.
        if(this.maxRating != 1 && this._ratingIndex <= this.data.attrs.dialog_threshold) {
            if(this.data.attrs.feedback_page_url) {
                var pageString = this.data.attrs.feedback_page_url;
                pageString = RightNow.Url.addParameter(pageString, "a_id", this.data.js.answerID);
                pageString = RightNow.Url.addParameter(pageString, "session", RightNow.Url.getSession());
                window.open(pageString, '', "resizable, scrollbars, width=630, height=400");
             }
            else {
                this._showDialog();
            }
        }
    },
    
    /**
    * Rating submission success callback function
    * @param {Object} response Response of AJAX call
    */
    _displayRatingSubmissionMessage: function(response){
        if(response.failure == null) {
            this.Y.one(this.baseSelector + '_DocumentComment').addClass('rn_Hidden');
            this.Y.one(this.baseSelector + '_ThanksMessage').removeClass('rn_Hidden');
            this.Y.all(this.baseSelector).detach('click');
            this._updateAriaAlert(this.Y.one(this.baseSelector + '_ThanksMessage').get('innerHTML'));
            this.Y.all(this.baseSelector + ' .rn_StarRatingInput').detach('click', this._updateRating, this);
            this.Y.all(this.baseSelector + ' .rn_RatingInput').detach('click', this._updateRating, this);
            this.Y.all(this.baseSelector + ' .rn_StarRatingInput').detach('mouseenter', this._onMouseOver, this);
            this.Y.all(this.baseSelector + ' .rn_StarRatingInput').detach('mouseleave', this._onMouseOut, this);
            this.Y.all(this.baseSelector + ' .rn_RatingInput').set('disabled', true);
            this.Y.one(this.baseSelector + '_SubmitButton').set('disabled', true);
        }
        else {
            this.Y.one(this.baseSelector + '_ErrorMessage').removeClass('rn_Hidden');
            this.Y.one(this.baseSelector + '_SubmitButton').removeAttribute('disabled');
        }
        this._toggleLoadingIndicators(false);
    },

    /**
    * Event handler executed when the rating is clicked
    * @param {Object} evt Event
    */
    _updateRating: function(evt) {
        this.Y.all(this.baseSelector + ' .rn_StarRatingInput').detach('mouseleave', this._onMouseOut, this);
        this.Y.one(this.baseSelector + '_SubmitButton').removeAttribute('disabled');
        this._rating = evt.target.getAttribute('data-rating');
        this._ratingIndex = evt.target.getAttribute('data-id') === "" ? evt.target.getAttribute('data-value') : evt.target.getAttribute('data-id');
        this.maxRating = maxRating = evt.target.getAttribute('data-maxRating');
        if (this.maxRating == 1 || this._ratingIndex > this.data.attrs.dialog_threshold) {
             this.Y.one(this.baseSelector + '_DocumentComment').removeClass('rn_Hidden');
        }
        else {
             this.Y.one(this.baseSelector + '_DocumentComment').addClass('rn_Hidden');
        }
        this._ratingPercentage = (maxRating === 2 ? (this._ratingIndex - 1) / (this._ratingIndex - 1) : (this._ratingIndex / maxRating)) * 100;
    },

    /**
    * Method to call on click of rating
    * @param {Object} evt Event
    */
    _selectRating: function(evt) {
        var ratingIndex = evt.target.getAttribute('data-id');
        var ratings = this.Y.all(this.baseSelector + ' .rn_StarRatingInput');
        ratings.removeClass('rn_Selected');
        for(var i = 0; i < ratingIndex; i++)
            ratings.item(i).addClass('rn_Selected');
        if (this.maxRating == 1 || this._ratingIndex > this.data.attrs.dialog_threshold){
            this.Y.one(this.baseSelector + '_DocumentComment').removeClass('rn_Hidden');
        }
        else {
            this.Y.one(this.baseSelector + '_DocumentComment').addClass('rn_Hidden');
        }
    },

    /**
    * Method to call on mouse over event
    * @param {Object} evt Event
    */
    _onMouseOver: function(evt) {
        var ratingIndex = evt.target.getAttribute('data-id');
        var ratings = this.Y.all(this.baseSelector + ' .rn_StarRatingInput');
        ratings.removeClass('rn_Selected');
        for(var i = 0; i < ratingIndex; i++)
            ratings.item(i).addClass('rn_Selected');
        this.Y.all(this.baseSelector + ' .rn_StarRatingInput').on('mouseleave', this._onMouseOut, this);
    },
    
    /**
    * Method to be called on mouse out event
    */
    _onMouseOut: function() {
        var ratings = this.Y.all(this.baseSelector + ' .rn_StarRatingInput');
        ratings.removeClass('rn_Selected');
        for(var i = 0; i < this._ratingIndex; i++)
            ratings.item(i).addClass('rn_Selected');
    },
    
    /**
     * Hides / shows the status message.
     * @param {Boolean=} turnOn Whether to turn on the loading indicators (T),
     * remove the loading indicators (F), or toggle their current state (default) (optional)
     */
    _toggleLoadingIndicators: function(turnOn) {
        var classFunc = ((typeof turnOn === "undefined") ? "toggleClass" : ((turnOn === true) ? "removeClass" : "addClass")),
            message = this.Y.one(this.baseSelector + "_StatusMessage");
        if (message) {
            message[classFunc]("rn_Hidden").setAttribute("aria-live", (message.hasClass("rn_Hidden")) ? "off" : "assertive");
        }
        this.Y.one(this.baseSelector + "_StatusMessage").addClass('rn_OkcsSubmit');
    },

    /**
     * Updates the text for the ARIA alert div that appears above document rating
     * @param {String} text The text to update the div with
     */
    _updateAriaAlert: function(text) {
        this._ariaAlert = this._ariaAlert || this.Y.one(this.baseSelector + '_Alert');
        if(this._ariaAlert) {
            this._ariaAlert.set('innerHTML', text);
        }
    },

    /**
     * Submit data to the server.
     */
    _submitFeedback: function() {
         var eventObject = new RightNow.Event.EventObject(this, {data: {
             a_id: this.data.js.answerID,
             rate: this._ratingIndex,
             threshold: this.data.attrs.dialog_threshold,
             options_count: this.maxRating,
             message: this._feedbackField.get('value'),
             email: (this._emailField) ? this._emailField.get('value') : this.data.js.email,
             f_tok: this.data.js.f_tok
        }});
        if(RightNow.Event.fire("evt_answerFeedbackRequest", eventObject)) {
            RightNow.Ajax.makeRequest(this.data.attrs.submit_feedback_ajax, eventObject.data, {successHandler: this._onResponseReceived, scope: this, data: eventObject, json: true});
        }
    },

    /**
     * Event handler for server sends response.
     * @param response Mixed - Integer on success, string on error.
     * @param originalEventObj Object event object
     */
    _onResponseReceived: function(response, originalEventObj) {
        if(RightNow.Event.fire("evt_answerFeedbackResponse", response, originalEventObj)) {
            if(this._incidentCreateFlag){
                this._incidentCreateFlag = false;
                if(response && response.ID) {
                    this._closeDialog();
                    RightNow.UI.displayBanner(this.data.attrs.label_feedback_submitted, {
                        focusElement: this._thanksLabel,
                        baseClass: "rn_ThanksLabel"
                    });
                }
                else {
                    var message = (response && response.error) ? response.error : response;
                    this._addErrorMessage(message, null);
                    RightNow.UI.Dialog.enableDialogControls(this._dialog, this._keyListener);
                }
            }
            else {
                this._closeDialog();
            }
        }
    },

    /**
     * Constructs and shows the dialog
     * @return None
     */
    _showDialog: function() {
        // get a new f_tok value each time the dialog is opened
        RightNow.Event.fire("evt_formTokenRequest",
            new RightNow.Event.EventObject(this, {data:{formToken:this.data.js.f_tok}}));
        // If the dialog doesn't exist, create it.  (Happens on first click).
        if (!this._dialog) {
            this.Y.augment(this, RightNow.RequiredLabel);
            var buttons = [ { text: this.data.attrs.label_send_button, handler: {fn: this._onSubmit, scope: this}, isDefault: true},
                            { text: this.data.attrs.label_cancel_button, handler: {fn: this._onCancel, scope: this}, isDefault: false}],
                templateData = {domPrefix: this.baseDomID,
                    labelDialogDescription: this.data.attrs.label_dialog_description,
                    labelEmailAddress: this.data.attrs.label_email_address,
                    labelCommentBox: this.data.attrs.label_comment_box,
                    isProfile: this.data.js.isProfile,
                    userEmail: this.data.js.email
                },
                dialogForm = this.Y.Node.create(new EJS({text: this.getStatic().templates.feedbackForm}).render(templateData));
            this._dialog = RightNow.UI.Dialog.actionDialog(this.data.attrs.label_dialog_title, dialogForm, {"buttons" : buttons, "dialogDescription" : this.baseDomID + "_DialogDescription", "width" : this.data.attrs.dialog_width || ''});
            // Set up keylistener for <enter> to run onSubmit()
            this._keyListener = RightNow.UI.Dialog.addDialogEnterKeyListener(this._dialog, this._onSubmit, this);
            RightNow.UI.show(dialogForm);
            this.Y.one('#' + this._dialog.id).addClass('rn_DocumentRatingFeedbackDialog');
            //this.Y.one('#' + this._dialog.id).setStyle('width', '375px');
        }

        this._emailField = this._emailField || this.Y.one(this.baseSelector + "_EmailInput");
        this._errorDisplay = this._errorDisplay || this.Y.one(this.baseSelector + "_ErrorMessage");
        this._feedbackField = this._feedbackField || this.Y.one(this.baseSelector + "_FeedbackTextarea");

        if(this._errorDisplay) {
            this._errorDisplay.set("innerHTML", "").removeClass('rn_MessageBox rn_ErrorMessage');
        }

        this._dialog.show();

        // Enable controls, focus the first input element
        var focusElement;
        if(this._emailField && this._emailField.get("value") === '')
            focusElement = this._emailField;
        else
            focusElement = this._feedbackField;

        focusElement.focus();
        RightNow.UI.Dialog.enableDialogControls(this._dialog, this._keyListener);
    },

    /**
     * Event handler for click of submit buttons.
     */
    _onSubmit: function(type, args) {
        var target = (args && args[1]) ? (args[1].target || args[1].srcElement) : null;
            
        //Don't submit if they are using the enter key on certain elements
        if(type === "keyPressed" && target) {
            var tag = target.get('tagName'),
                innerHTML = target.get('innerHTML');
            if(tag === 'A' || tag === 'TEXTAREA' || innerHTML === this.data.attrs.label_send_button || innerHTML === this.data.attrs.label_cancel_button) {
                return;
            }
        }

        if (!this._validateDialogData()) {
            return;
        }
        // Disable submit and cancel dialog buttons
        RightNow.UI.Dialog.disableDialogControls(this._dialog, this._keyListener);
        this._incidentCreateFlag = true;   //Keep track that we're creating an incident.
        this._submitFeedback();
    },

    /**
     * Event handler for click of cancel button.
     */
    _onCancel: function() {
        RightNow.UI.Dialog.disableDialogControls(this._dialog, this._keyListener);
        this._closeDialog(true);
    },

    /**
     * Validates dlg data.
     */
    _validateDialogData: function() {
        this._errorDisplay.set("innerHTML", "").removeClass('rn_MessageBox rn_ErrorMessage');

        var returnValue = true;
        if (this._emailField) {
            this._emailField.set("value", this.Y.Lang.trim(this._emailField.get("value")));
            if (this._emailField.get("value") === "") {
                this._addErrorMessage(RightNow.Text.sprintf(RightNow.Interface.getMessage("PCT_S_IS_REQUIRED_MSG"), this.data.attrs.label_email_address), this._emailField.get("id"));
                returnValue = false;
            }
            else if (!RightNow.Text.isValidEmailAddress(this._emailField.get("value"))) {
                this._addErrorMessage(this.data.attrs.label_email_address + ' ' + RightNow.Interface.getMessage("FIELD_IS_NOT_A_VALID_EMAIL_ADDRESS_MSG"), this._emailField.get("id"));
                returnValue = false;
            }
        }
        
        // Examine feedback text.
        this._feedbackField.set("value", this.Y.Lang.trim(this._feedbackField.get("value")));
        if (this._feedbackField.get("value") === "") {
            this._addErrorMessage(RightNow.Text.sprintf(RightNow.Interface.getMessage("PCT_S_IS_REQUIRED_MSG"), this.data.attrs.label_comment_box), this._feedbackField.get("id"));
            returnValue = false;
        }
        return returnValue;
    },

    /**
     * Close the dialog.
     * @param cancelled Boolean T if the dialog was canceled
     */
    _closeDialog: function(cancelled) {
        if(!cancelled) {
            //Feedback submitted: clear existing data if dialog is reopened
            this._feedbackField.set("value", "");
        }
        // Get rid of any existing error message, so it's gone if the user opens the dialog again.
        if(this._errorDisplay) {
            this._errorDisplay.set("innerHTML", "").removeClass('rn_MessageBox rn_ErrorMessage');
        }

        if (this._dialog) {
            this._dialog.hide();
        }
    },

    /**
     * Adds an error message to the page and adds the correct CSS classes
     * @param message string The error message to display
     * @param focusElement HTMLElement|null The HTML element to focus on when the error message link is clicked
     */
     _addErrorMessage: function(message, focusElement) {
        if(this._errorDisplay) {
            this._errorDisplay.addClass('rn_MessageBox rn_ErrorMessage');
            //add link to message so that it can receive focus for accessibility reasons
            var newMessage = focusElement ? '<a href="javascript:void(0);" onclick="document.getElementById(\'' + focusElement + '\').focus(); return false;">' + message + '</a>' : message,
                oldMessage = this._errorDisplay.get("innerHTML");
            if (oldMessage !== "") {
                newMessage = oldMessage + '<br>' + newMessage;
            }

            this._errorDisplay.set("innerHTML", newMessage);
            this._errorDisplay.one("h2") ? this._errorDisplay.one("h2").setHTML(RightNow.Interface.getMessage("ERRORS_LBL")) : this._errorDisplay.prepend("<h2>" + RightNow.Interface.getMessage("ERROR_LBL") + "</h2>");
            this._errorDisplay.one("h2").setAttribute('role', 'alert');
            if(focusElement) {
                this._errorDisplay.one('a').focus();
            }
        }
    }
});
