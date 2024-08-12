UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'GuidedAssistant_0',
}, function(Y, widget, baseSelector){
    var guidedAssistantTests = new Y.Test.Suite({
        name: "standard/knowledgebase/GuidedAssistant"
    });

    guidedAssistantTests.add(new Y.Test.Case({
        name: "Functionality",

        testNewBranch: function() {
            var firstInputs = Y.one(".rn_Question").all(".rn_Response input, .rn_Response button");
            widget._onClick({target: firstInputs.item(firstInputs.size() - 1), halt: function(){}});
            Y.Assert.areSame(2, Y.one(".rn_Guide").get("children").size());
            Y.all(".rn_Guide a").each(function(link) {
                Y.Assert.areSame(RightNow.Text.getSubstringAfter(link.get('href').replace(/%3D/g, '='), '/session/'), RightNow.Url.getSession());
            });
            Y.Assert.isTrue(Y.one(".rn_Question").next().hasClass("rn_Result"));
        }
    }));

    return guidedAssistantTests;
});
UnitTest.run();
