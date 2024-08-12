UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'OkcsEmailAnswerLink_0'
}, function(Y, widget, baseSelector){
    var okcsEmailAnswerLinkTests = new Y.Test.Suite({
        name: "standard/okcs/OkcsEmailAnswerLink",

        setUp: function(){
            var testExtender = {
                checkEventParameters: function(eventName, type, args) {
                    Y.Assert.areSame(eventName, type);
                    Y.Assert.isInstanceOf(RightNow.Event.EventObject, args);
                    Y.Assert.isObject(args.filters);
                    Y.Assert.areSame(widget.instanceID, args.w_id);
                    Y.Assert.areSame(widget.data.js.emailAnswerToken, args.data.emailAnswerToken);
                },
                openDialog: function() {
                    Y.one(baseSelector + '_Link').simulate('click');
                },
                submitDialog: function() {
                    Y.one('#rn_ActionDialog_Generated1').one('button').simulate('click');
                },
                setValueFor: function(value, trailSelector) {
                    return Y.one(baseSelector + '_Input' + trailSelector).set('value', value);
                },
                cancelDialog: function() {
                    Y.one('#rn_ActionDialog_Generated1').all('button').item(1).simulate('click');
                },
                dialogIsHidden: function() {
                    return Y.one('#rn_ActionDialog_Generated1').getStyle('display') === 'none';
                },
                dialogIsShown: function() {
                    return !this.dialogIsHidden();
                },
                errorMessage: function() {
                    return Y.one(baseSelector + '_ErrorMessage');
                },
                requestListener: function(type, args) {
                    this.checkEventParameters("evt_emailLinkRequest", type, args[0]);
                    this.eventArgs = args[0];

                    return false;
                }
            };

            for (var testCase in this.items) {
                Y.mix(this.items[testCase], testExtender);
                RightNow.Event.on('evt_emailLinkRequest', testExtender.requestListener, this.items[testCase]);
            }

        }
    });

    okcsEmailAnswerLinkTests.add(new Y.Test.Case({
        name: "UI functional tests",

        invalidEmails: [
            "",
            "test@somewhere.com;test@somewhereelse.com",
            "test@somewhere.com test@somewhereelse.com",
            "test@somewhare.com,test@somewhereelse.com",
            "thisisatestofvalidationthatistoolong@somewherethatprobablydoesnotexistinthisuniverse.com",
            "test"
        ],

        invalidNames: [
            "",
            "poor<",
            "<poor",
            "po < or",
            "Christopher>",
            ">Christopher",
            "Chri>stopher",
            "may'be",
            '"maybe',
            '"maybe"',
            "ro&mance"
        ],

        verifyValidation: function(trailSelector, testData) {
            Y.Array.each(testData, function(email) {
                Y.all('input').set('value', 'legit.email@fake.invalid');

                this.setValueFor(email, trailSelector);
                this.submitDialog();

                Y.assert(this.errorMessage().hasClass('rn_ErrorMessage'));
                Y.Assert.areSame(2, this.errorMessage().all('*').size());
            }, this);
        },

        setUp: function() {
            this.widgetData = widget.data;

            Y.all('input').set('value', '');
            Y.one(baseSelector + '_ErrorMessage').setHTML('').set('className', '');

            this.openDialog();
        },

        tearDown: function() {
            widget._closeDialog();
        },

        "Dialog shows when trigger is clicked and hides when its cancel button is clicked": function() {
            Y.assert(this.dialogIsShown());
            this.cancelDialog();
            Y.assert(this.dialogIsHidden());
        },

        "Dialog shows when the trigger is clicked, contains the correct inputs, and submits with correct values": function() {
            Y.assert(this.dialogIsShown());

            if (this.widgetData.js.isProfile) {
                Y.Assert.isNull(Y.one(baseSelector + '_InputSenderName'));
                Y.Assert.isNull(Y.one(baseSelector + '_InputSenderEmail'));
            }
            else {
                this.setValueFor("Jim FooBar", 'SenderName');
                this.setValueFor("foo@bar.com", 'SenderEmail');
            }
            this.setValueFor("bar@foo.com", 'RecipientEmail');

            this.submitDialog();

            Y.assert(this.dialogIsShown());
        },

        "Sender email is validated": function() {
            this.verifyValidation('SenderEmail', this.invalidEmails);
        },

        "Recipient email is validated": function() {
            this.verifyValidation('RecipientEmail', this.invalidEmails);
        },

        "Sender name is validated": function() {
            this.verifyValidation('SenderName', this.invalidNames);
        },

    }));

    okcsEmailAnswerLinkTests.add(new Y.Test.Case({
        name: "Server response handling tests",

        setUp: function() {
            this.openDialog();
        },

        tearDown: function() {
            widget._closeDialog();
        },

        "The dialog is closed and no popup is displayed when there's an ajax error": function() {
            widget._onResponseReceived({ ajaxError: true });
            Y.assert(this.dialogIsHidden());
        },

        "Message from the server is displayed in a popup": function() {
            var selector = '#rn_ActionDialog_Generated2';
            widget._onResponseReceived("holla");

            Y.assert(this.dialogIsShown());
            Y.assert(Y.one(selector));
            Y.assert(Y.one(selector).get('text').indexOf('holla') > -1);

            Y.one(selector + ' button').simulate('click');

            Y.assert(Y.one(selector).getStyle('display') === 'none');
            Y.assert(this.dialogIsHidden());
        },

        "A response indicating an error displays the generic error message": function() {
            var selector = '#rn_ActionDialog_Generated3';
            widget._onResponseReceived({result: false, errors: ['Wrong!']});

            Y.assert(this.dialogIsShown());
            Y.assert(Y.one(selector));
            Y.assert(Y.one(selector).get('text').indexOf('There was an error with the request') > -1);

            Y.one(selector + ' button').simulate('click');

            Y.assert(Y.one(selector).getStyle('display') === 'none');
            Y.assert(this.dialogIsHidden());
        },

        "label_email_sent attribute appears in popup when there's no message from the server": function() {
            var selector = '#rn_ActionDialog_Generated4';
            widget._onResponseReceived({});

            Y.assert(this.dialogIsShown());
            Y.assert(Y.one(selector));
            Y.assert(Y.one(selector).get('text').indexOf(widget.data.attrs.label_email_sent) > -1);

            Y.one(selector + ' button').simulate('click');

            Y.assert(Y.one(selector).getStyle('display') === 'none');
            Y.assert(this.dialogIsHidden());
        }
    }));

    return okcsEmailAnswerLinkTests;
});
UnitTest.run();
