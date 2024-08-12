/**
 * Synopsis:
 * - Logged-in social user.
 * - One legit comment that the user authored.
 */
UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'QuestionComments_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/discussion/QuestionComments - Editing functionality tests when RichTextInput is not used"
    });

    suite.add(new Y.Test.Case({
        name: "Editing using TextInput",

        clickEdit: function() {
            Y.one('.rn_EditCommentAction').simulate('click');
        },

        isHidden: function(el) {
            if(Y.instanceOf(el, Y.NodeList)) {
                return el.hasClass('rn_Hidden').every(function(item) { return item; });
            }

            return el.hasClass('rn_Hidden');
        },

        isVisible: function(el) {
            return !this.isHidden(el);
        },

        tearDown: function() {
            // reset back into not-edit-mode.
            if (!Y.one('.rn_NoNewCommentMessage.rn_Hidden')) {
                this.clickEdit();
            }
        },

        "Editor appears in place of comment text when edit link is clicked": function() {
            this.wait(function() {
                var comment = Y.one('.rn_CommentContainer');
                comment.setAttribute('data-contenttype', 'text/x-markdown');
                this.clickEdit();

                Y.assert(this.isHidden(comment));
                var injectedForm = comment.next(baseSelector + '_RovingCommentForm');
                Y.assert(injectedForm);

                Y.Assert.areSame(widget.data.attrs.label_edit_comment, injectedForm.one('.rn_CommentContent .rn_Label').get('text'));

                Y.assert(injectedForm.one('.rn_DeleteCommentAction'));
            }, 3000);
        },

        "A comment with a content type of text/html enables editing": function() {
            var commentContainer = Y.one('.rn_CommentContainer');
            commentContainer.setAttribute('data-contenttype', 'text/html');
            widget.editor.editComment(commentContainer, 'blah blah blah');
            this.clickEdit();

            Y.Assert.areSame(widget.data.attrs.label_edit_comment, Y.one('.rn_CommentContent .rn_Label').get('innerHTML'));
            Y.Assert.isFalse(Y.one('.rn_CommentEditForm .rn_FormSubmit button').get('disabled'));
            Y.Assert.areSame(Y.one('.rn_CommentEditForm textarea.rn_TextArea').getAttribute('readonly'), '');
            // reset
            commentContainer.setAttribute('data-contenttype', 'text/x-markdown');
        },

        "New answer area is hidden and explanation message is shown when edit button is clicked": function() {
            var noNewMessage = Y.one('.rn_NoNewCommentMessage'),
                newCommentForm = Y.one('.rn_PostNewComment');

            Y.assert(this.isHidden(noNewMessage));
            Y.assert(this.isVisible(newCommentForm));

            this.clickEdit();

            Y.assert(this.isVisible(noNewMessage));
            Y.assert(this.isHidden(newCommentForm));
        },

        "Cancel hides the injected form": function() {
            this.clickEdit();

            Y.one('.rn_CancelEdit a').simulate('click');

            var commentText = Y.one('.rn_CommentText');
            Y.assert(this.isVisible(commentText));
            Y.assert(this.isHidden(Y.one(baseSelector + '_RovingCommentForm')));

            Y.one('.rn_CancelEdit a').simulate('click');
        },

        "Cancel re-shows new answer area and hides explanation message": function() {
            this.clickEdit();
            Y.one('.rn_CancelEdit a').simulate('click');

            Y.assert(this.isHidden(Y.one('.rn_NoNewCommentMessage')));
            Y.assert(this.isVisible(Y.one('.rn_PostNewComment')));
        },

        "Replying while editing goes out of edit mode and into reply mode": function() {
            this.clickEdit();

            // Click another comment's reply button, since you can't reply to a comment
            // being edited.
            Y.all('a.rn_ReplyAction').item(1).simulate('click');

            var comment = Y.one('.rn_CommentContainer'),
                form = Y.one(baseSelector + '_RovingCommentForm');

            Y.assert(this.isVisible(comment));
            Y.Assert.areNotSame(comment.next(), form);
            Y.Assert.isTrue(form.hasClass('rn_CommentReplyForm'));

            this.clickEdit();

            Y.assert(this.isHidden(comment));
            Y.Assert.areSame(comment.next(), form);
            Y.Assert.isTrue(form.hasClass('rn_CommentEditForm'));
        }
    }));

    return suite;
}).run();
