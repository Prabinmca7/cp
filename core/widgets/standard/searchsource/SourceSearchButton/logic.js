RightNow.Widgets.SourceSearchButton = RightNow.SearchProducer.extend({
    overrides: {
        constructor: function() {
            this.parent();

            this.searchButton = this.Y.one(this.baseSelector + "_SubmitButton");
            RightNow.Event.subscribe("evt_sendSearchField", function(evt, evtData) {
                this.searchField = evtData[0].data;
                this.search();
            }, this);

            if(this.searchButton) {
                this.enableClickListener();
                this.searchSource()
                    .setOptions(this.searchOptions())
                    .setOptions(this.data.js.sources)
                    .on('response', this.enableClickListener, this);
            }
        }
    },

    /**
     * Returns options to apply towards the search.
     * @return {object} Search options
     */
    searchOptions: function () {
        return this._searchOptions || (this._searchOptions = {
            new_page: this.data.attrs.search_results_url,
            target: this.data.attrs.target,
            limit: this.data.attrs.per_page,
            sourceCount: this.data.attrs.source_id.split(',').length
        });
    },

    /**
     * Searches when the button is clicked.
     * @param  {object} e Click event
     */
    search: function(e) {
        if(e)
            e.halt();
        if(!this.searchField){
            RightNow.Event.fire("evt_getSearchField", new RightNow.Event.EventObject(this));
            return;
        }

        if(this.searchInProgress) return;

        this.searchSource().fire('collect');

        if(this.searchSource().multiple) {
            this.filters = this.searchSource().sources[0].filters;
        }
        else {
            this.filters = this.searchSource().filters;
        }
        if(this.filters.query && this.Y.Lang.trim(this.filters.query.value) !== '') {
            if (this.filters.query.key === "kw" && this.Y.Lang.trim(this.filters.query.value) === "*") {
                this.filters.direction = this.filters.direction || {};
                this.filters.direction.value = 0;
                // Wildcard(*) search returns result in random order, so force it to use sorting on 'UpdatedTime' column. Additional note: use 1 for 'UpdatedTime' and 2 for 'CreatedTime'.
                this.filters.sort = this.filters.sort || {};
                this.filters.sort.value = 1;
            }
            if(!this.data.attrs.search_results_url){
                this.disableClickListener();
            }
            this.searchSource().fire('search', new RightNow.Event.EventObject(this, {
               data: this.searchOptions()
           }));
        }
        else {
            RightNow.UI.displayBanner(this.data.attrs.label_enter_search_keyword, {
                type: 'WARNING',
                focusElement: this.searchField
            });
        }
    },

    /**
     * Enables the click listener.
     */
    enableClickListener: function(evt) {
        this.searchInProgress = false;
        this.searchButton.set('disabled', false).on('click', this.search, this);
        if(evt) {
            this.searchButton.focus();
        }
    },

    /**
     * Disables the click listener.
     */
    disableClickListener: function() {
        this.searchInProgress = true;
        this.searchButton.set('disabled', true).detach('click', this.search, this);
    }
});
