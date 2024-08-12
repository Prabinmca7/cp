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

        "Email dialog behaves properly": function() {
            this.initValues();
            var dialog = Y.one('.rn_Dialog');
            if (!dialog && (!this.widgetData.js || !this.widgetData.js.error)) return;

            // error dialog
            if (this.widgetData.js && this.widgetData.js.error) {
                dialog.one("button").simulate('click');
                Y.assert(dialog.ancestor('.yui3-panel-hidden'));
                return;
            }

            // email dialog

            // dialog was properly contructed
            Y.Assert.areSame(this.widgetData.attrs.label_email_prompt_title, dialog.one('#rn_Dialog_1_Title').get('innerHTML'));
            Y.Assert.isTrue(dialog.get('innerHTML').indexOf(this.widgetData.attrs.label_email_prompt) > -1);
            Y.Assert.isTrue(dialog.one('label').get('innerHTML').indexOf(this.widgetData.attrs.label_email_address) > -1);
            Y.assert(dialog.one('label span.rn_Required'));

            // email validation
            dialog.one('input[type="email"]').set("value", "banana");
            dialog.all("button").item(1).simulate('click');
            Y.Assert.isNotNull(dialog.one('.rn_ErrorMessage.rn_MessageBox'));
            Y.Assert.isNotNull(dialog.one('.rn_ErrorMessage a'));

            // email submittal
            dialog.one('input[type="email"]').set("value", "banana@bar.foo");
            var callback, callbackScope;
            RightNow.Ajax.makeRequest = RightNow.Event.createDelegate(this, function(url, data, options) {
                Y.Assert.areSame(this.widgetData.attrs.provide_email_ajax, url);
                Y.Assert.areSame('banana@bar.foo', data.email);
                Y.Assert.areSame(RightNow.Url.getParameter("emailerror"), data.userData);
                callback = options.successHandler;
                callbackScope = options.scope;
            });
            dialog.all("button").item(1).simulate('click');
            Y.assert(!dialog.ancestor('.yui3-panel-hidden'));
            dialog.all(".yui3-widget-ft button").each(function(button) {
                Y.Assert.isTrue(button.get("disabled"));
            }, this);

            // email ajax return
            RightNow.Url.navigate = RightNow.Event.createDelegate(this, function(url) {
                Y.Assert.areSame(this.widgetData.attrs.redirect_url, url);
            });
            callback.call(callbackScope, {responseText: "true"});

            // email ajax return invalid
            callback.call(callbackScope, "true");
            var errorDialog = Y.one("#rnDialog2");
            Y.Assert.isNotNull(errorDialog);
            errorDialog.one("button").simulate('click');
            Y.assert(errorDialog.ancestor('.yui3-panel-hidden'));
            var cancel = dialog.all("button");
            cancel = cancel.item(cancel.size() - 1);
            cancel.simulate('click');
            Y.assert(dialog.ancestor('.yui3-panel-hidden'));
        },

        "Provider details are shown when provider button is clicked": function() {
            this.initValues();
            var called;
            RightNow.Event.subscribe("evt_FederatedProviderSelected", function(evt, args) {
                called = true;
            }, this);
            Y.one("#rn_" + this.instanceID + "_ProviderButton").simulate('click');
            if (this.widgetData.attrs.display_in_dialog) {
                Y.all('.rn_Dialog').slice(-1).each(function(node) {
                    Y.assert(node.hasClass('rn_OpenLogin'), 'Expected classes are missing');
                    Y.assert(node.hasClass('rn_OpenLoginDialog'), 'Expected classes are missing');
                }, this);
            }
            else {
                this.wait(function() {
                    Y.Assert.isTrue(called, 'called not true');
                    Y.Assert.isFalse(Y.one(this.selector + "_ActionArea").hasClass("rn_Hidden"));
                    Y.Assert.areSame("true", Y.one(this.selector + "_LoginButton").getAttribute("aria-selected"));
                }, 1000);
            }
        },

        "Redirect url should pass through url params sent in": function() {
            this.initValues();
            var redirectVal = widget._redirectUrl;
            widget._redirectUrl = "/app/home";

            var eventObject = new RightNow.Event.EventObject(this, {data: {
                isSocialAction: true,
                urlParamsToAdd: {
                    content_id: 1,
                    rating: 1
                }
            }});

            RightNow.Event.fire('evt_requireLogin', eventObject);

            Y.Assert.areSame("/app/home/content_id/1/rating/1", widget._redirectUrl);
            widget._redirectUrl = redirectVal;
        },

        "Page navigates to proper endpoint when log in button is clicked": function() {
            this.initValues();

            if (Y.UA.ie) return; // IE builds up and clicks a link in order to capture referrer

            RightNow.Url.navigate = RightNow.Event.createDelegate(this, function(url) {
                Y.Assert.areSame(this.widgetData.attrs.controller_endpoint + encodeURIComponent(this.widgetData.attrs.redirect_url), url);
            });
            Y.one(this.selector + "_LoginButton").simulate('click');
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
