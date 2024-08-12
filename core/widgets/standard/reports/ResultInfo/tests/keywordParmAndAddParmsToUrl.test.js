UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'ResultInfo_0',
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
                }
            };
            for(var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    resultInfoTests.add(new Y.Test.Case({
        name: "Ensure no results displays properly",
        /**
         * Tests that the widget's reponse to correct data and an response event is to display the results element
         */
        testReportResponse: function() {
            this.initValues();
            
            function testExistence(eo) {
                var spelling = Y.one(this.baseID + "Spell");
                Y.Assert.isTrue(spelling.hasClass('rn_Spell'));
                Y.Assert.isTrue(spelling.hasClass('rn_Hidden'));
                Y.Assert.isTrue(spelling.get('innerHTML').indexOf(this.widgetData.attrs.label_spell) > -1);
                
                var noResults = Y.one(this.baseID + "NoResults");
                Y.Assert.isTrue(noResults.hasClass('rn_NoResults'));
                Y.Assert.isTrue(noResults.hasClass('rn_Hidden'));
                
                var results = Y.one(this.baseID + "Results");
                Y.Assert.isTrue(results.hasClass('rn_Results'));
                Y.Assert.isFalse(results.hasClass('rn_Hidden'));
                var aTag = results.all("a");
                aTag = aTag.item(0);
                Y.Assert.areSame(aTag.get('innerHTML'), "phone");
                if(eo) {
                    eo.filters.allFilters.format.parmList = this.widgetData.attrs.add_params_to_url;
                    Y.Assert.isTrue(aTag.get('href').indexOf(RightNow.Url.addParameter(this.widgetData.js.linkUrl + "phone" + RightNow.Url.buildUrlLinkString(eo.filters.allFilters) + "/search/1", "session", RightNow.Url.getSession())) > -1);
                }
                
                var suggestion = Y.one(this.baseID + "Suggestion");
                Y.Assert.isTrue(suggestion.hasClass('rn_Suggestion'));
                Y.Assert.isTrue(suggestion.hasClass('rn_Hidden'));
            }
            
            var eo = new RightNow.Event.EventObject();
            eo.w_id = this.instanceID;
            eo.filters = {"allFilters": {format: {}, filters: {}}};
            eo.data = {
                "search_term": "phone",
                "stopword": "stop",
                "not_dict": "some words",
                "total_num": 16,
                "end_num": 10,
                "report_id": this.widgetData.attrs.report_id
            };

            testExistence.call(this);
            


            this.instance.searchSource().fire("response", eo);
            testExistence.call(this, eo);
        }
    }));
    return resultInfoTests;
});
 UnitTest.run();
