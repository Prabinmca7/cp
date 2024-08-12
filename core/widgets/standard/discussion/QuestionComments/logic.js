RightNow.Widgets.QuestionComments = RightNow.Widgets.extend({
    constructor: function() {
        this._subscribe([
            ['click', '.rn_DeleteCommentAction',            this._deleteCommentConfirm],
            ['click', '.rn_EditCommentAction',              this._extractCommentIDFromEvent(this._commentEditDisplayClick)],
            ['click', '.rn_BestAnswerAssignment button',    this._extractCommentIDFromEvent(this._bestAnswerClick)],
            ['click', '.rn_BestAnswerRemoval button',       this._extractCommentIDFromEvent(this._bestAnswerClick)],
            ['click', '.rn_Paginator a',                    this._paginateCommentsClick],
            ['click', '.rn_SocialLogin',                    this._loginLinkClick],
            ['click', '.rn_NeedSocialInfo',                 this._needSocialInfoClick],
            ['click', '[data-toggle-parent]',               this._toggleParent],
            ['click', '.rn_SortOrder button',               this._sortComment],
            ['click', '.rn_ViewOriginalLink',               this._goToOriginalComment]
        ]);

        this.readingPosition = new RightNow.Widgets.QuestionComments.readingPosition(this.data, this.instanceID, this.Y);
        this.readingPosition.setCommentsSelector(this.baseSelector + '_Comments .rn_CommentContainer');
        this.readingPosition.on('requestPageWithComment', this._requestPageWithComment, this);

        this.commentActions = new RightNow.Widgets.QuestionComments.commentActions(this.data, this.instanceID, this.Y);
        this.commentActions.on('reply', this._commentReplyClick, this);
        this.commentActions.on('quoteComment', this._commentQuoteClick, this);

        this.newCommentEditor = new RightNow.Widgets.QuestionComments.embeddedEditor(this.data, this.instanceID, this.Y);
        this.newCommentEditor.on('submit', this._newCommentSubmitted, this);
        this.newCommentEditor.setForm(this.Y.one(this.baseSelector + '_CommentForm'));

        this.editor = new RightNow.Widgets.QuestionComments.rovingEditor(this.data, this.instanceID, this.Y);
        this.editor.on('move', this._editorMoved, this);
        this.editor.on('toggle', this._editorToggled, this);
        this.editor.on('submit', this._editorSubmitted, this);
        this.editor.setForm(this.Y.one(this.baseSelector + '_RovingCommentForm'));

        this._deleteDialog = null;

        RightNow.Event.on('evt_bestAnswerUnselect', function (evt, args) {            
            var requestParams = { commentID: args[0].data.commentID, removeAnswer: "true", chosenByType: args[0].data.chosenByType };
            
            if(this.data.js.f_tok){
                RightNow.Event.subscribe("evt_formTokenUpdate", RightNow.Widgets.onFormTokenUpdate, this);
                // Get a new f_tok value on each uploads
                RightNow.Event.fire("evt_formTokenRequest", new RightNow.Event.EventObject(this, {data:{formToken:this.data.js.f_tok}}));

                requestParams.f_tok = this.data.js.f_tok;
            }
            
            this._makeRequest(requestParams, this._events('bestAnswer'));
        }, this);
    },

    /**
     * Defines the fire event -> make request -> handle response
     * transaction flow for each ajax request that this
     * widget makes.
     * @param  {String=} name One of the event names; if not
     * specified, all events are returned
     * @return {Object}      Info about the event (or all events)
     */
    _events: function(name) {
        this.__events || (this.__events = {
            'new': {
                name:       'newComment',
                endpoint:   this.data.attrs.new_comment_ajax,
                callback:   this._newCommentResponse,
                dataType:   'text/html'
            },
            'edit': {
                name:       'editComment',
                endpoint:   this.data.attrs.edit_comment_ajax,
                callback:   this._editCommentResponse
            },
            'reply': {
                name:       'replyToComment',
                endpoint:   this.data.attrs.reply_to_comment_ajax,
                callback:   this._replyToCommentResponse,
                dataType:   'text/html'
            },
            'delete': {
                name:       'deleteComment',
                endpoint:   this.data.attrs.delete_comment_ajax,
                callback:   this._deleteCommentResponse
            },
            'bestAnswer': {
                name:       'bestAnswer',
                endpoint:   this.data.attrs.best_answer_ajax,
                callback:   this._bestAnswerResponse
            },
            'paginate': {
                name:       'paginateComments',
                endpoint:   this.data.attrs.paginate_comments_ajax,
                callback:   this._paginateCommentsResponse,
                dataType:   'text/html'
            },
            'pageWithComment': {
                name:      'pageWithComment',
                endpoint:  this.data.attrs.fetch_page_with_comment_ajax,
                callback:  this._paginateCommentsResponse,
                dataType:  'text/html'
            },
            'sortComment': {
                name:      'sortComment',
                endpoint:  this.data.attrs.sort_order_ajax,
                callback:  this._sortCommentsResponse,
                dataType:  'text/html'
            }
        });

        return (name) ? this.__events[name] : this.__events;
    },

    /**
     * Handler when a link's clicked that requires
     * social user info.
     */
    _needSocialInfoClick: function(e) {
        RightNow.Event.fire("evt_userInfoRequired");
        e.halt();
    },

    /**
     * Called when an action requiring user auth is clicked.
     * Fires an event that the LoginDialog widget is listening for.
     * @param  {Object} e Click event
     */
    _loginLinkClick: function(e) {
        e.halt();

        var loginLink = this.Y.one(this.baseSelector + '_Login');

        if (e.target.compareTo(loginLink)) {
            this.readingPosition.updatePath(0);
        }

        RightNow.Event.fire('evt_requireLogin', new RightNow.Event.EventObject(this, {
            data: {
                isSocialAction: true,
                title: RightNow.Interface.getMessage("PLEASE_LOG_CREATE_AN_ACCOUNT_CONTINUE_LBL")
            }
        }));
    },
    /**
     * Generic class toggler. Switches the
     * class (specified by a `data-toggle-parent`
     * attribute) on the immediate parent.
     * @see _toggleClass for more info
     * @param  {Object} e DOM event
     */
    _toggleParent: function(e) {
        this._swapClasses(e.target.get('parentNode'), e.target.getAttribute('data-toggle-parent'));
    },

    /**
     * Toggles / replaces the specified
     * class name(s).
     * @param  {Object} el      Y.Node
     * @param  {String} classes Single class name
     *                          or two class names
     *                          separated by a `|`
     */
    _swapClasses: function(el, classes) {
        classes = classes.split('|');

        if (classes.length === 1) {
            el.toggleClass(classes[0]);
            return;
        }
        if (!el.hasClass(classes[0])) {
            classes.reverse();
        }

        el.replaceClass(classes[0], classes[1]);
    },

    /**
     * Event handler to toggle marking an
     * answer as The Best.
     * Expected to be passed comment id as
     * the first parameter.
     * @param {Number} commentID Comment id for the action
     * @param {Object} target Target of event
     */
    _bestAnswerClick: function(commentID, target) {
        if (commentID) {
            target.set('disabled', true);
            
            var requestParams = {
                commentID: commentID,
                removeAnswer: target.ancestor().hasClass('rn_BestAnswerRemoval'),
                chosenByType: target.ancestor().hasClass('rn_UserTypeAuthor') ? "Author" : "Moderator"
            };
            
            if(this.data.js.f_tok){
                RightNow.Event.subscribe("evt_formTokenUpdate", RightNow.Widgets.onFormTokenUpdate, this);
                // Get a new f_tok value on each uploads
                RightNow.Event.fire("evt_formTokenRequest", new RightNow.Event.EventObject(this, {data:{formToken:this.data.js.f_tok}}));

                requestParams.f_tok = this.data.js.f_tok;
            }
            
            this._makeRequest(requestParams, this._events('bestAnswer'));
        }
    },

    /**
     * Event handler to submit comment pagination action.
     * @param {Object} e Target of event
     */
    _paginateCommentsClick: function(e) {
        e.halt();
        this._requestPage({sortOrder: this.data.attrs.comments_sort_order, pageID: parseInt(e.currentTarget.getAttribute('data-pageID'), 10) }, this._events('paginate'));
    },

    /**
     * Requests a page containing the specified
     * comment.
     * @param  {string} evt Event name
     * @param {array} args Contains a single object
     *                     {string} commentID Comment id
     */
    _requestPageWithComment: function(evt, args) {
        this._requestPage({ commentID: args[0].commentID }, this._events('pageWithComment'));
    },

    /**
     * Fetches a page of comments if the widget's comment
     * container isn't already loading.
     * @param  {object} requestParameters POST parameters to submit
     * @param  {object} eventDetails      Event options from #_events
     */
    _requestPage: function(requestParameters, eventDetails) {
        if (!this._toggleLoading(true)) {
            
            if(this.data.js.f_tok){
                RightNow.Event.subscribe("evt_formTokenUpdate", RightNow.Widgets.onFormTokenUpdate, this);
                // Get a new f_tok value on each uploads
                RightNow.Event.fire("evt_formTokenRequest", new RightNow.Event.EventObject(this, {data:{formToken:this.data.js.f_tok}}));

                requestParameters.f_tok = this.data.js.f_tok;
            }
            
            this._makeRequest(requestParameters, eventDetails);
        }
    },

    /**
     * Toggles the loading state on or off.
     * @param {bool} loading True if the loading state is to be enabled
     * @return {bool} The loading state when this function was called.
     */
    _toggleLoading: function(loading) {
        var commentListContainer = this.Y.one(this.baseSelector + ' .rn_Comments'),
            requestedLoading = typeof loading === 'undefined' || !loading ? false : true,
            isLoading = (commentListContainer && commentListContainer.hasClass('rn_Loading'));

        if (isLoading && !requestedLoading) {
            commentListContainer.removeClass('rn_Loading').get('parentNode').setAttribute('aria-busy', false);
        }
        else if (!isLoading && requestedLoading && commentListContainer) {
            commentListContainer.addClass('rn_Loading').get('parentNode').setAttribute('aria-busy', true);
        }

        return isLoading;
    },

    /*
     * Gets the current existing files in the edit mode
     * @param {Object} comment Y.Node comment container
     */
    _refreshFiles: function(comment) {
        if (this.data.attrs.use_file_attachments) {
            if(this.editor.form.one(' .rn_SocialFileAttachmentUpload').one(' .rn_ExistingFileAttachments')) {
                this.editor.form.one(' .rn_SocialFileAttachmentUpload').one(' .rn_ExistingFileAttachments').remove();
            }
            var fileDisplayInstanceID = RightNow.Text.getSubstringAfter(comment.one(' .rn_FileListDisplay').get('id'), 'rn_'),
                fileAttachments = JSON.stringify(RightNow.Widgets.getWidgetInstance(fileDisplayInstanceID)._currentAttachments);
            var eo = new RightNow.Event.EventObject(this, {data: {
                instanceID: RightNow.Text.getSubstringAfter(this.editor.form.one(' .rn_SocialFileAttachmentUpload').get('id'), 'rn_'),
                fileAttachments: fileAttachments
            }});
            RightNow.Event.fire('evt_editRefreshFileAttachments', eo);
        }
    },

    /**
     * Event handler to capture edit commit click.
     * Expected to be passed comment id as
     * the first parameter.
     * @param {Number} commentID Comment id for the action
     * @param {Object} target Target of event
     * @param {Object} comment Y.Node comment container
     */
    _commentEditDisplayClick: function(commentID, target, comment) {
        if (!commentID) return;

        var commentDivID = this.baseSelector + '_' + commentID,
            commentText = this.data.attrs.use_rich_text_input ? comment.one('.rn_CommentText').getHTML() : this.Y.one(commentDivID + ' span' + commentDivID + '_rawComment').getAttribute('data-rawCommentText');

        this.editor.editComment(comment, commentText);

        this._refreshFiles(comment);

        if (this.editor.isHidden()) {
            target.focus();
        }
    },

    /**
     * Event listener for editor's `move` event.
     * Resets the comment states according to
     * the action taken.
     * @param {string} evt Event name
     * @param {array} args Contains a single object:
     *                     - {String} mode       edit or reply
     *                     - {Object} prevParent Y.Node editor's
     *                             parent before
     *                             the move
     *                     - {Object} newParent  Y.Node editor's
     *                             new parent
     */
    _editorMoved: function(evt, args) {
        var details = args[0];
        if (details.mode === 'edit') {
            this._toggleComment(details.newParent, 'hide');
        }
        var noNewCommentMessage = this.Y.one(this.baseSelector + ' .rn_NoNewCommentMessage');
        if (noNewCommentMessage && noNewCommentMessage.hasClass('rn_Hidden')) {
           this._toggleNewCommentAction();
        }
        if (details.prevParent !== details.newParent) {
            this._toggleComment(details.prevParent, 'show');
        }
    },

    /**
     * Event listener for editor's `toggle` event.
     * @param {string} evt Event
     * @param {array} args Contains a single object:
     *                     - {String} mode   edit or reply
     *                     - {Object} parent Y.Node editor's parent
     */
    _editorToggled: function(evt, args) {
        var details = args[0];
        if (details.mode === 'edit') {
            this._toggleComment(details.parent, this.editor.isVisible() ? 'hide' : 'show');
        }
        this._toggleNewCommentAction();
    },

    /**
     * Toggles the comment components of the given node
     * @param  {Object} parent   Y.Node comment container
     *                         or child of comment container
     * @param  {String=} action hide or show
     */
    _toggleComment: function(parent, action) {
        var comment = this._getCommentContainer(parent),
            method = action === 'show' ? 'removeClass' : 'addClass';

        if (comment) {
            comment[method]('rn_Hidden');
        }
    },

    /**
     * Event listener for reply button click event.
     * @param {string} evt Event name
     * @param {array} args Contains a single object: click event
     */
    _commentReplyClick: function(evt, args) {
        var clickEvent = args[0];
        if(!this.data.js.displayName) {
            this._needSocialInfoClick(clickEvent);
        }

        var commentID = this._getCommentIDFromNode(clickEvent.currentTarget),
            commentReplies = this.Y.one(this.baseSelector + '_Replies_' + commentID),
            replyToLocation = commentReplies ? commentReplies : this._getCommentContainer(clickEvent.currentTarget);

        this.editor.replyToComment(replyToLocation);
        
        if(this.editor.form.one('.rn_ExistingFileAttachments')) {
            this.editor.form.one('.rn_ExistingFileAttachments').remove();            
        }
        if (this.data.attrs.use_file_attachments) {
            var instanceId = RightNow.Text.getSubstringAfter(this.newCommentEditor.form.one('.rn_SocialFileAttachmentUpload').get('id'), 'rn_');
        }
        //evt_newCommentRefresh
        var eo = new RightNow.Event.EventObject(this, {data: {
            instanceID:  instanceId,
        }});
        RightNow.Event.fire('evt_newCommentRefresh', eo);

        if (this.editor.isHidden()) {
            clickEvent.currentTarget.focus();
        }
    },

    /**
     * Called when the user clicks on view original link in a quoted comment.
     * It navigates to the particular page and posiion where the original comment is present.
     * @param {Object} e Click event
     */
    _goToOriginalComment: function(e) {
        e.preventDefault();
        var href = e.currentTarget.get('href'),
        commentUrlPart = "comment/",
        commentId = href.substr(href.indexOf(commentUrlPart) + commentUrlPart.length)
        this.readingPosition.jumpToOrLoadComment(commentId);
    },

    /**
     * Event listener for quote button click event.
     * @param {string} evt Event name
     * @param {array} args Contains a single object: click event
     */
    _commentQuoteClick: function(evt, args) {
        if(!this.data.attrs.use_rich_text_input || !this.data.attrs.quote_comment_enabled)
            return;
        var clickEvent = args[0];
        if(!this.data.js.displayName) {
            this._needSocialInfoClick(clickEvent);
        }
        var commentId = this._getCommentIDFromNode(clickEvent.currentTarget),
        comment = this.Y.one(this.baseSelector + '_' + commentId),
        newCommentDiv = this.Y.one('.rn_PostNewComment'),
        commentAuthorName = comment.one('.rn_DisplayName').getHTML();
        if(this.data.attrs.quote_comment_truncate_size === 0) {
            var clonedCommentTextNode = comment.one('.rn_CommentText').cloneNode(true),
            originalCommentPermaLink = clonedCommentTextNode.one('.rn_ViewOriginalLink');
            if(originalCommentPermaLink)
                originalCommentPermaLink.remove(true);
            var commentText = clonedCommentTextNode.getHTML();
        }
        else
            var commentText = this._getCommentTextToQuote(comment.one('.rn_CommentText'), this.data.attrs.quote_comment_truncate_size);
        if(newCommentDiv) {
            if(!newCommentDiv.hasClass('rn_Hidden'))
                this.newCommentEditor.setQuotedContent(commentAuthorName,commentText, commentId);
            else
                this.editor.setQuotedContent(commentAuthorName,commentText, commentId);
        }
    },

    /**
     * Process comment for quoting
     * @param {object} comment
     * @return {string} comment text to quote
     */
     _getCommentTextToQuote: function(element, characterLimit) {
        var commentText = '',
        context = this, 
        classNamesOfTagsToIgNore = ["rn_ViewOriginalLink"];
        element.get('children').each(function(child){
            if(characterLimit == 0) 
                return commentText;
            
            var obj = context.processNodeForTruncation(child, characterLimit, classNamesOfTagsToIgNore, context);
            if(obj.processedNode)
                commentText += obj.processedNode.get('outerHTML');
            characterLimit = obj.characterLimit;
        }); 
        return commentText;
     },
     
    /**
     * Process node for quoting
     * @param {object} currentNode
     * @param int characterLimit
     * @param string classNamesOfTagsToIgnore
     * @param {object} context
     * @return {object} 
     */
     processNodeForTruncation: function(currentNode, characterLimit, classNamesOfTagsToIgnore, context) {
        if(currentNode.hasClass(classNamesOfTagsToIgnore))
            return { processedNode: null, characterLimit: characterLimit};

        var clonedNode = currentNode.cloneNode(false);
        if(currentNode.get('children').size() === 0) {
            truncatedText = context.getTruncatedTextAndCharLimit(currentNode.get('innerHTML'), characterLimit);
            clonedNode.set('innerHTML', truncatedText.text);
            return { processedNode: clonedNode, characterLimit: truncatedText.characterLimit };
        }
        currentNode.get('childNodes').each(function(childNode) {
            if(characterLimit === 0)
                return { processedNode: clonedNode, characterLimit : characterLimit };
            if(childNode.get('innerHTML') === undefined) {
                var text = childNode.get('data'),
                truncatedText = context.getTruncatedTextAndCharLimit(text, characterLimit);
                clonedNode.set('innerHTML', clonedNode.get('innerHTML') + truncatedText.text);
                characterLimit = truncatedText.characterLimit;
            }
            else {
                var obj = context.processNodeForTruncation(childNode, characterLimit, classNamesOfTagsToIgnore, context);
                if(obj.processedNode)
                    clonedNode.set('innerHTML', clonedNode.get('innerHTML') + obj.processedNode.get('outerHTML'));
                characterLimit = obj.characterLimit;
            }
        });
        return { processedNode: clonedNode, characterLimit : characterLimit };
     },

    /**
     * Truncate comment text
     * @param {string} text
     * @param {int} characterLimit
     * @return {object}
     */
     getTruncatedTextAndCharLimit: function(text, characterLimit) {
        if(text === null)
            return null;
        if(text.length > characterLimit) {
            text = text.substr(0,characterLimit) + '...';
            characterLimit = 0;
        }
        else {
            characterLimit -= text.length;
        }
        return { text: text, characterLimit: characterLimit };
     },

    _escapeMapping: {
        // Escaping backtick means that inline `code blocks` aren't supported.
        // I'm told that we don't care.
        '`': '&#x60;',
        // Escaping gt & lt means that auto links and quote blocks aren't supported.
        // I'm told that we don't care.
        '<': '&lt;',
        '>': '&gt;',
        '&': '&amp;',
        '"': '&quot;',
        "'": '&#x27;',
        '*': '&#x2A;',
        // Don't escape backslash, since it escapes benign sequences like 'http://'
        // '/': '&#x2F;',
    },

    _escape: function(string) {
        var mapping = this._escapeMapping;

        return (string + '').replace(/[&<>"'`]/g, function(match) {
            return mapping[match];
        });
    },

    /**
     * Event listener for new comment editor's
     * `submit` event.
     * @param {string} evt Event name
     * @param {array} args Contains a single object
     *                     - {Object} value Editor's current value
     *                        contains `text` value
     */
    _newCommentSubmitted: function(evt, args) {
        var value = this.data.attrs.use_rich_text_input ? args[0].value.text : args[0].value;
        if (this.data.attrs.use_file_attachments) {
            var fileUploadInstanceID = RightNow.Text.getSubstringAfter(this.newCommentEditor.form.one('.rn_SocialFileAttachmentUpload').get('id'), 'rn_'),
                fileAttachments = {};
            fileAttachments.newFiles = RightNow.Widgets.getWidgetInstance(fileUploadInstanceID)._attachments;
            fileAttachments.removedFiles = null;
        }

        var editor = this.newCommentEditor._getCommentEditorWidget();
        
        this._requestPage({
            sortOrder: this.data.attrs.comments_sort_order,
            commentBody: value,
            required : editor.data.attrs.required,
            fileAttachments: JSON.stringify(fileAttachments)
        }, this._events('new'));
    },

    /**
     * Determines if the HTML response for a comment
     * operation is an error message.
     * @param  {string}  response HTML response
     * @return {boolean}          True if the response
     *                                 is an error message
     */
    _isCommentErrorResponse: function(response) {
        return this.Y.Node.create(response).get('nodeName') === '#text';
    },

    /**
     * Handles the AJAX response for `new_comment_endpoint`.
     * @param  {String} response HTML content
     * @param {object} origResponseObj The original response object that
     *                                 initiated the request.
     */
    _newCommentResponse: function(response, origResponseObj) {
        this._paginateCommentsResponse(response, origResponseObj);

        if (this.data.attrs.label_new_comment_banner) {
            var newComments = this.Y.one(this.baseSelector + '_Comments .rn_Comments');
            if (newComments) {
                if(this.data.attrs.comments_sort_order === 'DESC') {
                    RightNow.UI.displayBanner(this.data.attrs.label_new_comment_banner, {focusElement: newComments.one(' > .rn_CommentContainer:first-child')});
                }
                else {
                    RightNow.UI.displayBanner(this.data.attrs.label_new_comment_banner, {focusElement: newComments.one(' > .rn_CommentContainer:last-child')}); 
                }
            }
        }

        this.newCommentEditor.reload();
        if (this.data.attrs.use_file_attachments) {
            var instanceId = RightNow.Text.getSubstringAfter(this.newCommentEditor.form.one('.rn_SocialFileAttachmentUpload').get('id'), 'rn_');
        }
        var eo = new RightNow.Event.EventObject(this, {data: {
                instanceID:  instanceId,
            }
        });
        RightNow.Event.fire('evt_newCommentRefresh', eo);
    },

    /**
     * Event listener for editor's `submit` event.
     * @param {string} evt Event name
     * @param {array} args Contains a single object
     *                     - {String} mode              edit or comment
     *                     - {Object} value             Editor's current value
     *                                    contains `text` values
     *                     - {Object} comment Comment the editor is submitting
     *                                    the event for
     */
    _editorSubmitted: function(evt, args) {
        var details = args[0];
        var value = this.data.attrs.use_rich_text_input ? details.value.text : details.value;
        if (this.data.attrs.use_file_attachments) {
            var fileUploadInstanceID = RightNow.Text.getSubstringAfter(this.editor.form.one(' .rn_SocialFileAttachmentUpload').get('id'), 'rn_');
            var fileAttachmentValue = JSON.stringify(RightNow.Widgets.getWidgetInstance(fileUploadInstanceID)._updateFileAttachments);
        }
        var editor = this.editor._getCommentEditorWidget();
        
        var requestParams = {
            commentID:       this._getCommentIDFromNode(details.comment),
            commentBody:     value,
            required : editor.data.attrs.required,
            fileAttachments: fileAttachmentValue
        };
        
        if(this.data.js.f_tok){
            RightNow.Event.subscribe("evt_formTokenUpdate", RightNow.Widgets.onFormTokenUpdate, this);
            // Get a new f_tok value on each uploads
            RightNow.Event.fire("evt_formTokenRequest", new RightNow.Event.EventObject(this, {data:{formToken:this.data.js.f_tok}}));

            requestParams.f_tok = this.data.js.f_tok;
        }
        
        this._makeRequest(requestParams, this._events(details.mode));
    },

    /**
     * Handles the AJAX response for `edit_comment_endpoint`.
     * @param {object} response JSON-parsed response from the server
     * @param {object} originalEventObj `eventObj`
     */
    _editCommentResponse: function(response, originalEventObj) {
        var commentID = this.baseSelector + '_' + originalEventObj.data.commentID,
            comment = this.Y.one(commentID),
            commentText = comment.one('.rn_CommentText');

        this.editor.hide().resetForm();
        this._toggleComment(comment, 'show');
        if(this.data.attrs.use_rich_text_input) {
            commentText.setHTML(response.comment.result.Body);
        }
        else {
            var rawInputText = this._escape(response.comment.result.Body),
                html = (new Markdown.Converter()).makeHtml(rawInputText),
                output = this.Y.Node.create('<div>' + html + '</div>').getHTML();

            // Preserve raw text to be used for editing markdown text
            this.Y.one(commentID + ' span.rn_rawCommentText').setAttribute('data-rawcommenttext', rawInputText);

            // Set comment html
            commentText.setHTML(output);
        }

        if (this.data.attrs.use_file_attachments) {
            var instanceId = RightNow.Text.getSubstringAfter(comment.one(' .rn_FileListDisplay').get('id'), 'rn_');
        }

        //fire event to filelistdisplay to render updated file list
        var eo = new RightNow.Event.EventObject(this, {data: {
            instanceID:  instanceId,
            fileAttachments: response.fileAttachments
        }});
        RightNow.Event.fire('evt_editSuccessFileRefresh', eo);

        this._refreshUpdatedTime(commentID, response.formattedUpdatedTime);

        this._toggleNewCommentAction();

        if(this.data.attrs.label_edit_comment_banner) {
            RightNow.UI.displayBanner(this.data.attrs.label_edit_comment_banner, {
                focusElement: this.Y.one('#rn_' + originalEventObj.w_id + '_' +
                    originalEventObj.data.commentID + ' .rn_EditCommentAction')
            });
        }
        this._checkBestAnswerComment(originalEventObj.data.commentID);
    },

    /**
     * Refreshes the updated time for a comment.
     * @param {int} commentID  ID of the comment whose updated time is to be refreshed
     * @param {array} formattedTime  Contains the two different formats of the updated time to be displayed
     */
    _refreshUpdatedTime: function(commentID, formattedTime) {
        var updatedTimeElem = this.Y.one(commentID + ' time[itemprop=dateUpdated]');

        if(!updatedTimeElem) {
            var timestampElem = this.Y.one(commentID + ' .rn_CommentTimestamp');
            var labelUpdatedTime = this.data.attrs.label_updated_time.replace('%s', '<time itemprop="dateUpdated" datetime="' + formattedTime[1] + '" >' + formattedTime[0] + '</time>');
            timestampElem.setHTML(timestampElem.getHTML() + labelUpdatedTime);
        }
        else {
            updatedTimeElem.setAttribute("datetime", formattedTime[1]);
            updatedTimeElem.setHTML(formattedTime[0]);
        }
    },

    /**
     * Handles the AJAX response for `reply_to_comment_ajax`
     * @param  {Object} response         JSON-parsed response from the server
     * @param  {Object} originalEventObj event object for the request
     */
    _replyToCommentResponse: function(response, originalEventObj) {
        this.editor.hide().reload();

        var comment = this.Y.one(this.baseSelector + '_' + originalEventObj.data.commentID),
            replies = this.Y.one(this.baseSelector + '_Replies_' + originalEventObj.data.commentID),
            label;

        if (!replies) {
            replies = this.Y.Node.create(new EJS({text: this.getStatic().templates.commentReplies})
                .render({
                    baseDomID: this.baseDomID,
                    commentID: originalEventObj.data.commentID,
                    labelReplies: this.data.attrs.label_replies
                }));
            comment.insert(replies, 'after');
        }

        if (replies) {
            RightNow.UI.show(replies.append(response));
            if (!this.Y.Lang.isObject(response)) {
                RightNow.Widgets.instantiateWidgetsFoundInContent(response);
            }
            this.readingPosition.fire('refresh');
        }

        if (label = this.data.attrs.label_replied_banner) {
            var children = replies.get('childNodes');
            RightNow.UI.displayBanner(label, {focusElement: (children && children.size() > 2) ? children.slice(-1).item(0) : comment});
        }

        this._toggleNewCommentAction();
        if(this.newCommentEditor) {
            // move the roving editor back down to the new comment area so it
            // isn't wiped out during the paginate comments operation
            this.newCommentEditor.reload();
            this.newCommentEditor.form.insert(this.editor.form, "after");
        }
    },

    /**
     * Event handler to submit comment delete action.
     * @param {Object} e Event
     */
    _deleteCommentConfirm: function(e) {
        var commentId = this._getCommentIDFromNode(e.target.ancestor('form').previous());
        //checking for the best answer possibility
        var commentMarkedAsBestAnswer = (this.Y.one(this.baseSelector + '_' + commentId + ".rn_BestAnswer")) ? true : false;
        var bestReplyPresentForComment = (this.Y.one(this.baseSelector + '_Replies_' + commentId + " .rn_BestAnswer")) ? true : false;

        var confirmElement = this.Y.Node.create('<p>')
            .addClass('rn_QuestionCommentsDeleteDialog');

        if(commentMarkedAsBestAnswer && bestReplyPresentForComment) {
            confirmElement.set('innerHTML', this.data.attrs.comment_and_reply_marked_as_best_answer_delete_confirm);
        }
        else if(commentMarkedAsBestAnswer) {
            confirmElement.set('innerHTML', this.data.attrs.comment_marked_as_best_answer_delete_confirm);
        }
        else if(bestReplyPresentForComment) {
            confirmElement.set('innerHTML', this.data.attrs.comment_reply_marked_as_best_answer_delete_confirm);
        }
        else {
            confirmElement.set('innerHTML', this.data.attrs.label_delete_confirm);
        }

        this._deleteDialog = RightNow.UI.Dialog.actionDialog(this.data.attrs.label_delete_confirm_title, confirmElement, {buttons: [
            { text: this.data.attrs.label_confirm_delete_button, handler: {fn: function(){
                this._deleteComment(this._getCommentIDFromNode(e.target.ancestor('form').previous()));
            }, scope: this}, isDefault: true},
            { text: this.data.attrs.label_cancel_delete_button, handler: {fn: function(){
                this._deleteDialog.hide();
            }, scope: this}, isDefault: false}
        ]});
        this._deleteDialog.show();
    },

     /**
     * Event handler to sort comments.
     * @param {Object} e Target of event
     */
    _sortComment: function(e) {
        var button = e.target,
            commentSortOrder = (button.getAttribute('data-sort') === "ASC") ? 'DESC' : 'ASC',
            currentPage = parseInt(this.Y.all('#rn_' + this.instanceID + ' .rn_CurrentPage').getHTML()[0], 10),
            pageID = parseInt(currentPage, 10);
        this._requestPage({sortOrder: commentSortOrder, pageID: (!isNaN(currentPage) ? pageID : null)}, this._events('sortComment'));
    },

    /**
     * Deletes the comment specified by 'id'
     * @param {Int} commentID Comment id to delete
     */
    _deleteComment: function(commentID) {
        this._deleteDialog.destroy();
        this.editor.hide().resetForm();
        
        var requestParams = {commentID: commentID};
        
        if(this.data.js.f_tok){
            RightNow.Event.subscribe("evt_formTokenUpdate", RightNow.Widgets.onFormTokenUpdate, this);
            // Get a new f_tok value on each ajax request
            RightNow.Event.fire("evt_formTokenRequest", new RightNow.Event.EventObject(this, {data:{formToken:this.data.js.f_tok}}));

            requestParams.f_tok = this.data.js.f_tok;
        }
        
        this._makeRequest(requestParams, this._events('delete'));
        location.reload();
    },

    /**
     * Handles the AJAX response for `delete_comment_endpoint`.
     * @param {object} response JSON-parsed response from the server
     * @param {object} originalEventObj `eventObj`
     */
    _deleteCommentResponse: function(response, originalEventObj) {
        var comment = this.Y.one(this.baseSelector + '_' + originalEventObj.data.commentID),
            currentPage = parseInt(this.Y.all('#rn_' + this.instanceID + ' .rn_CurrentPage').getHTML()[0], 10);
        // If there is pagination on the page
        if(!isNaN(currentPage)) {
            // If there are more comments on the page then reload the same page
            if(this._hasMoreComments(comment) || this._isAReply(comment)) {
                this._requestPage({ pageID: parseInt(currentPage, 10)}, this._events('paginate'));
            }
            // If it was the last comment on the page then load previous/first page
            else {
                this._requestPage({ pageID: Math.max(currentPage - 1, 1)}, this._events('paginate'));
            }
        }
        // No pagination
        else {
            this._requestPage({ pageID: 1 }, this._events('pageWithComment'));
        }

        RightNow.UI.displayBanner(this.data.attrs.label_delete_comment_banner, {baseClass: this.baseSelector + ' .rn_CommentText'});
        this._toggleNewCommentAction();
        if(this.newCommentEditor) {
            this.newCommentEditor.reload();
            this.newCommentEditor.form.insert(this.editor.form, "after");
        }
        this._checkBestAnswerComment(originalEventObj.data.commentID);
    },

    /**
     * Determines if there are more comments on the page.
     * @param {object} comment Deleted comment object
     * @return {bool}          True if another comment was found,
                               False otherwise
     */
    _hasMoreComments: function(comment) {
        return comment.previous('div .rn_CommentContainer') || comment.next('div .rn_CommentContainer');
    },

    /**
     * Determines if a comment is a reply to a parent comment.
     * @param {object} comment Deleted comment object
     * @return {bool}          True if comment was a reply,
                               False otherwise
     */
    _isAReply: function(comment) {
        return comment.ancestor('.rn_Replies') !== null;
    },

    /**
     * Handles the AJAX response for `best_answer_endpoint`.
     * @param {object} bestAnswers JSON-parsed response from the server
     * @param {object} originalEventObj `eventObj`
     */
    _bestAnswerResponse: function(bestAnswers, originalEventObj) {
        this._updateBestAnswerButtons(originalEventObj.data.commentID, originalEventObj.data.chosenByType);
        this._refreshBestAnswerBanner(bestAnswers);
    },

    /**
     * Handles the AJAX response for `paginate_comments_endpoint`.
     * @param {object} response JSON-parsed response from the server
     * @param {object} originalEventObj Original event object that
     *                                  initiated the request
     */
    _paginateCommentsResponse: function(response, originalEventObj) {
        this.Y.one(this.baseSelector + '_Comments').setHTML(response).removeAttribute('aria-busy');
        this.readingPosition.fire('refresh', { commentID: originalEventObj.data.commentID });
        var newCommentEditor = this.Y.one(this.baseSelector + '_NewComment');
        if(newCommentEditor && newCommentEditor.hasClass('rn_Hidden')) {
            this._toggleNewCommentAction();
        }
        RightNow.Widgets.instantiateWidgetsFoundInContent(response);
        if(RightNow.Url.getParameter('comment')) {
            this.readingPosition.removePath();
        }
        this.readingPosition.replaceHistoryState(RightNow.Url.addParameter(window.location.pathname, 'page', originalEventObj.data.pageID));
    },

    /**
     * Handles the AJAX response for `sortComment_endpoint`.
     * @param {object} response JSON-parsed response from the server
     * @param {object} originalEventObj Original event object that
     *                                  initiated the request
     */
    _sortCommentsResponse: function(response, originalEventObj) {
        var newButton = this.Y.one(this.baseSelector + " .rn_SortOrder .rn_NewestButton");
        var oldButton = this.Y.one(this.baseSelector + " .rn_SortOrder .rn_OldestButton");

        if(originalEventObj.data.sortOrder === "ASC") {
            newButton.removeClass('rn_Disabled');
            oldButton.addClass('rn_Disabled');
            this.data.attrs.comments_sort_order = 'ASC';
        }
        else {
            oldButton.removeClass('rn_Disabled');
            newButton.addClass('rn_Disabled');
            this.data.attrs.comments_sort_order = 'DESC';
        }

        if(response) {
            this.Y.one(this.baseSelector + '_Comments').setHTML(response).removeAttribute('aria-busy');
            this.readingPosition.fire('refresh', { commentID: originalEventObj.data.commentID });
            var newCommentEditor = this.Y.one(this.baseSelector + '_NewComment');
            if(newCommentEditor && newCommentEditor.hasClass('rn_Hidden')) {
                this._toggleNewCommentAction();
            }
            RightNow.Widgets.instantiateWidgetsFoundInContent(response);
            if(RightNow.Url.getParameter('comment')) {
                this.readingPosition.removePath();
            }
            this.readingPosition.replaceHistoryState(RightNow.Url.addParameter(window.location.pathname, 'page', originalEventObj.data.pageID));
        }
    },

    /**
     * For the supplied best answers, updates their
     * comments in the DOM.
     * @param {array} bestAnswers Each object must have a
     *                            commentID and label property
     */
    _refreshBestAnswerBanner: function(bestAnswers) {
        this.Y.one(this.baseSelector).all('.rn_CommentContainer.rn_BestAnswer').each(function(comment) {
            comment.removeClass('rn_BestAnswer');
            comment.all('.rn_BestAnswerInfo').setHTML('');
        });

        this.Y.Object.each(bestAnswers, function(bestAnswer) {
            var comment = this.Y.one(this.baseSelector + '_' + bestAnswer.commentID);

            if (comment) {
                comment.addClass('rn_BestAnswer')
                       .one('.rn_BestAnswerInfo').setHTML(this._renderBestAnswerLabel(bestAnswer));
            }
        }, this);
    },

    /**
     * Update best answer buttons to reflect current status
     * @param {Number} updatedCommentID ID of the comment that was marked/unmarked best answer
     * @param {String} updatedAnswerUser Type of user that updated a best answer - either Author or Moderator
     */
    _updateBestAnswerButtons: function(updatedCommentID, updatedAnswerUser) {
        var buttonToUpdate = this.Y.one(this.baseSelector + '_' + updatedCommentID + ' .rn_UserType' + updatedAnswerUser);
        var newButtonType = buttonToUpdate.hasClass('rn_BestAnswerAssignment') ? "remove" : "pick";

        if(newButtonType === "remove") {
            var bestAnswerButton;
            this.Y.one(this.baseSelector).all('.rn_CommentContainer.rn_BestAnswer').each(function(comment) {
                if(bestAnswerButton = comment.one('.rn_BestAnswerRemoval.rn_UserType' + updatedAnswerUser)) {
                    bestAnswerButton.replace(this.Y.Node.create(
                        this._renderBestAnswerButton({
                            commentID: comment.getAttribute('data-commentid')
                        }, true, 'pick', updatedAnswerUser)));
                }
            }, this);
        }

        buttonToUpdate.replace(this.Y.Node.create(
            this._renderBestAnswerButton({
                commentID: updatedCommentID
            }, true, newButtonType, updatedAnswerUser)));
    },

    /**
     * Renders the best answer button template.
     * @param {Object} bestAnswer Object representing the Best Answer object or object with commentID attribute
     * @param {Boolean} displayBestAnswerButton Whether to display the button
     * @param {String} buttonType Type of button to display, either pick or remove
     * @param {String} userType Type of user for the button, either moderator, author, or both
     * @return {String} rendered view
     */
    _renderBestAnswerButton: function (bestAnswer, displayBestAnswerButton, buttonType, userType) {
        this._bestAnswerButton || (this._bestAnswerButton = new EJS({text: this.getStatic().templates.bestAnswerButton}));

        var viewArgs = {
            displayButton: (typeof displayBestAnswerButton !== 'undefined') ? displayBestAnswerButton : !!userType,
            buttonType: (typeof buttonType !== 'undefined') ? buttonType : 'pick',
            userType: userType,
            labels: this.data.attrs,
            bestAnswer: bestAnswer
        };

        return this._bestAnswerButton.render(viewArgs);
    },

    /**
     * Renders the best answer label template.
     * @param {Object} bestAnswer Object representing the Best Answer object or object with commentID attribute
     * @return {String} rendered view
     */
    _renderBestAnswerLabel: function (bestAnswer) {
        this._bestAnswerLabel || (this._bestAnswerLabel = new EJS({text: this.getStatic().templates.bestAnswerLabel}));

        return this._bestAnswerLabel.render({
            label: bestAnswer.label,
            attrs: this.data.attrs
        });
    },

    /**
     * Intended to wrap several handlers that need to:
     * 1. Halt the event.
     * 2. Extract the comment id from the clicked on
     *    element.
     * @param  {Function} handler Handler function
     * @return {Function}         Function to add as a delegate listener;
     *                                     When invoked, calls handler with:
     *                                     - {Number} comment id,
     *                                     - {Object} current target
     *                                     - {Object} comment container
     *                                     - {Object} event
     */
    _extractCommentIDFromEvent: function (handler) {
        var self = this;

        return function (e) {
            e.preventDefault();

            handler.call(self, self._getCommentIDFromNode(e.currentTarget),
                e.currentTarget, self._getCommentContainer(e.currentTarget), e);
        };
    },

    /**
     * Gets the comment container ancestor for
     * the given Node.
     * @param  {Object} node Y.Node
     * @param {Boolean=} excludeNode Whether to
     *                               exclude node
     *                               from consideration;
     *                               defaults to false
     * @return {Object|null}      Ancestor
     *         (or node if node is the comment
     *         container) or null if not found
     */
    _getCommentContainer: function(node, excludeNode) {
        var className = 'rn_CommentContainer';

        return (!excludeNode && node.hasClass(className)) ? node : node.ancestor('.' + className);
    },

    /**
     * Grabs the comment id out of the node's data attribute or the element's
     * container comment element.
     * @param  {Object} node Y.Node
     * @return {Number}      0 if a comment id wasn't found
     */
    _getCommentIDFromNode: function(node) {
        var idAttr = 'data-commentid',
            commentID;

        if (!node.hasAttribute(idAttr)) node = this._getCommentContainer(node);

        commentID = parseInt(node ? node.getAttribute(idAttr) : null, 10);

        return isNaN(commentID) ? 0 : commentID;
    },

    /**
     * Subscribes the given events via event delegation
     * on the widget's root element.
     * @param  {Array} eventMap Each item should contain:
     *                          ['dom event', 'selector', handler function]
     */
    _subscribe: function(eventMap) {
        var el = this.Y.one(this.baseSelector);
        if (el) {
            this.Y.Array.each(eventMap, function(eventInfo) {
                el.delegate(eventInfo[0], eventInfo[2], eventInfo[1], this);
            }, this);
        }
    },

    /**
     * Toggles the display of the new comment link / form and the
     * message that says why a new comment can't be performed. This is
     * so that a new comment isn't attempted to be submitted while in the
     * process of doing some other action.
     */
    _toggleNewCommentAction: function() {
        this.Y.one(this.baseSelector)
            .all('.rn_PostNewComment,.rn_NoNewCommentMessage')
            .toggleClass('rn_Hidden');
    },

    /**
     * Fires an event and makes an ajax request if the event isn't cancelled.
     * @param  {Object} requestData parameters to post to the server
     * @param  {Object} event       Event info from #_events
     */
    _makeRequest: function(requestData, event) {
        var eventObject = new RightNow.Event.EventObject(this, {
            data: this.Y.mix({
                w_id:       this.data.info.w_id,
                questionID: this.data.js.questionID
            }, requestData)
        }),
        eventName = 'evt_' + event.name;

        if (RightNow.Event.fire(eventName + 'Request', eventObject)) {
            var requestOptions = {
                scope:          this,
                json:           true,
                type:           'POST',
                data:           eventObject,
                headers:        { 'Accept': 'application/json' },
                successHandler: this._ajaxResponse(eventName, event.callback)
            };
            if (event.dataType) {
                requestOptions.json = false;
                requestOptions.headers = { 'Accept': event.dataType };
            }

            RightNow.Ajax.makeRequest(event.endpoint, eventObject.data, requestOptions);
        }
    },

    /**
     * Creates a handler for ajax responses.
     * @param  {String}   eventName event to fire when
     *                              a response comes in
     * @param  {Function} callback  Widget method to call
     *                              if there's no problems
     * @return {Function}             success handler
     */
    _ajaxResponse: function(eventName, callback) {
        return function(response, originalEventObj) {
            if (RightNow.Event.fire(eventName + 'Response', response, originalEventObj)) {
                var error = ('responseText' in response) ?
                    this._isCommentErrorResponse(response.responseText) :
                    response.errors;

                if (error) {
                    if(eventName.indexOf('bestAnswer') > -1) {
                        var bestAnswerLink = this.Y.one(this.baseSelector + '_' + originalEventObj.data.commentID + ' .rn_BestAnswerActions .rn_UserType' + originalEventObj.data.chosenByType + ' button');
                        if(bestAnswerLink !== null) {
                            bestAnswerLink.set('disabled', false);
                        }
                    }
                    (eventName.indexOf('newComment') > -1 ? this.newCommentEditor : this.editor).resetForm();
                    if(!RightNow.Ajax.indicatesSocialUserError(response)) {
                        if (response.errors && response.errors[0].externalMessage) {
                            message = response.errors[0].externalMessage;
                        }
                        else {
                            message = RightNow.Interface.getMessage("UNABLE_SAVE_YOUR_COMMENT_AT_THIS_TIME_LBL");
                        }

                        RightNow.UI.displayBanner(message, { type: 'ERROR' });
                    }
                    this._toggleLoading(false);
                    if(eventName.indexOf('deleteComment') > -1) {
                        this._toggleNewCommentAction();
                    }
                }
                else {
                    this.moveRovingEditorToOrigin();
                    callback.call(this, response.responseText || response, originalEventObj);
                }
            }
        };
    },

    /**
     * Moving roving editor to its original postion if it's moved
     */
    moveRovingEditorToOrigin: function() {
        var newCommentForm = this.Y.one(this.baseSelector + '_CommentForm');
        if(newCommentForm) {
            var isSibling = this.editor.isSiblingOfNode(newCommentForm);
            if(!isSibling) {
                var editor = this.editor._getCommentEditorWidget();
                editor.destroy();
                this.newCommentEditor.form.insert(this.editor.form, "after");
                this.editor.hide().resetForm();
            }
        }
    },
    /**
     * check and fire event to refresh best answers
     * @param {Number} commentID
     */
    _checkBestAnswerComment: function(commentID) {
        if(this.Y.one('.rn_BestAnswerList .rn_ShowAllCommentText[data-commentid=' + commentID + ']')) {
            RightNow.Event.fire('evt_refreshBestAnswer', new RightNow.Event.EventObject(this, {data: {commentID: commentID}}));
        }
    }
});

