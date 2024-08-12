UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'UserInfoDialog_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/user/UserInfoDialog",

        setUp: function(){
            var testExtender = {
                initValues : function() {
                    if(!this.instanceID){
                        this.instanceID = 'LoginDialog_0';
                        this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    }
                }
            };

            for (var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    suite.add(new Y.Test.Case({
        name: "Social User Info Dialog Test",

        "Check form visibility on page load": function() {
            this.initValues();

            if(widget.data.attrs.display_on_page_load) {
                Y.Assert.isFalse(Y.one('.rn_UserInfoDialog').hasClass('rn_Hidden'));
            }
            else {
                Y.Assert.isTrue(Y.one('.rn_UserInfoDialog').hasClass('rn_Hidden'));
            }
        },

        "Launch user info dialog via event": function() {
            this.initValues();

            RightNow.Event.fire("evt_userInfoRequired");
            Y.Assert.areSame(widget.data.attrs.show_social_warning ? widget.data.attrs.label_social_form_description :
                widget.data.attrs.label_form_description, Y.one("#rnDialog1 .rn_UserInfoDescription").getHTML());
            Y.Assert.areSame("Finish", Y.all("#rnDialog1 .yui3-widget-ft button").item(0).getHTML());
            // Ensure display name field has focus
            var activeElement = document.activeElement,
                input = Y.one(widget.baseSelector + '_DisplayName');
            Y.Assert.areSame(activeElement.id, input.get('id'));
        },

        "Fill in form and click finish": function() {
            var given,
                subscriber = function(name, eventData) {
                    given = eventData;
                    // cancel ajax request
                    return false;
                };

            this.initValues();
            Y.one(widget.baseSelector + "_DisplayName").set("value", "CoolDude");

            RightNow.Event.subscribe("evt_createUserInfoRequest", subscriber, this);
            Y.all("#rnDialog1 .yui3-widget-ft button").item(0).simulate("click");

            Y.Assert.areSame("CoolDude", given[0].data.displayName);
            Y.Assert.areSame(window.location.pathname, given[0].data.url);

            RightNow.Event.unsubscribe("evt_createUserInfoRequest", subscriber, this);
        },

        "Test if error indicator toggles": function() {
            this.initValues();
            Y.one(widget.baseSelector + "_DisplayName").set("disabled", false);

            //test empty
            Y.one(widget.baseSelector + "_DisplayName").set("value", "");
            Y.one(widget.baseSelector + "_DisplayName").focus();
            this.wait(function() {
                Y.Assert.isFalse(Y.one(widget.baseSelector + "_DisplayName").hasClass('rn_ErrorField'));
                Y.Assert.isFalse(Y.one(widget.baseSelector + "_DisplayName_Label").hasClass('rn_ErrorLabel'));
            }, 200);
            Y.one(widget.baseSelector + "_DisplayName").blur();
            this.wait(function() {
                Y.Assert.isTrue(Y.one(widget.baseSelector + "_DisplayName").hasClass('rn_ErrorField'));
                Y.Assert.isTrue(Y.one(widget.baseSelector + "_DisplayName_Label").hasClass('rn_ErrorLabel'));
            }, 200);

            //test non-empty
            Y.one(widget.baseSelector + "_DisplayName").focus();
            Y.one(widget.baseSelector + "_DisplayName").set("value", "cool");
            Y.one(widget.baseSelector + "_DisplayName").blur();
            this.wait(function() {
                Y.Assert.isFalse(Y.one(widget.baseSelector + "_DisplayName").hasClass('rn_ErrorField'));
                Y.Assert.isFalse(Y.one(widget.baseSelector + "_DisplayName_Label").hasClass('rn_ErrorLabel'));
            }, 200);
        }
    }));

    return suite;
}).run();
