UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'AnswerNotificationManager_0',
}, function(Y, widget, baseSelector){
    var suite = new Y.Test.Suite({
        name: "standard/notifications/AnswerNotificationManager"
    });

    suite.add(new Y.Test.Case({
        name: "Attribute testing",
        "Custom message container displays error message": function() {
            var message = "Unit Test Message";
            widget.displayMessage(message);
            Y.Assert.areEqual(Y.one('#' + widget.data.attrs.message_element).get('text'), message);
        }
    }));

    return suite;
}).run();
