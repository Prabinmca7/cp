UnitTest.addSuite({
    type: UnitTest.Type.Admin,
    preloadFiles: [
        '/euf/core/admin/css/explorer/explorer.css',
        '/euf/core/thirdParty/codemirror/lib/codemirror-min.js',
        '/euf/core/admin/js/explorer/util.js',
        '/euf/core/admin/js/explorer/queryHistory.js',
        '/euf/core/admin/js/explorer/dialogs.js',
        '/euf/core/admin/js/explorer/editor.js',
        '/euf/core/admin/js/explorer/tabView.js'
    ],
    yuiModules: ['connectexplorer-tabview']
}, function(Y) {
    var suite = new Y.Test.Suite({name: 'ConnectExplorer.TabView'});

    suite.add(new Y.Test.Case({
        name: 'Test Y.ConnectExplorer.TabView Initialization',

        'Test initialization': function() {
            var queryTabs = Y.one('#queryTabs');
            Y.Assert.isFalse(queryTabs.hasChildNodes());

            // only one instance for all tests, otherwise selected index gets messed up
            Y.ConnectExplorer.TabView.initialize();
            Y.Assert.isTrue(queryTabs.hasChildNodes());

            // make sure editor was also created, which in turn creates the results
            Y.Assert.isNotNull(Y.one('#query0'));
            Y.Assert.isNotNull(Y.one('#query0 .actionContainer'));
            Y.Assert.isNotNull(Y.one('#query0 .resultsContainer'));
            Y.Assert.isNotNull(Y.one('#editor0'));
            Y.Assert.isNotNull(Y.one('#editor0 .CodeMirror'));
        },

        'Test selected tab label': function() {
            Y.Assert.areSame('New Query', Y.one('.yui3-tab-label').get('innerText'));
            Y.ConnectExplorer.TabView.setSelectedTabLabel('foo');
            Y.Assert.areSame('foo', Y.one('.yui3-tab-label').get('innerText'));
        },

        'Adding and removing tabs': function() {
            Y.Assert.areSame(2, Y.all('.yui3-tabview-list li').size());
            Y.one('.yui3-tab-add').simulate('click');
            Y.Assert.areSame('TEXTAREA', document.activeElement.tagName);
            Y.Assert.areSame(3, Y.all('.yui3-tabview-list li').size());
            Y.one('.yui3-tab-remove').simulate('click');
            Y.Assert.areSame(2, Y.all('.yui3-tabview-list li').size());
            Y.one('.yui3-tab-add').simulate('click');
            Y.Assert.areSame('TEXTAREA', document.activeElement.tagName);
            Y.Assert.areSame(3, Y.all('.yui3-tabview-list li').size());

            // @@@ QA 130828-000102 - Closing a tab causes subsequent queries to throw errors.
            Y.ConnectExplorer.TabView.setQuery('SELECT ID FROM Account LIMIT 1');
            this.shouldTearDown = false;
            Y.Assert.areSame('SELECT ID FROM Account LIMIT 1', Y.one('#editor2 pre').get('innerText'));

            Y.one('.yui3-tab-remove').simulate('click');
            Y.Assert.areSame(2, Y.all('.yui3-tabview-list li').size());
        },

        'Test setting the query': function() {
            Y.ConnectExplorer.TabView.setQuery('SELECT foo FROM bar');
            this.shouldTearDown = false;

            Y.Assert.areSame('SELECT foo FROM bar', Y.one('#editor0 pre').get('innerText'));
            Y.ConnectExplorer.TabView.setQuery('SELECT bar FROM foo');
            Y.Assert.areSame('SELECT bar FROM foo', Y.one('#editor0 pre').get('innerText'));
        },

        'Test clear': function() {
            Y.Assert.areNotSame('', Y.one('#editor0 .CodeMirror-code pre').get('innerText'));
            Y.ConnectExplorer.TabView.clear();
            Y.Assert.areSame('&nbsp;', Y.one('#editor0 .CodeMirror-code pre').getHTML());
            Y.Assert.areSame('New Query', Y.one('.yui3-tab-label').get('innerText'));
            Y.one('.yui3-tab-label').set('innerText', 'custom label');
            Y.ConnectExplorer.TabView.clear();
            Y.Assert.areSame('New Query', Y.one('.yui3-tab-label').get('innerText'));
        }
    }));

    return suite;
}).run();