UnitTest.addSuite({
    type: UnitTest.Type.Framework,
    jsFiles:  [
        '/euf/core/debug-js/RightNow.UI.js',
        '/euf/core/debug-js/RightNow.Text.js',
        '/euf/core/debug-js/RightNow.Url.js',
        '/euf/core/debug-js/RightNow.Ajax.js',
        '/euf/core/debug-js/RightNow.UI.AbuseDetection.js',
        '/euf/core/debug-js/RightNow.Event.js',
        '/euf/core/debug-js/modules/widgetHelpers/EventProvider.js',
        '/euf/core/debug-js/modules/widgetHelpers/Form.js',
        '/euf/core/debug-js/modules/widgetHelpers/Field.js'
    ],
    namespaces: [
        'RightNow.UI.findParentForm'
    ]
}, function(Y){
    function createForm(id, action) {
        var form = Y.Node.create("<form style='display:none' onsubmit='return false;' id='" + id + "'><div id='rn_widget_" + id + "'><input type='submit' id='rn_widget_" + id + "_Button'></div></form>");
        if (action) {
            form.set('action', action);
        }
        return Y.one(document.body).appendChild(form);
    }
    var tests = new Y.Test.Suite({
        name: "Forms"
    });

    tests.add(new Y.Test.Case({
        name: "Test UI functionality",
        setUp: function() {
            Y.one(document.body).append();
        },
        "Hide and show should affect the form button": function() {
            createForm('hide_form');
            var instance = new RightNow.Form({
                attrs: {},
                js: { f_tok: 'token' }
            }, 'widget_hide_form', Y);

            Y.Assert.isFalse(instance._formButton.hasClass('rn_Hidden'));
            instance.hide();
            Y.Assert.isTrue(instance._formButton.hasClass('rn_Hidden'));
            instance.show();
            Y.Assert.isFalse(instance._formButton.hasClass('rn_Hidden'));
        },
        "Disable and enable should affect the form button": function() {
            createForm('disable_form');
            var instance = new RightNow.Form({
                attrs: {},
                js: { f_tok: 'token' }
            }, 'widget_disable_form', Y);

            Y.Assert.isFalse(instance._formButton.get('disabled'));
            instance.disable();
            Y.Assert.isTrue(instance._formButton.get('disabled'));
            instance.enable();
            Y.Assert.isFalse(instance._formButton.get('disabled'));
        }
    }));

    tests.add(new Y.Test.Case({
        name: "testCreation",

        _should: {
            error: {
                "Form instance throws an error if form doesn't have input specified by instance id": true,
                "Form instance throws an error if form is attempted to be bound to already existing form": true,
                "Form instance throws an error if no `f_tok` property is supplied on `this.data.js`": true
            }
        },

        "Form instance throws an error if form doesn't have input specified by instance id": function() {
            this.wait(function() {
                var form = Y.Node.create("<form style='display:none' id='foo'><input type='text' id='bar' name='bar'><div id='rn_widget_foo'></div></form>");
                Y.one(document.body).appendChild(form);
                var Widget = RightNow.Form.extend({});
                new Widget({
                    attrs: {},
                    js: { f_tok: 'token' }
                }, 'widget_foo', Y);
            }, 1000);
        },

        testCreation: function() {
            createForm("foo");
            var Widget = RightNow.Form.extend({});
            new Widget({
                attrs: {},
                js: { f_tok: 'token' }
            }, 'widget_foo', Y);
        },

        "Form instance throws an error if form is attempted to be bound to already existing form": function() {
            var Widget = RightNow.Form.extend({});
            new Widget({
                attrs: {},
                js: { f_tok: 'token' }
            }, 'widget_foo', Y);
        },

        "Form instance throws an error if no `f_tok` property is supplied on `this.data.js`": function() {
            createForm("sonogram");
            var Widget = RightNow.Form.extend({});
            new Widget({
                attrs: {},
                js: {}
            }, 'widget_sonogram', Y);
        }
    }));

    tests.add(new Y.Test.Case({
        name : "testEvents",

        "Submit event is cancelled when FALSE is returned": function() {
            var calledSubmit = false;
            var calledValidationPass = false;
            var calledValidationFail = false;
            createForm("bar");

            var Widget = RightNow.Form.extend({
                overrides: {
                    constructor: function() {
                        this.parent();
                        this
                        .on("submit", function(name, args) {
                            calledSubmit = true;
                            Y.Assert.areSame("submit", name);
                            Y.Assert.isArray(args);
                            Y.Assert.areSame(1, args.length);
                            Y.Assert.areSame("bar", args[0].data.foo);
                            return false;
                        }, this)
                        .on("validation:pass", function() {
                            // never called
                            calledValidationPass = true;
                        })
                        .on("validation:fail", function(name, args) {
                            calledValidationFail = true;
                            Y.Assert.areSame("validation:fail", name);
                            Y.Assert.isArray(args);
                            Y.Assert.areSame(1, args.length);
                            Y.Assert.areSame("bar", args[0].data.foo);
                        })
                        .fire("collect", new RightNow.Event.EventObject(this, {data: {foo: "bar"}}));
                    }
                }
            });
            new Widget({
                attrs: {},
                js: { f_tok: 'token' }
            }, 'widget_bar', Y);
            Y.Assert.isTrue(calledSubmit);
            Y.Assert.isTrue(calledValidationFail);
            Y.Assert.isFalse(calledValidationPass);
        },

        "Submit event is cancelled when nothing is returned": function() {
            var calledSubmit = false;
            var calledValidationPass = false;
            var calledValidationFail = false;
            createForm("nono");
            var Widget = RightNow.Form.extend({
                overrides: {
                    constructor: function() {
                        this.parent();
                        this
                        .on("submit", function(name, args) {
                            calledSubmit = true;
                            Y.Assert.areSame("submit", name);
                            Y.Assert.isArray(args);
                            Y.Assert.areSame(1, args.length);
                            Y.Assert.areSame("bar", args[0].data.foo);
                            // nothing returned (undefined)
                        }, this)
                        .on("validation:pass", function(name, args) {
                            // called
                            calledValidationPass = true;
                            Y.Assert.areSame("validation:pass", name);
                            Y.Assert.isArray(args);
                            Y.Assert.areSame(1, args.length);
                            Y.Assert.areSame("bar", args[0].data.foo);
                        })
                        .on("validation:fail", function() {
                            // never called
                            calledValidationFail = true;
                        })
                        .fire("collect", new RightNow.Event.EventObject(this, {data: {foo: "bar"}}));
                    }
                }
            });
            new Widget({
                attrs: {},
                js: { f_tok: 'token' }
            }, 'widget_nono', Y);
            Y.Assert.isTrue(calledSubmit);
            Y.Assert.isFalse(calledValidationFail);
            Y.Assert.isTrue(calledValidationPass);
        },

        "Submit event is cancelled when empty string is returned": function() {
            var calledSubmit = false;
            var calledValidationPass = false;
            var calledValidationFail = false;
            createForm("form1");
            var Widget = RightNow.Form.extend({
                overrides: {
                    constructor: function() {
                        this.parent();
                        this
                        .on("submit", function(name, args) {
                            calledSubmit = true;
                            Y.Assert.areSame("submit", name);
                            Y.Assert.isArray(args);
                            Y.Assert.areSame(1, args.length);
                            Y.Assert.areSame("bar", args[0].data.foo);
                            return "";
                        }, this)
                        .on("validation:pass", function(name, args) {
                            calledValidationPass = true;
                            Y.Assert.areSame("validation:pass", name);
                            Y.Assert.isArray(args);
                            Y.Assert.areSame(1, args.length);
                            Y.Assert.areSame("bar", args[0].data.foo);
                        })
                        .on("validation:fail", function() {
                            // never called
                            calledValidationFail = true;
                        })
                        .fire("collect", new RightNow.Event.EventObject(this, {data: {foo: "bar"}}));
                    }
                }
            });
            new Widget({
                attrs: {},
                js: { f_tok: 'token' }
            }, 'widget_form1', Y);
            Y.Assert.isTrue(calledSubmit);
            Y.Assert.isFalse(calledValidationFail);
            Y.Assert.isTrue(calledValidationPass);
        },

        "Submit event is cancelled when 0 is returned": function() {
            var calledSubmit = false;
            var calledValidationPass = false;
            var calledValidationFail = false;
            createForm("form2");
            var Widget = RightNow.Form.extend({
                overrides: {
                    constructor: function() {
                        this.parent();
                        this
                        .on("submit", function(name, args) {
                            calledSubmit = true;
                            Y.Assert.areSame("submit", name);
                            Y.Assert.isArray(args);
                            Y.Assert.areSame(1, args.length);
                            Y.Assert.areSame("bar", args[0].data.foo);
                            return 0;
                        }, this)
                        .on("validation:pass", function(name, args) {
                            calledValidationPass = true;
                            Y.Assert.areSame("validation:pass", name);
                            Y.Assert.isArray(args);
                            Y.Assert.areSame(1, args.length);
                            Y.Assert.areSame("bar", args[0].data.foo);
                        })
                        .on("validation:fail", function() {
                            // never called
                            calledValidationFail = true;
                        })
                        .fire("collect", new RightNow.Event.EventObject(this, {data: {foo: "bar"}}));
                    }
                }
            });
            new Widget({
                attrs: {},
                js: { f_tok: 'token' }
            }, 'widget_form2', Y);
            Y.Assert.isTrue(calledSubmit);
            Y.Assert.isFalse(calledValidationFail);
            Y.Assert.isTrue(calledValidationPass);
        },

        "Submit event succeeds when an EventObject is returned": function() {
            var calledSubmit = false;
            var calledValidationPass = false;
            var calledValidationFail = false;
            createForm("form3");
            var Widget = RightNow.Form.extend({
                overrides: {
                    constructor: function() {
                        this.parent();
                        this
                        .on("submit", function(name, args) {
                            calledSubmit = true;
                            Y.Assert.areSame("submit", name);
                            Y.Assert.isArray(args);
                            Y.Assert.areSame(1, args.length);
                            Y.Assert.areSame("bar", args[0].data.foo);
                            return new RightNow.Event.EventObject(this, {data: {yes: "no"}});
                        }, this)
                        .on("validation:fail", function() {
                            calledValidationFail = true;
                        })
                        .on("validation:pass", function(name, args) {
                            calledValidationPass = true;
                            Y.Assert.areSame("validation:pass", name);
                            Y.Assert.isArray(args);
                            Y.Assert.areSame(1, args.length);
                            Y.Assert.areSame("bar", args[0].data.foo);
                        })
                        .fire("collect", new RightNow.Event.EventObject(this, {data: {foo: "bar"}}));
                    }
                }
            });
            new Widget({
                attrs: {},
                js: { f_tok: 'token' }
            }, 'widget_form3', Y);
            Y.Assert.isTrue(calledSubmit);
            Y.Assert.isFalse(calledValidationFail);
            Y.Assert.isTrue(calledValidationPass);
        },

        "Default sendForm behavior results in an error": function() {
            var calledSubmit = false;
            var calledValidationPass = false;
            var calledValidationFail = false;
            var testCase = this;
            createForm("form4");
            var Widget = RightNow.Form.extend({
                overrides: {
                    constructor: function() {
                        this.parent();
                        this
                        .on("submit", function(name, args) {
                            calledSubmit = true;
                            Y.Assert.areSame("submit", name);
                            Y.Assert.isArray(args);
                            Y.Assert.areSame(1, args.length);
                            Y.Assert.areSame("bar", args[0].data.foo);
                            return new RightNow.Event.EventObject(this, {data: {yes: "no"}});
                        }, this)
                        .on("validation:fail", function() {
                            calledValidationFail = true;
                        })
                        .on("validation:pass", function(name, args) {
                            calledValidationPass = true;
                            Y.Assert.areSame("validation:pass", name);
                            Y.Assert.isArray(args);
                            Y.Assert.areSame(1, args.length);
                            Y.Assert.areSame("bar", args[0].data.foo);
                            this.fire("send");
                        }, this)
                        .on("response", function(name, args) {
                            testCase.resume(function(){
                                Y.Assert.areSame("response", name);
                                Y.Assert.isArray(args);
                                Y.Assert.areSame(1, args.length);
                                args = args[0];
                                Y.Assert.areSame("/app/error/error_id/5", args.data.result.redirectOverride);
                                Y.Assert.areSame('widget_form4', args.w_id);
                                Y.Assert.areSame(1, args.data.errors.length); //We sent a form with no data, we should expect an error
                            });

                        })
                        .fire("collect", new RightNow.Event.EventObject(this, {data: {foo: "bar"}}));
                    }
                }
            });
            UnitTest.overrideMakeRequest(null, {'flash_message': 'spoooooooooooooon'});
            new Widget({
                attrs: {'flash_message': 'spoooooooooooooon'},
                js: { f_tok: 'token' }
            }, 'widget_form4', Y);
            Y.Assert.isTrue(calledSubmit);
            Y.Assert.isFalse(calledValidationFail);
            Y.Assert.isTrue(calledValidationPass);
        },

        "Form's action attribute is used as the ajax endpoint": function() {
            var calledSubmit = false;
            var calledValidationPass = false;
            var calledValidationFail = false;
            var testCase = this;
            createForm("form5", "/ci/ajaxRequest/getAnswer");
            var Widget = RightNow.Form.extend({
                overrides: {
                    constructor: function() {
                        this.parent();
                        this
                        .on("submit", function(name, args) {
                            calledSubmit = true;
                            Y.Assert.areSame("submit", name);
                            Y.Assert.isArray(args);
                            Y.Assert.areSame(1, args.length);
                            Y.Assert.areSame("bar", args[0].data.foo);
                            return new RightNow.Event.EventObject(this, {data: {yes: "no"}});
                        }, this)
                        .on("validation:fail", function() {
                            calledValidationFail = true;
                        })
                        .on("validation:pass", function(name, args) {
                            calledValidationPass = true;
                            Y.Assert.areSame("validation:pass", name);
                            Y.Assert.isArray(args);
                            Y.Assert.areSame(1, args.length);
                            Y.Assert.areSame("bar", args[0].data.foo);
                            this.fire("send");
                        }, this)
                        .on("response", function(name, args) {
                            testCase.resume(function(){
                                Y.Assert.areSame("response", name);
                                Y.Assert.isArray(args);
                                Y.Assert.areSame(1, args.length);
                                args = args[0];
                                Y.Assert.areSame('widget_form5', args.w_id);
                                Y.Assert.areSame(1, args.data.errors.length); //We didn't send in a valid answer ID, we should expect an error
                                Y.Assert.isNull(args.data.result);
                            });
                        })
                        .fire("collect", new RightNow.Event.EventObject(this, {data: {foo: "bar"}}));
                    }
                }
            });
            new Widget({
                //We need to set the on_success_url attribute, otherwise we'll just submit this form normally and redirects are bad when testing
                attrs: {on_success_url: '/fake/endpoint'},
                js: { f_tok: 'token' }
            }, 'widget_form5', Y);
            Y.Assert.isTrue(calledSubmit);
            Y.Assert.isFalse(calledValidationFail);
            Y.Assert.isTrue(calledValidationPass);
            this.wait();
        },
        testCollectFields: function() {
            var calledSubmit = false,
                calledNotCollected = false,
                calledCollected = false,
                calledFormCollect = false,
                calledNotCollected2 = false;

            createForm("form9")
                .append("<div id='rn_testField1'><input type='text'/></div>")
                .append("<div id='rn_testField2'><input type='text'/></div>")
                .append("<div id='rn_testField3'><input type='text'/></div>");

            var fieldNotCollected = RightNow.Field.extend({
                overrides: {
                    constructor: function() {
                        this.parent();
                        this.parentForm().on("submit", function() {
                            //Should not be called, since the field is not collected.
                            calledNotCollected = true;
                        }, this);
                    },
                    onCollect: function(evt, args) {
                        return false;
                    }
                }
            });
            var fieldNotCollected2 = RightNow.Field.extend({
                overrides: {
                    constructor: function() {
                        this.parent();
                        this.parentForm().on("submit", function() {
                            //Should not be called, since the field is not collected.
                            calledNotCollected2 = true;
                        }, this);
                    },
                    onCollect: function(evt, args) {
                        return false;
                    }
                }
            });

            var fieldCollected = RightNow.Field.extend({
                overrides: {
                    constructor: function() {
                        this.parent();
                        this.parentForm().on("submit", function() {
                            //Should be called, since the field is collected
                            calledCollected = true;
                        }, this);
                    },
                    onCollect: function(evt, args) {
                        return 'testField2';
                    }
                }
            });

            new fieldNotCollected({
                attrs: {},
                js: {name: 'testField1'}
            }, 'testField1', Y);

            new fieldCollected({
                attrs: {},
                js: {name: 'testField2'}
            }, 'testField2', Y);

            new fieldNotCollected2({
                attrs: {},
                js: {name: 'testField3'}
            }, 'testField3', Y);

            var formInstance = RightNow.Form.extend({
                overrides: {
                    constructor: function() {
                        this.data.js.f_tok = 'token';
                        this.parent();
                        this
                        .on("collect", function(name, args) {
                            //Should be called, since all non-field subscribers are collected
                            calledFormCollect = true;
                            Y.Assert.areSame("collect", name);
                            Y.Assert.isArray(args);
                            Y.Assert.areSame(1, args.length);
                            Y.Assert.areSame("bar", args[0].data.foo);
                        })
                        .on("submit", function(name, args) {
                            calledSubmit = true;
                            Y.Assert.areSame("submit", name);
                            Y.Assert.isArray(args);
                            Y.Assert.areSame(1, args.length);
                            Y.Assert.areSame("bar", args[0].data.foo);
                            return false; //Cancel the submission, since we aren't testing the round trip
                        }, this)
                        .fire("collect", new RightNow.Event.EventObject(this, {data: {foo: "bar"}}));
                    }
                }
            });

            new formInstance({
                attrs: {},
                js: {}
            }, 'widget_form9', Y);

            Y.Assert.isTrue(calledFormCollect);
            Y.Assert.isFalse(calledNotCollected);
            Y.Assert.isFalse(calledNotCollected2);
            Y.Assert.isTrue(calledCollected);
            Y.Assert.isTrue(calledSubmit);
        },
        testGetValidatedFields: function() {
            var calledSubmit = false;
            var calledValidationPass = false;
            createForm("form6");
            var Widget = RightNow.Form.extend({
                overrides: {
                    constructor: function() {
                        this.data.js.f_tok = 'token';
                        this.parent();
                        this
                        .on("submit", function() {
                            Y.Assert.isArray(this.getValidatedFields());
                            Y.Assert.areSame(0, this.getValidatedFields().length);
                            return new RightNow.Event.EventObject(this, {data: {
                                name: "fieldName",
                                value: "banana",
                                prev: null,
                                required: true
                            }});
                        }, this)
                        .on("submit", function() {
                            var fields = this.getValidatedFields();
                            Y.Assert.isArray(fields);
                            Y.Assert.areSame(1, fields.length);
                            Y.Assert.areSame("fieldName", fields[0].name);
                            Y.Assert.areSame("banana", fields[0].value);
                            Y.Assert.isNull(fields[0].prev);
                            Y.Assert.isTrue(fields[0].required);
                            // make sure modification isn't allowed
                            fields[0].prev = "whatev";
                            fields[0].required = false;
                            return new RightNow.Event.EventObject(this, {data: {
                                name: "field2",
                                value: "apricot"
                            }});
                        }, this)
                        .on("validation:pass", function() {
                            var fields = this.getValidatedFields();
                            Y.Assert.isArray(fields);
                            Y.Assert.areSame(2, fields.length);
                            Y.Assert.areSame("fieldName", fields[0].name);
                            Y.Assert.areSame("banana", fields[0].value);
                            Y.Assert.isNull(fields[0].prev);
                            Y.Assert.isTrue(fields[0].required);
                            Y.Assert.areSame("field2", fields[1].name);
                            Y.Assert.areSame("apricot", fields[1].value);
                        }, this)
                        .fire("collect", new RightNow.Event.EventObject(this, {data: {foo: "bar"}}));
                    }
                }
            });
            new Widget({
                attrs: {},
                js: { f_tok: 'token' }
            }, 'widget_form6', Y);
        },

        testSendFormWithTimeout: function() {
            var makeRequest = RightNow.Ajax.makeRequest,
                widget, instance, ajaxRequests = [];

            RightNow.Ajax.makeRequest = function(url, postData, requestOptions) {
                ajaxRequests.push(requestOptions.timeout);
            };

            createForm("form7");
            widget = RightNow.Form.extend({
                overrides: {
                    constructor: function() {
                        this.data.js.f_tok = 'token';
                        this.parent();
                        this
                        .fire("collect", new RightNow.Event.EventObject(this, {data: {foo: "bar"}}))
                        .fire("send");
                    }
                }
            });
            instance = new widget({
                //We need to set the on_success_url attribute, otherwise we'll just submit this form normally and redirects are bad when testing
                attrs: {on_success_url: '/fake/endpoint'},
                js: {}
            }, 'widget_form7', Y);

            createForm("form8");
            widget = RightNow.Form.extend({
                overrides: {
                    constructor: function() {
                        this.data.js.f_tok = 'token';
                        this.parent();
                        this
                        .fire("collect", new RightNow.Event.EventObject(this, {data: {foo: "bar", timeout: 500}}))
                        .fire("send");
                    }
                }
            });
            instance = new widget({
                //We need to set the on_success_url attribute, otherwise we'll just submit this form normally and redirects are bad when testing
                attrs: {on_success_url: '/fake/endpoint'},
                js: {}
            }, 'widget_form8', Y);


            this.wait(function () {
                Y.Assert.areSame(2, ajaxRequests.length);
                Y.Assert.isUndefined(ajaxRequests[0]);
                Y.Assert.areSame(500, ajaxRequests[1]);
            }, 1);

            RightNow.Ajax.makeRequest = makeRequest;
        }
    }));

    tests.add(new Y.Test.Case({
        name : "Default form token handling behavior",

        replaceMakeRequest: function(replace) {
            this.origMakeRequest = RightNow.Ajax.makeRequest;

            RightNow.Ajax.makeRequest = replace;
        },

        restoreMakeRequest: function() {
            RightNow.Ajax.makeRequest = this.origMakeRequest;
        },

        "Form token is sent along in the request": function() {
            createForm("north_coast");
            var token = '';
            this.replaceMakeRequest(function(url, data) {
                token = data.f_tok;
            });
            var Widget = RightNow.Form.extend({
                overrides: {
                    constructor: function() {
                        this.parent();
                        this.
                        on('submit', function() {
                            return new RightNow.Event.EventObject({
                                name: 'ruin',
                                value: 'show'
                            });
                        })
                        .fire('submit', new RightNow.Event.EventObject(this, { data: { ambition: 'stay' }}))
                        .fire('send');
                    }
                }
            });

            new Widget({
                attrs: {},
                js: { f_tok: 'token' }
            }, 'widget_north_coast', Y);

            this.wait(function() {
                Y.Assert.areSame('token', token);
                this.restoreMakeRequest();
            }, 2);
        },

        "New form token is requested before form is submitted when the existing token has expired": function() {
            var requestMade = false,
                tokenRequested = false,
                formToken = '';

            createForm('no_more');

            this.replaceMakeRequest(function(url, data) {
                requestMade = url === '/ci/ajaxRequest/sendForm';
                formToken = data.f_tok;
            });

            RightNow.Event.on('evt_formTokenRequest', function(name, args) {
                Y.Assert.areSame('RightNow.Form', args[0].w_id);
                Y.Assert.areSame('token', args[0].data.formToken);

                tokenRequested = true;
            });

            var Widget = RightNow.Form.extend({
                overrides: {
                    constructor: function() {
                        this.parent();
                        this.
                        on('submit', function() {
                            return new RightNow.Event.EventObject({
                                name: 'you',
                                value: 'me'
                            });
                        })
                        .fire('submit', new RightNow.Event.EventObject(this, { data: { disclose: 'nothing' }}))
                        .fire('send');
                    }
                }
            });

            new Widget({
                attrs: {},
                js: { f_tok: 'token', formExpiration: -1 }
            }, 'widget_no_more', Y);

            Y.assert(!requestMade, "shouldn't make request without getting a new token first");
            Y.assert(tokenRequested, "token should get requested");

            RightNow.Event.fire('evt_formTokenUpdate', new RightNow.Event.EventObject({ instanceID: 'RightNow.Form' }, { data: { newToken: 'arise' }}));
            Y.assert(requestMade, "pending request should get made as soon as new token arrives");
            Y.Assert.areSame(formToken, 'arise');

            this.restoreMakeRequest();
        }
    }));

    tests.add(new Y.Test.Case({
        name: "Form.formToken API",

        setUp: function () {
            this.makeRequest = RightNow.Ajax.makeRequest;
            RightNow.Ajax.makeRequest = function () { /* Don't actually request a new token. */ };
        },

        tearDown: function () {
            RightNow.Ajax.makeRequest = this.makeRequest;
            this.makeRequest = null;
        },

        fireTokenResponse: function () {
            RightNow.Event.fire('evt_formTokenUpdate', new RightNow.Event.EventObject({ instanceID: 'RightNow.Form' }, { data: { newToken: 'tonite' }}));
        },

        "Callback given to #onNewToken gets a new token": function () {
            var newToken = '';
            var callback = function (token) { newToken = token; };
            RightNow.Form.formToken.init('token', -1);
            RightNow.Form.formToken.onNewToken(callback);
            this.fireTokenResponse();
            Y.Assert.areSame('tonite', newToken);
        },

        "#onNewToken properly applies context": function () {
            var context = {};
            var callback = function () { this.called = true; };
            RightNow.Form.formToken.init('token', -1);
            RightNow.Form.formToken.onNewToken(callback, context);
            this.fireTokenResponse();
            Y.assert(context.called);
        },

        "#onNewToken defaults to global context": function () {
            var calledGlobally;
            var callback = function () { calledGlobally = this.alert === window.alert; };
            RightNow.Form.formToken.init('token', -1);
            RightNow.Form.formToken.onNewToken(callback);
            this.fireTokenResponse();
            Y.assert(calledGlobally);
        },

        "#onNewToken calls callback asynchronously when token isn't expired": function () {
            var called;
            var callback = function () { called = true; };
            RightNow.Form.formToken.init('token', 1000);
            RightNow.Form.formToken.onNewToken(callback);
            Y.assert(!called);
            this.wait(function () {
                Y.assert(called);
            }, 1);
        },

        "#onNewToken subscribers are cleared after new token arrives": function () {
            var callback = function () { this.called = true; };
            RightNow.Form.formToken.init('token', -1);
            RightNow.Form.formToken.onNewToken(callback, callback);
            this.fireTokenResponse();
            Y.assert(callback.called);
            callback.called = false;
            this.fireTokenResponse();
            Y.assert(!callback.called);
        }
    }));

    tests.add(new Y.Test.Case({
        name: "Error response handling",

        "Response error callback fires `responseError` event": function () {
            RightNow.Ajax.makeRequest = function (url, data, options) {
                setTimeout(function () {
                    options.failureHandler.call(options.scope, {
                        status: 404
                    });
                }, 200);
            };
            var Widget = RightNow.Form.extend({
                overrides: {
                    constructor: function () {
                        this.data.js.f_tok = 'token';
                        this.parent();
                        this.on("responseError", this._callback, this);
                        this.fire("submit", new RightNow.Event.EventObject());
                        this.fire("send", new RightNow.Event.EventObject());
                    }
                },
                _callback: function () {
                    this.calledWith = Array.prototype.slice.call(arguments);
                }
            });
            createForm("form10", "/bananas");
            var widget = new Widget({
                attrs: {on_success_url: '/fake/endpoint'},
                js: {}
            }, 'widget_form10', Y);
            this.wait(function () {
                Y.Assert.areSame("responseError", widget.calledWith[0]);
                Y.Assert.areSame(404, widget.calledWith[1][0].data.status);
            }, 1000);
        }
    }));

    tests.add(new Y.Test.Case({
        name : "privateMembersHiddenTest",

        testPrivateMembers: function() {
            UnitTest.recursiveMemberCheck(Y, RightNow.Form);
        }
    }));

    return tests;
});
UnitTest.run();
