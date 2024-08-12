/**
 * Synopsis:
 * - Logged-in social user who is authorized to update but not delete.
 * - Verify user does not see delete button
 */
UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'QuestionComments_0',
    jsFiles: [
        '/euf/core/thirdParty/js/ORTL/ortl.js'],
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/discussion/QuestionComments - Deleting functionality tests without authorized user"
    });

    suite.add(new Y.Test.Case({
        name: "Deleting",

        "Check for delete button": function() {
            var comment = Y.one('.rn_CommentContainer[data-commentid]'),
            commentID = parseInt(comment.getAttribute('data-commentid'), 10),
            editButton = comment.one('.rn_EditCommentAction'), deleteButton;
            this.wait(function() {
                editButton.simulate('click');
                this.wait(function() {
                    deleteButton = Y.one('.rn_DeleteCommentAction');
                    Y.Assert.isNull(deleteButton);
                }, 3000);
            }, 3000);
        }
    }));

    return suite;
}).run();
