UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'VirtualAssistantBanner_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/chat/VirtualAssistantBanner",
    });

    /* @@@ QA 130405-000087 */
    suite.add(new Y.Test.Case({
        name: "Banners",

        testBannerWithImage: function() 
        {    
            RightNow.Event.fire('evt_chatPostResponse', new RightNow.Event.EventObject(widget, {data: {vaResponse: {'banners': [{'targetUrl': 'MyTargetUrl', 
                                                                                                                                 'imageUrl': 'MyImageUrl', 
                                                                                                                                 'tooltip': 'MyTooltip'}]}}}));

            //test that a banner with image is displayed properly
            Y.Assert.areNotSame(Y.one(baseSelector).getHTML().indexOf('MyTargetUrl'), -1);
            Y.Assert.areNotSame(Y.one(baseSelector).getHTML().indexOf('MyImageUrl'), -1);
            Y.Assert.areNotSame(Y.one(baseSelector).getHTML().indexOf('MyTooltip'), -1);
        },
                
        testBannerWithoutImage: function() 
        {    
            RightNow.Event.fire('evt_chatPostResponse', new RightNow.Event.EventObject(widget, {data: {vaResponse: {'banners': [{'targetUrl': 'MyTargetUrl', 
                                                                                                                                 'imageUrl': '', 
                                                                                                                                 'title': 'MyTitle', 
                                                                                                                                 'description': 'MyDescription'}]}}}));

            //test that a banner without image is displayed properly
            Y.Assert.areNotSame(Y.one(baseSelector).getHTML().indexOf('MyTargetUrl'), -1);
            Y.Assert.areNotSame(Y.one(baseSelector).getHTML().indexOf('MyTitle'), -1);
            Y.Assert.areNotSame(Y.one(baseSelector).getHTML().indexOf('MyDescription'), -1);
        },
                
        testNoBanner: function() 
        {    
            RightNow.Event.fire('evt_chatPostResponse', new RightNow.Event.EventObject(widget, {data: {vaResponse: {}}}));

            //test that a VA response without a banner is displayed properly
            Y.Assert.areEqual(Y.one(baseSelector + '_Banner').getHTML(), '');
        },
                
        //@@@ QA 131017-000025
        testNullVaResponse: function()
        {
            // Test that "happy" is still shown after null vaResponse
            RightNow.Event.fire('evt_chatPostResponse', new RightNow.Event.EventObject(widget, {data: {vaResponse: null}}));
            Y.Assert.areEqual(Y.one(baseSelector + '_Banner').getHTML(), '');
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
        
    }));

    return suite;
}).run();
