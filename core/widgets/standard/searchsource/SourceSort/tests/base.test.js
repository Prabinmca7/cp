UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'SourceSort_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/searchsource/SourceSort"
    });

    suite.add(new Y.Test.Case({
        name: "Event handling and operation",

        setUp: function () {
            this.inputColumn = Y.one(baseSelector + '_Column').set('selectedIndex', -1);
            this.inputDirection = Y.one(baseSelector + '_Direction').set('selectedIndex', -1);
            widget.searchSource().on('search', function () {
                // Prevent the search from happening.
                return false;
            });
        },

        "Nothing is returned in response to 'collect' if nothing is selected": function () {
            var called = false;
            widget.searchSource().once('searchCancelled', function (e, args) {
                called = true;
                Y.Assert.isFalse('sort' in args[0]);
                Y.Assert.isFalse('direction' in args[0]);
            });
            widget.searchSource().fire('collect').fire('search');
            Y.Assert.isTrue(called);
        },

        "Value is returned in response to 'collect' if something is selected": function () {
            var called = false;
            this.inputColumn.set('value', 1);
            this.inputDirection.set('value', 2);
            widget.searchSource().once('searchCancelled', function (e, args) {
                called = true;
                Y.Assert.areSame(widget.data.js.filter_column.key, args[0].sort.key);
                Y.Assert.areSame(widget.data.js.filter_column.type, args[0].sort.type);
                Y.Assert.areSame('1', args[0].sort.value);
                Y.Assert.areSame(widget.data.js.filter_direction.key, args[0].direction.key);
                Y.Assert.areSame(widget.data.js.filter_direction.type, args[0].direction.type);
                Y.Assert.areSame('2', args[0].direction.value);
            });
            widget.searchSource().fire('collect').fire('search');
            Y.Assert.isTrue(called);
        },

        "Value is not returned in response to 'collect' if one of the dropdowns is empty": function () {
            var called = false;
            this.inputColumn.set('value', 1);
            this.inputDirection.set('value', -1);
            widget.searchSource().once('searchCancelled', function (e, args) {
                called = true;
                Y.Assert.isFalse('sort' in args[0]);
                Y.Assert.isFalse('direction' in args[0]);
            });
            widget.searchSource().fire('collect').fire('search');
            Y.Assert.isTrue(called);
        },

        "Dropdown's value is updated in response to 'updateFilters' event": function () {
            widget.searchSource()
                .fire('updateFilters', new RightNow.Event.EventObject(null, { data: [
                    {
                        key: widget.data.js.filter_column.key,
                        value: 1
                    },
                    {
                        key: widget.data.js.filter_direction.key,
                        value: 2
                    }
                ]}));

            Y.Assert.areEqual(1, this.inputColumn.get('value'));
            Y.Assert.areEqual(2, this.inputDirection.get('value'));
        },

        "Dropdown's value is set to empty in response to 'updateFilters' event without sort or direction filters": function () {
            widget.searchSource()
                .fire('updateFilters', new RightNow.Event.EventObject(null, { data: [] }));

            Y.Assert.areEqual(-1, this.inputColumn.get('value'));
            Y.Assert.areEqual(-1, this.inputDirection.get('value'));
        },

        "Dropdown's value is updated to initial value in response to 'reset' event": function () {
            widget.initialValueColumn = 2;
            widget.initialValueDirection = 1;
            widget.searchSource().fire('reset');
            Y.Assert.areEqual(widget.initialValueColumn, this.inputColumn.get('value'));
            Y.Assert.areEqual(widget.initialValueDirection, this.inputDirection.get('value'));
        }
    }));

    return suite;
}).run();
