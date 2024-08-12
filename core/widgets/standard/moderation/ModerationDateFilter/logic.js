RightNow.Widgets.ModerationDateFilter = RightNow.SearchFilter.extend({
    overrides: {
        constructor: function() {
            this.parent();
            this._radioButtons = this.Y.all(this.baseSelector + " input[type=radio]");
            if (!this._radioButtons)
                return;
            this._radioButtons.on("click", this._onDateChange, this);
            this._eo = new RightNow.Event.EventObject(this);
            this._newFilterData = false;
            this.searchSource().on("search", this._onSearch, this);
            this.searchSource().on("reset", this._onReset, this);
            if(this._isCustomDateOptionEnabled()){
                this.calendarConfigs = {};
                this.calendarConfigs.fromDate = {
                    contentBox: this.baseSelector + '_EditedOnFromCal',
                    boundingBox: this.baseSelector + '_EditedOnFromBoundingBox',
                    dateInput: this.baseSelector + '_EditedOnFrom',
                    calIcon: this.baseSelector + '_EditedOnFromIcon'
                };
                this.calendarConfigs.toDate = {
                    contentBox: this.baseSelector + '_EditedOnToCal',
                    boundingBox: this.baseSelector + '_EditedOnToBoundingBox',
                    dateInput: this.baseSelector + '_EditedOnTo',
                    calIcon: this.baseSelector + '_EditedOnToIcon'
                };
                this.Y.Object.each(Object.keys(this.calendarConfigs), function(key) {
                    this._createCalendar(this.calendarConfigs[key]);
                }, this);
                RightNow.Event.fire("evt_moderationCustomDateFilterEnabled", new RightNow.Event.EventObject(this, {
                    data: {
                        report_id: this.data.attrs.report_id
                    }
                }));
                RightNow.Event.subscribe('evt_validateModerationDateFilter', this._validate, this);
            }
            this._setFilter();
        }
    },

    /**
     * Callback for the search event.
     */
    _onSearch: function() {
        if (this._isCustomDateSelected()) {
            this._newFilterData = this._getCustomDateInputsAsString();
        }
        this._eo.filters.data = (this._newFilterData === false) ? this._eo.filters.data : this._newFilterData;
        return this._eo;
    },

    /**
     * Callback for the reset event.
     */
    _onReset: function(type, args) {
        //If the 'reset' event is fired from ModerationFilterBreadCrumbs widget (by clicking remove filter icon), then set it to default filter
        if (args && args[0].data.name === this.data.attrs.report_filter_name) {
            this._eo.filters.data = this._getDefaultDate();
        }
        this._newFilterData = this._eo.filters.data; //Set the filters to initial selection if cancel or close buttons are clicked
        var customDateRange = (!this._isStandardDateOption(this._eo.filters.data) && this._eo.filters.data) ? this._eo.filters.data.split("|") : null;
        if (this._eo.filters.data) {
            this._setSelectedRadioButton(customDateRange ? customDateRange[0] : this._eo.filters.data,
                    customDateRange ? customDateRange[1] : null);
        }
    },

    /**
     * Sets the radio buttons to the one matching the passed-in value.
     * @param valueToSelect Value of date filter to be selected
     */
    _setSelectedRadioButton: function(fromDate, toDate) {
        toDate = toDate || '';
        if (!this._isStandardDateOption(fromDate)) {
            this.Y.one(this.calendarConfigs.fromDate.dateInput).set('value', fromDate);
            this.Y.one(this.calendarConfigs.toDate.dateInput).set('value', toDate);
            var selectedRadio = this.Y.one(this.baseSelector + "_DateFilter_custom");
            selectedRadio.set("checked", "true");
            this._toggleCustomDateRange(true);
        }
        else {
            var selectedDate = this.Y.one(this.baseSelector + "_DateFilter_" + fromDate);
            if (selectedDate) {
                selectedDate.set("checked", true);
                this._toggleCustomDateRange(false);
                this._setCustomDateInputs('', '');
            }
        }
    },

    /**
     * Enables the radio buttons on report reponse
     */
    _onChangedResponse: function() {
        this._radioBtns.set('disabled', false);
    },

    /**
     * Event handler executed when radio buttons change
     */
    _onDateChange: function() {
        this._setSelectedFilters();
    },

    /**
     * Sets the event object values based on the current selection
     */
    _setSelectedFilters: function() {
        if (this._isCustomDateSelected()) {
            this._toggleCustomDateRange(true);
        }
        else {
            var selectedRadio = this.Y.one(this.baseSelector + " input[type='radio']:checked");
            this._setCustomDateInputs('', '');
            this._newFilterData = selectedRadio ? selectedRadio.get('value') : false;
            this._toggleCustomDateRange(false);
        }
    },

    /**
     * Initializes the event object with default data
     */
    _setFilter: function() {
        var urlDateValue = this.data.js.urlDateValue || this._getDefaultDate();
        var dateRange = urlDateValue.split("|");
        this._setSelectedRadioButton(dateRange[0], dateRange[1]);
        this._eo.filters = {
            rnSearchType: this.data.attrs.report_filter_name,
            report_id: this.data.attrs.report_id,
            searchName: this.data.attrs.report_filter_name,
            oper_id: this.data.js.oper_id,
            fltr_id: this.data.js.filter_id,
            data: urlDateValue
        };
    },

    /**
     * Creates a new filter object and returns
     *
     * @param {String} reportFilterName Name of the filter
     * @param {String} value Value of the filter
     * @return {Object} filter object to be passed for search
     *
     */
    _newDateFilter: function(reportFilterName, value) {
        return {
            rnSearchType: reportFilterName,
            report_id: this.data.attrs.report_id,
            searchName: reportFilterName,
            oper_id: this.data.js[reportFilterName].oper_id,
            fltr_id: this.data.js[reportFilterName].filter_id,
            data: value
        };
    },

    /**
     * Creates a YUI calendar instance based on the config
     * @param {Object} calendarConfig configurations of the calendar instance
     * @return {Object} YUI calendar instance
     *
     */
    _createCalendar: function(calendarConfig) {
        var now = new Date();
        var calendar = new this.Y.Calendar({
            contentBox: calendarConfig.contentBox,
            showPrevMonth: true,
            showNextMonth: true,
            boundingBox: calendarConfig.boundingBox,
            maximumDate: now,
            minimumDate: this._getMinimumDate(),
            date: now
        }).render().hide();
        var dtdate = this.Y.DataType.Date;

        calendar.on("dateClick", function(ev) {
            if (ev.date > now) {
                return null;
            }
            this.Y.one(calendarConfig.dateInput).set('value', dtdate.format(ev.date, {
                format: this._getDateFormat()
            }));
            if (ev.cell.get('aria-disabled') !== 'true') {
                calendar.hide();
            }
        }, this);

        calendar.on("selectionChange", function(ev) {
            var newDate = ev.newSelection[0];
            this.Y.one(calendarConfig.dateInput).set('value', dtdate.format(newDate, {
                format: this._getDateFormat()
            }));
            this._newFilterData = this._getCustomDateInputsAsString();
            calendar.hide();
        }, this);

        this.Y.one(calendarConfig.calIcon).on('click', function(ev) {
            ev.preventDefault();
            var dateInput = this.Y.one(calendarConfig.dateInput).get('value');
            var dateValue = this._parseDate(dateInput) || now;
            calendar.deselectDates();
            calendar.set("date", dateValue);
            calendar.selectDates(dateValue);
            if (dateValue === now){
                this.Y.one(calendarConfig.dateInput).set('value', null);
            }
            if (this.Y.one(calendarConfig.boundingBox).hasClass("yui3-calendar-hidden")) {
                calendar.show();
                calendar.focus();
            }
            else {
                calendar.hide();
            }
        }, this);

        calendar.after('focusedChange', function(ev) {
            if (!ev.target.get('focused')) {
                calendar.hide();
                this.Y.one(calendarConfig.calIcon).focus();
            }
        }, this);

        return calendar;
    },

    /**
     * Toggles the custom date range options
     * @param {boolean} show True or False
     */
    _toggleCustomDateRange: function(show) {
        if(!this._isCustomDateOptionEnabled()){
            return;
        }
        var dateRangeDiv = this.Y.one(this.baseSelector + " .rn_DateRangeContainer");
        dateRangeDiv.removeClass("rn_Hidden");
        dateRangeDiv.removeClass("rn_Show");
        dateRangeDiv.toggleClass(show ? "rn_Show" : "rn_Hidden");
    },

    /**
     * Sets the To and From parametersto the respective textboxes
     * @param {String} fromDate From Date
     * @param {String} toDate To Date
     */
    _setCustomDateInputs: function(fromDate, toDate) {
        if(!this._isCustomDateOptionEnabled()){
            return;
        }
        this.Y.one(this.calendarConfigs.fromDate.dateInput).set('value', fromDate);
        this.Y.one(this.calendarConfigs.toDate.dateInput).set('value', toDate);
    },

    /**
     * Checks the value passed is one of the standard date options
     * @param {String} value Date option value
     * @return {Boolean} true or false
     *
     */
    _isStandardDateOption: function(value) {
        return value !== 'custom' && this.data.js.options[value] !== undefined;
    },

    /**
     * Creates the object of From and To date values
     * @return {Object} Object with string of from and to date values
     */
    _getCustomDateInputs: function() {
        return {
            from: this.Y.one(this.calendarConfigs.fromDate.dateInput).get('value'),
            to: this.Y.one(this.calendarConfigs.toDate.dateInput).get('value')
        };
    },

    /**
     * Returns the date range seperated by delimitter
     * @param {String} delimitter
     * @return {String} Date range seperated by delimitter
     */
    _getCustomDateInputsAsString: function(delimitter) {
        delimitter = delimitter || "|";
        var dates = this._getCustomDateInputs();
        return (dates.from.replace(/-/g,'\/') || "") + delimitter + (dates.to.replace(/-/g,'\/') || "");
    },

    /**
     * Converts the date format attribute value to the YUI DateType format
     * @return {String} YUI date type format
     */
    _getDateFormat: function() {
        return ("%" + this.data.js.date_format.short).replace(/-/g, "-%")
                .replace(/\//g, "/%");
    },

    /**
     * Checks whether the custom date option is selected
     * @return {Boolean} True or False
     */
    _isCustomDateSelected: function() {
        var selectedRadio = this.Y.one(this.baseSelector + " input[type='radio']:checked");
        return selectedRadio.get('value') === 'custom';
    },

    /**
     * Parses and returns a date if the date string is valid else null
     *
     * @param {String} dateVal Date value
     * @return {Date} Parsed Date or null
     */
    _parseDate: function(dateVal) {
        if(dateVal.trim() === ""){
            return null;
        }
        var dateArray = dateVal.split(/[\/-]/);
        dateArray = this.Y.Array.map(dateArray, function(datePart){
            return parseInt(datePart, 10);
        });
        var dateObj = new Date(dateArray[this.data.js.date_format.yearOrder],
            dateArray[this.data.js.date_format.monthOrder] - 1, dateArray[this.data.js.date_format.dayOrder]);
        var isValidDate = ((dateObj.getMonth() + 1 === dateArray[this.data.js.date_format.monthOrder])
                && (dateObj.getDate() === dateArray[this.data.js.date_format.dayOrder])
                && (dateObj.getFullYear() === dateArray[this.data.js.date_format.yearOrder]));
        return isValidDate ? dateObj : null;
    },

    /**
     * Handler for the event "evt_validateModerationDateFilter". Validates the
     * date inputs and fires "evt_moderationDateFilterValidated" event
     * @param {String} evt Name of the source event
     * @param {Object} args Data of the source event
     *
     */
    _validate: function(evt, args) {
        if (parseInt(this.data.attrs.report_id, 10) !== args[0].data.report_id) {
            return;
        }
        errors = [];
        if (this._isCustomDateSelected()) {
            var dtdate = this.Y.DataType.Date;
            var dates = this._getCustomDateInputs();
            var fromDate = this._parseDate(dates.from);
            var toDate = this._parseDate(dates.to);
            var fromDateInputID = this.calendarConfigs.fromDate.dateInput.replace("#", "");
            var toDateInputID = this.calendarConfigs.toDate.dateInput.replace("#", "");
            var errorMsgArg = {
                                'fromDate': dates.from !== null && dates.from.length ? dates.from : this.data.attrs.label_from_date,
                                'toDate': dates.to !== null && dates.to.length ? dates.to : this.data.attrs.label_to_date
                              };

            if (!fromDate) {
                errors.push(this._getErrorMessage(fromDateInputID, this.data.attrs.label_invalid_from_date_error, errorMsgArg.fromDate));
            }
            if (!toDate) {
                errors.push(this._getErrorMessage(toDateInputID, this.data.attrs.label_invalid_to_date_error, errorMsgArg.toDate));
            }
            if (fromDate && toDate) {
                var today = new Date();
                var minDate = this._getMinimumDate();
                var minDateStr = dtdate.format(minDate, { format: this._getDateFormat()} );
                if (dtdate.isGreater(fromDate, today)) {
                    errors.push(this._getErrorMessage(fromDateInputID, this.data.attrs.label_future_from_date_error));
                }
                if (dtdate.isGreater(toDate, today)) {
                    errors.push(this._getErrorMessage(toDateInputID, this.data.attrs.label_future_to_date_error));
                }

                if (dtdate.isGreater(minDate, fromDate)) {
                    errors.push(this._getErrorMessage(fromDateInputID, this.data.attrs.label_min_from_date_error, minDateStr));
                }
                if (dtdate.isGreater(minDate, toDate)) {
                    errors.push(this._getErrorMessage(toDateInputID, this.data.attrs.label_min_to_date_error, minDateStr));
                }

                if (dtdate.isGreater(fromDate, toDate)) {
                    errors.push(this._getErrorMessage(fromDateInputID, this.data.attrs.label_invalid_date_range_error));
                }

                var intervalParts = this.data.attrs.max_date_range_interval.split(" ");
                if(intervalParts[0] && intervalParts[1]){
                    var unit = intervalParts[1];

                    if(unit === "hours" || unit === "hour"){
                        unit = "days";
                        intervalParts[0] = intervalParts[0] / 24;
                    }

                    unit  = (unit.slice(-1) === 's') ? unit : (unit + 's');

                    unit = unit.charAt(0).toUpperCase() + unit.slice(1);

                    var maxToDate = dtdate["add" + unit](fromDate, parseInt(intervalParts[0], 10));
                    if (!dtdate.isGreater(fromDate, toDate) && !dtdate.isInRange(toDate, fromDate, maxToDate)) {
                        errors.push(this._getErrorMessage(fromDateInputID, this.data.attrs.label_max_date_range_error, this.data.js.max_data_range_label));
                    }
                }
            }
        }
        RightNow.Event.fire("evt_moderationDateFilterValidated", new RightNow.Event.EventObject(this, {
            data: {
                report_id: this.data.attrs.report_id,
                errors: errors.length ? errors : null
            }
        }));
    },

    /**
     * Builds the error message HTML for a specific error
     * @param {String} elementID Id of DOM element with error
     * @param {String} msg Error message
     * @param {String} msgArg Error meesage palceholder value
     * @return {Object} YUI Node Element
     */
    _getErrorMessage: function(elementID, msg, msgArg)
    {
        return this.Y.Node.create(new EJS({
            text: this.getStatic().templates.errorMessage
        }).render({
            error: {
                id: elementID,
                msg: msgArg ? msg.replace("%s", msgArg) : msg
            }
        }));
    },

    /**
     * The default standard date option
     * @return {String} default date option
     */
    _getDefaultDate: function() {
        return this.data.js.default_value || 'last_90_days';
    },

    /**
     * Returns the minimum date
     * @return {Date} minimum date
     */
    _getMinimumDate: function() {
        return new Date(0);
    },

    /**
     * Checks the custom date options is enabled or not
     * @return {Boolean} true or false
     *
     */
    _isCustomDateOptionEnabled: function() {
        return this.data.js.options.custom !== undefined;
    }
});
