RightNow.Widgets.AnswerComments = RightNow.Widgets.extend({
    constructor: function(){
        this._deleteDialog = null;
        this._answerID = RightNow.Url.getParameter('a_id');

        var commentListing = this.Y.one(this.baseSelector + '_CommentListing'),
            commentID = RightNow.Url.getParameter('comment'),
            commentSuffix, commentContainer;

        if(commentListing)
        {
            commentListing.delegate("click", this._commentListingClick, 'a, button', this);
        }
        if(commentID)
        {
            if(commentID === "0")
            {
                if(!this.data.js.newUser){
                    this._createOrEditComment(commentID);
                    commentSuffix = '_NewComment';
                }
                else{
                    // user needs to create a community account, so just focus on the empty Actions div
                    // to scroll the user to the message to create a community account
                    commentSuffix = '_Actions';
                }
            }
            else
            {
                commentSuffix = '_Content';
            }
            commentContainer = this.Y.one(this.baseSelector + commentSuffix + commentID);
            if(commentContainer)
            {
                window.scrollTo(0, commentContainer.getY());
            }
        }
    },

    /**
     * Routing function for when a click event is captured in the comment form
     * @param {Object} event The event details
     */
    _commentListingClick:function(event)
    {
        var target = event.currentTarget;
        if(target && target.hasAttribute("data-action") && target.hasAttribute("data-commentID"))
        {
            var commentAction = target.getAttribute("data-action").toLowerCase(),
                commentID = parseInt(target.getAttribute("data-commentID"), 10);
            if(commentID !== null && commentID !== false)
            {
                switch(commentAction)
                {
                    //Reply or edit
                    case "reply":
                    case "newcomment":
                        this._createOrEditComment(commentID);
                        break;
                    case "edit":
                        this._createOrEditComment(commentID, true);
                        break;
                    //Submit comment or edit
                    case "submitreply":
                        this._submitComment(commentID);
                        break;
                    case "submitedit":
                        this._submitComment(commentID, true);
                        break;
                    //Delete or cancel
                    case "delete":
                        this._deleteCommentConfirm(commentID);
                        break;
                    case "cancelreply":
                        this._cancelSubmit(commentID);
                        break;
                    case "canceledit":
                        this._cancelSubmit(commentID, true);
                        break;
                    //Rate or Flag
                    case "flag":
                        this._flagComment(target, commentID);
                        break;
                    case "rateup":
                        this._rateComment(target, commentID, true);
                        break;
                    case "ratedown":
                        this._rateComment(target, commentID, false);
                        break;
                }
            }
        }
    },

    /**
     * Event response for when an action has been submitted
     * @param {string} type The event name
     * @param {Object} response Event data
     */
    _onActionResponse: function(response, originalEventObject)
    {
        if(!RightNow.Event.fire("evt_answerCommentActionResponse", {data: originalEventObject, response: response}))
            return;
        var action = response.action.toLowerCase(),
            id = response.id;
        switch(action)
        {
            case 'delete':
            case 'edit':
            case 'reply':
                if(response.error)
                {
                    if(action === 'edit' || action === 'reply')
                    {
                        this.Y.one(this.baseSelector + "_ResponseContainer" + id).removeClass("rn_Loading");
                        this.Y.one(this.baseSelector + "_ResponseContent" + id).setStyle("visibility", "visible");
                    }
                    else
                    {
                        this.Y.one(this.baseSelector + "_DeleteContainer" + id).removeClass("rn_Loading");
                        this.Y.one(this.baseSelector + "_DeleteContent" + id).setStyle("visibility", "visible");
                    }
                    RightNow.UI.Dialog.messageDialog(response.message);
                    return;
                }
                //No error found, but we need to display a message before refreshing the page
                else if(response.message)
                {
                    RightNow.UI.Dialog.messageDialog(response.message, {'exitCallback': function(){window.location = RightNow.Url.addParameter(window.location.pathname, 'comment', id);}});
                }
                else
                {
                    window.location = RightNow.Url.addParameter(window.location.pathname, 'comment', id);
                }
                break;
            case 'flag':
                var flagElement = this.Y.one(this.baseSelector + "_FlagContainer" + id);
                if(flagElement)
                {
                    flagElement.removeClass("rn_Loading").set("innerHTML", this.data.attrs.label_flagged);
                    if(response.error)
                    {
                        this.Y.one(this.baseSelector + "_FlagContent" + id).setStyle("visibility", "visible");
                        RightNow.UI.Dialog.messageDialog(response.message);
                        return;
                    }
                }
                break;
            case 'rate':
                var positive = response.rating,
                    rateElement = this.Y.one(this.baseSelector + "_RateContent" + id);
                if(rateElement)
                {
                    this.Y.one(this.baseSelector + "_RateContainer" + id).removeClass("rn_Loading");
                    rateElement.setStyle("visibility", "visible").set("innerHTML", '<span class="' + ((positive) ? 'rn_ThumbUp' : 'rn_ThumbDown') + ' rn_RatingIcon rn_Selected">' +
                                                '<span class="rn_ScreenReaderOnly">' + ((positive) ? this.data.attrs.label_rate_up : this.data.attrs.label_rate_down) + '</span></span>');
                    if(response.error)
                    {
                        RightNow.UI.Dialog.messageDialog(response.message);
                        return;
                    }
                }
                break;
        }
    },

    /**
     * Flags a specified comment
     * @param {Object} target The HTMLElement that was clicked
     * @param {string} id The ID of the comment flagged
     */
    _flagComment: function(target, id)
    {
        this.Y.one(this.baseSelector + "_FlagContainer" + id).addClass("rn_Loading");
        this.Y.one(this.baseSelector + "_FlagContent" + id).setStyle("visibility", "hidden");
        this._submitAction({action: "flag", content: {id: id}, answerID: this._answerID});
    },

    /**
     * Handler for when the reply to comment link is clicked. Builds up a
     * textarea and submit/cancel buttons for the user to enter their reply
     * @param {string} id The ID of the comment that is being replied to
     * @param {?boolean} edit Denotes if the comment is being edited instead of replied to
     */
    _createOrEditComment: function(id, edit)
    {
        var viewData = {existingText: "",
                        submitAction: "submitReply",
                        cancelAction: "cancelReply",
                        containerID: "NewComment",
                        editMode: edit,
                        commentID: id,
                        domPrefix: this.baseDomID,
                        notifyLabel: this.data.attrs.label_notify,
                        submitLabel: this.data.attrs.label_submit,
                        cancelLabel: this.data.attrs.label_cancel_submit,
                        commentLabel: RightNow.Interface.getMessage("ENTER_YOUR_COMMENT_LBL")};
        if(edit === true)
        {
            //Check if there already is an edit box
            if(this.Y.one(this.baseSelector + "_EditComment" + id))
                return;
            var existingReplyBox = this.Y.one(this.baseSelector + "_NewComment" + id);
            if(existingReplyBox)
                existingReplyBox.remove();

            var currentContent = this.Y.one(this.baseSelector + "_Text" + id);
            viewData.submitAction = "submitEdit";
            viewData.cancelAction = "cancelEdit";
            viewData.containerID = "EditComment";
            if(currentContent)
                viewData.existingText = currentContent.get("innerHTML");
            viewData.existingText = viewData.existingText.replace(/<br>/gi, "\n");
        }
        else
        {
            //Check if there already is a reply box
            if(this.Y.one(this.baseSelector + "_NewComment" + id))
                return;
            var existingEditBox = this.Y.one(this.baseSelector + "_EditComment" + id);
            if(existingEditBox)
                existingEditBox.remove();
        }

        var siblingNode = this.Y.one(this.baseSelector + "_Actions" + id),
            newComment = this.Y.Node.create(new EJS({text: this.getStatic().templates.view}).render(viewData));
        //newCommentContainer.insert(form, "replace");
        siblingNode.insert(newComment, "before");
        this.Y.one(this.baseSelector + "_Reply" + id).focus();
    },

    /**
     * Handler for when a users submits a new comment
     * @param {string} id The ID of the comment being replied to or edited
     * @param {?boolean} edit Denotes if the comment submitted is a reply or an edit
     */
    _submitComment: function(id, edit)
    {
        var textArea = this.Y.one(this.baseSelector + "_Reply" + id),
            notifyBox = this.Y.one(this.baseSelector + "_Notify" + id),
            commentAction = (edit) ? 'edit' : 'reply',
            content, commentData, notify;
        if(!textArea)
            return;
        content = textArea.get("value");
        if(this.Y.Lang.trim(content) === "")
        {
            RightNow.UI.Dialog.messageDialog(RightNow.Interface.getMessage("COMMENT_MUST_HAVE_CONTENT_MSG"), {icon: "WARN"});
            return;
        }
        this.Y.one(this.baseSelector + "_ResponseContainer" + id).addClass("rn_Loading");
        this.Y.one(this.baseSelector + "_ResponseContent" + id).setStyle("visibility", "hidden");
        notify = (!edit && notifyBox && notifyBox.get("checked"));
        this._submitAction({answerID: this._answerID, action: commentAction, content: {id: id, content: content, notify: notify}});
    },

    /**
     * Handler for when a user cancels out of a comment reply
     * @param {string} id The ID of the comment being replied to or edited
     * @param {?boolean} edit Denotes if the comment submitted is a reply or an edit
     */
    _cancelSubmit: function(id, edit)
    {
        var replyNode = this.Y.one(this.baseSelector + ((edit) ? '_EditComment' : '_NewComment') + id);
        if(replyNode)
            replyNode.remove();
    },

    /**
     * Handler for when a user deletes a comment
     * @param {string} id The ID of the comment being replied to or edited
     */
    _deleteCommentConfirm: function(id)
    {
        var buttons = [{ text: RightNow.Interface.getMessage("YES_LBL"), handler: {fn: function(){this._deleteComment(id);}, scope: this}, isDefault: true},
                       { text: RightNow.Interface.getMessage("NO_LBL"), handler: {fn: function(){this._deleteDialog.hide();}, scope: this}, isDefault: false}];
        var confirmElement = this.Y.Node.create("<p>")
            .addClass("rn_AnswerCommentDeleteDialog")
            .set("innerHTML", this.data.attrs.label_delete_confirm)
        this._deleteDialog = RightNow.UI.Dialog.actionDialog(this.data.attrs.label_delete_confirm_title, confirmElement, {"buttons": buttons});
        this._deleteDialog.show();
    },

    /**
     * Fires the event to delete the comment specified
     * @param {string} id The ID of the comment being deleted.
     */
    _deleteComment: function(id)
    {
        this.Y.one(this.baseSelector + "_DeleteContainer" + id).addClass("rn_Loading");
        this.Y.one(this.baseSelector + "_DeleteContent" + id).setStyle("visibility", "hidden");
        this._submitAction({action: "delete", answerID: this._answerID, content: {id: id}});
        this._deleteDialog.destroy();
    },

    /**
     * Handler for when a user rates a comment
     *
     * @param {Object} rateTarget HTML element that was clicked
     * @param {string} id The ID of the comment being replied to or edited
     * @param {boolean} rateUp Denotes if the rating was positive
     */
    _rateComment: function(rateTarget, id, rateUp)
    {
        this.Y.one(this.baseSelector + "_RateContainer" + id).addClass("rn_Loading");
        this.Y.one(this.baseSelector + "_RateContent" + id).setStyle("visibility", "hidden");
        this._submitAction({action: "rate", answerID: this._answerID, content: {id: id, rating: (rateUp) ? 100 : 0}});
    },

    /**
     * Sends a request to the server for the requested action
     * @param {Object} actioNData Details of the action to submit to the server
     */
    _submitAction: function(actionData){
        // Format an event object
        actionData.w_id = this.data.info.w_id;
        if(actionData.content){
            actionData.data = RightNow.JSON.stringify(actionData.content);
            delete actionData.content;
        }
        var eventObject = new RightNow.Event.EventObject(this, {data: actionData});
        if(RightNow.Event.fire("evt_answerCommentActionRequest", eventObject)){
            RightNow.Ajax.makeRequest(this.data.attrs.submit_action_ajax, eventObject.data, {successHandler: this._onActionResponse, scope: this, data: eventObject, json: true});
        }
    }
});
