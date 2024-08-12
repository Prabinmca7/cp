UnitTest.addSuite({
    type: UnitTest.Type.Framework,
    jsFiles: [
        '/rnt/rnw/javascript/vs/1/vsapi.js',
        '/vs/1/vsopts.js'
    ],
    namespaces: [
        'RightNow.Client.Event'
    ]
}, function(Y) {
    var vsapiTests = new Y.Test.Suite("VisitorService.Rules.Adapter");
    ATGSvcs = Y.Mock();
    ATGSvcs.cfg = function(arg) { return arg; }
    this._oldRNUtilGet = RightNow.util.Get;
    vsapiTests.add(new Y.Test.Case(
    {
        name: 'utilFunctions',
        _should: {
            ignore : {
                testSPACAction: true,
                testSCCLLoad: true,
                testSCCLReloadForCustomDataChange: true,
                testSPACReloadForCustomDataChange: true
            }
        },
        
        testVsq: function(){
            //_vsq should be defined
            Y.Assert.isObject(window._vsq);
        },
        
        testSPACAction: function() {
            var scope = this;
            RightNow.Client.Event.evt_widgetLoaded.subscribe(function(evtName, args, instance) {
                //make sure this is fired for the correct instance
                if(args[0].id !== 'spac_625344')
                    return;

                //Mock RightNow.Util.Get.Script so data requests to chat servers can be asserted
                instance._mockRNUtilGet = RightNow.util.Get = Y.Mock();
                
                //I expect the script() method to be called with the given arguments
                Y.Mock.expect(instance._mockRNUtilGet, {
                    method: "script",
                    args: [Y.Mock.Value.String, Y.Mock.Value.Object]
                });
            }, scope);

            RightNow.Client.Event.evt_dataRequest.subscribe(function(evtName, args, instance) {
                //make sure this is fired for the correct instance
                if(args[0].id !== 'spac_625344')
                    return;

                Y.Assert.areSame('evt_dataRequest', evtName);

                //verify the expectations were met
                Y.Mock.verify(instance._mockRNUtilGet);
                RightNow.util.Get = instance._oldRNUtilGet;
                scope.resume();
            }, scope);
            
            //simulate rule to fire a SPAC rule
            var action = ["synWidget", {chat_login_page_height: 500, chat_login_page_width: 500, common_fields: "{\"incidents.prod\":\"4\"}", custom_fields: "{}", div_id: "myDiv", 
                                      ee_id: "200106304784", ee_session_id: "-ea66461:14abf032de4:-eee-10.222.148.62", estara_id: "1000tYBR_3x1K1p7nLP50-gpSQyNL9CJgfXUjrfdQAEMYmoA1D", 
                                      instance_id: "spac_625344", lazy_mode: true, min_agents_avail: 10, min_agents_avail_type: "sessions", modal: false, module: "ProactiveChat", 
                                      open_in_new_window: true, ruleId: "2282954", suppress: undefined, ts: 1420555763741, type: 2, 
                                      visitor_id: "1000tYBR_3x1K1p7nLP50-gpSQyNL9CJgfXUjrfdQAEMYmoA1D1", vstype: 2, widget_id: "625344", widget_name: "spac_0"}];
            window._vsq.push(action);
            
            //there should be at least 1 spac widget loaded
            Y.Assert.areSame(window._vsq._spacWidgets.length, 1);
            var spacWidget = window._vsq._spacWidgets[0];
            Y.Assert.areSame(spacWidget.instanceId, "spac_625344");
            Y.Assert.areSame(spacWidget.state, window._vsq.WIDGET_STATE.LOADING);
            
            //wait and assert that widget gets loaded
            this.wait();
        },
        testOnBeforeDataRequestSyndicated: function(){
             var id = 'spac_625344';
             var pArgs = {'id':id, 'name':'ProactiveChat'};
             Y.Assert.isTrue(RightNow.Client.Event.evt_beforeDataRequest.fire(pArgs));
             
             // the first and last poll times should be equal
             Y.Assert.areSame( _throttler._widgets[id].firstPollTime, _throttler._widgets[id].lastPollTime );

             // the inital value of the last reported wait time should be zero
             Y.Assert.areSame( _throttler._widgets[id].lastReportedWaitTime, 0 );

             // Set the expected wait time to greater than 3 minutes
             _throttler._widgets[id].lastReportedWaitTime = 240;
             
             // We should throttle now
             Y.Assert.isFalse(RightNow.Client.Event.evt_beforeDataRequest.fire(pArgs));

             var lastPollTime = _throttler._widgets[id].lastPollTime;

             // Reset the first and last poll times to four minutes ago
             _throttler._widgets[id].firstPollTime = _throttler._widgets[id].firstPollTime - 240;
             _throttler._widgets[id].lastPollTime = _throttler._widgets[id].lastPollTime - 240;

             // Since the test runs so fast we need to wait at least one second so the last poll time will be different
             var currentTime = Math.round( new Date().getTime() /1000);
             var testTime = currentTime;

             do
             {
                 testTime = Math.round( new Date().getTime() /1000);
             }
             while( currentTime === testTime );

             // Make sure we are not throttling now
             Y.Assert.isTrue(RightNow.Client.Event.evt_beforeDataRequest.fire(pArgs));

             // Make sure the last poll time has been updated
             Y.Assert.areNotSame( _throttler._widgets[id].lastPollTime, lastPollTime );
        },
        testWaitTimeUpdateOnChatAvailabilityResponse: function(){
             var currentTime = Math.round( new Date().getTime() /1000);
             var id = 'spac_625344';
             _throttler._widgets[id] = {"firstPollTime": currentTime, "lastPollTime": currentTime, "lastReportedWaitTime":0};

             var lastReportedWaitTime = 60;
             var pArgs = {'id':id, 'name':'ProactiveChat', 'data':{'expectedWaitSeconds':lastReportedWaitTime}};

             RightNow.Client.Event.evt_chatAvailabilityResponse.fire(pArgs);
             Y.Assert.areSame( _throttler._widgets[id].lastReportedWaitTime, lastReportedWaitTime );
        },
        testWaitTimeUpdateOnConditionalChatLinkAvailabilityResponse: function(){
             var currentTime = Math.round( new Date().getTime() /1000);
             var id = 'spac_625344';
             _throttler._widgets[id] = {"firstPollTime": currentTime, "lastPollTime": currentTime, "lastReportedWaitTime":0};

             var lastReportedWaitTime = 120;
             var pArgs = {'id':id, 'name':'ProactiveChat', 'data': {'stats':{'expectedWaitSeconds':lastReportedWaitTime}}};

             RightNow.Client.Event.evt_conditionalChatLinkAvailabilityResponse.fire(pArgs);
             Y.Assert.areSame( _throttler._widgets[id].lastReportedWaitTime, lastReportedWaitTime );
        },
        // @@@ 161019-000085 [MVP] Custom Fields are Updated:Test SCCL Load
        // @@@ 161128-000059 [MVP] Chat queue identification url contains only custom fields used for queue routing : Test evt_updateChatQueueURI
        testSCCLLoad: function(){
            var scope = this;
            RightNow.Client.Event.evt_widgetLoaded.subscribe(function(evtName, args, instance) {
                //make sure this is fired for the correct instance
                if(args[0].id !== 'sccl_625388')
                    return;

                //Mock RightNow.Util.Get.Script so data requests to chat servers can be asserted
                instance._mockRNUtilGet = RightNow.util.Get = Y.Mock();

                //I expect the script() method to be called with the given arguments
                Y.Mock.expect(instance._mockRNUtilGet, {
                    method: "script",
                    args: [Y.Mock.Value.String, Y.Mock.Value.Object]
                });

                RightNow.Client.Event.evt_updateWidgetData.subscribe(function(type, args, instance) {
                    //make sure this is fired for the correct instance
                    if(args[0].id !== 'sccl_625388')
                        return;

                    scope.resume();
                }, scope);

            }, scope);

            //simulate rule to fire a SCCL rule
            var action = ["synWidget", {chat_login_page: "app/chat/chat_launch", common_fields: "{}", custom_fields: "{}",
                instance_id: "sccl_625388", enable_availability_check: false, ignore_preroute: true, enable_polling: false,
                label_available_immediately_template: "please click", module: "ConditionalChatLink",type: 7,
                ruleId: "8120631", visitor_id: "1000tYBR_3x1K1p7nLP50-gpSQyNL9CJgfXUjrfdQAEMYmoA1D1", vstype: 7, widget_id: "625388", widget_name: "scc2"}];
            window._vsq.push(action);

           // there should be at least 1 sccl widget loaded
            Y.Assert.areSame(window._vsq._scclWidgets.length, 1);
            var scclWidget = window._vsq._scclWidgets[0];
            Y.Assert.areSame(scclWidget.instanceID, "sccl_625388");
            Y.Assert.areSame(scclWidget.state, window._vsq.WIDGET_STATE.LOADING);

            //wait and assert that widget gets loaded
            this.wait();
        },
        // @@@ 161019-000085 [MVP] Custom Fields are Updated:Test evt_updateOnCustomDataChange event for SCCL
        testSCCLReloadForCustomDataChange: function(){
            var scope = this;
            RightNow.Client.Event.evt_updateOnCustomDataChange.subscribe(function(type, args, instance) {
                //make sure this is fired for the correct instance
                if(args[0].id !== 'sccl_625389')
                    return;
                Y.Assert.areSame(args[0].data.dataFields.name, "38");
                Y.Assert.areSame(args[0].data.dataFields.value, "NewValue");
                },scope);


            var widgetData = {chat_login_page: "app/chat/chat_launch", common_fields: "{}", custom_fields: "{}",
                                      instance_id: "sccl_625389", enable_availability_check: true, ignore_preroute: true, enable_polling: true, 
                                      label_available_immediately_template: "please click", module: "ConditionalChatLink", 
                                      ruleId: "8120631", visitor_id: "1000tYBR_3x1K1p7nLP50-gpSQyNL9CJgfXUjrfdQAEMYmoA1D1", vstype: 2, widget_id: "625389", widget_name: "scc2"};


            var  sccl = {instanceID: widgetData.instance_id, state: window._vsq.WIDGET_STATE.LOADED, widget: widgetData}
            window._vsq._scclWidgets.push(sccl);

            var action = ["customData", {name: "38", ruleid: "11452361", ts: 1477944531791 , value: "NewValue"}];
            window._vsq.push(action);
        },
        // @@@ 161128-000058 [MVP] SPAC widget  is automatically updated when custom data fields are changed:Test evt_updateOnCustomDataChange event for SPAC
        testSPACReloadForCustomDataChange: function(){
            var scope = this;
            RightNow.Client.Event.evt_updateOnCustomDataChange.subscribe(function(type, args, instance) {
                //make sure this is fired for the correct instance
                if(args[0].id !== 'spac_625345')
                    return;
                Y.Assert.areSame(args[0].data.dataFields.name, "38");
                Y.Assert.areSame(args[0].data.dataFields.value, "NewValue");
            },scope);


            //simulate rule to fire a SPAC rule
            var widgetData = ["synWidget", {chat_login_page_height: 500, chat_login_page_width: 500, common_fields: "{\"incidents.prod\":\"4\"}", custom_fields: "{}", div_id: "myDiv",
                ee_id: "200106304784", ee_session_id: "-ea66461:14abf032de4:-eee-10.222.148.62", estara_id: "1000tYBR_3x1K1p7nLP50-gpSQyNL9CJgfXUjrfdQAEMYmoA1D",
                instance_id: "spac_625345", lazy_mode: true, min_agents_avail: 10, min_agents_avail_type: "sessions", modal: false, module: "ProactiveChat",
                open_in_new_window: true, ruleId: "2282954", suppress: undefined, ts: 1420555763741, type: 2,
                visitor_id: "1000tYBR_3x1K1p7nLP50-gpSQyNL9CJgfXUjrfdQAEMYmoA1D1", vstype: 2, widget_id: "625345", widget_name: "spac_0"}];


            var  spac = {instanceID: widgetData.instance_id, state: window._vsq.WIDGET_STATE.LOADED, widget: widgetData}
            window._vsq._spacWidgets.push(spac);
            //window._vsq.push(widgetData);

            var action = ["customData", {name: "38", ruleid: "11452361", ts: 1477944531791 , value: "NewValue"}];
            window._vsq.push(action);

        }
    }));
    return vsapiTests;
}).run();
