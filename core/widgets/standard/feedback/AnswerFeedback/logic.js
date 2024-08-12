RightNow.Widgets.AnswerFeedback = RightNow.Widgets.extend({
    constructor: function() {
        this._dialog = this._keyListener = this._thanksLabel = null;
        this._rate = 0;
        var Event = this.Y.Event;

        if (this.data.js.buttonView) {
            var noButton = this.Y.one(this.baseSelector + "_RatingNoButton"),
                yesButton = this.Y.one(this.baseSelector + "_RatingYesButton");
            Event.attach("click", this._onClick, noButton, this, 1);
            Event.attach("click", this._onClick, yesButton, this, 2);
        }
        else if (this.data.attrs.use_rank_labels) {
            var ratingButton = this.baseSelector + "_RatingButton_";
            for(var i = 1, ids = []; i <= this.data.attrs.options_count; ++i) {
                ids.push(ratingButton + i);
            }
            this.Y.Array.each(ids, function(id, i) {
                Event.attach("click", this._onClick, id, this, i + 1);
            }, this);
        }
        else {
            var ratingCell = this.baseSelector + "_RatingCell_";
            for(i = 1, ids = []; i <= this.data.attrs.options_count; ++i) {
                ids.push(ratingCell + i);
            }
            this.Y.Array.each(ids, function(id, i) {
                var j = i + 1;
                Event.attach("mouseover", this._onCellOver, id, this, j);
                Event.attach("focus", this._onCellOver, id, this, j);
                Event.attach("mouseout", this._onCellOut, id, this, j);
                Event.attach("blur", this._onCellOut, id, this, j);
                Event.attach("click", this._onClick, id, this, j);
            }, this);
        }
    },
    /**
     * Event handler for when a user clicks on an answer rating
     * @param type String Event name
     * @param rating Int rating
     */
    _onClick: function(event, rating) {
        this._rate = rating;
        this._submitAnswerRating();

        // Show feedback dialog if indicated.
        if (this._rate <= this.data.attrs.dialog_threshold) {
            //If attribute is set, don't display widget but rather show popup page
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
     * Constructs and shows the dialog
     * @return None
     */
    _showDialog: function() {
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
            this.Y.one('#' + this._dialog.id).addClass('rn_AnswerFeedbackDialog');
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
        this._incidentCreateFlag = true;  //Keep track that we're creating an incident.
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
     * Submit data to the server.
     */
    _submitFeedback: function() {
        var eventObject = new RightNow.Event.EventObject(this, {data: {
                a_id: this.data.js.answerID,
                rate: this._rate,
                threshold: this.data.attrs.dialog_threshold,
                options_count: this.data.attrs.options_count,
                message: this._feedbackField.get('value'),
                email: (this._emailField) ? this._emailField.get('value') : this.data.js.email
        }});
    
        if(this.data.js.f_tok){
            RightNow.Event.subscribe("evt_formTokenUpdate", RightNow.Widgets.onFormTokenUpdate, this);
            // Get a new f_tok value on each uploads
            RightNow.Event.fire("evt_formTokenRequest", new RightNow.Event.EventObject(this, {data:{formToken:this.data.js.f_tok}}));

            eventObject.data.f_tok = this.data.js.f_tok;
        }
        
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
     * Submit answer rating for clicktrack record.
     * @return boolean
     */
    _submitAnswerRating: function() {
        var eventObject = new RightNow.Event.EventObject(this, {data: {
                a_id: this.data.js.answerID,
                rate: this._rate,
                options_count: this.data.attrs.options_count
        }});
    
        if(this.data.js.f_tok){
            RightNow.Event.subscribe("evt_formTokenUpdate", RightNow.Widgets.onFormTokenUpdate, this);
            // Get a new f_tok value on each uploads
            RightNow.Event.fire("evt_formTokenRequest", new RightNow.Event.EventObject(this, {data:{formToken:this.data.js.f_tok}}));

            eventObject.data.f_tok = this.data.js.f_tok;
        }
        
        RightNow.ActionCapture.record('answer', 'rate', this.data.js.answerID);
        //Record the rating as a percentage. To get it, subtract 1 off both the count and the rating to support the 0% case
        RightNow.ActionCapture.record('answer', 'rated', ((this._rate - 1) / (this.data.attrs.options_count - 1)) * 100);
        if(RightNow.Event.fire("evt_answerRatingRequest", eventObject)) {
            RightNow.Ajax.makeRequest(this.data.attrs.submit_rating_ajax, eventObject.data, {successHandler: this._onRatingResponseReceived, scope: this, data: {eventName: "evt_answerRatingResponse", data: eventObject}, json: true});

            //There's no need to wait for the request to finish before we display a note to the user
            this._replaceRatingElementsWithMessage();
        }
    },

    /**
     * Updates the UI controls to replace the rating buttons with the label_feedback_submit label and focus on the label.
     */
    _replaceRatingElementsWithMessage: function() {
        var ratingElement = this.Y.one(this.baseSelector + ((this.data.js.buttonView || this.data.attrs.use_rank_labels) ? "_RatingButtons" : "_RatingMeter"));

        if(ratingElement) {
            this._thanksLabel = this.Y.Node.create('<div id="rn_' + this.instanceID + '_ThanksLabel" class="rn_ThanksLabel">');
            this._thanksLabel.set('innerHTML', this.data.attrs.label_feedback_thanks).set('tabIndex', -1);
            ratingElement.replace(this._thanksLabel);
            this._thanksLabel.focus();
        }
    },

    /**
     * Event handler for server sends rating response.
     * @param response Mixed - Integer on success, string on error.
     * @param originalEventObj Object event object
     */
    _onRatingResponseReceived: function(response, originalEventObj) {
        //We don't have anything else to do with this response currently, so just fire the response event
        RightNow.Event.fire("evt_answerRatingResponse", response, originalEventObj);
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
    },

    /*-----------------  UI Handling Routines -----------------*/

    /**
     * Event handler for when the cursor is over a rating cell
     * @param event Object Event
     * @param chosenRating Integer Index of chosen control.
     */
    _onCellOver: function(event, chosenRating) {
        if(this._rate < 1) {
            this._updateCellClass(1, chosenRating, "add");
            this._updateCellClass(chosenRating + 1, this.data.attrs.options_count, "remove");
        }
    },

    /**
    * Adds or removes a CSS class from a range of rating cells.
    * @param minBound Int Starting point of first index into rating cells
    * @param maxBound Int Ending point of last index into rating cells
    * @param removeOrAddClass String 'add' or 'remove'
    */
    _updateCellClass: function(minBound, maxBound, removeOrAddClass) {
        var elementID = this.baseSelector + "_RatingCell_";
        for(var i = minBound; i <= maxBound; i++) {
            if (removeOrAddClass === "add") {
                this.Y.one(elementID + i).addClass('rn_RatingCellOver');
            } else {
                this.Y.one(elementID + i).removeClass('rn_RatingCellOver');
            }
        }
    },

    /**
     * Event handler for when the cursor leaves a rating cell
     * @param event Object Event
     * @param args Object Event arguments
     */
    _onCellOut: function(event, args) {
        if(this._rate < 1)
            this._updateCellClass(1, this.data.attrs.options_count, "remove");
    }
});
