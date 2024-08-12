RightNow.Widgets.FormSubmit = RightNow.Form.extend({
    overrides: {
        constructor: function() {
            this.parent();

            this.inputDataChanged = false;
            this._formButton = this.Y.one(this.baseSelector + "_Button");
            this._formSubmitFlag = this.Y.one(this.baseSelector + "_Submission");
            this._navigateToUrlFlag = false;

            if(!this._formButton || !this._formSubmitFlag) return;

            //if value's been set, then the form's already been submitted
            if(this._formSubmitFlag.get("checked")) {
                this._formButton.set("disabled", "true");
                return;
            }

            /**
             * 'submitResponse' - Fired if a Field widget wishes to initiate a form submission programmatically
             * 'reset' - Fired if a Field widget wishes to reset the form: removing any error messages and
             *      loading indicators, and making the submit button available for submittal
             */
            this.on("validation:fail", this._onFormValidationFail, this)
                .on("validation:fail", this._resetFormButton, this, false)
                .on("validation:pass", this._onFormValidated, this)
                .on("response", this._defaultFormSubmitResponse, this)
                .on("response", this._resetFormButton, this)
                .on("submitRequest", this._onButtonClick, this)
                .on("reset", this._resetFormForSubmission, this)
                .on("responseError", this._onErrorResponse, this)
                .on("formUpdated", this._onFormUpdated, this);

            this._toggleClickListener(true);

            RightNow.Event.subscribe("evt_formToggleButton", function(type, args) { this._toggleClickListener(args[0]);}, this);
            RightNow.Event.subscribe("evt_formInputDataChanged", function() {
                this.inputDataChanged = true;
            }, this);

            if(this.data.attrs.unsaved_data_dialog) {
                var that = this;
                window.onbeforeunload = function(e) {
                    if (that.inputDataChanged) {
                        return "";
                    }
                };
            }
        }
    },

    /**
     * Handles when user clicks the submit button.
     * @param {Object=} evt Click event or null if called programmatically
     */
    _onButtonClick: function(evt) {
        // cancel unsaved changes dialog box
        this.inputDataChanged = false;

        if (evt && evt.halt) {
            evt.halt();
        }
        if (this._requestInProgress) return false;

        this._toggleClickListener(false);

        this._removeFormErrors();

        //since the form is submitted by script, deliberately tell IE to do auto completion of the form data
        if (this.Y.UA.ie && window.external && "AutoCompleteSaveForm" in window.external) {
            window.external.AutoCompleteSaveForm(document.getElementById(this._parentForm));
        }
        this._fireSubmitRequest();
    },

    _fireSubmitRequest: function() {
        var eo = new RightNow.Event.EventObject(this, {data: {
            form: this._parentForm,
            f_tok: this.data.js.f_tok,
            error_location: this._errorMessageDiv.get("id"),
            timeout: this.data.attrs.timeout * 1000
        }});
        RightNow.Event.fire("evt_formButtonSubmitRequest", eo);
        this.fire("collect", eo);
    },

    /**
     * Event handler for when form has been validated.
     */
    _onFormValidated: function() {
        this._toggleLoadingIndicators(true);
        this.fire("send", this.getValidatedFields());
    },

    /**
     * Event handler for when form fails validation check.
     */
    _onFormValidationFail: function() {
        this._displayErrorMessages(this._errorMessageDiv);
        RightNow.Event.fire("evt_formValidateFailure", new RightNow.Event.EventObject(this));
    },

    /**
     * Clears the informational flash data div if present
     */
    _clearFlashData: function() {
        var infoDiv = this.Y.one('.rn_MessageBox.rn_InfoMessage');
        if (infoDiv) {
            //Skip clearing SmartAssistant message div
            var parentClass = infoDiv.ancestor().getAttribute('class');
            if (parentClass && parentClass.search("rn_SmartAssistantDialog") === -1)
                infoDiv.set('innerHTML', '').removeClass('rn_InfoMessage');
        }
    },

    /**
     *Returns the element's absolute position on the page
     * @param {Object} element Y.node 
     */
    _absoluteOffset: function(element) {
        var top = 0;
        do {
            top += element.get('offsetTop');
            element = element.get('offsetParent');
        } 
        while(element);

        return top;
    },

    /**
     * For the given node,
     * - adds and removes classes
     * - scrolls to it, if it's not in the viewport
     * - focuses on the first <a> child, or on
     *   the element itself (by setting tabindex)
     * @param  {Object} messageArea Y.Node
     */
    _displayErrorMessages: function(messageArea) {
        messageArea.addClass("rn_MessageBox").addClass("rn_ErrorMessage").removeClass("rn_Hidden");
        this._clearFlashData();
        if (!this.Y.DOM.inViewportRegion(this.Y.Node.getDOMNode(messageArea), true)) {
            (new this.Y.Anim({
                node: this.Y.one(document.body),
                to:   { scrollTop: this._absoluteOffset(messageArea) - 40 },
                duration: 0.5
            })).run();
        }
        
        var firstField = messageArea.one("a");
        
        if (firstField) {
            // Focus first link in the error box.
            firstField.focus();
            firstField.setAttribute('role', 'alert');
            // If tabIndex had previously been set via the
            // else case (during a different failure) then remove it now.
            messageArea.removeAttribute('tabIndex');
        }
        else {
            // The error box doesn't have any links, so focus on the box itself.
            // Setting tabIndex to 0 on an element that's not normally tab-focusable gives
            // it normal tab flow in the document.
            messageArea.set('tabIndex', 0);
            messageArea.setAttribute('role', 'alert');
            messageArea.focus();
        }
        var errorLbl = messageArea.all("div").size() > 1 ? RightNow.Interface.getMessage("ERRORS_LBL") : RightNow.Interface.getMessage("ERROR_LBL");
        messageArea.prepend("<h2>" + errorLbl + "</h2>");
        messageArea.one("h2").setAttribute('role', 'alert');
    },

    /**
     * Default handler for form response.
     * @param  {String} type Event name
     * @param  {Array} args EventObjects
     */
    _defaultFormSubmitResponse: function(type, args) {
        if (this.fire('defaultResponseHandler', args[0])) {
            this._formSubmitResponse(type, args);
        }
    },

    /**
     * Event handler for when form submission returns from the server
     * @param {String} type Event name
     * @param {Array} args Event arguments
     */
    _formSubmitResponse: function(type, args) {
        var responseObject = args[0].data,
            result;

        if (!this._handleFormResponseFailure(responseObject) && responseObject.result) {
            result = responseObject.result;

            // Don't process a SmartAssistant response.
            if (!result.sa) {
                if (result.transaction || result.redirectOverride) {
                    return this._handleFormResponseSuccess(result);
                }
                else {
                    // Response object has a result, but not a result we expect.
                    this._displayErrorDialog();
                }
            }
        }

        args[0].data || (args[0].data = {});
        args[0].data.form = this._parentForm;
        RightNow.Event.fire('evt_formButtonSubmitResponse', args[0]);
    },

    /**
     * Deals with a successful sendForm response.
     * @param  {object} result Result from response object
     */
    _handleFormResponseSuccess: function(result) {
        this._formSubmitFlag.set("checked", true);

        if (this.data.attrs.label_on_success_banner) {
            RightNow.UI.displayBanner(this.data.attrs.label_on_success_banner, { focusElement: this._formButton }).on('close', function() {
                this._confirmOnNavigate(result);
            }, this);
            return;
        }
        this._confirmOnNavigate(result);
    },


    /**
     * Deals with errors in the sendForm response.
     * @param  {Object} responseObject Response Object from the server
     * @return {Boolean}                True if there's an error that was dealt with
     *                                  False if no errors were found
     */
    _handleFormResponseFailure: function(responseObject) {
        if (!responseObject) {
            // Didn't get any kind of a response object back; that's... unexpected.
            this._displayErrorDialog(RightNow.Interface.getMessage("THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG"));

            return true;
        }
        if (responseObject.result != null && responseObject.result.newFormToken) {
            RightNow.Event.fire("evt_formTokenUpdate", new RightNow.Event.EventObject(null, {data: {newToken: responseObject.result.newFormToken}}));
        }
        if (responseObject.errors) {
            // Error message(s) on the response object.
            var errorMessage = "";
            this.Y.Array.each(responseObject.errors, function(error) {
                errorMessage += "<div><b>" + error.externalMessage + "</b></div>";
            });
            this._errorMessageDiv.append(errorMessage);
            this._onFormValidationFail();

            return true;
        }
        if (!responseObject.result) {
            // Response object doesn't have a result or errors on it.
            this._displayErrorDialog();

            return true;
        }

        return false;
    },

    /**
     * Given the sendForm result, redirects to the next page.
     * @param  {Object} result Result from response object
     */
    _navigateToUrl: function(result) {
        var url;
        if (result.redirectOverride) {
            url = result.redirectOverride + result.sessionParam;
        }
        else if (this.data.attrs.on_success_url) {
            var paramsToAdd = '';
            this.Y.Object.each(result.transaction, function(details) {
                if (details.key) {
                    paramsToAdd += '/' + details.key + '/' + details.value;
                }
            });

            if (paramsToAdd) {
                url = this.data.attrs.on_success_url + paramsToAdd + result.sessionParam;
            }
            else {
                var sessionValue = result.sessionParam.substr(result.sessionParam.lastIndexOf("/") + 1);
                if(!sessionValue && this.data.js.redirectSession)
                    sessionValue = this.data.js.redirectSession;
                url = RightNow.Url.addParameter(this.data.attrs.on_success_url, 'session', sessionValue);
            }
        }
        else {
            url = window.location + result.sessionParam;
        }

        RightNow.Url.navigate(url + this.data.attrs.add_params_to_url);
    },

    /**
     * Shows a message dialog before redirecting if a success url is defined.
     * @param {Object} result Result from response object
     */
    _confirmOnNavigate : function(result){
        if (this.data.attrs.on_success_url !== 'none') {
            if (this.data.attrs.label_confirm_dialog && this.data.attrs.label_confirm_dialog !== '') {
                // Either create confirmation dialog...
                RightNow.UI.Dialog.messageDialog(this.data.attrs.label_confirm_dialog, {
                    exitCallback: {
                        fn: function() { this._navigateToUrl(result); },
                        scope: this
                    },
                    width: '250px'
                });
            }
            else {
                // ...or go directly to the next page.
                if(this.Y.Lang.trim(this.data.attrs.on_success_url) !== '') {
                   this._navigateToUrlFlag = true;
                }
                this._navigateToUrl(result);
            }
        }
    },

    /**
     * Turns loading indicators off,
     * turns back on the click listener,
     * and clears the error div.
     */
    _resetFormForSubmission: function() {
        this._navigateToUrlFlag = false;
        this._removeFormErrors();
        this._resetFormButton();
    },

    /**
     * Triggered when the form is updated by the dynamic forms API. If the error
     * div is now empty, hide it from the page.
     */
    _onFormUpdated: function() {
        if(this._errorMessageDiv.all('[data-field]').size() === 0) {
            this._errorMessageDiv.addClass("rn_Hidden").set("innerHTML", "");
        }
    },

    /**
     * Handler for the `responseError` form event.
     * If any non-HTTP 200 OK response is received, display a generic error
     * message and reset the form for resubmission.
     * The event object's `data` property contains the full AJAX response object
     * for the request.
     */
    _onErrorResponse: function(response) {
        this._displayErrorDialog(response.suggestedErrorMessage || RightNow.Interface.getMessage("THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG"));
        this._resetFormButton();
    },

    /**
     * Turns loading indicators off and
     * turns back on the click listener.
     */
    _resetFormButton: function() {
        if(this._navigateToUrlFlag) {
            return;
        }
        this._toggleLoadingIndicators(false);
        this._toggleClickListener(true);
    },

    /**
     * Clears out the error div.
     */
    _removeFormErrors: function() {
        this._errorMessageDiv.addClass("rn_Hidden").setHTML("");
    },

    /**
     * Displays a generic error dialog.
     * @param {string=} message Error message to use; if not supplied
     * a generic 'Error - please try again' message is displayed
     */
    _displayErrorDialog: function(message) {
        RightNow.UI.Dialog.messageDialog(message || RightNow.Interface.getMessage('ERROR_PAGE_PLEASE_S_TRY_MSG'), {icon : "WARN"});
    },

    /**
     * Hides / shows the loading icon and status message.
     * @param {Boolean} turnOn Whether to turn on the loading indicators (T),
     * remove the loading indicators (F)
     */
    _toggleLoadingIndicators: function(turnOn) {
        this._formButton.setHTML((turnOn) ? this.data.attrs.label_submitting_message : this.data.attrs.label_button)
                        .toggleClass('rn_Loading', turnOn);
    },

    /**
    * Enables / disables the form submit button and adds / removes its onclick listener.
    * @param {Boolean} To enable or disable the button
    */
    _toggleClickListener: function(enable) {
        if (this.Y.UA.ie) {
            this._formButton.set("disabled", false);
            this.Y.one(this.baseSelector + " button").toggleClass("rn_IeFormButton", !enable);
        }
        else {
            this._formButton.set("disabled", !enable);
        }
        this._requestInProgress = !enable;
        this.Y.Event[((enable) ? "attach" : "detach")]("click", this._onButtonClick, this._formButton, this);
    }
});
