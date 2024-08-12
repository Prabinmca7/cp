UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'LoginDialog_0'
}, function(Y, widget, baseSelector){
    var loginDialogTests = new Y.Test.Suite({
        name: "standard/login/LoginDialog - Login Functionality",

        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.usernameField = Y.one(baseSelector + '_Username').set('value', '');
                    this.passwordField = Y.one(baseSelector + '_Password').set('value', '');
                    this.errorLocation = Y.one(baseSelector + '_LoginErrorMessage').setHTML("");
                },

                getDialogButtons: function(){
                    return Y.all('#rnDialog1 .yui3-widget-ft button');
                },

                clickTrigger: function() {
                    Y.one('#' + widget.data.attrs.trigger_element).simulate('click');
                    this.wait(function() {                
                    }, 1000);
                },
                clickOK: function() {
                    this.getDialogButtons().item(0).simulate('click');
                },
                verifyErrorLink: function(message) {
                    Y.Assert.areSame(1, this.errorLocation.all('*').size());
                    var link = this.errorLocation.one('a');
                    Y.Assert.areSame('javascript:void(0);', link.get('href'));
                    Y.Assert.areSame(message, Y.Lang.trim(link.getHTML()));
                }
            };

            for(var item in this.items)
            {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    loginDialogTests.add(new Y.Test.Case({
        name: "UI functional tests",

        tearDown: function() {
            this.getDialogButtons().item(1).simulate('click');
        },

        "Dialog toggles open and closed": function(){
            this.initValues();

            Y.Assert.isTrue(Y.one(baseSelector).hasClass('rn_Hidden'));
            this.clickTrigger();
            Y.Assert.isTrue(widget._dialog.cfg.getProperty('visible'));
            Y.one(this.getDialogButtons().item(1)).simulate('click');
            Y.Assert.isFalse(widget._dialog.cfg.getProperty('visible'));
        },

        testValidateUsername: function() {
            Y.Assert.areSame('Username must not contain spaces.', widget._validateUsername('with spaces'));
            Y.Assert.areSame('Username must not contain double quotes.', widget._validateUsername('with"quotes'));
            Y.Assert.areSame("Username must not contain either '<' or '>'", widget._validateUsername('with<brackets'));
            Y.Assert.areSame("Username must not contain either '<' or '>'", widget._validateUsername('with>brackets'));
        },

        setCredentials: function(login, password, message, success) {
            var expectedPostData = {
                    login: login,
                    password: password,
                    w_id: 0,
                    url: window.location.pathname},
                mockedResponse = {
                    _isParsed: true,
                    addSession: false,
                    message: message,
                    sessionParm: "",
                    success: success,
                    url: window.location.pathname,
                    w_id: 0};
            var mockedEventObj = new RightNow.Event.EventObject(widget, {data: expectedPostData});

            this.initValues();
            this.clickTrigger();

            this.usernameField.set('value', "gesobnasegh");
            this.passwordField.set('value', "aennoaseg");

            UnitTest.overrideMakeRequest(widget.data.attrs.login_ajax, expectedPostData,
                '_onResponseReceived', widget, mockedResponse, mockedEventObj);
        },

        testInvalidCredentials: function() {
            this.setCredentials('gesobnasegh', 'aennoaseg', 'Invalid Credentials', 0);
            widget._submitLoginForm();
            Y.Assert.areSame(Y.one('.rn_ErrorMessage a').get('innerText'), 'Invalid Credentials');
        },

        testKeyboardFunctionality: function() {
            this.setCredentials('gesobnasegh', 'aennoaseg', 'Invalid Credentials - Keyboard', 0);
            this.usernameField.focus().simulate('keydown', {keyCode: RightNow.UI.KeyMap.ENTER});
            Y.Assert.areSame(Y.one('.rn_ErrorMessage a').get('innerText'), 'Invalid Credentials - Keyboard');
        },

        "Dialog displays the login form on demand with 'evt_requireLogin' event, setting the dialog title": function() {
            RightNow.Event.fire('evt_requireLogin', new RightNow.Event.EventObject(widget, {data: {title: 'bananas'}}));
            Y.Assert.isTrue(widget._dialog.cfg.getProperty('visible'));
            Y.Assert.areSame(0, Y.one('.yui3-widget-hd').get('textContent').indexOf('bananas'));
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

            this.getDialogButtons().item(1).simulate('click');
        },

        'Loading indicators and inputs are properly turned off and on during an unsuccessful login attempt': function() {
            this.initValues();
            this.clickTrigger();

            Y.all('input').set('value', 'banana@bar.foo');

            var tested;

            RightNow.Ajax.makeRequest = function(url, data, options) {
                Y.Assert.areSame('truetrue', Y.all('input').getAttribute('disabled').join(''));
                Y.Assert.areSame('truetrue', Y.all('.yui3-widget-ft .yui3-widget-buttons button').get('disabled').join(''));
                if (Y.UA.ie || Y.UA.ie  > 8) {
                    Y.assert(!Y.one(baseSelector).hasClass('rn_ContentLoading'));
                }
                else {
                    Y.assert(Y.one(baseSelector).hasClass('rn_ContentLoading'));
                }

                options.successHandler.call(options.scope, {success: false});

                Y.Assert.areSame('', Y.all('input').getAttribute('disabled').join(''));
                Y.Assert.areSame('falsefalse', Y.all('.yui3-widget-ft .yui3-widget-buttons button').get('disabled').join(''));

                Y.assert(!Y.one(baseSelector).hasClass('rn_ContentLoading'));

                tested = true;
            };
            Y.one('.yui3-widget-ft .yui3-widget-buttons').one('button').simulate('click');

            Y.assert(tested);
        },

        'Loading indicators and inputs are properly turned off and on during a successful login attempt': function() {
            this.initValues();
            this.clickTrigger();

            Y.all('input').set('value', 'banana@bar.foo');

            var tested;

            RightNow.Ajax.makeRequest = function(url, data, options) {
                Y.Assert.areSame('truetrue', Y.all('input').getAttribute('disabled').join(''));
                Y.Assert.areSame('truetrue', Y.all('.yui3-widget-ft .yui3-widget-buttons button').get('disabled').join(''));
                if (Y.UA.ie || Y.UA.ie  > 8) {
                    Y.assert(!Y.one(baseSelector).hasClass('rn_ContentLoading'));
                }
                else {
                    Y.assert(Y.one(baseSelector).hasClass('rn_ContentLoading'));
                }

                options.successHandler.call(options.scope, {success: true, sessionParm: ''});

                Y.Assert.areSame(0, Y.all(baseSelector + ' input').size());
                Y.Assert.areSame('truetrue', Y.all('.yui3-widget-ft .yui3-widget-buttons button').get('disabled').join(''));

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
