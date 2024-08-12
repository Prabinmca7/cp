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
    type: UnitTest.Type.Widget,
    instanceID: 'ChatAgentStatus_0'
}, function(Y, widget, baseSelector) {
    var chatAgentStatusTests = new Y.Test.Suite({
        name: "standard/chat/ChatAgentStatus",
    });

    chatAgentStatusTests.add(new Y.Test.Case({
        name: "Functionality",
        
        testParticipantAdded: function() {
            RightNow.Event.fire('evt_chatEngagementParticipantAddedResponse', new RightNow.Event.EventObject(widget, {data: {agent: {name: "amychat", clientID: "1234"}}}));
            
            //test that widget is displayed
            Y.Assert.isFalse(Y.one(baseSelector).hasClass("rn_Hidden"));
            
            //test that we have one agent inserted into the roster
            Y.Assert.areEqual(1, widget._roster.get("children").size());
            
            //test that we have an element with the appropriate id that represents the agent
            Y.Assert.isNotNull(Y.one(baseSelector + "_Agent_1234"));
            
            //test that the status is "Listening"
            Y.Assert.areEqual('amychat&nbsp;(' + widget.data.attrs.label_status_listening + ')', Y.one(baseSelector + "_AgentStatus_1234").get('innerHTML'));

            RightNow.Event.fire('evt_chatEngagementParticipantAddedResponse', new RightNow.Event.EventObject(widget, 
                    {data: {agent: {name: "bubbachat", clientID: "4321"}}}));
            
            //test that we have two agents inserted into the roster
            Y.Assert.areEqual(2, widget._roster.get("children").size());
            
            //test that we have an element with the appropriate id that represents the agent
            Y.Assert.isNotNull(Y.one(baseSelector + "_Agent_4321"));
            
            //test that the status is "Listening"
            Y.Assert.areEqual('bubbachat&nbsp;(' + widget.data.attrs.label_status_listening + ')', Y.one(baseSelector + "_AgentStatus_4321").get('innerHTML'));
        },
        
        testAgentStatusChange : function() {
            var agentName = "amychat";
            RightNow.Event.fire('evt_chatAgentStatusChangeResponse', new RightNow.Event.EventObject(widget, 
                    {data: {agent: {name: agentName, 
                                    clientID: "1234",
                                    activityStatus: RightNow.Chat.Model.ChatActivityState.RESPONDING}}}));
            
            //test that the status is set to "Responding"
            Y.Assert.areEqual(agentName + '&nbsp;(' + widget.data.attrs.label_status_responding + ')', Y.one(baseSelector + "_AgentStatus_1234").get('innerHTML'));
            
            RightNow.Event.fire('evt_chatAgentStatusChangeResponse', new RightNow.Event.EventObject(widget, 
                    {data: {agent: {name: agentName, 
                                    clientID: "1234",
                                    activityStatus: RightNow.Chat.Model.ChatActivityState.ABSENT}}}));
            
            //test that the status is set to "Responding"
            Y.Assert.areEqual(agentName + '&nbsp;(' + widget.data.attrs.label_status_absent + ')', Y.one(baseSelector + "_AgentStatus_1234").get('innerHTML'));
        },
        
        testParticipantRemoved: function() {
            RightNow.Event.fire('evt_chatEngagementParticipantRemovedResponse', new RightNow.Event.EventObject(widget, 
                    {data: {agent: {name: "bubbachat", clientID: "4321"}}}));
            
            //test that we have only one agent remaining in the roster
            Y.Assert.areEqual(1, widget._roster.get("children").size());
            
            //test that correct element is removed
            Y.Assert.isNotNull(Y.one(baseSelector + "_Agent_1234"));
            Y.Assert.isNull(Y.one(baseSelector + "_Agent_4321"));
        },
        
        testChatStateChanged: function() {
            RightNow.Event.fire('evt_chatStateChangeResponse', new RightNow.Event.EventObject(widget, 
                    {data: {currentState: RightNow.Chat.Model.ChatState.CANCELLED}}));
            Y.Assert.isTrue(Y.one(baseSelector ).hasClass("rn_Hidden"), "Widget should be hidden when state is CANCELED!");
            
            RightNow.UI.show(baseSelector);
            RightNow.Event.fire('evt_chatStateChangeResponse', new RightNow.Event.EventObject(widget, 
                    {data: {currentState: RightNow.Chat.Model.ChatState.DISCONNECTED}}));
            Y.Assert.isTrue(Y.one(baseSelector ).hasClass("rn_Hidden"), "Widget should be hidden when state is DISCONNECTED!");
            
            RightNow.UI.show(baseSelector);
            RightNow.Event.fire('evt_chatStateChangeResponse', new RightNow.Event.EventObject(widget, 
                    {data: {currentState: RightNow.Chat.Model.ChatState.REQUEUED}}));
            Y.Assert.isTrue(Y.one(baseSelector ).hasClass("rn_Hidden"), "Widget should be hidden when state is REQUEUED!");
        }
    }));
    return chatAgentStatusTests;
});
UnitTest.run();