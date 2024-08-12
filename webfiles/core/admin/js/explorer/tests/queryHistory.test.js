UnitTest.addSuite({
    type: UnitTest.Type.Admin,
    preloadFiles: [
        '/euf/core/admin/css/explorer/explorer.css',
        '/euf/core/admin/js/explorer/util.js',
        '/euf/core/admin/js/explorer/queryHistory.js'
    ],
    yuiModules: ['connectexplorer-query-history']
}, function(Y) {
    var suite = new Y.Test.Suite({name: 'ConnectExplorer.QueryHistory'});

    suite.add(new Y.Test.Case({
        name: 'Item storage and retrieval',

        setUp: function() {
            Y.ConnectExplorer.QueryHistory.clear();
        },

        tearDown: function() {
            Y.ConnectExplorer.QueryHistory.clear();
        },

        'Test item storage and retrieval': function() {
            Y.Assert.isNumber(Y.ConnectExplorer.QueryHistory.size());
            Y.Assert.areSame(Y.ConnectExplorer.QueryHistory.size(), 0);

            // add some items to the history
            Y.ConnectExplorer.QueryHistory.addItem("SELECT ID, LookupName FROM Contact");
            Y.ConnectExplorer.QueryHistory.addItem("SELECT Contact FROM Contact");
            Y.ConnectExplorer.QueryHistory.addItem("SELECT Object FROM Contact AS Object");
            Y.Assert.isNumber(Y.ConnectExplorer.QueryHistory.size());
            Y.Assert.areSame(Y.ConnectExplorer.QueryHistory.size(), 3);

            // remove an item and check size
            Y.ConnectExplorer.QueryHistory.removeItem("SELECT Object FROM Contact AS Object");
            Y.Assert.isNumber(Y.ConnectExplorer.QueryHistory.size());
            Y.Assert.areSame(Y.ConnectExplorer.QueryHistory.size(), 2);

            // clear all items and check size
            Y.ConnectExplorer.QueryHistory.clear();
            Y.Assert.isNumber(Y.ConnectExplorer.QueryHistory.size());
            Y.Assert.areSame(Y.ConnectExplorer.QueryHistory.size(), 0);
        },

        'Ensure history is limited to 35 items': function() {
            // try to add 40 unique queries
            for (var i = 1; i <= 40; ++i) {
                Y.ConnectExplorer.QueryHistory.addItem("SELECT Contact FROM Contact LIMIT " + i);
            }

            // size should be 35
            Y.Assert.isNumber(Y.ConnectExplorer.QueryHistory.size());
            Y.Assert.areSame(Y.ConnectExplorer.QueryHistory.size(), 35);
        },

        'Test item truncation': function() {
            Y.ConnectExplorer.QueryHistory.addItem("SELECT SomeObject FROM ServiceCategory AS SomeObject WHERE Parent IS NULL LIMIT 5");
            Y.ConnectExplorer.QueryHistory.addItem("SELECT SomeObject2 FROM ServiceCategory AS SomeObject2 WHERE Parent IS NULL LIMIT 5");

            // the first query should not be truncated, but the second should be (queries are last in first out)
            var listSelector = Y.all('.queryHistory span');
            Y.Assert.areSame(listSelector.item(1).get('title'), listSelector.item(1).get('innerText'));
            Y.Assert.areNotSame(listSelector.item(0).get('title'), listSelector.item(0).get('innerText'));
            Y.Assert.areSame(listSelector.item(0).get('innerText'), 'SELECT SomeObject2 FROM ServiceCategory AS SomeObject2 WHERE Parent I ... LIMIT 5');
        },

        'Test UI transitions': function() {
            var listWrapperSelector = Y.one('.queryHistory div');
            Y.Assert.areSame(listWrapperSelector.getStyle('display'), 'none');
            Y.Assert.areSame(listWrapperSelector.getStyle('opacity'), '0');

            Y.ConnectExplorer.QueryHistory.show();
            Y.Assert.areSame(listWrapperSelector.getStyle('display'), 'block');
            Y.Assert.areSame(listWrapperSelector.getStyle('opacity'), '1');

            Y.ConnectExplorer.QueryHistory.hide();
            Y.Assert.areSame(listWrapperSelector.getStyle('opacity'), '0');
            this.wait(function() {
                Y.Assert.areSame(listWrapperSelector.getStyle('display'), 'none');
            }, 500); // hide transition lasts 0.4 seconds, wait 500 ms for effect to finish
        }
    }));

    return suite;
}).run();

