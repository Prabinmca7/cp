RightNow.Widgets.CommunityPostDisplay = RightNow.Widgets.extend({
    constructor: function() {
        this._ratings = [];
        this._ariaAlert = this.Y.Node.create("<div/>")
            .set("role", "alert")
            .set('className', "rn_ScreenReaderOnly");
        this._baseDiv = this.Y.one(this.baseSelector);
        this._baseDiv.insertBefore(this._ariaAlert, this._baseDiv.one('*') || this._baseDiv);
        var showComments;
        if(this.data.attrs.show_comment_count && (showComments = this.Y.one(this.baseSelector + '_ShowComments'))){
            showComments.on('click', this._showComments, this);
        }
        if(this.data.attrs.post_ratings && RightNow.Profile.isLoggedIn()){
            var rateUp = this.Y.one(this.baseSelector + '_RateUp'),
                rateDown = this.Y.one(this.baseSelector + '_RateDown');
            if(rateUp)
                rateUp.on('click', this._submitRating, this, 100);
            if(rateDown)
                rateDown.on('click', this._submitRating, this, 0);
        }
        if(this.data.attrs.post_comments && RightNow.Profile.isLoggedIn()){
            var toggle = this.Y.one(this.baseSelector + "_PostCommentSubmit"),
                textBox = this.Y.one(this.baseSelector + "_Comment"),
                cssClass = "rn_CommentPlaceHolder",
                placeHolder = this.data.attrs.label_comment_placeholder,
                submit = this.Y.one(this.baseSelector + '_Submit');
            if(textBox) {
                textBox.on('focus', function(evt){
                        var focused = evt.target;
                        if(!RightNow.Profile.isLoggedIn()){
                            evt.halt();
                            focused.blur();
                            this._promptToLogIn();
                            return;
                        }
                        if(focused.get('value') === placeHolder)
                            focused.set('value', "");
                        focused.removeClass(cssClass);
                        RightNow.UI.show(toggle);
                    }, this);
                textBox.on('blur', function(evt){
                        var blurred = evt.target;
                        if(blurred.get('value') === ""){
                            blurred.set('value', placeHolder)
                                .addClass(cssClass);
                            RightNow.UI.hide(toggle);
                        }
                    }, this);
            }
            if(submit)
                submit.on('click', this._submitComment, this, 100);
        }
    },

    /**
    * Displays a dialog.
    * @param {Object} evt The show comments link clicked event
    */
    _showComments: function(evt){
        if(!this._commentsLoaded){
            this._commentsLoaded = true;
            this._loading = this.Y.Node.create("<span/>");
            this._loading.set('className', "rn_Loading");
            evt.target.insert(this._loading, 'after');
            var eventObject = new RightNow.Event.EventObject(this, {data: {
                w_id: this.data.info.w_id,
                postID: this.data.js.postHash
            }});
            if(RightNow.Event.fire("evt_getCommentListRequest", eventObject)){
                RightNow.Ajax.makeRequest(this.data.attrs.get_post_comments_ajax, eventObject.data, {successHandler: this._readyToShowComments, scope: this, data: eventObject, json: true});
            }
            else {
                this._commentsLoaded = false;
            }
        }
    },
    
    /**
    * Called when comments are returned from the server.
    * @param {Object} response Response event object
    * @param {Object} originalEventObj Original event object
    */
    _readyToShowComments: function(response, originalEventObj){
        if(RightNow.Event.fire("evt_getCommentListResponse", {data: originalEventObj, response: response})) {
            if(originalEventObj.w_id === this.instanceID && response.comments){
                var comments = response.comments, createdCommentWrapper = this._createdCommentWrapper,
                    i, wrapper, comment, previousCommentElement, commentElement, insertCommentsBefore;

                wrapper = this._getCommentContainer();
                if(createdCommentWrapper){
                    //user submitted a comment before expanding comments so the comment is already showing
                    comments.pop();
                    insertCommentsBefore = wrapper.hasChildNodes();
                }
                for(i = 0; i < comments.length; i++){
                    comment = comments[i];
                    commentElement = this._createCommentElement(comment, false);
                    if(previousCommentElement)
                        previousCommentElement.insert(commentElement, 'after');
                    else if(insertCommentsBefore)
                        wrapper.one('*').insert(commentElement, 'before');
                    else
                        wrapper.append(commentElement);
                    this._ratings[comment.id] = {rating: comment.ratingValueTotal, count: comment.ratingCount};
                    previousCommentElement = commentElement;
                }
                //kill loading; it should only be used and displayed once
                this._loading.remove();
                delete this._loading;
                var commentsLink = this.Y.one(this.baseSelector + "_ShowComments");
                if(commentsLink)
                {
                    var alertSpan = this.Y.Node.create("<span/>")
                        .set("role", "alert")
                        .set('className', "rn_ScreenReaderOnly")
                        .set('innerHTML', " " + RightNow.Interface.getMessage("NEW_CONTENT_ADDED_BELOW_MSG"));
                    commentsLink.appendChild(alertSpan);
                    commentsLink.on('blur', function(){ alertSpan.set('innerHTML', ""); commentsLink.detach('blur');});
                }
            }
        }
    },
    
    /**
    * User rates a post or comment.
    * @param {Object} evt The rate link click event
    * @param {Int} rating The rating; either 0 or 100
    * @param {Int} [commentID] The ID of the comment being rated;
    *   omitted when the post is being rated
    */
    _submitRating: function(evt, rating, commentID){
        if(!this._currentlyRating && !this._submittingComment){
            this._currentlyRating = true;
            var eventObject = new RightNow.Event.EventObject(this, {
                data: {
                    w_id: this.data.info.w_id,
                    postID: this.data.js.postHash,
                    content: rating,
                    action: "rate"
            }});
            if(commentID){
                eventObject.data.commentID = commentID;
            }
            if(RightNow.Event.fire("evt_postCommentActionRequest", eventObject)){
                RightNow.Ajax.makeRequest(this.data.attrs.post_comment_ajax, eventObject.data, {successHandler: this._onServerActionResponse, scope: this, data: eventObject, json: true});
            }
            else {
                this._currentlyRating = false;
            }
        }
    },
    
    /**
    * Rating submission response has returned from the server.
    * @param {Object} response Response object
    */
    _onSubmitRatingSuccess: function(response){
        var ratingSection, ratingControls, html;
        if(response.id !== null && this._ratings[response.id]){
            //comment rating
            ratingSection = this.Y.one(this.baseSelector + "_Rating" + response.id);
            ratingControls = this.Y.one(this.baseSelector + "_RatingControls" + response.id);
            html = new EJS({text: this.getStatic().templates.rating}).render(
                    this._generateRatingSection(this._ratings[response.id].count, this._ratings[response.id].rating + response.rating, response.rating));
        }
        else if(response.rating !== null){
            //post rating
            ratingSection = this.Y.one(this.baseSelector + "_PostRating");
            ratingSection.set('className', "rn_PostRating");
            ratingControls = this.Y.one(this.baseSelector + "_PostRatingControls");
            html = new EJS({text: this.getStatic().templates.rating}).render(
                    this._generateRatingSection(((this.data.attrs.show_post_ratings) ? this.data.js.postRating.count : 0), this.data.js.postRating.rating + response.rating, response.rating));
        }
        else if(response.message){
            RightNow.UI.Dialog.messageDialog(response.message, {icon: "WARN"});
        }
        
        if(ratingSection && ratingControls){
            ratingSection.set('innerHTML', html);
            this._ariaAlert.set('innerHTML', ratingSection.get('children').item(0) ? ratingSection.get('children').item(0).get('innerHTML') : html);
            ratingControls.remove();
        }
        this._currentlyRating = false;
    },
    
    /**
    * The user submits a comment.
    * @param {Object} evt The submit button's click event
    */
    _submitComment: function(evt){
        if(!this._currentlyRating && !this._submittingComment){
            this._submittingComment = true;
            var commentField = this.Y.one(this.baseSelector + "_Comment"),
                comment;
            if(commentField){
                commentField.set('disabled', true);
                comment = this.Y.Lang.trim(commentField.get('value'));
                if(comment){
                    var eventObject = new RightNow.Event.EventObject(this, {data: {
                        w_id: this.data.info.w_id,
                        postID: this.data.js.postHash,
                        content: comment,
                        action: "reply"
                    }});
                    if(RightNow.Event.fire("evt_postCommentActionRequest", eventObject)){
                        RightNow.Ajax.makeRequest(this.data.attrs.post_comment_ajax, eventObject.data, {successHandler: this._onServerActionResponse, scope: this, data: eventObject, json: true});
                    }
                    else {
                        this._submittingComment = false;
                    }
                }
            }
        }
    },
    
    /**
    * Comment submission response has returned from the server.
    * @param {Object} response Response object
    */
    _onSubmitCommentSuccess: function(response){
        if(response.comment){
            var newCommentHTML = this._createCommentElement(response.comment, true),
                commentContainer = this._getCommentContainer(),
                commentField = this.Y.one(this.baseSelector + "_Comment");
            if(newCommentHTML && commentContainer && commentField){
                commentContainer.append(newCommentHTML);
                commentField.set('value', "")
                    .set('disabled', false);
                this._ratings[response.comment.id] = {rating: 0, count: 0};
                this._ariaAlert.set('innerHTML', RightNow.Interface.getMessage("YOUR_COMMENT_WAS_ADDED_LBL"));
            }
        }
        else if(response.message){
            RightNow.UI.Dialog.messageDialog(response.message, {icon: "WARN"});
        }
        this._submittingComment = false;
    },
    
    /**
    * Event listener for when a response is returned from the server.
    * @param {Object} response Response event object
    * @param {Object} originalEventObj Original event object
    */
    _onServerActionResponse: function(response, originalEventObj){
        if(RightNow.Event.fire("evt_postCommentActionResponse", {data: originalEventObj, response: response})) {
            var action = originalEventObj.data.action;
            if(originalEventObj.w_id === this.instanceID){
                if(action === "reply"){
                    this._onSubmitCommentSuccess(response);
                }
                else if(action === "rate"){
                    this._onSubmitRatingSuccess(response);
                }
            }
        }
    },
    
    /**
    * Returns the comment container HTML element; creates it
    * if it doesn't already exist.
    */
    _getCommentContainer: function(){
        if(!this._createdCommentWrapper){
            var wrapper = this.Y.Node.create("<div/>"),
                previousSibling = this.Y.one(this.baseSelector + "_CommentCount");
            wrapper.set('className', "rn_LoadedComments")
                .set('id', this.baseSelector.substr(1) + "_Comments");
            //various siblings may/may not exist depending on attrs and we want to stick comments in between some of them
            //try up to three siblings; the final sibling will always exist (assuming the view wasn't modified)
            //if none of expected siblings exist then the comment element isn't added and comments aren't displayed
            if(previousSibling){
                previousSibling.insert(wrapper, 'after');
            }
            else{
                var insertBySibling = function(baseDiv, classNameOfSibling, positionToInsert){
                        var nextSibling = baseDiv.all('div.' + classNameOfSibling);
                        if(nextSibling && nextSibling.item(0)){
                            nextSibling.item(0).insert(wrapper, positionToInsert);
                            return true;
                        }
                        return false;
                    };
                (insertBySibling(this._baseDiv, "rn_PostComment", "before") || insertBySibling(this._baseDiv, "rn_PostRate", "after"));
            }
            wrapper.on("click", function(evt){
                var target = evt.target,
                    name, rating, commentID;
                if(target.get('href') && target.get('tagName') === "A"){
                    name = target.get('name');
                    commentID = name.match(/\d+/);
                    if(commentID !== null){
                        commentID = parseInt(commentID[0], 10);
                        rating = (name.indexOf("rateUp") > -1) ? 100 : 0;
                        this._submitRating("", rating, commentID);
                    }
                }
            }, this);
            this._createdCommentWrapper = true;
        }
        return wrapper || this.Y.one(this.baseSelector + "_Comments");
    },
    
    /**
    * Helper that creates a comment element from the supplied comment object
    * @param {Object} comment The comment object
    * @param {Bool} hideRatingOptions Denotes if rating links should be hidden. If set to false, ratings will only be shown if the 
    *                          current comment was not created by the currently logged in user.
    * @return {String} The HTML representing the comment
    */
    _createCommentElement: function(comment, hideRatingOptions){
        var displayRatingControls = true,
            userRating,
            attrs = this.data.attrs;
        //Don't allow commenting if the user isn't logged in, they're a new user, or they are the one who created the comment
        if(!RightNow.Profile.isLoggedIn() || this.data.js.newUser || hideRatingOptions || parseInt(comment.createdBy.guid, 10) === RightNow.Profile.contactID()){
            displayRatingControls = false;
        }
        else if(comment.ratedByRequestingUser && comment.ratedByRequestingUser.ratingValue !== null){
            displayRatingControls = false;
            userRating = comment.ratedByRequestingUser.ratingValue;
        }

        return this.Y.Node.create("<div/>")
            .addClass('rn_Comment')
            .set("innerHTML", new EJS({text: this.getStatic().templates.comment}).render({
                avatar: comment.createdBy.avatar,
                commenter: comment.createdBy.name,
                comment: comment.value,
                showPostedDate: attrs.show_posted_date,
                postedDate: attrs.label_posted_date,
                commentCreated: comment.created,
                displayRatingControls: displayRatingControls,
                instanceID: this.instanceID,
                commentID: comment.id,
                positiveRating: attrs.label_positive_rating,
                negativeRating: attrs.label_negative_rating,
                ratingSection: new EJS({text: this.getStatic().templates.rating}).render(
                    this._generateRatingSection(comment.ratingCount, comment.ratingValueTotal, userRating))
            }));
    },
    
    /**
    * Helper that creates object used to render the rating section for a comment.
    * @param {Int} ratingCount The number of times a comment has been rated
    * @param {Int} ratingTotalValue The combined score of the comment
    * @param {Int} [usersRating] The user's rating; either 0 or 100
    * @return {Object} Object used to render the rating section
    */
    _generateRatingSection: function(ratingCount, ratingTotalValue, usersRating){
        var returnValue = {
                displayUserRating: false,
                displayPositiveRating: false,
                displayNegativeRating: false
            },
            positiveRating, negativeRating,
            attrs = this.data.attrs;
        if(typeof usersRating !== "undefined" && usersRating !== null){
            returnValue.displayUserRating = true;
            if(usersRating === 100){
                returnValue.userClassRating = "rn_PositiveRating";
                returnValue.userRatingLabel = attrs.label_positive_rating_submitted;
            }
            else if(usersRating === 0){
                returnValue.userClassRating = "rn_NegativeRating";
                returnValue.userRatingLabel = attrs.label_negative_rating_submitted;
            }
        }
        if(ratingCount){
            positiveRating = Math.round(ratingTotalValue / 100);
            negativeRating = ratingCount - positiveRating;
            if(positiveRating > 0 && !(usersRating === 100 && positiveRating === 1)){
                //don't display "one person liked this" if the one person happened to be the current user
                returnValue.displayPositiveRating = true;
                returnValue.positiveRatingLabel = RightNow.Text.sprintf(((positiveRating === 1) ? attrs.label_single_rating_count : attrs.label_rating_count), positiveRating);
            }
            if(negativeRating > 0 && !(usersRating === 0 && negativeRating === 1)){
                //ditto here too
                returnValue.displayNegativeRating = true;
                returnValue.negativeRatingLabel = RightNow.Text.sprintf(((negativeRating === 1) ? attrs.label_single_negative_rating_count : attrs.label_negative_rating_count) + "</span>", negativeRating);
            }
        }
        return returnValue;
    }
});
