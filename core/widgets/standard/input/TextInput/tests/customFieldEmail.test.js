UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'TextInput_0'
}, function(Y, widget, baseSelector){
    var textInputTests = new Y.Test.Suite({
        name: 'standard/input/TextInput'
    });

    textInputTests.add(new Y.Test.Case({
        name: 'input email validation',

        testValidateCustomFieldEmail: function() {
            var input = Y.one(baseSelector + '_' + widget.data.js.name.replace(/\./g, "\\."));
            Y.Assert.areSame(true, widget.data.js.email);
            Y.Assert.isTrue(typeof widget.data.js.url === 'undefined');
        }

    }));
    return textInputTests;
});
UnitTest.run();
