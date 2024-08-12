UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'ModerationDateFilter_0'
}, function(Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/moderation/ModerationDateFilter",
        setUp: function() {
            var testExtender = {
                initValues: function(defaultDateOption) {
                    this.instanceID = 'ModerationDateFilter_0';
                    this.reportID = widget.data.attrs.report_id;
                    this.defaultValue = 0;
                    widget.data.js.filter_id = 3;
                    widget.data.js.oper_id = 6;
                    widget.data.attrs.report_id = this.reportID;
                    widget.data.attrs.report_filter_name = "questions.updated";
                    widget.data.attrs.selected_date = defaultDateOption || 'last_90_days';
                    widget._setFilter();
                    this.defaultEventData = {fltr_id: 3, oper_id: 6, report_id: this.reportID, rnSearchType: 'questions.updated', searchName: 'questions.updated', data: "all"};
                }
            };

            for (var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    suite.add(new Y.Test.Case({
        name: "Filter data sent in the search event corresponds to the selected radio button",
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
                        Y.Assert.areSame("questions.updated", args.filters.rnSearchType);
                        Y.Assert.areSame("questions.updated", args.filters.searchName);
                    }, this)
                    .on("send", function() {
                        return false;
                    }).fire("search", eo);
        },
        testSearchFiltersWithInvalidValue: function() {
            this.initValues("invalid");
            Y.Assert.isTrue(Y.one("#rn_" + this.instanceID + "_DateFilter_last_90_days").get('checked'));
        },
        testChangeRadioBtn: function() {
            this.initValues();
            Y.all("#rn_" + this.instanceID + " input").item(0).simulate('click');
            Y.Assert.areSame(widget._eo.filters.data, 'last_90_days');
            Y.Assert.areSame(widget._eo.filters.oper_id, 6);
            Y.Assert.areSame(widget._eo.filters.fltr_id, 3);

            var eo = new RightNow.Event.EventObject(widget, {filters: this.defaultEventData});
            widget.searchSource()
                    .on("search", function(type, args) {
                        //Overrding the default for the testcases
                    }, this).fire("search", eo);
            Y.all("#rn_" + this.instanceID + " input").item(0).simulate('click');
            //test if selected filters are available in newFilterData
            Y.Assert.areSame(widget._newFilterData, 'last_24_hours');

            //test only initial filters are available in event object and recently selected filters (i.e 1) are removed after reset event
            widget.searchSource().fire("reset", eo);
            Y.Assert.areSame(widget._newFilterData, 'last_24_hours');
            Y.Assert.areSame(widget._eo.filters.data, 'last_24_hours');
        },
        testResetClearFilter: function() {
            Y.Assert.areSame(widget._eo.filters.data, "last_24_hours");
            var eo = new RightNow.Event.EventObject(widget, {filters: {report_id: widget.data.attrs.report_id}, data: {name: 'questions.updated'}});
            widget.searchSource().fire("reset", eo);
            Y.Assert.areSame(widget._eo.filters.data, "last_90_days");
        },
        testValidCustomDateRange: function() {
            this.initValues();
            Y.all("#rn_" + this.instanceID + " input").item(4).simulate('click');

            Y.all("#rn_" + this.instanceID + "_EditedOnFromCal td[aria-disabled=false].yui3-calendar-day").item(0).simulate('click');
            Y.all("#rn_" + this.instanceID + "_EditedOnToCal td[aria-disabled=false].yui3-calendar-day").item(0).simulate('click');
            var fromInput = Y.all(".rn_DateInput").item(0).get("value");
            var toInput = Y.all(".rn_DateInput").item(1).get("value");
            Y.Assert.isNotNull(fromInput);
            Y.Assert.isNotNull(toInput);
            var errorCallback = function(evt,args){
                Y.Assert.isNull(args[0].data.errors);
            };
            RightNow.Event.subscribe('evt_moderationDateFilterValidated', errorCallback, widget);
            RightNow.Event.fire("evt_validateModerationDateFilter", new RightNow.Event.EventObject(widget, {data: {
                report_id: widget.data.attrs.report_id  }}) );
            Y.Assert.areSame(widget._newFilterData,fromInput+"|"+toInput);
            RightNow.Event.unsubscribe('evt_moderationDateFilterValidated', errorCallback, widget);
        },
        testInvalidCustomDateRange: function() {
            this.initValues();
            Y.all("#rn_" + this.instanceID + " input").item(4).simulate('click');

            // provide date-range outside today's date
            Y.all("#rn_" + this.instanceID + "_EditedOnFromCal tr:last-child td[aria-disabled=false]:last-child").item(0).simulate('click');
            Y.all("#rn_" + this.instanceID + "_EditedOnToCal tr:last-child td[aria-disabled=false]:last-child").item(0).simulate('click');

            var fromInput = Y.all(".rn_DateInput").item(0).get("value");
            var toInput = Y.all(".rn_DateInput").item(1).get("value");
            Y.Assert.isTrue(fromInput === '');
            Y.Assert.isTrue(toInput === '');

            // set value to invalid
            Y.all(".rn_DateInput").item(0).set("value","invalid");
            Y.all(".rn_DateInput").item(1).set("value","invalid");
            var errorCallback = function(evt,args){
                Y.Assert.isNotNull(args[0].data.errors);
            };
            RightNow.Event.subscribe('evt_moderationDateFilterValidated', errorCallback, widget);
            RightNow.Event.fire("evt_validateModerationDateFilter", new RightNow.Event.EventObject(widget, {data: {
                report_id: widget.data.attrs.report_id  }}) );
            RightNow.Event.unsubscribe('evt_moderationDateFilterValidated', errorCallback, widget);
        },
         testDefaultDateSelection: function() {
            var d = new Date();
            this.initValues();
            Y.all("#rn_" + this.instanceID + " input").item(4).simulate('click');
            Y.all("#rn_" + this.instanceID + "_EditedOnFromIcon").item(0).simulate('click');
            var dateVal = Y.all("#rn_" + this.instanceID + "_EditedOnFromCal td[aria-selected=true]").item(0).get('innerHTML');
            Y.Assert.areEqual(dateVal,d.getDate());

            var fromInput = Y.all("#rn_" + this.instanceID + "_EditedOnFrom").item(0).get("value");
            Y.Assert.areEqual(fromInput,'');

            Y.all("#rn_" + this.instanceID + "_EditedOnFromCal td[aria-disabled=false]").item(0).simulate('click');
            var fromInputVal = Y.all(".rn_DateInput").item(0).get("value");
            var fromInputField = Y.all("#rn_" + this.instanceID + "_EditedOnFrom").item(0).get("value");
            Y.Assert.areEqual(fromInputVal,fromInputField);
        },
        testInValidDate: function() {
            this.isInvalid("02/31/2014","04/30/2014");
            this.isInvalid("02/28/1969","04/30/1970");
        },
        isInvalid: function(invalidFromDate, invalidToDate) {
            this.initValues();
            Y.all("#rn_" + this.instanceID + " input").item(4).simulate('click');
            Y.all(".rn_DateInput").item(0).set("value",invalidFromDate);
            Y.all(".rn_DateInput").item(1).set("value",invalidToDate);
            var errorCallback = function(evt,args){
                Y.Assert.isNotNull(args[0].data.errors);
            };
            RightNow.Event.subscribe('evt_moderationDateFilterValidated', errorCallback, widget);
            RightNow.Event.fire("evt_validateModerationDateFilter", new RightNow.Event.EventObject(widget, {data: {
                report_id: widget.data.attrs.report_id  }}) );
            RightNow.Event.unsubscribe('evt_moderationDateFilterValidated', errorCallback, widget);
        },
        testValidDate: function() {
            this.isValid("02/27/2014","04/30/2014");
            this.isValid("02-28-2014","04-30-2014");
        },
        testValidDateWithYMDFormat: function() {
            widget.data.js.date_format.yearOrder = 0;
            widget.data.js.date_format.monthOrder = 1;
            widget.data.js.date_format.dayOrder = 2;
            this.isValid("2014/02/27","2014/04/30");
            this.isValid("2014-02-28","2014-04-30");
            this.isValid("2014/02/27","2014/04/30");
            widget.data.js.date_format.yearOrder = 2;
            widget.data.js.date_format.monthOrder = 0;
            widget.data.js.date_format.dayOrder = 1;
        },
        isValid: function(validFromDate, validToDate) {
            this.initValues();
            Y.all("#rn_" + this.instanceID + " input").item(4).simulate('click');
            Y.all(".rn_DateInput").item(0).set("value",validFromDate);
            Y.all(".rn_DateInput").item(1).set("value",validToDate);
            var errorCallback = function(evt,args){
                Y.Assert.isNull(args[0].data.errors);
            };
            RightNow.Event.subscribe('evt_moderationDateFilterValidated', errorCallback, widget);
            RightNow.Event.fire("evt_validateModerationDateFilter", new RightNow.Event.EventObject(widget, {data: {
                report_id: widget.data.attrs.report_id  }}) );
            RightNow.Event.unsubscribe('evt_moderationDateFilterValidated', errorCallback, widget);
        },
        testIfMaxDateRangeIntervalSucceeds: function() {
            widget.data.attrs.max_date_range_interval = "1 year";
            this.initValues();
            Y.all("#rn_" + this.instanceID + " input").item(4).simulate('click');
            Y.all(".rn_DateInput").item(0).set("value","03/27/2015");
            Y.all(".rn_DateInput").item(1).set("value","02/27/2016");
            var errorCallback = function(evt,args){
                Y.Assert.isNull(args[0].data.errors);
            };
            RightNow.Event.subscribe('evt_moderationDateFilterValidated', errorCallback, widget);
            RightNow.Event.fire("evt_validateModerationDateFilter", new RightNow.Event.EventObject(widget, {data: {
                report_id: widget.data.attrs.report_id  }}) );
            RightNow.Event.unsubscribe('evt_moderationDateFilterValidated', errorCallback, widget);
        },
        testIfMaxDateRangeIntervalFails: function() {
            widget.data.attrs.max_date_range_interval = "1 year";
            this.initValues();
            Y.all("#rn_" + this.instanceID + " input").item(4).simulate('click');
            Y.all(".rn_DateInput").item(0).set("value","01/27/2015");
            Y.all(".rn_DateInput").item(1).set("value","02/27/2016");
            var errorCallback = function(evt,args){
                Y.Assert.isNotNull(args[0].data.errors);
            };
            RightNow.Event.subscribe('evt_moderationDateFilterValidated', errorCallback, widget);
            RightNow.Event.fire("evt_validateModerationDateFilter", new RightNow.Event.EventObject(widget, {data: {
                report_id: widget.data.attrs.report_id  }}) );
            RightNow.Event.unsubscribe('evt_moderationDateFilterValidated', errorCallback, widget);
        }
    }));

    return suite;
}).run();
