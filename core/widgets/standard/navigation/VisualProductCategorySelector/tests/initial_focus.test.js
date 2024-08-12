UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'VisualProductCategorySelector_0'
}, function(Y, widget, baseSelector){
    var suite = new Y.Test.Suite({
        name: "standard/navigation/VisualProductCategorySelector"
    });

    suite.add(new Y.Test.Case({
        name: "Intial Focus",

        "Widget automatically captures focus on page load when initial_focus is true, regardless of per_page setting": function() {
            Y.Assert.areSame(Y.Node(document.activeElement).getHTML(), Y.one(".rn_ItemLink").getHTML());
        }
    }));

    return suite;
}).run();
