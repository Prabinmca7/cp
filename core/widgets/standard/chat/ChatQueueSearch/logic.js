RightNow.Widgets.ChatQueueSearch = RightNow.Widgets.extend({
    constructor: function(){
        this._widgetElement = this.Y.one(this.baseSelector);
        if(this._widgetElement){
            RightNow.Event.subscribe("evt_chatStateChangeResponse", this._onChatStateChangeResponse, this);
        }
    },

    /**
     * Listener for Chat State Change notifications.
     * @param type string Event name
     * @param args object Event arguments
     */
    _onChatStateChangeResponse: function(type, args){
        var eventObject = args[0];

        //show or hide the widget
        switch(eventObject.data.currentState)
        {
            case RightNow.Chat.Model.ChatState.RECONNECTING:
                return;
            case RightNow.Chat.Model.ChatState.SEARCHING:
            case RightNow.Chat.Model.ChatState.REQUEUED:
            {
                this._widgetElement.removeClass("rn_Hidden");
                break;
            }
            case RightNow.Chat.Model.ChatState.CANCELLED:
            {
                if(eventObject.data.reason == 'FAIL_NO_AGENTS_AVAIL' ||
                   eventObject.data.reason == 'QUEUE_TIMEOUT')
                    this._widgetElement.removeClass("rn_Hidden");
                else
                    this._widgetElement.addClass("rn_Hidden");
                break;
            }
            default:
            {
                this._widgetElement.addClass("rn_Hidden");
                break;
            }
        }
    }
});
