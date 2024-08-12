if (RightNow.UI && RightNow.UI.AbuseDetection) { throw new Error("The RightNow.UI.AbuseDetection namespace variable has already been defined somewhere."); }
YUI().use('node-base', 'node-core', function(Y) {
RightNow.UI = RightNow.UI || {};
/**
 * This namespace contains functions and variables which relate to detecting and preventing
 * abusive behavior, such as displaying CAPTCHAs.
 *
 * @namespace
 */
RightNow.UI.AbuseDetection = (function(){
    /**
     * @private
     * @constant
     **/
    var _captchaRequiredText = "==CHALLENGE REQUIRED==";
    /**
     * @private
     * @constant
     **/
    var _captchaIncorrectText = "==CHALLENGE INCORRECT==";
    /**
     * @type boolean
     * @private
     */
    var _isRetry = false;

    return {
        /**
         * Contains options which will be passed by the default challenge handler to the underlying challenge provider.  See http://recaptcha.net/apidocs/captcha/client.html for examples that will work with the reCAPTCHA.net challenge provider.
         * @type Object
         */
        options: {},

        /** Indicates if the server says that the user submitted an incorrect CAPTCHA in the previous request.
         * @return {boolean} whether the previous request contained an incorrect CAPTCHA.
         */
        isRetry: function() {
            return _isRetry;
        },

        /**
         * Returns an object containing methods which can create and interrogate an abuse challenge.
         * @param {Object} abuseResponse An object containing a 'challengeProvider' member containing script which will evaluate to a challenge provider.
         * @return {*} An object implementing the challenge provider interface.
         */
        getChallengeProvider: function(abuseResponse) {
            if (!abuseResponse || !abuseResponse.challengeProvider) {
                throw "The server did not return a challenge provider.";
            }
            try {
                return eval(abuseResponse.challengeProvider);
            }
            catch (ex) {
                throw "There was an error evaluating the challenge provider.  " + ex;
            }
        },

        /**
         * Returns the dialog caption returned by the server.
         * @param {Object} abuseResponse An object containing a 'dialogCaption' member.
         * @return {string} The dialog caption returned by the server.
         */
        getDialogCaption: function(abuseResponse) {
            return (abuseResponse) ? abuseResponse.dialogCaption : "";
        },

        /**
         * Examines the YUI Connect response object to determine if the server said that there was abuse.
         * @param {Object} responseObject YUI Connect response object
         * @return {boolean} Indicating abuse.
         */
        doesResponseIndicateAbuse: function(responseObject) {
            if (!responseObject || !responseObject.responseText) {
                return false;
            }
            if (_captchaRequiredText === responseObject.responseText.slice(0, _captchaRequiredText.length)) {
                _isRetry = false;
                return true;
            }
            if (_captchaIncorrectText === responseObject.responseText.slice(0, _captchaIncorrectText.length)) {
                _isRetry = true;
                return true;
            }
            return false;
        },

        /**
         * Default handler
         * @param {Object} requestResubmitHandler
         */
        Default: function(requestResubmitHandler) {
            this._requestResubmitHandler = requestResubmitHandler || RightNow.Event.createDelegate(this, this._requestResubmitHandler);
        }
    };
}());

RightNow.UI.AbuseDetection.Default.prototype = {
    /**
     * This is just a shortcut for when I need to refer to the parent namespace.
     * @type Object
     * @private
     */
    _abuse: RightNow.UI.AbuseDetection,

    /**
     * @constant
     * @private
     * */
    _abuseChallengeDivID: "rn_DefaultAbuseChallengeDiv",

    /**
     * The dialog the CAPTCHA will be shown in by default
     * @type Object
     * @private
     */
    _dialog: null,

    /**
     * The instance of the challenge provider the service passed the client code for.
     * @type Object
     * @private
     */
    _challengeProvider: null,

    /**
     * @type Object
     * @private
     */
    _requestObject: null,

    /**
     * @private
     */
    _requestResubmitHandler: function() {
        var postData = Y.merge(this._requestObject.post || {}, this._challengeProvider.getInputs(this._abuseChallengeDivID));
        RightNow.Ajax.makeRequest(this._requestObject.url, postData, this._requestObject);
    },

    /**
     * @private
     */
    _dialogSubmitHandler: function() {
        this._dialog.hide();
        this._requestResubmitHandler();
        this._challengeProvider.destroy();
        this._clearDefaultDialog();
    },

    /**
     * @private
     * @param {Object} abuseResponse Response returned by server
     */
    _createDefaultDialog: function(abuseResponse) {
        var dialogTitle = this._abuse.getDialogCaption(abuseResponse),
            abuseChallengeDiv = Y.one('#' + this._abuseChallengeDivID),
            dialogOptions = {
                "buttons": [{
                    text: RightNow.Interface.getMessage("OK_LBL"),
                    isDefault: true,
                    handler: { scope: this, fn: this._dialogSubmitHandler }
                }],
                "close": false,
                "width": '400px',
                "cssClass": 'rn_CaptchaDialog'
            };

        // reCaptcha2 needs an empty element to render the captcha challenge
        abuseChallengeDiv = Y.Node.create("<div id='" + this._abuseChallengeDivID + "'></div>");
        this._dialog = RightNow.UI.Dialog.actionDialog(dialogTitle, abuseChallengeDiv, dialogOptions);
        // Disable ESC key because there is no cancel button on the Captcha dialog and control will remain to boundingBox
        if (typeof this._dialog.set !== 'undefined') {
            this._dialog.set('hideOn', [{eventName: 'key', keyCode: 'esc'}]);
        }
        if(!this._validationLink) {
            this._validationLink = Y.Node.create('<a href="javascript:void(0)">' + this._abuse.getDialogCaption(abuseResponse) + '</a>');
            this._validationLink.on("click", function() {
                this._challengeProvider.focus(this._abuseChallengeDivID);
            }, this);
            this._errorMessageDiv = Y.Node.create('<div>');
            this._errorMessageDiv.appendChild(this._validationLink);
            abuseChallengeDiv.insert(this._errorMessageDiv, "before");
        }
        this._validationLink.addClass("rn_Hidden");
        RightNow.UI.Dialog.addDialogEnterKeyListener(this._dialog, function(type, args) {
            if (type !== "keyPressed" || args[1].target.tagName !== 'INPUT' || args[1].target.type !== "text") {
                return;
            }
            // Without this, IE gets weird and calls the submit handler twice.
            args[1].halt();
            this._dialogSubmitHandler();
        }, this);
    },

    /**
     * Returns the challenge handler
     * @return {Object}
     */
    getChallengeHandler: function() {
        return RightNow.Event.createDelegate(this, this._challengeHandler);
    },

    /**
     * @private
     * @param {Object} abuseResponse
     * @param {Object} requestObject
     * @param {Boolean} isRetry
     */
    _challengeHandler: function(abuseResponse, requestObject, isRetry) {
        this._challengeProvider = this._abuse.getChallengeProvider(abuseResponse);
        this._requestObject = requestObject;

        if (!this._dialog) {
            this._createDefaultDialog(abuseResponse);
        }
        else {
            this._dialog.setHeader(this._abuse.getDialogCaption(abuseResponse));
            this._clearDefaultDialog();
        }

        if(this._abuse.isRetry() && this._validationLink)
        {
            this._errorMessageDiv.addClass("rn_ErrorMessage rn_MessageBox");
            this._validationLink.removeClass("rn_Hidden");
        }

        this._challengeProvider.create(this._abuseChallengeDivID, this._abuse.options, RightNow.Event.createDelegate(this, function() {
            var ariaBusy = "true";
            if(this._abuse.isRetry())
            {
                Y.Lang.later(100, this, function() {
                    if (this._validationLink && this._validationLink.focus) {
                        this._validationLink.focus();
                    }
                });
                ariaBusy = "false";
            }
            else
            {
                this._dialog.showEvent.subscribe(this._challengeProvider.focus);
            }
            if(this._dialog.body)
                Y.one(this._dialog.body).setAttribute("aria-busy", ariaBusy);
            else if(this._dialog._content)
                Y.one(this._dialog._content).setAttribute("aria-busy", ariaBusy);
            this._dialog.show();

            // Disable the dialog's OK button until a user returns a valid captcha check.
            this._dialog.disableButtons();
            }),
        this._dialog);
    },

    /**
     * @private
     */
    _clearDefaultDialog: function() {
        var div = document.getElementById(this._abuseChallengeDivID);
        if (div) {
            div.innerHTML = "<div style='height: 129px'><img src='" + RightNow.Env('coreAssets') + "images/indicator.gif'></div>";
        }

        if(this._dialog) {
            var dialogID = this._dialog.get('contentBox').get('id') + '_c';
            var dialog = document.getElementById(dialogID);
            // reCaptchav2 will not render in the element in which it was already rendered. So delete it when the dialog is closed.
            dialog.parentNode.removeChild(dialog);
        }
    }
};
});
