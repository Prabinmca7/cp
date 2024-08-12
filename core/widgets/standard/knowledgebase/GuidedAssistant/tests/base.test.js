UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'GuidedAssistant_0',
}, function(Y, widget, baseSelector){
    var guidedAssistantTests = new Y.Test.Suite({
        name: "standard/knowledgebase/GuidedAssistant"
    });

    guidedAssistantTests.add(new Y.Test.Case({
        name: "Branch Navigation Functionality",

        testNavigateDown: function() {
            this._levels = this.goDown(Y.one(".rn_Question"), 0, function(node) {
                Y.one(node).simulate('click');
            });
        },

        testNewBranch: function() {
            var firstInputs = Y.one(".rn_Question").all(".rn_Response input, .rn_Response button");
            widget._onClick({target: firstInputs.item(firstInputs.size() - 1), halt: function(){}});
            Y.Assert.areSame(2, Y.one(".rn_Guide").get("children").size());
            Y.Assert.isTrue(Y.one(".rn_Question").next().hasClass("rn_Result"));
        },

        verifyEvent: function(evt, args) {
            this.eventCalled = (args && typeof args === 'object') || false;
        },

        goDown: function(container, levels, navigateFunction) {
            this.verifyEvent();
            RightNow.Event.on('evt_GuideQuestionRendered', this.verifyEvent, this);
            var actionable = container.one('.rn_Response input, .rn_Response button');
            if (actionable) {
                navigateFunction(Y.Node.getDOMNode(actionable));
                Y.Assert.isFalse(container.hasClass("rn_Hidden"));
                var result = container.next();
                if (result.hasClass('rn_Question')) {
                    Y.Assert.isTrue(this.eventCalled, 'question render event was\'t called');
                    result.all('input, a').each(function(input, parent) {
                        parent = input.get('parentNode');
                        if (parent.hasClass('rn_Result') || parent.get('tagName') === 'P') {
                            Y.Assert.areSame('', input.get('id'));
                        }
                        else {
                            Y.Assert.isTrue(/rn_GuidedAssistant_[0-9]_Response[0-9]_[0-9]_[0-9]/.test(input.get('id')), input.get('id') + " is an unexpected id!");
                        }
                    });
                }
                else {
                    Y.Assert.isTrue(result.hasClass('rn_Result'));
                    Y.Assert.isFalse(this.eventCalled, 'question render event was called');
                }
                RightNow.Event.unsubscribe('evt_GuideQuestionRendered', this.verifyEvent, this);
            }
            else {
                Y.Assert.isTrue(container.hasClass("rn_Result"), "final result is messed up!");
                Y.Assert.isTrue(container.hasClass("rn_Text"), "final result is messed up!");
                Y.Assert.areSame(1, container.get("children").size(), "final result is messed up!");
                Y.Assert.isTrue(container.one("*").hasClass("rn_ResultText"), "final result is messed up!");
                return levels;
            }
            return this.goDown(container.next(), ++levels, navigateFunction);
        },

        verifyTextboxValue: function() {
            // In future if we get a new CP Database with a scenario of guide having a textbox, then add a test here to check
            // if its value gets cleared when clicked on GoBack button.
        }

    }));

    guidedAssistantTests.add(new Y.Test.Case({
        name: "Functional",

        "#toggleQuestion returns false if the specified element doesn't exist": function() {
            Y.Assert.isFalse(widget._toggleQuestion());
            Y.Assert.isFalse(widget._toggleQuestion(0));
            Y.Assert.isFalse(widget._toggleQuestion(''));
            Y.Assert.isFalse(widget._toggleQuestion(9877, true));
            Y.Assert.isFalse(widget._toggleQuestion({ questionID: 342 }, true));
        },

        "#toggleQuestion returns false if the specified element exists but is already in the desired state": function() {
            Y.Assert.isFalse(widget._toggleQuestion(1, true));
            var question = Y.one(baseSelector).one('.rn_Guide').one('.rn_Question');
            Y.Assert.isTrue(!question.hasClass('rn_Hidden'));
        },

        "#toggleQuestion returns true and toggles the display of the specified element when it's not already in that state": function() {
            Y.Assert.isTrue(widget._toggleQuestion(1));
            var question = Y.one(baseSelector).one('.rn_Guide').one('.rn_Question');
            Y.Assert.isTrue(question.hasClass('rn_Hidden'));
        }
    }));

    guidedAssistantTests.add(new Y.Test.Case({
        name: "Ajax Tests",

        "CT Object is defined": function() {
            Y.Assert.isObject(RightNow.Ajax.CT);
        }
    }));

    return guidedAssistantTests;
});
UnitTest.run();
