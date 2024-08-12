UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'DisplaySearchSourceFilters_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/searchsource/DisplaySearchSourceFilters"
    });

    suite.add(new Y.Test.Case({
        name: "Event handling and operation",

        setUp: function () {
            widget.searchSource().on('search', function () {
                // Prevent the search from happening.
                return false;
            });
        },

        "Widget has attributes after initialization": function () {
            Y.Assert.isNotNull(widget.filterLinks);
            Y.Assert.isObject(widget.data.js);
            Y.Assert.isObject(widget.data.js.filters);
        },

        "Widget and filters are not visible after clicked": function () {
            var filterLink = Y.one(baseSelector + ' a');
            filterLink.simulate('click');
            Y.Assert.isTrue(Y.one(baseSelector).hasClass('rn_Hidden'));
            Y.Assert.isTrue(Y.one(baseSelector + '_Filter_' + filterLink.getData('type')).hasClass('rn_Hidden'));
        },

        "Value is updated in response to 'updateFilters' event": function () {
            widget.searchSource().fire('updateFilters', new RightNow.Event.EventObject(null, { data: [
                {
                    key: widget.data.js.filters.author.key,
                    value: "Batman and Robin"
                }
            ]}));
            Y.Assert.areEqual(widget.data.js.filters.author.value, "Batman and Robin");
        },

        "Value is returned in response to 'collect' if something is selected": function () {
            widget.data.js.filters.author.value = 'Spiderman and the Human Torch';
            widget.searchSource().once('searchCancelled', function (e, args) {
                Y.Assert.isTrue('author' in args[0]);
                Y.Assert.areSame(widget.data.js.filters.author.key, args[0].author.key);
                Y.Assert.areSame(widget.data.js.filters.author.type, args[0].author.type);
                Y.Assert.areSame('Spiderman and the Human Torch', args[0].author.value);
            });
            widget.searchSource().fire('collect').fire('search');
        }
    }));

    return suite;
}).run();
