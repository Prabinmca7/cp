RightNow.Widgets.ChatAttachFileButton = RightNow.Widgets.FileAttachmentUpload.extend({
    overrides: {
        constructor: function() {
            this.parent();
            this._container = this.Y.one(this.baseSelector);
            this._dialog = null;
            this._keyListener = null;
            this._positionDuringUpload = this.data.attrs.position_during_upload;
            this._centeredDuringUpload = true;
            this._transactionID = null;
            this._form = this.Y.one(this.baseSelector + '_Form');
            this._eo = new RightNow.Event.EventObject(this);

            if(this._positionDuringUpload !== 'center')
            {
                var positionValues = this._positionDuringUpload.split('-');
                this._centeredDuringUpload = false;
                this._xPositionDuringUpload = positionValues[1] === 'left' ? 0 : 9999;
                this._yPositionDuringUpload = positionValues[0] === 'top' ? 0 : 9999;
            }

            this._attachButton = this.Y.one(this.baseSelector + '_Button');

            // Event subscription and listener section.
            if(this._attachButton)
            {
                this._attachButton.on("click", this._onButtonClick, this);
                RightNow.Event.subscribe("evt_fileUploadRequest", this._uploadFile, this);
                RightNow.Event.subscribe("evt_chatNotifyFattachUpdateResponse", this._fileNotifyResponse, this);
                RightNow.Event.subscribe("evt_chatStateChangeResponse", this._onChatStateChangeResponse, this);
                RightNow.Event.subscribe("evt_chatEngagementParticipantAddedResponse", this._onChatEngagementParticipantAddedResponse, this);
            }
        },
        _sendUploadRequest: function() {
            RightNow.Event.fire("evt_fileUploadRequest", this._eo);
        }
    },

    /**
     * Handles when user clicks attach button. Shows file attach dialog.
     * @param type string Event name
     * @param args object Event arguments
     */
    _onButtonClick: function(type, args)
    {
        // Reset transactionID
        this._transactionID = null;

        if(!this._dialog)
        {
            var dialogTitle = this.data.attrs.label_dialog_title;
            var buttons = [{text: this.data.attrs.label_cancel_button, handler: {fn:this._onCancel, scope:this}, isDefault: false}];

            // Surround the form in a "content" div for the panel. This is dirty, but required if we're going to extend
            // off of the FileAttachUpload widget. Necessary because the tag library (as of 12.8) breaks with unterminated div's in the view.
            // If that's ever fixed, this should go away.
            this._form.set('innerHTML', '<div id="rn_' + this.instanceID + '_FileAttachContent" class="rn_ChatFileAttachContent rn_Hidden">' + this._form.get('innerHTML') + '</div>');

            this._dialog = RightNow.UI.Dialog.actionDialog(dialogTitle, this._form, {"buttons": buttons, "cssClass": "rn_ChatAttachFileDialog"});
            this._dialog.cfg.setProperty('modal', false);
            this._dialog.cancelEvent.subscribe(this._onCancel, null, this); // Perform our dialog close cleanup when they use the X button as well
            this._dialog.validate = function() { return false; }; // Override default YUI validation to return false: don't want YUI to try to submit the form

            this.input = this.Y.one(this.baseSelector + "_FileInput");
            this.input.on("change", this._onFileAdded, this);
            this.input.on("keypress", this._onKeyPress, this);
            this.input.on("paste", function(){return false;});
            this._statusMessage = this.Y.one(this.baseSelector + "_StatusMessage");
        }
        else
        {
            // If the dialog is already showing, don't re-initialize the stuff below
            if(this._dialog.cfg.getProperty('visible') === true)
                return;
        }

        if(this._statusMessage)
            this._statusMessage.set('innerHTML',"");

        this.input.removeClass("rn_ErrorField");
        RightNow.UI.show([this.baseSelector + '_FileAttachContent', this.input]);

        this._dialog.cfg.setProperty('fixedcenter', true);
        this._dialog.show();
    },

    /**
     * Handler for the event fired by the parent for uploading the file
     * @param eventObject object Event arguments
     */
     _uploadFile: function(eventObject)
    {
        // Notify chat server that a file is being uploaded
        RightNow.Event.fire("evt_chatNotifyFattachLocalRequest", new RightNow.Event.EventObject(this));

        if(!this._centeredDuringUpload)
        {
            this._dialog.cfg.setProperty('fixedcenter', false);
            this._dialog.cfg.setProperty('x', this._xPositionDuringUpload);
            this._dialog.cfg.setProperty('y', this._yPositionDuringUpload);
        }

        // Let parent know that we will handle the file upload entirely in this widget
        return false;
    },

    /**
    * Event handler for response on "notify" signal. This effectively
    * makes the notify/upload signals fire synchronously
    * @param type string Event name
    * @param args object Event arguments
    */
    _fileNotifyResponse: function(type, args)
    {
        this._transactionID = args[0].transactionID;
        this._parentFormElement.set('enctype', 'multipart/form-data').set('encoding', 'multipart/form-data');
        this._eo.data.transactionID = this._transactionID;
        this._eo.data.filename = this.input.get('value');

        var postData = {
            name: this.data.js.id,
            path: this.data.js.path,
            max_attach_hash: this.data.js.max_attach_hash,
            constraints: this.data.js.constraints
        };
        
        if(this.data.js.f_tok){
            RightNow.Event.subscribe("evt_formTokenUpdate", RightNow.Widgets.onFormTokenUpdate, this);
            // Get a new f_tok value on each uploads
            RightNow.Event.fire("evt_formTokenRequest",
                new RightNow.Event.EventObject(this, {data:{formToken:this.data.js.f_tok}}));
                
            if(postData)    
                postData.f_tok = this.data.js.f_tok;
        }

        RightNow.Ajax.makeRequest("/ci/fattach/upload", postData, {
            json: true,
            data: this._eo,
            scope: this,
            timeout: (RightNow.Interface.getConfig("CP_FILE_UPLOAD_MAX_TIME") || 300) * 1000,
            upload: this._parentFormElement.get('id'),
            successHandler: this._onFileUploaded,
            failureHandler: function() {} // Disable default exception handler behavior (prevent generic error dialog from appearing)
        });

        this.input.set("disabled", true);
        this._statusMessage.set('innerHTML', this.data.attrs.label_wait_upload_finished);
    },
    
    /**
    * Event handler for upload response. Parse out the result code and update UI accordingly.
    * @param response object Response object
    * @param originalEventObject object Original response object
    */
    _onFileUploaded: function(response, originalEventObject)
    {
        var attachmentInfo = response,
            attachmentErrorInfo;

        // It could be possible that the response is from a previous upload request that was canceled. Validate that.
        if(this._transactionID === null || this._transactionID !== originalEventObject.data.transactionID)
            return;

        this._resetDialog();
        RightNow.UI.hide(this.input);

        attachmentErrorInfo = this.getAttachmentErrorInfo(attachmentInfo);
        if(this._statusMessage)
        {
            if(attachmentErrorInfo && attachmentErrorInfo.errorMessage)
            {
                this._statusMessage.set('innerHTML', attachmentErrorInfo.errorMessage);
                this._dialog.syncUI();
                return;
            }
        }

        // If we are here, all is well. hide the dialog and notify chat server of the upload
        this._dialog.hide();
        var eventObject = new RightNow.Event.EventObject(this);
        eventObject.data = response;
        eventObject.data.transactionID = originalEventObject.data.transactionID;

        //Notify chat service that the file is uploaded
        RightNow.Event.fire("evt_chatNotifyFileUploadRequest", eventObject);
    },

    /**
    * Function used to reset the file attachment dialog and hide it
    */
    _resetDialog: function()
    {
        this._uploading = false;
        this.input.set("disabled", false);
        this.recreateInput();
        this._parentFormElement.reset();
        RightNow.UI.hide([this._loading, this.baseSelector + '_Indicator']);
    },

    /**
     * User cancelled file attachment upload. Clean up.
     */
    _onCancel: function()
    {
        RightNow.Event.fire("evt_fileUploadCancelRequest", new RightNow.Event.EventObject(this));

        if(this._dialog)
            this._dialog.hide();

        this._resetDialog();
    },

    /**
     * Handles the state of the chat has changed. Hides button if disconnected.
     * @param type string Event name
     * @param args object Event arguments
     */
    _onChatStateChangeResponse: function(type, args)
    {
        var currentState = args[0].data.currentState;
        var ChatState = RightNow.Chat.Model.ChatState;

        if(currentState === ChatState.CONNECTED)
        {
            this._container.addClass("rn_ChatAttachFileButtonShown");
            RightNow.UI.show(this._container);
        }
        else if(currentState === ChatState.REQUEUED ||
                currentState === ChatState.DISCONNECTED ||
                currentState === ChatState.RECONNECTING)
        {
            this._container.removeClass("rn_ChatAttachFileButtonShown");
            RightNow.UI.hide(this._container);
            if(currentState !== ChatState.RECONNECTING) 
            {
                this._onCancel();
            }
        }
    },

    /**
     * Listener for participant joining the engagement.
     * @param type string Event name
     * @param args object Event arguments
     */
    _onChatEngagementParticipantAddedResponse: function(type, args)
    {
        var vaMode = (args[0].data.virtualAgent === undefined) ? false : args[0].data.virtualAgent;

        if (vaMode === false)
        {
            this._attachButton.removeClass("rn_Hidden");
        }
        else
        {
            this._attachButton.addClass("rn_Hidden");
        }
    }
});
