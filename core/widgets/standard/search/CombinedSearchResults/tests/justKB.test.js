UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'CombinedSearchResults_0'
}, function(Y, widget, baseSelector){
    var tests = new Y.Test.Suite({
        name: "standard/reports/CombinedSearchResults",
        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.id = 'CombinedSearchResults_0';
                    this.instance = RightNow.Widgets.getWidgetInstance(this.id);
                    this.widgetData = this.instance.data;
                    this.id = 'rn_' + this.id;
                    this.testResults = function() {
                        var contents = Y.one('#' + this.id + "_Content"),
                            list = contents.one('*');
                        Y.Assert.isTrue(contents.hasClass('rn_Content'));
                        Y.Assert.areSame(list.get('children').size(), this.widgetData.attrs.per_page);
                    };
                }
            };
            for(var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    tests.add(new Y.Test.Case({
        name: "Test new results coming back",
        testResponse: function() {
            this.initValues();
            this.testResults();

            // Verify searchSource gets hit, and CombinedSearchResults deals with the results
            UnitTest.overrideMakeRequest('/ci/ajaxRequest/getReportData', {report_id: String(widget.data.attrs.report_id)}, '_searchResponse', widget, null, [{data:''}]);
            function testResponse() {
                this.testResults();
                Y.Assert.isFalse(Y.one('#CombinedSearchResults_0_Loading').hasClass('rn_Loading'));
            }
            this.instance.searchSource().on("response", testResponse, this)
                .fire("search", new RightNow.Event.EventObject(null, {
                filters: {
                    searchName: "keyword",
                    data: "",
                    rnSearchType: "kw",
                    report_id: this.widgetData.attrs.report_id
            }}));
        }
    }));
    return tests;
});
UnitTest.run();