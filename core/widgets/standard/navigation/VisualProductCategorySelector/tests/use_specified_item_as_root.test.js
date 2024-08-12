UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'VisualProductCategorySelector_0'
}, function(Y, widget, baseSelector){
    var suite = new Y.Test.Suite({
        name: "standard/navigation/VisualProductCategorySelector"
    });

    suite.add(new Y.Test.Case({
        name: "show_sub_items_for + limit_sub_items_branch Tests",

        "Initial set of (legit) items are the immediate children of item specified for show_sub_items_for": function() {
            Y.Assert.areSame(2, Y.all('.rn_Item').size());
            Y.assert(Y.one('.rn_ItemWithID159'));
            Y.assert(Y.one('.rn_ItemWithID160'));
            Y.Assert.areSame('&nbsp;', Y.Lang.trim(Y.one('.rn_CurrentLevelBreadCrumb').getHTML()));
        }
    }));

    return suite;
}).run();
