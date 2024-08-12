RightNow.Widgets.ProactiveSurveyLink = RightNow.Widgets.extend({
    /**
     * Widget constructor.
     */
    constructor: function() {
        RightNow.ActionCapture.record('proactiveSurveyLink', 'impression', this.data.attrs.survey_id);
        if (this.data.attrs.wait_for_event) {
            RightNow.Event.subscribe("evt_showOffer", this._onShowOffer, this);
        }
        else {
            this._onShowOffer();
        }
    },

    /**
     * Event handler for wait_for_event
     */
    _onShowOffer: function() {
        this._dialog = null;
        if (this.data.attrs.seconds > 0) {
            this.Y.Lang.later(this.data.attrs.seconds * 1000, this, '_showDialog');
        }
        else {
            this._showDialog();
        }
    },

    /**
     * Show modal dialog
     */
    _showDialog: function() {
        RightNow.ActionCapture.record('proactiveSurveyLink', 'show', this.data.attrs.survey_id);
        this._setCookie();
        var buttons = [ { text: this.data.attrs.label_cancel_button, handler: {fn: this._onCancel, scope: this}, isDefault: false},
                        { text: this.data.attrs.label_accept_button, handler: {fn: this._onSubmit, scope: this}, isDefault: true}];
        this._dialog = RightNow.UI.Dialog.actionDialog(this.data.attrs.title, this.Y.one('#' + this.baseDomID),
            {"buttons": buttons,
            "width": this.data.attrs.dialog_width}
        );

        RightNow.UI.show(this.baseSelector);
        this._dialog.show();
        this._dialog.enableButtons();
    },

    _onCancel: function() {
        this._dialog.hide();
    },

    _onSubmit: function() {
        switch (this.data.attrs.target) {
            case '_self':
                window.location = this.data.js.survey_url;
                break;
            case '_blank':
                window.open(this.data.js.survey_url);
                break;
        }
        this._dialog.hide();
    },


    /**
     * Sets a cookie in the format instanceID=questionID to expire when cookie_duration is complete
     */
    _setCookie: function() {
        var date = new Date();
        date.setDate(date.getDate() + this.data.attrs.cookie_duration);
        document.cookie = this.instanceID + "=" + this.data.attrs.survey_id + ";expires=" + date.toUTCString() + ";path=/";
    }
});
