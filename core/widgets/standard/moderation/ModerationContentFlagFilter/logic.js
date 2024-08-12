RightNow.Widgets.ModerationContentFlagFilter = RightNow.SearchFilter.extend({
    overrides: {
        constructor: function() {
            this.parent();
            this._checkBoxes = this.Y.all(this.baseSelector + " input[type=checkbox]");
            if (!this._checkBoxes)
                return;
            this._checkBoxes.on("click", this._onFlagChange, this);
            this._eo = new RightNow.Event.EventObject(this);
            this._newFilterData = false;
            this.searchSource().on("search", this._onSearch, this);
            this.searchSource().on("reset", this._onReset, this);
            this._setFilter();
        }
    },

    /**
     * Callback for the search event.
     */
    _onSearch: function() {
        this._eo.filters.data = (this._newFilterData === false) ? this._eo.filters.data : this._newFilterData;
        return this._eo;
    },

    /**
     * Callback for the reset event.
     */
    _onReset: function(type, args) {
        //If the 'reset' event is fired from ModerationFilterBreadCrumbs widget (by clicking remove filter icon), then set it to default filter.
        if(args && args[0].data.name === this.data.attrs.report_filter_name) {
            this._eo.filters.data = this.data.js.selected_flags ? this.data.js.selected_flags.join(",") : '';
        }
        this._newFilterData = this._eo.filters.data;
        this._setSelectedCheckboxes(this._eo.filters.data.split(","));
    },

    /**
     * Sets the checkboxes to the one matching the passed-in value.
     * @param {Array} flags Array of flag type ids to be selected
     */
    _setSelectedCheckboxes: function(flags) {
        var flagsToBeSelected = this.Y.Array(flags);
        this._checkBoxes.each(function(checkbox) {
            checkbox.set("checked", (this.Y.Array.indexOf(flagsToBeSelected, checkbox.get('value')) !== -1));
        }, this);
    },

    /**
     * Enables the checkboxes on report reponse
     */
    _onChangedResponse: function() {
        this._checkBoxes.set('disabled', false);
    },

    /**
     * Event handler executed when checkboxes change
     */
    _onFlagChange: function() {
        this._setSelectedFilters();
    },

    /**
     * Sets the event object values based on the current selection
     */
    _setSelectedFilters: function() {
        var selectedCheckboxes = this.Y.all(this.baseSelector + " input[type='checkbox']");
        var selectedIDs = [];
        selectedCheckboxes.each(function(checkbox) {
            if (checkbox.get('checked')) {
                selectedIDs.push(checkbox.get("value"));
            }
        });
        this._newFilterData = selectedIDs.join(",");
    },

    /**
     * Initializes the event object with default data
     */
    _setFilter: function() {
        var urlFlagValue = RightNow.Url.getParameter(this.data.attrs.report_filter_name);
        var defaultFilters = urlFlagValue
                || (this.data.js.selected_flags ? this.data.js.selected_flags.join(",") : '') || '';
        this._setSelectedCheckboxes(defaultFilters.split(","));
        this._eo.filters = {rnSearchType: this.data.attrs.report_filter_name,
            report_id: this.data.attrs.report_id,
            searchName: this.data.attrs.report_filter_name,
            oper_id: this.data.js.oper_id,
            fltr_id: this.data.js.filter_id,
            data: defaultFilters
        };
    }

});