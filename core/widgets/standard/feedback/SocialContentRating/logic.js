RightNow.Widgets.SocialContentRating = RightNow.Widgets.extend({
    constructor: function() {
        if(!this.data.js.canRate){
            return;
        }
        this._baseNode = this.Y.one(this.baseSelector);
        this._resetButton = this._baseNode.one(".rn_ResetButton");

        if(this.data.js.canResetRating) {
            this._resetButton.removeClass('rn_Hidden');
        }

        if(this.data.attrs.rating_count_format === "graphical"){
            this.rateNoVoteGraph = this._baseNode.one(".rn_RateGraphNoVotes");
            this.rateVotedGraph = this._baseNode.one(".rn_RateGraphVoted");
        }

        if(this._resetButton) {
            this._resetButton.on('click', this._submit, this);
        }

        this._upvoteButton = this.Y.all(this.baseSelector + " .rn_RatingButtons button");

        if(this._upvoteButton) {
            this._upvoteButton.on('click', this._submit, this);
        }
    },

    /**
     * Event handler for when a user is submitting a vote of a piece of content
     * @param event String event object
     */
    _submit: function(e) {
        e.target.hasClass('rn_ResetButton') ? this._resetButton.set('disabled', true) : this._toggleDisabledVoteButton(true);
        this.userRating = e.target.hasClass('rn_ResetButton') ? 0 : (parseFloat(e.target.get('value')) || 1);
        this._sendVote();
    },

    /**
     * Builds event object and fires off the ajax call for submitting a vote
     */
    _sendVote: function() {
        var eventObject = new RightNow.Event.EventObject(this, {
            data: {
                questionID: this.data.attrs.question_id,
                commentID: this.data.attrs.comment_id,
                rating: this.userRating,
                w_id: this.data.info.w_id
            }
        });
        
        if(this.data.js.f_tok){
            RightNow.Event.subscribe("evt_formTokenUpdate", RightNow.Widgets.onFormTokenUpdate, this);
            // Get a new f_tok value on each ajax request
            RightNow.Event.fire("evt_formTokenRequest", new RightNow.Event.EventObject(this, {data:{formToken:this.data.js.f_tok}}));

            eventObject.data.f_tok = this.data.js.f_tok;
        }

        if(RightNow.Event.fire("evt_submitVoteRequest", eventObject)) {
            RightNow.Ajax.makeRequest(this.data.attrs.submit_vote_ajax,
                eventObject.data,
                {successHandler: (this.userRating ? this._onResponseReceived : this._onVoteResetResponse), scope: this, data: eventObject, json: true});
        }
    },

    /**
     * Toggles disabled attribute on the upvote button.
     * @param {bool} force Force button to a specific state
     */
    _toggleDisabledVoteButton: function(force) {
        this._upvoteButton.set('disabled', force);
    },

    /**
     * Updates the button's title and screen reader text.
     * @param  {string} message Message to set
     * @param  {object} button Button on which action needs to be performed
     * @param  {boolean} voteAction True if it is a rating operation
     */
    _updateVoteButtonTitle: function(message, button, voteAction) {
        var title;
        button = button || this._upvoteButton;
        button.set('title', message);

        if (this.data.attrs.rating_type === 'upvote') {
            title = message;
            button.all('.rn_ScreenReaderOnly').setHTML(title);
        }
        else if(this.data.attrs.rating_type === 'star') {
            title = message + " " + (voteAction ? this.userRating : parseInt(button.get('value'), 10));
            if (voteAction) {
                button.all('.rn_ScreenReaderOnly').addClass('rn_Hidden');
                this.Y.one(this.baseSelector + " .rn_StarVoting .rn_ScreenReaderOnly").removeClass('rn_Hidden').setHTML(title);
                button.set('aria-hidden', 'true');
            }
            else {
                this.Y.one(this.baseSelector + " .rn_StarVoting .rn_ScreenReaderOnly").addClass('rn_Hidden');
                button.all('.rn_ScreenReaderOnly').removeClass('rn_Hidden').setHTML(title);
                button.set('aria-hidden', 'false');
            }
        }
        else if(this.data.attrs.rating_type === 'updown') {
            if (voteAction) {
                title = message + " " + ((this.userRating === 2) ? RightNow.Interface.getMessage("UP_LBL") : RightNow.Interface.getMessage("DOWN_LBL"));
                button.all('.rn_ScreenReaderOnly').addClass('rn_Hidden');
                this.Y.one(this.baseSelector + " .rn_UpDownVoting .rn_ScreenReaderOnly").removeClass('rn_Hidden').setHTML(title);
                button.set('aria-hidden', 'true');
            }
            else {
                title = message + " " + (button.hasClass('rn_ThumbsUpButton') ? RightNow.Interface.getMessage("UP_LBL") : RightNow.Interface.getMessage("DOWN_LBL"));
                this.Y.one(this.baseSelector + " .rn_UpDownVoting .rn_ScreenReaderOnly").addClass('rn_Hidden');
                button.all('.rn_ScreenReaderOnly').removeClass('rn_Hidden').setHTML(title);
                button.set('aria-hidden', 'false');
            }
        }
    },

    /**
     * Updates the rating value UI.
     * @param  {string} totalRatingLabel Rating of the content to be displayed
     */
    _updateRating: function(totalRatingLabel) {
        var ratingTitle = totalRatingLabel.label;
        if (totalRatingLabel.totalVotes === 0) {
            ratingTitle = this.data.attrs.label_be_first_to_vote;
        }
        else if(this.data.attrs.rating_count_format === "numerical" && this.data.attrs.label_vote_count_singular && this.data.attrs.label_vote_count_plural){
            ratingTitle = (totalRatingLabel.totalVotes === 1) ? this.data.attrs.label_vote_count_singular : this.data.attrs.label_vote_count_plural;
        }

        this._baseNode.one(".rn_RatingValue").set('title', ratingTitle);
        if(this.data.attrs.rating_count_format === "numerical") {
            this._baseNode.one(".rn_RatingValueNumerical").setHTML(totalRatingLabel.label);
        }
        else {
            var totalVoteLabel = "(" + totalRatingLabel.totalVotes + " " + (totalRatingLabel.totalVotes === 1 ? RightNow.Interface.getMessage('USER_LC_LBL') : RightNow.Interface.getMessage('USERS_LC_LBL')) + ")";
            var buttonVal = 1;
            if(totalRatingLabel.totalVotes === 0) {
                this.rateVotedGraph.addClass('rn_Hidden');
                this.rateNoVoteGraph.removeClass('rn_Hidden');
                this.rateNoVoteGraph.setHTML(totalRatingLabel.label);
            }
            else {
                this.rateNoVoteGraph.addClass('rn_Hidden');
                this.rateVotedGraph.removeClass('rn_Hidden');
                if(this.data.attrs.rating_type === 'star') {
                    this.Y.all(this.baseSelector + " .rn_StarRateCount .rn_StarButton").each(function(star) {
                        star.removeClass('rn_VotedRating').addClass('rn_VoteRating');
                        star.removeClass('rn_VotedHalfRating').addClass('rn_VoteRating');
                        var classToAdd = (totalRatingLabel.ratedValue < buttonVal) ? ((totalRatingLabel.ratedValue < (buttonVal - 0.5)) ? 'rn_VoteRating' : 'rn_VotedHalfRating') : 'rn_VotedRating';
                        star.removeClass('rn_VoteRating').addClass(classToAdd);
                        this._baseNode.one(".rn_StarRateTotal").setHTML(totalVoteLabel);
                        buttonVal++;
                    }, this);
                }
                else if(this.data.attrs.rating_type === 'updown') {
                    this._baseNode.one(".rn_UpDownRatePositive").setHTML(totalRatingLabel.positiveVotes);
                    this._baseNode.one(".rn_UpDownRateNegative").setHTML(totalRatingLabel.negativeVotes);
                    this._baseNode.one(".rn_UpDownRateTotal").setHTML(totalVoteLabel);
                }
                else {
                    this._baseNode.one(".rn_RatePositive").setHTML(totalRatingLabel.positiveVotes);
                    this._baseNode.one(".rn_RateTotal").setHTML(totalVoteLabel);
                }
            }
        }
    },

    /**
     * Event handler for when a user clicks on an answer rating
     * @param {object} response Response object from ajax call
     */
    _onResponseReceived: function(response) {
        var bannerOptions = {},
            message;

        if(response && response.ratingID && !response.errors) {
            message = this.data.attrs.label_upvote_thanks;
            this._upvoteButton.each(function(button) {
                this._updateVoteButtonTitle(message, button, true);
                if(this.data.attrs.rating_type === 'star') {
                    if(parseInt(button.get('value'), 10) <= this.userRating) {
                        button.addClass('rn_Voted').removeClass('rn_Vote');
                    }
                }
                else if(this.data.attrs.rating_type === 'updown') {
                    if(parseFloat(button.get('value')) === this.userRating) {
                        button.addClass('rn_Voted').removeClass('rn_Vote');
                    }
                }
            }, this);
            if(response.canResetRating) {
                this._resetButton.removeClass('rn_Hidden');
                this._resetButton.set('disabled', false);
            }
            this._updateRating(response.totalRatingLabel);
        }
        else {
            this._toggleDisabledVoteButton(false);
            if(RightNow.Ajax.indicatesSocialUserError(response)) {
                return;
            }
            if (response.errors && response.errors[0]) {
                message = response.errors[0].externalMessage;
            }
            else {
                message = response.suggestedErrorMessage || RightNow.Interface.getMessage("THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG");
            }

            bannerOptions.type = 'ERROR';
        }

        var activeButton = this.Y.one(this.baseSelector + ' .rn_RatingValue');
        bannerOptions.focusElement = activeButton;
        RightNow.UI.displayBanner(message, bannerOptions);
    },

     /**
     * Success handler for when a user clicks on an vote reset
     * @param {object} response Response object from ajax call
     */
    _onVoteResetResponse: function(response) {
        var bannerOptions = {},
            message;

        if(response && response.ratingReset && !response.errors) {
            message = this.data.attrs.label_vote_reset;
            this._updateRating(response.totalRatingLabel);
            this._toggleDisabledVoteButton(false);
            this._resetButton.addClass('rn_Hidden');
            this._upvoteButton.each(function(button) {
                this._updateVoteButtonTitle(this.data.attrs.label_upvote_hint, button, false);
                button.removeClass('rn_Voted').addClass('rn_Vote');
            }, this);
        }
        else if (response.errors) {
            if(!RightNow.Ajax.indicatesSocialUserError(response)) {
                message = response.errors[0].externalMessage;
            }
            bannerOptions.type = 'ERROR';
        }
        else {
            message = response.suggestedErrorMessage || RightNow.Interface.getMessage("THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG");
            bannerOptions.type = 'ERROR';
        }

        RightNow.UI.displayBanner(message, bannerOptions);
    }
});
