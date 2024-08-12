UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'SearchSuggestions_0',
}, function(Y, widget, baseSelector){
    var searchSuggestionsTests = new Y.Test.Suite({
        name: "standard/knowledgebase/SearchSuggestions",
        setUp: function(){
            var testExtender = {
                                
                initValues : function() {
                    this.instanceID = 'SearchSuggestions_0';
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    if (this.instance) {
                        this.widgetData = this.instance.data;
                        this.addParams = this.widgetData.attrs.add_params_to_url;
                        this.label = this.widgetData.attrs.label_title;
                        this.reportID = this.widgetData.attrs.reportID;
                        this.reportPageUrl = this.widgetData.attrs.report_page_url;
                    }
                }
            };
            
            for(var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }            
        }
    });
        
    searchSuggestionsTests.add(new Y.Test.Case({
        name: "Event Handling and Operation",
        
        /**
         * Tests the widget's response to the evt_reportResponse event. It checks if the provided
         * values are displayed and if the corresponding number of items is actually displayed.
         * It also verifies that if no data is provide, then the widget is hidden.
         * 
         * Note that currently if a widget instance is not available the test automatically passes
         */
        testReportResponse: function() {
            this.initValues();
            
            if (this.instance) {
                var eventObject = new RightNow.Event.EventObject(this.instance, {data: this.mockData});
                this.instance.searchSource().fire('response', eventObject);
                
                var rnInstanceID = "rn_" + this.instanceID;
                Y.Assert.isFalse(Y.one("#" + rnInstanceID).hasClass("rn_Hidden"));

                var suggestionList = document.getElementById(rnInstanceID + "_SuggestionsList");
                Y.Assert.isNotNull(suggestionList);
                Y.Assert.areNotSame('', suggestionList.innerHTML);

                this.instance.searchSource().fire('response', new RightNow.Event.EventObject(this.instance));
                Y.Assert.isTrue(Y.one("#" + rnInstanceID).hasClass("rn_Hidden"));
            }
        },

        "Adding params to generated links": function() {
            var origAttr = this.instance.data.attrs.add_params_to_url;

            this.instance.data.attrs.add_params_to_url = 'p,kw';

            this.instance.searchSource().fire('response', new RightNow.Event.EventObject(null, {data: this.mockData, filters: {
                allFilters: {
                    p: {
                        filters: {
                            data: ['200']
                        }
                    },
                    keyword: { filters: { data: 'yeah'} }
                }
            }}));

            var links = Y.all('#rn_' + this.instanceID + ' a');
            Y.Assert.areSame(4, links.size());
            links.each(function(a) {
                Y.Assert.isTrue(/^.*\/p\/200\/kw\/yeah$/.test(a.get('href')));
            });

            this.instance.data.attrs.add_params_to_url = origAttr;
        },

        mockData: {
                    related_prods: [
                        {id: 1, label: 'Test Product 1'},
                        {id: 2, label: 'Test Product 2'}
                    ],
                    related_cats: [
                        {id: 3, label: 'Test Category 1'},
                        {id: 4, label: 'Test Category 2'}
                    ]
                }
    }));

    return searchSuggestionsTests;
});
UnitTest.run();
