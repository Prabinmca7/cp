UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'VirtualAssistantSimilarMatches_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/chat/VirtualAssistantSimilarMatches",
    });

    suite.add(new Y.Test.Case({
        name: "Similar Matches",

        testSimilarMatches: function() 
        {    
            RightNow.Event.fire('evt_chatPostResponse', new RightNow.Event.EventObject(widget, {data: {vaResponse: {questionlist: [{'repr': 'MatchQuestion1'}, {'repr': 'MatchQuestion2'}]}}}));

            //test that a similar match is displayed properly
            Y.Assert.areNotSame(Y.one(baseSelector + '_Matches').getHTML().indexOf('MatchQuestion1'), -1);
            Y.Assert.areNotSame(Y.one(baseSelector + '_Matches').getHTML().indexOf('MatchQuestion2'), -1);

        },      

        testMaxSimilarMatches: function() 
        {    
            RightNow.Event.fire('evt_chatPostResponse', new RightNow.Event.EventObject(widget, {data: {vaResponse: {questionlist: [{'repr': 'MatchQuestion1'}, {'repr': 'MatchQuestion2'}, {'repr': 'MatchQuestion3'}, {'repr': 'MatchQuestion4'}, {'repr': 'MatchQuestion5'}, {'repr': 'MatchQuestion6'}]}}}));

            //test that a similar match is displayed properly
            Y.Assert.areNotSame(Y.one(baseSelector + '_Matches').getHTML().indexOf('MatchQuestion5'), -1);
            
            if (widget.data.attrs.max_items_to_show > 0)
            {
                Y.Assert.areSame(Y.one(baseSelector + '_Matches').getHTML().indexOf('MatchQuestion6'), -1);
            }
            else
            {
                Y.Assert.areNotSame(Y.one(baseSelector + '_Matches').getHTML().indexOf('MatchQuestion6'), -1);
            }
        },      


        testNoSimilarMatches: function() 
        {    
            RightNow.Event.fire('evt_chatPostResponse', new RightNow.Event.EventObject(widget, {data: {vaResponse: {}}}));

            //test that a VA response without a banner is displayed properly
            Y.Assert.areEqual(Y.one(baseSelector + '_Matches').getHTML(), '');
        },

        //@@@ QA 131017-000025
        testNullVaResponse: function()
        {
            RightNow.Event.fire('evt_chatPostResponse', new RightNow.Event.EventObject(widget, {data: {vaResponse: null}}));

            // Test that it handles "null" vaResponse properly
            Y.Assert.areEqual(Y.one(baseSelector + '_Matches').getHTML(), '');
        },
                
        testVaHidden: function() 
        {
            RightNow.Event.fire('evt_chatEngagementParticipantAddedResponse', new RightNow.Event.EventObject(widget, {}));

            //test that the widget is hidden
            Y.Assert.isTrue(Y.one(baseSelector).hasClass("rn_Hidden"));
        },
                
        testVaShown: function() 
        {
            RightNow.Event.fire('evt_chatEngagementParticipantAddedResponse', new RightNow.Event.EventObject(widget, {data: {virtualAgent: true}}));

            //test that the widget is shown
            Y.Assert.isFalse(Y.one(baseSelector).hasClass("rn_Hidden"));
        },
        
        testClickableInOnMatchClick: function() 
        {
            Y.Assert.isTrue(widget._clickable);
            widget._onMatchClick({
                preventDefault: function(){},
                currentTarget: {
                    get: function(){ return '';}
                },
            });
            Y.Assert.isFalse(widget._clickable);
            //resetting the variable back to it's initial state 
            //just in case some other test happens to run after this one that relies on that variable. 
            widget._clickable = true; 
        },

        testClickableInOnChatPostResponse: function() 
        {
            RightNow.Event.fire('evt_chatPostResponse', new RightNow.Event.EventObject(widget, {data: {isEndUserPost: false, vaResponse: {questionlist: [{'repr': 'MatchQuestion1'}, {'repr': 'MatchQuestion2'}]}}}));
            Y.Assert.isTrue(widget._clickable);
        },

        testClickableInOnChatStateChangeResponse: function()
        {
            RightNow.Chat = {
                Model: {
                    ChatState: {
                        REQUEUED: 0,
                        CANCELED: 1,
                        DISCONNECTED: 2,
                        RECONNECTING: 3,
                    }
                }
            };

            widget._clickable = false;
            RightNow.Event.fire('evt_chatStateChangeResponse', new RightNow.Event.EventObject(widget, {data: {currentState: RightNow.Chat.Model.ChatState.REQUEUED}}));
            Y.Assert.isTrue(widget._clickable);

            widget._clickable = false;
            RightNow.Event.fire('evt_chatStateChangeResponse', new RightNow.Event.EventObject(widget, {data: {currentState: RightNow.Chat.Model.ChatState.CANCELED}}));
            Y.Assert.isTrue(widget._clickable);
            
            widget._clickable = false;
            RightNow.Event.fire('evt_chatStateChangeResponse', new RightNow.Event.EventObject(widget, {data: {currentState: RightNow.Chat.Model.ChatState.DISCONNECTED}}));
            Y.Assert.isTrue(widget._clickable);
            
            widget._clickable = false;
            RightNow.Event.fire('evt_chatStateChangeResponse', new RightNow.Event.EventObject(widget, {data: {currentState: RightNow.Chat.Model.ChatState.RECONNECTING}}));
            Y.Assert.isTrue(widget._clickable);
        }
    }));


    return suite;
}).run();
