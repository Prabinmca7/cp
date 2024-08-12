UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'MobileProductCategorySearchFilter_0',
    jsFiles: ['/euf/core/debug-js/RightNow.UI.Mobile.js']
}, function(Y, widget, baseSelector){
    var tests = new Y.Test.Suite({
        name: "standard/search/MobileProductCategorySearchFilter",
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
        setUp: function() {
            this.origMakeRequest = RightNow.Ajax.makeRequest;
            RightNow.Ajax.makeRequest = RightNow.Event.createDelegate(this, this.makeRequest);
            this.madeRequest = false;
        },

        tearDown: function() {
            RightNow.Ajax.makeRequest = this.origMakeRequest;
        },

        makeRequest: function(url, data, options) {
            this.successCallback = options.successHandler;
            this.failureCallback = options.failureHandler;
            this.madeRequest = true;
            this.originalEventObject = options.data;
            this.callbackContext = options.scope;
        },

        //Go down path, exit. Go back to same path, verify path is correctly highlighted
        'Verify going down the same path twice correcly highlights selections': function() {
            this.initValues();
            this.validateSelectionOnPage(0);
            this.validateOpen();
            this.element('_Input1_2').simulate('click');
            Y.Assert.isTrue(this.madeRequest, 'Ajax was not set up properly');
            this.successCallback.call(this.callbackContext, {"result":[[{"id":2,"label":"Android","hasChildren":true},{"id":3,"label":"Blackberry","hasChildren":false},{"id":4,"label":"iPhone","hasChildren":true}]]}, this.originalEventObject);
            this.element('_Level2Input_1_3').simulate('click');
            this.validateClose(2);
            this.validateOpen();
            this.element('_Input1_2').simulate('click');
            this.validateSelectionInDialog([this.baseID('_Input1_2'), this.baseID('_Level2Input_1_3')]);;
            this.validateClose(2);
            this.validateOpen();
            this.element('_Input1_1').simulate('click');
            this.validateClose(1);
        },

        //Go down path, exit, go down different path, exit, go back to first path, verify first path isn't highlighted from original trip down.
        'Verify going down different paths correctly unhighlights old selections': function() {
            this.validateSelectionOnPage(0);
            this.validateOpen();
            this.element('_Input1_2').simulate('click');
            this.element('_Level2Input_1_3').simulate('click');
            this.validateSelectionOnPage(2);
            this.validateClose(2)
            this.validateOpen();
            this.element('_Input1_7').simulate('click');
            Y.Assert.isTrue(this.madeRequest, 'Ajax was not set up properly');
            this.successCallback.call(this.callbackContext, {"result":[[{"id":132,"label":"p1a","hasChildren":false},{"id":133,"label":"p1b","hasChildren":false},{"id":129,"label":"p2","hasChildren":true}]]}, this.originalEventObject);
            this.element('_Level2Input_128_3').simulate('click');
            this.validateSelectionOnPage(2);
            this.validateClose(2);
            this.validateOpen();
            this.element('_Input1_7').simulate('click');
            this.validateClose(2);
            this.validateOpen();
            this.element('_Input1_2').simulate('click');
            this.validateSelectionInDialog([this.baseID('_Input1_7'), this.baseID('_Level2Input_128_3')]);
            this.validateClose(2);
            this.validateOpen();
            this.element('_Input1_1').simulate('click');
            this.validateClose(1);
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
                if(Y.one("#rn_ActionDialog_Generated1").one("a.rn_Button"))
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

            //@@@ QA 130430-000003 Filters should link to the search page
            if(filters > 1) {
                for(i = 0; i < allFilters.size(); i++) {
                    href = allFilters.item(i).get('href');
                    if(i !== allFilters.size() - 1) {
                        Y.Assert.isTrue(href.indexOf(widget.data.js.searchPage + widget.data.js.searchName + '/') !== -1);
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
            Y.Assert.areSame(selectedIDs.length, selectedItems.size(), 'Unexpected number of selected elements');
        }
    }));
    return tests;
});
UnitTest.run();