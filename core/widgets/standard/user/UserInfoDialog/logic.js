RightNow.Widgets.UserInfoDialog = RightNow.Widgets.extend({
    constructor: function() {
        this._dialog = null;
        this._keyListener = null;
        this._container = this.Y.one(this.baseSelector);
        this.Y.one(this.baseSelector + "_DisplayName").on("blur", this._toggleErrorClass, this);

        if(this.data.attrs.display_on_page_load) {
            this._launchDialog();
        }
        else {
            RightNow.Event.on('evt_userInfoRequired', this._launchDialog, this);
        }
    },

    /**
     * Launch user info dialog
     */
    _launchDialog: function() {
        if (!this._dialog) {
            this._dialog = RightNow.UI.Dialog.actionDialog(this.data.attrs.label_dialog_title,
                this._container, {
                    buttons: [
                        {text: this.data.attrs.label_submit_button, handler: {fn:this._onSubmit, scope:this}, name: 'submit'},
                        {text: this.data.attrs.label_cancel_button, handler: {fn:this._onCancel, scope:this, href: 'javascript:void(0)'}}
                    ],
                    width: '400px'
                 }
            );
        }

        // Set up keylistener for <enter> to run onSubmit()
        this._keyListener = RightNow.UI.Dialog.addDialogEnterKeyListener(this._dialog, this._onSubmit, this, 'input');
        //override default YUI validation to return false: don't want YUI to try to submit the form
        this._dialog.validate = function() { return false; };
        //make sure the container is visible
        RightNow.UI.show(this._container);

        if(RightNow.Env('module') === 'standard') {
            //Perform dialog close cleanup if the [x] cancel button or esc is used
            //(only standard page set has [x] or uses esc button)
            this._dialog.cancelEvent.subscribe(this._onCancel, null, this);
        }

        RightNow.UI.Dialog.enableDialogControls(this._dialog, this._keyListener);
        this._clearErrorMessage();
        this._dialog.show();
    },

    /**
     * User cancelled. Cleanup and close the dialog.
     */
    _onCancel: function(){
        this._clearErrorMessage();
        RightNow.UI.Dialog.disableDialogControls(this._dialog, this._keyListener);
        this.Y.one(this.baseSelector + "_DisplayName").set("value", "");
        this._toggleErrorClass(false);
        this._dialog.hide();
    },

    /**
     * Clears out the error message divs and their classes.
     */
    _clearErrorMessage: function() {
        this.Y.all(this.baseSelector + "_UserInfoErrorMessage").each(function(errorNode) {
            errorNode.setHTML('').removeClass('rn_MessageBox rn_ErrorMessage');
        }, this);
    },

    /**
     * Event handler for when login form is submitted.
     */
    _onSubmit: function(){
        // pull display name in to send to form handler
        var displayName = this.Y.one(this.baseSelector + "_DisplayName");

        if (displayName) {
            this._toggleLoading(true);

            var eventObject = new RightNow.Event.EventObject(this, {data: {
               displayName: this.Y.Lang.trim(displayName.get('value')) || '',
               url:      window.location.pathname,
               w_id:     this.data.info.w_id
            }});

            if (RightNow.Event.fire("evt_createUserInfoRequest", eventObject)) {
                RightNow.Ajax.makeRequest(this.data.attrs.create_social_user_ajax, eventObject.data, {
                    successHandler: this._onSocialUserInfoResponse,
                    scope: this,
                    data: eventObject,
                    json: true
                }, this);
            }
        }
    },

    /**
     * Add / remove the error class on the DisplayName input field and it's label.
     * @param {Object}|{Boolean} e Event or Boolean true to show and false to hide error style. If 'e' is an event object, apply error class only if display name is empty.
     */
    _toggleErrorClass: function(e) {
        var toggleClass = 'addClass';
        if ((e && e.target && this.Y.one(this.baseSelector + "_DisplayName").get('value') !== '') || (e === false)) {
            toggleClass = 'removeClass';
        }
        this.Y.one(this.baseSelector + "_DisplayName")[toggleClass]("rn_ErrorField");
        this.Y.one(this.baseSelector + "_DisplayName_Label")[toggleClass]("rn_ErrorLabel");
    },

    /**
     * Response handler for create social user ajax call. If everything was successful, we
     * can go ahead with the redirect.
     * @param response {Object} Result from server
     * @param originalEventObject {Object} Original request object sent in request
     */
    _onSocialUserInfoResponse: function(response, originalEventObject) {
        if (response.success) {
            RightNow.Url.navigate(window.location.pathname);
        }
        else if (this._errorDisplay = this.Y.one(this.baseSelector + "_UserInfoErrorMessage")) {
            this._toggleLoading(false);
            this._toggleErrorClass(true);
            this._addErrorMessage(this.Y.Lang.isArray(response.errors) ? response.errors[0] : this.data.attrs.label_incorrect_display_name,
                                  this.baseDomID + "_DisplayName",
                                  true);
        }
    },

    /**
     * Adds an error message to the page and adds the correct CSS classes
     * @param message string The error message to display
     * @param focusElement HTMLElement The HTML element to focus on when the error message link is clicked
     * @param showLink Boolean Denotes if error message should be surrounded in a link tag
     */
    _addErrorMessage: function(message, focusElement, showLink){
        this._errorDisplay || (this._errorDisplay = this.Y.one(this.baseSelector + "_UserInfoErrorMessage"));

        if(this._errorDisplay)
        {
            this._errorDisplay.addClass('rn_MessageBox rn_ErrorMessage');
            //add link to message so that it can receive focus for accessibility reasons
            if(showLink === false)
            {
                this._errorDisplay.set("innerHTML", message);
            }
            else
            {
                this._errorDisplay.set("innerHTML", '<a href="javascript:void(0);" onclick="document.getElementById(\'' + focusElement + '\').focus(); return false;">' + message + '</a>')
                    .get("firstChild").focus();
            }
            this._errorDisplay.one("h2") ? this._errorDisplay.one("h2").setHTML(RightNow.Interface.getMessage("ERRORS_LBL")) : this._errorDisplay.prepend("<h2>" + RightNow.Interface.getMessage("ERROR_LBL") + "</h2>");
            this._errorDisplay.one("h2").setAttribute('role', 'alert');
        }
    },

    /**
     * Toggles the state of loading indicators:
     * Fades the form out/in (for decent browsers)
     * Disables/enables form inputs and dialog buttons
     * Adds/Removes loading indicator class
     * @param {Boolean} turnOn Whether to add or remove the loading indicators.
     */
    _toggleLoading: function(turnOn) {
        this._dialogContent || (this._dialogContent = this.Y.one(this.baseSelector + "_UserInfoContent"));

        this._dialogContent.all('input')[(turnOn) ? 'setAttribute' : 'removeAttribute']('disabled', true);

        if (!this.Y.UA.ie || this.Y.UA.ie > 8) {
            // YUI's animation causes JS execution in IE7-8 to fail in weird ways, like failing to redirect the page
            // when a user's successfully logged in...
            this._dialogContent.transition({
                opacity: turnOn ? 0 : 1,
                duration: 0.4
            });
            this.Y.one(this.baseSelector)[(turnOn) ? 'addClass' : 'removeClass']('rn_Loading');
        }
    }
});
