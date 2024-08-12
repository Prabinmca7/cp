RightNow.Widgets.OrgList = RightNow.SearchFilter.extend({
    overrides: {
        constructor: function(){
            this.parent();

            this._selectBox = this.Y.one(this.baseSelector + "_Options");
            if(!this._selectBox)
                return;
            this._eo = new RightNow.Event.EventObject(this);

            this._selectBox.set('selectedIndex', this.data.js.defaultIndex)
                           .on("change", this._onSelectChange, this);

            this.searchSource().on("orgChanged", this._onChangedResponse, this) // keep all instances in sync
                               .on("search", function(){return this._eo;}, this)
                               .on("reset", this._onResetRequest, this);
            this.searchSource(this.data.attrs.report_id).on("response", this._onChangedResponse, this);

            this._setFilter();
        }
    },

    /**
     * Event handler executed when drop down is changed
     */
    _onSelectChange: function()
    {
        this._setSelectedFilters();
        this.searchSource().fire("orgChanged", this._eo);
        if (this.data.attrs.search_on_select)
        {
            this._eo.filters.reportPage = this.data.attrs.report_page_url;
            this.searchSource().fire("search", this._eo);
        }
    },

    /**
     * Sets the selected dropdown item to the one matching the passed-in value.
     * @param valueToSelect Int Value of item to select
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
     * Sets the event object values based on the current selection
     */
    _setSelectedFilters: function()
    {
        var selectedOption = this._selectBox.get('options').item(this._selectBox.get('selectedIndex')),
            value = parseInt(selectedOption.get('value'), 10),
            label = selectedOption.get('text'),
            selectedFilter = this.data.js.options[value];

        this._eo.filters.data = {
            val: selectedFilter.val,
            fltr_id: selectedFilter.fltr_id,
            oper_id: selectedFilter.oper_id,
            selected: value,
            label: label
        };
    },

    /**
     * Initializes the event object with default data
     */
    _setFilter: function()
    {
        var defaultFilter = this.data.js.options[this.data.js.defaultIndex];
        this._eo.filters = {"rnSearchType": this.data.js.rnSearchType,
                            "report_id": this.data.attrs.report_id,
                            "searchName": this.data.js.searchName,
                            "data": {"fltr_id": defaultFilter.fltr_id,
                                     "oper_id": defaultFilter.oper_id,
                                     "val": defaultFilter.val,
                                     "label": defaultFilter.label,
                                     "selected": this.data.js.defaultIndex
                                    }
                            };
    },

    /**
     * Event handler executed when the org type data is updated
     *
     * @param type string Event type
     * @param args object Arguments passed with event
     */
    _onChangedResponse: function(type, args)
    {
        var data = RightNow.Event.getDataFromFiltersEventResponse(args, this.data.js.searchName, this.data.attrs.report_id),
            newValue = this.data.js.defaultIndex || 0;

        if(data && data.selected !== null && data.selected !== undefined){
            newValue = data.selected;
        }
        this._setSelectedDropdownItem(newValue);
        this._setSelectedFilters();
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
            //The reset is coming from the DisplaySearchFilters widget, set it back to the reset value.
            if(args[0].data.name === this.data.js.searchName) {
                this._setSelectedDropdownItem(this.data.js.resetValue);
                this._setSelectedFilters();
            }
            //The reset is coming from the AdvancedSearchDialog widget, set it to the page's initial value.
            else if(args[0].data.name === 'all') {
                this._setSelectedDropdownItem(this.data.js.defaultIndex);
                this._setSelectedFilters();
            }
        }
    }
});