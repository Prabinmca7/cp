RightNow.Widgets.SearchTypeList = RightNow.SearchFilter.extend({
    overrides: {
        constructor: function() {
            this.parent();
            
            this._selectBox = this.Y.one(this.baseSelector + "_Options");
            if(!this._selectBox)
                return;
            this._eo = new RightNow.Event.EventObject(this);

            this._selectBox.on("change", this._onSelectChange, this);
            this.searchSource().on("searchTypeChanged", this._onChangedResponse, this) // keep all instances in sync
                               .on("search", function(){return this._eo;}, this)
                               .on("reset", this._onResetRequest, this);
            this.searchSource(this.data.attrs.report_id).on("response", this._onChangedResponse, this);
            this._setFilter();
            this._setSelectedDropdownItem(this.data.js.defaultFilter);
        }
    },

    /**
    * Event handler executed when the select box is changed
    */
    _onSelectChange: function()
    {
        this._setSelected();
        this.searchSource().fire("searchTypeChanged", this._eo);
        if (this.data.attrs.search_on_select)
        {
            this._eo.filters.reportPage = this.data.attrs.report_page_url;
            this.searchSource().fire("search", this._eo);
        }
    },
    
    /**
    * Sets the selected dropdown item to the one matching the passed-in value.
    * @param valueToSelect Int Value of item to select
    * @return Boolean Whether or not the operation was successful
    */
    _setSelectedDropdownItem: function(valueToSelect)
    {
        this._selectBox.get("options").each(function(option, i) {
                if (parseInt(option.get("value"), 10) === valueToSelect) {
                    this._selectBox.set("selectedIndex", i);
                    return;
                }
            }, this);
    },

    /**
    * Sets the event object values from the select box values
    */
    _setSelected: function()
    {
        var selectedOption = this._selectBox.get('options').item(this._selectBox.get('selectedIndex')),
            value = parseInt(selectedOption.get('value'), 10),
            label = selectedOption.get('text'),
            node;
        for(node in this.data.js.filters)
        {
            if (this.data.js.filters[node].fltr_id === value)
            {
                this._setSelectedFilter(this.data.js.filters[node], label);
            }
        }
    },

    /**
    * Sets the event object values to the selected values
    */
    _setSelectedFilter: function(selected, label)
    {
        this._eo.filters.fltr_id = selected.fltr_id;
        this._eo.filters.data = {"val": selected.fltr_id};
        this._eo.filters.data.label = label;
        this._eo.filters.oper_id = selected.oper_id;
    },

    /**
    * Sets the initial event object values
    */
    _setFilter: function()
    {
        this._eo.filters = {"rnSearchType": this.data.js.rnSearchType,
                            "searchName": this.data.js.searchName,
                            "report_id": this.data.attrs.report_id};
        for (var node in this.data.js.filters)
        {
            if (this.data.js.filters[node].fltr_id === this.data.js.defaultFilter)
            {
                this._setSelectedFilter(this.data.js.filters[node], this.data.js.filters[node].prompt);
                break;
            }
        }
    },

    /**
    * Responds to the filterReset event by setting the internal eventObject's data back to default
    * @param type String Event name
    * @param args Object Event object
    */
    _onResetRequest: function(type, args)
    {
        if(RightNow.Event.isSameReportID(args, this.data.attrs.report_id))
        {
            if(args[0].data.name === this.data.js.searchName) {
                this._setSelectedDropdownItem(this.data.js.resetFilter);
                this._setSelected();
            }
            else if(args[0].data.name === 'all') {
                this._setSelectedDropdownItem(this.data.js.defaultFilter);
                this._setSelected();
            }
        }
    },

    /**
     * Event handler executed whent the search type data is changed
     *
     * @param type String Event type
     * @param args Object Arguments passed with event
     */
    _onChangedResponse: function(type, args)
    {
        if (RightNow.Event.isSameReportID(args, this.data.attrs.report_id))
        {
            var data = RightNow.Event.getDataFromFiltersEventResponse(args, this.data.js.searchName, this.data.attrs.report_id);
            this._setSelectedDropdownItem(((data && data.val) ? data.val : this.data.js.defaultFilter));
            this._setSelected();
        }
    }
});
