/**
 * Synopsis:
 * - Logged-in social user who authored the question.
 * - At least three legit comments.
 */
UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'QuestionComments_0'
}, function (Y, widget) {
    var suite = new Y.Test.Suite({
        name: "standard/discussion/QuestionComments - Best answer functionality tests",

        setUp: function() {
            var testExtender = {
                setUp: function() {
                    this.origMakeRequest = RightNow.Ajax.makeRequest;
                    var self = this;
                    RightNow.Ajax.makeRequest = function () {
                        self.requested = Array.prototype.slice.call(arguments);
                    };
                },

                tearDown: function() {
                    RightNow.Ajax.makeRequest = this.origMakeRequest;
                    this.requested = null;
                    Y.all('.rn_CommentContainer button').removeAttribute('disabled');
                }
            };

            Y.Array.each(this.items, function(testCase) {
                testCase.setUp = testExtender.setUp;
                testCase.tearDown = testExtender.tearDown;
            });
        }
    });

    suite.add(new Y.Test.Case({
        name: "Best answer is the best",

        "Clicking best answer button disables the button and notifies the server": function() {
            var button = Y.one('.rn_BestAnswerAssignment button');
            button.simulate('click');
            Y.Assert.areSame(widget.data.attrs.best_answer_ajax, this.requested[0]);
            Y.Assert.areEqual(button.getAttribute('data-commentid'), this.requested[1].commentID);
            Y.assert(button.get('disabled'));
        },

        "When the server responds, the comment is updated to reflect best answer status and unmark best answer button is shown": function() {
            var button = Y.one('.rn_BestAnswerAssignment button'),
                commentID = parseInt(button.getAttribute('data-commentid'), 10);

            button.simulate('click');
            widget._bestAnswerResponse(
                [{ commentID: commentID, types: {1: true}, label: 'moderator' }],
                new RightNow.Event.EventObject(null, { data: { commentID: commentID, chosenByType: 'Moderator' }})
            );

            var label = Y.one('.rn_BestAnswerLabel');
            Y.assert(label.get('text').indexOf('moderator') > -1);
            Y.assert(label.get('text').indexOf(widget.data.attrs.label_best_answer) > -1, "2");
            Y.assert(label.ancestor('.rn_CommentContainer.rn_BestAnswer'), "3");
        }
    }));

    // Expects another suite to have left a best answer in its wake.
    suite.add(new Y.Test.Case({
        name: "Multiple comments can be a best answer",

        "Best answer indication is retained on other comment when it was chosen by another user role": function() {
            var other = Y.one('.rn_BestAnswer'),
                container = Y.one('.rn_CommentContainer:not(.rn_BestAnswer)'),
                commentID = parseInt(container.getAttribute('data-commentid'), 10);

            widget._bestAnswerResponse([
                { commentID: commentID, types: {3: true} },
                { commentID: parseInt(other.getAttribute('data-commentid'), 10), types: {2: true} }
            ], new RightNow.Event.EventObject(null, { data: { commentID: commentID, chosenByType: 'Moderator' }}));

            Y.assert(container.hasClass('rn_BestAnswer'));
            Y.assert(other.hasClass('rn_BestAnswer'));
            Y.Assert.areSame(2, Y.all('.rn_CommentContainer.rn_BestAnswer').size());
        },

        "Best answer indication is removed from other comment when it was chosen by the same user": function() {
            var container = Y.one('.rn_CommentContainer.rn_BestAnswer'),
                commentID = parseInt(container.getAttribute('data-commentid'), 10);

            widget._bestAnswerResponse(
                [{ commentID: commentID, types: {2: true} }],
                new RightNow.Event.EventObject(null, { data: { commentID: commentID, chosenByType: 'Moderator' }})
            );

            Y.assert(container.hasClass('rn_BestAnswer'));
            Y.Assert.areSame(1, Y.all('.rn_CommentContainer.rn_BestAnswer').size());
        }
    }));

    return suite;
}).run();