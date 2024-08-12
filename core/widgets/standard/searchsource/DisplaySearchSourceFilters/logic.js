RightNow.Widgets.DisplaySearchSourceFilters = RightNow.SearchProducer.extend({
    overrides: {
        constructor: function() {
            this.parent();

            this.filterLinks = this.Y.all(this.baseSelector + ' a');
            this.filterLinks.on("click", this.onFilterClick, this);

            var searchSource = this.searchSource()
                .on('updateFilters', this.onFilterUpdate, this)
                .on('reset', this.onReset, this);

            for(var filter in this.data.js.filters) {
                searchSource.on('collect', this.getOnCollectMethod(this.data.js.filters[filter]), this);
                this.data.js.filters[filter].initialValue = this.data.js.filters[filter].value;
            }
            this.updateVisibility();
        }
    },

    /**
     * Handle when a filter is clicked (removed)
     * @param  {string} e Event from the click event
     */
    onFilterClick: function(e) {
        var filter = e.currentTarget.getData('type');
        this.data.js.filters[filter].value = null;
        this.updateVisibility();
        this.searchSource().fire('collect');
        this.searchSource().fire('search', new RightNow.Event.EventObject(this, {
            data: {
                sourceCount: this.data.js.sourceCount
            }
        }));
    },

    /**
     * Returns a method to be assigned for a specific filter's 'collect' event
     * @return {function} A function which returns a RightNow.Event.EventObject object
     */
    getOnCollectMethod: function(filterData) {
        return function() {
            return new RightNow.Event.EventObject(this, {
                data: {
                    key: filterData.key,
                    type: filterData.type,
                    value: filterData.value
                }
            });
        }
    },

    /**
     * Updates the dropdown's selected value in response to the
     *   'updateFilters' event.
     * @param  {string} e  Event name
     * @param  {array} eo Array of event objects
     */
    onFilterUpdate: function(e, eo) {
        this.Y.Object.each(eo[0].data, function(filter) {
            for(var filterData in this.data.js.filters) {
                if(filter.key === this.data.js.filters[filterData].key) {
                    this.data.js.filters[filterData].value = filter.value;
                }
            }
        }, this);
        this.updateVisibility();
    },

    /**
     * Sets the filter's value to the initial value in response to the
     *   'reset' event.
     */
    onReset: function () {
        for(var filter in this.data.js.filters) {
            this.data.js.filters[filter].value = this.data.js.filters[filter].initialValue;
        }
        this.updateVisibility();
    },

    /**
     * Updates the widget and its subsequent filter's visibility
     */
    updateVisibility: function() {
        var filterDiv;
        for(var filter in this.data.js.filters) {
            filterDiv = this.Y.one(this.baseSelector + '_Filter_' + this.data.js.filters[filter].type);
            if(!this.data.js.filters[filter].value) {
                filterDiv.addClass('rn_Hidden');
            } else {
                filterDiv.removeClass('rn_Hidden');
            }
        }
        if(this.Y.all(this.baseSelector + ' .rn_Filter:not(.rn_Hidden)').size() > 0) {
            this.Y.one(this.baseSelector).removeClass('rn_Hidden');
        }
        else {
            this.Y.one(this.baseSelector).addClass('rn_Hidden');
        }
    }
});
