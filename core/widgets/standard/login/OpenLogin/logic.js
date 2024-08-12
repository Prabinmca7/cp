RightNow.Widgets.OpenLogin = RightNow.Widgets.extend({
    constructor: function() {
        this._redirectUrl = this.data.attrs.redirect_url;
        if (this.data.attrs.one_click_access_enabled) {
            this.Y.Event.attach("click", this._onLogin, this.baseSelector + "_ProviderButton", this);
        }
        else {
            this.Y.Event.attach("click", this._onClick, this.baseSelector + "_ProviderButton", this);
            this.Y.Event.attach("click", this._onLogin, this.baseSelector + "_LoginButton", this);
        }
        RightNow.Event.on("evt_requireLogin", this._socialAction, this);
        RightNow.Event.on("evt_FederatedProviderSelected", function() {
            this.Y.one(this.baseSelector + "_ActionArea").hide();
        }, this);

        if (RightNow.Url.getParameter("emailerror") && this.data.attrs.controller_endpoint.search(/twitter/i) > 0) {
            this._displayMessage("email");
        }
        else if (this.data.js && this.data.js.error) {
            this._displayMessage("error", this.data.js.error);
        }
    },

    /**
     * Determines if this is a social action, and adds any params that need to be passed through.
     * @param {Object} event The event name
     * @param {Object} args Arguments sent with the event
     */
    _socialAction: function(event, args) {
        var data = (args[0] && args[0].data) ? args[0].data : null;

        if (data && data.isSocialAction && data.urlParamsToAdd) {
            // if redirect url is blank, set it to the current page
            if (this._redirectUrl === '') {
                this._redirectUrl = window.location.pathname;
            }

            for (var param in data.urlParamsToAdd) {
                this._redirectUrl = RightNow.Url.addParameter(this._redirectUrl, param, data.urlParamsToAdd[param]);
            }
        }
    },

    /**
    * Displays an additional widget area when a provider is clicked.
    * @param {Object} event The click event
    */
    _onClick: function(event) {
        var actionArea = this.Y.one(this.baseSelector + "_ActionArea");

        if (this.data.attrs.display_in_dialog) {
            this._dialog = this._dialog || RightNow.UI.Dialog.actionDialog(RightNow.Interface.getMessage("LOGIN_LBL"), actionArea, {
                buttons: [{text: RightNow.Interface.getMessage("CANCEL_LBL"), handler: function(){this.hide();}}],
                cssClass: "rn_OpenLogin rn_OpenLoginDialog",
                navButtons: true,
                width: '100%'
            });
            if (this._dialog.submitEvent) {
                this._dialog.cfg.setProperty("hideaftersubmit", false);
                this._dialog.submitEvent.subscribe(this._onLogin, null, this);
            }
            this._dialog.show();
        }
        else {
            // fire event to notify other widget instances to hide their actionArea
            RightNow.Event.fire("evt_FederatedProviderSelected", new RightNow.Event.EventObject(this));
        }

        if (this.data.attrs.display_in_dialog) {
            RightNow.UI.show(actionArea);
            this._selectProvider();
        }
        else {
            actionArea.setStyle('opacity', '0').removeClass("rn_Hidden").show();
            actionArea.transition({
                opacity: 1,
                duration: 0.1
            }, RightNow.Event.createDelegate(this, this._selectProvider));
        }
    },

    /**
    * Selects the appropriate input element.
    */
    _selectProvider: function() {
        var loginButton = this.Y.one(this.baseSelector + "_LoginButton");
        if (!this.data.attrs.openid && loginButton) {
            this._setAriaSelected(loginButton.set("tabIndex", -1)).focus();
        }
        else if (this.data.attrs.openid && this.data.attrs.openid_placeholder) {
            this._selectOpenIDProvider();
        }
    },

    /**
    * Sets the aria-selected attribute on the specified element.
    * Maintains an internal list in order to ensure that only one element for
    * all widget instances is aria-selected="true".
    * @param {Object} selctedElement The element to set the attribute
    * @return {Object} selectedElement
    */
    _setAriaSelected: function(selectedElement) {
        this._setAriaSelected._items = this._setAriaSelected._items || [];
        var items = this._setAriaSelected._items,
            Dom = this.Y.DOM,
            alreadyInList = false,
            i, length;
        for (i = 0, length = items.length; i < length; i++) {
            Dom.setAttribute(items[i], 'aria-selected', 'false');
            if(items[i] === selectedElement.get('id')) {
                alreadyInList = true;
            }
        }
        if (!alreadyInList) {
            this._setAriaSelected._items.push(selectedElement.get('id'));
        }
        return selectedElement.setAttribute('aria-selected', 'true');
    },

    /**
    * Displays a dialog.
    * @param {String} messageType The type of message being displayed. Either 'email' or 'error'
    * @param {String=} errorMessage optional error message
    */
    _displayMessage: function(messageType, errorMessage) {
        if (!this._displayMessage._displayingMessage) {
            //only display error dialog once across all widget instances
            this._displayMessage._displayingMessage = true;
            if (messageType === "email") {
                this._displayEmailPrompt();
            }
            else if (messageType === "error") {
                if(RightNow.UI !== undefined && RightNow.UI.Dialog !== undefined) {
                    RightNow.UI.Dialog.messageDialog(errorMessage, {icon: "WARN", title: RightNow.Interface.getMessage("OOPS_LBL"), width: "330px"});
                }
                else {
                    alert(errorMessage);
                }
            }
        }
    },

    _displayEmailPrompt: function() {
        this.Y.augment(this, RightNow.RequiredLabel);
        var dialog, errorDiv, emailField,
            Dialog = RightNow.UI.Dialog,
            inputID = this.baseDomID + "_Email",
            dialogBody = this.Y.Node.create("<div class='rn_OpenLogin rn_OpenLoginDialog'></div>")
                .set("innerHTML", new EJS({text: this.getStatic().templates.emailPrompt}).render({
                    inputID: inputID,
                    inputLabel: this.data.attrs.label_email_address,
                    promptLabel: this.data.attrs.label_email_prompt
            })),
            successHandler = function(serverResponse) {
                if (serverResponse.responseText === "true") {
                    RightNow.Url.navigate(this.data.attrs.redirect_url);
                }
                else {
                    this._submittingEmail = false;
                    dialog.enableButtons();
                    Dialog.messageDialog(serverResponse.suggestedErrorMessage || RightNow.Interface.getMessage("THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG"), {icon: "WARN"});
                }
            },
            submitHandler = function() {
                if (!this._submittingEmail) {
                    this._submittingEmail = true;
                    dialog.disableButtons();
                    emailField = emailField || this.Y.one("#" + inputID);
                    if (emailField && emailField.get("value")) {
                        var email = emailField.set("value", this.Y.Lang.trim(emailField.get("value"))).get("value");
                        if (RightNow.Text.isValidEmailAddress(email)) {
                            RightNow.UI.hide(errorDiv);
                            RightNow.Ajax.makeRequest(this.data.attrs.provide_email_ajax, {
                                email: email,
                                userData: RightNow.Url.getParameter("emailerror")
                            }, {
                                successHandler: successHandler,
                                scope: this
                            });
                            return;
                        }
                        else if (errorDiv) {
                            errorDiv.removeClass("rn_Hidden").one("a").focus();
                        }
                        else {
                            errorDiv = this.Y.Node.create(
                                "<div class='rn_MessageBox rn_ErrorMessage'>" +
                                "<a href='javascript:void(0);' onclick='document.getElementById(\"" + inputID + "\").focus();'>" +
                                RightNow.Text.sprintf(RightNow.Interface.getMessage("PCT_S_IS_INVALID_MSG"), this.data.attrs.label_email_address) +
                                "</a></div>"
                            );

                            dialogBody.insert(errorDiv, 'before');
                            errorDiv.one("a").focus();
                        }
                    }
                    dialog.enableButtons();
                    this._submittingEmail = false;
                }
            };

        dialog = Dialog.actionDialog(this.data.attrs.label_email_prompt_title, dialogBody, {
                    buttons: [{text: this.data.attrs.label_email_prompt_submit_button, handler: {fn: submitHandler, scope: this}, isDefault: true},
                              {text: this.data.attrs.label_email_prompt_cancel_button, handler: function(){this.hide();}}],
                    width: "330px"
        });
        Dialog.addDialogEnterKeyListener(dialog, submitHandler, this);
        dialog.show();
    },

    /**
    * Called when an OpenID provider is clicked. Performs preselection on the provider's pre-filled URL.
    */
    _selectOpenIDProvider: function(){
        if (this.data.attrs.openid_placeholder && this.data.attrs.openid_placeholder.indexOf("[") > -1) {
            var input = this.Y.one(this.baseSelector + "_ProviderUrl"),
                start, end, selection;
            if (input) {
                this._setAriaSelected(input);
                input.focus();
                start = input.get("value").indexOf("[");
                end = input.get("value").indexOf("]") + 1;
                if (input.get("value") && start > -1 && end > start) {
                    input = this.Y.Node.getDOMNode(input);
                    if (window.getSelection) {
                        selection = window.getSelection();
                         if (selection.rangeCount > 0)
                            selection.removeAllRanges();
                        input.setSelectionRange(start, end);
                    }
                    else if (document.selection && document.selection.createRange) {
                        //older IE
                        selection = input.createTextRange();
                        selection.collapse(true);
                        selection.moveStart("character", start);
                        selection.moveEnd("character", end - start);
                        selection.select();
                    }
                }
            }
        }
    },

    /**
    * Navigates the browser to the controller endpoint.
    */
    _onLogin: function(evt){
        evt.preventDefault();
        if (this._cookiesEnabled()) {
            var input = this.Y.one(this.baseSelector + "_ProviderUrl"),
                inputValue = ((input) ? input.set("value", this.Y.Lang.trim(input.get("value"))).get("value") : "");
            this._goToUrl(inputValue);
        }
        else {
            RightNow.UI.Dialog.messageDialog(RightNow.Interface.getMessage("COOKIES_ENABLED_BROWSER_ORDER_LOG_MSG"), {icon: "WARN"});
        }
    },

    /**
    * Navigates the browser to the controller endpoint.
    * @param {String} inputValue the value of the input field
    */
    _goToUrl: function(inputValue) {
        var goToUrl = function(url) {
                if (this.Y.UA.ie) {
                    // IE doesn't set http_referer when window.location changes.
                    // IE9 says this behavior is a feature, not a bug.
                    var Url = RightNow.Url,
                        referLink = this.Y.Node.getDOMNode(
                            this.Y.Node.create("<a class='rn_Hidden'></a>")
                                .set("href", ((Url.getSession()) ? Url.addParameter(url, "session", Url.getSession()) : url))
                        );
                    document.body.appendChild(referLink);
                    referLink.click();
                }
                else {
                    RightNow.Url.navigate(url);
                }
            },
            urlToGoTo = "";
        if (this.data.attrs.openid) {
            if (this.data.attrs.preset_openid_url &&
                inputValue !== "" &&
                inputValue !== this.data.attrs.openid_placeholder &&
                !RightNow.Text.isValidUrl(inputValue)) {
                // username on a preset openid service
                urlToGoTo = this.data.attrs.preset_openid_url.replace(/\[username\]/, inputValue);
            }
            else if (!this.data.attrs.preset_openid_url &&
                inputValue !== this.data.attrs.openid_placeholder &&
                RightNow.Text.isValidUrl(inputValue)) {
                // url to an openid service
                urlToGoTo = inputValue;
            }
            if (urlToGoTo) {
                urlToGoTo = encodeURIComponent(urlToGoTo) + "/";
            }
            else {
                return;
            }
        }
        goToUrl.call(this, this.data.attrs.controller_endpoint + urlToGoTo + encodeURIComponent(this._redirectUrl));
    },

    /**
    * Determines if the browser has cookies enabled.
    * First checks if any cookies exist on the domain; failing that check, attempts to set
    * a test cookie; cleans up after itself.
    * @return {Boolean} True if browser cookies are enabled False if cookies are disabled.
    */
    _cookiesEnabled: function() {
        var noCookie = function() {
                return document.cookie === "";
            },
            setCookie = function(value) {
                return (document.cookie = value);
            };
        if (noCookie()) {
            if (setCookie("cp_login_start=1;path=/;secure") && noCookie()) {
                return false;
            }
            setCookie("");
        }
        return true;
    }
});
