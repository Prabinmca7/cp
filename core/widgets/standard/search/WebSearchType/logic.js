RightNow.Widgets.WebSearchType = RightNow.SearchFilter.extend({
    overrides: {
        constructor: function() {
            this.parent();
            
            this._optionsSelect = this.Y.one(this.baseSelector + "_Options");
            this._searchName = "webSearchType";
            if(!this._optionsSelect)
                return;
            this.searchSource().on("webSearchTypeChanged", this._onChangedResponse, this)
                                .on("search", function(){return this._eo;}, this)
                                .on("reset", this._onReset, this);
            this.searchSource(this.data.attrs.report_id).on("response", this._onChangedResponse, this);
            this._optionsSelect.on("change", this._onSearchChange, this);
            this._eo = new RightNow.Event.EventObject(this, {
                filters: {
                    searchName: this._searchName,
                    report_id: this.data.js.report_id
                }
            });
            this._setFilter();
        }
    },

    /**
    * Resets the inital event object data
    */
    _setFilter: function()
    {
        this._eo.filters.data = this.data.js.searchDefault;
    },

    /**
    * Sets the event object from the column select box value
    */
    _setSelected: function()
    {
        var num = this._optionsSelect.get('selectedIndex');
        if (this._optionsSelect.get('options').item(num))
        {
            this._eo.filters.data = this._optionsSelect.get('options').item(num).get('value');
        }
    },
    
    /**
    * Sets the selected dropdown item to the one matching the passed-in value.
    * @param {Int} valueToSelect Value of item to select
    */
    _setSelectedDropdownItem: function(valueToSelect)
    {
        this._optionsSelect.get('options').each(function(option, i) {
            if(parseInt(option.get('value'), 10) === valueToSelect)
            {
                this._optionsSelect.set('selectedIndex', i);
            }
        }, this);
    },

    /**
    * Event handler fired when the search type select box is changed
    *
    * @param {Object} evt Event object
    */
    _onSearchChange: function(evt)
    {
        this._setSelected();
        this.searchSource().fire("webSearchTypeChanged", this._eo);
    },

    /**
    * Event handler received when the search type is changed
    *
    * @param {String} type Event type
    * @param {Object} args Event object
    */
    _onChangedResponse: function(type, args)
    {
        if (RightNow.Event.isSameReportID(args, this.data.attrs.report_id))
        {
            var data = RightNow.Event.getDataFromFiltersEventResponse(args, this._searchName, this.data.attrs.report_id);
            var newValue = (!data) ? this.data.js.searchDefault : data;
            this._setSelectedDropdownItem(newValue);
            this._setSelected();
        }
    },
    
    /**
    * Responds to the filterReset event by setting the internal eventObject's data back to default
    * @param {String} type Event type
    * @param {Object} args Event object
    */
    _onReset: function(type, args)
    {
        if(RightNow.Event.isSameReportID(args, this.data.attrs.report_id) && (args[0].data.name === this._searchName || args[0].data.name === "all"))
        {
            this._setSelectedDropdownItem(this.data.js.searchDefault);
            this._setFilter();
        }
    }
});
