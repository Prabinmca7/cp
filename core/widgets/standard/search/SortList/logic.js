RightNow.Widgets.SortList = RightNow.SearchFilter.extend({
    overrides: {
        constructor: function() {
            this.parent();
            
            this._headingsSelect = this.Y.one(this.baseSelector + "_Headings");
            this._directionSelect = this.Y.one(this.baseSelector + "_Direction");
        
            RightNow.Event.on("evt_sortChange", this._onResponse, this);
            this.searchSource()
                .on("search", this._onSearch, this)
                .on("reset", this._onReset, this);
            this.searchSource(this.data.attrs.report_id).on("response", this._onResponse, this);
            this._setFilter();
            this._setSelectedDropdownItem(this._headingsSelect, this.data.js.col_id);
            this._setSelectedDropdownItem(this._directionSelect, this.data.js.sort_direction);
        
            this._headingsSelect.on("change", this._onChange, this, "column");
            this._directionSelect.on("change", this._onChange, this, "direction");
        }
    },
    
    /**
    * Event handler executed when either the direction
    * or column select box is changed.
    * @param {Object} evt The change event
    * @param {String} dropdownThatChanged Either 'direction' or 'column'
    */
    _onChange: function(evt, dropdownThatChanged) {
        this._setEventObjectFromUI(dropdownThatChanged);
        RightNow.Event.fire("evt_sortChange", this._eo);
        if (this.data.attrs.search_on_select) {
            this._eo.filters.reportPage = this.data.attrs.report_page_url;
            this.searchSource().fire("search", this._eo);
        }
    },

    /**
    * Sets the event object from the column or direction select box value.
    * @param {String} dropdownThatChanged Either 'direction' or 'column'
    */
    _setEventObjectFromUI: function(dropdownThatChanged) {
        var selectBox, memberToSet, index;
        if (dropdownThatChanged === "direction") {
            selectBox = this._directionSelect;
            memberToSet = "sort_direction";
        }
        else {
            selectBox = this._headingsSelect;
            memberToSet = "col_id";
        }
        
        if (selectBox) {
            index = selectBox.get("selectedIndex");
            index = (index > 0) ? index : 0;
            this._eo.filters.data[memberToSet] = parseInt(selectBox.get("options").item(index).get("value"), 10);
        }
    },
    
    /**
    * Sets the selected dropdown item to the one matching the passed-in value.
    * @param {Object} selectBox HTMLElement Select box in which to set
    * @param {Number} valueToSelect Value of item to select
    * @return {Boolean} Whether or not the operation was successful
    */
    _setSelectedDropdownItem: function(selectBox, valueToSelect) {
        if (selectBox) {
            selectBox.get("options").each(function(option, i) {
                if (parseInt(option.get("value"), 10) === valueToSelect) {
                    selectBox.set("selectedIndex", i);
                    return true;
                }
            });
        }
        return false;
    },

    /**
    * Internal function to set the initial event object data.
    */
    _setFilter: function() {
        this._eo = new RightNow.Event.EventObject(this, {
            filters: {
                searchName: this.data.js.searchName,
                report_id: this.data.attrs.report_id,
                data: this._getDataObject()
            }
        });
    },

    /**
    * Internal function to reset event data.
    * @return {Object} EventObject data
    */
    _getDataObject: function() {
        return {
            col_id: this.data.js.col_id,
            sort_direction: this.data.js.sort_direction
        };
    },

    /**
    * Event handler executed when the sort type is changed.
    * @param {String} name Event name
    * @param {Array} args rguments passed with event
    */
    _onResponse: function(name, args) {
        if (args[0].w_id === this.instanceID) return;
        
        var data = RightNow.Event.getDataFromFiltersEventResponse(args, this.data.js.searchName, this.data.attrs.report_id);
        if (this._eo.filters.data === null) {
            this._eo.filters.data = this._getDataObject();
        }
            
        //set headings
        this._setSelectedDropdownItem(this._headingsSelect, ((!data || data.col_id == null) ? this.data.js.col_id : data.col_id));
        this._setEventObjectFromUI("column");
        //set direction
        this._setSelectedDropdownItem(this._directionSelect, ((!data || data.sort_direction == null) ? this.data.js.sort_direction : data.sort_direction));
        this._setEventObjectFromUI("direction");        
    },
    
    /**
    * Responds to the filterReset event by setting the internal eventObject's data back to default
    * @param {String} name Event name
    * @param {Array} args Event object
    */
    _onReset: function(type, args) {
        if (!args[0] || args[0].data.name === this.data.js.searchName || args[0].data.name === "all") {
            this._setSelectedDropdownItem(this._headingsSelect, this.data.js.col_id);
            this._setSelectedDropdownItem(this._directionSelect, this.data.js.sort_direction);
            this._setFilter();
        }
    },

    /**
    * Event handler executed when search filters are requested.
    * @param {String} name Event name
    * @param {Array} Event object
    * @return {Object} Event Object
    */
    _onSearch: function(type, args) {
        return this._eo;
    }

});
