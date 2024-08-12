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
        
        testLinks: function() {
            this.initValues();
            var logged = false, action, details;
            RightNow.Ajax.CT.addAction = function(action2, details2) {
                logged = true;
                action = action2;
                details = details2;
            };
            var no = Y.one(".rn_Question").all(".rn_Response button");
            no = no.item(0);
            no.simulate('click');
            Y.Assert.isTrue(logged, "Something was wrong with the submitAction");
            Y.Assert.areSame(RightNow.Ajax.CT.GA_SESSION_DETAILS, action);
            if(details.a_id) {
                Y.Assert.isTypeOf("number", details.a_id);
                Y.Assert.isTrue(details.a_id > 0);
            }
            Y.Assert.isTypeOf("number", details.ga_id);
            Y.Assert.isTrue(details.ga_id > 0);
            Y.Assert.isTypeOf("string", details.ga_sid);
            Y.Assert.isTypeOf("number", details.q_id);
            Y.Assert.isTrue(details.q_id > 0);
            if(details.r_id) {
                Y.Assert.isTypeOf("number", details.r_id);
                Y.Assert.isTrue(details.r_id > 0);
            }
            Y.Assert.isTypeOf("object", Y.one('.rn_Result'));
        }
    }));

    return guidedAssistantTests;
});
UnitTest.run();
