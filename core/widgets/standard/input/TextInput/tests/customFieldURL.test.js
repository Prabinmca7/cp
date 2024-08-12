UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'TextInput_0'
}, function(Y, widget, baseSelector){
    var textInputTests = new Y.Test.Suite({
        name: 'standard/input/TextInput'
    });

    textInputTests.add(new Y.Test.Case({
        name: 'input URL validation',

        testValidateCustomFieldURL: function() {
            Y.Assert.areSame(true, widget.data.js.url);
            Y.Assert.isTrue(typeof widget.data.js.email === 'undefined');
        }

    }));
    return textInputTests;
});
UnitTest.run();
