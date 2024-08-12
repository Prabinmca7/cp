RightNow.Widgets.SourceSort = RightNow.SearchProducer.extend({
    overrides: {
        constructor: function() {
            this.parent();
            this.inputColumn = this.Y.one(this.baseSelector + '_Column');
            this.initialValueColumn = this.data.js.filter_column.value || '-1';
            this.inputDirection = this.Y.one(this.baseSelector + '_Direction');
            this.initialValueDirection = this.data.js.filter_direction.value || '-1';

            this.searchSource()
                .on('collect', this.onCollectColumn, this)
                .on('collect', this.onCollectDirection, this)
                .on('updateFilters', this.onFilterUpdate, this)
                .on('reset', this.onReset, this);
        }
    },

    /**
     * Triggers a new search in response to the DOM change event.
     */
    onChange: function () {
        this.searchSource().fire('collect').fire('search');
    },

    /**
     * Returns the column dropdown's filter value in response to the
     * 'collect' searchSource event.
     * @return {object} RightNow.Event.EventObject
     */
    onCollectColumn: function () {
        return this.onCollect(this.inputColumn, this.data.js.filter_column);
    },

    /**
     * Returns the direction dropdown's filter value in response to the
     * 'collect' searchSource event.
     * @return {object} RightNow.Event.EventObject
     */
    onCollectDirection: function () {
        return this.onCollect(this.inputDirection, this.data.js.filter_direction);
    },

    /**
     * Returns the given dropdown's filter value in response to the
     * 'collect' searchSource event.
     * @param {object} input Dropdown element
     * @param {object} filter Filter object
     * @return {object} RightNow.Event.EventObject
     */
    onCollect: function (input, filter) {
        var value;
        if (this.incompleteValues()) {
            value = '';
        }
        else {
            value = this.getValue(input);
        }

        return new RightNow.Event.EventObject(this, {
            data: this.Y.merge(filter, { value: value })
        });
    },

    /**
     * Determines if either dropdown is not selected, indicating not to use either value in the search.
     * @return {bool} True if either of the dropdowns does not have a selection
     */
    incompleteValues: function () {
        return this.getValue(this.inputColumn) === '' || this.getValue(this.inputDirection) === '';
    },

    /**
     * Returns the dropdown's processed filter value.
     * @param {object} input Dropdown element
     * @return {string} Processed filter value (empty string if dropdown value is '0' or '-1')
     */
    getValue: function (input) {
        var value = input.get('value');

        // test for string or int values
        if (value == '0' || value == '-1') {
            value = '';
        }

        return value;
    },

    /**
     * Updates both dropdowns to a selected value in response to
     * the 'updateFilters' event.
     * @param  {string} e  Event name
     * @param  {array} eo Array of event objects
     */
    onFilterUpdate: function(e, eo) {
        this.inputColumn.set('value', '-1');
        this.inputDirection.set('value', '-1');
        this.Y.Object.each(eo[0].data, function(filter) {
            if(filter.key === this.data.js.filter_column.key) {
                this.inputColumn.set('value', filter.value);
            }
            else if(filter.key === this.data.js.filter_direction.key) {
                this.inputDirection.set('value', filter.value);
            }
        }, this);
    },

    /**
     * Sets both dropdowns to their initial values in
     * response to the 'reset' event.
     */
    onReset: function () {
        this.inputColumn.set('value', this.initialValueColumn);
        this.inputDirection.set('value', this.initialValueDirection);
    }
});
