/**
 * Synopsis:
 * - Logged-in Moderator/Author
 * - Unmark best answer chosen by both Author and Moderator
 */
UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'QuestionComments_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/discussion/QuestionComments - Unmarking functionality tests As ModAndAuthor"
    });

    suite.add(new Y.Test.Case({
        name: "Unmarking same best as Moderator And Author",

        setUp: function() {
            this.moderatorBestAnswerButton = Y.one('.rn_BestAnswerRemoval.rn_UserTypeModerator button'),
            this.authorBestAnswerButton = Y.one('.rn_BestAnswerRemoval.rn_UserTypeAuthor button'),
            this.buttonID = this.moderatorBestAnswerButton.getAttribute('data-commentID'),
            this.commentID = '#rn_QuestionComments_0_' + this.buttonID;

            this.bestAnswerResponse = Array({"commentID":this.buttonID,"label":"I'ma Label"});
        },

        subscribeBestAnswer: function(userType) {
            var expectedPostData = {
                commentID: parseInt(this.buttonID, 10),
                removeAnswer: true,
                chosenByType: userType};

            var mockedEventObj = new RightNow.Event.EventObject(widget, {
                data: {commentID: this.buttonID, chosenByType: userType}});

            Y.Assert.isTrue(Y.one(this.commentID).hasClass('rn_BestAnswer'));
            UnitTest.overrideMakeRequest(widget.data.attrs.best_answer_ajax, expectedPostData,
                '_bestAnswerResponse', widget, this.bestAnswerResponse, mockedEventObj);
            widget._bestAnswerClick(parseInt(this.buttonID, 10), userType === 'Moderator' ? this.moderatorBestAnswerButton : this.authorBestAnswerButton);

            var bestAnswerAssignmentButton = Y.one(this.commentID + ' .rn_BestAnswerAssignment.rn_UserType' + userType + ' button'),
                bestAnswerRemovalButton = Y.one(this.commentID + ' .rn_BestAnswerRemoval.rn_UserType' + userType + ' button');

            Y.Assert.isNotNull(bestAnswerAssignmentButton);
            Y.Assert.isNull(bestAnswerRemovalButton);
        },

        "Unmark comment chosen as best by Moderator while logged in as Author and Moderator": function() {
            this.subscribeBestAnswer("Moderator");
        },
        "Unmark comment chosen as best by Author while logged in as Author and Moderator": function() {
            this.subscribeBestAnswer("Author");
        },
    }));

    return suite;
}).run();