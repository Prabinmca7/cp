/**
 * Synopsis:
 * - Logged-in Author
 * - Unmark best answer chosen by Author
 */
UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'QuestionComments_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/discussion/QuestionComments - Unmarking functionality tests As Author"
    });

    suite.add(new Y.Test.Case({
        name: "Unmarking best answer as Author",

        setUp: function() {
            this.bestAnswerButton = Y.one('.rn_BestAnswerRemoval.rn_UserTypeAuthor button'),
            this.buttonID = this.bestAnswerButton.getAttribute('data-commentID'),
            this.commentID = '#rn_QuestionComments_0_' + this.buttonID;
        },

        "Unmark comment chosen as best by Author": function() {
            var expectedPostData = {
                commentID: parseInt(this.buttonID, 10),
                removeAnswer: true,
                chosenByType: 'Author'};

            var mockedEventObj = new RightNow.Event.EventObject(widget,
                {data: {commentID: this.buttonID, chosenByType: "Author"}});

            Y.Assert.isTrue(Y.one(this.commentID).hasClass('rn_BestAnswer'));
            UnitTest.overrideMakeRequest(widget.data.attrs.best_answer_ajax, expectedPostData,
                '_bestAnswerResponse', widget, Array(), mockedEventObj);
            widget._bestAnswerClick(parseInt(this.buttonID, 10), this.bestAnswerButton);

            var bestAnswerAssignmentButton = Y.one(this.commentID + ' .rn_BestAnswerAssignment.rn_UserTypeAuthor button'),
                bestAnswerRemovalButton = Y.one(this.commentID + ' .rn_BestAnswerRemoval.rn_UserTypeAuthor button');

            Y.Assert.isFalse(Y.one(this.commentID).hasClass('rn_BestAnswer'));
            Y.Assert.isNotNull(bestAnswerAssignmentButton);
            Y.Assert.isNull(bestAnswerRemovalButton);
        }
    }));

    return suite;
}).run();
