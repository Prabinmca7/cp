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
                    Y.one('#rnDialog1').one('.yui3-widget-ft button').simulate('click');
                },
                setValueFor: function(value, trailSelector) {
                    return Y.one(baseSelector + '_Input' + trailSelector).set('value', value);
                },
                cancelDialog: function() {
                    Y.one('#rnDialog1').all('.yui3-widget-ft button').item(1).simulate('click');
                },
                dialogIsHidden: function() {
                    return !!Y.one('#rnDialog1').ancestor('.yui3-panel-hidden');
                },
                dialogIsShown: function() {
                    return !this.dialogIsHidden();
                },
                errorMessage: function() {
                    return Y.one(baseSelector + '_ErrorMessage');
                },
                requestListener: function(type, args) {
                    this.checkEventParameters("evt_okcsEmailLinkRequest", type, args[0]);
                    this.eventArgs = args[0];

                    return false;
                }
            };

            for (var testCase in this.items) {
                Y.mix(this.items[testCase], testExtender);
                RightNow.Event.on('evt_okcsEmailLinkRequest', testExtender.requestListener, this.items[testCase]);
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

        "ENTER keypress on a form input submits the form": function() {
            Y.one('input').focus().simulate('keydown', { keyCode: RightNow.UI.KeyMap.ENTER });

            Y.assert(this.errorMessage().hasClass('rn_ErrorMessage'));
            Y.assert(this.errorMessage().all('*').size());
        },

        "ENTER keypress on an error link focuses on the erroneous field": function() {
            Y.one('input').focus().simulate('keydown', { keyCode: RightNow.UI.KeyMap.ENTER });

            this.errorMessage().one('a').focus();
            // Simulating a key event doesn't do the trick, but good ol' #click does.
            this.errorMessage().one('a').simulate('click');
            Y.Assert.areSame(document.activeElement, document.getElementsByTagName('input')[0]);
        }
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
            var selector = '#rnDialog2';
            widget._onResponseReceived("holla");

            Y.assert(this.dialogIsShown());
            Y.assert(Y.one(selector));
            Y.assert(Y.one(selector).get('text').indexOf('holla') > -1);

            Y.one(selector + ' .yui3-widget-ft button').simulate('click');

            Y.assert(Y.one(selector).ancestor('.yui3-panel-hidden'));
            Y.assert(this.dialogIsHidden());
        },

        "A response indicating an error displays the generic error message": function() {
            var selector = '#rnDialog3';
            widget._onResponseReceived({result: false, errors: ['Wrong!']});

            Y.assert(this.dialogIsShown());
            Y.assert(Y.one(selector));
            Y.assert(Y.one(selector).get('text').indexOf('There was an error with the request') > -1);

            Y.one(selector + ' .yui3-widget-ft button').simulate('click');

            Y.assert(Y.one(selector).ancestor('.yui3-panel-hidden'));
            Y.assert(this.dialogIsHidden());
        },

        "A response indicating an error displays custom error message": function() {
            var selector = '#rnDialog4';
            widget._onResponseReceived({result: false, errors: [{externalMessage: "Cannot find question"}]});

            Y.assert(this.dialogIsShown());
            Y.assert(Y.one(selector));
            Y.assert(Y.one(selector).get('text').indexOf('Cannot find question') > -1);

            Y.one(selector + ' .yui3-widget-ft button').simulate('click');

            Y.assert(Y.one(selector).ancestor('.yui3-panel-hidden'));
            Y.assert(this.dialogIsHidden());
        },

        "label_email_sent attribute appears in popup when there's no message from the server": function() {
            var selector = '#rnDialog5';
            widget._onResponseReceived({});

            Y.assert(this.dialogIsShown());
            Y.assert(Y.one(selector));
            Y.assert(Y.one(selector).get('text').indexOf(widget.data.attrs.label_email_sent) > -1);

            Y.one(selector + ' .yui3-widget-ft button').simulate('click');

            Y.assert(Y.one(selector).ancestor('.yui3-panel-hidden'));
            Y.assert(this.dialogIsHidden());
        }
    }));

    return okcsEmailAnswerLinkTests;
});
UnitTest.run();

