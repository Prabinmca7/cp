/**
 * Synopsis:
 * - Logged-in social user who is a moderator.
 */

UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'QuestionDetail_0',
    subInstanceIDs: ['FileListDisplay_8']
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/discussion/QuestionDetail - Moderator"
    });

    suite.add(new Y.Test.Case({
        name: "Editing",

        tearDown: function() {
            var cancelEdit = Y.one('.rn_CancelEdit a');
            if (!cancelEdit.hasClass('rn_Hidden')) cancelEdit.simulate('click');
        },

        clickEdit: function() {
            Y.one('.rn_EditQuestionLink').simulate('click');
        },

        "Form is shown when edit button is clicked": function() {
            var form = Y.one(baseSelector + ' form');
            Y.assert(form.ancestor('.rn_Hidden'));
            this.clickEdit();
            Y.assert(!form.ancestor('.rn_Hidden'));
        },

        "Input fields for body and title are not present in the form": function() {
            this.clickEdit();
            Y.assert(!Y.one('[name="Question\\.Title"]'));
            Y.assert(!Y.one('[name="Question\\.Body"]'));
        }
    }));

    return suite;
}).run();
