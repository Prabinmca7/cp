UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'ModerationContentFlagFilter_0'
}, function(Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/moderation/ModerationContentFlagFilter",
        setUp: function() {
            var testExtender = {
                initValues: function(defaultData) {
                    this.instanceID = 'ModerationContentFlagFilter_0';
                    this.reportID = widget.data.attrs.report_id;
                    this.defaultValue = 0;
                    widget.data.js.filter_id = 4;
                    widget.data.js.oper_id = 10;
                    widget.data.attrs.report_id = this.reportID;
                    widget.data.attrs.report_filter_name = "question_content_flags.flag";
                    widget._setFilter();
                    this.defaultEventData = defaultData || {fltr_id: 4, oper_id: 10, report_id: this.reportID, rnSearchType: 'question_content_flags.flag', searchName: 'question_content_flags.flag', data: "1,2"};
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
                        Y.Assert.areSame("question_content_flags.flag", args.filters.rnSearchType);
                        Y.Assert.areSame("question_content_flags.flag", args.filters.searchName);
                    }, this)
                    .on("send", function() {
                        return false;
                    }).fire("search", eo);
        },
        testChangeCheckbox: function() {
            this.initValues();
            Y.all("#rn_" + this.instanceID + " input").item(0).simulate('click');
            Y.Assert.areSame(widget._newFilterData, "1");
            Y.Assert.areSame(widget._eo.filters.oper_id, 10);
            Y.Assert.areSame(widget._eo.filters.fltr_id, 4);

            var eo = new RightNow.Event.EventObject(widget, {filters: this.defaultEventData});
            widget.searchSource()
                    .on("search", function(type, args) {
                        //Overrding the default for the testcases
                    }, this).fire("search", eo);
            Y.all("#rn_" + this.instanceID + " input").item(1).simulate('click');
            //test if selected filters are available in newFilterData
            Y.Assert.areSame(widget._newFilterData, "1,2");

            //test only initial filters are available in event object and recently selected filters (i.e 2) are removed after reset event
            widget.searchSource().fire("reset", eo);
            Y.Assert.areSame(widget._newFilterData, "1");
            Y.Assert.areSame(widget._eo.filters.data, "1");
        },
        testReset: function() {
            this.initValues();
            var eo = new RightNow.Event.EventObject(widget, {filters: this.defaultEventData});
            widget.searchSource().fire("reset", eo);
            Y.Assert.areSame(widget._newFilterData, "");
            Y.Assert.areSame(widget._eo.filters.data, "");
        },
        testSelectedFlagsOnLoad: function() {
            widget.data.js.selected_flags = [2, 1];
            this.initValues({fltr_id: 4, oper_id: 10, report_id: this.reportID, rnSearchType: 'question_content_flags.flag', searchName: 'question_content_flags.flag'});
            Y.Assert.areSame(widget._eo.filters.data, "2,1");
        }
    }));
    return suite;
}).run();
