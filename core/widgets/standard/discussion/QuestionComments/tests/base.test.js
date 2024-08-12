UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'QuestionComments_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/discussion/QuestionComments - Base functionality tests"
    });

    suite.add(new Y.Test.Case({
        name: "Ajax Methods",

        verifyErrorStatuses: function(errorMessage, shouldDisplay) {
            var responseHandler = widget._ajaxResponse('evt_newComment', function() {
                Y.fail();
            });

            responseHandler.call(widget, {"errors":[{"errorCode": errorMessage}]});
            shouldDisplay ? Y.Assert.isNotNull(Y.one('.rn_BannerAlert')) : Y.Assert.isNull(Y.one('.rn_BannerAlert'));
        },

        "Check banner display with various errors": function() {
            this.verifyErrorStatuses("ERROR_USER_NOT_LOGGED_IN", false);
            this.verifyErrorStatuses("ERROR_USER_HAS_NO_SOCIAL_USER", false);
            this.verifyErrorStatuses("ERROR_USER_HAS_BLANK_SOCIAL_USER", false);
            this.verifyErrorStatuses("UNRELATED_ERROR", true);
        },

        "Check if the sorting toggles on click": function() {
            widget._sortCommentsResponse(null, {"data":[{sortOrder: 'ASC', pageID: 1}]});
            Y.Assert.isFalse(Y.one(baseSelector + ' .rn_SortOrder .rn_OldestButton').hasClass("rn_Disabled"));
            Y.Assert.isTrue(Y.one(baseSelector + ' .rn_SortOrder .rn_NewestButton').hasClass("rn_Disabled"));
            Y.Assert.areSame(widget.data.attrs.comments_sort_order, "DESC");
            widget.data.attrs.comments_sort_order = "ASC";
        }
    }));

    suite.add(new Y.Test.Case({
        name: "Reading position",

        _should: {
            error: {
                "Scrolling to a comment updates the URL and doesn't modify the history": Y.UA.ie && Y.UA.ie < 10
            }
        },

        setUp: function () {
            document.body.scrollTop = 0;
            this.comments = Y.all(baseSelector + ' .rn_Comments > .rn_CommentContainer');
        },

        tearDown: function () {
            document.body.scrollTop = 0;
            if ('replaceState' in window.history) {
                window.history.replaceState({}, "", window.location.pathname.replace(/\/comment\/\d+/, ""));
            }
        },

        commentInUrl: function (commentID) {
            var match = window.location.pathname.match(/\/comment\/(\d+)/);
            return match ? match[1] : null;
        },

        // Commenting out because... Cruise Control
        // "Scrolling to a comment updates the URL and doesn't modify the history": function () {
        //     var first = this.comments.item(0),
        //         histLength = window.history.length;
        //     document.body.scrollTop = first.get('offsetTop');
        //     Y.one(document.body).simulate('scroll');

        //     Y.Assert.areSame(first.getAttribute('data-commentid'), this.commentInUrl());
        //     Y.Assert.areSame(histLength, window.history.length);
        // },

        "evt_jumpToComment event causes widget to scroll and update the URL": function () {
            var second = this.comments.item(1);
            var commentID = second.getAttribute('data-commentid');

            RightNow.Event.fire('evt_jumpToComment', new RightNow.Event.EventObject(null, { data: { commentID: commentID }}));
            Y.Assert.areSame(commentID, this.commentInUrl(commentID));
        },

        "An AJAX request is made to fetch a comment page if it's not found when global evt_jumpToComment event is fired": function () {
            RightNow.Ajax.makeRequest = function () {
                RightNow.Ajax.makeRequest.calledWith = Array.prototype.slice.call(arguments);
            };

            RightNow.Event.fire('evt_jumpToComment', new RightNow.Event.EventObject(null, { data: { commentID: 'boomerang' }}));

            Y.Assert.areSame(widget.data.attrs.fetch_page_with_comment_ajax, RightNow.Ajax.makeRequest.calledWith[0]);
            Y.Assert.areSame('boomerang', RightNow.Ajax.makeRequest.calledWith[1].commentID);
            Y.Assert.isTrue(Y.one(baseSelector + ' .rn_Comments').hasClass('rn_Loading'));
        },

        "Hide and show child comments": function () {
            var replies = Y.one(baseSelector + ' .rn_ReplyTitle');

            // Hide child comments
            replies.simulate('click');
            Y.Assert.isTrue(replies.ancestor().hasClass('rn_Collapsed'));

            // Show child comments
            replies.simulate('click');
            Y.Assert.isFalse(replies.ancestor().hasClass('rn_Collapsed'));
        }
    }));

    return suite;
}).run();
