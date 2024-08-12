UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'DisplaySearchFilters_0'
}, function(Y, widget, baseSelector){
    var testSuite = new Y.Test.Suite({
        name: "standard/search/DisplaySearchFilters",
        setUp: function(){
            var testExtender = {
                getResponseObject: function(testFilters) {
                    return new RightNow.Event.EventObject(null, {
                        filters: {
                            allFilters: testFilters
                        }
                    });
                },
                textContent: (Y.UA.ie) ? 'innerText' : 'textContent'
            }
            for(var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });
    testSuite.add(new Y.Test.Case({
        'A response using the webindexer report has no filters and the widget container is hidden': function() {
            Y.Assert.areSame(widget.data.attrs.label_title, Y.Lang.trim(Y.one(baseSelector + ' .rn_Heading').getHTML()));
            Y.Assert.areSame('', Y.Lang.trim(Y.one(baseSelector + '_FilterContainer').getHTML()));
            widget.searchSource().once('response', function() {
                //Widget is hidden, the value is a default.
                Y.assert(Y.one(baseSelector));
                Y.Assert.areSame('none', Y.one(baseSelector).getStyle('display'));

                //Filter container is empty, no filter is displayed
                Y.Assert.areSame('', Y.Lang.trim(Y.one(baseSelector + '_FilterContainer').getHTML()));
            }, this);

            //Make sure that no searches are performed.
            widget.searchSource().once('send', function() {return false;});
            widget.searchSource().fire('response', this.getResponseObject({
                page: 1,
                recordKeywordSearch: true,
                c: {
                    report_default: null,
                    type: null,
                    filters: {
                        data: [],
                        fltr_id: null,
                        oper_id: null,
                        optlist_id: null,
                        report_id: 10022,
                        rnSearchType: "menufilter"
                    }
                },
                keyword: {
                    data: 'phone',
                    w_id: "KeywordText_11",
                    filters: {
                        data: 'phone',
                        report_id: '10022',
                        rnSearchType: 'keyword',
                        searchName: 'keyword'
                    }
                },
                p: {
                    report_default: null,
                    type: null,
                    filters: {
                        data: [],
                        fltr_id: null,
                        oper_id: null,
                        optlist_id: null,
                        report_id: 10022,
                        rnSearchType: "menufilter"
                    }
                },
                searchType: {
                    type: 'searchType',
                    filters: {
                        data: 5,
                        fltr_id: 5,
                        oper_id: 1,
                        report_id: 10022, //CP_WIDX_REPORT_DEFAULT,
                        rnSearchType: 'searchType'
                    }
                },
                webSearchSort: {
                    w_id: "WebSearchSort_9",
                    filters: {
                        report_id: "10022",
                        searchName: "webSearchSort",
                        data: {
                            col_id: null,
                            sort_direction: 1,
                            sort_order: 1
                        }
                    }
                },
                webSearchType: {
                    w_id: "WebSearchType_10",
                    filters: {
                        data: 1,
                        report_id: "10022",
                        searchName: "webSearchType"
                    }
                }
            }));
        }
    }));
    return testSuite;
});
UnitTest.run();
