UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'SourceResultListing_0',
}, function(Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/searchsource/SourceResultListing",
    });

    suite.add(new Y.Test.Case({
        name: "per_page attributes is handled correctly",

        "Passes limit to the searchSource object, it is not overriden": function () {
            var limit;
            widget.searchSource().on('search', function () {
                limit = widget.searchSource().options.limit;
                return false; /* Prevent the search from actually happening. */
            });
            widget.searchSource().fire('search');

            Y.Assert.areSame(33, limit);
        }
    }));

    return suite;
}).run();
