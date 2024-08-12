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
                        if(first && last && numberOfResults) {
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
        name: "Ensure proper results for KB + Social search response events",
        testReportResponse: function() {
            this.initValues();
            this.testResults(this.widgetData.js.firstResult, this.widgetData.js.lastResult, this.widgetData.js.totalResults);
            var eo = this.buildEventObject({
                keyword: {filters: {data: "cell phone"}},
                page: 1
            });
            this.instance.searchSource().fire('send', eo);
            
            eo.data = {
                "search_term": "phone",
                "total_num": 21,
                "report_id": this.widgetData.attrs.report_id,
                "start_num": 1,
                "end_num": 10
            };
            this.instance.searchSource().fire('response', eo);
            this.testResults(1, 10, 21);
            
            eo.allFilters = {
                keyword: {filters: {data: "cell phone"}}, page: 2
            };
            this.instance.searchSource().fire('send', eo);
            eo.data = {
                "search_term": "phone",
                "total_num": 21,
                "report_id": this.widgetData.attrs.report_id,
                "start_num": 11,
                "end_num": 20
            };
            this.instance.searchSource().fire('response', eo);
            this.testResults(11, 20, 21);
        },
        
        testSocialResponse: function() {
            this.initValues();
            var eo = this.buildEventObject({keyword: {filters: {data: "phone"}}, page: 1});
            this.instance.searchSource().fire('send', eo);
            this.instance.searchSource(this.widgetData.attrs.source_id).fire('response', {data: {social: {data: {totalResults: 20}}}});
            this.testResults(1, 20, 20);
            
            eo.allFilters = {keyword: {filters: {data: "phone"}}, page: 1};
            this.instance.searchSource().fire('send', eo);
            this.instance.searchSource(this.widgetData.attrs.source_id).fire('response', {data: {social: {data: {totalResults: 30}}}});
            this.testResults(1, 20, 20);
            
            eo.data = {
                "search_term": "phone",
                "total_num": 15,
                "report_id": this.widgetData.attrs.report_id,
                "start_num": 1,
                "end_num": 5,
                "page": 1
            };
            this.instance.searchSource().fire('response', eo);
            this.testResults(1, 25, 35);
            
            eo.allFilters = {keyword: {filters: {data: "phone"}}, page: 2};
            this.instance.searchSource().fire('send', eo);
            eo.data = {
                "search_term": "phone",
                "total_num": 15,
                "report_id": this.widgetData.attrs.report_id,
                "start_num": 6,
                "end_num": 10,
                "page": 2
            };
            this.instance.searchSource().fire('response', eo);
            this.testResults(26, 30, 35);
        }
        
    }));
    return resultInfoTests;
});
UnitTest.run();
