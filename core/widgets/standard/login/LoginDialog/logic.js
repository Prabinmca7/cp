RightNow.Widgets.LoginDialog = RightNow.Widgets.extend({
    constructor: function() {
        this._dialog = null;
        this._keyListener = null;
        this._currentForm = 'login';
        this._isSocialAction = false;
        this._redirectUrl = '';

        this._container = this.Y.one(this.baseSelector);
        this._formContainer = this.Y.one('.rn_FormContent');
        this._initializeTriggerLink(this.data.attrs.trigger_element);

        var createAccountToggle = this.Y.one(this.baseSelector + '_FormTypeToggle');
        if (createAccountToggle) {
            createAccountToggle.on('click', this._toggleForms, this);
        }

        if (this.data.attrs.open_login_providers) {
            RightNow.Event.on('evt_FederatedProviderSelected', function() {
                this.Y.Lang.later(200, this, this._adjustForOpenLoginExplanationArea);
            }, this);
        }

        this._conformLoginPlaceholders();
        RightNow.Event.subscribe("evt_formTokenUpdate", RightNow.Widgets.onFormTokenUpdate, this);

        if(this.data.js.email_password_message) {
            this._createEmailMessageDialog();
        }
    },

    /**
     * Create Dialog to display message to look for email with reset password link.
     */
    _createEmailMessageDialog: function() {
        var dialog = RightNow.UI.Dialog.messageDialog(this.data.js.email_password_message,
                {
                    title: 'Success',
                    width: '100%',
                    height: '100%'
                });
        dialog.show();
        return;
    },

    /**
     * Initializes the trigger element.
     * @param {String} id DOM element ID
     * @return {Boolean} True if the trigger element was found and events were set
     * up correctly, False if the trigger element wasn't found.
     */
    _initializeTriggerLink: function(id) {
        var loginLink = this.Y.one('#' + id);
        if(loginLink) {
            if(this.data.js.loginLinkOverride) {
                loginLink.set('href', this.data.js.loginLinkOverride);
                RightNow.Event.on('evt_requireLogin', function() {
                    RightNow.Url.navigate(this.data.js.loginLinkOverride);
                }, this);
            }
            else {
                RightNow.Event.on('evt_requireLogin', this._onLoginEventFired, this);
                loginLink.on("click", this._onLoginTriggerClick, this);
            }
        }
        else {
            RightNow.Event.on('evt_requireLogin', this._onLoginEventFired, this);
        }
    },

    /**
     * Toggles the login and create account forms.
     */
    _toggleForms: function () {
        var visibleElement;
        this.Y.Array.each(['.rn_LoginDialogContent', '.rn_SignUpDialogContent'], function(selector) {
            var element = this._container.one(' ' + selector).toggleClass('rn_Hidden');
            if (!element.hasClass('rn_Hidden')) {
                visibleElement = element;
            }
        }, this);

        visibleElement.one('input').focus();
        this._currentForm = visibleElement.get('className').toLowerCase().indexOf('login') > -1 ? 'login' : 'create';

        if (this._dialog) {
            if (this._dialog.centered) {
                this._dialog.centered();
            }
            this._swapDialogLabelsForForm(this._currentForm);
        }
    },

    /**
     * Swaps the labels in the dialog's submit button,
     * link to go to the other form, and heading label
     * for the form.
     * @param  {string} form 'login' or 'create'
     */
    _swapDialogLabelsForForm: function (form) {
        var labels = [
            this.data.attrs.label_login_button,
            this.data.attrs.label_create_account_button
        ], firstButton;

        if (form === 'create') {
            labels.reverse();
        }

        this._dialog.getButton(0).setHTML(labels[0]);
        this._container.one(this.baseSelector + '_FormTypeLabel').setHTML(labels[0]);
        this._container.one(this.baseSelector + '_FormTypeToggle').setHTML(labels[1]);
    },

    /**
     * Event handler for "evt_requireLogin" event.
     * @param  {String} evt  Event name
     * @param  {Array} args Event args with EventObject
     */
    _onLoginEventFired: function(evt, args) {
        if (args[0] && args[0].data) {
            var title = args[0].data.title;
            if (title) {
                this._setDialogTitleText(title);
            }

            // determine if this login has been fired for a social action
            if (args[0].data.isSocialAction) {
                this._urlParamsToAdd = args[0].data.urlParamsToAdd;
            }
        }

        this._onLoginTriggerClick(null, null, true);
    },

    /**
     * Event handler for when login control is clicked.
     * @param {String} event Event name
     * @param {Object} args DOM event
     * @param {Boolean} triggeredFromSocialAction Whether this method was
     *                                            called as a result of a social action
     */
    _onLoginTriggerClick: function(event, args, triggeredFromSocialAction){
        // get a new f_tok value each time the dialog is opened
        RightNow.Event.fire("evt_formTokenRequest",
            new RightNow.Event.EventObject(this, {data:{formToken:this.data.js.f_tok}}));

        this._dialog || (this._dialog = this._createDialog());
        this._clearErrorMessage();
        this._isSocialAction = triggeredFromSocialAction;

        if (this._currentForm !== 'login') {
            this._toggleForms();
        }

        this._dialog.show();
        this._toggleWarningMessageOnSocialAction(false);

        RightNow.UI.Dialog.enableDialogControls(this._dialog, this._keyListener);

        // Focus the first input element, unless it has a value.
        this._container.one(this.baseSelector + '_Form').all('input').some(function(input, index) {
            if (input.get('value') === '' || index > 0) {
                input.focus();

                return true;
            }
        }, this);
    },

    /**
     * Creates the dialog
     * @return {Object} Y.Panel dialog instance
     */
    _createDialog: function() {
        var dialog = RightNow.UI.Dialog.actionDialog(this.data.attrs.label_dialog_title,
                this._container, {
                    buttons: [
                        {text: this.data.attrs.label_login_button, handler: {fn:this._onSubmit, scope:this}, name: 'submit'},
                        {text: this.data.attrs.label_cancel_button, handler: {fn:this._onCancel, scope:this, href: 'javascript:void(0)'}}
                    ],
                    width: '100%',
                    height: '100%',
                    fillHeight: '100%'
                });

        // Set up keylistener for <enter> to run onSubmit()
        this._keyListener = RightNow.UI.Dialog.addDialogEnterKeyListener(dialog, this._onSubmit, this);

        //override default YUI validation to return false: don't want YUI to try to submit the form
        dialog.validate = function() { return false; };

        RightNow.UI.show(this._container);

        if(RightNow.Env('module') === 'standard') {
            //Perform dialog close cleanup if the [x] cancel button or esc is used
            //(only standard page set has [x] or uses esc button)
            dialog.cancelEvent.subscribe(this._onCancel, null, this);
        }

        return dialog;
    },

    /**
     * User cancelled. Cleanup and close the dialog.
     */
    _onCancel: function(){
        this._clearErrorMessage();
        RightNow.UI.Dialog.disableDialogControls(this._dialog, this._keyListener);
        this._toggleWarningMessageOnSocialAction(true);
        this._dialog.hide();
    },

    /**
     * Clears out the error message divs and their classes.
     */
    _clearErrorMessage: function() {
        var errorNode = this.Y.one(this.baseSelector + "_LoginErrorMessage");
        if(errorNode)
            errorNode.setHTML('').removeClass('rn_MessageBox rn_ErrorMessage');
    },

    /**
     * Don't submit the form if the user's using the enter key on certain elements.
     * @param  {String} name  Event name
     * @param  {Array} event Event arguments
     * @return {Boolean}      Whether to ignore the current key press.
     */
    _shouldIgnoreEnterKey: function(name, event) {
        if (name === "keyPressed") {
            var target = event[1].target,
                targetContents = target.getHTML();

            return (target.get('tagName') === 'A' ||
                    targetContents === this.data.attrs.label_login_button ||
                    targetContents === this.data.attrs.label_cancel_button);
        }

        return false;
    },

    /**
     * Processes the inputs in the login form.
     * @param  {Object} form Y.Node for the form
     * @return {Object}      The keys are the field names:
     *                           'Contact.Login': {value: input value, id: input id},
     *                           'Contact.Password': {value: input value, id: input id},
     *                           'error': string localized error message|null if no errors
     */
    _processLoginForm: function(form) {
        var fields = {error: false};

        form.all('input').each(function(input) {
            var name = input.get('name'),
                nameInfo = { value: input.get('value'), id: input.get('id') };

            if(name !== 'Contact.Password')
                nameInfo.value = this.Y.Lang.trim(nameInfo.value);

            fields[name] = nameInfo;

            if (name === 'Contact.Login')
                fields.error = this._validateUsername(fields[name].value);
        }, this);

        return fields;
    },

    /**
     * Validates the given string against proper username rules.
     * @param  {String} username Username input value
     * @return {Null|String} Error message if validation fails
     */
    _validateUsername: function(username) {
        if(username.indexOf(' ') > -1)
            return RightNow.Text.sprintf(RightNow.Interface.getMessage("PCT_S_MUST_NOT_CONTAIN_SPACES_MSG"), RightNow.Interface.getMessage("USERNAME_LBL"));
        if(username.indexOf('"') > -1)
            return RightNow.Text.sprintf(RightNow.Interface.getMessage("PCT_S_CONTAIN_DOUBLE_QUOTES_MSG"), RightNow.Interface.getMessage("USERNAME_LBL"));
        if(username.indexOf("<") > -1 || username.indexOf(">") > -1)
            return RightNow.Text.sprintf(RightNow.Interface.getMessage("PCT_S_CNT_THAN_MSG"), RightNow.Interface.getMessage("USERNAME_LBL"));
    },

    /**
     * Event handler for when login form is submitted.
     * @param {String} event Event name
     * @param {Object} args DOM event
     */
    _onSubmit: function(event, args){
        if (this._shouldIgnoreEnterKey(event, args)) return;

        if (this._currentForm === 'social') {
            this._submitSocialUserInfo();
        }
        else if (this._currentForm === 'create') {
            this._submitCreateAccountForm();
        }
        else {
            this._submitLoginForm();
        }
    },

    /**
     * Submits the create account form.
     */
    _submitCreateAccountForm: function() {
        this.Y.Node.getDOMNode(this._container.one('.rn_SignUpDialogContent form button[type="submit"]')).click();
    },

    /**
     * Submits the login form - validates, constructs event object
     * and sends it along to be sent to the server, if there's no errors.
     */
    _submitLoginForm: function() {
        var fields = this._processLoginForm(this.Y.one(this.baseSelector + '_Form')),
            login = fields['Contact.Login'].value || '',
            password = (!this.data.attrs.disable_password && fields['Contact.Password']) ? fields['Contact.Password'].value : '';

        if (fields.error) {
            return this._addLoginErrorMessage(fields.error, fields['Contact.Login'].id);
        }

        var eventObject = new RightNow.Event.EventObject(this, {data: {
           login:    login,
           password: password,
           url:      window.location.pathname,
           w_id:     this.data.info.w_id,
           f_tok:    this.data.js.f_tok
        }});

        if (RightNow.Event.fire("evt_loginFormSubmitRequest", eventObject)) {
            this._sendLoginForm(eventObject);
        }
    },

    /**
     * Disables the form and sends along the event object to the server.
     * @param  {Object} eventObject Event Object
     */
    _sendLoginForm: function(eventObject) {
        this._toggleLoading(true);

        // disable login and cancel buttons
        RightNow.UI.Dialog.disableDialogControls(this._dialog, this._keyListener);

        //Attempt to set a test login cookie
        !RightNow.Event.noSessionCookies() || RightNow.Event.setTestLoginCookie();

        RightNow.Ajax.makeRequest(this.data.attrs.login_ajax, eventObject.data, {
            successHandler: this._onResponseReceived,
            scope: this,
            data: eventObject,
            json: true
        });

        //since this form is submitted by script, force ie to do auto-complete
        if (this.Y.UA.ie > 0 && window.external && "AutoCompleteSaveForm" in window.external) {
            window.external.AutoCompleteSaveForm(document.getElementById(this.baseDomID + "_Form"));
        }
    },

    /**
     * Event handler for when login has returned. Handles either successful login or failed login
     * @param response {Object} Result from server
     * @param originalEventObject {Object} Original request object sent in request
     */
    _onResponseReceived: function(response, originalEventObject){
        if(!RightNow.Event.fire("evt_loginFormSubmitResponse", {data: originalEventObject, response: response})){
            return;
        }

        this._toggleLoading(false);
        if(response.success == 1)
        {
            this._redirectUrl = this._getRedirectUrl(response);
            //Perform dialog close cleanup if the [x] cancel button or esc is used
            this._dialog.cancelEvent.subscribe(function() {
                RightNow.Url.navigate(this._redirectUrl);
            }, null, this);

            if (this._isSocialAction && this._formContainer) {
                this._toggleLoading(true);
                this._formContainer.setHTML('');

                var eventObject = new RightNow.Event.EventObject(this, {data: {
                   url:      window.location.pathname,
                   w_id:     this.data.info.w_id
                }});

                if (RightNow.Event.fire("evt_hasSocialUserRequest", eventObject)) {
                    var _self = this;
                    // refresh token and fire ajax request to determine if the contact has a social user.
                    RightNow.Ajax.makeRequest("/ci/ajaxRequest/getNewFormToken", {"formToken": eventObject.data.rn_formToken, "tokenIdentifier": eventObject.data.w_id}, {
                        successHandler: function(response){
                            eventObject.data.rn_formToken = response.newToken;
                            RightNow.Ajax.makeRequest(_self.data.attrs.has_social_user_ajax, eventObject.data, {
                                successHandler: _self._onHasSocialUserResponse,
                                scope: _self,
                                data: eventObject,
                                json: true
                            });
                        },
                        scope: this,
                        data: eventObject,
                        json: true
                    });
                }
            }
            // standard login
            else {
                this._container && this._container.set("innerHTML", response.message);
                RightNow.Url.navigate(this._redirectUrl);
            }
        }
        else
        {
            // enable buttons to allow the form to be re-submitted
            RightNow.UI.Dialog.enableDialogControls(this._dialog, this._keyListener);
            this._addLoginErrorMessage(response.message, this._container.one('.rn_LoginDialogContent input').get('id'), response.showLink);
            if (response != null && response.newFormToken) {
                RightNow.Event.fire("evt_formTokenUpdate", new RightNow.Event.EventObject(this, {data: {newToken: response.newFormToken}}));
            }
        }
    },

    /**
     * Calculates the URL to redirect the user to after a login
     * @param result Object The result information returned from the server
     * @return String The URL to redirect to
     */
    _getRedirectUrl: function(result){
        var redirectUrl;
        result.sessionParm = RightNow.Text.getSubstringAfter(result.sessionParm, 'session/');
        if(this.data.js && this.data.js.redirectOverride){
            redirectUrl = RightNow.Url.addParameter(this.data.js.redirectOverride, 'session', result.sessionParm);
        }
        else{
            redirectUrl = this.data.attrs.redirect_url || result.url;
            if(result.addSession)
                redirectUrl = RightNow.Url.addParameter(redirectUrl, 'session', result.sessionParm);
        }
        redirectUrl += this.data.attrs.append_to_url;

        for (var param in this._urlParamsToAdd) {
            redirectUrl = RightNow.Url.addParameter(redirectUrl, param, this._urlParamsToAdd[param]);
        }

        if (result.forceRedirect) {
            redirectUrl = RightNow.Url.addParameter(result.forceRedirect, 'redirect', encodeURIComponent(redirectUrl));
        }

        return redirectUrl;
    },

    /**
     * Response handler for hasSocialUser ajax call. Determines if we need to show
     * the additional social user info form
     * @param response {Object} Result from server
     * @param originalEventObject {Object} Original request object sent in request
     */
    _onHasSocialUserResponse: function(response, originalEventObject) {
        this._isSocialAction = false;
        if(!RightNow.Event.fire("evt_loginFormHasSocialUserResponse", {data: originalEventObject, response: response})){
            return;
        }

        // if the user does not have a social user profile yet
        if (response.socialUser === "" && this._formContainer) {
            this._toggleLoading(false);
            this._currentForm = "social";
            this._createSocialUserForm(this.data.attrs.label_social_user_info_desc);
            this._removeOpenLogin();
        }
        else {
            RightNow.Url.navigate(this._redirectUrl);
        }
    },

    /**
     * Remove the OpenLogin portion of LoginDialog
     */
    _removeOpenLogin: function() {
        var openLoginContainer = this.Y.one(this.baseSelector + ' .rn_OpenLoginAlternative');

        if(this._formContainer && openLoginContainer) {
            openLoginContainer.remove();
            this._formContainer.setStyle('width', '100%');
        }
    },

    /**
     * Create a short form for entering the user's display name
     * @param description {string} Description message that will be displayed at the top of the form to explain why extra info is needed.
     */
    _createSocialUserForm: function(description) {
        this.Y.augment(this, RightNow.RequiredLabel);
        var templateData = {domPrefix: this.baseDomID,
                labelDisplayName: RightNow.Interface.getMessage("DISPLAY_NAME_LBL"),
                socialUserInfoDesc: description
            },
            socialUserInfoForm = this.Y.Node.create(new EJS({text: this.getStatic().templates.socialUserForm}).render(templateData)),
            dialogButtons = this._dialog.getButtons();

        this._formContainer.setHTML(socialUserInfoForm);
        this.Y.one(this.baseSelector + '_DisplayName').focus();
        this.Y.one(this.baseSelector + '_DisplayName').on("blur", this._toggleErrorClass, this);
        // The dialogButtons object is a NodeList in standard and an array in mobile.
        // Also, the removeButton and addButton functions only exist for standard dialogs, but not mobile ones.
        if (dialogButtons.item) {
            this._dialog.removeButton(dialogButtons.item(0)).removeButton(dialogButtons.item(1));
            this._dialog.addButton({label: this.data.attrs.label_social_user_finish_button,
                action: this._submitSocialUserInfo,
                context: this
            });
        }
        else {
            dialogButtons[0].ancestor().remove();
            dialogButtons[1].ancestor().remove();
            dialogButtons.splice(0, 2);
            var button = this.Y.Node.create('<div class="rn_MobileDialogButton"><button class="rn_Button">' + this.data.attrs.label_social_user_finish_button + '</button></div>');
            button.on("click", this._submitSocialUserInfo, this);
            this.Y.one('#' + this._dialog.id).one('.rn_PanelContent').append(button);
            dialogButtons.push(button);
        }
        this._keyListener = RightNow.UI.Dialog.addDialogEnterKeyListener(this._dialog, this._onSubmit, this);
    },

    /**
     * Submits social user info via an ajax request
     */
    _submitSocialUserInfo: function() {
        // pull display name in to send to form handler
        var displayName = this.Y.one(this.baseSelector + "_DisplayName");

        if (displayName) {
            this._dialogContent = this.Y.one(".rn_SocialUserInfoDialogContent");
            this._toggleLoading(true);

            var eventObject = new RightNow.Event.EventObject(this, {data: {
               displayName: this.Y.Lang.trim(displayName.get('value')) || '',
               url:      window.location.pathname,
               w_id:     this.data.info.w_id
            }});

            if (RightNow.Event.fire("evt_createSocialUserRequest", eventObject)) {
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
     * Response handler for create social user ajax call. If everything was successful, we
     * can go ahead with the redirect.
     * @param response {Object} Result from server
     * @param originalEventObject {Object} Original request object sent in request
     */
    _onSocialUserInfoResponse: function(response, originalEventObject) {
        if (response.success) {
            RightNow.Url.navigate(this._redirectUrl);
        }
        else if (this._errorDisplay = this.Y.one(this.baseSelector + "_SocialUserInfoErrorMessage")) {
            this._toggleLoading(false);
            this._addLoginErrorMessage(this.data.attrs.label_incorrect_display_name,
                this.baseDomID + "_DisplayName",
                true
            );
        }
    },

    /**
     * Adds an error message to the page and adds the correct CSS classes
     * @param message string The error message to display
     * @param focusElement HTMLElement The HTML element to focus on when the error message link is clicked
     * @param showLink Boolean Denotes if error message should be surrounded in a link tag
     */
    _addLoginErrorMessage: function(message, focusElement, showLink){
        this._errorDisplay || (this._errorDisplay = this.Y.one(this.baseSelector + "_LoginErrorMessage"));

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
        this._dialogContent || (this._dialogContent = this.Y.one(this.baseSelector + "_LoginContent"));

        this._dialogContent.all('input')[(turnOn) ? 'setAttribute' : 'removeAttribute']('disabled', true);

        this._container[(turnOn) ? 'addClass' : 'removeClass']('rn_ContentLoading');
    },

    /**
     * Sets the dialog's title text.
     * @param {String} text Title text to set
     */
    _setDialogTitleText: function(text) {
        if (this._dialog) {
            this._dialog.setHeader(text);
        }
        else {
            this.data.attrs.label_dialog_title = text;
        }
    },

    /**
     * Removes input placeholders and shows labels if the form's not supposed to have any
     * or the browser isn't capable of showing placeholders.
     */
    _conformLoginPlaceholders: function() {
        if (!this.data.attrs.login_field_placeholders || !('placeholder' in document.createElement('input'))) {
            var form = this._container.one(this.baseSelector + '_LoginContent');
            form.all('label').removeClass('rn_ScreenReaderOnly');
            form.all('input').removeAttribute('placeholder');
        }
    },

    /**
     * Due to the way OpenLogin's explanation area is positioned, the
     * widget's container needs to be `overflow:hidden`. But the absolute
     * positioned element can't be hidden. This sets the dialog's min-height
     * to ensure that nothing gets cut off.
     */
    _adjustForOpenLoginExplanationArea: function() {
        var selected;

        this._container.all('.rn_ActionArea').some(function(el) {
            if (el.getComputedStyle('display') !== 'none') {
                selected = el;
                return true;
            }
        });

        if (selected) {
            this._container.setStyle('minHeight', parseInt(selected.getComputedStyle('height'), 10) + selected.get('offsetTop') + 50 + 'px');
        }
    },

    /**
     * Toggles the warning message that displays on social interactions
     * @param {Boolean} shouldHide Whether the message should be hidden
     */
    _toggleWarningMessageOnSocialAction: function(shouldHide) {
        if(!(this._isSocialAction && this.data.attrs.show_social_warning))
            return;

        var warningMessage = this._container.one('.rn_WarningMessage');
        shouldHide ? RightNow.UI.hide(warningMessage) : RightNow.UI.show(warningMessage);
    },

    /**
     * Add / remove the error class on the DisplayName input field and it's label.
     */
    _toggleErrorClass: function() {
        var toggleClass = 'addClass';
        if (this.Y.one(this.baseSelector + "_DisplayName").get('value') !== '') {
            toggleClass = 'removeClass';
        }
        this.Y.one(this.baseSelector + "_DisplayName")[toggleClass]("rn_ErrorField");
        this.Y.one(this.baseSelector + "_SocialUserInfoForm").one('label')[toggleClass]("rn_ErrorLabel");
    }
});
