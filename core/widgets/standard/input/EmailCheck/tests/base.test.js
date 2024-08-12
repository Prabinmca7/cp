UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'EmailCheck_0'
}, function(Y, widget, baseSelector){
    var emailCheckTests = new Y.Test.Suite({
        name: "standard/input/EmailCheck"
    });

    emailCheckTests.add(new Y.Test.Case(
    {
        name: "Event Handling and Operation",


        "An error dialog appears when email is empty and the form is submitted": function() {
            var input = Y.one(baseSelector + '_Email');
            input.set('value', '');

            // test initial focus
            if(widget.data.attrs.initial_focus) {
                Y.Assert.areSame(input.getDOMNode(), document.activeElement);
            }

            Y.one(baseSelector + '_Submit').simulate('click');
            Y.Assert.areSame(Y.one('#rn_Dialog_1_Message').get('innerHTML'), widget.data.attrs.label_warning);
            Y.one('#rnDialog1 button').simulate('click');
            Y.Assert.areSame(input.getDOMNode(), document.activeElement);
        },

        "The form should check if email exists": function() {
            var expectedPostData = {
                email: 'jimbob@rightnow.invalid',
                checkForChallenge: true,
                contactToken: UnitTest.NO_VALUE
            };

            Y.one(baseSelector + '_Email').set('value', 'jimbob@rightnow.invalid');

            UnitTest.overrideMakeRequest(widget.data.attrs.account_exists_ajax, expectedPostData);
            Y.one(baseSelector + '_Submit').simulate('click');
        }
    }));
    return emailCheckTests;
});
UnitTest.run();