/**
 * Responsible for handling comment permalinking.
 * As the viewport is scrolled, the topmost comment's id is set in the URL.
 * When the page is loaded, if the URL contains a comment id then the specified
 * comment is scrolled to.
 * If the specified comment id isn't found on the page, the widget issues a
 * `requestPageWithComment` event.
 *
 * Local (intra-widget) interaction with this module occurs thru events:
 *
 * * `refresh`: A new set of comments was loaded. Notify by triggering this event.
 *   If an optional commentID appears in the event object, then that comment is
 *   scrolled to.
 *
 * Global interaction with this module occurs thru the global `evt_jumpToComment` event.
 *
 * This module emits local events:
 *
 * * `requestPageWithComment`: Requests that a page containing the specified comment
 *   id is loaded.
 */
RightNow.Widgets.QuestionComments.readingPosition = RightNow.EventProvider.extend({
    overrides: {
        constructor: function () {
            this.parent();

            // Current comment id that's scrolled to.
            this.current = null;
            // Y.NodeList of comments on the page.
            this.comments = null;
            // String selector of comments on the page.
            this.selector = null;

            this.jumpToOrLoadComment(this.getCommentParam());

            this.Y.one(window).on('scroll', this.Y.throttle(this.Y.bind(this.guardedScrollHandler, this), 100));

            this.on('refresh', function (evt, args) {
                this.refreshComments();

                if (args[0] && args[0].commentID) {
                    this.jumpToComment(args[0].commentID);
                }
            }, this);

            RightNow.Event.on('evt_jumpToComment', function (evt, args) {
                this.jumpToOrLoadComment(args[0].data.commentID);
            }, this);
        }
    },

    /**
     * Sets the comment selector for this module to watch.
     * @param {string} selector Selector that is expected
     *                          to yield a NodeList
     */
    setCommentsSelector: function (selector) {
        this.selector = selector;

        if (!this.comments) {
            this.refreshComments();
        }
    },

    /**
     * Refreshes the comments property with the updated
     * NodeList of the current selector.
     */
    refreshComments: function () {
        this.comments = this.Y.all(this.selector);
    },

    /**
     * Executes the onScroll method if not programmatically scrolling
     * and if the required comments NodeList is present.
     */
    guardedScrollHandler: function () {
        if (!this._restoringPosition && this.comments) {
            this.onScroll();
        }
    },

    /**
     * Callback for window scroll event.
     */
    onScroll: function () {
        this.setComment(this.getTopMostComment(this.comments, Math.abs(document.documentElement.scrollTop || document.body.scrollTop)));
    },

    /**
     * Returns the top most comment relative to the
     * given bodyTop position.
     * @param  {object} nodeList Y.NodeList of comments
     * @param  {number} bodyTop  Position (scrollTop attribute) of document.documentElement or document.body
     * @return {object|null}     Y.Node comment or null if none
     */
    getTopMostComment: function (nodeList, bodyTop) {
        var current;

        nodeList.some(function (node, index) {
            var nodeTop = node.getY(),
                midwayThruNode = nodeTop + (node.get('offsetHeight') / 2);

            if (bodyTop <= midwayThruNode) {
                if (index === 0 && (nodeTop - bodyTop > 200)) {
                    // Over 200px above the topmost comment on the page.
                    // → Remove the comment from the URL.
                    this.removeComment();
                }
                else {
                    current = node.getAttribute('data-commentid');
                }

                return true;
            }
        }, this);

        return current;
    },

    /**
     * Resets the `current` attribute
     * and removes the comment id from the URL
     */
    removeComment: function () {
        if (this.current) {
            this.current = null;
            this.removePath();
        }
    },

    /**
     * Updates the `current` attribute
     * and updates the comment id in the URL
     * @param {string} commentID Current comment id
     */
    setComment: function (commentID) {
        if (commentID && commentID !== this.current) {
            this.current = commentID;
            this.updatePath(commentID);
        }
    },

    /**
     * Updates the comment ID in the URL
     * @param  {string} commentID Comment id
     */
    updatePath: function (commentID) {
        this.replaceHistoryState(this.replacePath(commentID));
    },

    /**
     * Removes the comment id and url param from the URL.
     */
    removePath: function () {
        this.replaceHistoryState(RightNow.Url.deleteParameter(window.location.pathname, 'comment'));
    },

    /**
     * Replaces the history state, updating the browser url
     * with the given path value, if the browser supports the
     * replaceState API.
     * @{@link http://caniuse.com/#feat=history}
     * @param  {string} path URL path to set
     */
    replaceHistoryState: function (path) {
        if ('replaceState' in window.history && typeof window.history.replaceState === 'function') {
            window.history.replaceState({ commentID: this.current }, "", path);
        }
    },

    /**
     * Replaces or adds a comment url param to the current
     * window.location.pathname
     * @param  {string} commentID Comment id to insert
     * @return {string}           The window.location.pathname with a comment param and id
     */
    replacePath: function (commentID) {
        return RightNow.Url.addParameter(window.location.pathname, 'comment', commentID);
    },

    /**
     * Scrolls to the specified comment and upadates the URL.
     * @param  {string} commentID Comment id
     * @return {bool}           True if the comment was found and
     *                               scrolled to, False otherwise
     */
    jumpToComment: function (commentID) {
        if (commentID === '0') {
            window.location.hash = this.Y.one(this.baseSelector + '_NewComment').getAttribute('id');
            return true;
        }

        var commentOnThePage = commentID ? this.Y.one(this.baseSelector + ' #comment_' + commentID) : null;

        if (commentOnThePage) {
            var commentReplies = this.Y.one(this.baseSelector + '_' + commentID).get('parentNode');
            if(commentReplies.hasClass('rn_Collapsed')) {
                commentReplies.removeClass('rn_Collapsed');
            }
            this.scrollNodeIntoViewport(commentOnThePage.get('parentNode'), 50);
            this.setComment(commentID);

            return true;
        }

        return false;
    },

    /**
     * Emits the `requestPageWithComment` event.
     * @param  {string} commentID Comment id to load
     */
    loadComment: function (commentID) {
        this.fire('requestPageWithComment', { commentID: commentID });
    },

    /**
     * Scrolls to the specified comment or requests to load it if it
     * isn't present on the page.
     * @param  {string} commentID Comment id to scroll to
     */
    jumpToOrLoadComment: function (commentID) {
        this.jumpToComment(commentID) || this.loadComment(commentID);
    },

    /**
     * Retrieves a comment ID from the URL.
     * @return {string|null} Comment id or null if not found
     */
    getCommentParam: function () {
        return RightNow.Url.getParameter('comment');
    },

    /**
     * Smoothly scrolls to the given node.
     * @param  {object} node   Y.Node to scroll to
     * @param  {number=} offset Y-offset pixels above the node to scroll to
     *                          (default: 100)
     */
    scrollNodeIntoViewport: function (node, offset) {
        var focusOnNode = function () {
                // Focus the comment for screenreader friendliness.
                node.setAttribute('tabIndex', -1).focus().once('blur', function () {
                    node.removeAttribute('tabIndex');
                });
            }, anim;

        anim = new this.Y.Anim({
            node:       'win',
            to:         { scroll: [ document.body.scrollLeft, node.get('region').top - (offset || 100) ] },
            duration:   0.5
        });

        // Set a flag so that the onScroll handler doesn't run while this programmatic scroll happens.
        anim.after('end', function () {
            this._restoringPosition = false;
            focusOnNode();
        }, this);
        this._restoringPosition = true;

        anim.run();
    }
});

