/**
 * Synopsis:
 * - Logged-in social user.
 * - At least one legit comment.
 */
UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'QuestionComments_0',
    preloadFiles: ['/euf/assets/themes/standard/site.css',
        '/euf/core/thirdParty/js/ORTL/ortl.js'],
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/discussion/QuestionComments - Reply functionality tests"
    });

    suite.add(new Y.Test.Case({
        name: "Replies",

        setUp: function() {
            this.origMakeRequest = RightNow.Ajax.makeRequest;
        },

        tearDown: function() {
            // Reset position of comment form.
            Y.one(baseSelector).insert(
                Y.one(baseSelector + '_RovingCommentForm').addClass('rn_Hidden'), 'after');
            RightNow.Ajax.makeRequest = this.origMakeRequest;
        },

        clickReply: function() {
            var reply = Y.one('.rn_ReplyAction');
            reply.simulate('click');
            return reply;
        },

        clickEdit: function() {
            var edit = Y.one('.rn_EditCommentAction');
            edit.simulate('click');
            return edit;
        },

        submitForm: function() {
            this.clickReply();
            widget.editor._getCommentEditorWidget().reload('spectrum');
            this.wait(function() {
                RightNow.Ajax.makeRequest = function() { /* Don't actually make the request. */ };

                var form = Y.one(baseSelector + '_RovingCommentForm');
                form.one('button[type="submit"]').simulate('click');

                return form;
            }, 1000);
        },

        "Form is injected after comment when reply button for the comment is clicked": function() {
            //give child widget time a moment to instantiate
            this.wait(function() {
                var replyButton = this.clickReply(),
                    form = replyButton.ancestor('.rn_CommentContainer').next();

                Y.Assert.areSame((baseSelector + '_RovingCommentForm').substr(1), form.get('id'));
                Y.assert(form.hasClass('rn_CommentReplyForm'));
                Y.Assert.areSame(widget.data.attrs.label_reply_to_comment, form.one('label .rn_LabelInput').get('text'));
                Y.assert(!form.hasClass('rn_Hidden'));
            }, 3000);
        },

        "Form is hidden when reply button for the comment is clicked while replying": function() {
            var replyButton = this.clickReply(),
                form = replyButton.ancestor('.rn_CommentContainer').next();

            this.clickReply();

            Y.assert(form.hasClass('rn_CommentReplyForm'));
            Y.assert(form.hasClass('rn_Hidden'));
        },

        "Clicking the cancel button in the form hides the form": function() {
            this.clickReply();

            var form = Y.one(baseSelector + '_RovingCommentForm');
            form.one('.rn_CancelEditor').simulate('click');

            Y.assert(form.hasClass('rn_Hidden'));
        },

        "Reply data is sent to the server when user submits the form": function() {
            var commentID = 292,
                replyButton = this.clickReply(),
                form = Y.one(baseSelector + '_RovingCommentForm');

            replyButton.ancestor('.rn_CommentContainer')
                .set('id', baseSelector.substr(1) + '_' + commentID)
                .setAttribute('data-commentid', commentID);
            widget.editor._getCommentEditorWidget().reload('bananas!');
            this.wait(function() {
                var endpoint, params;
                RightNow.Ajax.makeRequest = function(a, b) {
                    endpoint = a;
                    params = b;
                };

                form.one('button[type="submit"]').simulate('click');
                widget.editor.on('instanceReady', function(evt) {
                    Y.Assert.areSame(widget.data.attrs.reply_to_comment_ajax, endpoint);
                    Y.Assert.areSame('<p>bananas!</p>', Y.Lang.trim(params.commentBody));
                    Y.Assert.areSame(commentID, params.commentID);
                }, this);
            }, 3000);
        },

        "Form remains in sending mode until a response from the server comes back": function() {
            var form = this.submitForm();

            Y.assert(form.one('button[type="submit"]').get('disabled'));

            widget._replyToCommentResponse({ result: {}}, new RightNow.Event.EventObject({}, {
                data: { commentID: Y.one('[data-commentid]').getAttribute('data-commentid') }
            }));

            Y.assert(!form.one('button[type="submit"]').get('disabled'));
        },

        "Reply content is injected after comment upon server response": function() {
            var comment = Y.one('[data-commentid]');
            this.submitForm(),

            widget._replyToCommentResponse('<div class="rn_CommentContainer">bananas</div>', new RightNow.Event.EventObject({}, {
                data: { commentID: comment.getAttribute('data-commentid') }
            }));

            Y.Assert.areSame(1, comment.next('.rn_Replies').all('> div').size());
            Y.assert(!comment.next('.rn_Replies').hasClass('rn_Hidden'));
            Y.Assert.areSame(widget.data.attrs.label_replies, Y.Lang.trim(comment.next('.rn_Replies').one('.rn_ReplyTitle').get('text')));
        },

        "Reply form is hidden upon server response": function() {
            var form = this.submitForm(),
                comment = Y.one('[data-commentid]');
            this.wait(function() {
                widget._replyToCommentResponse('nitebike', new RightNow.Event.EventObject({}, {
                    data: { commentID: comment.getAttribute('data-commentid') }
                }));
                this.wait(function() {
                    Y.assert(form.hasClass('rn_Hidden'));
                    Y.assert(form.ancestor().hasClass('rn_PostNewComment'));
                    Y.assert(!RightNow.Widgets.getWidgetInstance(form.one('.rn_RichTextInput').get('id').substr(3)).getValue().text, 'editor should be cleared out');
                }, 3000);
            }, 3000);
        },

        "Comments don't nest more than one level:": function() {
            var comment = Y.one('[data-commentid]');
            this.submitForm(),

            widget._replyToCommentResponse('number son', new RightNow.Event.EventObject({}, {
                data: { commentID: comment.getAttribute('data-commentid') }
            }));

            Y.assert(!comment.next('.rn_Replies').all('.rn_ReplyAction').size());
        },

        "Banner isn't displayed if its label isn't specified": function() {
            var alertCount = Y.all('.rn_Alert').size();
            widget.data.attrs.label_replied_banner = '';
            var comment = Y.one('[data-commentid]');
            this.submitForm(),
            widget._replyToCommentResponse('number son', new RightNow.Event.EventObject({}, {
                data: { commentID: comment.getAttribute('data-commentid') }
            }));
            Y.assert(alertCount === Y.all('.rn_Alert').size());
        },

        "Banner is displayed if its label is specified": function() {
            var alertCount = Y.all('.rn_Alert').size();
            widget.data.attrs.label_replied_banner = 'Nevermind';
            var comment = Y.one('[data-commentid]');
            this.submitForm(),
            widget._replyToCommentResponse('number son', new RightNow.Event.EventObject({}, {
                data: { commentID: comment.getAttribute('data-commentid') }
            }));
            Y.assert(alertCount + 1 === Y.all('.rn_Alert').size());
            Y.assert(Y.all('.rn_Alert').slice(-1).get('text').toString().indexOf('Nevermind') > -1);
        },

        "Reply section is collapsible and expandable": function() {
            var title = Y.one('.rn_ReplyTitle'),
                replyContainer = title.get('parentNode'),
                originalClasses = replyContainer.get('className');

            function commentsAreHidden() {
                var styles = Y.one('.rn_Replies').all('.rn_CommentContainer')
                                .getComputedStyle('display');
                styles = Y.Array.dedupe(styles);

                return styles.length === 1 && styles[0] === 'none';
            }

            title.simulate('click');

            Y.Assert.areNotEqual(originalClasses, replyContainer.get('className'));
            Y.assert(commentsAreHidden(), 'Comments not hidden');

            title.simulate('click');

            Y.Assert.areEqual(originalClasses, replyContainer.get('className'));
            Y.assert(!commentsAreHidden(), 'Comments not visible');
        },

        "Edit mode displays correctly after continually toggling reply mode": function() {
            var form = Y.one(baseSelector + '_RovingCommentForm');

            this.clickReply();
            Y.Assert.areSame('Post comment', form.one('.rn_FormSubmit button').get('innerHTML'));
            Y.Assert.isFalse(form.hasClass('rn_Hidden'));
            Y.Assert.isTrue(form.hasClass('rn_CommentReplyForm'));
            Y.Assert.isTrue(form.one('label').get('text').indexOf(widget.data.attrs.label_reply_to_comment) > -1);

            this.clickReply();
            Y.Assert.areSame('Post comment', form.one('.rn_FormSubmit button').get('innerHTML'));
            Y.Assert.isTrue(form.hasClass('rn_Hidden'));
            Y.Assert.isTrue(form.hasClass('rn_CommentReplyForm'));
            this.clickEdit();
            Y.Assert.areSame('Save', form.one('.rn_FormSubmit button').get('innerHTML'));
            Y.Assert.isFalse(form.hasClass('rn_Hidden'));
            Y.Assert.isFalse(form.hasClass('rn_CommentReplyForm'));
            Y.Assert.isTrue(form.hasClass('rn_CommentEditForm'), 'rn_CommentEditForm class missing');
            Y.Assert.isTrue(form.one('label').get('text').indexOf(widget.data.attrs.label_edit_comment) > -1, 'label_edit_comment not present');
        },

        "Login event is fired when login link is clicked and displayName is falsey": function() {
            widget.data.js.displayName = null;

            var wasCalled;
            RightNow.Event.on("evt_userInfoRequired", function(evt, args) {
                wasCalled = true;
            });

            this.clickReply();

            Y.Assert.isTrue(wasCalled);
        }
    }));

    return suite;
}).run();
