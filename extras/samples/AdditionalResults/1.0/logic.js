/**
 * File: logic.js
 * Abstract: Extending logic for AdditionalResults widget
 * Version: 1.0
 */
RightNow.namespace('Custom.Widgets.search.AdditionalResults');
Custom.Widgets.search.AdditionalResults = RightNow.Widgets.Multiline.extend({
    overrides: {
        /**
         * Overrides RightNow.Widgets.Multiline#constructor.
         */
        constructor: function() {
            // Call into parent's constructor
            this.parent();

            this.Y.one(this.baseSelector).delegate('click', this.onResultClick, '.result');

            // The endpoint for searches for this source_id is specified here.
            var options = {};
            options[this.data.attrs.source_id] = { endpoint: this.data.attrs.search_endpoint };
            // Any searches that are triggered with this source_id will automatically POST the
            // search term (kw) to the endpoint.
            // Additional options can also be specified such as...
            // Additional search filters to include (if they're on the page) and POST values to the endpoint.
            // options[this.data.attrs.source_id].filters = [ 'p', 'c', 'sort' ];
            // Additional parameters to POST to the endpoint.
            // options[this.data.attrs.source_id].params = { 'key': 'value' };

            this.searchSource(options)
                .on('search', this.onSearch, this)
                .on('response', this.onAdditionalResultsResponse, this);
        },

        /**
         * Overrides RightNow.Widgets.Multiline#_searchInProgress.
         * Resets the _reportResults member on a new search before calling its parent.
         * @param {string} evt Event name
         * @param {array} args Event object
         */
        _searchInProgress: function(evt, args) {
            this._reportResults = null;
            this.parent(evt, args);
        },

        /**
         * Overrides RightNow.Widgets.Multiline#_onReportChanged.
         * Sets the _reportResults member to the given results. If both the
         * report results and additional results have returned then the additional
         * results are added to data so that they're applied toward the rendered view
         * in the parent method.
         * @param {string} evt Event name
         * @param {array} args Event object
         */
        _onReportChanged: function(type, args) {
            this._reportResults || (this._reportResults = args);

            if (this._reportResults) {
                this._reportResults[0].data.heading = this.data.attrs.label_heading;
                this._reportResults[0].data.results = this._additionalResults || [];
                this.parent('response', this._reportResults);
            }
        }
    },

    /**
     * Click handler for result div.
     * Makes the result href easier to click
     * by making the entire result div clickable.
     * @param {object} e Click event
     */
    onResultClick: function(e) {
        e.halt();

        RightNow.Url.navigate(e.currentTarget.one('.content a').get('href'), true);
    },

    /**
     * Called when a new search is triggered by a widget with the same source_id attribute.
     * Resets the member keeping track of additional results and responds with an event object
     * containing the widget's id.
     * @return {object} EventObject
     */
    onSearch: function() {
        this._additionalResults = null;

        return new RightNow.Event.EventObject(this, { data: {
            // Since the keyword being searched on is automatically included
            // (see the constructor comments above),
            // the only thing we need to send to the server is the widget's id
            // which is used to sort out which widget instance's method should
            // be used to handle the AJAX request.
            w_id: this.data.info.w_id
        }});
    },

    /**
     * Called when results for the AJAX request for additional results are returned from the server.
     * @param {string} evt Event name
     * @param {array} args Event arguments; the event object that we want will be at index 0
     */
    onAdditionalResultsResponse: function(evt, args) {
        this._additionalResults = args[0].data.RelatedTopics;
        this._onReportChanged();
    }
});
