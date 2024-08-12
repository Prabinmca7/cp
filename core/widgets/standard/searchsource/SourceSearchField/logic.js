RightNow.Widgets.SourceSearchField = RightNow.SearchProducer.extend({
    overrides: {
        constructor: function() {
            this.parent();
            this.input = this.Y.one(this.baseSelector + '_SearchInput');

            if (this.data.attrs.initial_focus) {
                this.input.focus();
            }

            this.searchSource()
                .on('collect', this.onCollect, this)
                .on('collect', this.addAccessType , this)
                .on('updateFilters', this.onFilterUpdate, this)
                .on('reset', this.onReset, this);

            RightNow.Event.subscribe('evt_getSearchField', function() {
                RightNow.Event.fire("evt_sendSearchField", new RightNow.Event.EventObject(this, {data: this.input}));
            }, this);
        }
    },

    /**
     * Returns a RightNow.Event.EventObject with the filter value filled in
     * @return {object} RightNow.Event.EventObject with the filter value filled in
     */
    onCollect: function () {
        var searchTerm = "";
        var keywordEntered = this.Y.Lang.trim(this.input.get('value'));
        if (keywordEntered) {
            searchTerm = this.Y.Escape.html(keywordEntered);
        }
        //reset the page number to 1, when search button is clicked
        this.searchSource().setOptions({page: {key: "page", type: "page", value: 1}});
        return new RightNow.Event.EventObject(this, {
            data: this.Y.merge(this.data.js.filter, { value: searchTerm })
        });
    },

    /**
     * Returns a RightNow.Event.EventObject with the filter value filled in with accesstype
     * @return {object} RightNow.Event.EventObject with the filter value filled in
     */
    addAccessType: function() {
        return new RightNow.Event.EventObject(this, {
            data: {value: this.data.attrs.access_type, key: 'accessType', type: 'accessType'}
        });
    },
 
    /**
     * Updates the filter value.
     * @param  {string} e  Event name
     * @param  {object} eo RightNow.Event.EventObject
     */
    onFilterUpdate: function(e, eo) {
        this.Y.Object.some(eo[0].data, function(filter) {
            if(filter.key === this.data.js.filter.key) {
                var filterValue = this.Y.Node.create('<textarea>' + filter.value + '</textarea>').get('innerText');
                this.input.set('value', (filter.value !== '*') ? filterValue : '');
                return true;
            }
        }, this);
    },

    /**
     * Resets the input field to its original value.
     */
    onReset: function () {
        this.input.set('value', this.data.js.prefill);
        }
});
