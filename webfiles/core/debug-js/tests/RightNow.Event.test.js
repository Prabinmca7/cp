
//Due to a dependency this needs to be defined before the Test Suite is executed.
RightNow.Url = RightNow.Url || {getSession: function(){return "";}, getParameter: function(){return null;}};

UnitTest.addSuite({
    type: UnitTest.Type.Framework,
    jsFiles: ['/euf/core/debug-js/RightNow.Event.js'],
    namespaces: ['RightNow.Event']
}, function(Y){
    var rightnowEventTests = new Y.Test.Suite("RightNow.Event");

    function getNumberOfSubscribers(customEvent, count, i) {
        var subCount = 0;
        for (var i=0; i<customEvent.getSubs().length; i++) {
            subCount += customEvent.getSubs()[i].length;
        }
        return subCount;
    }
    function getFirstSubscriber(customEvent, i) {
        if(customEvent.getSubs()[0].length){
            return customEvent.getSubs()[0][0];
        }
        return null;
    }

    rightnowEventTests.add(new Y.Test.Case(
    {
        name : "eventCreationTest",

        testCreate: function()
        {
            var createdEvent = RightNow.Event.create("evt_eventCreationTestEvent");
            var customEvent = RightNow.Event.get("evt_eventCreationTestEvent");
            Y.Assert.areSame(createdEvent.id, customEvent.id);
            Y.Assert.areSame(customEvent.context, window);
            Y.Assert.areSame(getNumberOfSubscribers(customEvent), 0);
            Y.Assert.areSame(customEvent.type, "evt_eventCreationTestEvent");
        }
    }));

    rightnowEventTests.add(new Y.Test.Case(
    {
        name : "eventSubscribeAndFireTest",

        setUp: function()
        {
            RightNow.Event.subscribe("evt_eventSubscribeAndFireTestEvent", this.eventHandler, this, {subscribeParameter: "value"});
        },

        testSubscribeAndFire: function()
        {
            var customEvent = RightNow.Event.get("evt_eventSubscribeAndFireTestEvent");
            Y.Assert.areSame(customEvent.context, this);
            Y.Assert.areSame(1, getNumberOfSubscribers(customEvent));
            Y.Assert.areSame("evt_eventSubscribeAndFireTestEvent", customEvent.type);
            var subscribeArgument = getFirstSubscriber(customEvent);
            Y.Assert.areSame("value", subscribeArgument.args[0].subscribeParameter);
            RightNow.Event.fire("evt_eventSubscribeAndFireTestEvent", {fireParameter1: "value"}, {fireParameter2: "value"});
            RightNow.Event.unsubscribe("evt_eventSubscribeAndFireTestEvent", this.eventHandler);
            Y.Assert.areSame(0, getNumberOfSubscribers(customEvent));
            RightNow.Event.fire("evt_eventSubscribeAndFireTestEvent", {fireParameter1: "value"}, {fireParameter2: "value"});
        },

        eventHandler: function(event, args)
        {
            Y.Assert.areSame("evt_eventSubscribeAndFireTestEvent", event);
            Y.Assert.areSame(args[0].fireParameter1, "value");
            Y.Assert.areSame(args[1].fireParameter2, "value");
        }
    }));

    rightnowEventTests.add(new Y.Test.Case(
    {
        name : "miscFunctionsTest",

        testIsSameReportID: function()
        {
            var reportID = 166,
                correctReportID = [{filters:{report_id: 166}}],
                incorrectReportID = [{filters:{report_id: 156}}],
                incorrectlyFormattedArguments1 = null,
                incorrectlyFormattedArguments2 = [],
                incorrectlyFormattedArguments3 = [{filt:{prod: 156}}],
                incorrectlyFormattedArguments4 = [{filters:{prod: 156}}];
            Y.Assert.areSame(RightNow.Event.isSameReportID(correctReportID, reportID), true);
            Y.Assert.areSame(RightNow.Event.isSameReportID(incorrectReportID, reportID), false);
            Y.Assert.areSame(RightNow.Event.isSameReportID(incorrectlyFormattedArguments1, reportID), null);
            Y.Assert.areSame(RightNow.Event.isSameReportID(incorrectlyFormattedArguments2, reportID), undefined);
            Y.Assert.areSame(RightNow.Event.isSameReportID(incorrectlyFormattedArguments3, reportID), undefined);
            Y.Assert.areSame(RightNow.Event.isSameReportID(incorrectlyFormattedArguments4, reportID), false);
        },

        testBrowserHistoryManagementKey: function()
        {
            Y.Assert.areSame(RightNow.Event.browserHistoryManagementKey, "s");

            window.location.hash = "#s=asdfasdf";
            Y.Assert.isTrue(RightNow.Event.isHistoryManagerFragment());

            window.location.hash = "#s=";
            Y.Assert.isFalse(RightNow.Event.isHistoryManagerFragment());

            window.location.hash = "#s=&";
            Y.Assert.isFalse(RightNow.Event.isHistoryManagerFragment());

            window.location.hash = "#s=&gs=eyJndWlkZUlEIjoxLCJxdWVzdGlvbklEIjoxLCJyZXNwb25zZUlEIjoxLCJndWlkZVNlc3Npb24iOiJJZUVkLWdmayIsInNlc3Npb25JRCI6IkRjb25aZ2ZrIn0.";
            Y.Assert.isFalse(RightNow.Event.isHistoryManagerFragment());

            window.location.hash = "#homeTab";
            Y.Assert.isFalse(RightNow.Event.isHistoryManagerFragment());

            window.location.hash = "";
        },

        testCreateDelegate: function()
        {
            Y.Assert.isFunction(RightNow.Event.createDelegate(this, function(){alert('foo');}));
        },

        testGetDataFromFiltersEventResponse: function()
        {
            var eventArguments1 = [{
                    filters: {
                        report_id: 10,
                        allFilters: {
                            filters: {
                                p: {
                                    filters: {
                                        data: "product"
                                    }
                                }
                            }
                        }
                    }
                }];

            Y.Assert.areSame(RightNow.Event.getDataFromFiltersEventResponse(eventArguments1, "p", 10), "product");
            Y.Assert.areSame(RightNow.Event.getDataFromFiltersEventResponse(eventArguments1, "p", 11), null);

            var eventArguments2 = [{
                    filters: {
                        data: "product",
                        report_id: 10
                    }
                }];

            Y.Assert.areSame(RightNow.Event.getDataFromFiltersEventResponse(eventArguments2, "p", 10), "product");
            Y.Assert.areSame(RightNow.Event.getDataFromFiltersEventResponse(eventArguments2, "p", 11), null);

        },

        testEventObject: function()
        {
            var eventObject = new RightNow.Event.EventObject();
            Y.Assert.isInstanceOf(RightNow.Event.EventObject, eventObject);
            Y.Assert.areSame(null, eventObject.w_id);
            Y.Assert.isObject(eventObject.data);
            Y.Assert.isObject(eventObject.filters);

            var widget = {
                instanceID: "banana"
            },
            eo = {
                data: {
                    foo: "bar",
                    bar: "no"
                },
                filters: {
                    report_id: "yes"
                }
            };
            eventObject = new RightNow.Event.EventObject(widget, eo);
            Y.Assert.isInstanceOf(RightNow.Event.EventObject, eventObject);
            Y.Assert.areSame("banana", eventObject.w_id);
            Y.Assert.isObject(eventObject.data);
            Y.Assert.areSame("bar", eventObject.data.foo);
            Y.Assert.areSame("no", eventObject.data.bar);
            Y.Assert.isObject(eventObject.filters);
            Y.Assert.areSame("yes", eventObject.filters.report_id);
        },

        testInitializeFunction: function()
        {
            Y.Assert.isFunction(RightNow.Event.EventBus.initializeEventBus);
        },

        testGetWidgetsSubscribedToFTokUpdate: function()
        {
            this.instanceID = "test_12";
            RightNow.Event.subscribe("evt_formTokenUpdate", RightNow.Widgets.onFormTokenUpdate, this);
            var arr = RightNow.Event.getWidgetsSubscribedToFTokUpdate()
            Y.Assert.isObject(arr);
            Y.Assert.areSame("test_12", arr[0]);
        }
    }));

    rightnowEventTests.add(new Y.Test.Case(
    {
        name : "privateMembersHiddenTest",

        testPrivateMembers: function()
        {
            UnitTest.recursiveMemberCheck(Y, RightNow.Event);
        }
    }));

    return rightnowEventTests;
});
UnitTest.run();
