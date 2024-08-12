RightNow.Widgets.LoginForm = RightNow.Widgets.extend({
    constructor: function(){
        this.Y.one(this.baseSelector + "_Submit").on("click", this._onSubmit, this);
        this._usernameField = this.Y.one(this.baseSelector + "_Username");
        this._passwordField = this.Y.one(this.baseSelector + "_Password");
        if(this.data.attrs.initial_focus && !this.Y.one('.rn_Dialog'))
        {
            if(this._usernameField && this._usernameField.get("value") === '')
                this._usernameField.focus();
            else if(this._passwordField)
                this._passwordField.focus();
        }

        //this check is necessary because the widget may be used in CPv3.1 and CPv3.2,
        //which doesn’t contain the framework function formTokenRegistration
        if(RightNow.Widgets.formTokenRegistration)
        {
            RightNow.Widgets.formTokenRegistration(this);
        }
    },
    /**
     * Function used to parse out the URL where we should redirect to
     * after a successful login
     * @param result Object The response object returned from the server
     * @return String The URL to redirect to
     */
     _getRedirectUrl: function(result){
         var redirectUrl;
         if(this.data.js && this.data.js.redirectOverride)
             redirectUrl = RightNow.Url.addParameter(this.data.js.redirectOverride, 'session', result.sessionParm.substr(result.sessionParm.lastIndexOf("/") + 1));
         else
             redirectUrl = (this.data.attrs.redirect_url || result.url) + ((result.addSession) ? result.sessionParm : "");

        redirectUrl += this.data.attrs.append_to_url;

        if (result.forceRedirect) {
            redirectUrl = RightNow.Url.addParameter(result.forceRedirect, 'redirect', encodeURIComponent(redirectUrl));
        }

        return redirectUrl;
    },

    /**
     * Event handler for when login has returned. Handles either successful login or failed login
     * @param response {Object} Result from server
     * @param originalEventObject {Object} Original request object sent in request
     */
    _onLoginResponse: function(response, originalEventObject)
    {
        if(!RightNow.Event.fire("evt_loginFormSubmitResponse", {data: originalEventObject, response: response})){
            return;
        }

        this._toggleLoading(false);

        if(response.success)
        {
            this.Y.one(this.baseSelector + "_Content").set("innerHTML", response.message);
            var redirectUrl = this._getRedirectUrl(response);
            if(this.Y.UA.ie && this.Y.UA.ie < 9 && RightNow.Text.beginsWith(redirectUrl, '/ci/fattach/get/'))
                this.Y.one(this.baseSelector).set('innerHTML', RightNow.Text.sprintf(RightNow.Interface.getMessage("PLS_CLCK_HREF_EQS_PCT_S_THAN_S_MSG"), redirectUrl));
            else
                RightNow.Url.navigate(redirectUrl);
        }
        else
        {
            this._addErrorMessage(response.message, this.baseDomID + '_Username', response.showLink);
            if(response != null && response.newFormToken) {
                RightNow.Event.fire("evt_formTokenUpdate", new RightNow.Event.EventObject(this, {data: {newToken: response.newFormToken}}));
            }
        }
    },

    /**
     * Event handler for when login button is clicked.
     * @param {Object} e YUI Event facade
     */
    _onSubmit: function(e)
    {
        e.halt();

        var username = (this._usernameField) ? this.Y.Lang.trim(this._usernameField.get("value")) : "",
            errorMessage, eventObject;

        if(username.indexOf(' ') > -1)
            errorMessage = RightNow.Text.sprintf(RightNow.Interface.getMessage("PCT_S_MUST_NOT_CONTAIN_SPACES_MSG"), RightNow.Interface.getMessage("USERNAME_LBL"));
        else if(username.indexOf('"') > -1)
            errorMessage = RightNow.Text.sprintf(RightNow.Interface.getMessage("PCT_S_CONTAIN_DOUBLE_QUOTES_MSG"), RightNow.Interface.getMessage("USERNAME_LBL"));
        else if(username.indexOf("<") > -1 || username.indexOf(">") > -1)
            errorMessage = RightNow.Text.sprintf(RightNow.Interface.getMessage("PCT_S_CNT_THAN_MSG"), RightNow.Interface.getMessage("USERNAME_LBL"));

        if(errorMessage)
        {
            this._addErrorMessage(errorMessage, this.baseDomID + '_Username');
            return false;
        }

        eventObject = new RightNow.Event.EventObject(this, {data: {
           login: username,
           password: ((!this.data.attrs.disable_password && this._passwordField) ? this._passwordField.get("value") : ""),
           url: window.location.pathname,
           w_id: this.data.info.w_id,
           f_tok: this.data.js.f_tok
        }});
        if(RightNow.Event.fire("evt_loginFormSubmitRequest", eventObject)){
            this._toggleLoading(true);

            if(RightNow.Event.noSessionCookies()) {
                //Attempt to set a test login cookie
                RightNow.Event.setTestLoginCookie();
            }
            RightNow.Ajax.makeRequest(this.data.attrs.login_ajax, eventObject.data, {successHandler: this._onLoginResponse, scope: this, data: eventObject, json: true});

            if(this.Y.UA.ie && window.external && "AutoCompleteSaveForm" in window.external)
            {
                //since this form is submitted by script, force ie to do auto_complete
                var form = document.getElementById(this.baseDomID + "_Form");
                if(form)
                    window.external.AutoCompleteSaveForm(form);
            }
        }
    },

    /**
     * Utility function to display an error message
     * @param message String  The error message to display
     * @param focusElement String The ID of the element to focus when clicking on the error message
     * @param showLink [optional] Boolean Denotes if error message should be surrounded in a link tag
     */
    _addErrorMessage: function(message, focusElement, showLink){
        var error = this.Y.one(this.baseSelector + "_ErrorMessage");
        if(error)
        {
            error.addClass('rn_MessageBox rn_ErrorMessage');
            //add link to message so that it can receive focus for accessibility reasons
            if(showLink === false)
            {
                error.set("innerHTML", message);
            }
            else
            {
                error.set("innerHTML", '<a href="javascript:void(0);" onclick="document.getElementById(\'' + focusElement + '\').focus(); return false;">' + message + '</a>');
                error.one('a').focus();
            }
            error.one("h2") ? error.one("h2").setHTML(RightNow.Interface.getMessage("ERRORS_LBL")) : error.prepend("<h2>" + RightNow.Interface.getMessage("ERROR_LBL") + "</h2>");
            error.one("h2").setAttribute('role', 'alert');
        }
    },

    /**
     * Toggles the state of loading indicators:
     * Fades the form out/in (for decent browsers)
     * Disables/enables form inputs
     * Adds/Removes loading indicator class
     * @param {Boolean} turnOn Whether to add or remove the loading indicators.
     */
    _toggleLoading: function(turnOn) {
        this._widgetContent || (this._widgetContent = this.Y.one(this.baseSelector + '_Content'));

        this._widgetContent.all('input')[(turnOn) ? 'setAttribute' : 'removeAttribute']('disabled', true);

        if (!this.Y.UA.ie || this.Y.UA.ie > 8) {
            // YUI's animation causes JS execution in IE7-8 to fail in weird ways, like failing to redirect the page
            // when a user's successfully logged in...
            this._widgetContent.transition({
                opacity: turnOn ? 0 : 1,
                duration: 0.4
            });
            this.Y.one(this.baseSelector)[(turnOn) ? 'addClass' : 'removeClass']('rn_Loading');
        }
    }
});
