 /*global UnitTest:false*/
UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'ResultInfo_0'
}, function(Y, widget, baseSelector){
    var resultInfoTests = new Y.Test.Suite({
        name: "standard/reports/ResultInfo",
        setUp: function(){
            var testExtender = {
                initValues: function() {
                    this.instanceID = 'ResultInfo_0';
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.widgetData = this.instance.data;
                    this.baseID = '#rn_' + this.instanceID + '_';
                    this.instance.searchSource().on('send', function() { return false; });
                },
                testResults: function(first, last, numberOfResults) {
                    var noResults, results, aTag,
                        spelling = Y.one(this.baseID + "Spell");
                    Y.Assert.isTrue(spelling.hasClass('rn_Spell'));
                    Y.Assert.isTrue(spelling.hasClass('rn_Hidden'));
                    if(first === "noResults" && !last && !numberOfResults) {
                        noResults = Y.one(this.baseID + "NoResults");
                        Y.Assert.isTrue(noResults.hasClass('rn_NoResults'));
                        Y.Assert.isFalse(noResults.hasClass('rn_Hidden'));
                        results = Y.one(this.baseID + "Results");
                        Y.Assert.isTrue(results.hasClass('rn_Results'));
                        Y.Assert.isTrue(results.hasClass('rn_Hidden'));
                    }
                    else {
                        noResults = Y.one(this.baseID + "NoResults");
                        Y.Assert.isTrue(noResults.hasClass('rn_NoResults'));
                        Y.Assert.isTrue(noResults.hasClass('rn_Hidden'));
                        
                        results = Y.one(this.baseID + "Results");
                        Y.Assert.isTrue(results.hasClass('rn_Results'));
                        Y.Assert.isFalse(results.hasClass('rn_Hidden'));
                        aTag = results.all("a");
                        Y.Assert.areSame(0, aTag.size());
                        if(first !== undefined && last !== undefined && numberOfResults !== undefined) {
                            Y.Assert.areSame(RightNow.Text.sprintf(this.widgetData.attrs.label_results, first, last, numberOfResults), Y.Lang.trim(results.get('innerHTML')));
                        }
                    }
                    var suggestion = Y.one(this.baseID + "Suggestion");
                    Y.Assert.isTrue(suggestion.hasClass('rn_Suggestion'));
                    Y.Assert.isTrue(suggestion.hasClass('rn_Hidden'));
                },
                buildEventObject: function(filters) {
                    var eo = new RightNow.Event.EventObject(this);
                    eo.filters.allFilters = eo.allFilters = filters;
                    eo.report_id = this.widgetData.attrs.report_id;

                    return eo;
                }
            };
            for(var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    resultInfoTests.add(new Y.Test.Case({
        name: "Ensure proper results for KB + Social + Intent Guide search response events",
        testReportResponse: function() {
            this.initValues();
            this.testResults("noResults");
            var eo = this.buildEventObject({keyword: {filters: {data: "cell phone"}}, page: 1});
            this.instance.searchSource().fire("send", eo);
            
            eo.data = {
                "search_term": "phone",
                "total_num": 21,
                "report_id": this.widgetData.attrs.report_id,
                "start_num": 1,
                "end_num": 10
            };
            this.instance.searchSource().fire("response", eo);
            this.testResults("noResults");
            
            eo.allFilters = {keyword: {filters: {data: "cell phone"}}, page: 2};
        
            this.instance.searchSource().fire("send", eo);
            eo.data = {
                "search_term": "phone",
                "total_num": 21,
                "report_id": this.widgetData.attrs.report_id,
                "start_num": 11,
                "end_num": 20
            };
            this.instance.searchSource().fire("response", eo);
            this.testResults("noResults");
        },
        
        testSocialResponse: function() {
            this.initValues();
            var first = this.widgetData.js.firstResult,
                last = this.widgetData.js.lastResult,
                total = this.widgetData.js.totalResults,
                eo = this.buildEventObject({keyword: {filters: {data: "cell phone"}}, page: 1});

            this.instance.searchSource().fire("send", eo);
            this.instance.searchSource(this.widgetData.attrs.source_id).fire("response", {data: {social: {data: {totalResults: 20}}}});
            this.testResults(1, 20, 20);
            
            eo.allFilters = {keyword: {filters: {data: "cell phone"}}, page: 1};
            this.instance.searchSource().fire("send", eo);
            this.instance.searchSource(this.widgetData.attrs.source_id).fire("response", {data: {social: {data: {totalResults: 30}}}});
            this.testResults(1, 20, 20);
            
            eo.allFilters = {keyword: {filters: {data: "phone"}}};
            this.instance.searchSource().fire("send", eo);
            this.instance.searchSource(this.widgetData.attrs.source_id).fire("response", {data: {social: {data: {totalResults: 30}}}});
            this.testResults(1, 20, 20);
            
            eo.data = {
                "search_term": "phone",
                "total_num": 15,
                "report_id": this.widgetData.attrs.report_id,
                "start_num": 1,
                "end_num": 5,
                "page": 1
            };
            this.instance.searchSource().fire("response", eo);
            this.testResults(1, 20, 20);
            
            eo.allFilters = {keyword: {filters: {data: "phone"}}, page: 2};
            this.instance.searchSource().fire("send", eo);
            eo.data = {
                "search_term": "phone",
                "total_num": 15,
                "report_id": this.widgetData.attrs.report_id,
                "start_num": 6,
                "end_num": 10,
                "page": 2
            };
            this.instance.searchSource().fire("response", eo);
            this.testResults(1, 20, 20);
            
            eo.allFilters = {keyword: {filters: {data: "cell phone"}}};
            this.instance.searchSource().fire("send", eo);
            this.instance.searchSource(this.widgetData.attrs.source_id).fire("response", {data: {social: {data: {totalResults: null}}}});
            this.testResults("noResults");
        },
        
        testIntentResponse: function() {
            this.initValues();
            var eo = this.buildEventObject({keyword: {filters: {data: "cell phone"}}, page: 1});

            this.instance.searchSource().fire("send", eo);
            this.instance.searchSource(this.widgetData.attrs.source_id).fire("response", {data: {intentGuide: {numberOfResults: 20}}});
            this.testResults("noResults");
            
            eo.allFilters = {keyword: {filters: {data: "cell phone"}}, page: 1};
            this.instance.searchSource().fire("send", eo);
            this.instance.searchSource(this.widgetData.attrs.source_id).fire("response", {data: {intentGuide: {numberOfResults: 30}}});
            this.testResults("noResults");
            
            eo.allFilters = {keyword: {filters: {data: "phone"}}};
            this.instance.searchSource().fire("send", eo);
            this.instance.searchSource(this.widgetData.attrs.source_id).fire("response", {data: {intentGuide: {numberOfResults: 30}}});
            this.testResults("noResults");
            
            eo.data = {
                "search_term": "phone",
                "total_num": 15,
                "report_id": this.widgetData.attrs.report_id,
                "start_num": 1,
                "end_num": 5,
                "page": 1
            };
            this.instance.searchSource().fire("response", eo);
            this.testResults("noResults");
            
            eo.allFilters = {keyword: {filters: {data: "phone"}}, page: 2};
            this.instance.searchSource().fire("send", eo);
            eo.data = {
                "search_term": "phone",
                "total_num": 15,
                "report_id": this.widgetData.attrs.report_id,
                "start_num": 6,
                "end_num": 10,
                "page": 2
            };
            this.instance.searchSource().fire("response", eo);
            this.testResults("noResults");
        },
        
        testIntentandSocial: function() {
            this.initValues();
            var eo = this.buildEventObject({keyword: {filters: {data: "cell phone"}}, page: 1});
            this.instance.searchSource().fire("send", eo);
            this.instance.searchSource(this.widgetData.attrs.source_id).fire("response", {data: {social: {data: {totalResults: 20}}, intentGuide: {numberOfResults: 20}}});
            this.testResults(1, 20, 20);
            
            eo.allFilters = {keyword: {filters: {data: "cell phone"}}, page: 1};
            this.instance.searchSource().fire("send", eo);
            eo.numberOfResults = 30;
            this.instance.searchSource(this.widgetData.attrs.source_id).fire("response", {data: {social: {data: {totalResults: 30}}, intentGuide: {numberOfResults: 30}}});
            this.testResults(1, 20, 20);
            
            eo.allFilters = {keyword: {filters: {data: "phone"}}};
            this.instance.searchSource().fire("send", eo);
            this.instance.searchSource(this.widgetData.attrs.source_id).fire("response", {data: {social: {data: {totalResults: 30}}, intentGuide: {numberOfResults: 30}}});
            this.testResults(1, 20, 20);
            
            eo.data = {
                "search_term": "phone",
                "total_num": 15,
                "report_id": this.widgetData.attrs.report_id,
                "start_num": 1,
                "end_num": 5,
                "page": 1
            };
            this.instance.searchSource().fire("response", eo);
            this.testResults(1, 20, 20);
            
            eo.allFilters = {keyword: {filters: {data: "phone"}}, page: 2};
            this.instance.searchSource().fire("send", eo);
            eo.data = {
                "search_term": "phone",
                "total_num": 15,
                "report_id": this.widgetData.attrs.report_id,
                "start_num": 6,
                "end_num": 10,
                "page": 2
            };
            this.instance.searchSource().fire("response", eo);
            this.testResults(1, 20, 20);
            
            eo.allFilters = {keyword: {filters: {data: "noresultsforthiskeyword"}}};
            this.instance.searchSource().fire("send", eo);
            this.instance.searchSource(this.widgetData.attrs.source_id).fire("response", {data: {social: {data: {totalResults: 0}}, intentGuide: {numberOfResults: 0}}});
            this.testResults("noResults");
            
            eo.data = {
                "search_term": "noresultsforthiskeyword",
                "total_num": 0,
                "report_id": this.widgetData.attrs.report_id,
                "start_num": 0,
                "end_num": 0,
                "page": 1
            };
            this.instance.searchSource().fire("response", eo);
            this.testResults("noResults");
        }
    }));
    return resultInfoTests;
});
UnitTest.run();
