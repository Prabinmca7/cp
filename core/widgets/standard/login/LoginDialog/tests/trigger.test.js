UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'LoginDialog_0'
}, function(Y, widget, baseSelector){
    var loginDialogTests = new Y.Test.Suite({
        name: "standard/login/LoginDialog",

        setUp: function(){
            var testExtender = {
                initValues : function() {
                    if(!this.instanceID){
                        this.instanceID = 'LoginDialog_0';
                        this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                        this.triggerElement = document.getElementById('rn_LoginLink');
                    }
                    this.usernameField = document.getElementById('rn_' + this.instanceID + '_Username');
                    this.passwordField = document.getElementById('rn_' + this.instanceID + '_Password');
                    this.errorLocation = document.getElementById('rn_' + this.instanceID + '_LoginErrorMessage');
                    this.usernameField.value = "";
                    this.passwordField.value = "";
                    this.errorLocation.innerHTML = "";
                },

                getDialogButtons: function(){
                    return Y.all('#rnDialog1 .yui3-widget-ft button');
                }
            };

            for(var item in this.items)
            {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    loginDialogTests.add(new Y.Test.Case(
    {
        name: "UI functional tests",

        testTogglingDialog: function(){
            this.initValues();

            Y.Assert.isTrue(Y.one('#rn_' + this.instanceID).hasClass('rn_Hidden'));
            Y.one(this.triggerElement).simulate('click');
            Y.Assert.isTrue(this.instance._dialog.cfg.getProperty('visible'));
            Y.one(this.getDialogButtons().item(1)).simulate('click');
            Y.Assert.isFalse(this.instance._dialog.cfg.getProperty('visible'));
        },

        /**
         * Tests the link by clicking on it to open the email form and then provides valid
         * data and presses the submit button. It is subscribed to relevant actions for which
         * it checks the returned data to verify the functionality of the widget.
         *
         * Note that currently if the widget does not show up on the page this test will automatically pass
         */
        testInvalidInput: function() {
            this.initValues();

            Y.one(this.triggerElement).simulate('click');
            var dialogButtons = this.getDialogButtons();
            this.usernameField.value = "with spaces";
            dialogButtons.item(0).simulate('click');

            var errorLink = this.errorLocation.childNodes[1];
            Y.Assert.areSame('A', errorLink.tagName);
            Y.Assert.areSame('javascript:void(0);', errorLink.href);
            Y.Assert.areSame('Username must not contain spaces.', errorLink.innerHTML);

            this.usernameField.value = 'with"quotes';

            dialogButtons.item(0).simulate('click');

            errorLink = this.errorLocation.childNodes[1];
            Y.Assert.areSame('A', errorLink.tagName);
            Y.Assert.areSame('javascript:void(0);', errorLink.href);
            Y.Assert.areSame('Username must not contain double quotes.', errorLink.innerHTML);

            this.usernameField.value = 'with<brackets';

            dialogButtons.item(0).simulate('click');

            errorLink = this.errorLocation.childNodes[1];
            Y.Assert.areSame('A', errorLink.tagName);
            Y.Assert.areSame('javascript:void(0);', errorLink.href);
            Y.Assert.areSame("Username must not contain either '&lt;' or '&gt;'", errorLink.innerHTML);

            this.usernameField.value = 'with>brackets';

            dialogButtons.item(0).simulate('click');

            errorLink = this.errorLocation.childNodes[1];
            Y.Assert.areSame('A', errorLink.tagName);
            Y.Assert.areSame('javascript:void(0);', errorLink.href);
            Y.Assert.areSame("Username must not contain either '&lt;' or '&gt;'", errorLink.innerHTML);
            dialogButtons.item(1).simulate('click');
        },

        /**
         * Opens the email form by clicking on the link and then verfies that pressing the cancel button
         * will close the form.
         *
         * Note that currently if the widget does not show up on the page this test will automatically pass
         */
        testInvalidCredentials: function() {
            this.initValues();
            Y.one(this.triggerElement).simulate('click');
            this.usernameField.value = "gesobnasegh";
            this.passwordField.value = "aennoaseg";

            var hasCalledRequest = false;
            RightNow.Event.subscribe('evt_loginFormSubmitRequest', function(eventName, eventData){
                if (hasCalledRequest) return;

                hasCalledRequest = true;
                Y.Assert.areSame('evt_loginFormSubmitRequest', eventName);
                Y.Assert.isInstanceOf(RightNow.Event.EventObject, eventData[0]);
                eventData = eventData[0].data;
                Y.Assert.areSame('gesobnasegh', eventData.login);
                Y.Assert.areSame('aennoaseg', eventData.password);
                Y.Assert.areSame(0, eventData.w_id);
                Y.Assert.areSame(window.location.pathname, eventData.url);
            }, this);

            var hasCalledResponse = false;
            RightNow.Event.subscribe("evt_loginFormSubmitResponse", function(eventName, eventData){
                if (hasCalledResponse) return;

                this.resume(function() {
                    hasCalledResponse = true;
                    Y.Assert.areSame('evt_loginFormSubmitResponse', eventName);
                    Y.Assert.isObject(eventData[0]);
                    eventData = eventData[0];
                    Y.Assert.isObject(eventData.data);
                    Y.Assert.isObject(eventData.response);
                    var serverResponse = eventData.response;
                    var originalEventData = eventData.data.data;

                    Y.Assert.areSame('gesobnasegh', originalEventData.login);
                    Y.Assert.areSame('aennoaseg', originalEventData.password);
                    Y.Assert.areSame(0, originalEventData.w_id);
                    Y.Assert.areSame(window.location.pathname, originalEventData.url);

                    Y.Assert.areSame("The username or password you entered is incorrect or your account has been disabled.", serverResponse.message);
                    Y.Assert.areSame(0, serverResponse.success);
                    Y.Assert.areSame(window.location.pathname, serverResponse.url);
                    Y.Assert.areSame("0", serverResponse.w_id);
                    Y.Assert.isBoolean(serverResponse.addSession);
                    Y.Assert.isString(serverResponse.sessionParm);
                    RightNow.UI.Dialog.enableDialogControls(this.instance._dialog, this.instance._keyListener);
                    this.getDialogButtons().item(1).simulate('click');
                });
            }, this);
            this.getDialogButtons().item(0).simulate('click');
            this.wait();
            Y.Assert.isTrue(hasCalledRequest);
            Y.Assert.isTrue(hasCalledResponse);
        }
    }));

    loginDialogTests.add(new Y.Test.Case({
        name: 'Loading behavior',

        setUp: function() {
            this.origMakeRequest = RightNow.Ajax.makeRequest;
            this.origNavigate = RightNow.Url.navigate;
            RightNow.Url.navigate = function() {};
        },

        tearDown: function() {
            RightNow.Ajax.makeRequest = this.origMakeRequest;
            RightNow.Url.navigate = this.origNavigate;
        },

        'Loading indicators and inputs are properly turned off and on during an unsuccessful login attempt': function() {
            this.initValues();

            Y.all('input').set('value', 'banana@bar.foo');

            var tested;

            RightNow.Ajax.makeRequest = function(url, data, options) {
                Y.assert(Y.all('.yui3-widget-ft .yui3-widget-buttons button').get('disabled').toString().indexOf('true,true') > -1);
                if (Y.UA.ie || Y.UA.ie  > 8) {
                    Y.assert(!Y.one(baseSelector).hasClass('rn_ContentLoading'));
                }
                else {
                    Y.assert(Y.one(baseSelector).hasClass('rn_ContentLoading'));
                }

                options.successHandler.call(options.scope, {success: false});

                Y.assert(Y.all('input').getAttribute('disabled').toString().indexOf('true,true') === -1);
                Y.assert(Y.all('.yui3-widget-ft .yui3-widget-buttons button').get('disabled').toString().indexOf('true,true') === -1);

                Y.assert(!Y.one(baseSelector).hasClass('rn_ContentLoading'));

                tested = true;
            };
            Y.one('.yui3-widget-ft .yui3-widget-buttons').one('button').simulate('click');

            Y.assert(tested);
        },

        'Loading indicators and inputs are properly turned off and on during a successful login attempt': function() {
            this.initValues();

            Y.all('input').set('value', 'banana@bar.foo');

            var tested;

            RightNow.Ajax.makeRequest = function(url, data, options) {
                Y.assert(Y.all('input').getAttribute('disabled').toString().indexOf('true,true') > -1);
                Y.assert(Y.all('.yui3-widget-ft .yui3-widget-buttons button').get('disabled').toString().indexOf('true,true') === 0);
                if (Y.UA.ie || Y.UA.ie  > 8) {
                    Y.assert(!Y.one(baseSelector).hasClass('rn_ContentLoading'));
                }
                else {
                    Y.assert(Y.one(baseSelector).hasClass('rn_ContentLoading'));
                }

                options.successHandler.call(options.scope, {success: true, sessionParm: ''});

                Y.Assert.areSame(0, Y.all(baseSelector + ' input').size());
                Y.assert(Y.all('.yui3-widget-ft .yui3-widget-buttons button').get('disabled').toString().indexOf('true,true') === 0);

                Y.assert(!Y.one(baseSelector).hasClass('rn_ContentLoading'));

                tested = true;
            };
            Y.one('.yui3-widget-ft .yui3-widget-buttons').one('button').simulate('click');

            Y.assert(tested);
        }
    }));

    return loginDialogTests;
});
UnitTest.run();
