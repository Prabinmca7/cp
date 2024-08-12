RightNow.Widgets.ResetPassword = RightNow.Widgets.extend({
    constructor: function() {
        RightNow.Event.subscribe("evt_formButtonSubmitRequest", this._formSubmitButtonPushed, this);
    },

    /**
     * Handles when FormSubmitButton is clicked to indicate that this is for a password reset
     * and to add additional request data.
     */
    _formSubmitButtonPushed: function() {
        var requestData = {
            'pw_reset': this.data.js.resetString,
            'w_id': this.data.info.w_id,
            'rn_contextData': this.data.contextData,
            'rn_contextToken': this.data.contextToken,
            'rn_timestamp': this.data.timestamp,
            'rn_formToken': this.data.formToken
        };

        for (var toAdd in requestData) {
            RightNow.Ajax.addRequestData(toAdd, requestData[toAdd], true);
        };
    }
});
