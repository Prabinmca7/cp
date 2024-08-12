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
                }
            };
            for(var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });
    tests.add(new Y.Test.Case({
        name: "Test that the proper number of results exist",
        testResponse: function() {
            this.initValues();
            var contents = Y.one('#' + this.id + "_Content"),
                list = contents.one('*');
            Y.Assert.isTrue(contents.hasClass('rn_Content'));

            var social = list.get('children').item(this.widgetData.attrs.social_results - 1);

            if (!this.widgetData.attrs.social_results) {
                Y.Assert.isNull(social);
            }
            else {
                Y.Assert.areSame(social.get('id'), this.id + "_Social");
                Y.Assert.isTrue(social.hasClass('rn_Social'));
            }
        }
    }));

    tests.add(new Y.Test.Case({
        name: "Test report and source isolation",

        verifyReportOnlyResults: function() {
            var lists = Y.one('#' + this.id + '_Content').all('ul');
            Y.Assert.areSame(1, lists.size());
            lists = lists.item(0);
            Y.Assert.areSame(2, lists.get('children').size());
        },

        "Social results only display on first page": function() {
            this.initValues();
            var eo = new RightNow.Event.EventObject(null, {filters: {
                report_id: this.widgetData.attrs.report_id,
                per_page: this.widgetData.attrs.per_page,
                page: 2
            }});
            this.instance.searchSource().on('send', function() { return false; }).fire('appendFilter', eo).fire('search', eo).fire('response', new RightNow.Event.EventObject(null, {data: {
                per_page: eo.filters.per_page,
                headers: [{'heading': ""}, {'heading': ""}, {'heading':""},
                           {'heading': 'something'}, {'heading': 'something else'}, {'heading': 'more something else'}],
                total_num: 2,
                row_num: false,
                data: [["1-1","1-2","1-3"],["2-1","2-2","2-3"]]
            }}));
            this.verifyReportOnlyResults();
        },

        "Social sources don't display and loading is removed when sources are turned off": function() {
            this.initValues();
            this.origAttributes = RightNow.Lang.cloneObject(this.widgetData.attrs);
            this.widgetData.attrs.social_results = 0;

            this.instance.searchSource().fire('search', new RightNow.Event.EventObject());
            this.instance.searchSource().fire('response', new RightNow.Event.EventObject(null, {data: {
                per_page: this.widgetData.attrs.per_page,
                headers: [{'heading': ""}, {'heading': ""}, {'heading':""},
                           {'heading': 'something'}, {'heading': 'something else'}, {'heading': 'more something else'}],
                total_num: 2,
                row_num: false,
                data: [["1-1","1-2","1-3"],["2-1","2-2","2-3"]]
            }}));
            Y.Assert.isFalse(Y.one('#' + this.id + '_Loading').hasClass('rn_Loading'));
            this.verifyReportOnlyResults();

            this.widgetData.attrs = this.origAttributes;
        },

        "Social sources don't display and loading is removed when a search is triggered for the report": function() {
            this.initValues();
            this.origAttributes = RightNow.Lang.cloneObject(this.widgetData.attrs);
            this.widgetData.attrs.social_results = 2;

            this.instance.searchSource(this.widgetData.attrs.report_id).fire('search', new RightNow.Event.EventObject());
            this.instance.searchSource(this.widgetData.attrs.report_id).fire('response', new RightNow.Event.EventObject(null, {data: {
                per_page: this.widgetData.attrs.per_page,
                headers: [{'heading': ""}, {'heading': ""}, {'heading':""},
                           {'heading': 'something'}, {'heading': 'something else'}, {'heading': 'more something else'}],
                total_num: 2,
                row_num: false,
                data: [["1-1","1-2","1-3"],["2-1","2-2","2-3"]]
            }}));
            Y.Assert.isFalse(Y.one('#' + this.id + '_Loading').hasClass('rn_Loading'));
            this.verifyReportOnlyResults();

            this.widgetData.attrs = this.origAttributes;
        },

        "A report with blank values should display based on the hide_empty_columns attribute": function() {
            var data = [["1-1","1-2","1-3","","1-5"],["2-1","2-2","2-3","2-4",""]];
            var headers = [{'heading': ""}, {'heading': ""}, {'heading':""},
                           {'heading': 'something'}, {'heading': ''}];
            var eo = new RightNow.Event.EventObject(null, {
                data: {
                    "report_id": widget.data.attrs.report_id,
                    "start_num": 1,
                    "row_num": true,
                    "total_num": data.length,
                    "per_page": data.length,
                    "headers": headers,
                    "data": data
                  },
                filters:   {
                    "report_id": widget.data.attrs.report_id,
                    "allFilters": widget.data.js.filters,
                    "format": widget.data.js.format
               }
            });

            widget.searchSource().fire("response", eo);

            var content = Y.one(baseSelector + '_Content'),
                liElements = content.all('li'),
                headerElements, itemElements;

            if (widget.data.attrs.hide_empty_columns) {
                headerElements = liElements.item(0).all('.rn_ElementsHeader');
                Y.Assert.areSame(1, headerElements.size());
                Y.Assert.areSame('', headerElements.item(0).get('innerHTML'));
                headerElements = liElements.item(1).all('.rn_ElementsHeader');
                Y.Assert.areSame(1, headerElements.size());
                Y.Assert.areSame('something:', headerElements.item(0).get('innerHTML'));

                itemElements = liElements.item(0).all('.rn_ElementsData');
                Y.Assert.areSame(1, itemElements.size());
                Y.Assert.areSame('1-5', itemElements.item(0).get('innerHTML'));
                itemElements = liElements.item(1).all('.rn_ElementsData');
                Y.Assert.areSame(1, itemElements.size());
                Y.Assert.areSame('2-4', itemElements.item(0).get('innerHTML'));
            }
            else {
                headerElements = liElements.item(0).all('.rn_ElementsHeader');
                Y.Assert.areSame(2, headerElements.size());
                Y.Assert.areSame('something:', headerElements.item(0).get('innerHTML'));
                Y.Assert.areSame('', headerElements.item(1).get('innerHTML'));
                headerElements = liElements.item(1).all('.rn_ElementsHeader');
                Y.Assert.areSame(2, headerElements.size());
                Y.Assert.areSame('something:', headerElements.item(0).get('innerHTML'));
                Y.Assert.areSame('', headerElements.item(1).get('innerHTML'));

                itemElements = liElements.item(0).all('.rn_ElementsData');
                Y.Assert.areSame(2, itemElements.size());
                Y.Assert.areSame('', itemElements.item(0).get('innerHTML'));
                Y.Assert.areSame('1-5', itemElements.item(1).get('innerHTML'));
                itemElements = liElements.item(1).all('.rn_ElementsData');
                Y.Assert.areSame(2, itemElements.size());
                Y.Assert.areSame('2-4', itemElements.item(0).get('innerHTML'));
                Y.Assert.areSame('', itemElements.item(1).get('innerHTML'));
            }
        }
    }));

    return tests;
});
UnitTest.run();