/**
 * Comment form and rich text editor used for making new comments.
 */
RightNow.Widgets.QuestionComments.embeddedEditor = RightNow.EventProvider.extend({
    overrides: {
        constructor: function() {
            this.parent();

            this.hideClass = 'rn_Hidden';
        }
    },

    /**
     * Defaults handler for form's submit event.
     * @param  {Object} editor RichTextInput instance
     */
    _onFormSubmit: function(editor) {
        this.fire('submit', { value: editor.getValue() });
    },

    /**
     * Embedded RichText widget.
     * @return {Object} RightNow.Widgets.RichTextEditor
     */
    _getCommentEditorWidget: function() {
        var editorType = this.data.attrs.use_rich_text_input ? '.rn_RichTextInput' : '.rn_TextInput';
        this._editorInstanceID ||
            (this._editorInstanceID = RightNow.Text.getSubstringAfter(this.form.one(editorType).get('id'), 'rn_'));

        return this._monitorEditorSubmitEvent(RightNow.Widgets.getWidgetInstance(this._editorInstanceID));
    },

    /**
     * Input widgets need a standard form and FormSubmit in order to do things
     * like validation. But we don't want the regular form submission, but rather
     * the widget endpoint. So cancel the form's `send` event and send it ourselves.
     * @param  {Object} editor RightNow.Widgets.RichText instance
     */
    _monitorEditorSubmitEvent: function(editor) {
        if (!this._monitoringEditor && editor) {
            // Don't add a subscriber multiple times,
            // or we'll do multiple submissions.
            this._monitoringEditor = true;

            editor.parentForm().on('send', function() {
                this._onFormSubmit(editor);

                return false;
            }, this);
        }
        return editor;
    },

    /**
     * Reloads the RichText widget's editor.
     * @param {String} label Label to set for the editor
     * @param {String} formSubmitLabel Label to set for the form submit button
     * @param {String=} content Content to use for the editor
     * @param {boolean} readOnly True if editor should be placed in read only mode.
     */
    _reloadEditor: function(label, formSubmitLabel, content, readOnly) {
        var editor = this._getCommentEditorWidget();
        if (editor) {
            editor.setLabel(label);
            editor.reload(content, readOnly,editor);
            this.resetForm(editor);
            if(formSubmitLabel)
                this.Y.one(editor.parentForm().baseSelector + ' button').set('innerHTML', formSubmitLabel);
            if(readOnly) {
                this.Y.one('.rn_CommentEditForm .rn_FormSubmit button').set('disabled', true);
                if(!this.data.attrs.use_rich_text_input)
                    this.Y.one(this.baseSelector + ' textarea.rn_TextArea').setAttribute('readonly', 'readonly');
            }
        }
    },

    /**
     * Smoothly scrolls the form into view
     * if it currently isn't in the viewport.
     */
    _scrollIntoViewport: function() {
        if (!this.Y.DOM.inViewportRegion(this.Y.Node.getDOMNode(this.form, true))) {
            (new this.Y.Anim({
                node:       'win',
                to:         { scroll: [ document.body.scrollLeft, this.form.getY() -
                                                                  (this.Y.DOM.winHeight() / 2) +
                                                                  (this.form.get('offsetHeight') / 2) ]},
                duration:   0.5
            })).run();
        }
    },

    /**
     * Sets the form property.
     * @param  {Object} form Y.Node form node
     */
    setForm: function(form) {
        if (form) {
            this.form = form;

            // Override the form's default behavior by cancelling its `send` event.
            this.form.all('[type="submit"]').on('click', this._getCommentEditorWidget, this);
        }

        return this;
    },

    /**
     * Fires the `reset` event on the editor's form instance.
     * @param {Object=} editor editor instance, if available
     * @chainable
     */
    resetForm: function(editor) {
        editor || (editor = this._getCommentEditorWidget());
        if (editor) {
            editor.parentForm().fire('reset', new RightNow.Event.EventObject(this));
        }

        return this;
    },

    /**
     * Reloads the editor
     * (clears its content and resets its form).
     * @chainable
     */
    reload: function() {
        this._reloadEditor();

        return this;
    },

    /**
     * gets the url fo given question id and comment id
     * @param String questionId
     * @param String commentId
     */
    getQuestionCommentUrl: function(questionId, commentId) {
        if(!questionId)
            return null;
        var url = '/app/' + RightNow.Interface.getConfig('CP_SOCIAL_QUESTIONS_DETAIL_URL') + "/qid/" + questionId;
        if(commentId)
            return  url + "/comment/" + commentId;
        return url;
    }, 

    /**
     * changes the editor contents
     * @param String author
     * @param String content
     * @param int commentId
     * @chainable
     */
    setQuotedContent: function(authorName, quotedContentInnerHtml, commentId) {
        var originalCommentPermaLink = '';
        if(commentId != null) {
            originalCommentUrl = this.getQuestionCommentUrl(RightNow.Url.getParameter('qid'), commentId),
            originalCommentPermaLink = '<a class="rn_ViewOriginalLink" href="' + originalCommentUrl + '">' + this.data.attrs.label_view_original_comment + '</a>';
        }
        var contentTextToBeUsed = quotedContentInnerHtml,
        author = RightNow.Text.sprintf(this.data.attrs.label_quote_author_wrote, authorName),
        content = author + '<blockquote class="rn_QuotedContentText"><em>' + contentTextToBeUsed + '</em>' + originalCommentPermaLink + '</blockquote><p></p>',
        editor = this._getCommentEditorWidget(),
        editorInstance = editor.getInstance(),
        range = editor.getRange();
        range.selectNodeContents(editorInstance.document.getBody());
        editor.insertHtml(content,'html',range);
        range = editor.getRange();
        range.selectNodeContents(editorInstance.document.getBody().getLast().getLast());
        range.deleteContents();
        if(!this.Y.UA.phantomjs)
            editor.insertHtml('','html');
        this._scrollIntoViewport();
        return this;
    },  

    /**
     * Gets the html from the editor.
     * @return {String} editor's html content
     */
    getHTML: function() {
        return this._getCommentEditorWidget().getValue().text;
    },

    /**
     * Is the form hidden?
     * @return {Boolean} result
     */
    isHidden: function() {
        return this.form && this.form.hasClass(this.hideClass);
    },

    /**
     * Is the form not hidden?
     * @return {Boolean} result
     */
    isVisible: function() {
        return !this.isHidden();
    },

    /**
     * Removes the hidden class from the form.
     * @chainable
     */
    show: function() {
        this.form.removeClass(this.hideClass);

        return this;
    },

    /**
     * Adds the hidden class onto the form.
     * @chainable
     */
    hide: function() {
        this.form.addClass(this.hideClass);

        return this;
    },

    /**
     * Hides / shows the form.
     * @chainable
     */
    toggle: function() {
        this.form.toggleClass(this.hideClass);

        return this;
    }

});

