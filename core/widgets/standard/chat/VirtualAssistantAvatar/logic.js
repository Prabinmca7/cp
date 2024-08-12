RightNow.Widgets.VirtualAssistantAvatar = RightNow.Widgets.extend({
    constructor: function() {
        RightNow.Event.subscribe('evt_chatPostResponse', this._onChatPostResponse, this);
        RightNow.Event.subscribe('evt_chatEngagementParticipantAddedResponse', this._onChatEngagementParticipantAddedResponse, this);
        RightNow.Event.subscribe("evt_chatStateChangeResponse", this._onChatStateChangeResponse, this);

        this.baseElement = this.Y.one(this.baseSelector);
        this.emotionElement = this.Y.one(this.baseSelector + ' .rn_Emotion');
        this.previousEmotion = '';
        this.displayAvatar = false;
    },

    /**
     * Handles the CSS class based on the emotion returned in the VA response
     * @param type string Event name
     * @param args object Event arguments
     */
    _onChatPostResponse: function(type, args) {
        if (!args[0].data.isEndUserPost && args[0].data.vaResponse !== undefined && args[0].data.vaResponse !== null) {
            var vaResponse = args[0].data.vaResponse,
                emotion = vaResponse.emotion;

            if (this.previousEmotion !== '') {
                this.emotionElement.removeClass(this.previousEmotion);
            }
            this.emotionElement.addClass(emotion);
            this.previousEmotion = emotion;
        }
    },

    /**
     * Listener for participant joining the engagement.
     * @param type string Event name
     * @param args object Event arguments
     */
    _onChatEngagementParticipantAddedResponse: function(type, args) {
        var vaMode = (args[0].data.virtualAgent === undefined) ? false : args[0].data.virtualAgent;

        if (vaMode === false) {
            if (this.data.attrs.default_chat_avatar !== '') {
                this.emotionElement.addClass(this.data.attrs.default_chat_avatar);
            }
            else {
                this.baseElement.addClass('rn_Hidden');
            }
        }
        else {
            if (this.data.attrs.default_chat_avatar !== '') {
                this.emotionElement.removeClass(this.data.attrs.default_chat_avatar);
            }

            // We only want to subsequently display this on other joins if this path was reached prior (a VA avatar existed at some point in the chat)
            this.displayAvatar = true;
        }

        if(this.displayAvatar)
            this.baseElement.removeClass('rn_Hidden');
    },

    /**
     * Listener for Chat State Change notifications.
     * @param type string Event name
     * @param args object Event arguments
     */
    _onChatStateChangeResponse: function(type, args) {
        var eventObject = args[0];

        // Hide the avatar on requeue
        if(eventObject.data.currentState === RightNow.Chat.Model.ChatState.REQUEUED)
            this.baseElement.addClass('rn_Hidden');
    }
});
