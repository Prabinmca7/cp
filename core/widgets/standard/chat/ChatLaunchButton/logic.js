RightNow.Widgets.ChatLaunchButton = RightNow.Widgets.FormSubmit.extend({
    overrides: {
        constructor: function(data, instanceID, Y){
            this.parent();
            this._isProactiveChat = RightNow.Url.getParameter('pac') !== null;
            if(!this._parentForm || !this._formButton) return;
            // Dynamically create form element to pass referring url data to the chat landing page
            this._addHiddenPostField('referrerUrl', this._getReferrerUrl());

            if(!this.data.js.showForm)
            {
                if(!this.data.js.isBrowserSupported) {
                    Y.one('#' + this._parentForm)
                        .get('parentNode')
                        .insert(RightNow.Interface.getMessage("CHAT_SUPP_BROWSER_CHAT_AVAIL_MSG"), 'before');
                }
                RightNow.UI.hide(this._parentForm);
                return;
            }

            if(RightNow.Profile.isLoggedIn() && this._isProactiveChat)
            {
                var eo = new RightNow.Event.EventObject(this, {data: {"form" : this._parentForm, "error_location" : this._errorMessageDiv.get("id"), "f_tok" : this.data.js.f_tok}});
                this.fire("collect", eo);
            }
        },

        /**
         * get the ReferrerUrl
         */
        _getReferrerUrl: function()
        {
            var referrerUrl;
            var chatData = RightNow.Text.Encoding.base64Decode(RightNow.Url.getParameter("chat_data"));
            var dataValues = chatData.split('&');
            for(var index = 0; index < dataValues.length; index++)
            {
                var value = dataValues[index].split('=');
                if(value[0] === "referrerUrl")
                {
                    referrerUrl = decodeURIComponent(value[1]);
                    break;
                }
            }
            if (!referrerUrl)
            {
                referrerUrl = document.referrer;
                // Try document.referrer first. If that's undefined, try window.opener.location (pop-up). If that fails, just leave undefined.
                if (!referrerUrl && window.opener && window.opener.location)
                    referrerUrl = window.opener.location.href;
            }
            return referrerUrl;
        },

        /**
         * Opens the chat window
         */
        _openChatWindow: function(){
            var parentForm = this.Y.one("#" + this._parentForm);
            if(!this._isProactiveChat)
            {
                var leftPos = (screen.width / 2) - (this.data.attrs.launch_width / 2);
                var topPos = (screen.height / 2) - (this.data.attrs.launch_height / 2);

                var url = '/euf/core/static/blank.html';
                if(this.Y.UA.ie === 6)
                    url = '';

                var chatWindowName = this.data.js.chatWindowName || 'chatWindow';
                try
                {
                    if(this.data.attrs.open_in_new_window)
                    {
                        if(!(this.Y.UA.chrome && this.Y.UA.ios)){
                            this.chatWindow = window.open(url, chatWindowName, 'status=1,toolbar=0,menubar=0,location=0,resizable=1' + (this.data.attrs.enable_scrollbars ? ',scrollbars=1' : '') +
                                ',height=' + this.data.attrs.launch_height + 'px,width=' + this.data.attrs.launch_width + 'px,left=' + leftPos + ',top=' + topPos);
                            this.chatWindow.focus();
                            parentForm.set('target', chatWindowName);
                        }
                        else
                        {
                            parentForm.set('target', '_blank');
                        }
                    }
                }
                catch(e)
                {
                    this._toggleLoadingIndicators(false);
                    return;
                }
            }
            else
            {
                // Let's try to resize the window to ideal chat size. If blocked, continue anyway, ignoring exception.
                try
                {
                    resizeTo(this.data.attrs.launch_height, this.data.attrs.launch_height + 47);
                }
                catch(e) {}
            }

            try
            {
                //finally send the form
                this.fire("send", this.getValidatedFields());
            }
            catch(e) {}
        },

        /**
         * Event handler for when form has been validated
         */
        _onFormValidated: function()
        {
            if(this.data.attrs.is_persistent_chat)
            {
                RightNow.Event.fire("evt_startPersistentChatRequest", new RightNow.Event.EventObject(this, {}));
                var UI = RightNow.Chat.UI;
                UI.EventBus = new UI.EventBus();
                UI.EventBus.initializeEventBus();
            }
            var challengeName = (this.data.attrs.hasOwnProperty('challenge_location')) ? this.data.attrs.challenge_location : "";
            RightNow.Ajax.makeRequest("/ci/ajaxRequest/formChallengeCheck", {formToken: this.data.js.f_tok, challengeName: challengeName}, {successHandler:this._chatVerifySuccess, scope: this, json: true});
        },


        /**
         * Handler for checking challenge on chat launch page
         * @param {Object} data response from the server
         */
        _chatVerifySuccess: function(data)
        {
            if(data.status == false)
            {
                this._showErrorMessage(data.error);
                this._resetFormButton();
                this._onFormValidationFail();
            }
            else
            {
                this._openChatWindow();
                if(RightNow.Profile.isLoggedIn())
                {
                    RightNow.Ajax.makeRequest("/ci/ajaxRequest/validateChatForm", {formToken: this.data.js.f_tok}, {successHandler:this._onLoggedInVerification, scope: this, json: true});
                }
                else
                {
                    this._onLoggedInVerification({status: true});
                }
            }
        },
        /**
         * Displays error message
         * @param {String} errorMessage Error Message to display
         */
        _showErrorMessage: function(errorMessage)
        {
            var error_span;
            if(this._errorLocation)
            {
               error_span = document.getElementById(this._errorLocation);
            }
            else
            {
               if(this.data.attrs.is_persistent_chat)
               {
                    error_span = document.getElementById("rn_PCErrorLocation");
               }
               else
               {
                   error_span = document.getElementById("rn_ErrorLocation");
               }
            }
            error_span.style.display = 'block';
            var error_message = document.createElement("span");
            error_span.className = "rn_MessageBox rn_ErrorMessage";
            error_message.className = "ErrorSpan";
            error_message.innerHTML = errorMessage;
            error_span.appendChild(error_message);
        },

        /**
         * Function to be called after logged in condition check
         * @param {Object} serverResponse Response from the server
         */
        _onLoggedInVerification:function(serverResponse)
        {
            if(serverResponse.status === false)
            {
                this._showErrorMessage(serverResponse.error);
                this.chatWindow.close();
                this._eventCanceled = true;
                return;
            }
            this._toggleLoadingIndicators(false);
        },

        /**
         * Adds a hidden input field to the page with a value. Will be submitted along with the form.
         * @param {String} name Name of the field to add
         * @param {String} value Value to set on the input field
         */
        _addHiddenPostField: function(name, value)
        {
            if(name !== undefined && value !== undefined)
            {
                // Add the input element to the DOM
                var parentForm = this.Y.one("#" + this._parentForm);
                var input = this.Y.Node.create('<input name="' + name + '" type="hidden" value="' + this.Y.Escape.html(value) + '">');
                parentForm.appendChild(input);

                // Simulate the events necessary for validating form events. Usually done in FormInput widget logic.js.
                RightNow.Form.find(this._parentForm, this.instanceID).on("submit", function() {
                    var eventObject = new RightNow.Event.EventObject(this, {data: {
                        name: name,
                        value: value,
                        required: false,
                        form: this._parentForm
                    }});

                    RightNow.Event.fire("evt_formFieldValidatePass", eventObject);

                    return eventObject;
                }, this);
            }
        }
    }
});