/**
 * Roving comment form and rich text editor used for editing comments
 * and replying to comments.
 *
 * This class manages the movement of the editor's form.
 * It places the form after the comment element that it's associated with.
 *
 * Editing:
 *
 *      <div data-commentid="{id}">{Comment}</div>
 *      <form>{Editor}</form> ← Inserted here
 *
 * Replying:
 *
 *      <div data-commentid="{id}">{Comment}</div>
 *      <div class="rn_Replies" data-commentid="{id}">
 *          <div data-commentid="{reply1id}">...</div>
 *          <div data-commentid="{reply2id}">...</div>
 *      </div>
 *      <form>{Editor}</form> ← Inserted here
 *
 */
RightNow.Widgets.QuestionComments.rovingEditor = RightNow.Widgets.QuestionComments.embeddedEditor.extend({
    overrides: {
        /**
         * Applies a click handler on the form's cancel link.
         * @param  {Object} form Y.Node form node
         */
        setForm: function(form) {
            this.parent(form);

            if (this.form) {
                this.form.delegate('click', this._cancelClick, '.rn_CancelEditor', this);
            }
        },

        /**
         * Adds additional context onto `submit` event.
         * @param  {Object} editor RichTextInput instance
         */
        _onFormSubmit: function(editor) {
            this.fire('submit', {
                mode: this.mode,
                value: editor.getValue(),
                comment: this.form.previous()
            });
        }
    },

    /**
     * Modes this component is aware of
     * and the class names to set on the form
     * when in each of the modes.
     * @type {Object}
     */
    _modes: {
        edit:  'rn_CommentEditForm',
        reply: 'rn_CommentReplyForm'
    },

    /**
     * Sets the form mode.
     * @param  {String} mode edit or reply
     */
    _setMode: function(mode) {
        this.mode = mode;
        this._setFormClassName(this._modes[mode]);
    },

    /**
     * Removes any other mode classes from the
     * form and adds the supplied class.
     * @param  {String} className class name to add to the form
     */
    _setFormClassName: function(className) {
        this._modeClassNames || (this._modeClassNames = this.Y.Object.values(this._modes));
        this.Y.Array.each(this._modeClassNames, function(classToRemove) {
            this.form.removeClass(classToRemove);
        }, this);

        this.form.addClass(className);
    },

    /**
     * Event listener for cancel link in the form.
     * @param  {Object} e Click event
     */
    _cancelClick: function(e) {
        e.halt();

        this.hide();
        this.fire('toggle', {
            mode: this.mode,
            parent: this.form.previous()
        });
        var editor = this._getCommentEditorWidget();
        if (typeof editor.reset === "function") 
        editor.reset();
    },

    /**
     * Handles the commonalities between replying
     * and editing.
     * @param  {String} mode       edit or reply
     * @param  {Object} directives Needs to contain
     *                             `shouldMove` and
     *                             `move` functions
     * @return {Boolean}            Whether the move
     *                                      happened
     */
    _orchestrateMove: function(mode, directives) {
        if (!this.form) return false;

        var parentComment = this.form.previous();

        if (this.mode === mode && !directives.shouldMove.call(this)) {
            this.toggle();
            this.fire('toggle', {
                mode: mode,
                parent: parentComment
            });

            if (this.isVisible()) {
                this._scrollIntoViewport();
            }

            return false;
        }

        this._setMode(mode);

        var comment = directives.move.call(this, parentComment);
        this._scrollIntoViewport();
        this.fire('move', {
            mode: mode,
            prevParent: parentComment,
            newParent: comment
        });

        return true;
    },

    /*
    * Determines if the delete button
    * should be shown within the Comment Toolbar
    * @param {Object} commentContainer Y.Node containing
    *                                  the current comment
    */
    _toggleDeleteButton: function(commentContainer) {
        var deleteButton = this.Y.Node.one('.rn_DeleteCommentAction'),
            isDeletable = commentContainer.one('.rn_CommentContent').hasClass('rn_CommentDeletable');

        if(!deleteButton && isDeletable) {
            this.Y.Node.create('<button class="rn_DeleteCommentAction">' +
                                this.data.attrs.label_delete +
                                '</button>').appendTo(this.Y.one('.rn_CommentEditOptions'));
        }
        else if(deleteButton && !isDeletable) {
            deleteButton.remove();
        }
    },

    /**
     * Puts the editor into edit mode.
     * @param  {Object} commentContainer Y.Node to move the
     *                                   editor form into
     * @param  {String} commentText      text to initialize
     *                                   the editor with
     * @return {Boolean}                  Whether the editor
     *                                            moved
     */
    editComment: function(commentContainer, commentText) {
        var editor = this._getCommentEditorWidget();
        return this._orchestrateMove('edit', {
            // Whether the editor should move or if it's already in place.
            shouldMove: function() {
                var isSibling = this.isSiblingOfNode(commentContainer);
                if(isSibling) {
                    var readOnly = editor.data.attrs.read_only,
                        label = readOnly ? this.data.attrs.label_comment_not_editable : this.data.attrs.label_edit_comment;
                    this._reloadEditor(label, this.data.attrs.label_save_edit, this.Y.Lang.trim(commentText), readOnly);
                }
                return !isSibling;
            },
            // Moves the editor and returns the new parent.
            move: function() {
                this.show();
                var readOnly = editor.data.attrs.read_only,
                    label = readOnly ? this.data.attrs.label_comment_not_editable : this.data.attrs.label_edit_comment;

                this._reloadEditor(label, this.data.attrs.label_save_edit, this.Y.Lang.trim(commentText), readOnly);
                commentContainer.insert(this.form, 'after');
                this._toggleDeleteButton(commentContainer);

                return commentContainer;
            }
        });
    },

    /**
     * Puts the editor into reply mode.
     * @param  {Object} commentContainer Y.Node to move
     *                                   the editor form
     *                                   after
     * @return {Boolean}                  Whether the editor
     *                                            moved
     */
    replyToComment: function(commentContainer) {
        return this._orchestrateMove('reply', {
            // Whether the editor should move or if it's already in place.
            shouldMove: function() {
                var isSibling = this.isSiblingOfNode(commentContainer);
                if(isSibling) {
                    this._reloadEditor(this.data.attrs.label_reply_to_comment, this.data.attrs.label_post_reply_button);
                }
                return !isSibling;
            },
            // Moves the editor and returns commentContainer.
            move: function() {
                this.show();
                this._reloadEditor(this.data.attrs.label_reply_to_comment, this.data.attrs.label_post_reply_button);
                commentContainer.insert(this.form, 'after');
                return commentContainer;
            }
        });
    },

    /**
     * Determines whether the form is the
     * next sibling of node.
     * @param  {Object} node Y.Node
     * @return {Boolean}      Whether node
     *                                contains the form
     */
    isSiblingOfNode: function(node) {
        return node.next() === this.form;
    }
});
/**
 * Comment actions menubar.
 */
