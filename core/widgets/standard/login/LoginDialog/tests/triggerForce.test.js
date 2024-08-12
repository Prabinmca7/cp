UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: "LoginDialog_0"
}, function(Y, widget, baseSelector){
    var loginDialogTests = new Y.Test.Suite({
        name: "standard/login/LoginDialog - Trigger force"
    });

    loginDialogTests.add(new Y.Test.Case(
    {
        name: "UI functional tests",

        testTogglingDialog: function(){
            Y.Assert.isTrue(Y.one(baseSelector).hasClass("rn_Hidden"));
            Y.Assert.areSame("javascript:void(0);", Y.one("#rn_LoginLink").get("href"));
        }
    }));

    return loginDialogTests;
});
UnitTest.run();
