UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'AnswerNotificationIcon_0'
}, function(Y, widget, baseSelector){
    var answerNotificationIconTests = new Y.Test.Suite({
        name: "standard/notifications/AnswerNotificationIcon",

        setUp: function(){
        }
    });

    answerNotificationIconTests.add(new Y.Test.Case(
    {
        name: "Event Handling for disable",

        testDisable: function() {
            Y.Assert.areSame(this.button, undefined);
        }
    }));

    return answerNotificationIconTests;
});
UnitTest.run();
