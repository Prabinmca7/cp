UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'ModerationStatusFilter_0'
}, function(Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/moderation/ModerationStatusFilter",
        setUp: function() {
            var testExtender = {
                initValues: function() {
                    this.instanceID = 'ModerationStatusFilter_0';
                    this.reportID = widget.data.attrs.report_id;
                    this.defaultValue = 0;
                    widget.data.js.filter_id = 1;
                    widget.data.js.oper_id = 10;
                    widget.data.attrs.report_id = this.reportID;
                    widget.data.attrs.report_filter_name = "questions.status";
                    widget._setFilter();
                    this.defaultEventData = {fltr_id: 1, oper_id: 10, report_id: this.reportID, rnSearchType: 'questions.status', searchName: 'questions.status', data: "29,30"};
                }
            };

            for (var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    suite.add(new Y.Test.Case({
        name: "Filter data sent in the search event corresponds to the selected checkbox",
        testSearchFilters: function() {
            this.initValues();
            var eo = new RightNow.Event.EventObject(widget, {filters: this.defaultEventData});
            widget.searchSource()
                    .on("search", function(type, args) {
                        Y.Assert.areSame("search", type);
                        args = args[0];
                        Y.Assert.isInstanceOf(RightNow.Event.EventObject, args);
                        Y.Assert.areSame(this.instanceID, args.w_id);
                        Y.Assert.isObject(args.filters);
                        Y.Assert.areSame("29,30", args.filters.data);
                        Y.Assert.areSame("questions.status", args.filters.rnSearchType);
                        Y.Assert.areSame("questions.status", args.filters.searchName);
                    }, this)
                    .on("send", function() {
                        return false;
                    }).fire("search", eo);
        },
        testChangeCheckbox: function() {
            this.initValues();

            Y.all("#rn_" + this.instanceID + " input").item(1).simulate('click');
            Y.Assert.areSame(widget._newFilterData, "29");
            Y.Assert.areSame(widget._eo.filters.oper_id, 10);
            Y.Assert.areSame(widget._eo.filters.fltr_id, 1);

            var eo = new RightNow.Event.EventObject(widget, {filters: this.defaultEventData});
            widget.searchSource()
                    .on("search", function(type, args) {
                        //Overrding the default for the testcases
                    }, this).fire("search", eo);
            Y.all("#rn_" + this.instanceID + " input").item(1).simulate('click');
            //test if selected filters are available in newFilterData
            Y.Assert.areSame(widget._newFilterData, "29,30");

            //test only initial filters are available in event object and recently selected filters (i.e 32) are removed after reset event
            widget.searchSource().fire("reset", eo);
            Y.Assert.areSame(widget._newFilterData, "29");
            Y.Assert.areSame(widget._eo.filters.data, "29");
        },
        testResetClearFilter: function() {
            Y.Assert.areSame(widget._eo.filters.data, "29");
            var eo = new RightNow.Event.EventObject(widget, {filters: {report_id: widget.data.attrs.report_id}, data: {name: 'questions.status'}});
            widget.searchSource().fire("reset", eo);
            Y.Assert.areSame(widget._eo.filters.data, "29,30,32");
        }
    }));

    return suite;
}).run();
