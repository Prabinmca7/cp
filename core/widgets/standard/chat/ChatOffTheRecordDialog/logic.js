RightNow.Widgets.ChatOffTheRecordDialog = RightNow.Widgets.ChatPostMessage.extend({
    overrides: {
        constructor: function(){
            this.parent();

            this._dialog = null;
            this.isOffTheRecord = true;

            if(this.input)
            {
                RightNow.Event.subscribe("evt_chatOffTheRecordButtonClickResponse", this._onChatOffTheRecordButtonClickResponse, this);
            }
        },

        _onEnterKey: function(e){
            if(e.shiftKey)
                return;

            this.parent(e);
            this._clearAndHide();
        },

        _onChatStateChangeResponse: function(type, args){
            if(args[0].data.currentState !== RightNow.Chat.Model.ChatState.CONNECTED)
                this._clearAndHide();
        }
    },

    /**
    * Off The Record button click handler. Shows dialog for entering message.
    * @param type string Event name
    * @param args object Event arguments
    */
    _onChatOffTheRecordButtonClickResponse: function(type, args)
    {
        if(!this._dialog)
        {
            var dialogTitle = this.data.attrs.label_window_title;
            var buttons = [{text: this.data.attrs.label_send_button, handler: {fn:this._onSend, scope:this}, isDefault: true},
                          {text: this.data.attrs.label_cancel_button, handler: {fn:this._clearAndHide, scope:this}}];

            // Define buttons and alignment options in JSON object.
            var dialogOptions = {
                "buttons": buttons,
                "alignOn": [
                    {
                        eventName: 'resize',
                        node: 'win'
                    },
                    {
                        eventName: 'scroll',
                        node: 'win'
                    }
                ],
                "cssClass": 'rn_ChatOffTheRecordDialogContainer'
            };

            this._dialog = RightNow.UI.Dialog.actionDialog(dialogTitle, this.container, dialogOptions);

            //Perform our dialog close cleanup when they use the X button as well
            if(this._dialog.cancelEvent)
                this._dialog.cancelEvent.subscribe(this._clearAndHide, null, this);

            //override default YUI validation to return false: don't want YUI to try to submit the form
            this._dialog.validate = function() { return false; };
        }

        RightNow.UI.show(this.container);
        this._dialog.show();
    },

    /**
     * Handle submit (send)
     */
     _onSend: function()
     {
         var text = this.input.get('value');

         if(text.replace(/^\s*/, "").length === 0 || text.length > 349525)
            return;
         //send the OTR message to the agent
         this.sendText();

         //clear state and hide us
         this._clearAndHide();
     },

     /**
     * Clears state and hides the dialog.
     */
    _clearAndHide: function()
    {
        if(this._dialog)
        {
            var eo = new RightNow.Event.EventObject(this, {data: {
                    keyEvent: null,
                    inputValue: "",
                    inputValueBeforeChange: "",
                    isOffTheRecord: true
                }});

            RightNow.Event.fire("evt_chatPostMessageKeyUpRequest", eo);

            this.input.set('value', '');
            this._dialog.hide();
        }
    }
});
