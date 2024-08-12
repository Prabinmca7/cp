/**
 * Synopsis:
 * - Logged-in social user who is the author of the question / comment.
 */

UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'SocialContentRating_0'
}, function (Y, widget, baseSelector) {

    Y.one(document.head).appendChild('<style type="text/css">.yui3-panel-hidden{display:none;}</style>');

    var suite = new Y.Test.Suite({
        name: "standard/feedback/SocialContentRating"
    });

    suite.add(new Y.Test.Case({
        name: "UI Behavior For Updown Voting",

        setUp: function () {
            this.button = Y.all('.rn_RatingButtons button');
            this.resetButton = Y.one(".rn_ResetButton");
            this.ratingValue = Y.one(".rn_RatingValue");

            this.makeRequest = RightNow.Ajax.makeRequest;
            this.displayBanner = RightNow.UI.displayBanner;

            RightNow.Ajax.makeRequest = function () {
                RightNow.Ajax.makeRequest.calledWith = Array.prototype.slice.call(arguments);
            };
            RightNow.UI.displayBanner = function () {
                RightNow.UI.displayBanner.calledWith = Array.prototype.slice.call(arguments);
                return { on: function () {} };
            };
        },

        tearDown: function () {
            RightNow.Ajax.makeRequest = this.makeRequest;
            RightNow.UI.displayBanner = this.displayBanner;
        },

        getRatingValue: function () {
            return Y.one(baseSelector + ' .rn_RatingValueNumerical').getHTML();
        },

        "Values of updown voting buttons": function () {
            Y.Assert.areSame("0.4", this.button.item(1).get('value'));
            Y.Assert.areSame("2", this.button.item(0).get('value'));
        },

        "Successful rating is submitted to the server": function () {
            this.button.item(0).simulate('click');

            Y.Assert.areSame(this.button.item(0).get('title'), widget.data.attrs.label_upvote_hint);
            Y.Assert.areSame(widget.data.attrs.label_be_first_to_vote, this.getRatingValue().trim());
            Y.assert(this.button.get('disabled'));

            var expectedArgs = RightNow.Ajax.makeRequest.calledWith;
            Y.Assert.areSame(widget.data.attrs.submit_vote_ajax, expectedArgs[0]);
            // Call the callback.
            expectedArgs[2].successHandler.call(expectedArgs[2].scope, { ratingID: 'young', totalRatingLabel:{totalVotes: 1, label: 'Rating: +0/-1 (1 user)'}});
            // Button is updated.
            Y.Assert.areSame(this.button.item(0).get('title'), widget.data.attrs.label_upvote_thanks);
            // And rating is reflected.
            Y.Assert.areSame('Rating: +0/-1 (1 user)', this.getRatingValue());
	    Y.Assert.areSame(this.ratingValue.get('title'), widget.data.attrs.label_vote_count_singular);
        },

        "Rating is not submitted to the server if it's already selected": function () {
            widget._baseNode.addClass('rn_Voted');
            this.button.item(0).simulate('click');
            Y.assert(!RightNow.Ajax.makeRequest.calledWith);
        },

        "Successful reset of rating is submitted to the server": function () {
            Y.Assert.areSame(this.resetButton.get('title'), widget.data.attrs.label_vote_reset_title);

            this.resetButton.simulate('click');

            var expectedArgs = RightNow.Ajax.makeRequest.calledWith;
            Y.Assert.areSame(widget.data.attrs.submit_vote_ajax, expectedArgs[0]);

            // Call the callback.
            expectedArgs[2].successHandler.call(expectedArgs[2].scope, { ratingReset: true, totalRatingLabel:{totalVotes: 0, label: 'Be the first to vote'}});
            // Button is updated.
            // And rating is reflected.
            Y.Assert.areSame('Be the first to vote', this.getRatingValue());
        }
    }));

    return suite;
}).run();

