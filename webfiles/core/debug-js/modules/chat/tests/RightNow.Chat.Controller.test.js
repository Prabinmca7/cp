// Polyfill for CustomEvent
(function() {

    if (typeof window.CustomEvent === "function") return false;

    function CustomEvent(event, params) {
        params = params || {
            bubbles: false,
            cancelable: false,
            detail: undefined
        };
        var evt = document.createEvent('CustomEvent');
        evt.initCustomEvent(event, params.bubbles, params.cancelable, params.detail);
        return evt;
    }

    CustomEvent.prototype = window.Event.prototype;

    window.CustomEvent = CustomEvent;
})();
// Polyfill for Function.prototype.bind
if (!Function.prototype.bind) {
    Function.prototype.bind = function(oThis) {
        if (typeof this !== 'function') {
            // closest thing possible to the ECMAScript 5
            // internal IsCallable function
            throw new TypeError('Function.prototype.bind - what is trying to be bound is not callable');
        }
        var aArgs   = Array.prototype.slice.call(arguments, 1),
            fToBind = this,
            fNOP    = function() {},
            fBound  = function() {
                return fToBind.apply(this instanceof fNOP
                    ? this
                    : oThis,
                    aArgs.concat(Array.prototype.slice.call(arguments)));
            };

        if (this.prototype) {
            // Function.prototype doesn't have a prototype property
            fNOP.prototype = this.prototype;
        }
        fBound.prototype = new fNOP();

        return fBound;
    };
}
UnitTest.addSuite({
    type: UnitTest.Type.Framework,
    jsFiles: [
        '/euf/core/debug-js/RightNow.Event.js',
        '/euf/core/debug-js/modules/chat/RightNow.Chat.Controller.js',
        '/euf/core/debug-js/modules/chat/RightNow.Chat.Communicator.js',
        '/euf/core/debug-js/modules/chat/RightNow.Chat.Model.js',
        '/euf/core/debug-js/modules/chat/RightNow.Chat.UI.js',
        '/euf/core/debug-js/RightNow.Text.js'
    ],
    namespaces: [
        'RightNow.Event',
        'RightNow.Chat.Controller',
        'RightNow.Chat.Controller.ChatCommunicationsController',
        'RightNow.Chat.UI',
        'RightNow.Chat.Model',
        'RightNow.Chat.Controller'
    ]
}, function(Y) {
    var rightnowChatControllerTests = new Y.Test.Suite("RightNow.Chat.Controller");

    rightnowChatControllerTests.add(new Y.Test.Case(
    {
        name: 'ControllerFunctions',
        testLogonRest: function()
        {
            var eventObject = {
                data: {
                    jwt: "abc123",
                    pool: "12:34",
                    firstName: "Cuffy",
                    lastName: "Meigs",
                    email: "cuffy.meigs@noreply.oracle.com"
                }
            };

            // mock call to communicator
            var parms = [];
            var wasItCalled = false;

            var testController = RightNow.Chat.Controller; 
            testController.ChatCommunicationsController._chatCommunicator = { 
                makeRequest: function(a, b, c) { 
                    wasItCalled = true; 
                    parms = [a,b,c]; 
                } 
            }; 

            // method under test
            testController.ChatCommunicationsController.logonRest(eventObject);

            console.log(parms);
            Y.Assert.isTrue(wasItCalled); 
            Y.Assert.areSame(eventObject.data.pool, parms[0].data.pool);
            Y.Assert.areSame('Bearer ' + eventObject.data.jwt, parms[0].headers['Authorization']);
            Y.Assert.areSame('application/json; charset=utf-8', parms[0].headers['Accept']);
            Y.Assert.areSame('application/json; charset=utf-8', parms[0].headers['Content-Type']);
            Y.Assert.areSame(true, parms[1]);
            Y.Assert.areSame(eventObject.data.firstName, RightNow.Chat.Controller.ChatCommunicationsController._endUser.firstName);
            Y.Assert.areSame(eventObject.data.lastName, RightNow.Chat.Controller.ChatCommunicationsController._endUser.lastName);
            Y.Assert.areSame(eventObject.data.email, RightNow.Chat.Controller.ChatCommunicationsController._endUser.email);
        },
        testOnFetchUpdateDoesNotRemoveSessionIdAddedByAnotherInstance: function()
        {
            // mock call to communicator
            var parms = [];
            var wasItCalled = false;
            var now = new Date();
            var response = {
                responses : [{
                      getResponseTypes:[{clientId: 1, chatMessageType:"ChatDisconnectNotification", reason: {value:"AGENT_CONCLUDED"}, createdTime: now.toTimeString()}]
                   }
                ]
            };
            var testController = RightNow.Chat.Controller; 
            testController.ChatCommunicationsController._chatCommunicator = { 
                setJavaSessionID: function(a){
                } 
            };

            var logonResponseObject = {
                responses:[{   clientId:1, 
                       sessionId: "Should not remove this session id added by other one.....", 
                       engagementId: 99,  
                       chatCreateEngagementResult: 
                          { 
                            sneakPreviewState: "none", 
                            sneakPreviewInterval: 100, 
                            resultCode: "SUCCESS"
                        }
                    }]
            };

            var Y2 = YUI().use("cookie");
            Y2.Cookie.set("CHAT_SESSION_ID", "Initial Cookie", {path: "/", expires: new Date().getTime() + 7200000});

            testController.ChatCommunicationsController.onLogonSuccess(logonResponseObject); 
            // method under test
            testController.ChatCommunicationsController.onFetchUpdate(response);

            Y.Assert.areNotSame(Y2.Cookie.get("CHAT_SESSION_ID"), "Should not remove this session id added by other one.....");

            //Reset logonResponse so that it should be same
            logonResponseObject = {
                responses:[{   
                       clientId:1, 
                       sessionId:"This cookie will get reset by another process", 
                       engagementId: 99,  
                       chatCreateEngagementResult:{sneakPreviewState: "none", sneakPreviewInterval: 100, resultCode: "SUCCESS"} 
                   }]
            };
            testController.ChatCommunicationsController.onLogonSuccess(logonResponseObject); 
            Y2.Cookie.set("CHAT_SESSION_ID", "Not my Cookie", {path: "/", expires: new Date().getTime() + 7200000});

            // method under test
            testController.ChatCommunicationsController.onFetchUpdate(response);

            Y.Assert.areSame(Y2.Cookie.get("CHAT_SESSION_ID"), "Not my Cookie");
            Y2.Cookie.remove("CHAT_SESSION_ID", {path: "/"});

        },
        // @@@ QA 210830-000110
        testOnFetchUpdateCapturesMaxSequenceNumber: function()
        {
            // this response is straight from 210830-000110
            var response = {
                "responses": [{
                    "sequenceNumber": 87,
                    "clientId": 41,
                    "serviceStartTime": "2021-08-30T19:51:14.005Z",
                    "containsDisconnect": false,
                    "getResponseTypes": [{
                        "sequenceNumber": 216,
                        "clientId": 40,
                        "visibility": {
                            "value": "ALL",
                            "chatMessageType": "ChatPostVisibility"
                        },
                        "destinationId": 147,
                        "sneakPreviewFocus": true,
                        "mode": {
                            "value": "RESPONDING",
                            "chatMessageType": "ChatInteractionMode"
                        },
                        "senderId": 40,
                        "messageType": {
                            "value": "ACTIVITY_SIGNAL",
                            "chatMessageType": "ChatMessageType"
                        },
                        "createdTime": "2021-08-30T19:51:13.817Z",
                        "destinationType": {
                            "value": "ENGAGEMENT",
                            "chatMessageType": "ChatChannelType"
                        },
                        "senderType": {
                            "value": "AGENT",
                            "chatMessageType": "ChatChannelType"
                        },
                        "sneakPreviewInterval": 3000,
                        "engagementId": 147,
                        "sneakPreviewState": {
                            "value": "DISABLED",
                            "chatMessageType": "ChatSneakPreviewState"
                        },
                        "chatMessageType": "ChatActivitySignal"
                    }, {
                        "sequenceNumber": 217,
                        "clientId": 40,
                        "visibility": {
                            "value": "ALL",
                            "chatMessageType": "ChatPostVisibility"
                        },
                        "destinationId": 147,
                        "sneakPreviewFocus": true,
                        "mode": {
                            "value": "RESPONDING",
                            "chatMessageType": "ChatInteractionMode"
                        },
                        "senderId": 40,
                        "messageType": {
                            "value": "ACTIVITY_SIGNAL",
                            "chatMessageType": "ChatMessageType"
                        },
                        "createdTime": "2021-08-30T19:51:13.907Z",
                        "destinationType": {
                            "value": "ENGAGEMENT",
                            "chatMessageType": "ChatChannelType"
                        },
                        "senderType": {
                            "value": "AGENT",
                            "chatMessageType": "ChatChannelType"
                        },
                        "sneakPreviewInterval": 3000,
                        "engagementId": 147,
                        "sneakPreviewState": {
                            "value": "DISABLED",
                            "chatMessageType": "ChatSneakPreviewState"
                        },
                        "chatMessageType": "ChatActivitySignal"
                    }],
                    "sessionId": "node08264cqgsvzzd1umih633yek1517443",
                    "responseSentMilliseconds": 1630353074011,
                    "serviceFinishTime": "2021-08-30T19:51:14.014Z",
                    "chatMessageType": "ChatGetResponse"
                }],
                "chatMessageType": "ChatMessage"
            };
            var testController = RightNow.Chat.Controller;
            testController.ChatCommunicationsController._chatCommunicator = {
                setJavaSessionID: function(a) { },
                getLastTransactionID: function() { return 1; }
            };

            // prevent false positives
            Y.Assert.areNotSame(217, testController.ChatCommunicationsController._startingSequenceNumber);

            // method under test
            testController.ChatCommunicationsController.onFetchUpdate(response);

            // the correct sequence number as indicated in 210830-000110
            Y.Assert.areSame(217, testController.ChatCommunicationsController._startingSequenceNumber);
        },
        // @@@ QA 180802-000113
        testCreateServiceFinishTimeStamp: function () 
        {
            var timezoneDate = new Date();
            var timezoneOffset = timezoneDate.getTimezoneOffset();

            var testController = RightNow.Chat.Controller;

            var isoDate = "2018-08-03T12:34:56Z";
            var explicitTimezoneDate1 = "2018-08-03T06:34:56-06:00";
            var explicitTimezoneDate2 = "2018-08-03T14:34:56+02:00";
            var expectedTimestamp = 1533299696000;

            var localBrowserDate = "2018-08-03T12:34:56";
            var expectedLocalBrowserTimestamp = 1533299696000 + (timezoneOffset * 60 * 1000);

            
            var actualTimestamp = testController.ChatCommunicationsController.createServiceFinishTimestamp(isoDate);
            Y.Assert.areSame(actualTimestamp, expectedTimestamp, "Expected generated 'UTC' timestamp to match the given timestamp");

            actualTimestamp = testController.ChatCommunicationsController.createServiceFinishTimestamp(explicitTimezoneDate1);
            Y.Assert.areSame(actualTimestamp, expectedTimestamp, "Expected generated 'explicit timezone' timestamp to match the given timestamp");
            actualTimestamp = testController.ChatCommunicationsController.createServiceFinishTimestamp(explicitTimezoneDate2);
            Y.Assert.areSame(actualTimestamp, expectedTimestamp, "Expected generated 'explicit timezone' timestamp to match the given timestamp");

            actualTimestamp = testController.ChatCommunicationsController.createServiceFinishTimestamp(localBrowserDate);
            Y.Assert.areSame(actualTimestamp, expectedLocalBrowserTimestamp, "Expected generated 'local' timestamp to match the given timestamp");

        },
        testOnLogoffSuccess: function () {
            var response = {};
            var testController = RightNow.Chat.Controller;
            var Y2 = YUI().use("cookie");

            // When the current tab/window is still the 'owner' of the cookie
            testController.ChatCommunicationsController._endUser = { servletSessionID: "SomeSession" };
            Y2.Cookie.set("CHAT_SESSION_ID", "SomeSession", {path: "/", expires: new Date().getTime() + 7200000});
            
            testController.ChatCommunicationsController.onLogoffSuccess(response);

            Y.Assert.isNull(Y2.Cookie.get("CHAT_SESSION_ID"), "Chat Session ID should have been cleared");

            // When the current tab/window is not the 'owner' of the cookie anymore
            testController.ChatCommunicationsController._endUser = { servletSessionID: "SomeOtherSession" };
            Y2.Cookie.set("CHAT_SESSION_ID", "SomeSession", {path: "/", expires: new Date().getTime() + 7200000});

            testController.ChatCommunicationsController.onLogoffSuccess(response);

            Y.Assert.areSame(Y2.Cookie.get("CHAT_SESSION_ID"), "SomeSession", "Chat Session ID should still exist");
            Y2.Cookie.remove("CHAT_SESSION_ID", {path: "/"});
        },
        // @@@ QA 230214-000062
        testOnLogonSuccessVerifySneakPreviewFocus: function()
        {
			var testController = RightNow.Chat.Controller;
			testController.ChatCommunicationsController._sneakPreviewFocus = false;
            // sample response
            var response = {
				"sneakPreviewState":"ENABLED",
				"sneakPreviewInterval":3000
			};
			
			//Check default value
            Y.Assert.isFalse(testController.ChatCommunicationsController._sneakPreviewFocus);

            // method under test
            testController.ChatCommunicationsController.onLogonSuccess(response);

			//Verify sneakPreviewFocus value change
            Y.Assert.isTrue(testController.ChatCommunicationsController._sneakPreviewFocus);
        }
    }));
    return rightnowChatControllerTests;
}).run();
