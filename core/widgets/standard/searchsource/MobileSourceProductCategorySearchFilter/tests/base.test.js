UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'MobileSourceProductCategorySearchFilter_0',
    jsFiles: ['/euf/core/debug-js/RightNow.UI.Mobile.js']
}, function(Y, widget, baseSelector){
    var tests = new Y.Test.Suite({
        name: "standard/search/MobileSourceProductCategorySearchFilter",
        setUp: function(){
            var testExtender = {
                initValues: function() {
                    this.baseID = function(post) {
                        return '#rn_' + widget.instanceID + ((widget.data.attrs.filter_type === 'Product') ? '_Product' : '_Category') + post;
                    };
                    this.element = function(post) {
                        return Y.one(this.baseID(post));
                    };
                }
            };
            for(var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    tests.add(new Y.Test.Case({
        name: "UI functional tests",

        setUp: function () {
            // Don't navigate away from this page.
            widget.data.attrs.search_on_select = false;
            widget.data.attrs.label_nothing_selected = RightNow.Interface.getMessage("SELECT_A_PRODUCT_LBL");

            this.makeRequest = RightNow.Ajax.makeRequest;

            RightNow.Ajax.makeRequest = Y.bind(function () {
                this.makeRequestCalledWith = [].slice.call(arguments);
            }, this);
        },

        tearDown: function () {
            RightNow.Ajax.makeRequest = this.makeRequest;
            this.makeRequestCalledWith = null;
        },

        selectItem: function (itemPosition, level) {
            var prevLevel = level > 1 ? '_' + (level - 1) : '',
                items = Y.one(this.baseID('_Level' + level + 'Input' + prevLevel)).all('div');

            items.item(itemPosition).one('label').simulate('click');
        },

        selectNoValue: function () {
            // Click on 'All'
            var topLevelItems = Y.one(baseSelector + '_Product_Level1Input').all('.rn_Parent');
            topLevelItems.item(2).one('label').simulate('click');
        },

        "Dialog should open and close": function() {
            this.initValues();
            this.validateOpen();
            this.validateClose();
            this.validateSelectionOnPage();
        },

        "Selecting a top level item should display on the page": function() {
            this.initValues();
            this.validateOpen();
            this.selectItem(2, 1);
            this.validateClose(0);
            this.validateSelectionOnPage(1);
        },

        "Selecting a multi-level item should show the second level item and then display on the page": function() {
            var subLevelResponseMock = {
                'w_id': widget.instanceID,
                'data': {
                    'data_type': "Product",
                    'level': 2,
                    'hier_data': [
                        { 'ID': 2, 'label': 'Android', 'hasChildren': true },
                        { 'ID': 3, 'label': 'Blackberry', 'hasChildren': false },
                        { 'ID': 4, 'label': 'iPhone', 'hasChildren': true }
                    ]
                }
            };

            this.initValues();
            this.validateOpen();
            this.selectItem(1, 1);

            // verify what was requested
            Y.Assert.isTrue(this.makeRequestCalledWith[0].indexOf('getHierValues') > -1);
            Y.Assert.areSame(this.makeRequestCalledWith[1].filter, 'Product');
            Y.Assert.areSame(this.makeRequestCalledWith[1].id, 1);

            widget.getSubLevelResponse('evt_menuFilterGetResponse', [subLevelResponseMock]);

            Y.Assert.areSame(widget._currentLevel, 2);
            Y.Assert.areSame('none', Y.one(this.baseID('_Level1Input')).getComputedStyle('display'));

            // verify the second level has been shown
            var secondLevelForm = Y.one(this.baseID('_Level2Input_1'));
            Y.Assert.isNotNull(secondLevelForm);
            Y.Assert.areSame('block', secondLevelForm.getComputedStyle('display'));
            Y.Assert.areSame(3, secondLevelForm.all('.rn_SubItem').size());

            this.selectItem(2, 2);
            this.validateClose(0);
            this.validateSelectionOnPage(1);
        },

        "Clicking remove should pull the filter from the DOM": function() {
            this.initValues();
            this.element('_FilterRemove').simulate('click');
            this.validateClose(0);
            this.validateSelectionOnPage();
            this.validateSelectionInDialog();
        },

        validateOpen: function() {
            this.element('_Launch').simulate('click');
            Y.Assert.isFalse(Y.one(this.baseID('_Level1Input')).hasClass('rn_Hidden'));
            Y.Assert.areSame("block", Y.one(this.baseID('_Level1Input')).getComputedStyle("display"));
        },

        validateClose: function(times) {
            if (typeof times !== "number") {
                times = 1;
            }
            for (var i = 0; i < times; i++) {
                Y.one("#rn_ActionDialog_Generated1").one("a.rn_Button").simulate('click');
            }
            Y.Assert.areSame("none", Y.one("#rn_ActionDialog_Generated1").getComputedStyle("display"));
        },

        validateSelectionOnPage: function(filters) {
            if (typeof filters !== "number") {
                filters = 0;
            }
            var assertion = (filters) ? 'isFalse' : 'isTrue',
                allFilters = Y.one(this.baseID("_Filters")).get("children"),
                href, i;

            if(filters > 1) {
                for(i = 0; i < allFilters.size(); i++) {
                    href = allFilters.item(i).get('href');
                    if(i !== allFilters.size() - 1) {
                        Y.Assert.isTrue(href.indexOf(widget.data.js.searchPage + widget.data.js.filter.key + '/') !== -1);
                    }
                    else {
                        Y.Assert.areSame('javascript:void(0);', href);
                    }
                }
            }

            Y.Assert.areSame(filters, allFilters.size());
            Y.Assert[assertion](Y.one(this.baseID("_FilterRemove")).hasClass("rn_Hidden"));
        },

        validateSelectionInDialog: function(selectedIDs) {
            var selectedItems = Y.one("#rn_ActionDialog_Generated1").all(".rn_Selected");
            if (!selectedIDs) {
                selectedItems.each(function(node) {
                    if (node.one("input").get("id").indexOf("_Level") === -1) {
                        Y.fail("A wrong top-level item remains selected in the dialog");
                    }
                }, this);
                return;
            }
            if (typeof selectedIDs === "string") {
                selectedIDs = [selectedIDs];
            }
            selectedItems.each(function(node) {
                var input = node.one('input');
                if (Y.Array.indexOf(selectedIDs, '#' + input.get('id')) === -1) {
                    Y.fail("Something is selected in the dialog that shouldn't be");
                }
            }, this);
        }
    }));
    return tests;
});
UnitTest.run();
