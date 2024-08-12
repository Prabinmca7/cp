RightNow.Widgets.SourceResultDetails = RightNow.SearchConsumer.extend({
    overrides: {
        constructor: function() {
            this.parent();

            this.searchSource().on('response', this.onSearchComplete, this);
        }
    },

    /**
     * Re-renders the widget when new search results come back
     * in the 'response' event.
     * @param  {string} evt  Event name
     * @param  {object} args RightNow.Event.EventObject
     */
    onSearchComplete: function (evt, args) {
        this.Y.one(this.baseSelector).setHTML(this.getResultText(args[0].data));
    },

    /**
     * Determines the result set window and
     * displays it within a label.
     * @param  {object} result Search results
     * @return {string}        Label containing result window or
     *                               empty string if no results came back
     */
    getResultText: function (result) {
        if (!result.size) return '';

        var label = (result.total && this.data.attrs.label_known_results) ? this.data.attrs.label_known_results : this.data.attrs.label_results;

        return RightNow.Text.sprintf(label, result.offset + 1, result.offset + result.size, result.total);
    }
});
