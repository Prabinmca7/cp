RightNow.Widgets.KeywordText = RightNow.SearchFilter.extend({
    overrides: {
        constructor: function() {
            this.parent();
            
            this._textElement = this.Y.one(this.baseSelector + "_Text");
            this.data.js.initialValue = this._decoder(this.data.js.initialValue);
            if(this._textElement) {
                this._searchedOn = this._textElement.get("value");
                if(this._searchedOn !== this.data.js.initialValue)
                    this._textElement.set("value", this.data.js.initialValue);
                    
                this._setFilter();
                this._textElement.on("change", this._onChange, this);

                this.searchSource().on("keywordChanged", this._onChangedResponse, this) // keep all instances in sync
                                   .on("search", this._onGetFiltersRequest, this)
                                   .on("reset", this._onResetRequest, this);
                // only update the keyword from the server data for report responses
                this.searchSource(this.data.attrs.report_id).on("response", this._onChangedResponse, this);
                if(this.data.attrs.initial_focus)
                    this._textElement.focus();  
            }
        }
    },

    /**
    * Event handler executed when text has changed
    *
    * @param evt object Event
    */
    _onChange: function(evt) {
        this._eo.data = this._textElement.get("value");
        this._eo.filters.data = this._textElement.get("value");
        this.searchSource().fire("keywordChanged", this._eo);
    },

    /**
    * Event handler executed to fire the event object for search filters
    *
    * @param type string Event type
    * @param args object Arguments passed with event
    */
    _onGetFiltersRequest: function(type, args) {
        this._eo.filters.data = this.Y.Lang.trim(this._textElement.get("value"));
        this._searchedOn = this._eo.filters.data;
        return this._eo;
    },

    /**
    * internal function to set the initial values of the event object
    *
    */
    _setFilter: function() {
        this._eo = new RightNow.Event.EventObject(this, {filters: {
            searchName: this.data.js.searchName,
            data: this.data.js.initialValue,
            rnSearchType: this.data.js.rnSearchType,
            report_id: this.data.attrs.report_id
        }});
    },

    /**
    * Event handler executed when the keyword data is changed
    *
    * @param type string Event type
    * @param args object Arguments passed with event
    */
    _onChangedResponse: function(type, args) {
        var data = RightNow.Event.getDataFromFiltersEventResponse(args, this.data.js.searchName),
            newValue = (data === null) ? this.data.js.initialValue : data;
        newValue = this._decoder(newValue);
        if(this._textElement.get("value") !== newValue)
            this._textElement.set("value", newValue);
    },

    /**
    * Responds to the filterReset event by setting the keyword data back to either the initial
    * value or the last searched value.
    * @param type String Event name
    * @param args Object Event object
    */
    _onResetRequest: function(type, args) {
        //The history manager is reseting all of the filters to their initial value
        if(!args[0] || args[0].data.name === this.data.js.searchName) { 
            this._textElement.set('value', this.data.js.initialValue);
        }
        //The AdvancedSearchDialog is reseting all of the filters to their prior value (before user interaction)
        else if(args[0].data.name === 'all') {
            this._textElement.set('value', this._searchedOn);
        }
    },

    /**
    * Decode back to what is stored in the DOM nodes value property so it can be used for comparison
    * @param value String Text to possibly decode
    * @return String Decoded text (null if value was null)
    */
    _decoder: function(value) {
        if(value)
            return value.replace(/&gt;/g, '>').replace(/&lt;/g, '<').replace(/&#039;/g, "'").replace(/&quot;/g, '"');
        return value;
    }
});
