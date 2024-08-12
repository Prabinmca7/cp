/**
 * File: logic.js
 * Abstract: Extending logic for ExtendedAnswerFeedback widget.
 * Version: 1.0
 */
RightNow.namespace('Custom.Widgets.feedback.ExtendedAnswerFeedback');
Custom.Widgets.feedback.ExtendedAnswerFeedback = RightNow.Widgets.AnswerFeedback.extend({
    /**
     * Place all properties that intend to
     * override those of the same name in
     * the parent inside `overrides`.
     */
    overrides: {
        /**
         * Overrides RightNow.Widgets.AnswerFeedback#constructor.
         */
        constructor: function() {
            // Call into parent's constructor
            this.parent();

            // Overriding the parent's _submitFeedback method in order to add the custom field values
            // to the AJAX request's data would require duplicating a lot of code.
            // Rather, by subscribing to this event, which fires just prior to the AJAX request, we can
            // then just add to the data that'll be sent in the request.
            RightNow.Event.subscribe('evt_answerFeedbackRequest', this.onAnswerFeedbackRequest, this);
        },

        /**
         * Overrides RightNow.Widgets.AnswerFeedback#_showDialog.
         */
        _showDialog: function() {
            this.parent();

            // If there's not an email field present (user is logged-in) or it has an
            // autofilled value (saved email from the session) then the feedback textarea
            // is autofocused when the dialog is shown. But that's below our new type
            // custom field, so we'll refocus on the first type radio input instead.
            if (document.activeElement === this.Y.Node.getDOMNode(this._feedbackField)) {
                this.Y.one(this.baseSelector + '_FeedbackType').one('input').focus();
            }
        },

        /**
         * Calls the parent's _validateDialogData method before validating
         * that a feedback type option has been selected.
         * @return {boolean} True if the parent validation passed and a feedback type
         *      option has been selected; False if parent validation failed or a feedback
         *      type option hasn't been selected
         */
        _validateDialogData: function() {
            var parentReturnValue = this.parent();

            this.Y.all(this.baseSelector + '_FeedbackType input').some(function(input) {
                if (input.get('checked')) {
                    this._selectedFeedbackType = input.get('value');
                    return true;
                }
            }, this);

            if (!this._selectedFeedbackType) {
                this._addErrorMessage(
                    // Use the label for the fieldset for the '{field name} is required' error message.
                    RightNow.Text.sprintf(RightNow.Interface.getMessage("PCT_S_IS_REQUIRED_MSG"), this.data.js.typeLabel),
                    // This is the DOM element ID of the first radio input to focus on when the error message's link is clicked.
                    this.Y.one(this.baseSelector + '_FeedbackType').one('input').get('id')
                );
                return false;
            }

            return parentReturnValue;
        }
    },

    /**
     * The value of the selected radio input for the type field.
     * Set during validation and retrieved to send the value along
     * in the AJAX request.
     * @type {null|string}
     */
    _selectedFeedbackType: null,

    /**
     * Called when the 'evt_answerFeedbackRequest' event is fired
     * by the parent just prior to making the AJAX request that posts
     * the supplied EventObject data to the server. Since objects in JavaScript
     * are passed by reference, modifying the EventObject's data will
     * add these additional fields to the data that's posted.
     * @param {string} evt Event name
     * @param {array} args Event data; in this case, the EventObject that's supplied
     * is in only element in the array
     */
    onAnswerFeedbackRequest: function(evt, args) {
        args[0].data.type = this._selectedFeedbackType;
        args[0].data.source = this.Y.one(this.baseSelector + '_Source').get('value');
    }
});
