RightNow.Widgets.EmailCredentials = RightNow.Widgets.extend({
    constructor: function() {
        this._submitting = false;
        this.credentialType = this.data.attrs.credential_type;
        this.selectorPrefix = this.baseSelector + '_' + this.credentialType;
        this.form = this.Y.one(this.selectorPrefix + '_Form');
        this.form.on('submit', this._onSubmit, this, null);
        this.loadingIcon = this.Y.one(this.selectorPrefix + '_LoadingIcon');
        this.errorDivID = this.baseDomID + '_' + this.credentialType + '_ErrorDiv';
        this.inputField = this.Y.one(this.selectorPrefix + '_Input');
        this.requestType = this.data.js.request_type;
        if (this.data.attrs.initial_focus && this.inputField) {
            this.inputField.focus();
        }
    },

    /**
     * Event handler for when response has been sent from server
     * @param response {object}
     * @param originalEventObject {object}
     */
    _onResponseReceived: function(response, originalEventObject) {
        if (RightNow.Event.fire('evt_' + this.requestType + 'Response', {data: originalEventObject, response: response})) {
            this._submitting = false;
            var dialogBody = this.Y.Node.create('<div></div>')
                .addClass("rn_EmailCredentialsSuccessDialog")
                .set('innerHTML', response.message);
            if(!this.successDialog){
                this.successDialog = RightNow.UI.Dialog.actionDialog(RightNow.Interface.getMessage("INFORMATION_LBL"), dialogBody);
            }
            this.successDialog.show();
            this.loadingIcon.removeClass('rn_Loading');
        }
    },

    /**
     * Event when submit button has been clicked
     */
    _onSubmit: function(event, arg) {
        if (this._submitting || !this.inputField) {
            return (this._submitting) ? false : null;
        }

        var value = this.Y.Lang.trim(this.inputField.get('value'));

        if (!value.length) {
            this._displayErrorMessage(this.data.js.field_required);
            return;
        }

        RightNow.UI.hide(this.errorDivID);

        if (this.credentialType === 'password') {
            // value is the contact's username. A password reset notification will be sent.
            var errorMessage = "";
            //check spaces, quotes and brackets
            if (value.indexOf(' ') > -1)
                errorMessage = RightNow.Text.sprintf(RightNow.Interface.getMessage("PCT_S_MUST_NOT_CONTAIN_SPACES_MSG"), RightNow.Interface.getMessage("USERNAME_LBL"));
            else if (value.indexOf('"') > -1)
                errorMessage = RightNow.Text.sprintf(RightNow.Interface.getMessage("PCT_S_CONTAIN_DOUBLE_QUOTES_MSG"), RightNow.Interface.getMessage("USERNAME_LBL"));
            else if (value.indexOf("<") > -1 || value.indexOf(">") > -1)
                errorMessage = RightNow.Text.sprintf(RightNow.Interface.getMessage("PCT_S_CNT_THAN_MSG"), RightNow.Interface.getMessage("USERNAME_LBL"));
            if (errorMessage !== "") {
                this._displayErrorMessage(errorMessage);
                return false;
            }
        }
        else if (!RightNow.Text.isValidEmailAddress(value)) {
            // value is the contact's email address. Email their username.
            this._displayErrorMessage(RightNow.Interface.getMessage("EMAIL_IS_NOT_VALID_MSG"));
            return false;
        }
        this._submitting = true;
        var eventObject = new RightNow.Event.EventObject(this, {data: {
            requestType: this.requestType,
            value: value,
            w_id: this.data.info.w_id
        }});
    
        if(this.data.js.f_tok){
            RightNow.Event.subscribe("evt_formTokenUpdate", RightNow.Widgets.onFormTokenUpdate, this);
            // Get a new f_tok value on each uploads
            RightNow.Event.fire("evt_formTokenRequest", new RightNow.Event.EventObject(this, {data:{formToken:this.data.js.f_tok}}));

            eventObject.data.f_tok = this.data.js.f_tok;
        }
        
        if (RightNow.Event.fire('evt_' + this.requestType + 'Request', eventObject)) {
            this.loadingIcon.addClass('rn_Loading');
            RightNow.Ajax.makeRequest(this.data.attrs.email_credentials_ajax, eventObject.data, {
                successHandler: this._onResponseReceived, scope: this, data: eventObject, json: true
            });
        }
    },

    /**
    * Displays an error message in a div above the form.
    * @param errorMessage String The error message
    */
    _displayErrorMessage: function(errorMessage) {
        if (!this._errorMessageDiv) {
            this._errorMessageDiv = this.Y.Node.create('<div class="rn_MessageBox rn_ErrorMessage"></div>')
                .set('id', this.errorDivID);
            this.form.insert(this._errorMessageDiv, 'before');
        }
        this._errorMessageDiv.set('innerHTML', "<b><a href='javascript:void(0);' onclick='document.getElementById(\"" + this.inputField.get('id') +
                "\").focus(); return false;'><h2>" + RightNow.Interface.getMessage("ERROR_LBL") + "</h2>" + errorMessage + "</a></b>");
        this._errorMessageDiv.setAttribute('role', 'alert');
        RightNow.UI.show(this._errorMessageDiv);
        var errorLink = this._errorMessageDiv.one('a');
        if (errorLink)
            errorLink.focus();
    }
});
