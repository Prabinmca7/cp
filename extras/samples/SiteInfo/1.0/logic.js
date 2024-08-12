/**
 * File: logic.js
 * Abstract: Extended (from RightNow.Field) logic file for the SiteInfo widget
 * Version: 1.0
 */

RightNow.namespace('Custom.Widgets.input.SiteInfo');
Custom.Widgets.input.SiteInfo = RightNow.Field.extend({
    overrides: {
        /**
         * Overriding the general Field constructor in order to add the event listeners we need.
         */
        constructor: function() {
            this.parent();

            // Find the input text box
            this._inputSelector = this.baseSelector + "_Input";
            this.input = this.Y.one(this._inputSelector);
            this._isFormSubmitting = false;

            // Add some event listeners on the form
            this.parentForm().on("submit", this.onValidate, this)
                .on("send", this._toggleFormSubmittingFlag, this)
                .on("response", this._toggleFormSubmittingFlag, this);
        }
    },

    /**
     * Event handler executed when form is being submitted
     * Note: This function was taken directly from the standard/input/TextInput widget.
     *
     * @param type String Event name
     * @param args Object Event arguments
     */
    onValidate: function(type, args) {
        var eventObject = this.createEventObject(),
            errors = [];
            
        this._toggleErrorIndicator(false);
        
        if(!this.validate(errors)) {
            this._displayError(errors, args[0].data.error_location);
            RightNow.Event.fire("evt_formFieldValidateFailure", eventObject);
            return false;
        }
        
        RightNow.Event.fire("evt_formFieldValidatePass", eventObject);
        return eventObject;
    },

    /**
     * This function adds an error to the form's error location div
     * Note: This function was taken directly from the standard/input/TextInput widget.
     *
     * @param errors Array Errors to add
     * @param errorLocation String Id of error location div
     */
    _displayError: function(errors, errorLocation) {
        var commonErrorDiv = this.Y.one("#" + errorLocation),
            verifyField;
        if(commonErrorDiv) {   
            for(var i = 0, errorString = "", message, id = this.input.get("id"); i < errors.length; i++) {
                message = errors[i];
                if (typeof message === "object" && message !== null && message.id && message.message) {
                    id = verifyField = message.id;
                    message = message.message;
                }
                else {
                    message = (message.indexOf("%s") > -1) ? RightNow.Text.sprintf(message, this.data.attrs.label_site_url) : this.data.attrs.label_site_url + " " + message;
                }
                errorString += "<div><b><a href='javascript:void(0);' onclick='document.getElementById(\"" + id +
                    "\").focus(); return false;'>" + message + "</a></b></div> ";
            }
            commonErrorDiv.append(errorString);
        }
        if (!verifyField || errors.length > 1) {
            this._toggleErrorIndicator(true);
        }
    },
    
    /**
     * This function highlights the form field where the error was found
     * Note: This function was taken directly from the standard/input/TextInput widget.
     *
     * @param showOrHide Boolean Should the highlight be shown
     * @param fieldToHighlight Node of the field to highlight
     * @param labelToHighlight Node of the label to highlight
     */
    _toggleErrorIndicator: function(showOrHide, fieldToHighlight, labelToHighlight) {
        var method = ((showOrHide) ? "addClass" : "removeClass");
        if (fieldToHighlight && labelToHighlight) {
            fieldToHighlight[method]("rn_ErrorField");
            labelToHighlight[method]("rn_ErrorLabel");
        }
        else {
            this.input[method]("rn_ErrorField");
            this.label = this.label || this.Y.one(this.baseSelector + "_Label");
            this.label[method]("rn_ErrorLabel");
        }
    },

    /**
     * Keep track of what state the form is in. We need to know if it is being submitted
     * so that we don't show any alert dialogs for onBlur errors.
     * Note: This function was taken directly from the standard/input/TextInput widget.
     *
     * @param {String} event Name of event being fired, either 'send' or 'response'
     */
    _toggleFormSubmittingFlag: function(event){
        this._isFormSubmitting = (event === 'send');
    }
});