UnitTest.addSuite({
    type: UnitTest.Type.Framework,
    jsFiles: ['/euf/core/debug-js/modules/widgetHelpers/EventProvider.js']
}, function(Y){
    var Test = new Y.Test.Suite({
        name: "RightNow.EventProvider",
        setUp: function() {
            Y.Assert.areSameObject = function(expected, actual, message) {
                if ((expected + "") !== (actual + "")) {
                    throw new Y.Assert.ComparisonFailure(this._formatMessage(message, "Objects aren't the same."), expected, actual);
                }
            };
        }
    });
    Test.add(new Y.Test.Case({
        name: "testInitialization",

        testInstanceOf: function() {
            var provider = new RightNow.EventProvider({}, 'testInstance', Y);
            Y.Assert.isTrue(provider instanceof RightNow.EventProvider);
        },

        testProperties: function() {
            var provider = new RightNow.EventProvider({}, 'testInstance', Y);
            Y.Assert.isNotUndefined(provider.fire);
            Y.Assert.isNotUndefined(provider.on);
            Y.Assert.isNotUndefined(provider._addEventHandler);
            Y.Assert.isNotUndefined(provider._getEventHandlers);
            Y.Assert.isNotUndefined(provider._events);
            Y.Assert.isObject(provider._events);
            Y.Assert.isObject(provider._eventHandlers);
        }
    }));
    Test.add(new Y.Test.Case({
        name: "testAddEventHandler",

        testAddEventHandler: function() {
            var provider = new RightNow.EventProvider({}, 'testInstance', Y);
            Y.Assert.areSameObject({}, provider._getEventHandlers("foo"));
            var handler = function(){};
            provider._addEventHandler("foo", handler);
            Y.Assert.areSameObject(handler, provider._getEventHandlers("foo"));
            provider._addEventHandler("foo", "bar");
            Y.Assert.areSame("bar", provider._getEventHandlers("foo"));
            Y.Assert.areSame(provider, provider._addEventHandler("no", "no"));
        }
    }));

    Test.add(new Y.Test.Case({
        name: "testEventsWithoutHandler",

        _should: {
            error: {
                testNoHandler: true,
                testNoParams: true
            }
        },

        testEventsWithoutHandler: function() {
            var provider = new RightNow.EventProvider({}, 'testInstance', Y);
            Y.Assert.areSameObject({}, provider._getEventHandlers());
            var scope = this;
            var arg = {a: "b"};
            var handler = function(name, param) {
                Y.Assert.areSameObject(scope, this);
                Y.Assert.areSame("foo", name);
                Y.Assert.isArray(param);
                Y.Assert.areSameObject(arg, param[0]);
            };
            provider.on("foo", handler, this);
            provider.fire("foo", arg);
        },

        testNoHandler: function() {
            new RightNow.EventProvider({}, 'testInstance', Y).on("foo", "bar", "baz");
        },

        testNoParams: function() {
            new RightNow.EventProvider({}, 'testInstance', Y).on();
        }
    }));

    Test.add(new Y.Test.Case({
        name: 'Test detach',

        "Detaching something that wasn't subscribed is a no-op and doesn't throw an error": function() {
            var ep = new RightNow.EventProvider({}, 'testInstance', Y);
            Y.Assert.areSameObject({}, ep._events);
            Y.Assert.areSameObject({}, ep._eventHandlers);

            ep.detach('bananaEvent', function() {});

            Y.Assert.areSameObject({}, ep._events);
            Y.Assert.areSameObject({}, ep._eventHandlers);

            ep.detach(null, function() {});

            Y.Assert.areSameObject({}, ep._events);
            Y.Assert.areSameObject({}, ep._eventHandlers);
        },

        "Detaching with context removes the specific callback for that event": function() {
            var context = { some: 'thing' },
                callback = function() { this.some = 'none'; },
                ep = new RightNow.EventProvider({}, 'testInstance', Y);

            function handlers() {
                if (Object.keys) return Object.keys(ep._events).length;
                var i, keys = 0;
                for (i in ep._events) {
                    if (ep._events.hasOwnProperty(i)) {
                        keys++;
                    }
                }
                return keys;
            }

            ep.on('banana', callback, context)  // Targeting
              .on('banana', callback)           // No context
              .on('banana', callback, this)     // Different context
              .on('pie', callback, context);    // Different event, same context

            Y.Assert.areSame(2, handlers());
            Y.Assert.areSame(3, ep._events.banana.length);

            ep.detach('banana', callback, context);

            Y.Assert.areSame(2, handlers());
            Y.Assert.areSame(2, ep._events.banana.length);
            // Other handlers are intact
            Y.Assert.isUndefined(ep._events.banana[0].context);
            Y.Assert.areSameObject(this, ep._events.banana[1].context);
            Y.Assert.areSameObject(context, ep._events.pie[0].context);
        },

        "Detaching without context removes anywhere that callback is subscribed": function() {
            var context = { some: 'thing' },
                callback = function() { this.some = 'none'; },
                ep = new RightNow.EventProvider({}, 'testInstance', Y);

            function handlers() {
                if (Object.keys) return Object.keys(ep._events).length;
                var i, keys = 0;
                for (i in ep._events) {
                    if (ep._events.hasOwnProperty(i)) {
                        keys++;
                    }
                }
                return keys;
            }

            ep.on('banana', callback, context)  // Targeting
              .on('banana', callback)           // No context
              .on('banana', callback, this)     // Different context
              .on('pie', callback, context);    // Different event, same context

            Y.Assert.areSame(2, handlers());
            Y.Assert.areSame(3, ep._events.banana.length);

            ep.detach('banana', callback);

            Y.Assert.areSame(2, handlers());
            Y.Assert.areSame(0, ep._events.banana.length);
            // Other event is intact
            Y.Assert.areSameObject(context, ep._events.pie[0].context);
        }
    }));

    Test.add(new Y.Test.Case({
        name: 'Test detach without handler',

        _should: {
            error: {
                testNoHandler: true,
                testNoParams: true
            }
        },

        testNoHandler: function() {
            new RightNow.EventProvider({}, 'testInstance', Y).detach('foo', 'bar', 'baz');
        },

        testNoParams: function() {
            new RightNow.EventProvider({}, 'testInstance', Y).detach();
        }
    }));

    Test.add(new Y.Test.Case({
        name: 'Test once subscription',

        "One-timer is removed after one call": function() {
            var ep = new RightNow.EventProvider({}, 'testInstance', Y),
                called = 0,
                handler = function() {
                    ++called;
                };

            ep.once('banana', handler).fire('banana').fire('banana');

            Y.Assert.areSame(1, called);
            Y.Assert.areSame(0, ep._events.banana.length);
        },

        "The correct one-timers are removed": function() {
            var ep = new RightNow.EventProvider({}, 'testInstance', Y),
                calls = 0;
            ep
              .on('banana', function() {
                calls += 1;
              })
              .once('banana', function() {
                calls += 2;
              })
              .on('banana', function() {
                calls += 3;
              })
              .once('banana', function() {
                calls += 4;
              });

            Y.Assert.areSame(4, ep._events.banana.length);

            ep.fire('banana');

            Y.Assert.areSame(2, ep._events.banana.length);
            Y.Assert.areSame(10, calls);
            calls = 0;

            ep.fire('banana');

            Y.Assert.areSame(4, calls);
        }
    }));

    Test.add(new Y.Test.Case({
        name: 'Test once errors',

        _should: {
            error: {
                testNoHandler: true,
                testNoParams: true
            }
        },

        testNoHandler: function() {
            new RightNow.EventProvider({}, 'testInstance', Y).once('foo', 'bar', 'baz');
        },

        testNoParams: function() {
            new RightNow.EventProvider({}, 'testInstance', Y).once();
        }
    }));

    Test.add(new Y.Test.Case({
        name: "testItAll",

        testItAll: function() {
            var provider = new RightNow.EventProvider({}, 'testInstance', Y);
            var arg = {
                data: "yes"
            };
            var modifiedArg = {
                data: "no"
            };
            var assert = Y.Assert;

            var fooHandler = {
                pre: function(param) {
                    assert.areSame(arg, param);
                }
            };
            var barHandler = {
                during: function(param) {
                    assert.areSame(arg, param);
                }
            };
            var bananaHandler = {
                post: function(param) {
                    assert.areSame(arg, param);
                }
            };
            provider._addEventHandler("foo", fooHandler)
                    ._addEventHandler("bar", barHandler)
                    ._addEventHandler("banana", bananaHandler);

            Y.Assert.areSameObject(fooHandler, provider._getEventHandlers("foo"));
            Y.Assert.areSameObject(barHandler, provider._getEventHandlers("bar"));
            Y.Assert.areSameObject(bananaHandler, provider._getEventHandlers("banana"));

            provider.on("foo", function(name, param) {
                assert.isArray(param);
                assert.areSameObject(arg, param[0]);
            }).fire("foo", arg).fire("bar").fire("baz").fire("nonononono");

            fooHandler = {
                pre: function(param) {
                    assert.areSameObject(arg, param);
                    assert.isString(this.foo);
                }
            };
            barHandler = {
                during: function(param) {
                    assert.areSameObject(arg, param);
                    assert.isString(this.foo);
                }
            };
            bananaHandler = {
                post: function(param) {
                    assert.areSameObject(arg, param);
                    assert.areSameObject(modifiedArg, param);
                    assert.isString(this.foo);
                }
            };
            provider = RightNow.EventProvider.extend({
                constructor: function() {
                    this._addEventHandler("foo", fooHandler)
                        ._addEventHandler("bar", barHandler)
                        ._addEventHandler("banana", bananaHandler);
                },
                foo: "yes"
            });

            var consume = function(type) {
                var supplier = new provider({}, 'testInstance', Y);
                if (type === 1) {
                    supplier.on("foo", this.handleFoo, this).fire("foo", arg);
                }
                else if (type === 2) {
                    supplier.on("bar", this.handleBar, this).fire("bar", arg);
                }
                else if (type === 3) {
                    supplier.on("banana", this.handleBanana, this).fire("banana", arg);
                }
            };
            consume.prototype = {
                handleFoo: function(name, param) {
                    assert.areSame("foo", name);
                    assert.isArray(param);
                    assert.areSameObject(arg, param[0]);
                    assert.areSame("yes", this.someProp);
                    this.someProp += "" + Math.random();
                    return modifiedArg;
                },
                handleBar: function(name, param) {
                    assert.areSame("bar", name);
                    assert.isArray(param);
                    assert.areSameObject(arg, param[0]);
                    assert.areSame("yes", this.someProp);
                    this.someProp += "" + Math.random();
                    return modifiedArg;
                },
                handleBanana: function(name, param) {
                    assert.areSame("banana", name);
                    assert.isArray(param);
                    assert.areSameObject(arg, param[0]);
                    assert.areSame("yes", this.someProp);
                    this.someProp += "" + Math.random();
                    return modifiedArg;
                },
                someProp: "yes"
            };
            new consume(1); new consume(2); new consume(3);
        }
    }));

    Test.add(new Y.Test.Case({
        name: 'Test filter subscribers',

        'The list of subscribers should be appropriately filtered': function() {
            var eventOneCalled = false,
                eventTwoCalled = false,
                eventThreeCalled = false;

            var filterProvider = RightNow.EventProvider.extend({
                overrides: {
                    constructor: function() {
                        this.parent();
                        this._addSubscribersFilter('testEvent', function(subscribers) {
                            var excludedIndices = [];

                            //Skip the first and third subscribers
                            Y.Array.each(subscribers, function(subscriber, index) {
                                if(subscriber.context.testValue !== 2) {
                                    excludedIndices.push(index);
                                }
                            });
                            return excludedIndices;
                        }, this);

                        this._addEventHandler('testEvent');

                        this.on('testEvent', function() {
                            eventOneCalled = true;
                        }, {testValue: 1});

                        this.on('testEvent', function() {
                            eventTwoCalled = true;
                        }, {testValue: 2});

                        this.on('testEvent', function() {
                            eventThreeCalled = true;
                        }, {testValue: 3});

                        this.fire('testEvent');
                    }
                }
            });

            var instance = new filterProvider({}, 'testInstance', Y);
            Y.Assert.isFalse(eventOneCalled);
            Y.Assert.isTrue(eventTwoCalled);
            Y.Assert.isFalse(eventThreeCalled);
        }
    }));

    Test.add(new Y.Test.Case({
        name: 'TestPreHandlerExit',

        testPreHandlerForcedExit: function() {
            var testProvider = RightNow.EventProvider.extend({
                overrides: {
                    constructor: function() {
                        this.parent();
                        this._addEventHandler('testEvent', {
                            pre: function(eo) {
                                if(eo.testData === 1)
                                    return false;
                                else
                                    Y.Assert.areSame(eo.testData, 2);
                            },
                            during: function(eo) {
                                Y.Assert.areSame(eo.testData, 2);
                                Y.Assert.areSame(eo.addedData, 2);
                            },
                            post: function(eo) {
                                Y.Assert.areSame(eo.testData, 2);
                            }
                        });
                    }
                }
            });

            var impl = new testProvider({}, 'testInstance', Y);
            impl.on('testEvent', function(evtName, eo) {
                Y.Assert.areSame(eo[0].testData, 2);
                eo[0].addedData = 2;
                return eo[0];
            });

            impl.fire('testEvent', {testData: 1}); //Causes pre to drop out
            impl.fire('testEvent', {testData: 2}); //Causes pre to continue
        }
    }));

    return Test;
});
UnitTest.run();
