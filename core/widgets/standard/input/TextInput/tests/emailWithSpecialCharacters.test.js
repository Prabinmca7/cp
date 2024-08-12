UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'TextInput_0'
}, function(Y, widget, baseSelector){
    var suite = new Y.Test.Suite({name: 'standard/input/TextInput'});

    suite.add(new Y.Test.Case({
        name: 'Test check exists',
        setUp: function() {
            this.originalMakeRequest = RightNow.Ajax.makeRequest;
        },
        tearDown: function() {
            RightNow.Ajax.makeRequest = this.originalMakeRequest;
        },
        'Previously seen e-mail with special characters should not trigger already exists dialog': function() {
            widget.validate();
            Y.Assert.areSame(widget._value, "john'hacker@example.com");
            Y.Assert.areSame(widget.data.js.previousValue, "john&#039;hacker@example.com");

            var madeRequest = false;
            RightNow.Ajax.makeRequest = function() {
                madeRequest = true;
            };
            widget._checkExistingAccount();
            Y.Assert.isFalse(madeRequest, "The e-mail values should be the same and a request should not be made.");
        }
    }));
    return suite;
}).run();
