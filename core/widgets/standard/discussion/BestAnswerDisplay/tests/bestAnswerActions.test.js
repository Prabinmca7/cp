UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'BestAnswerDisplay_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/discussion/BestAnswerDisplay"
    });

    suite.add(new Y.Test.Case({
        name: "Base interaction tests",

        "Unselecting a best answer broadcasts its action for other widgets to hear": function() {
            var given,
                baseNode = Y.one(baseSelector),
                subscriber = function(name, eventData) {
                    given = eventData;
                    // cancel ajax request
                    return false;
                };

            RightNow.Event.subscribe("evt_bestAnswerUnselect", subscriber, this);
            baseNode.one('.rn_BestAnswerRemoval span').simulate('click');

            Y.assert(given[0].data.commentID > 0);
            Y.assert(given[0].data.chosenByType === "Moderator");

            RightNow.Event.unsubscribe("evt_bestAnswerUnselect", subscriber, this);
        }
    }));

    return suite;
}).run();
