RightNow.Widgets.VirtualAssistantFeedback = RightNow.Widgets.AnswerFeedback.extend({
    overrides: {
        /**
         * Overrides RightNow.Widgets.AnswerFeedback#constructor.
         */
        constructor: function() {
            this.parent();

            RightNow.Event.subscribe("evt_chatStateChangeResponse", this._onChatStateChangeResponse, this);
            RightNow.Event.subscribe("evt_chatPostCompletion", this._onChatPostCompletion, this);
            RightNow.Event.subscribe("evt_chatEngagementParticipantAddedResponse", this._onChatEngagementParticipantAddedResponse, this);

            this._vaMode = false;
            this._baseElement = this.Y.one(this.baseSelector);
            this._thanksMessage = null;
        },

        /**
         * Event handler for when a user clicks on an answer rating. Overrides RightNow.Widgets.AnswerFeedback#_onClick.
         * @param type String Event name
         * @param rating Int rating
         */
        _onClick: function(event, rating) {
            var data = {
                    method: 'feedback',
                    package: {rating: 0}
                },
                // the API expects all ratings to be 0-100, regardless of number of options and rating of 1 is always 0
                ratings = {
                    5: [0, 25, 50, 75, 100],
                    4: [0, 33, 66, 100],
                    3: [0, 50, 100],
                    2: [100, 0] // buttons are ordered Yes, No
                };

            event.preventDefault();

            //disable the control
            if (this.data.js.buttonView || this.data.attrs.use_rank_labels)
            {
                this.Y.one(this.baseSelector + '_RatingNoButton').set('disabled', true);
                this.Y.one(this.baseSelector + '_RatingYesButton').set('disabled', true);
            }
            else
            {
                this._onCellOver(1, rating);
                event.preventDefault();
                var rateMeter = this.Y.one(this.baseSelector + "_RatingMeter");
                if (rateMeter)
                    rateMeter.purge(true);

                //change hidden alt text on each star to match the visual cues we give to a non-disabled user
                for(var cell, i = 0; i <= this.data.attrs.options_count; ++i)
                {
                    cell = this.Y.one(this.baseSelector + "_RatingCell_" + i);
                    if(cell)
                    {
                        cell.all('span.rn_ScreenReaderOnly').setHTML(RightNow.Text.sprintf(RightNow.Interface.getMessage("PCT_D_OF_PCT_D_SELECTED_LBL"), rating, this.data.attrs.options_count));
                        // re-attach onclick to prevent IE from firing Chat's onbeforeunload event.
                        this.Y.Event.attach("click", function(e) { e.preventDefault(); }, cell);
                    }
                }
            }

            // adjust rating for API
            if (ratings[this.data.attrs.options_count] && ratings[this.data.attrs.options_count][rating - 1]) {
                data.package.rating = ratings[this.data.attrs.options_count][rating - 1];
            }
            //Create the label the first time through
            if(this._thanksMessage === null){
                this._thanksMessage = this.Y.Node.create('<span class="rn_ThanksLabel">');
                this.Y.one(this.baseSelector + ((this.data.js.buttonView || this.data.attrs.use_rank_labels) ? '_RatingButtons' : '_RatingMeter')).append(this._thanksMessage);
            }
            this._thanksMessage.set('innerHTML', this.data.attrs.label_feedback_submitted).setAttribute('role', 'alert');
            RightNow.Event.fire('evt_chatPostOutOfBandDataRequest',
                new RightNow.Event.EventObject(this, {data: data}));
        }
    },

    _reEnable: function() {
        //disable the control
        if (this.data.js.buttonView || this.data.attrs.use_rank_labels) {
            this.Y.one(this.baseSelector + '_RatingNoButton').set('disabled', false);
            this.Y.one(this.baseSelector + '_RatingYesButton').set('disabled', false);
        }
        else {
            // kill the click events to prevent IE from firing onbeforeunload event
            var rateMeter = this.Y.one(this.baseSelector + "_RatingMeter");
            if (rateMeter)
                rateMeter.purge(true);
            // re-attach the event listeners
            var ratingCell = this.baseSelector + "_RatingCell_";
            for (i = 1, ids = []; i <= this.data.attrs.options_count; ++i) {
                ids.push(ratingCell + i);
            }
            this.Y.Array.each(ids, function(id, i) {
                var j = i + 1;
                this.Y.Event.attach("mouseover", this._onCellOver, id, this, j);
                this.Y.Event.attach("focus", this._onCellOver, id, this, j);
                this.Y.Event.attach("mouseout", this._onCellOut, id, this, j);
                this.Y.Event.attach("blur", this._onCellOut, id, this, j);
                this.Y.Event.attach("click", this._onClick, id, this, j);
            }, this);

            //change hidden alt text on each star to match the visual cues we give to a non-disabled user
            for (var cell, i = 0; i <= this.data.attrs.options_count; ++i) {
                cell = this.Y.one(this.baseSelector + "_RatingCell_" + i);
                if (cell) {
                    cell.all('span.rn_ScreenReaderOnly').setHTML(RightNow.Text.sprintf(this.data.attrs.label_accessible_option_description, i, this.data.attrs.options_count));
                }
            }

            this._onCellOver(1, 0); // de-select any ratings
        }
    },

    /**
     * Handles chat state changes. Hides widget if disconnected, canceled, requeued or re-connecting.
     * @param type string Event name
     * @param args object Event arguments
     */
    _onChatStateChangeResponse: function(type, args)
    {
        if(!RightNow.Event.fire("evt_handleChatStateChange", new RightNow.Event.EventObject(this, {data: args[0].data})))
            return;

        var currentState = args[0].data.currentState,
            ChatState = RightNow.Chat.Model.ChatState;

        switch (currentState)
        {
            case ChatState.REQUEUED:
            case ChatState.CANCELED:
            case ChatState.DISCONNECTED:
            case ChatState.RECONNECTING:
                this._baseElement.addClass("rn_Hidden");
                break;

            case ChatState.CONNECTED:
                if (this._vaMode === true) {
                    this._baseElement.removeClass("rn_Hidden");
                }
                break;
        }
    },

    /**
     * Event received when a post has been successfuly sent
     * @param type string Event name
     * @param args object Event arguments
     */
    _onChatPostCompletion: function(type, args) {
        if (this._vaMode === false) {
            this._baseElement.addClass("rn_Hidden");
        }
        else {
            this._reEnable();
            if(this._thanksMessage !== null){
                this._thanksMessage.set('innerHTML', '');
            }
            this._baseElement.removeClass("rn_Hidden");
        }
    },

    /**
     * Listener for participant joining the engagement.
     * @param type string Event name
     * @param args object Event arguments
     */
    _onChatEngagementParticipantAddedResponse: function(type, args) {
        this._vaMode = (args[0].data.virtualAgent === undefined) ? false : args[0].data.virtualAgent;
    }
});
