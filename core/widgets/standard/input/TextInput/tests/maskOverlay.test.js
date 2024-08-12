UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'TextInput_0'
}, function(Y, widget, baseSelector){
    var textInputTests = new Y.Test.Suite({
        name: 'standard/input/TextInput'
    });

    textInputTests.add(new Y.Test.Case({
        name: 'test mask overlay',

        testMaskOverlay: function() {
            var mask = Y.one('.rn_MaskOverlay'),
                input = Y.one(baseSelector + '_' + widget.data.js.name.replace(/\./g, "\\."));

            // mask should not contain any information
            Y.Assert.areEqual("", mask.get('innerHTML'));

            // set a value too long for the mask
            input.set("value", "some random string");
            input.focus();

            // make sure the mask is updated
            if (!Y.UA.gecko)
                Y.Assert.areNotEqual("", mask.get('innerHTML'));
        }

    }));
    return textInputTests;
});
UnitTest.run();
