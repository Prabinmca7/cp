RightNow.Widgets.FacetFilter = RightNow.SearchProducer.extend({
    overrides: {
        constructor: function() {
            this.parent();
            
            var searchSourceOptions = this.data.js.filter;
            this.facetFilterContent = this.Y.one(this.baseSelector + '_Content');
            this.resetFilterButton = this.Y.one(this.baseSelector + '_ResetFilterButton');
            this._facet = '';
            this._isFFAction = false;
            this._multiFacet = this.data.js.selectedFacetDetails ? this.data.js.selectedFacetDetails : '';
            if(this.data.js.selectedFacets === null || this.data.js.selectedFacets === undefined )
                this.Y.one(this.baseSelector + '_Content').addClass('rn_Hidden');
            else
                this._facet = this.data.js.selectedFacets;
            
                this.searchSource().setOptions(searchSourceOptions)
                    .on('collect', this.updateFacet, this)
                    .on('response', this._onResponse, this);

            this.Y.one(this.baseSelector + '_Content').delegate('click', this._resetFacets, 'button.rn_ResetFilterBtn', this);
            this.Y.one(this.baseSelector + '_Content').delegate('click', this._clearFacetFilterIcon, 'span.rn_filterChoice', this);
            this.Y.one(this.baseSelector + '_Content').delegate('click', this._clearFacetFilterIcon, 'span.rn_FacetFilterClearIcon', this);
            RightNow.Event.subscribe("evt_updateFacetFilter", this._getFacetDetails, this);
            RightNow.Event.subscribe("evt_sendIsMultiSelectFlag", function(evt, evtData) {
                this._isMultiSelect = evtData[0].data.enable_multi_select;
                this.fetchMultiSelect();
            }, this);
        }
    },

    /**
     * Method to set multi select flag.
     */
    fetchMultiSelect: function() {
        if(this._isMultiSelect === undefined){
            RightNow.Event.fire("evt_getIsMultiSelectFlag");
            return;
        }
    },

    /**
    * Collects facet filter
    * @return {object} Event object
    */
    updateFacet: function() {
        if(this._isFFAction) {
            this._isFFAction = false;
            return new RightNow.Event.EventObject(this, {
            data: {value: this._facet, key: 'facet', type: 'facet'}
            });
        }
        return;
    },

    /**
     * Event handler received when search response data is changed.
     * @param {String} type Event name
     * @param {Array} args Arguments passed with event
     */
    _onResponse: function(type, args) {
        this.fetchMultiSelect();
        this._collectFacet = args[0].data.filters.collectFacet.value;// retain facet flag
        if(!this._isMultiSelect)//For single Facet flows only
            this._facet = '';
        //On retain facet
        if(this._collectFacet || (args[0].data.filters.searchType && args[0].data.filters.searchType.value === 'PAGE')){
                return;
        }
        //On clear filters
        if(args[0].data.filters.facet.value === null) {
            this.Y.one(this.baseSelector + '_Content').get('childNodes').remove();
            this.Y.one(this.baseSelector + '_Content').addClass('rn_Hidden');
        }
    },

    /**
    * Event handler to reset facets.
    * @param {string} evt Event name
    */
    _resetFacets: function(event) {
        this.Y.one(this.baseSelector + '_Content').addClass('rn_Hidden');
        RightNow.Event.fire("evt_ResetFacets");
    },

    /**
    * Event handler to clear the chosen facet.
    * @param {string} evt Event name
    */
    _clearFacetFilterIcon: function(event) {
        if(this._isMultiSelect === undefined)
            this.fetchMultiSelect();

        var clearFacetFilterRefKey = event.target._node.getAttribute('data-id'),
            selectedFacetArr = this.Y.all('.rn_filterChoice') ? this.Y.all('.rn_filterChoice')._nodes : array(),
            len = selectedFacetArr.length,
            refKey = '',
            ffArray = [];

        for(i = 0; i < len; ++i) {
            var iterKey = selectedFacetArr[i].getAttribute('data-id'),
                value = selectedFacetArr[i].innerText;
            refKey = refKey === '' ? iterKey : refKey + ',' + iterKey;
            ffArray[iterKey] = value;
        }
        this._facet = refKey;
        if(this._facet && this._facet !== '') {
            var pos = this._facet.indexOf(clearFacetFilterRefKey);
            if(pos === 0) {
                if(this._facet.indexOf(',') !== -1)
                    this._facet = this._facet.replace(clearFacetFilterRefKey + ',' , '');
                else
                    this._facet = this._facet.replace(clearFacetFilterRefKey, '');
            }
            else if(pos > 0)
                this._facet = this._facet.replace(',' + clearFacetFilterRefKey , '');
        }
        if(!this._isMultiSelect) {//For single Facet flows only
            this._facet = clearFacetFilterRefKey.split('.')[0];
            RightNow.Event.fire("evt_ClearFacetFilter", new RightNow.Event.EventObject(this, {data : { clearFacetFilterRefKey : clearFacetFilterRefKey, facet: this._facet, selectedFacetDetails: ffArray}}));
        }
        else 
            RightNow.Event.fire("evt_ClearFacetFilter", new RightNow.Event.EventObject(this, {data : { clearFacetFilterRefKey : clearFacetFilterRefKey, facet: this._facet }}));
        this._isFFAction = true;
        this.searchSource().fire('collect').fire('search', new RightNow.Event.EventObject(this, {
            data: {
                page: this.data.js.filter,
                sourceCount: 1
            }
        }));
    },

    /**
    * Event handler to pass details of current selected facet.
    * @param {string} evt Event name
    * @param {args} args Arguments provided from event fire
    */
    _getFacetDetails: function(evt, args) {
        this.Y.one(this.baseSelector + '_Content').get('childNodes').remove();
        this._selectedFacetDetails = args[0].data.selectedFacetDetails;
        this._facet = args[0].data.facet;
        if(this._selectedFacetDetails === undefined) {
            this.Y.one(this.baseSelector + '_Content').addClass('rn_Hidden');
            return;
        }
        var facetOrder = this.data.js.orderedFacets,
        selectedFacetLength = Object.keys(args[0].data.selectedFacetDetails).length;
        if(selectedFacetLength === 0) {
            this.Y.one(this.baseSelector + '_Content').addClass('rn_Hidden');
        }
        else {
            this.Y.one(this.baseSelector + '_Content').removeClass('rn_Hidden');
            this._selectedFacetDetails = args[0].data.selectedFacetDetails;
            this.facetFilterContent.append(new EJS({text: this.getStatic().templates.view}).render({
                widgetInstanceID: this.baseDomID,
                facetArray: this._selectedFacetDetails,
                facetOrder: facetOrder,
                data: this.data,
                labelFilter: this.data.attrs.label_filter
            }));
        }
    }
});
