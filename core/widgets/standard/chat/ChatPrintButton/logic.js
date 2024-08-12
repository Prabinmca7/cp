RightNow.Widgets.ChatPrintButton = RightNow.Widgets.extend({
    constructor: function(){
        // Local member variables section
        this._container = this.Y.one(this.baseSelector);
        this._printButton = this.Y.one(this.baseSelector + '_Button');

        // Event subscription and listener section.
        if(this._printButton && this._container)
        {
            this._printButton.on("click", this._onButtonClick, this);
            RightNow.Event.subscribe("evt_chatStateChangeResponse", this._onChatStateChangeResponse, this);

            this._toggleInProblematicBrowsers(true);
        }
    },

    /**
     * Handles when user clicks button.
     */
    _onButtonClick: function()
    {
        window.print();
    },

    /**
     * Handles the state of the chat has changed.
     * @param type string Event name
     * @param args object Event arguments
     */
    _onChatStateChangeResponse: function(type, args)
    {
        var currentState = args[0].data.currentState;
        var ChatState = RightNow.Chat.Model.ChatState;

        if(currentState === ChatState.CONNECTED)
        {
            RightNow.UI.show(this._container);
        }
        else if(currentState === ChatState.REQUEUED)
        {
            RightNow.UI.hide(this._container);
        }
        else if(currentState === ChatState.DISCONNECTED)
        {
            this._toggleInProblematicBrowsers(false);
        }
    },

    /**
     * Some browsers have difficulty with printing when there are
     * outstanding AJAX requests, which is the case during an active chat.
     * Detect these browsers and disable. Print is re-enabled on chat completion.
     * @param disabled boolean Flag indicating whether the button should be disabled in problematic browsers. Enabled if false.
     */
    _toggleInProblematicBrowsers: function(disabled)
    {
        var userAgent = navigator.userAgent.toLowerCase();

        // Currently, Chrome and Safari are the affected browsers. Both have "safari" in their user agent string.
        if(userAgent.indexOf('safari') > -1)
        {
            this._printButton.set('disabled', disabled);
            if(disabled)
            {
                this._printButton.set('title', this.data.attrs.label_print_after_chat);
            }
            else
            {
                this._printButton.set('title', this.data.attrs.label_tooltip);
            }
        }
    }
});