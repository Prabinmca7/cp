RightNow.Widgets.SourceFilter = RightNow.SearchProducer.extend({
    overrides: {
        constructor: function() {
            this.parent();
            this.input = this.Y.one(this.baseSelector + '_Dropdown');
            this.initialValue = this.data.js.filter.value;

            if (this.data.attrs.initial_focus) {
                this.input.focus();
            }

            this.searchSource()
                .on('collect', this.onCollect, this)
                .on('updateFilters', this.onFilterUpdate, this)
                .on('reset', this.onReset, this);

            if (this.data.attrs.search_on_select) {
                this.input.on('change', this.onChange, this);
            }
        }
    },

    /**
     * Triggers a new search in response to the DOM change event.
     */
    onChange: function () {
        this.searchSource()
            .setOptions({page: {key: "page", type: "page", value: 1}})
            .fire('collect')
            .fire('search');
    },

    /**
     * Returns the dropdown's filter value in response to the
     * 'collect' searchSource event.
     * @return {object} RightNow.Event.EventObject
     */
    onCollect: function () {
        var value = this.input.get('value');

        // test for string or int values
        if (value == '0' || value == '-1') {
            value = '';
        }

        return new RightNow.Event.EventObject(this, {
            data: this.Y.merge(this.data.js.filter, { value: value })
        });
    },

    /**
     * Updates the dropdown's selected value in response to
     * the 'updateFilters' event.
     * @param  {string} e  Event name
     * @param  {array} eo Array of event objects
     */
    onFilterUpdate: function(e, eo) {
        this.Y.Object.each(eo[0].data, function(filter) {
            if(filter.key === this.data.js.filter.key) {
                this.input.set('value', filter.value);
            }
        }, this);
    },

    /**
     * Sets the dropdown's value to the initial value in
     * response to the 'reset' event.
     */
    onReset: function () {
        this.input.set('value', this.initialValue);
    }
});
