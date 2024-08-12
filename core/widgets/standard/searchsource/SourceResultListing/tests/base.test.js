UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'SourceResultListing_0',
}, function(Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/searchsource/SourceResultListing",
    });

    suite.add(new Y.Test.Case({
        name: "Event Handling and Operation",

        setUp: function () {
            this.content = Y.one(baseSelector + '_Content');
        },

        "Updates the loading indicators when the 'search' event fires": function () {
            widget.searchSource().on('search', function () { return false; /* Prevent the search from actually happening. */});
            widget.searchSource().fire('search');

            Y.Assert.areSame('true', document.body.getAttribute('aria-busy'));
            Y.Assert.areNotSame(1, this.content.getComputedStyle('opacity'));
        },

        "Sets its own ajax handler as the ajax endpoint": function () {
           Y.Assert.areSame(widget.data.attrs.search_results_ajax, widget.searchSource().options.endpoint);
           Y.Assert.areSame('SourceResultListing_' + widget.data.info.w_id, widget.searchSource().options.params.w_id);
        },

        "Injects new html content from 'response' event": function () {
           var html = '<strong>The Selfish Giant</strong>';

           widget.searchSource().fire('response', new RightNow.Event.EventObject(null, { data: {
               total: 2,
               results: [],
               html: html,
               filters: { limit: { value: 2 } }
           }}));

           Y.Assert.areSame(html, this.content.getHTML());
       },

        "Adds more link if number of results is more than the limit value": function () {
            var filters = { limit: { value: 1 }, query: { key: 'kw', value: 'carmen' } };

            widget.searchSource().fire('response', new RightNow.Event.EventObject(null, { data: {
                total: 2,
                results: [],
                filters: filters
            }}));

            var moreResults = Y.one(baseSelector + ' .rn_AdditionalResults .rn_MoreResultsLink');

            Y.Assert.areSame(widget.data.attrs.label_more_link, Y.Lang.trim(moreResults.getHTML()));
            var expectedUrl = widget.data.attrs.more_link_url + '/' + filters.query.key + '/' + filters.query.value;
            Y.Assert.areSame(0, moreResults.getAttribute('href').indexOf(expectedUrl));
        },

        "Removes more link if the number of results is less than the limit value": function () {
            widget.searchSource().fire('response', new RightNow.Event.EventObject(null, { data: {
                total: 2,
                results: [],
                filters: { limit: { value: 3 } }
            }}));

            Y.Assert.isNull(Y.one(baseSelector + ' .rn_AdditionalResults .rn_MoreResultsLink'));
        }
    }));

    return suite;
}).run();
