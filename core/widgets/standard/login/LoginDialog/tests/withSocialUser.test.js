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
                    this.errorLocation = document.getElementById('rn_' + this.instanceID + '_LoginErrorMessage');
                    this.usernameField.value = "";
                    this.errorLocation.innerHTML = "";
                },

                createDialog: function(){
                    widget._dialog = widget._createDialog();
                },
                getDialogButtons: function(){
                    return Y.all('#rnDialog1 .yui3-widget-ft button');
                },
                clickOK: function() {
                    this.getDialogButtons().item(0).simulate('click');
                },
            };

            for(var item in this.items)
            {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    // Unfortunately, the waits are necessary in these tests since we are constantly recreating the form.
    // We need to ensure the form has time to recreate itself before we start poking and prodding at it.
    loginDialogTests.add(new Y.Test.Case({
        name: "Social User Form Test",

        tearDown: function(){
            widget._dialog = null;
        },

        "Login response with social action": function() {
            this.createDialog();

            this.wait(function() {
                widget._isSocialAction = true;

                var fakeResponse = {
                    addSession: false,
                    sessionParm: '',
                    message: 'Redirecting...',
                    success: 1,
                    url: window.location.pathname,
                    w_id: widget.data.info.w_id
                };
                var eventObject = new RightNow.Event.EventObject(this, {data: {
                    login: 'slatest',
                    password: '',
                    url: window.location.pathname,
                    w_id: widget.data.info.w_id
                }});

                RightNow.Event.subscribe("evt_hasSocialUserRequest", function(eventName, eventData){
                    Y.Assert.areSame("evt_hasSocialUserRequest", eventName);
                    Y.Assert.areSame(window.location.pathname, eventData[0].data.url);

                    // cancel ajax request
                    return false;
                }, this);

                widget._onResponseReceived(fakeResponse, eventObject);
            }, 500);
        },

        "Has no social user response": function() {
            this.createDialog();

            this.wait(function() {
                var fakeResponse = {socialUser: ''};
                var eventObject = new RightNow.Event.EventObject(this, {data: {
                    url: window.location.pathname,
                    w_id: widget.data.info.w_id
                }});

                widget._onHasSocialUserResponse(fakeResponse, eventObject);

                Y.Assert.areSame("social", widget._currentForm);

                var socialUserInfoForm = widget._formContainer.one(widget.baseSelector + "_SocialUserInfo");
                Y.Assert.isNotNull(socialUserInfoForm);
                Y.Assert.areSame("Finish", (Y.one('#rnDialog2 .yui3-widget-ft button') || Y.one('#' + widget._dialog.id + ' button')).getHTML());
                Y.Assert.isNull(Y.one(widget.baseSelector + ' .rn_OpenLoginAlternative'));
            }, 500);
        },

        "Submit social user info": function() {
            this.createDialog();

            this.wait(function() {
                widget._createSocialUserForm("Test description");

                widget._formContainer.one(widget.baseSelector + "_DisplayName").set("value", "CoolDude");

                RightNow.Event.subscribe("evt_createSocialUserRequest", function(eventName, eventData){
                    Y.Assert.areSame("CoolDude", eventData[0].data.displayName);
                    Y.Assert.areSame(window.location.pathname, eventData[0].data.url);

                    // cancel ajax request
                    return false;
                }, this);

                // remove rn_Hidden so that RightNow.UI.findParentForm can actually find the parent form
                Y.one('#rn_LoginDialog_0').removeClass('rn_Hidden');
                widget._submitSocialUserInfo();
                Y.one('#rn_LoginDialog_0').addClass('rn_Hidden');
            }, 500);
        },

        "Redirect url should pass through url params sent in": function() {
            this.wait(function() {
                var fakeResult = {
                    url: "/app/home",
                    sessionParm: "hello"
                };

                widget._urlParamsToAdd = {
                    content_id: 1,
                    rating: 1
                };

                var redirectUrl = widget._getRedirectUrl(fakeResult);
                Y.Assert.areSame("/app/home/content_id/1/rating/1", redirectUrl);
            }, 500);
        },

        "Test if error indicator toggles in case of Display Name": function() {
            this.createDialog();
            this.wait(function() {
                widget._createSocialUserForm("Test description");
                widget._formContainer.one(widget.baseSelector + "_DisplayName").set("disabled", false);

                //test empty
                widget._formContainer.one(widget.baseSelector + "_DisplayName").set("value", "");
                widget._formContainer.one(widget.baseSelector + "_DisplayName").focus();

                this.wait(function() {
                    Y.Assert.isFalse(widget._formContainer.one(widget.baseSelector + "_DisplayName").hasClass('rn_ErrorField'));
                    Y.Assert.isFalse(widget._formContainer.one(widget.baseSelector + "_SocialUserInfoForm").one("label").hasClass('rn_ErrorLabel'));
                }, 500);

                widget._formContainer.one(widget.baseSelector + "_DisplayName").blur();
                this.wait(function() {
                    Y.Assert.isTrue(widget._formContainer.one(widget.baseSelector + "_DisplayName").hasClass('rn_ErrorField'));
                    Y.Assert.isTrue(widget._formContainer.one(widget.baseSelector + "_SocialUserInfoForm").one("label").hasClass('rn_ErrorLabel'));
                }, 500);

                //test non-empty
                widget._formContainer.one(widget.baseSelector + "_DisplayName").focus();
                widget._formContainer.one(widget.baseSelector + "_DisplayName").set("value", "cool");
                widget._formContainer.one(widget.baseSelector + "_DisplayName").blur();
                this.wait(function() {
                    Y.Assert.isFalse(widget._formContainer.one(widget.baseSelector + "_DisplayName").hasClass('rn_ErrorField'));
                    Y.Assert.isFalse(widget._formContainer.one(widget.baseSelector + "_SocialUserInfoForm").one("label").hasClass('rn_ErrorLabel'));
                }, 500);

                //resetting the value of display name
                widget._formContainer.one(widget.baseSelector + "_DisplayName").set("value", "");
            }, 500);
        }
    }));

    return loginDialogTests;
});
UnitTest.run();

