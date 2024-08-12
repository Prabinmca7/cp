UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'LoginDialog_0'
}, function(Y, widget, baseSelector){
    var suite = new Y.Test.Suite({
        name: "standard/login/LoginDialog - Create Account Functionality",
        setUp: function() {
            var testExtender = {
                getDialogButtons: function(){
                    return Y.all('#rnDialog1 .yui3-widget-ft button');
                },
                clickTrigger: function() {
                    Y.one('#' + widget.data.attrs.trigger_element).simulate('click');
                },
                clickOK: function() {
                    this.getDialogButtons().item(0).simulate('click');
                },
                clickCancel: function() {
                    this.getDialogButtons().item(1).simulate('click');
                },
                verifyErrorLink: function(message) {
                    Y.Assert.areSame(1, this.errorLocation.all('*').size());
                    var link = this.errorLocation.one('a');
                    Y.Assert.areSame('javascript:void(0);', link.get('href'));
                    Y.Assert.areSame(message, Y.Lang.trim(link.getHTML()));
                }
            };

            for (var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    suite.add(new Y.Test.Case({
        name: "UI functional tests for create account functionality",

        setUp: function() {
            this.clickTrigger();
        },

        tearDown: function() {
            this.clickCancel();
        },

        "Login and create forms toggle when clicking on the link to switch between": function() {
            var toggle = Y.one(baseSelector + '_FormTypeToggle');
            Y.Assert.areSame(widget.data.attrs.label_create_account_button + widget.data.attrs.label_opens_new_dialog, toggle.get('text'));

            // login → create account
            toggle.simulate('click');
            Y.Assert.areSame(widget.data.attrs.label_login_button, toggle.getHTML());
            Y.assert(Y.one(baseSelector + '_LoginContent').hasClass('rn_Hidden'));
            Y.assert(!Y.one(baseSelector + '_SignUpContent').hasClass('rn_Hidden'));

            // create account → login
            toggle.simulate('click');
            Y.Assert.areSame(widget.data.attrs.label_create_account_button, toggle.getHTML());
            Y.assert(!Y.one(baseSelector + '_LoginContent').hasClass('rn_Hidden'));
            Y.assert(Y.one(baseSelector + '_SignUpContent').hasClass('rn_Hidden'));
        },

        "Dialog always goes back to showing the login form when closed and reopened": function(){
            var toggle = Y.one(baseSelector + '_FormTypeToggle');
            Y.Assert.areSame(widget.data.attrs.label_create_account_button, toggle.getHTML());

            // login → create account
            toggle.simulate('click');
            Y.Assert.areSame(widget.data.attrs.label_login_button, toggle.getHTML());
            Y.assert(Y.one(baseSelector + '_LoginContent').hasClass('rn_Hidden'));
            Y.assert(!Y.one(baseSelector + '_SignUpContent').hasClass('rn_Hidden'));

            // Close and re-open dialog
            this.clickCancel();
            this.clickTrigger();

            // Once again displaying login form
            Y.Assert.areSame(widget.data.attrs.label_create_account_button, toggle.getHTML());
            Y.assert(!Y.one(baseSelector + '_LoginContent').hasClass('rn_Hidden'));
            Y.assert(Y.one(baseSelector + '_SignUpContent').hasClass('rn_Hidden'));
        },

        "Dialog displays the login form on demand with 'evt_requireLogin' event": function() {
            var toggle = Y.one(baseSelector + '_FormTypeToggle');

            // login → create account
            toggle.simulate('click');
            Y.Assert.areSame(widget.data.attrs.label_login_button, toggle.getHTML());
            Y.Assert.areSame(Y.one(baseSelector + '_FormTypeLabel').getHTML(), widget.data.attrs.label_create_account_button);
            Y.assert(Y.one(baseSelector + '_LoginContent').hasClass('rn_Hidden'));
            Y.assert(!Y.one(baseSelector + '_SignUpContent').hasClass('rn_Hidden'));

            this.clickCancel();

            RightNow.Event.fire('evt_requireLogin');

            // Once again displaying login form
            Y.Assert.areSame(widget.data.attrs.label_create_account_button, toggle.getHTML());
            Y.Assert.areSame(Y.one(baseSelector + '_FormTypeLabel').getHTML(), widget.data.attrs.label_login_button);
            Y.assert(!Y.one(baseSelector + '_LoginContent').hasClass('rn_Hidden'));
            Y.assert(Y.one(baseSelector + '_SignUpContent').hasClass('rn_Hidden'));
        }
    }));

    return suite;
}).run();
