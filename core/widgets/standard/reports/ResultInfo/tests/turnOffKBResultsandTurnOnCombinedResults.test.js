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
                    // prevent searches from going to server
                    this.instance.searchSource().on('send', function() { return false; });
                },
                testExistence: function(numberOfResults) {
                    var noResults, results, aTag,
                        spelling = Y.one(this.baseID + "Spell");
                    Y.Assert.isTrue(spelling.hasClass('rn_Spell'));
                    Y.Assert.isTrue(spelling.hasClass('rn_Hidden'));

                    noResults = Y.one(this.baseID + "NoResults");
                    Y.Assert.isTrue(noResults.hasClass('rn_NoResults'));
                    Y.Assert.isTrue(noResults.hasClass('rn_Hidden'));

                    results = Y.one(this.baseID + "Results");
                    Y.Assert.isTrue(results.hasClass('rn_Results'));
                    Y.Assert.isFalse(results.hasClass('rn_Hidden'));
                    aTag = results.all("a");
                    Y.Assert.areSame(0, aTag.size());
                    if(numberOfResults) {
                        Y.Assert.areSame(RightNow.Text.sprintf(this.widgetData.attrs.label_results, 1, numberOfResults, numberOfResults), Y.Lang.trim(results.get('innerHTML')));
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
    var totalResults;
    resultInfoTests.add(new Y.Test.Case({
        name: "Ensure proper results",
        testSocialResponse: function() {
            this.initValues();

            var eo = this.buildEventObject({keyword: {filters: {data: "cell phone"}}, page: 1});

            this.instance.searchSource().fire("send", eo);
            this.instance.searchSource(this.widgetData.attrs.source_id).fire("response", {data: {social: {data: {totalResults: 20}}}});
            this.testExistence(20);
            eo.allFilters = {keyword: {filters: {data: "cell phone"}}, page: 1};
            this.instance.searchSource().fire("send", eo);
            this.instance.searchSource(this.widgetData.attrs.source_id).fire("response", {data: {social: {data: {totalResults: 30}}}});
            this.testExistence(20);
        },

        /**
         * Tests that the widget's reponse to correct data and an response event is to display the suggested spelling & no results elements
         */
        testReportResponse: function() {
            this.initValues();
            this.testExistence(totalResults);
            
            var eo = new RightNow.Event.EventObject(this, {filters: {allFilters: {format: "something"}}, data: {
                "search_term": "phone",
                "total_num": 20,
                "report_id": this.widgetData.attrs.report_id,
                "start_num": 10,
                "end_num": 20
            }});

            this.instance.searchSource().fire("response", eo);
            this.testExistence();
        },

    }));
    return resultInfoTests;
});
UnitTest.run();
