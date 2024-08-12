RightNow.Widgets.CommunityPostSubmit = RightNow.Widgets.extend({
    constructor: function(){
        if(this.data.js.newUser){
            return;
        }
        this._titleField = this.Y.one(this.baseSelector + "_Title");
        this._bodyField = this.Y.one(this.baseSelector + "_Body");
        this._submit = this.Y.one(this.baseSelector + '_Submit');

        if(this._titleField && this._bodyField){
            var titleHint = this.data.attrs.label_title_hint,
                bodyHint = this.data.attrs.label_body_hint;
            if(titleHint){
                this._initializeHint(this._titleField, {name: "title", hint: titleHint});
            }
            if(bodyHint){
                this._initializeHint(this._bodyField, {name: "body", hint: bodyHint});
            }
            this._submit.on('click', this._onFormSubmit, this, null);
            if(this.data.attrs.initial_focus){
                this._titleField.focus();
            }
        }
    },

    /**
    * Called when the form is submitted by the user.
    */
    _onFormSubmit: function(){
        if(!this._submitting){
            this._submitting = true;
            this._titleField.set('value', this.Y.Lang.trim(this._titleField.get('value')));
            this._bodyField.set('value', this.Y.Lang.trim(this._bodyField.get('value')));
            if(this._checkErrors(this._titleField, this._bodyField)){
                RightNow.UI.show(this.baseSelector + '_LoadingIcon');
                var eventObject = new RightNow.Event.EventObject(this, {data: {
                    w_id: this.data.info.w_id,
                    token: this.data.js.token,
                    titleID: parseInt(this._titleField.get('name'), 10),
                    titleValue: this._titleField.get('value'),
                    bodyID: parseInt(this._bodyField.get('name'), 10),
                    bodyValue: this._bodyField.get('value'),
                    postTypeID: this.data.attrs.post_type_id,
                    resourceHash: this.data.attrs.resource_id
                }});
                if (RightNow.Event.fire('evt_submitCommunityPostRequest', eventObject)) {
                    RightNow.Ajax.makeRequest(this.data.attrs.submit_post_ajax, eventObject.data, {
                        successHandler: this._onFormSubmitResponse, scope: this, data: eventObject, json: true
                    });
                }
            }
            else{
                this._submitting = false;
            }
        }
    },

    /**
    * Called when a response is received from the server.
    * @param response {Object}
    * @param originalEventObject {Object}
    */
    _onFormSubmitResponse: function(response, originalEventObject){
        if(RightNow.Event.fire('evt_submitCommunityPostResponse', {data: originalEventObject, response: response})){
            this._submitting = false;
            RightNow.UI.hide(this.baseSelector + '_LoadingIcon');
            var checkbox = this.Y.one(this.baseSelector + "_PostToWall"),
                url = this.data.attrs.on_success_url || (window.location + this.data.attrs.add_params_to_url),
                navigateToUrl = function(){
                    RightNow.Url.navigate(url);
                },
                dialogMessage = (typeof response.message === 'string') ? response.message : this.data.attrs.label_confirm_dialog,
                whenFinallyDone = function(){
                    if(dialogMessage){
                        //either create confirmation dialog
                        RightNow.UI.Dialog.messageDialog(dialogMessage, {exitCallback: navigateToUrl, width: '250px'});
                    }
                    else{
                        //or go directly to the next page
                        navigateToUrl();
                    }
                };
            if(response.created){
                if(checkbox && checkbox.get('checked') && FB){
                    FB.ui({
                        method: 'stream.publish',
                        message: originalEventObject.data.titleValue,
                        attachment: {
                            name: originalEventObject.data.titleValue,
                            description: originalEventObject.data.bodyValue
                        }
                      },
                      whenFinallyDone
                    );
               }
               else{
                   whenFinallyDone();
               }
            }
            else if(response.status === -1){
                //Security token error: display message in dialog and refresh the page upon confirmation
                url = RightNow.Url.deleteParameter(window.location.pathname, 'session') + originalEventObject.data.token;
                dialogMessage = RightNow.Interface.getMessage("FORM_OP_TIMED_PLS_RESUBMIT_INFO_MSG");
                whenFinallyDone();
            }
            else if(response.error){
                if(response.errorCode === this.data.js.inputError){
                    //user error
                    this._setErrorMessage("<div><strong>" + dialogMessage + "</strong></div>");
                    return;
                }
                //the API request failed
                RightNow.UI.Dialog.messageDialog(dialogMessage, {icon: "WARN"});
            }
        }
    },

    /**
    * Checks the given fields for requirement errors.
    * @param titleInput HTMLElement Title field
    * @param bodyInput HTMLElment Body field
    * @return T if the form has no errors, F otherwise
    */
    _checkErrors: function(titleInput, bodyInput){
        this._removeErrors();
        var errorLink = "<strong><a href='javascript:void(0);' onclick='document.getElementById(\"%s\").focus(); return false;'>%s</a></strong>",
            errors = "",
            createErrorMessage = function(message, elementID, label){
                label = (message.indexOf("%s") > -1) ? RightNow.Text.sprintf(message, label) : label + message;
                errors += "<div>" + RightNow.Text.sprintf(errorLink, elementID, label) + "</div>";
            };

        if(!titleInput.get('value')){
            createErrorMessage(this.data.attrs.label_required, titleInput.get('id'), this.data.attrs.label_title_field);
        }
        if(!bodyInput.get('value')){
            createErrorMessage(this.data.attrs.label_required, bodyInput.get('id'), this.data.attrs.label_body_field);
        }
        if(errors){
            this._setErrorMessage(errors);
            return false;
        }
        return true;
    },

    /**
    * Outputs the specified error message.
    * @param message String innerHTML message to set
    */
    _setErrorMessage: function(message){
        this._errorMessageDiv = this._errorMessageDiv || this.Y.one('#' + this.data.attrs.error_location);
        if(!this._errorMessageDiv){
            this._errorMessageDiv = this._submit.insertBefore(this.Y.Node.create('<div id="' + this.baseDomID + '_ErrorLocation"></div>'), this._submit);
        }
        this._errorMessageDiv
            .addClass('rn_MessageBox rn_ErrorMessage')
            .removeClass('rn_Hidden')
            .setAttribute('tabIndex', -1)
            .setAttribute('aria-live', 'rude')
            .set('innerHTML', message)
            .focus();
    },

    /**
    * Hides the error div, if it exists, and clears out any existing error message.
    */
    _removeErrors: function(){
        if(this._errorMessageDiv){
            this._errorMessageDiv
                .addClass('rn_Hidden')
                .set('innerHTML', "");
        }
    },

    /**
    * Sets up hints.
    * @param field HTMLElement Field to attach the hint onto
    * @param options Object {
    *       name: String name of hint overlay to create,
    *       hint: String hint message}
    */
    _initializeHint: function(field, options) {
        if (this.data.attrs.always_show_hint) {
            field.insert(this.Y.Node.create("<span class='rn_HintText rn_AlwaysVisibleHint'>" + options.hint + "</span>"), "after");
            return;
        }

        var overlay = new this.Y.Overlay({
            bodyContent: this.Y.Node.create("<span class='rn_HintBox'>" + options.hint + "</span>"),
            visible: false,
            align: {
                node: field,
                points: [this.Y.WidgetPositionAlign.TL, this.Y.WidgetPositionAlign.TR]
            }
        });
        field.on("focus", function(){overlay.show();});
        field.on("blur", function(){overlay.hide();});
        overlay.render();
    }
});
