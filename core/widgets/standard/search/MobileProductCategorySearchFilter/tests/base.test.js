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
        'Verify that search event provides initial filter values': function() {
            this.initValues();
            widget.searchSource().once('send', function(evtName, args) {
                var searchName = (widget.data.attrs.filter_type  === 'Product') ? 'p' : 'c', urlValue;
                Y.Assert.areSame('send', evtName);
                args = args[0].allFilters[searchName].filters;
                if((urlValue = RightNow.Url.getParameter(searchName)) && widget.data.js.initial) {
                    Y.Assert.areSame(urlValue, args.data[0].join(','));
                }
                else {
                    Y.assert(!args.data[0].length);
                }
                Y.Assert.areSame(widget.data.attrs.report_id, args.report_id);
                Y.Assert.areSame(searchName, args.searchName);
                return false;
            });
            widget.searchSource().fire('search');

            //Now that we've checked the initial filter, pretend the widget isn't initialized for the other tests
            widget._initialized = false;
        },
        testNewSearchResponse: function() {
            this.initValues();
            widget.searchSource().fire("response", new RightNow.Event.EventObject(widget.instanceID, {
                filters: {
                    report_id: widget.data.attrs.report_id,
                    allFilters: {
                        p: {filters: {data: {reconstructData: [{value: 1, label: "Mobile Phones"}]}}},
                        c: {filters: {data: {reconstructData: [{value: 131, label: "Basics"}]}}}
                    }
                }
            }));
            this.validateSelectionOnPage(1);
            Y.Assert.areSame(null, Y.one("#rn_ActionDialog_Generated1"));
        },

        "New search with multiple product levels should maintain currentLevel": function() {
            this.initValues();
            widget._initialized = false;
            widget.searchSource().fire("response", new RightNow.Event.EventObject(widget.instanceID, {
                filters: {
                    report_id: widget.data.attrs.report_id,
                    allFilters: {
                        p: {filters: {data: {reconstructData: [
                            {value: 1, label: "Mobile Phones"},
                            {value: 2, label: "Android"},
                            {value: 8, label: "Motorola Droid"}
                        ]}}},
                        c: {filters: {data: {reconstructData: [{value: 131, label: "Basics"}]}}}
                    }
                }
            }));
            if (widget.data.js.searchName === 'p') {
                Y.Assert.areSame(3, widget._selections.length);
                Y.Assert.areSame(3, widget._currentLevel);
            }
            else {
                Y.Assert.areSame(1, widget._selections.length);
                Y.Assert.areSame(1, widget._currentLevel);
            }
            widget._initialized = false;
        },

        testUnsetSearchResponse: function() {
            this.initValues();
            widget.searchSource().fire("response", new RightNow.Event.EventObject(widget.instanceID, {
                filters: {
                    report_id: widget.data.attrs.report_id,
                    allFilters: {
                        p: {filters: {data: {0: ""}}},
                        c: {filters: {data: {0: ""}}}
                    }
                }
            }));
            this.validateSelectionOnPage();
            Y.Assert.areSame(null, Y.one("#rn_ActionDialog_Generated1"));
        },

        testOpenAndClose: function() {
            this.initValues();
            this.validateOpen();
            this.validateClose();
            this.validateSelectionOnPage();
            this.validateSelectionInDialog();
        },

        testMultiLevelNoSelection: function() {
            this.initValues();
            this.validateOpen();
            var test = function() {
                RightNow.Event.unsubscribe("evt_menuFilterGetResponse", test);
                this.resume(function() {
                    this.validateClose(2);
                    this.validateSelectionOnPage();
                    this.validateSelectionInDialog();
                });
            };
            RightNow.Event.subscribe("evt_menuFilterGetResponse", test, this);
            this.element(((widget.data.js.searchName === 'p') ? '_Input1_2' : '_Input1_7')).simulate('click');
            this.wait();
        },

        testSingleLevelSelection: function() {
            this.initValues();
            this.validateOpen();
            this.element('_Input1_3').simulate('click');
            this.validateClose(0);
            this.validateSelectionOnPage(1);
            this.validateSelectionInDialog(this.baseID('_Input1_3'));
        },

        testMultiLevelSelection: function() {
            this.initValues();
            this.validateOpen();
            var test = function() {
                RightNow.Event.unsubscribe("evt_menuFilterGetResponse", test);
                this.resume(function() {
                    this.element(((widget.data.js.searchName === 'p') ? '_Level2Input_1_3' : '_Level2Input_71_2')).simulate('click');
                    this.validateClose(0);
                    this.validateSelectionOnPage(2);
                    if (widget.data.js.searchName === 'p') {
                        this.validateSelectionInDialog([this.baseID('_Input1_2'), this.baseID('_Level2Input_1_3')]);
                    }
                    else {
                        this.validateSelectionInDialog([this.baseID('_Input1_7'), this.baseID('_Level2Input_71_2')]);
                    }
                });
            };
            RightNow.Event.subscribe("evt_menuFilterGetResponse", test, this);
            this.element(((widget.data.js.searchName === 'p') ? '_Input1_2' : '_Input1_7')).simulate('click');
            this.wait();
        },

        testRemoveFilter: function() {
            this.initValues();
            this.element('_FilterRemove').simulate('click');
            this.validateClose(0);
            this.validateSelectionOnPage();
            this.validateSelectionInDialog();
        },

        'Should not respond to non-report search source events': function() {
            this.initValues();

            var newWidget = RightNow.Widgets.MobileProductCategorySearchFilter.extend({
                overrides: {
                    constructor: function() {
                        this.calledSearch = false;
                        this.calledResponse = false;
                        this.parent();
                    },
                    _onSearch: function() {
                        this.calledSearch = true;
                        this.parent();
                    },
                    _onReportResponse: function() {
                        this.calledResponse = true;
                        this.parent();
                    }
                }
            });
            widget.data.attrs.source_id = 'banana';
            var instance = new newWidget(widget.data, widget.instanceID, widget.Y);
            instance.searchSource('banana').on('send', function () { return false; })
                .fire('search', new RightNow.Event.EventObject()).fire('response', new RightNow.Event.EventObject());
            Y.Assert.isFalse(instance.calledSearch);
            Y.Assert.isFalse(instance.calledResponse);
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
        }
    }));
    return tests;
});
UnitTest.run();
