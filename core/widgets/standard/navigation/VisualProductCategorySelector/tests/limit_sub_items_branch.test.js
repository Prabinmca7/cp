UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'VisualProductCategorySelector_0'
}, function(Y, widget, baseSelector){
    var suite = new Y.Test.Suite({
        name: "standard/navigation/VisualProductCategorySelector"
    });

    suite.add(new Y.Test.Case({
        name: "limit_sub_items_branch Behavior",

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

        verifyNoLinkBreadCrumb: function(level) {
            Y.assert(Y.one('.rn_BreadCrumb'));
            Y.assert(!Y.one('.rn_BreadCrumb a'));
            Y.Assert.areSame(level === 1 ? level : level + 1, Y.one('.rn_BreadCrumb').all('*').size());
            var selector = level === 1 ? '.rn_CurrentLevelBreadCrumb' : '.rn_BreadCrumbLevel';
            if(level === 1)
                Y.Assert.areSame('', Y.Lang.trim(Y.one('.rn_BreadCrumb ' + selector).get('text')));
            if(level === 2)
                Y.Assert.areSame(widget.data.attrs.label_breadcrumb, Y.Lang.trim(Y.one('.rn_BreadCrumb ' + selector).get('text')));
        },

        "Standalone title is initially shown for items": function() {
            this.verifyNoLinkBreadCrumb(1);
        },

        "A new level is added, breadcrumb doesn't contain links that go back up": function() {
            this.addNewLevel();
            this.verifyNoLinkBreadCrumb(2);
        }
    }));

    return suite;
}).run();
