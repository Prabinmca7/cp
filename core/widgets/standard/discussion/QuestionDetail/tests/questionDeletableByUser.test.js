/**
 * Synopsis:
 * - Logged-in social user who is authorized to update and delete.
 * - Verify user sees delete button
 */
UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'QuestionDetail_0',
    subInstanceIDs: ['FileListDisplay_8']
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/discussion/QuestionDetail - Deleting functionality tests with an authorized user"
    });

    suite.add(new Y.Test.Case({
        name: "Deleting",

        "Check for delete button": function() {
            var editButton = Y.one('.rn_EditQuestionLink'), deleteButton;

            editButton.simulate('click');
            deleteButton = Y.one('.rn_DeleteQuestion');
            Y.Assert.isNotNull(deleteButton);
        }
    }));

    return suite;
}).run();
