UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'VisualProductCategorySelector_0'
}, function(Y, widget, baseSelector){
    var suite = new Y.Test.Suite({
        name: "standard/navigation/VisualProductCategorySelector"
    });

    suite.add(new Y.Test.Case({
        name: "Breadcrumb without a top-level title Tests",

        setUp: function() {
            widget.currentLevel = 0;
            widget.itemLevels = [];

            this.currentLevel = 0;
            this.startingIndex = 100;

            Y.all('.rn_ItemGroup').slice(1).remove();
            Y.one('.rn_ItemGroup').removeClass('rn_Hidden');

            this.originalBreadCrumb = Y.one('.rn_BreadCrumb').getHTML();
        },

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

        verifyBreadCrumb: function(expectedItems) {
            Y.Assert.areSame(expectedItems - 1, Y.one('.rn_BreadCrumb').all('a').size());
            Y.Assert.areSame(expectedItems - 1, Y.one('.rn_BreadCrumb').all('a.rn_BreadCrumbLink').size());
            Y.Assert.areSame(expectedItems - 1, Y.one('.rn_BreadCrumb').all('.rn_BreadCrumbSeparator').size());
            Y.Assert.areSame(1, Y.one('.rn_BreadCrumb').all('.rn_CurrentLevelBreadCrumb').size());

            // Current level should always the be final non-link crumb.
            if(expectedItems === 1)
                Y.Assert.areSame('', Y.Lang.trim(Y.one('.rn_BreadCrumb .rn_CurrentLevelBreadCrumb').get('text')));
            else
                Y.Assert.areSame('bananas ' + (expectedItems - 1), Y.Lang.trim(Y.one('.rn_BreadCrumb .rn_CurrentLevelBreadCrumb').get('text')));

            // Verify the links.
            for (var i = 0, crumbs = Y.one('.rn_BreadCrumb').all('.rn_BreadCrumbLink'); i < expectedItems - 1; i++) {
                Y.Assert.areSame('bananas ' + i, Y.Lang.trim(crumbs.item(i).get('text')));
            }
        },

        verifyLinkFocus: function(level) {
            Y.Assert.areSame(Y.Node.getDOMNode(Y.one(baseSelector + ' .rn_ItemLevel' + (level + 1) + ' a')), document.activeElement);
        },

        "No title present": function() {
            Y.assert(!Y.one('.rn_BreadCrumb').getHTML());
        },

        "Widget doesn't steal initial focus": function() {
            Y.Assert.areSame(document.body, document.activeElement);
        },

        "New level is added and parent item is the only breadcrumb": function() {
            this.addNewLevel();
            this.verifyBreadCrumb(1);
        },

        "Subsequent levels are added and previous levels are turned into links": function() {
            this.addNewLevel();
            this.addNewLevel();
            this.addNewLevel();
            this.addNewLevel();
            this.verifyBreadCrumb(4);
        },

        "Clicking on a previous level shows that level": function() {
            this.addNewLevel();
            this.addNewLevel();
            this.addNewLevel();

            Y.one('.rn_BreadCrumb').all('a').slice(-1).item(0).simulate('click');
            this.verifyBreadCrumb(2);
            this.verifyLinkFocus(2);

            Y.one('.rn_BreadCrumb').all('a').slice(-1).item(0).simulate('click');
            this.verifyBreadCrumb(1);
            this.verifyLinkFocus(1);
        }
    }));

    return suite;
}).run();
