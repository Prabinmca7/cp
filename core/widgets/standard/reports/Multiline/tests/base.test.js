UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'Multiline_0',
}, function(Y, widget, baseSelector) {
    var multilineTests = new Y.Test.Suite({
        name: "standard/reports/Multiline",
    });

    multilineTests.add(new Y.Test.Case({
        name: "Event Handling and Operation",
        "A report with `row_num` should display as an ordered list": function() {
            var data = [["1-1","1-2","1-3"],["2-1","2-2","2-3"]];
            var headers = [{'heading': ""}, {'heading': ""}, {'heading':""},
                           {'heading': 'something'}, {'heading': 'something else'}, {'heading': 'more something else'}];
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

            this.verifyData(data.length, true);
        },

        "A report with `row_num=false` should display as an unordered list": function() {
            var data = [["1-1","1-2","1-3"],["2-1","2-2","2-3"]];
            var headers = [{'heading': ""}, {'heading': ""}, {'heading':""},
                           {'heading': 'something'}, {'heading': 'something else'}, {'heading': 'more something else'}];
            var eo = new RightNow.Event.EventObject(null, {
                data: {
                    "report_id": widget.data.attrs.report_id,
                    "start_num": 1,
                    "row_num": false,
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

            this.verifyData(data.length, false);
        },

        "No results in the response should be handled properly": function() {
            var data = [];
            var headers = [{'heading': ""}, {'heading': ""}, {'heading':""},
                           {'heading': 'something'}, {'heading': 'something else'}, {'heading': 'more something else'}];
            var eo = new RightNow.Event.EventObject(null, {
                data: {
                    "report_id": widget.data.attrs.report_id,
                    "start_num": 1,
                    "row_num": false,
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

            this.verifyData(0);
        },

        "The proper indicators are set when the report is loading": function() {
            widget.searchSource().on("send", function() { return false; });
            widget.searchSource().fire("search", new RightNow.Event.EventObject(this, {filters: {report_id: widget.data.attrs.report_id}}));

            Y.Assert.isTrue(Y.one(baseSelector + "_Loading").hasClass("rn_Loading"));
            Y.Assert.areSame('true', document.body.getAttribute('aria-busy'));

            widget.searchSource().fire('response', new RightNow.Event.EventObject(null, {
                data: {
                    "report_id": widget.data.attrs.report_id,
                    "start_num": 1,
                    "row_num": false,
                    "total_num": 0,
                    "per_page": 0,
                    "headers": [{'heading': ""}, {'heading': ""}, {'heading':""},
                           {'heading': 'something'}, {'heading': 'something else'}, {'heading': 'more something else'}],
                    "data": []
                  },
                filters:   {
                    "report_id": widget.data.attrs.report_id,
                    "allFilters": widget.data.js.filters,
                    "format": widget.data.js.format
               }
            }));

            Y.Assert.isFalse(Y.one(baseSelector + "_Loading").hasClass("rn_Loading"));
            Y.Assert.areSame('false', document.body.getAttribute('aria-busy'));
        },

        "A report error should display a warning dialog": function() {
            var data = [],
                error = "Value 'abc'must be an integer.",
                eo = new RightNow.Event.EventObject(null, {
                data: {
                    report_id: widget.data.attrs.report_id,
                    start_num: 1,
                    row_num: false,
                    total_num: data.length,
                    per_page: data.length,
                    headers: [],
                    data: data,
                    error: error
                  },
                filters:   {
                    report_id: widget.data.attrs.report_id,
                    allFilters: widget.data.js.filters,
                    format: widget.data.js.format
               }
            });

            widget.searchSource().fire("response", eo);
            Y.Assert.areSame(error, Y.one('#rn_Dialog_1_Message').get('innerHTML'));
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

            this.verifyData(data.length, true);

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
        },

        verifyData: function(items, ordered) {
            var unordered;
            if (typeof ordered === "undefined") {
                ordered = unordered = 0;
            }
            else {
                ordered = (ordered) ? 1 : 0;
                unordered = (ordered) ? 0 : 1;
            }
            Y.Assert.isFalse(Y.one(baseSelector + "_Loading").hasClass("rn_Loading"));
            Y.Assert.areSame('false', document.body.getAttribute('aria-busy'));

            var content = Y.one(baseSelector + '_Content');
            Y.Assert.areSame(ordered, content.all('ol').size());
            Y.Assert.areSame(unordered, content.all('ul').size());
            Y.Assert.areSame(items, content.all('li').size());

            if (items === 0 && widget.data.attrs.hide_when_no_results) {
                Y.Assert.isTrue(Y.one(baseSelector).hasClass("rn_Hidden"));
            }
        }
    }));

    return multilineTests;
});

UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'Multiline_0',
}, function(Y, widget, baseSelector){
    var suite = new Y.Test.Suite("Additional tests: require creating a new instance of the widget");
    // Kill original instance.
    var instanceID = 'Multiline_0',
        instance = RightNow.Widgets.getWidgetInstance(instanceID),
        widgetData = instance.data;
    instance = null;

    suite.add(new Y.Test.Case({
        name: 'Event handling',
        "The widget only responds to report event": function() {
            widgetData.attrs.source_id = 'banana';
            instance = new RightNow.Widgets.Multiline(widgetData, instanceID, Y);

            instance.searchSource({banana: { endpoint: 'yeah' }}).on('send', function() { return false; }).fire('search', new RightNow.Event.EventObject());
            // Not loading.
            Y.Assert.isFalse(Y.one("#rn_" + instanceID + "_Loading").hasClass("rn_Loading"));
            Y.Assert.areSame('false', document.body.getAttribute('aria-busy'));
            // Not throwing an error for unexpected results.
            instance.searchSource('banana').fire('response', new RightNow.Event.EventObject());
        }
    }));

    return suite;
});
UnitTest.run();
