/* global widgetNames */
/**
 * Provides autocomplete functionality onto an input field.
 *
 * Fires the global `widgets:select` event when an autocomplete item
 * is selected.
 */
YUI.add('WidgetAutocomplete', function(Y) {
    'use strict';

    // Search autocomplete
    var WidgetAutocomplete = Y.Component({
        init: function() {
            this.input = Y.one('#widgetsSearch');

            this.input.ancestor('form').on('submit', function(e) { e.halt(); });

            this.input.plug(Y.Plugin.AutoComplete, this.config());

            this.input.ac.on('select', this.onSelect, this);
            this.input.ac.on('query', this.onQuery, this);
        },

        config: function () {
            return {
                source: widgetNames,
                maxResults: 6,
                activateFirstItem: true,
                resultHighlighter: 'phraseMatch',
                resultFilters: this.filterResults,
                width: 'auto'
            };
        },

        filterResults: function (query, results) {
            return Y.Array.filter(results, function(result) {
                return result.text.toLowerCase().indexOf(query.toLowerCase()) !== -1;
            });
        },

        onSelect: function (e) {
            e.halt();
            var result = e.result,
                match = Y.one("#widgets .listing-item[data-name='" + result.text + "']").removeClass("hide");

            Y.Helpers.scrollListItemIntoView(match);

            this.input.set('value', '').blur();

            this.searchedOn = result.text;

            Y.fire('widgets:select', { target: match });
        },

        onQuery: function (e) {
            if (e.query === this.searchedOn) {
                this.searchedOn = null;
                this.input.ac.hide();
                e.halt();
            }
        }
    });

    Y.WidgetAutocomplete = new WidgetAutocomplete();

}, null, {
    requires: ['Component', 'Helpers', 'autocomplete', 'autocomplete-highlighters']
});
