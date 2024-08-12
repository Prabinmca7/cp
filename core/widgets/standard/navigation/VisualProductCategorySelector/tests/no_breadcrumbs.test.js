UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'VisualProductCategorySelector_0'
}, function(Y, widget, baseSelector){
    var suite = new Y.Test.Suite({
        name: "standard/navigation/VisualProductCategorySelector"
    });

    suite.add(new Y.Test.Case({
        name: "No Breadcrumb Behavior",

        currentLevel: 0,
        startingIndex: 100,

        addNewLevel: function() {
            var parentID = this.currentLevel + 100,
                previousElement = (!this.currentLevel)
                    ? Y.one(baseSelector).one('.rn_ItemGroup')
                    : Y.one(baseSelector).one('.rn_ItemGroup.rn_Item_' + (parentID - 1) + '_SubItems');

            widget.itemLevels.push({ el: previousElement, label: 'bananas ' + this.currentLevel, id: parentID });
            widget.currentLevel = ++this.currentLevel;
            widget._childrenResponse({ result: [[
                { id: this.startingIndex++, label: 'bananas ' + this.startingIndex, hasChildren: true }
            ]]}, { data: {}});
        },

        verifyStandaloneTitleForBreadCrumb: function() {
            Y.assert(!Y.one('.rn_BreadCrumb'));
            Y.Assert.areSame(widget.data.attrs.label_breadcrumb, Y.one('.rn_Title').get('text'));
        },

        "Standalone title is initially shown for items": function() {
            this.verifyStandaloneTitleForBreadCrumb();
        },

        "A new level is added, title is untouched and back button appears": function() {
            this.addNewLevel();
            this.verifyStandaloneTitleForBreadCrumb();
        }
    }));

    return suite;
}).run();
