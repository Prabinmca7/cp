 /*global UnitTest:false*/
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
         * Tests that the widget's reponse to correct data and an response event is to display the no results element
         */
        testReportResponse: function() {
            this.initValues();
            
            function testExistence() {
                var spelling = Y.one(this.baseID + "Spell");
                Y.Assert.isTrue(spelling.hasClass('rn_Spell'));
                Y.Assert.isTrue(spelling.hasClass('rn_Hidden'));
                Y.Assert.isTrue(spelling.get('innerHTML').indexOf(this.widgetData.attrs.label_spell) > -1);
                
                var noResults = Y.one(this.baseID + "NoResults");
                Y.Assert.isTrue(noResults.hasClass('rn_NoResults'));
                Y.Assert.isFalse(noResults.hasClass('rn_Hidden'));
                
                var results = Y.one(this.baseID + "Results");
                Y.Assert.isTrue(results.hasClass('rn_Results'));
                Y.Assert.isTrue(results.hasClass('rn_Hidden'));
                
                var suggestion = Y.one(this.baseID + "Suggestion");
                Y.Assert.isTrue(suggestion.hasClass('rn_Suggestion'));
                Y.Assert.isTrue(suggestion.hasClass('rn_Hidden'));
            }

            testExistence.call(this);
            
            var eo = new RightNow.Event.EventObject();
            eo.w_id = this.instanceID;
            eo.filters = {"allFilters": {"format": "something"}};
            eo.data = {
                "search_term": "robots",
                "stopword": "stop",
                "not_dict": "some words",
                "total_num": 0,
                "report_id": this.widgetData.attrs.report_id
            };

            this.instance.searchSource().fire("response", eo);
            testExistence.call(this);
        }
    }));
    return resultInfoTests;
});
UnitTest.run();
