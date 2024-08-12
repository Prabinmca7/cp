RightNow.Widgets.SourceResultListing = RightNow.SearchConsumer.extend({
    overrides: {
        constructor: function() {
            this.parent();

            this._contentDiv = this.Y.one(this.baseSelector + "_Content");

            if (!this._contentDiv) return;

            var searchSourceOptions = {
                endpoint: this.data.attrs.search_results_ajax
            }

            if('per_page' in this.data.attrs) {
                searchSourceOptions.limit = this.data.attrs.per_page;
            }
            
            this.searchSource().setOptions(searchSourceOptions)
                .on('search', this.searchInProgress, this)
                .on('response', this.onSearchComplete, this)
                .fire('initializeFilters', new RightNow.Event.EventObject(this, {data: this.data.js.filters}));
        }
    },

    /**
     * Called when the server has responded with
     * a list of results.
     * @param  {string} e      Event name
     * @param  {object} result RightNow.Event.EventObject
     */
    onSearchComplete: function(e, result) {
        var results = result[0].data;
        this.updateLoadingIndicators(false);

        this.updateAriaAlert(results && results.total);
        RightNow.Url.transformLinks(this._contentDiv.setHTML(results.html));
        this.updateMoreLink(results);

        if (this.data.attrs.hide_when_no_results) {
            this.Y.one(this.baseSelector).toggleClass('rn_Hidden', !results.total);
        }
    },

    /**
     * Updates the loading indicators when a new
     * search commences.
     */
    searchInProgress: function() {
        this._contentDiv.setHTML('');
        this.updateLoadingIndicators(true);
    },

    /**
     * Adds/removes the 'more results' link
     * depending on whether there are results.
     * @param  {object} results Search results
     */
    updateMoreLink: function (results) {
        if (!this.data.attrs.more_link_url) return;
        var label = this.data.attrs.label_heading ? (this.data.attrs.label_more_link + ' ' + this.data.attrs.label_heading) : this.data.attrs.label_more_link;

        var link = (results.total > 0 && results.total > results.filters.limit.value) ?
            new EJS({ text: this.getStatic().templates.moreResultsLink }).render({
                href: this.addSearchParametersToUrl(this.data.attrs.more_link_url, results.filters),
                label: label
            }) :
            '';
        this.Y.one(this.baseSelector + ' .rn_AdditionalResults').setHTML(link);
    },

    /**
     * Adds URL parameters from filters onto url.
     * @param {string} url     Base url
     * @param {object} filters Search filters
     */
    addSearchParametersToUrl: function (url, filters) {
        this.Y.Object.each(filters, function (filter) {
            if (filter.key && filter.value) {
                url = RightNow.Url.addParameter(url, filter.key, filter.value);
            }
        });

        return RightNow.Url.addParameter(url, 'session', RightNow.Url.getSession());
    },

    /**
    * Changes the loading icon and hides/unhide the data.
    * @param {Boolean} loading Whether to add or remove the loading indicators
    */
    updateLoadingIndicators: function(loading) {
        var loadingState = {
                toLoading: {
                    ariaBusy: true,
                    method: 'addClass',
                    opacity: 0
                },
                fromLoading: {
                    ariaBusy: false,
                    method: 'removeClass',
                    opacity: 1
                }
            },
            state = loadingState[loading ? 'toLoading' : 'fromLoading'];

        this._contentDiv.setStyle("height", loading ? this._contentDiv.get("offsetHeight") + "px" : "auto");
        document.body.setAttribute("aria-busy", state.ariaBusy + "");

        if(this.Y.UA.ie && this.Y.UA.ie < 11){
            //IE rendering: so bad it can't handle eye-candy
            this._contentDiv[state.method]("rn_Hidden");
        }
        else{
            this._contentDiv[state.method]("rn_Loading");
            this._contentDiv.transition({
                opacity: state.opacity,
                duration: 1.5
            });
        }
    },

    /**
     * Updates the text for the ARIA alert div that appears above the results listings.
     * @param {Boolean} newResults Whether there are new results or not
     */
    updateAriaAlert: function(newResults) {
        var label = (newResults)
            ? this.data.attrs.label_screen_reader_search_success_alert
            : this.data.attrs.label_screen_reader_search_no_results_alert;

        if(!label) return;

        this._ariaAlert = this._ariaAlert || this.Y.one(this.baseSelector + "_Alert");

        if(this._ariaAlert) {
            this._ariaAlert.set("innerHTML", this.data.attrs.label_heading + ' ' + label);
        }
    }
});
