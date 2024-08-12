UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'GuidedAssistant_0',
}, function(Y, widget, baseSelector){
    var guidedAssistantTests = new Y.Test.Suite({
        name: "standard/knowledgebase/GuidedAssistant",
        setUp: function(){
            var testExtender = {
                initValues: function() {
                    this.instanceID = 'GuidedAssistant_0';
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.widgetData = this.instance.data;
                    this.selector = "#rn_" + this.instanceID;
                }
            };
            for(var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    guidedAssistantTests.add(new Y.Test.Case({
        name: "Functionality",
        
        testNavigateDown: function() {
            this.initValues();
            this._levels = this.goDown(Y.one(".rn_Question"), 0, function(node) {
                Y.one(node).simulate('click');
            });
        },
        
        testNavigateUpUsingButton: function() {
            this.initValues();
            var selector = this.selector;
            this.goUp(this._levels, function() {
                Y.one(selector + "_BackButton").simulate('click');
            });
        },
        
        testRestart: function() {
            this.testNavigateDown();
            Y.one(this.selector + "_RestartButton").simulate('click');
            Y.Assert.areSame(1, Y.one('.rn_Guide').get('children').size());
            Y.Assert.isTrue(Y.one('.rn_Guide').one('*').hasClass('rn_Question'));
        },
        
        goDown: function(container, levels, navigateFunction) {
            Y.Assert[((levels) ? "isFalse" : "isTrue")](Y.one(this.selector + "_BackButton").hasClass("rn_Hidden"),
                "back button is messed up!");
            if (levels > 2) {
                Y.Assert.isTrue(Y.one(this.selector + "_RestartButton") !== null, "restart button wasn't created when expected!");
            }
            
            var actionable = container.one('.rn_Response input, .rn_Response button');
            if (actionable) {
                navigateFunction(actionable);
                Y.Assert.isTrue(container.hasClass("rn_Hidden"));
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
        
        goUp: function(levels, navigateFunction) {
            Y.Assert.areSame(levels, Y.all('.rn_Guide > div.rn_Hidden').size());
            Y.Assert.areSame(levels + 1, Y.one('.rn_Guide').get('children').size());
            navigateFunction();
            return (levels) ? this.goUp(--levels, navigateFunction) : levels;
        }
        
    }));

    return guidedAssistantTests;
});
UnitTest.run();