RightNow.Widgets.QuestionComments.commentActions = RightNow.EventProvider.extend({
    overrides: {
        constructor: function() {
            this.parent();
            var el = this.Y.one(this.baseSelector);

            this._eventHandler.subscribeToEvents(el, [
                { type: 'share', selector: '.rn_ShareAction', handler: '_shareClicked' },
                { type: 'reply', selector: '.rn_ReplyAction' },
                { type: 'quoteComment', selector: '.rn_QuoteAction' }
            ], this);
        }
    },

    _eventHandler: {
        /**
         * Attaches delegate event handlers onto
         * el.
         * @param  {Object} el      Attach delegate listener on
         * @param  {Array} events  Each item should have:
         *                         - type: String event name to broadcast
         *                                 when this event triggers
         *                         - selector: String delegate event selector
         *                         - handler: String optional name of widget
         *                                 method to call
         * @param  {object} context `this`
         */
        subscribeToEvents: function(el, events, context) {
            var delegator = this.delegator;
            context.Y.Array.each(events, function(event) {
                el.delegate('click', delegator, event.selector, this, event.type, event.handler);
            }, context);
        },

        /**
         * Event handler for all the delegate events.
         * - Halts the event
         * - Fires an event with the event target
         * - Calls handler, if given
         * @param  {Object} e       Click event
         * @param  {String} type    Name of event to broadcast
         * @param  {Function=} handler Optional handler
         */
        delegator: function(e, type, handler) {
            e.halt();

            if(type !== 'share') {
                this._checkAndHideSharePanel.call(this);
            }
            this.fire(type, e);

            if (handler) {
                this[handler].call(this, e);
            }
        }
    },

    /**
     * Hiding share panel, if it exists
     */
    _checkAndHideSharePanel: function() {
        if (this._panel) {
            this._panel.hide();
        }
    },
    /**
     * Get the comment id for the given node
     * by looking for an ancestor with a commentid
     * data attribute.
     * @param  {Object} node Y.Node
     * @return {String}      ID Attribute or empty string
     */
    _getCommentID: function(node) {
        var idAttr = 'data-commentid',
            commentContainer = node.ancestor('[' + idAttr + ']');

        return (commentContainer)
            ? commentContainer.getAttribute(idAttr)
            : '';
    },

    /**
     * Constructs a url representing the current page
     * and the comment anchor id.
     * @param  {String} commentID comment id
     * @return {String}           url
     */
    _getUrl: function(commentID) {
        var location = window.location,
            pathname = RightNow.Url.deleteParameter(location.pathname, 'session');

        return location.protocol + '//' +
            location.hostname +
            RightNow.Url.addParameter(RightNow.Url.deleteParameter(pathname, 'page'), 'comment', commentID);
    },

    /**
     * Handler for click event on the share link.
     * @param  {Object} e Click event
     */
    _shareClicked: function(e) {
        e.halt();
        var eventObject = new RightNow.Event.EventObject(this, {data: {
            commentID: this._getCommentID(e.currentTarget),
        }});
    
        if(this.data.js.f_tok){
            RightNow.Event.subscribe("evt_formTokenUpdate", RightNow.Widgets.onFormTokenUpdate, this);
            // Get a new f_tok value on each ajax request
            RightNow.Event.fire("evt_formTokenRequest", new RightNow.Event.EventObject(this, {data:{formToken:this.data.js.f_tok}}));

            eventObject.data.f_tok = this.data.js.f_tok;
        }

        RightNow.Ajax.makeRequest(this.data.attrs.check_comment_exists_ajax, eventObject.data, {
            data:           eventObject,
            json:           true,
            scope:          this,
            successHandler: this._onShareResponse
        });
    },

    /**
     * Displays the Share Link panel based on whether the comment exists or not
     * @param {Object} response Event response
     * @param {Object} e Click event
     */
    _onShareResponse: function(response, e) {
        if (response.errors) {
            if(!RightNow.Ajax.indicatesSocialUserError(response)) {
                var dialogParameters = {exitCallback: {fn: function() { messageDialog.hide(); }, scope: this}},
                    messageDialog;
                dialogParameters.icon = 'WARN';
                messageDialog = RightNow.UI.Dialog.messageDialog(response.errors[0].externalMessage, dialogParameters).show();
            }
        }
        else {
           this._showPanel(this.Y.one(this.baseSelector + '_' + e.data.commentID + ' .rn_CommentAction.rn_ShareAction'), this._getUrl(e.data.commentID));
        }
    },

    /**
     * Shows the panel.
     * @param  {Object} referenceNode Y.Node to align
     *                                the panel to
     * @param  {String} commentUrl    Url to pop into the input
     */
    _showPanel: function(referenceNode, commentUrl) {
        if (!this._panel) {
            this._panel = this._createPanel();
        }
        else if (this._panel.get('align').node === referenceNode && this._panel.get('visible')) {
            // The trigger element acts as a toggle.
            this._panel.hide();
            return;
        }
        
        this._adjustPanelForComment(referenceNode);
        this._populateDynamicContent(commentUrl);
    },
    
    /**
     * Plugs the comment url into the dynamic bits:
     * - input box for quick copy+paste
     * - share links
     * @param  {String} commentUrl Comment's url
     */
    _populateDynamicContent: function(commentUrl) {
        var body = this._panel.getStdModNode(this.Y.WidgetStdMod.BODY);

        body.all('a').each(function(a, baseHref) {
            baseHref = a.getAttribute('data-base-href');
            if (baseHref) {
                a.setAttribute('href', baseHref + encodeURIComponent(commentUrl));
            }
        });

        body.one('input').set('value', commentUrl).focus().set('selectionStart', 0).set('selectionEnd', commentUrl.length);
    },

    /**
     * A single share panel instance is shared across all comments. When it's shown
     * for a different comment than it was the last time it was visible it needs to
     * be adjusted for the new comment.
     * @param  {Object} referenceNode Y.Node The share button trigger
     */
    _adjustPanelForComment: function(referenceNode) {
        // Move the panel after the comment so that tab index behaves properly.
        referenceNode.ancestor('.rn_CommentContainer').insert(this._panel.get('boundingBox'), 'after');

        // Realign to the trigger element.
        this._panel.align(referenceNode,
            [this.Y.WidgetPositionAlign.TR, this.Y.WidgetPositionAlign.BR]).show();

        // Focus on the trigger element after the panel closes.
        this._panel.onceAfter('visibleChange', function (e) {
            if (!e.newVal) {
                this._panel.destroy();
                this._panel = null;
            }
        }, this);
    },

    /**
     * Creates a new panel instance.
     * @return {Object} Y.Panel
     */
    _createPanel: function() {
        return new this.Y.Panel({
            headerContent:  null,
            bodyContent:    this._renderShareBox(),
            constrain:      true,
            srcNode:        this.Y.Node.create('<div class="rn_ShareBox">'),
            alignOn:        [{ node: this.Y.one('win'), eventName: 'resize' }],
            render:         this.baseSelector,
            zIndex:         1, /* If the panel's not modal, YUI sets z-index to 0 */
            hideOn:         [
                { eventName: 'clickoutside' },
                { eventName: 'key', node: this.Y.one(document), keyCode: 'esc' }
            ]
        });
    },

    /**
     * Renders the commentShareBox view.
     * @return {String} rendered view
     */
    _renderShareBox: function() {
        if (!this._shareBoxView) {
            this._shareBoxView = new EJS({ text: this.getStatic().templates.commentShareBox })
                .render({
                    id: this.baseDomID,
                    labels: {
                        title:      RightNow.Interface.getMessage('SHARE_A_LINK_TO_THIS_COMMENT_LBL'),
                        label:      RightNow.Interface.getMessage('LINK_TO_THIS_COMMENT_LBL'),
                        fb:         RightNow.Interface.getMessage('SHARE_THIS_CONTENT_ON_FACEBOOK_LBL'),
                        twitter:    RightNow.Interface.getMessage('SHARE_THIS_CONTENT_ON_TWITTER_LBL'),
                        linkedin:   RightNow.Interface.getMessage('SHARE_THIS_CONTENT_ON_LINKEDIN_LBL'),
                        reddit:     RightNow.Interface.getMessage('SHARE_THIS_CONTENT_ON_REDDIT_LBL')
                    }
                });
        }

        return this._shareBoxView;
    }
});
