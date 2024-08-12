/**
 * Synopsis:
 * - Logged-in social user who is the moderator.
 */

UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'QuestionDetail_0',
    subInstanceIDs: ['FileListDisplay_8']
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/discussion/QuestionDetail - Edit functionality tests"
    });

    function clickEdit() {
        Y.one('.rn_EditQuestionLink').simulate('click');
    }

    function exitEditMode() {
        var cancelEdit = Y.one('.rn_CancelEdit a');
        if (!cancelEdit.hasClass('rn_Hidden')) cancelEdit.simulate('click');
    }

    suite.add(new Y.Test.Case({
        name: "Editing",

        tearDown: exitEditMode,

        "QuestionHeader, body, InfoOptions and moderations toolbar are hidden when edit button is clicked": function() {
            var group = Y.all('.rn_QuestionHeader,.rn_QuestionBody,.rn_QuestionInfoOptions,.rn_QuestionToolbar');
            Y.Assert.areSame('false', Y.Array.dedupe(group.hasClass('rn_Hidden')).toString());
            clickEdit();
            Y.Assert.areSame('true', Y.Array.dedupe(group.hasClass('rn_Hidden')).toString());
        },
    }));

    return suite;
}).run();
