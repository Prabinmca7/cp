UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'MobileMultiline_0',
}, function(Y, widget, baseSelector){
    var mobileMultilineTests = new Y.Test.Suite({
        name: "standard/reports/MobileMultiline",

        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.instanceID = 'MobileMultiline_0';
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.widgetData = this.instance.data;
                },

                checkEventParameters: function(eventName, type, args)
                {
                    Y.Assert.areSame(eventName, type);
                    Y.Assert.isInstanceOf(RightNow.Event.EventObject, args);
                    Y.Assert.isObject(args.filters);
                    Y.Assert.areSame(this.instanceID, args.w_id);
                }
            };

            for(var item in this.items)
            {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    mobileMultilineTests.add(new Y.Test.Case({
        name: "Event Handling and Operation",
        testReportResponseOl: function() {
            this.initValues();

            var data = [["1-1","1-2","1-3"],
                        ["2-1","2-2","2-3"],
                        ["3-1","3-2","3-3"],
                        ["4-1","4-2","4-3"],
                        ["5-1","5-2","5-3"],
                        ["6-1","6-2","6-3"],
                        ["7-1","7-2","7-3"],
                        ["8-1","8-2","8-3"],
                        ["9-1","9-2","9-3"],
                        ["10-1","10-2","10-3"],
                        ["11-1","11-2","11-3"],
                        ["12-1","12-2","12-3"]];
            var headers = [{"heading": ""}, {"heading": ""}, {"heading": ""},
                           {"heading": "one"},{"heading": "two"},{"heading": "three"}];
            var eo = new RightNow.Event.EventObject(this, {
                filters: {
                    "report_id": this.widgetData.attrs.report_id,
                    "token": this.widgetData.js.r_tok,
                    "allFilters": this.widgetData.js.filters,
                    "format": this.widgetData.js.format
                 },
                 data: {
                     "report_id": this.widgetData.attrs.report_id,
                     "per_page": data.length,
                     "total_num": data.length,
                     "row_num": true,
                     "start_num": 1,
                     "data": data,
                     "headers": headers
                 }
            });

            this.instance.searchSource().fire("response", eo);

            this.verifyData(data.length, true);
        },

        testReportResponseUl: function() {
            this.initValues();

            var data = [["1-1","1-2","1-3"],
                        ["2-1","2-2","2-3"],
                        ["3-1","3-2","3-3"],
                        ["4-1","4-2","4-3"],
                        ["5-1","5-2","5-3"],
                        ["6-1","6-2","6-3"],
                        ["7-1","7-2","7-3"],
                        ["8-1","8-2","8-3"],
                        ["9-1","9-2","9-3"],
                        ["10-1","10-2","10-3"],
                        ["11-1","11-2","11-3"],
                        ["12-1","12-2","12-3"]];
            var headers = [{"heading": ""}, {"heading": ""}, {"heading": ""},
                           {"heading": "one"},{"heading": "two"},{"heading": "three"}];
            var eo = new RightNow.Event.EventObject(this, {
                filters: {
                    "report_id": this.widgetData.attrs.report_id,
                    "token": this.widgetData.js.r_tok,
                    "allFilters": this.widgetData.js.filters,
                    "format": this.widgetData.js.format
                 },
                 data: {
                     "report_id": this.widgetData.attrs.report_id,
                     "per_page": data.length,
                     "total_num": data.length,
                     "row_num": false,
                     "start_num": 1,
                     "data": data,
                     "headers": headers
                 }
            });

            this.instance.searchSource().fire("response", eo);

            this.verifyData(data.length, false);
        },

        testNoResults: function() {
            this.initValues();

            var data = [];
            var headers = [{'heading': ""}, {'heading': ""}, {'heading':""},
                           {'heading': 'something'}, {'heading': 'something else'}, {'heading': 'more something else'}];
            var eo = new RightNow.Event.EventObject(null, {
                data: {
                    "report_id": this.widgetData.attrs.report_id,
                    "start_num": 1,
                    "row_num": false,
                    "total_num": data.length,
                    "per_page": data.length,
                    "headers": headers,
                    "data": data
                  },
                filters:   {
                    "report_id": this.widgetData.attrs.report_id,
                    "allFilters": this.widgetData.js.filters,
                    "format": this.widgetData.js.format
               }
            });

            this.instance.searchSource().fire("response", eo);
            this.verifyData(0);
        },

        testSearchInProgress: function() {
            this.initValues();
            this.instance.searchSource().on("send", function() { return false; });
            this.instance.searchSource().fire("search", new RightNow.Event.EventObject(this, {filters: {report_id: this.widgetData.attrs.report_id}}));

            Y.Assert.isTrue(Y.one("#rn_" + this.instanceID + "_Loading").hasClass("rn_Loading"));
            Y.Assert.areSame('true', document.body.getAttribute('aria-busy'));
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
            Y.Assert.isFalse(Y.one("#rn_" + this.instanceID + "_Loading").hasClass("rn_Loading"));
            Y.Assert.areSame('false', document.body.getAttribute('aria-busy'));

            var content = Y.one('#rn_' + this.instanceID + '_Content');
            Y.Assert.areSame(ordered, content.all('ol').size());
            Y.Assert.areSame(unordered, content.all('ul').size());
            Y.Assert.areSame(items, content.all('li').size());

            if (items === 0 && this.widgetData.attrs.hide_when_no_results) {
                Y.Assert.isTrue(Y.one("#rn_" + this.instanceID).hasClass("rn_Hidden"));
            }
        }
    }));

    return mobileMultilineTests;
});
UnitTest.run();
