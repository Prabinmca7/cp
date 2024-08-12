UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'VirtualAssistantAvatar_0'
}, function (Y, widget, baseSelector) {
    var vaAvatarTests = new Y.Test.Suite({
        name: "standard/chat/VirtualAssistantAvatar",
    });

    /* @@@ QA 130412-000051 */
    vaAvatarTests.add(new Y.Test.Case({
        name: "CSS class changes based on VA emotion", 

        testDefaultEmotion: function() 
        {    
            RightNow.Event.fire('evt_chatPostResponse', new RightNow.Event.EventObject(widget, {}));
            // test that the default class is used
            Y.Assert.isTrue(Y.one(baseSelector + ' .rn_DefaultEmotion').hasClass("neutral"));
        },
                
        testModifiedEmotion: function() 
        {    
            RightNow.Event.fire('evt_chatPostResponse', new RightNow.Event.EventObject(widget, {data: {vaResponse: {emotion: 'happy'}}}));
            // test that the right class is used
            Y.Assert.isTrue(Y.one(baseSelector + ' .rn_Emotion').hasClass("happy"));
            RightNow.Event.fire('evt_chatPostResponse', new RightNow.Event.EventObject(widget, {data: {vaResponse: {emotion: 'listen'}}}));
            // test that the new emotion is used
            Y. Assert.isTrue(Y.one(baseSelector + ' .rn_Emotion').hasClass("listen"));
            //test that the previous emotion is no longer used
            Y.Assert.isFalse(Y.one(baseSelector + ' .rn_Emotion').hasClass("happy"));
        },
            
        //@@@ QA 131017-000025
        testNullVaResponse: function()
        {
            // First, set emotion to "happy" so we can ensure it's still what's shown after null response
            RightNow.Event.fire('evt_chatPostResponse', new RightNow.Event.EventObject(widget, {data: {vaResponse: {emotion: 'happy'}}}));
            Y.Assert.isTrue(Y.one(baseSelector + ' .rn_Emotion').hasClass("happy"));

            // Test that "happy" is still shown after null vaResponse
            RightNow.Event.fire('evt_chatPostResponse', new RightNow.Event.EventObject(widget, {data: {vaResponse: null}}));
            Y.Assert.isTrue(Y.one(baseSelector + ' .rn_Emotion').hasClass("happy"));
        },
                
        testVaHidden: function() 
        {
            RightNow.Event.fire('evt_chatEngagementParticipantAddedResponse', new RightNow.Event.EventObject(widget, {}));
            // test that the widget is hidden
            Y.Assert.isTrue(Y.one(baseSelector).hasClass("rn_Hidden"));
        },

        testVaShown: function() 
        {
            RightNow.Event.fire('evt_chatEngagementParticipantAddedResponse', new RightNow.Event.EventObject(widget, {data: {virtualAgent: true}}));
            // test that the widget is shown
            Y.Assert.isFalse(Y.one(baseSelector).hasClass("rn_Hidden"));
        },
        
    }));

    return vaAvatarTests;
}).run();
