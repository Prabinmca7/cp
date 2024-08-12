// Mock server requests
window.Ajax = {
    waiting: function () {
        return false;
    },
    post: function (destination, callback, postVars) {
        var parts = destination.split('/'),
            id = parts[1] || 10001,
            response = {
                message: 'success',
                id: id
            };

        if (parts[0] === 'enablePageSet') {
            callback.apply(null, [(parts[2] ? true : false), {responseText: JSON.stringify(response)}]);
        }
        else {
            callback.apply(null, [id, {responseText: JSON.stringify(response)}]);
        }
    },
};

UnitTest.addSuite({
    type: UnitTest.Type.Admin,
    preloadFiles: [
        '/euf/core/admin/js/configurations/pageSetMapping.js',
        '/euf/core/admin/css/configurations/pageSetMapping.css'
    ]
}, function(Y) {
    var suite = new Y.Test.Suite({ name: "UI Behavior tests for page set mappings" }),
        firstRow = function () {
            return Y.one('table').all('tr').slice(1).item(0);
        },

        lastRow = function () {
            return Y.one('table').all('tr').slice(-1).item(0);
        },

        numberOfRows = function () {
            return Y.one('table').all('tr').size();
        },

        addRow = function () {
            return Y.one('#addPageSetButton').simulate('click');
        },

        toggleState = function (currentState) {
            Y.one('table').all('a[data-enable="' + currentState + '"]').each(function (node) {
                node.simulate('click');
            });
        };

    suite.add(new Y.Test.Case({
        name: "Tests for table page set state toggle",

        setUp: function () {
            this.showAllLink = Y.one('#showAll');
            this.showEnabledLink = Y.one('#showEnabled');
            this.mappingTable = Y.one('#mappingTable');
        },

        tearDown: function () {
            window.localStorage.removeItem('adminShowMappings');
        },

        "Selected view link is not clickable": function () {
            Y.assert(this.showAllLink.hasClass('selected'));
            Y.assert(!this.mappingTable.hasClass('hideDisabled'));
            this.showAllLink.simulate('click');
            Y.assert(this.showAllLink.hasClass('selected'));
            Y.assert(!this.mappingTable.hasClass('hideDisabled'));
            Y.assert(!window.localStorage.getItem('adminShowMappings'));
        },

        "Clicking non-selected view link changes what's displayed": function () {
            this.showEnabledLink.simulate('click');
            Y.assert(this.showEnabledLink.hasClass('selected'));
            Y.assert(!this.showAllLink.hasClass('selected'));
            Y.assert(this.mappingTable.hasClass('hideDisabled'));
            Y.Assert.areSame('enabled', window.localStorage.getItem('adminShowMappings'));
        }
    }));

    suite.add(new Y.Test.Case({
        name: "Adding a new row",

        tearDown: function () {
            Y.one('table').all('tr[data-new-row]').remove();
        },

        "Empty new row appears when 'add mapping' button is clicked": function () {
            var rows = numberOfRows();
            addRow();
            Y.Assert.areSame(rows + 1, numberOfRows());

            var newRow = lastRow();
            Y.Assert.areSame('true', newRow.getAttribute('data-new-row'));
            Y.Assert.areSame('custom_1', newRow.get('id'));
        },

        "Delete removes the row": function () {
            addRow();
            var newRow = lastRow(),
                rows = numberOfRows();

            newRow.one('.delete').simulate('click');
            this.wait(function () {
                Y.Assert.areSame(rows - 1, numberOfRows());
            }, 500);
        },

        "Can't save an empty row": function () {
            addRow();
            var newRow = lastRow(),
                rows = numberOfRows();
            newRow.one('.save').simulate('click');
            this.wait(function() {
                Y.Assert.areSame(rows, numberOfRows());
                Y.assert(Y.one('#flashMessage').hasClass('error'));
            }, 500);
        },
        "A new custom page set does not clobber a standard page set that was just enabled": function () {
            var standardMapping = firstRow(),
                button = standardMapping.one('a');

            button.simulate('click'); // Enable
            this.wait(function() {
                addRow();
                    var newRow = lastRow(),
                        id = newRow.get('id').split('_')[1];

                    newRow.all('input').each(function(item) {
                        if (item.get('id') === id + '_item') {
                            item.set('value', '/foo/i');
                        }
                        else if (item.get('id') === id + '_description') {
                            item.set('value', 'custom foo');
                        }
                        else if (item.get('id') === id + '_value') {
                            item.set('value', 'mobile');
                        }
                    }, this);

                    newRow.one('.save').simulate('click');
                    Y.Assert.areSame(standardMapping, firstRow());
                    Y.one('table').one('.delete').simulate('click');
                    button.simulate('click'); // Disable
            }, 500);
        }
    }));

    suite.add(new Y.Test.Case({
        name: "Enable / Disable state",

        tearDown: function () {
            toggleState(1);
        },

        "Clicking disable disables the mapping": function () {
            var button = Y.one('table').one('a[data-enable="0"]');

            button.simulate('click');
            this.wait(function() {
                Y.Assert.areSame('Enable', button.get('innerHTML'));
            }, 500);
        }

    }));

    return suite;
}).run();
