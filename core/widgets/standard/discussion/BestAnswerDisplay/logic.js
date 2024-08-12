RightNow.Widgets.BestAnswerDisplay = RightNow.Widgets.extend({
    constructor: function() {
        this.el = this.Y.one(this.baseSelector);

        this.el.delegate('click', this._toggleCommentClick,'.rn_ShowAllCommentText, .rn_CollapseCommentText', this);
        this.el.delegate('click', this._makeRefreshRequest, '.rn_Refresh', this);
        this.el.delegate('click', this._jumpToAnswer, '.rn_ReplyToComment', this);
        this.el.delegate('click', this._unselectBestAnswer, '.rn_BestAnswerRemoval span', this);

        RightNow.Event.on('evt_bestAnswerResponse', this._bestAnswerUpdated, this);
        RightNow.Event.on('evt_refreshBestAnswer', this._refreshBestAnswer, this);
        this._autoExpandShorterComments();
    },

    _unselectBestAnswer: function(e) {
        e.target.set('disabled', true);
        e.halt();
        RightNow.Event.fire('evt_bestAnswerUnselect',
            new RightNow.Event.EventObject(this, {data: {
            commentID: parseInt(e.currentTarget.one('button').getAttribute('data-commentid'), 10),
            chosenByType: e.currentTarget.hasClass('rn_UserTypeAuthor') ? "Author" : "Moderator"
        }}));
    },

    _jumpToAnswer: function (e) {
        e.halt();
        RightNow.Event.fire('evt_jumpToComment',
            new RightNow.Event.EventObject(this, { data: { commentID: e.currentTarget.getAttribute('data-commentid') } }));
    },

    /**
     * Call the appropriate expand or collapse function
     * @param {Object} e Click event
     */
    _toggleCommentClick: function(e) {
        var toggleLink = e.target,
            commentID = toggleLink.getAttribute('data-commentid'),
            commentBody = this.Y.one("#rn_" + this.instanceID + "_BestAnswerCommentText_" + commentID).ancestor('.rn_BestAnswerBody');

        if(e.target.hasClass('rn_ShowAllCommentText'))
            this._expandComment(toggleLink, commentBody, true);
        else if(e.target.hasClass('rn_CollapseCommentText'))
            this._collapseComment(toggleLink, commentBody);
    },

    /**
     * Show the full text of the comment
     * @param {Object}  expandLink Link node that is clicked to expand comment
     * @param {Object}  commentBody Node that contains the text of the comment
     * @param {Boolean} showCollapsedLink Whether collapsed link should be shown or not
     */
    _expandComment: function(expandLink, commentBody, showCollapsedLink) {
        RightNow.UI.hide(expandLink);

        if(showCollapsedLink)
            RightNow.UI.show(expandLink.siblings('.rn_CollapseCommentText'));

        RightNow.UI.show(expandLink.siblings('.rn_ReplyToComment'));
        commentBody.removeClass("rn_CommentCollapsed");
    },

    /**
     * Collapse the content of an expanded best answer
     * @param {Object} collapseLink Link node that is clicked to collapse comment
     * @param {Object} commentBody Node that contains the text of the comment
     */
    _collapseComment: function(collapseLink, commentBody) {
        RightNow.UI.hide(collapseLink);
        RightNow.UI.show(collapseLink.siblings('.rn_ShowAllCommentText'));

        commentBody.addClass("rn_CommentCollapsed").scrollIntoView(true);
    },

    /**
     * Automatically 'expand' comments that are shorter than the max height
     */
    _autoExpandShorterComments: function() {
        this.el.all('ul li.rn_BestAnswerContainer').each(function(bestAnswerItem) {
            var commentDiv = bestAnswerItem.one('.rn_BestAnswerBody'),
                commentText = commentDiv.one('.rn_CommentText'),
                maxHeight = commentText.getComputedStyle('maxHeight'),
                commentChildElements = commentText.all('p'),
                expandLink = bestAnswerItem.one('.rn_ShowAllCommentText');

            if (maxHeight === 'none' || maxHeight === '0') {
                return;
            }

            var currentCommentHeight = this.Y.Array.reduce(commentChildElements.get('offsetHeight'), 0, function(prev, curr) {
                return prev + curr;
            });

            maxHeight = parseInt(maxHeight, 10);
            if (currentCommentHeight <= maxHeight) {
                this._expandComment(expandLink, commentDiv, false);
            }
        }, this);
    },

    /**
     * Listener when a best answer selection has changed.
     * Displays the various notices.
     */
    _bestAnswerUpdated: function(response, origEventObj) {
        if(!origEventObj[0].errors) {
            this._displayRefreshMask(this.el);
            RightNow.UI.displayBanner(this.data.attrs.label_dynamic_notice, {
                baseClass: '#rn_' + origEventObj[1].w_id + '_' + origEventObj[1].data.commentID +
                    ' .rn_BestAnswerActions .rn_UserType' + origEventObj[1].data.chosenByType + ' button'
            });
        }
        else {
            var bestAnswerButton = this.Y.one(this.baseSelector + ' .rn_BestAnswer' + (origEventObj[1].data.removeAnswer ? 'Removal' : 'Assignment') + ' .rn_UserType' + origEventObj[1].data.chosenByType + ' button');

            if(bestAnswerButton) {
                bestAnswerButton.set('disabled', false);
            }
        }
    },

    /**
     * Refresh best answers
     */
    _refreshBestAnswer: function(event, origEventObj) {
        RightNow.UI.displayBanner(this.data.attrs.label_dynamic_notice, {focusElement: this.Y.one('#rn_' + origEventObj[0].w_id + '_' + origEventObj[0].data.commentID + ' .rn_EditCommentAction')});
        this._displayRefreshMask(this.el);
    },

    /**
     * Pops the RefreshMask view into the given
     * element.
     * @param  {Object} parent Node to inject the view into
     */
    _displayRefreshMask: function(parent) {
        parent.append(this._render('RefreshMask'));
        parent.all('ul').setAttribute('aria-hidden', 'true');
    },

    /**
     * Event handler for anchor link inside banner.
     * Sends off a request to retrieve the new content
     * and scrolls the widget into view.
     */
    _scrollToWidget: function() {
        this._makeRefreshRequest();
        this._removeMask();
        this.el.scrollIntoView();
    },

    /**
     * Event handler for refresh link click.
     */
    _makeRefreshRequest: function() {
        var eo = new RightNow.Event.EventObject(this, {data: {
            w_id:       this.data.info.w_id,
            questionID: RightNow.Url.getParameter('qid')
        }});
    
        if(this.data.js.f_tok){
            RightNow.Event.subscribe("evt_formTokenUpdate", RightNow.Widgets.onFormTokenUpdate, this);
            // Get a new f_tok value on each ajax request
            RightNow.Event.fire("evt_formTokenRequest", new RightNow.Event.EventObject(this, {data:{formToken:this.data.js.f_tok}}));

            eo.data.f_tok = this.data.js.f_tok;
        }

        if (RightNow.Event.fire('evt_bestAnswerRefreshRequest', eo)) {
            RightNow.Ajax.makeRequest(this.data.attrs.refresh_ajax, eo.data, {
                data: eo,
                scope: this,
                successHandler: this._onRefreshResponse
            });
        }
    },

    /**
     * Renders the response from the server.
     * @param  {Object} response     XMLHTTPRequest object
     * @param  {Object} origEventObj Event object passed in the
     *                               Request event
     */
    _onRefreshResponse: function(response, origEventObj) {
        if (RightNow.Event.fire('evt_bestAnswerRefreshResponse', response, origEventObj)) {
            this._replaceCurrentContent(response.responseText);
            this._removeMask();
        }
    },

    /**
     * Replaces the current widget content with newly
     * refreshed content. Properly handles no best answers
     * and the message that displays in that case.
     * @param  {String} newContent New list of answers or
     *                             empty string if none
     */
    _replaceCurrentContent: function(newContent) {
        var container = this.Y.Node.create('<div></div>').setHTML(newContent || '<p>' + this.data.attrs.label_no_best_answers + '</p>');

        RightNow.Url.transformLinks(container);

        var newUl = container.one('ul');

        (this.el.one('ul') || this.el.one('p')).replace((newUl && newUl.addClass('rn_Refreshed')) || container.one('p'));
        this._autoExpandShorterComments();
    },

    /**
     * Pulls off the mask, revealing the widget's
     * secret identity to the world.
     */
    _removeMask: function() {
        this.el.all('.rn_Mask').remove();
    },

    /**
     * Renders the view with the given name.
     * @param  {String} viewName View to render
     * @return {String}          Rendered view
     */
    _render: function(viewName) {
        this._views || (this._views = {});
        this._views[viewName] || (this._views[viewName] =
            new EJS({ text: this.getStatic().templates[viewName] }).render({
                labels: this.data.attrs
            })
        );

        return this._views[viewName];
    }
});
