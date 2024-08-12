UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'LoginDialog_0'
}, function(Y, widget, baseSelector){
    var loginDialogTests = new Y.Test.Suite({
        name: "standard/login/LoginDialog",
        setUp: function(){
            var testExtender = {
                getDialogButtons: function(){
                    return Y.all('#rnDialog1 .yui3-widget-ft button');
                }
            };

            for(var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    loginDialogTests.add(new Y.Test.Case({
        name: 'Password tests',
        "Leading and trailing spaces should be preserved in password field": function() {
            Y.one('#rn_LoginLink').simulate('click');
            this.buttons = this.getDialogButtons();

            var passwordIsIntact = false,
                user = 'joe',
                password = ' spacepreandpost ',
                usernameField = Y.one(widget.baseSelector + '_Username'),
                passwordField = Y.one(widget.baseSelector + '_Password');

            usernameField.set('value', user);
            passwordField.set('value', password);

            RightNow.Event.subscribe('evt_loginFormSubmitRequest', function(eventName, eventData){
                passwordIsIntact = (password === eventData[0].data.password);
                return false;
            }, this);

            this.buttons.item(0).simulate('click');
            Y.Assert.isTrue(passwordIsIntact);
        }
    }));

    return loginDialogTests;
});
UnitTest.run();

