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
        name: "Quote Comment",

        setUp: function() {
        },

        tearDown: function() {
        },

        clickQuote: function() {
            var reply = Y.one('.rn_QuoteAction');
            reply.simulate('click');
            return reply;
        },

        "New Comment Textbox gets populated with quoted comment after quote button is clicked": function() {
            //give child widget time a moment to instantiate
            this.wait(function() {
                this.newCommentEditor = widget.newCommentEditor._getCommentEditorWidget();
                this.newCommentEditor.reload();
                this.wait(function() {
                    var quoteButton = this.clickQuote(),
                    container = quoteButton.ancestor('.rn_CommentContainer'),
                    commentAuthorDisplayName = container.one('.rn_DisplayName').get('textContent'),
                    commentText = container.one('.rn_CommentText'),
                    editorData = this.newCommentEditor.getValue();
                    Y.assert(editorData.text.indexOf(commentText.getDOMNode().firstElementChild.textContent) > -1);
                    Y.assert(editorData.text.indexOf(commentAuthorDisplayName) > -1);
                }, 3000);
            }, 3000);
        }
    }));

    return suite;
}).run();
