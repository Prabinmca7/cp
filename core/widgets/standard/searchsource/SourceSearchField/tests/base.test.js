UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'SourceSearchField_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/searchsource/SourceSearchField",

        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.input = Y.one(baseSelector + '_SearchInput');
                }
            };

            for (var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    suite.add(new Y.Test.Case({
        name: "Event handling",

        "Event object returned to 'collect' event contains the field value": function() {
            this.initValues();

            var searchTerm = 'nocturno',
                searchTermReceivedInEvent = null;

            this.input.set('value', searchTerm);

            widget.searchSource().on('searchCancelled', function (evt, args) {
                var queryFilter = args[0].query;

                searchTermReceivedInEvent = queryFilter.value;

                Y.Assert.areSame(widget.data.js.filter.key, queryFilter.key);
                Y.Assert.areSame(widget.data.js.filter.type, queryFilter.type);
            }).on('search', function () { return false; /* Prevent the search from happening */ })
              .fire('collect').fire('search');

            Y.Assert.areSame(searchTerm, searchTermReceivedInEvent);
        },

        "Field value doesn't change in response to 'updateFilters' event when event doesn't have matching filter": function() {
            var originalSearchTerm = 'energia';

            this.input.set('value', originalSearchTerm);

            var eo = new RightNow.Event.EventObject(null, ( { data: [
                {
                    key: 'geography',
                    value: 'natural'
                }
            ]}));

            widget.searchSource().fire('updateFilters', eo);

            Y.Assert.areSame(originalSearchTerm, this.input.get('value'));
        },

        "Field value is updated to event data in 'updateFilters' event": function() {
            var searchTerm = 'poyeyu';
            var eo = new RightNow.Event.EventObject(null, ( { data: [
                {
                    key: widget.data.js.filter.key,
                    value: searchTerm
                }
            ]}));

            widget.searchSource().fire('updateFilters', eo);

            Y.Assert.areSame(searchTerm, this.input.get('value'));
        },

        "Field value is updated to prefill value when triggered by 'reset' event": function() {
            var prefill = widget.data.js.prefill = 'shalom';

            widget.searchSource().fire('reset');

            Y.Assert.areSame(prefill, this.input.get('value'));
        },

        "Empty search (when search term is *) should not set the text field's value": function() {
            var eo = [new RightNow.Event.EventObject(null, ( { data: [
                {
                    key: widget.data.js.filter.key,
                    value: '*'
                }
            ]}))];
            widget.onFilterUpdate(null, eo);
            Y.Assert.areSame(this.input.get('value'), '');
        },

        "Collect event should set the page number to 1": function() {
            widget.searchSource().setOptions({page: {key: "page", type: "page", value: ''}});
            Y.Assert.areSame('', widget.searchSource().options.page.value);
            widget.searchSource().fire('collect');
            Y.Assert.areSame(1, widget.searchSource().options.page.value);
        },

    }));

    return suite;
}).run();
