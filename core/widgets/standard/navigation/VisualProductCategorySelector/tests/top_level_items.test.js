UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'VisualProductCategorySelector_0'
}, function(Y, widget, baseSelector){
    var suite = new Y.Test.Suite({
        name: "standard/navigation/VisualProductCategorySelector"
    });

    suite.add(new Y.Test.Case({
        name: "top_level_products Tests",

        "Initial set of (legit) products are filtered by top_level_products": function() {
            Y.Assert.areSame(3, Y.all('.rn_Item').size());
            Y.assert(Y.one('.rn_ItemWithID1'));
            Y.assert(Y.one('.rn_ItemWithID6'));
            Y.assert(Y.one('.rn_ItemWithID163'));
            Y.assert(!Y.one('.rn_ItemWithIDbananas'));
            Y.assert(!Y.one('.rn_ItemWithID1000'));
            Y.assert(!Y.one('.rn_ItemWithID7'));
        }
    }));

    return suite;
}).run();
