UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'FormSubmit_0'
}, function(Y, widget, baseSelector){
    var formSubmitTests = new Y.Test.Suite({
        name: "standard/input/FormSubmit"
    });

    RightNow.Form.formToken.onNewFormToken = function (callback, context) {
        setTimeout(function() { callback.call(context, 'token'); }, 1);
    };

    formSubmitTests.add(new Y.Test.Case({
        name: "Operation",

        'Error displays and form does not submit when validation fails': function() {
            var errorLocation = Y.one("#" + widget.data.attrs.error_location) ||
                Y.one(baseSelector + "_ErrorLocation");
            widget.once("submit", function(evt, args) {
                Y.Assert.areSame("submit", evt);
                Y.Assert.isNotNull(args[0].data.f_tok);
                Y.Assert.areSame("unitTestID", args[0].data.form);
                Y.one("#" + args[0].data.error_location).append("<a href='#'>WRONG!</a>");
                return false;
            }, this)
            .on("validation:fail", function() {
                Y.Assert.areSame(2, errorLocation.get("children").size());
                Y.Assert.areSame("WRONG!", errorLocation.one("a").get("innerHTML"));
            }, this);
            Y.one(baseSelector + "_Button").simulate('click');
            this.validateSubmission(false);
            this.wait(function() {
                Y.Assert.areSame(document.activeElement, Y.Node.getDOMNode(errorLocation.one('a')));
                Y.Assert.areSame(true, Y.DOM.inViewportRegion(Y.Node.getDOMNode(errorLocation.one('a'))));
            }, 600);

        },

        'Specified timeout value is used': function() {
            widget.on("submit", function(evt, args) {
                return new RightNow.Event.EventObject(this, {data: {
                    name: "banana",
                    value: "green"
                }});
            }, this);
            var makeRequest = RightNow.Ajax.makeRequest,
                timeout;
            RightNow.Ajax.makeRequest = function(url, postData, requestOptions) {
                timeout = requestOptions.timeout;
            };
            Y.one(baseSelector + "_Button").simulate('click');

            this.wait(function() {
                widget.fire("response", new RightNow.Event.EventObject(this, {data: {result: ''}}));
                this.validateDialog();
                this.validateSubmission(false);
                if (widget.data.attrs.timeout)
                    Y.Assert.areSame(timeout, widget.data.attrs.timeout * 1000);
                else
                    Y.Assert.isUndefined(timeout);
                RightNow.Ajax.makeRequest = makeRequest;
            }, 2);
        },

        'Error does not display and form submits when validation passes': function() {
            widget.on("submit", function() {
                return new RightNow.Event.EventObject(this, {data: {
                    name: "banana",
                    value: "green"
                }});
            }, this)
            .on("validation:pass", function() {
                var errorLocation = Y.one("#" + widget.data.attrs.error_location) ||
                    Y.one(baseSelector + "_ErrorLocation");
                Y.Assert.areSame(0, errorLocation.get("children").size());
            }, this)
            .on("send", function() {
                return false;
            });
            Y.one(baseSelector + "_Button").simulate('click');
            this.validateSubmission(true);
        },

        'Error dialog displays when no result comes back from server': function() {
            widget.fire("response", new RightNow.Event.EventObject(this, {data: {result: ''}}));
            this.validateDialog();
            this.validateSubmission(false);
        },

        'Error dialog displays for a bad server response': function() {
            widget.fire("responseError", new RightNow.Event.EventObject());
            this.validateDialog();
            this.validateSubmission(false);
        },

        'Page navigates to error page when session error is returned from server': function() {
            RightNow.Url.navigate = RightNow.Event.createDelegate(this, function(url) {
                Y.Assert.areSame("/app/error/error_id/5newSession"  + widget.data.attrs.add_params_to_url, url);
            });
            widget.fire("response", new RightNow.Event.EventObject(this, {data: {result: {redirectOverride: '/app/error/error_id/5', sessionParam: "newSession"}}}));
            if (widget.data.attrs.label_confirm_dialog) {
                this.validateDialog();
            }
            this.validateSubmission(false);
        },

        'Pages refreshes when a successful response comes back from server and on_success_url is unset': function() {
            var originalValue = widget.data.attrs.on_success_url;
            widget.data.attrs.on_success_url = '';

            RightNow.Url.navigate = RightNow.Event.createDelegate(this, function(url) {
                Y.Assert.areSame(window.location + widget.data.attrs.add_params_to_url, url);
            });
            widget.fire("response", new RightNow.Event.EventObject(this, {data: {result: {transaction: {}, sessionParam: ""}}}));
            if (widget.data.attrs.label_confirm_dialog) {
                this.validateDialog();
            }
            this.validateSubmission(false);

            widget.data.attrs.on_success_url = originalValue;
        },

        'Page navigates to on_success_url when a successful response comes back from server': function() {
            var originalValue = widget.data.attrs.on_success_url;
            widget.data.attrs.on_success_url = '/app/foo/bar';

            RightNow.Url.navigate = RightNow.Event.createDelegate(this, function(url) {
                Y.Assert.areSame(widget.data.attrs.on_success_url + widget.data.attrs.add_params_to_url, url);
            });
            widget.fire("response", new RightNow.Event.EventObject(this, {data: {result: {transaction: {contact: {value: 27}}, sessionParam: ""}}}));
            if (widget.data.attrs.label_confirm_dialog) {
                this.validateDialog();
            }
            this.validateSubmission(false);

            widget.data.attrs.on_success_url = originalValue;
        },

        'Incident id param is added to success page': function() {
            var originalValue = widget.data.attrs.on_success_url;
            widget.data.attrs.on_success_url = '/app/foo/bar';

            RightNow.Url.navigate = RightNow.Event.createDelegate(this, function(url) {
                Y.Assert.areSame(widget.data.attrs.on_success_url + "/i_id/111newSession" + widget.data.attrs.add_params_to_url, url);
            });
            widget.fire("response", new RightNow.Event.EventObject(this, {data: {result: {transaction: {incident: {key: 'i_id', value: 111}}, sessionParam: "newSession"}}}));
            if (widget.data.attrs.label_confirm_dialog) {
                this.validateDialog();
            }
            this.validateSubmission(false);

            widget.data.attrs.on_success_url = originalValue;
        },

        'Ref num param is added to success page': function() {
            var originalValue = widget.data.attrs.on_success_url;
            widget.data.attrs.on_success_url = '/app/foo/bar';

            RightNow.Url.navigate = RightNow.Event.createDelegate(this, function(url) {
                Y.Assert.areSame(widget.data.attrs.on_success_url + "/refno/111212-111111newSession" + widget.data.attrs.add_params_to_url, url);
            });
            widget.fire("response", new RightNow.Event.EventObject(this, {data: {result: {transaction: {incident: {key: 'refno', value: '111212-111111'}}, sessionParam: "newSession"}}}));
            if (widget.data.attrs.label_confirm_dialog) {
                this.validateDialog();
            }
            this.validateSubmission(false);

            widget.data.attrs.on_success_url = originalValue;
        },

        'Redirect override is honored': function() {
            RightNow.Url.navigate = RightNow.Event.createDelegate(this, function(url) {
                if (widget.data.attrs.on_success_url) {
                    Y.Assert.areSame("/foo/bar" + "newSession" + widget.data.attrs.add_params_to_url, url);
                }
                else {
                    Y.Assert.areSame("/foo/bar" + "newSession" + widget.data.attrs.add_params_to_url, url);
                }
            });
            widget.fire("response", new RightNow.Event.EventObject(this, {data: {result: {i_id: 111, redirectOverride: "/foo/bar", sessionParam: "newSession"}}}));
            if (widget.data.attrs.label_confirm_dialog) {
                this.validateDialog();
            }
            this.validateSubmission(false);
        },

        'Unknown error triggers a generic dialog': function() {
            widget.fire("response", new RightNow.Event.EventObject(this, {data: {result: {foo: "bar"}}}));
            Y.Assert.isNotNull(Y.one('.rn_Dialog'));
            this.validateSubmission(false);
            this.validateDialog();
        },

        'Error message from server is added to error div': function() {
            widget.fire("response", new RightNow.Event.EventObject(this, {data: {result: "bar", errors: [{externalMessage: 'uhhh'}]}}));
            var errorLocation = Y.one("#" + widget.data.attrs.error_location) || Y.one(baseSelector + "_ErrorLocation");
            Y.Assert.areSame("alert", errorLocation.getAttribute("role"));
            Y.Assert.areSame(2, errorLocation.get("children").size());
            Y.Assert.areSame("uhhh", errorLocation.one("b").get("innerHTML"));
            this.validateSubmission(false);
        },

        'No op on smart assistant response': function() {
            RightNow.Url.navigate = function() {
                Y.Assert.fail("Navigation happened on a SA response!");
            };
            widget.fire("response", new RightNow.Event.EventObject(this, {data: {result: {sa: 1, results: []}}}));
            this.validateSubmission(false);
        },

        'Submit request happens': function() {
            var calledSubmit = false;
            widget.on("submit", function(evt, args) {
                calledSubmit = true;
                Y.Assert.isString(args[0].data.form);
                Y.Assert.isString(args[0].data.f_tok);
                Y.Assert.isString(args[0].data.error_location);
            }, this).on("send", function() { return false; })
            .fire("submitRequest");
            this.validateSubmission(true);
            Y.Assert.isTrue(calledSubmit);
            widget._toggleLoadingIndicators(false);
            widget._toggleClickListener(true);
        },

        'Legacy RightNow submit request fires': function() {
            var args;
            RightNow.Event.on('evt_formButtonSubmitRequest', function(a, b) {
                args = b;
            });

            Y.one(baseSelector + "_Button").simulate('click');

            Y.Assert.isArray(args);
            args = args[0].data;
            var errorLocation = (Y.one('#' + widget.data.attrs.error_location) || Y.one('#rn_FormSubmit_0_ErrorLocation')).get('id');
            Y.Assert.areSame(errorLocation, args.error_location);
            Y.Assert.isString(args.f_tok);
            Y.Assert.areSame(widget._parentForm, args.form);
        },

        'Legacy RightNow submit response fires': function() {
            function validateBasicEventObj (given) {
                Y.Assert.isArray(given);
                Y.Assert.areSame(widget._parentForm, given[0].data.form);
            }

            var args;
            RightNow.Event.on('evt_formButtonSubmitResponse', function(a, b) {
                args = b;
            });

            widget.fire("response", new RightNow.Event.EventObject(this, {data: {result: {foo: "bar"}}}));
            this.validateDialog();
            validateBasicEventObj(args);
            Y.Assert.areSame('bar', args[0].data.result.foo);

            args = null;
            widget.fire("response", new RightNow.Event.EventObject(this, {data: null}));
            this.validateDialog();
            validateBasicEventObj(args);
        },

        "reset event causes error div to hide and form button to re-enable": function() {
            // Abandon widget in a loading state.
            widget.once("send", function() { return false; });
            Y.one(baseSelector + "_Button").simulate('click');
            this.validateSubmission(true);
            var error = Y.one('.rn_MessageBox.rn_ErrorMessage').removeClass('rn_Hidden').setHTML('bananas');

            // Once again ready to go.
            widget.fire("reset");
            this.validateSubmission(false);
            Y.assert(error.hasClass('rn_Hidden'));
            Y.assert(!error.getHTML());
        },

        "defaultResponseHandler event is fired prior to form handling": function() {
            var response = { lost: 'love' };
            var eventArg;
            widget.once('defaultResponseHandler', function (evt, args) {
                eventArg = args[0];

                return false;
            });
            widget.fire('response', response);
            Y.Assert.areSame(response.lost, eventArg.lost);
        },

        "Banner is displayed when label_on_success_banner is set": function() {
            widget.data.attrs.label_on_success_banner = 'bananas';
            widget.data.attrs.on_success_url = 'none';

            var bannerCalledWith;
            RightNow.UI.displayBanner = function() {
                bannerCalledWith = arguments;
                this.on = function(){};
                return this;
            };

            widget._formSubmitResponse('', [{
                data: { result: { mountain: 'top', transaction: 'party' }}
            }]);

            Y.Assert.areSame('bananas', bannerCalledWith[0]);
            Y.Assert.isObject(bannerCalledWith[1]);
        },

        "Error message div is hidden when all data-fields are removed": function() {
            var errorContainer = Y.Node.create('<div id="errorTestNode"><div data-field="test"></div></div>');
            Y.one(document.body).append(errorContainer);
            widget._errorMessageDiv = errorContainer;

            widget._onFormUpdated();

            //The container should still be visible
            Y.Assert.areSame(1, errorContainer.all('[data-field]').size());
            Y.Assert.isFalse(errorContainer.hasClass('rn_Hidden'));

            //Remove the message, form update should hide the container
            errorContainer.one('[data-field]').remove();
            widget._onFormUpdated();
            Y.Assert.isTrue(errorContainer.hasClass('rn_Hidden'));
        },

        "ClickListener is toggled for all browsers": function() {
            var button = Y.one(baseSelector + " button");
            // non IE browsers
            widget._toggleClickListener(false);
            Y.Assert.isTrue(button.get("disabled"), "Button should be disabled");
            widget._toggleClickListener(true);
            Y.Assert.isFalse(button.get("disabled"), "Button should not be disabled");

            // IE browser
            widget.Y.UA.ie = true;
            widget._toggleClickListener(true);
            Y.Assert.isFalse(button.hasClass("rn_IeFormButton"), "Button should not have IeFormButton class");
            widget._toggleClickListener(false);
            Y.Assert.isTrue(button.hasClass("rn_IeFormButton"), "Button should have IeFormButton class");
        },

        validateSubmission: function(on) {
            var button = Y.one(baseSelector + "_Button");
            var buttonText = on ? widget.data.attrs.label_submitting_message : widget.data.attrs.label_button;

            if (on) {
                Y.Assert.isTrue(button.get("disabled"), "Button should be disabled");
                Y.Assert.isTrue(button.hasClass("rn_Loading"), "Button should have loading state");
            }
            else {
                Y.Assert.isFalse(button.get("disabled"), "Button should be enabled");
                Y.Assert.isFalse(button.hasClass("rn_Loading"), "Button should not have loading state");
            }

            Y.Assert.areSame(buttonText, Y.Lang.trim(button.getHTML()), "Button's text is incorrect. Didn't expect to find '" + button.getHTML() + "'");
        },

        validateDialog: function() {
            var dialog = Y.all('.rn_Dialog').each(function(item, i, all) {
                if (i === all.size() - 1) {
                    Y.assert(!item.ancestor('.yui3-panel-hidden'));
                }
                else {
                    Y.assert(item.ancestor('.yui3-panel-hidden'));
                }
            }, this);
            if (dialog.size()) {
                dialog = dialog.item(dialog.size() - 1);
            }
            dialog.one('button').simulate('click');
        }
    }));

    return formSubmitTests;
}).run();
