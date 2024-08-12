UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'VisualProductCategorySelector_0'
}, function(Y, widget, baseSelector){
    var suite = new Y.Test.Suite({
        name: "standard/navigation/VisualProductCategorySelector"
    });

    suite.add(new Y.Test.Case({
        name: "maximum_items Tests",

        "Initial set of items honors maximum_items": function() {
            Y.Assert.areSame(widget.data.attrs.maximum_items, Y.all('.rn_Item').size());
        },

        "Subsequent item groups loaded from server response honor maximum_items": function() {
            var items = [];
            for (var i = 0, max = widget.data.attrs.maximum_items + 1; i <= max; i++) {
                items.push({ id: 'banana' + i, label: 'banana ' + i, hasChildren: Math.round(Math.random()) });
            }

            widget._showNewGroup(items, { id: 'wolves', level: 2 });
            Y.Assert.areSame(widget.data.attrs.maximum_items, Y.one(baseSelector + '_wolves_SubItems').all('.rn_Item').size());
            Y.Assert.isTrue(Y.one(baseSelector + '_wolves_SubItems .rn_Item').get('text').indexOf('banana 0') > -1);
        }
    }));

    return suite;
}).run();
