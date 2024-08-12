UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'LoginForm_0'
}, function(Y, widget, baseSelector){
    var loginFormTests = new Y.Test.Suite({
        name: "standard/login/LoginForm",

        setUp: function(){
            var testExtender = {
                initValues : function() {
                    if(!this.instanceID) {
                        this.instanceID = 'LoginForm_0';
                        this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                        this.usernameField = Y.one('#rn_' + this.instanceID + '_Username');
                        this.passwordField = Y.one('#rn_' + this.instanceID + '_Password');
                        this.submitButton = Y.one('#rn_' + this.instanceID + '_Submit');
                        this.errorLocation = Y.one('#rn_' + this.instanceID + '_ErrorMessage');
                    }
                    this.usernameField.set('value', '');
                    this.passwordField.set('value', '');
                    this.errorLocation.set('innerHTML', '');
                }
            };

            for(var item in this.items)
            {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    loginFormTests.add(new Y.Test.Case(
    {
        name: "UI functional tests",
        'Test form validation when clicking submit': function() {
            this.initValues();

            this.usernameField.set('value', 'with spaces');
            this.submitButton.simulate('click');
            var errorLink = this.errorLocation.one('a');
            Y.Assert.areSame('A', errorLink.get('tagName'));
            Y.Assert.areSame('javascript:void(0);', errorLink.get('href'));
            Y.Assert.areSame('Username must not contain spaces.', errorLink.get('innerHTML'));

            this.usernameField.set('value', 'with"quotes');
            Y.one(this.submitButton).simulate('click');
            errorLink = this.errorLocation.one('a');
            Y.Assert.areSame('A', errorLink.get('tagName'));
            Y.Assert.areSame('javascript:void(0);', errorLink.get('href'));
            Y.Assert.areSame('Username must not contain double quotes.', errorLink.get('innerHTML'));

            this.usernameField.set('value', 'with<brackets');
            Y.one(this.submitButton).simulate('click');
            errorLink = this.errorLocation.one('a');
            Y.Assert.areSame('A', errorLink.get('tagName'));
            Y.Assert.areSame('javascript:void(0);', errorLink.get('href'));
            Y.Assert.areSame("Username must not contain either '<' or '>'", errorLink.get('textContent'));

            this.usernameField.set('value', 'with>brackets');
            Y.one(this.submitButton).simulate('click');
            errorLink = this.errorLocation.one('a');
            Y.Assert.areSame('A', errorLink.get('tagName'));
            Y.Assert.areSame('javascript:void(0);', errorLink.get('href'));
            Y.Assert.areSame("Username must not contain either '<' or '>'", errorLink.get('textContent'));
        },

        /**
         * Opens the email form by clicking on the link and then verifies that pressing the cancel button
         * will close the form.
         *
         * Note that currently if the widget does not show up on the page this test will automatically pass
         */
        testInvalidCredentials: function() {
            this.initValues();

            this.usernameField.set('value', 'gesobnasegh');
            this.passwordField.set('value', 'aennoaseg');

            var hasCalledRequest = false;
            RightNow.Event.subscribe('evt_loginFormSubmitRequest', function(evtName, args) {
                if (hasCalledRequest) return;

                hasCalledRequest = true;
                Y.Assert.areSame('evt_loginFormSubmitRequest', evtName);
                Y.Assert.isInstanceOf(RightNow.Event.EventObject, args[0]);
                args = args[0].data;
                Y.Assert.areSame('gesobnasegh', args.login);
                Y.Assert.areSame('aennoaseg', args.password);
                Y.Assert.areSame(0, args.w_id);
                Y.Assert.areSame(window.location.pathname, args.url);
            }, this);

            var hasCalledResponse = false;
            RightNow.Event.subscribe("evt_loginFormSubmitResponse", function(evtName, args){
                if (hasCalledResponse) return;

                this.resume(function() {
                    hasCalledResponse = true;
                    Y.Assert.areSame('evt_loginFormSubmitResponse', evtName);
                    Y.Assert.isObject(args[0]);
                    args = args[0];
                    Y.Assert.isObject(args.data);
                    Y.Assert.isObject(args.response);
                    var serverResponse = args.response;
                    var originalEventData = args.data.data;

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
                });
            }, this);

            this.submitButton.simulate('click');
            this.wait(); //Wait for the server response
            Y.Assert.isTrue(hasCalledRequest);
            Y.Assert.isTrue(hasCalledResponse);
        }
    }));


    loginFormTests.add(new Y.Test.Case({
        name: 'Loading behavior',

        setUp: function() {
            this.origMakeRequest = RightNow.Ajax.makeRequest;
        },

        tearDown: function() {
            RightNow.Ajax.makeRequest = this.origMakeRequest;
        },

        'Loading indicators and inputs are properly turned off and on': function() {
            this.initValues();

            Y.all('input').set('value', 'banana@bar.foo');

            var tested;

            RightNow.Ajax.makeRequest = function(url, data, options) {
                Y.assert(Y.all(baseSelector + ' input').getAttribute('disabled').toString().indexOf('true,true,true') === 0);
                if (Y.UA.ie || Y.UA.ie  > 8) {
                    Y.assert(!Y.one(baseSelector).hasClass('rn_Loading'));
                }
                else {
                    Y.assert(Y.one(baseSelector).hasClass('rn_Loading'));
                }

                options.successHandler.call(options.scope, {success: false});

                Y.assert(Y.all(baseSelector + ' input').getAttribute('disabled').toString().indexOf('true,true,true') === -1);

                Y.assert(!Y.one(baseSelector).hasClass('rn_Loading'));

                tested = true;
            };
            Y.one('input[type="submit"]').simulate('click');

            Y.assert(tested);
        }
    }));

    return loginFormTests;
});
UnitTest.run();
