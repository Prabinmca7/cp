UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'TextInput_0',
}, function(Y, widget, baseSelector){
    var textInputTests = new Y.Test.Suite({
        name: 'standard/input/TextInput',
    });

    textInputTests.add(new Y.Test.Case({
        name: 'input validation',

        setUp: function() {
            this.origMakeRequest = RightNow.Ajax.makeRequest;
            RightNow.Ajax.makeRequest = function () {
                RightNow.Ajax.makeRequest.calledWith = Array.prototype.slice.call(arguments);
            };
        },

        tearDown: function() {
            RightNow.Ajax.makeRequest = this.origMakeRequest;
        },

        setInput: function(value) {
            this.inputNode = this.inputNode || Y.one(baseSelector + '_' + widget.data.js.name.replace(/\./g, "\\."));
            this.inputNode.set('value', value);
            widget._value = value;
        },

        "Using a login that already exists and causing a blur on the field throws an error dialog": function() {
            this.setInput('walkerj');
            widget._checkExistingAccount();
            Y.Assert.areSame(RightNow.Ajax.makeRequest.calledWith[0], '/ci/ajax/widget/standard/input/TextInput/existingContactCheck', 'Value: ' + widget._value + ' was incorrectly passed through.');

            var messageToCheck = 'Hello';
            widget._onAccountExistsResponse({message: messageToCheck}, null);
            Y.Assert.areSame(Y.one('#rn_Dialog_1_Message').get('innerHTML'), messageToCheck);
        },

        "Submitting a value won't cause an error when the field blurs on response": function() {
            this.setInput('batman');

            // Mock a submission
            widget.onValidate(null, [{data: {errorLocation: null, value: widget._value}}]);
            // Mock the response blur event
            widget._checkExistingAccount();
            Y.Assert.isTrue(typeof RightNow.Ajax.makeRequest.calledWith === 'undefined', 'Value: ' + widget._value + ' was incorrectly classified as not having been seen.');
        }
    }));
    return textInputTests;
});
UnitTest.run();
