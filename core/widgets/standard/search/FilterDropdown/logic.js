RightNow.Widgets.FilterDropdown = RightNow.SearchFilter.extend({
    overrides: {
        constructor: function() {
            this.parent();

            this._eo = new RightNow.Event.EventObject(this);
            this._selectBox = this.Y.one(this.baseSelector + "_Options");

            this._selectBox.on("change", this._onSelectChange, this);
            this.searchSource().on("search", function(){return this._eo;}, this)
                               .on("reset", this._onResetRequest, this);
            this.searchSource(this.data.attrs.report_id).on("response", this._onChangedResponse, this);
            this._setSelectedDropdownItem(this.data.js.defaultValue);
            this._setFilter();
            this.lastValue = this._eo.filters.data.val;
        }
    },

    /**
     * Event handler executed when the drop down is changed
     */
    _onSelectChange: function()
    {
        this.lastValue = this._eo.filters.data.val;
        this._eo.filters.data.val = this._getSelected();
        if (this.data.attrs.search_on_select)
        {
            this._eo.filters.reportPage = this.data.attrs.report_page_url;
            this.searchSource().fire("search", this._eo);
        }
    },

    /**
     * internal function to set this.data from the select box into the event object
     */
    _getSelected: function()
    {
        return this._selectBox.get('options').item(this._selectBox.get('selectedIndex')).get('value');
    },
    
    /**
     * Sets the selected dropdown item to the one matching the passed-in value.
     * @param valueToSelect Int Value of item to select
     * @return Boolean Whether or not the operation was successful
     */
    _setSelectedDropdownItem: function(valueToSelect)
    {
        this._selectBox.get("options").each(function(option, i) {
                if (option.get("value") === (valueToSelect + '')) {
                    this._selectBox.set("selectedIndex", i);
                    return;
                }
            }, this);
    },

    /**
     * sets the initial event object data
     *
     */
    _setFilter: function()
    {
        this._eo.filters = {"searchName":  this.data.js.searchName,
                            "rnSearchType":this.data.js.rnSearchType,
                            "report_id": this.data.attrs.report_id,
                            "data": {"fltr_id": this.data.js.filters.fltr_id,
                                     "oper_id": this.data.js.filters.oper_id,
                                     "val": this._getSelected()}
                           };
    },

    /**
     * Event handler executed when the custom menu data is changed
     *
     * @param type string Event type
     * @param args object Arguments passed with event
     */
    _onChangedResponse: function(type, args)
    {
        var data = RightNow.Event.getDataFromFiltersEventResponse(args, this.data.js.searchName, this.data.attrs.report_id),
            newValue = this._eo.filters.data.val,
            allFilters;
        
        //If there is a new value matching the searchName and report_id
        if(data && data.fltr_id === this.data.js.filters.fltr_id) {
            newValue = data.val || this.data.js.defaultValue;
        }
        //If it matches the report, but the filter is missing, it's going back to an initial value; reset the filter.
        else if(RightNow.Event.isSameReportID(args, this.data.attrs.report_id)) {
            allFilters = args[0].filters.allFilters[this.data.js.searchName];
            if(!allFilters) {
                newValue = this.data.js.defaultValue;
            }
        }

        if (newValue !== this._eo.filters.data.val) {
            this._setSelectedDropdownItem(newValue);
            this._eo.filters.data.val = this.lastValue = newValue;
        }
    },

    /**
     * Responds to the filterReset event by setting the internal eventObject's data back to default
     * triggered by closing the advancedSearchDialog (all) or removing the filter from the DisplaySearchFilters widget (searchName) 
     * @param type String Event name
     * @param args Object Event object
     */
    _onResetRequest: function(type, args)
    {   
        var resetValue = this._eo.filters.data.val;
        if(RightNow.Event.isSameReportID(args, this.data.attrs.report_id) && (args[0].data.name === this.data.js.searchName || args[0].data.name === "all"))
        {
            //DisplaySearchFilters called this widget out specifically, we want to reset it to default.
            if(args[0].data.name === this.data.js.searchName) {
                resetValue = this.lastValue = this.data.js.defaultValue;
            }
            //AdvancedSearchDialog reset all widgets, revert back to lastValue
            else if(args[0].data.name === 'all') {
                resetValue = this.lastValue || resetValue;
            }

            if(resetValue !== this._eo.filters.data.val) {
                this._setSelectedDropdownItem(resetValue);
                this._eo.filters.data.val = resetValue;
            }
        }
    }
});
