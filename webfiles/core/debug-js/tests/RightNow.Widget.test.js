YUI.add("ModuleA", function(Y) {
    Y.ModuleA = true;
});
YUI.add("ModuleB", function(Y) {
    Y.ModuleB = true;
});
YUI.add("ModuleC", function(Y) {
    Y.ModuleC = true;
});

UnitTest.addSuite({
    type: UnitTest.Type.Framework,
    namespaces: ['RightNow.Widgets', 'RightNow.Event', 'RightNow.Ajax'],
    jsFiles: ['/euf/core/debug-js/RightNow.Event.js', '/euf/core/debug-js/RightNow.Ajax.js']
}, function(Y){
    var suite = new Y.Test.Suite("RightNow.Widgets");
    suite.add(new Y.Test.Case(
    {
        name: "widget instance creation and retrieval tests",
        genericSetUp: function () {
            this.data = {"i":{"c":"TestWidget","n":"TestWidget_0","w":0},"a":{"foo":"bar"},"j":{"foo": "bar"}};
            RightNow.Widgets.TestWidget = function(data, instanceID){this.data = data; this.instanceID = instanceID;};
            RightNow.Widgets.createWidgetInstance(this.data, "abitraryContextData", "arbitraryContextToken", 1410195019926, "TestWidget_0", "standard/TestWidget", "RightNow.Widgets.TestWidget", '0');
        },

        "Info is retrieved via #getWidgetInformation": function() {
            this.genericSetUp();
            var widgetInformation = RightNow.Widgets.getWidgetInformation("TestWidget_0");
            Y.Assert.areSame(widgetInformation.javaScriptPath, "standard/TestWidget");
            Y.Assert.areSame(widgetInformation.className, "RightNow.Widgets.TestWidget");
            Y.Assert.areSame(widgetInformation.suffix, "0");
            Y.Assert.isObject(widgetInformation.instance);
        },

        "Instance is retrieved via #getWidgetInstance": function() {
            this.genericSetUp();
            var widgetInstance = RightNow.Widgets.getWidgetInstance("TestWidget_0");
            Y.Assert.isObject(widgetInstance);
            Y.Assert.isObject(widgetInstance.data);
            Y.Assert.areSame(widgetInstance.instanceID, "TestWidget_0");
            Y.Assert.areSame(widgetInstance.data.attrs.foo, "bar");
            Y.Assert.areSame(widgetInstance.data.js.foo, "bar");
            Y.Assert.areSame(widgetInstance.data.contextData, "abitraryContextData");
            Y.Assert.areSame(widgetInstance.data.contextToken, "arbitraryContextToken");
            Y.Assert.areSame(widgetInstance.data.timestamp, 1410195019926);
        },

        "Widgets are found in HTML and instantiated": function() {
            // IE8 has trouble parsing the json script tag in the way we handle it.
            if(Y.UA.ie > 8 || !Y.UA.ie) {
                var HTML = '<div id="rn_TestWidget_1396897057"></div><script type="text/json">{"data":{"i":{"c":"TestWidget","n":"TestWidget_1396897057","w":1396897057},"a":{"foo":"bar"},"j":{"foo":"bar"}},"contextData":"abitraryContextData","contextToken":"arbitraryContextToken","timestamp":1410195019926, "instanceID":"TestWidget_1396897057","javaScriptPath":"","className":"RightNow.Widgets.TestWidget","suffix":"1396897057","showWarnings":false}</script>';
                RightNow.Widgets.instantiateWidgetsFoundInContent(Y.Node.create(HTML));
                var widgetInstance = RightNow.Widgets.getWidgetInstance("TestWidget_1396897057");
                Y.Assert.isObject(widgetInstance);
                Y.Assert.isObject(widgetInstance.data);
                Y.Assert.areSame(widgetInstance.instanceID, "TestWidget_1396897057");
                Y.Assert.areSame(widgetInstance.data.attrs.foo, "bar");
                Y.Assert.areSame(widgetInstance.data.js.foo, "bar");
                Y.Assert.areSame(widgetInstance.data.contextData, "abitraryContextData");
                Y.Assert.areSame(widgetInstance.data.contextToken, "arbitraryContextToken");
                Y.Assert.areSame(widgetInstance.data.timestamp, 1410195019926);
            }
        },

        "YUI dependencies are added to the Y instance supplied to the widget function": function() {
            RightNow.Widgets.BananaWidget = function(data, id, Y) { this.Y = Y; };
            RightNow.Widgets.BananaWidget.requires = ['ModuleA'];
            var instance = this.createBanana();
            this.wait(function() {
                var info = this.getBanana(instance);
                Y.Assert.isTrue(info.instance.Y.ModuleA);
            }, 100);
        },

        "YUI dependencies are added to the Y instance supplied to the widget instance": function() {
            RightNow.Widgets.BananaWidget = RightNow.Widgets.extend({constructor: function(){}}, {requires: ['ModuleB']});
            var instance = this.createBanana();
            this.wait(function() {
                var info = this.getBanana(instance);
                Y.Assert.isTrue(info.instance.Y.ModuleB);
            }, 100);
        },

        "YUI dependencies can be specified on a per-JS-module basis": function() {
            RightNow.Env = function() { return 'standard'; };
            // Standard (current) module
            RightNow.Widgets.BananaWidget = function(data, id, Y) { this.Y = Y; };
            RightNow.Widgets.BananaWidget.requires = {standard: ['ModuleC'], mobile: null};
            var instance = this.createBanana();
            this.wait(function() {
                var info = this.getBanana(instance);
                Y.Assert.isTrue(info.instance.Y.ModuleC);
            }, 100);

            // Mobile (not current) module
            RightNow.Widgets.BananaWidget = RightNow.Widgets.extend({}, {requires: {mobile: ['ModuleB'], foobar: ['ModuleC']}});
            instance = this.createBanana();
            this.wait(function() {
                this.assertDefaultYuiInstance(this.getBanana(instance));
            }, 100);

            // Standard (truthy)
            RightNow.Widgets.BananaWidget = RightNow.Widgets.extend({}, {requires: {standard: true, mobile: ['ModuleB'], foobar: ['ModuleC']}});
            instance = this.createBanana();
            this.wait(function() {
                this.assertDefaultYuiInstance(this.getBanana(instance));
            }, 100);

            // Standard (empty array)
            RightNow.Widgets.BananaWidget = RightNow.Widgets.extend({}, {requires: {standard: [], mobile: ['ModuleB'], foobar: ['ModuleC']}});
            instance = this.createBanana();
            this.wait(function() {
                this.assertDefaultYuiInstance(this.getBanana(instance));
            }, 100);

            // Standard (some object)
            RightNow.Widgets.BananaWidget = RightNow.Widgets.extend({}, {requires: {standard: {foo: 'bar'}, mobile: ['ModuleB'], foobar: ['ModuleC']}});
            instance = this.createBanana();
            this.wait(function() {
                this.assertDefaultYuiInstance(this.getBanana(instance));
            }, 100);
        },

        createBanana: function() {
            this.calls || (this.calls = 0);
            this.bananaData || (this.bananaData = {"i":{"c":"BananaWidget","n":"BananaWidget_0","w":0},"a":{"foo":"bar"},"j":{"foo": "bar"}});
            RightNow.Widgets.createWidgetInstance(this.bananaData, "abitraryContextData", "arbitraryContextToken", (new Date().getTime()), 'banana_' + (++this.calls), 'standard/BananaWidget', 'RightNow.Widgets.BananaWidget', this.calls + '');
            return this.calls;
        },

        getBanana: function(id) {
            return RightNow.Widgets.getWidgetInformation('banana_' + id);
        },

        assertDefaultYuiInstance: function(info) {
            info = info.instance.Y;
            Y.Assert.isNotUndefined(info.Lang);
            Y.Assert.isNotUndefined(info.History);
            Y.Assert.isNotUndefined(info.Node);
        }
    }));
    suite.add(new Y.Test.Case({
        name: "Test widget instantiation events",
        "Each widget should fire an instantiation event": function() {
            //Set count, instantiate some widgets, listen for events
            RightNow.Widgets.setInitialWidgetCount(2);
            var calledCount = 0,
                eventArguments;

            RightNow.Event.on('evt_WidgetInstantiated', function(name, args) {
                calledCount++;
                eventArguments = args;
            });

            var widget = RightNow.Widgets.extend({
                constructor: function() {
                    Y.Assert.areSame(1, calledCount);
                    Y.Assert.areSame('#rn_instance1', eventArguments[0].baseSelector);
                    Y.Assert.areSame('instance1', eventArguments[0].instanceID);
                    Y.Assert.areSame('widget1', eventArguments[0].name);
                }
            });

            var widget2 = RightNow.Widgets.extend({
                constructor: function() {
                    Y.Assert.areSame(2, calledCount);
                    Y.Assert.areSame('#rn_instance2', eventArguments[0].baseSelector);
                    Y.Assert.areSame('instance2', eventArguments[0].instanceID);
                    Y.Assert.areSame('widget2', eventArguments[0].name);
                }
            });

            new widget({info: {class_name: 'widget1'}}, 'instance1', Y);
            new widget2({info: {class_name: 'widget2'}}, 'instance2', Y);
        },
        "When all widgets are created an event should fire": function() {
            RightNow.Widgets.setInitialWidgetCount(2);

            var calledComplete = false;
            RightNow.Event.on('evt_WidgetInstantiationComplete', function(name, args) {
                Y.Assert.areSame('evt_WidgetInstantiationComplete', name);
                calledComplete = true;
            });

            var widget = RightNow.Widgets.extend({
                constructor: function() {}
            });
            new widget({info: {class_name: 'widget1'}}, 'instance1', Y);
            new widget({info: {class_name: 'widget1'}}, 'instance1', Y);
            Y.Assert.isTrue(calledComplete);
        }
    }));

    suite.add(new Y.Test.Case({
        name : "extendTest",
        testParentConstructorNeverCalled: function() {
            var base = RightNow.Widgets.extend({
                constructor: function() {
                    this.propSet = true;
                },
                someMethod: function() {

                }
            });
            var child = base.extend({
                constructor: function() {
                    this.propSet = false;
                }
            });
            var instance = new child();
            Y.Assert.isTrue(instance.propSet);
            Y.Assert.isFunction(instance.someMethod);
        },

        testParentConstructorManuallyCalled: function() {
            var base = RightNow.Widgets.extend({
                constructor: function(childCalling) {
                    this.propSet = true;
                    if (childCalling) {
                        Y.Assert.areSame('itsMeYourChild', childCalling);
                        Y.Assert.areSame('bar', this.property);
                        this.childCalled = true;
                    }
                },
                someMethod: function() {
                },
                property: 'foo'
            });
            var baseInstance = new base();
            Y.Assert.isTrue(baseInstance.propSet);

            var child = base.extend({
                overrides: {
                    constructor: function() {
                        Y.Assert.isUndefined(this.propSet);
                        Y.Assert.isFunction(this.someMethod);
                        Y.Assert.areSame('foo', this.property);
                        this.property = 'bar';
                        this.parent('itsMeYourChild');
                        Y.Assert.isTrue(this.propSet);
                        this.propSet = false;
                        Y.Assert.isTrue(this.childCalled);
                    }
                }
            });
            var childInstance = new child();
            Y.Assert.areSame('foo', baseInstance.property);
            Y.Assert.areSame('bar', childInstance.property);
            Y.Assert.isFalse(childInstance.propSet);
            Y.Assert.isTrue(baseInstance.propSet);
            Y.Assert.isUndefined(baseInstance.childCalled);
        },

        testOverridesAllowed: function() {
            var base = RightNow.Widgets.extend({
                constructor: function() {
                    Y.Assert.isUndefined(this.parent);
                    this.parentConstructed = true;
                },
                someMethod: function() {
                    return "parent";
                }
            });
            var child = base.extend({
                constructor: function() {
                    this.childConstructed = true;
                    Y.Assert.areSame("child", this.someMethod());
                },
                overrides: {
                    someMethod: function() {
                        return "child" + this.parent();
                    },
                    iHaveNoParentToOverride: function() {
                        Y.Assert.isUndefined(this.parent);
                        return true;
                    }
                },
                // This method is replaced by the one in `overrides`
                someMethod: function() {
                    return "child";
                }
            });
            var childInstance = new child();
            Y.Assert.isUndefined(childInstance.overrides);
            Y.Assert.isTrue(childInstance.parentConstructed);
            Y.Assert.areSame("childparent", childInstance.someMethod());
            Y.Assert.isTrue(childInstance.iHaveNoParentToOverride());
        },

        testHierarchy: function() {
            var a = RightNow.Widgets.extend({
                add: function(add) {
                    return 1 + (add || 0);
                }
            });
            var b = a.extend({
                overrides: {
                    add: function(add) {
                        return 2 + this.parent(add);
                    }
                }
            });
            var c = b.extend({
                overrides: {
                    add: function(add) {
                        return 3 + this.parent(add);
                    }
                }
            });
            Y.Assert.areSame(1, new a().add());
            Y.Assert.areSame(3, new b().add());
            Y.Assert.areSame(6, new c().add());
        },

        testCalling: function() {
            var a = RightNow.Widgets.extend({
                banana: function() {
                    return this._invoke();
                },
                _invoke: function() {
                    return "parent";
                }
            });
            var b = a.extend({
                overrides: {
                    _invoke: function() {
                        return "child";
                    }
                }
            });
            var aInstance = new a();
            Y.Assert.areSame("parent", aInstance.banana());
            var bInstance = new b();
            Y.Assert.areSame("child", bInstance.banana());
        },

        testStaticOverridesAllowed: function() {
            var base = RightNow.Widgets.extend({
                constructor: function() {
                    this.three = "2";
                },
                parentVar: true
            }, {
                staticMethod: function() {
                    return "static parent";
                },
                anotherStatic: function() {
                    return "another";
                },
                one: "1"
            });
            var child = base.extend({
                aMethod: function() {

                }
            }, {
                staticMethod: function() {
                    Y.Assert.areSame("2", this.two);
                    Y.Assert.areSame("1", this.one);
                    Y.Assert.isUndefined(this.parent);
                    Y.Assert.isUndefined(this.aMethod);
                    Y.Assert.isUndefined(this.parentVar);
                    Y.Assert.isFunction(this.anotherStatic);
                    Y.Assert.areSame("another", this.anotherStatic());
                    return "static child";
                },
                two: "2"
            });
            Y.Assert.areSame("static child", child.staticMethod());
            Y.Assert.areSame("1", base.one);
            Y.Assert.areSame("2", child.two);
        },

        testGlobalObjectProto: function() {
            Object.prototype.banana = function() {
                return 'sketchy!';
            };
            var a = RightNow.Widgets.extend({
                method: function() {
                    return this.banana();
                }
            });
            Y.Assert.areSame('sketchy!', a.banana());
            Y.Assert.isUndefined(a.method);
            Y.Assert.areSame('sketchy!', a.prototype.method());
            Y.Assert.areSame('sketchy!', new a().method());

            var b = a.extend({
                banana: function() {
                    return 'hey!';
                }
            });
            Y.Assert.areSame('sketchy!', b.banana());
            Y.Assert.areSame('sketchy!', new b().method());
            Y.Assert.areSame('sketchy!', new b().banana());

            var c = a.extend({
                overrides: {
                    banana: function() {
                        return 'hey!';
                    }
                }
            });

            Y.Assert.areSame('sketchy!', c.banana());
            Y.Assert.areSame('hey!', new c().method());
            Y.Assert.areSame('hey!', new c().banana());

            delete Object.prototype.banana;
        },

        testWidgetPredefines: function() {
            var outerID = Y.id;
            var widget = RightNow.Widgets.extend({
                constructor: function(data, instanceID, Y) {
                    Y.Assert.areSame(this.data, data);
                    Y.Assert.areSame("no", this.data.yes);
                    Y.Assert.areSame(this.instanceID, instanceID);
                    Y.Assert.areSame(this.instanceID, "widgetFoo");
                    Y.Assert.areSame(this.baseDomID, "rn_widgetFoo");
                    Y.Assert.areSame(this.baseSelector, "#rn_widgetFoo");
                    Y.Assert.areSame(outerID, Y.id);
                }
            });
            var instance = new widget({yes: "no"}, "widgetFoo", Y);
        },

        testNamespace: function()
        {
            Y.Assert.areSame('undefined', typeof Custom);
            var newNamespace = RightNow.namespace('Custom.Widgets.FormInput');
            Y.Assert.isObject(Custom.Widgets.FormInput);
            Y.Assert.areEqual(newNamespace, Custom.Widgets.FormInput);
            newNamespace = RightNow.namespace('Custom.Widgets.FormInput', 'Custom.Widgets.FormSubmit', 'Custom.Widgets.input.FormInput');
            Y.Assert.isObject(Custom.Widgets.FormSubmit);
            Y.Assert.isObject(Custom.Widgets.input.FormInput);
            Y.Assert.areEqual(newNamespace, Custom.Widgets.input.FormInput);
            Y.Assert.isTypeOf('undefined', Custom.Widgets.input.FormSubmit);
        }
    }));

    suite.add(new Y.Test.Case(
    {
        name : "privateMembersHiddenTest",

        testPrivateMembers: function()
        {
            UnitTest.recursiveMemberCheck(Y, RightNow.Widgets);
        }
    }));

    suite.add(new Y.Test.Case(
    {
        name : "FormToken refresh tests",

        setUp: function () {
            this.makeRequest = RightNow.Ajax.makeRequest;
            this.getConfig = RightNow.Interface.getConfig;
            this.formTokenExpirationValue = RightNow.Interface.getConfig('SUBMIT_TOKEN_EXP'),

            RightNow.Ajax.makeRequest = function () {
                RightNow.Ajax.makeRequest.calledWith = Array.prototype.slice.call(arguments);
            };

            RightNow.Interface.getConfig = function(index) {
                return RightNow.Interface.getConfig.configVal;
            };
        },

        tearDown: function () {
            RightNow.Ajax.makeRequest = this.makeRequest;
            RightNow.Interface.getConfig = this.getConfig;
        },

        testCheckFormTokenRefresh: function() {
            var f_tok = 'spongebob',
                widgetReference = {data: {js: {f_tok: f_tok}}};

            RightNow.Interface.getConfig.configVal = 30;
            RightNow.Widgets.checkFormTokenRefresh(widgetReference);
            Y.Assert.isUndefined(RightNow.Ajax.makeRequest.calledWith, "Token Refresh should not have fired, since time elapsed should be 0");

            // Wait a tick to ensure some time has elapsed
            this.wait(function() {
                RightNow.Interface.getConfig.configVal = 0;
                RightNow.Widgets.checkFormTokenRefresh(widgetReference);
                Y.Assert.areSame(RightNow.Ajax.makeRequest.calledWith[1].formToken, f_tok, "Token Refresh should have fired, since time elapsed should be greater than 0");

                RightNow.Ajax.makeRequest.calledWith = null;
                RightNow.Interface.getConfig.configVal = 30;
                RightNow.Widgets.checkFormTokenRefresh(widgetReference);
                Y.Assert.isNull(RightNow.Ajax.makeRequest.calledWith, "Token Refresh should not have fired, since time elapsed should be 0");
            }, 10);
        }
    }));

    return suite;
}).run();
