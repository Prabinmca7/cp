UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'ChatCobrowsePremium_0'
}, function(Y, widget, baseSelector){
    var ChatCobrowsePremiumTests = new Y.Test.Suite({
        name: "standard/chat/ChatCobrowsePremium"
    });

    ChatCobrowsePremiumTests.add(new Y.Test.Case({
        name: "Functionality",

        testLiveLookApi: function() {
            //test that LiveLook Api objects are defined
            Y.Assert.isObject(CoBrowseLauncher);
            Y.Assert.isFunction(CoBrowseLauncher.getEnvironment);
            Y.Assert.isFunction(CoBrowseLauncher.isEnvironmentSupported);
            Y.Assert.isBoolean(CoBrowseLauncher.isEnvironmentSupported());
        },

        testChatStatusChange: function() {
            RightNow.Chat = {
                Model: {
                    ChatState: {
                        UNDEFINED: 0,
                        SEARCHING: 1,
                        CONNECTED: 2,
                        REQUEUED: 3,
                        CANCELLED: 4,
                        DEQUEUED: 5,
                        DISCONNECTED: 6,
                        RECONNECTING: 7,
                    },
                    ChatCoBrowseStatusCode: {
                        ACCEPTED: 0,
                        DECLINED: 1,
                        UNAVAILABLE: 2,
                        TIMEOUT: 3,
                        STARTED: 4,
                        STOPPED: 5,
                        ERROR: 6,
                    }
                }
            };
            widget._inCoBrowse = true;
            RightNow.Event.fire('evt_chatStateChangeResponse', new RightNow.Event.EventObject(widget,
                    {data: {currentState: RightNow.Chat.Model.ChatState.CANCELLED}}));

            //test that '_inCoBrowse' is set to false when currentState is not CONNECTED
            Y.Assert.isFalse(widget._inCoBrowse);
        },

        testCoBrowseStatusChange: function() {
            RightNow.Chat = {
                Model: {
                    ChatCoBrowseStatusCode: {
                        ACCEPTED: 0,
                        DECLINED: 1,
                        UNAVAILABLE: 2,
                        TIMEOUT: 3,
                        STARTED: 4,
                        STOPPED: 5,
                        ERROR: 6,
                    }
                }
            };
            widget._inCoBrowse = false;
            RightNow.Event.fire('evt_chatCobrowseStatusResponse', new RightNow.Event.EventObject(widget,
                    {data: {coBrowseStatus: RightNow.Chat.Model.ChatCoBrowseStatusCode.STARTED}}));

            //test that '_inCoBrowse' is set to true
            Y.Assert.isTrue(widget._inCoBrowse);

            widget._inCoBrowse = true;
            RightNow.Event.fire('evt_chatCobrowseStatusResponse', new RightNow.Event.EventObject(widget,
                    {data: {coBrowseStatus: RightNow.Chat.Model.ChatCoBrowseStatusCode.STOPPED}}));

            //test that '_inCoBrowse' is set to false
            Y.Assert.isFalse(widget._inCoBrowse);
        },

        testACS: function() {
            //rnq must be defined
            if (window._rnq.length)
                Y.Assert.isArray(window._rnq);
            else
                Y.Assert.isObject(window._rnq);

            //start fresh
            var oldRnq = window._rnq;
            window._rnq = [];

            //fire invitation event
            var sessionID = "14764:458lkskj8943sfg8:34_5";
            RightNow.Event.fire('evt_chatCoBrowsePremiumInvitationResponse', new RightNow.Event.EventObject(widget,
                    {data: {coBrowseSessionId: sessionID}}));

            //invite should be recorded
            Y.Assert.areEqual(window._rnq.length, 1);
            Y.ArrayAssert.itemsAreSame(window._rnq[0], ['chatCobrowsePremium', 'invite', sessionID]);

            //fire accept event
            RightNow.Event.fire('evt_chatCoBrowsePremiumAcceptResponse', new RightNow.Event.EventObject(widget,
                    {data: {accepted: true, coBrowseSessionId: sessionID, test: true}}));

            //accept should be recorded
            Y.Assert.areEqual(window._rnq.length, 2);
            Y.ArrayAssert.itemsAreSame(window._rnq[1], ['chatCobrowsePremium', 'accept', sessionID]);

            //fire deny event
            RightNow.Event.fire('evt_chatCoBrowsePremiumAcceptResponse', new RightNow.Event.EventObject(widget,
                    {data: {accepted: false, coBrowseSessionId: sessionID}}));

            //decline should be recorded
            Y.Assert.areEqual(window._rnq.length, 3);
            Y.ArrayAssert.itemsAreSame(window._rnq[2], ['chatCobrowsePremium', 'decline', sessionID]);

            RightNow.Chat = {
                Model: {
                    ChatCoBrowseStatusCode: {
                        ACCEPTED: 0,
                        DECLINED: 1,
                        UNAVAILABLE: 2,
                        TIMEOUT: 3,
                        STARTED: 4,
                        STOPPED: 5,
                        ERROR: 6,
                    }
                }
            };

            //fire start event
            RightNow.Event.fire('evt_chatCobrowseStatusResponse', new RightNow.Event.EventObject(widget, 
                    {data: {coBrowseStatus: RightNow.Chat.Model.ChatCoBrowseStatusCode.STARTED, coBrowseData: sessionID}}));

            //sessionStart should be recorded
            Y.Assert.areEqual(window._rnq.length, 4);
            Y.ArrayAssert.itemsAreSame(window._rnq[3], ['chatCobrowsePremium', 'sessionStart', sessionID]);

            //fire stop event
            RightNow.Event.fire('evt_chatCobrowseStatusResponse', new RightNow.Event.EventObject(widget, 
                    {data: {coBrowseStatus: RightNow.Chat.Model.ChatCoBrowseStatusCode.STOPPED, coBrowseData: sessionID}}));

            //sessionEnd should be recorded
            Y.Assert.areEqual(window._rnq.length, 5);
            Y.ArrayAssert.itemsAreSame(window._rnq[4], ['chatCobrowsePremium', 'sessionEnd', sessionID]);

            window._rnq = oldRnq;
        }
    }));
    return ChatCobrowsePremiumTests;
});
UnitTest.run();
