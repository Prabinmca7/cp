UnitTest.addSuite({
    type: UnitTest.Type.Framework,
    jsFiles: [
        '/euf/core/debug-js/RightNow.Event.js',
        '/rnt/rnw/javascript/vs/1/vsapi.js',
        '/vs/1/vsopts.js'
    ],
    namespaces: [
        'RightNow.Event'
    ]
}, function(Y) {
    var vsapiTests = new Y.Test.Suite("VisitorService.Rules.Adapter");
    ATGSvcs = Y.Mock();
    ATGSvcs.cfg = function(arg) { return arg; }
    vsapiTests.add(new Y.Test.Case(
    {
        name: 'utilFunctions',
        
        testVsq: function(){
            //_vsq should be defined
            Y.Assert.isObject(window._vsq);
        },
        
        testPollingMoreThan60: function(){
            var curTime = Math.round( new Date().getTime() /1000);
 
            var widgets =  {"firstPollTime": curTime-4000, "lastPollTime": curTime, "lastReportedWaitTime":5};
            _throttler._widgets["pac_123456"] = {"firstPollTime": curTime-4000, "lastPollTime": curTime, "lastReportedWaitTime":5};
            var pArgs = {'w_id':'pac_123458', 'name':'ProactiveChat1'};
            //raise chatqueuerequest event.
            Y.Assert.isTrue(RightNow.Event.fire('evt_chatQueueRequest', pArgs));
            //values set initially should all remain same
            Y.Assert.areSame(_throttler._widgets["pac_123456"].firstPollTime,widgets.firstPollTime);
            Y.Assert.areSame(_throttler._widgets["pac_123456"].lastPollTime, widgets.lastPollTime);
            Y.Assert.areSame(_throttler._widgets["pac_123456"].lastReportedWaitTime, widgets.lastReportedWaitTime);
        },
        testPollingLessThan60WaitTime0: function(){
            var curTime = Math.round( new Date().getTime() /1000);
            var widgets =  {"firstPollTime": curTime-3500, "lastPollTime": curTime-3500, "lastReportedWaitTime":0};
            _throttler._widgets["pac_123456"] = widgets;
            var pArgs = {'w_id':'pac_123458', 'name':'ProactiveChat1'};
            //raise chatqueuerequest event
            Y.Assert.isTrue(RightNow.Event.fire('evt_chatQueueRequest', pArgs));
            //values set initially should all remain same
            Y.Assert.areSame(_throttler._widgets["pac_123456"].firstPollTime,widgets.firstPollTime);
            Y.Assert.areSame(_throttler._widgets["pac_123456"].lastPollTime, widgets.lastPollTime);
            Y.Assert.areSame(_throttler._widgets["pac_123456"].lastReportedWaitTime, widgets.lastReportedWaitTime);

        },
        testPollingLessThan60WaitTime70: function(){
            var curTime = Math.round( new Date().getTime() /1000);
            var widgets = {"firstPollTime": curTime-600, "lastPollTime": curTime-600, "lastReportedWaitTime":70};
             _throttler._widgets["pac_123458"] ={"firstPollTime": curTime-600, "lastPollTime": curTime-600, "lastReportedWaitTime":70} ;
            var pArgs = {'w_id':'pac_123458', 'name':'ProactiveChat1'};
            //raise chatqueuerequest event
            Y.Assert.isTrue(RightNow.Event.fire('evt_chatQueueRequest', pArgs));
            //values set initially should all remain same
            Y.Assert.areSame(_throttler._widgets["pac_123458"].firstPollTime,widgets.firstPollTime);
            Y.Assert.areNotSame(_throttler._widgets["pac_123458"].lastPollTime, widgets.lastPollTime);
            Y.Assert.areSame(_throttler._widgets["pac_123458"].lastReportedWaitTime, widgets.lastReportedWaitTime);

        },
        testPollingLessThan60WaitTime70NeedThrottling: function(){
            var curTime = Math.round( new Date().getTime() /1000);
            var widgets = {"firstPollTime": curTime-600, "lastPollTime": curTime, "lastReportedWaitTime":70};
             _throttler._widgets["pac_123458"] ={"firstPollTime": curTime-600, "lastPollTime": curTime, "lastReportedWaitTime":70} ;
            var pArgs = {'w_id':'pac_123458', 'name':'ProactiveChat1'};
            //raise chatqueuerequest event
            Y.Assert.isFalse(RightNow.Event.fire('evt_chatQueueRequest', pArgs));
            //values set initially should all remain same
            Y.Assert.areSame(_throttler._widgets["pac_123458"].firstPollTime,widgets.firstPollTime);
            Y.Assert.areSame(_throttler._widgets["pac_123458"].lastPollTime, widgets.lastPollTime);
            Y.Assert.areSame(_throttler._widgets["pac_123458"].lastReportedWaitTime, widgets.lastReportedWaitTime);

        }, 
        testWaitTimeUpdatedOnChatQueueuResponseEvent: function(){
            var curTime = Math.round( new Date().getTime() /1000);
            var widgets = {"firstPollTime": curTime, "lastPollTime": curTime-3500, "lastReportedWaitTime":10};
             _throttler._widgets["pac_123458"] = {"firstPollTime": curTime, "lastPollTime": curTime-3500, "lastReportedWaitTime":10};
            var pArgs = {'w_id':'pac_123458', 'name':'ProactiveChat1'};
            var pArgsRes = {'data':pArgs, 'response':{'stats':{'expectedWaitSeconds':60}}};
            //simulate chat queue request.
            Y.Assert.isTrue(RightNow.Event.fire('evt_chatQueueRequest', pArgs));
            //value set initially should be there 
            Y.Assert.areSame(_throttler._widgets["pac_123458"].firstPollTime,widgets.firstPollTime);
            Y.Assert.areNotSame(_throttler._widgets["pac_123458"].lastPollTime, widgets.lastPollTime);
            Y.Assert.areSame(_throttler._widgets["pac_123458"].lastReportedWaitTime, widgets.lastReportedWaitTime);
            //simulate response to get new times
            Y.Assert.isTrue(RightNow.Event.fire('evt_chatQueueResponse', pArgsRes));
            Y.Assert.areNotSame(_throttler._widgets["pac_123458"].lastReportedWaitTime,10);
        },
        testCpWidgetSuppress: function(){
            var cpObj = {"name":"ConditionalChatLink","suppress":"true", "instance_id":"xyz", "module":"test", "vstype":"none", "rule":{"id":"5"}}
            var cpWidget = ["cpWidget",cpObj];
            _adptr.push(cpWidget);
            Y.Assert.areNotSame(_adptr._widgets.length, 1);
        },
        testCpWidgetNoSuppress: function(){
            var cpObj = {"name":"ConditionalChatLink","supress":"false", "instance_id":"xyz", "module":"test", "vstype":"none", "rule":{"id":"5"}}
            var cpWidget = ["cpWidget",cpObj];
            _adptr.push(cpWidget);
            Y.Assert.areSame(_adptr._widgets.length, 1);

        },
        testSpecifiedPoolId: function() {
            _adptr._poolID = "12:34";
            var generatedURL = _adptr._buildVsURI(null, null);
            Y.Assert.isTrue(generatedURL.indexOf("pool=") == -1,
                "No pool ID expected, but got: " + generatedURL);
        },
        testEmptyPoolID: function() {
            _adptr._poolID = "";
            var generatedURL = _adptr._buildVsURI(null, null);
            Y.Assert.isTrue(generatedURL.indexOf("pool=") === -1,
                "No pool ID expected, but got: " + generatedURL);
        },
        //@@@ QA 170407-000142
        testBuildUrlOffer: function() {
            _adptr._poolID = "";
            var values = {widget_id: 987, widget_name: "SomeName", vstype: "SomeType"};
            var generatedURL = _adptr._buildVsURI("OFFER", values);
            Y.Assert.isTrue(generatedURL.indexOf("/type/OFFER/val1/987/val2/SomeName/val3/SomeType") > -1,
                "Offer URL expected but got: " + generatedURL);
        },
        //@@@ QA 170407-000142
        testBuildUrlConvert: function() {
            _adptr._poolID = "";
            var generatedURL = _adptr._buildVsURI("CONVERT", "SomeConversionValue");
            Y.Assert.isTrue(generatedURL.indexOf("/type/CONVERT/val1/SomeConversionValue") > -1,
                "Convert URL expected, but got: " + generatedURL);
        }
    }));
    return vsapiTests;
}).run();
