UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'OpenLogin_0'
}, function(Y, widget, baseSelector){
    var openLoginTests = new Y.Test.Suite({
        name: "standard/login/OpenLogin",
        setUp: function(){
            var testExtender = {
                initValues: function() {
                    this.instanceID = 'OpenLogin_0';
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.widgetData = this.instance.data;
                    this.selector = "#rn_" + this.instanceID;
                }
            };
            for(var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    openLoginTests.add(new Y.Test.Case({
        name: "UI",
        "Provider details are shown when provider button is clicked": function() {
            this.initValues();
            var called;
            RightNow.Event.subscribe("evt_FederatedProviderSelected", function(evt, args) {
                called = true;
            }, this);
            Y.one("#rn_" + this.instanceID + "_ProviderButton").simulate('click');
            if (this.widgetData.attrs.display_in_dialog) {
                var container = Y.one('.rn_Dialog');
                Y.Assert.isTrue(container.hasClass('rn_OpenLogin'), 'rn_OpenLogin class missing');
                Y.Assert.isTrue(container.hasClass('rn_OpenLoginDialog'), 'rn_OpenLoginDialog class missing');
            }
            else {
                Y.Assert.isTrue(called, 'called is not true');
            }

            Y.Assert.isFalse(Y.one(this.selector + "_ActionArea").hasClass("rn_Hidden"));
            var loginButton = Y.one(this.selector + "_LoginButton");
            var input = Y.one(this.selector + "_ProviderUrl");
            Y.Assert.areSame(this.widgetData.attrs.openid_placeholder, input.get("value"));
            if (!this.widgetData.attrs.preset_openid_url) {
                this.wait(function(){
                    Y.Assert.areSame("true", input.getAttribute("aria-selected"));
                }, 200);
            }
            // invalid submission (default)
            var fail = false;
            RightNow.Url.navigate = RightNow.Event.createDelegate(this, function() {
                fail = true;
            });
            loginButton.simulate('click');
            Y.Assert.isFalse(fail);

            // invalid submission (invalid input)
            if (this.widgetData.attrs.preset_openid_url) {
                input.set("value", "http://banana.com");
            }
            else {
                input.set("value", "banana");
            }
            loginButton.simulate('click');
            Y.Assert.isFalse(fail);

//            document.cookie = "testCookie";
            var actual = 'thisbettergetreplaced', expected = '';
            RightNow.Url.navigate = function(url) {
                actual = url;
            };
            // valid submission (invalid input)
            if (this.widgetData.attrs.preset_openid_url) {
                input.set("value", "banana");
                expected = this.widgetData.attrs.controller_endpoint +
                        encodeURIComponent(this.widgetData.attrs.preset_openid_url.replace(/\[username\]/, input.get("value"))) + "/";
            }
            else {
                input.set("value", "http://banana.com");
                expected = this.widgetData.attrs.controller_endpoint + encodeURIComponent("http://banana.com") + "/";
            }
            loginButton.simulate('click');
//            this.wait(function(){
//                Y.Assert.areSame(expected, actual);
//            }, 300);            
//            document.cookie = "";
        },

        "Dialog closes when 'x' button is clicked": function() {
            this.initValues();
            if (!this.widgetData.attrs.display_in_dialog) return;
            var dialog = Y.one('.rn_Dialog');
            dialog.one("button").simulate('click');
            Y.assert(dialog.ancestor('.yui3-panel-hidden'));
        }
    }));

    return openLoginTests;
});
UnitTest.run();
