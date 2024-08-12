UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'VirtualAssistantFeedback_0'
}, function (Y, widget, baseSelector) {
    var vaFeedbackTests = new Y.Test.Suite({
        name: "standard/chat/VirtualAssistantFeedback",
    });

    /* @@@ QA 130412-000054 */
    vaFeedbackTests.add(new Y.Test.Case({
        name: "Send feedback to the VA API",

        testVaHidden: function()
        {
            RightNow.Event.fire('evt_chatEngagementParticipantAddedResponse', new RightNow.Event.EventObject(widget, {data: {}}));
            RightNow.Event.fire('evt_chatPostCompletion', new RightNow.Event.EventObject(widget, {}));

            //test that the widget is hidden
            Y.Assert.isTrue(Y.one(baseSelector).hasClass("rn_Hidden"));
        },

        testVaShown: function()
        {
            RightNow.Event.fire('evt_chatEngagementParticipantAddedResponse', new RightNow.Event.EventObject(widget, {data: {virtualAgent: true}}));
            RightNow.Event.fire('evt_chatPostCompletion', new RightNow.Event.EventObject(widget, {data: {}}));

            //test that the widget is shown
            Y.Assert.isFalse(Y.one(baseSelector).hasClass("rn_Hidden"));

            Y.one(baseSelector + '_RatingCell_1').simulate('click');
            var thanksLabel = Y.one('.rn_ThanksLabel');
            Y.Assert.isObject(thanksLabel);
            Y.Assert.areSame(thanksLabel.get('innerHTML'), widget.data.attrs.label_feedback_submitted);

            RightNow.Event.fire('evt_chatPostCompletion', new RightNow.Event.EventObject(widget, {data: {}}));
            Y.Assert.areSame(thanksLabel.get('innerHTML'), '');
        }
    }));
    return vaFeedbackTests;
}).run();
