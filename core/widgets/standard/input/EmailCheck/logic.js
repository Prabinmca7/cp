RightNow.Widgets.EmailCheck = RightNow.Widgets.extend({
    constructor: function() {
        this._emailElement = this.Y.one(this.baseSelector + '_Email');
        this._submitElement = this.Y.one(this.baseSelector + '_Submit');
        this.requestInProgress = false;

        if(this.data.attrs.initial_focus && this._emailElement && this._emailElement.focus)
            this._emailElement.focus();

        this._submitElement.on("click", this._onSubmitClick, this);
    },

    /**
     * Event handler executed when form is being submitted
     */
    _onSubmitClick: function() {
        if(this._emailElement && this.requestInProgress === false) {
            var email = this.Y.Lang.trim(this._emailElement.get("value"));
            if (email !== '' && RightNow.Text.isValidEmailAddress(email)) {
                this.requestInProgress = true;
                var eo = new RightNow.Event.EventObject(this, {data: {email: email, contactToken: this.data.js.contactToken, checkForChallenge: true}});
                if (RightNow.Event.fire("evt_accountExistsRequest", eo)) {
                    RightNow.Ajax.makeRequest(this.data.attrs.account_exists_ajax, eo.data, {
                        successHandler: this._onEmailCheckResponse,
                        scope: this,
                        data: eo,
                        json: true
                    });

                    //Show the loading icon and status message
                    this._loadingElement = this._loadingElement || this.Y.one(this.baseSelector + "_LoadingIcon");
                    RightNow.UI.show(this._loadingElement);
                }
            }
            else {
                RightNow.UI.Dialog.messageDialog(this.data.attrs.label_warning, {
                    icon: "WARN",
                    width: "250px",
                    focusElement: this._emailElement
                });
            }
        }
    },

    /**
     * Event handler for when form submission returns from the server
     * @param {Object|Boolean} response Server response to request
     * @param {Object} originalEventObject Event arguments
     */
    _onEmailCheckResponse: function(response, originalEventObject) {
        if (RightNow.Event.fire("evt_accountExistsResponse", response, originalEventObject)) {
            var emailAddressParameter = this._emailElement ? ("/Contact.Emails.PRIMARY.Address/" + this.Y.Lang.trim(this._emailElement.get("value"))) : "",
                additionalParameters = "/" + (this.data.attrs.additional_parameters || ""),
                // @codingStandardsIgnoreStart
                url = (response)
                    // Account already exists
                    ? this.data.attrs.redirect_existing_contact
                    // No account exists
                    : this.data.attrs.redirect_new_contact + ((this.data.attrs.redirect_after_contact_creation)
                        ? "/redirect/" + encodeURIComponent(RightNow.Text.getSubstringAfter(this.data.attrs.redirect_after_contact_creation, "/app/"))
                        : ''
                );
                // @codingStandardsIgnoreEnd

            RightNow.UI.hide(this._loadingElement);
            RightNow.Url.navigate(url + emailAddressParameter + this.data.attrs.add_params_to_url + additionalParameters);
        }
        this.requestInProgress = false;
    }
});
