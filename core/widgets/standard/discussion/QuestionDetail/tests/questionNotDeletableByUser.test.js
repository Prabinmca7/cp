/**
 * Synopsis:
 * - Logged-in social user who is authorized to update but not delete.
 * - Verify user does not see delete button
 */
UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'QuestionDetail_0',
    subInstanceIDs: ['FileListDisplay_8']
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/discussion/QuestionDetail - Deleting functionality tests without an authorized user"
    });

    suite.add(new Y.Test.Case({
        name: "Deleting",

        checkBannerDisplay: function(errorMessage, shouldDisplay) {
            var errors = {"errors":[{"errorCode": errorMessage}]};

            widget._deleteQuestionResponse(errors, null);
            shouldDisplay ? Y.Assert.isNotNull(Y.one('.rn_BannerAlert')) : Y.Assert.isNull(Y.one('.rn_BannerAlert'));
        },

        "Check for delete button": function() {
            var editButton = Y.one('.rn_EditQuestionLink'), deleteButton;

            editButton.simulate('click');
            deleteButton = Y.one('.rn_DeleteQuestion');
            Y.Assert.isNull(deleteButton);
        },

        "Check banner display with various errors": function() {
            this.checkBannerDisplay("ERROR_USER_NOT_LOGGED_IN", false);
            this.checkBannerDisplay("ERROR_USER_HAS_NO_SOCIAL_USER", false);
            this.checkBannerDisplay("ERROR_USER_HAS_BLANK_SOCIAL_USER", false);
            this.checkBannerDisplay("UNRELATED_ERROR", true);
        }
    }));

    return suite;
}).run();
