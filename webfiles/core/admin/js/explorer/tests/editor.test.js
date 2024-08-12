UnitTest.addSuite({
    type: UnitTest.Type.Admin,
    preloadFiles: [
        '/euf/core/admin/css/explorer/explorer.css',
        '/euf/core/thirdParty/codemirror/lib/codemirror-min.js',
        '/euf/core/admin/js/explorer/util.js',
        '/euf/core/admin/js/explorer/queryHistory.js',
        '/euf/core/admin/js/explorer/dialogs.js',
        '/euf/core/admin/js/explorer/editor.js'
    ],
    yuiModules: ['connectexplorer-editor']
}, function(Y) {
    var suite = new Y.Test.Suite({name: 'ConnectExplorer.Editor'});

    suite.add(new Y.Test.Case({
        name: 'Test Y.ConnectExplorer.Editor',
        originalAjax: null,

        createContent: function(instanceID) {
            var template = new Y.Template().compile(Y.one('#editorView').getHTML()),
                _instanceID = instanceID || 1,
                fakeTabView = {
                    setSelectedTabLabel: function(label) {
                        return label;
                    }
                };
            Y.one(document.body).append(template({index: _instanceID}));
            return new Y.ConnectExplorer.Editor(_instanceID, fakeTabView);
        },

        destroyContent: function(instanceID) {
            var _instanceID = instanceID || 1;
            Y.one(document.body).removeChild(Y.one('#query' + _instanceID));
        },

        'Create an editor': function() {
            // should not have any markup to begin with
            Y.Assert.isNull(Y.one('#query123'));
            Y.Assert.isNull(Y.one('#query123 .actionContainer'));
            Y.Assert.isNull(Y.one('#query123 .resultsContainer'));
            Y.Assert.isNull(Y.one('#editor123'));
            Y.Assert.isNull(Y.one('#editor123 .CodeMirror'));

            // check for markup
            var editor = this.createContent(123);
            Y.Assert.areSame(editor.instanceID, 123);
            Y.Assert.isNotNull(Y.one('#query123'));
            Y.Assert.isNotNull(Y.one('#query123 .actionContainer'));
            Y.Assert.isNotNull(Y.one('#query123 .resultsContainer'));
            Y.Assert.isNotNull(Y.one('#editor123'));
            Y.Assert.isNotNull(Y.one('#editor123 .CodeMirror'));

            // cleanup
            this.destroyContent(123);
        },

        'Test values': function() {
            var editor = this.createContent();

            Y.Assert.areSame(editor.getValue(), '');
            editor.setValue('asdf');
            Y.Assert.areSame(editor.getValue(), 'asdf');
            editor.setValue('a?url encoded value');
            Y.Assert.areSame(editor.getValue(), 'a%3Furl%20encoded%20value');

            this.destroyContent();
        },

        'Test setLoading': function() {
            var editor = this.createContent(1);
            Y.Assert.isNotNull(Y.one('#resultsLoading1'));
            Y.Assert.isTrue(Y.one('#resultsLoading1').hasClass('resultsLoading'));
            Y.Assert.isFalse(Y.one('#resultsLoading1').hasClass('rn_Loading'));

            editor.setLoading(true);
            Y.Assert.isTrue(Y.one('#resultsLoading1').hasClass('rn_Loading'));

            editor.setLoading(false);
            Y.Assert.isFalse(Y.one('#resultsLoading1').hasClass('rn_Loading'));
            this.destroyContent(1);
        },

        'Test formatter': function() {
            var data = {
                    _ID_: '321',
                    _LINK_: 'the link',
                },
                editor = this.createContent(),
                result = editor.formatter({data: data, value: 'foo'});

            // without a title
            Y.Assert.areSame('<a href="javascript:void(0);" class="link" data-field="' + data._LINK_ + '" data-id="' + data._ID_ + '">foo</a>', result);

            // with a title
            data._TITLE_ = 'asdf';
            result = editor.formatter({data: data, value: 'bar'});
            Y.Assert.areSame(result, '<a href="javascript:void(0);" class="link" data-field="' + data._LINK_ + '" data-id="' + data._ID_ + '" title="' + data._TITLE_ + data._ID_ + '">bar</a>');

            this.destroyContent();
        },

        'Test results handling': function() {
            var results = {
                    data: {
                        duration: 0.0061,
                        limit: 3,
                        objectName: "Contact",
                        offset: 0,
                        query: "SELECT ID, LookupName FROM Contact LIMIT 3",
                        queryType: "select",
                        total: 3,
                        columns: [
                            {key: "ID"},
                            {key: "LookupName"}
                        ],
                        results: [
                            {ID: "1", LookupName: "Some Guy", _ID_: "1", _LINK_: "Contact", _TITLE_: "Inspect Contact "},
                            {ID: "2", LookupName: "Some Other Guy", _ID_: "2", _LINK_: "Contact", _TITLE_: "Inspect Contact "},
                            {ID: "3", LookupName: "Some Random Guy", _ID_: "3", _LINK_: "Contact", _TITLE_: "Inspect Contact "}
                        ]
                    },
                    callback: {}
                },
                editor = this.createContent();

            Y.Assert.areSame(editor.dataTable.data.size(), 0);
            Y.Assert.areSame(editor.dataTable.view.displayColumns.length, 0);
            editor.handleResults.call(editor, results);
            Y.Assert.areSame(editor.dataTable.data.size(), 3, 'Expecting three results in datatable');
            Y.Assert.areSame(editor.dataTable.view.displayColumns.length, 2, 'Expecting two columns in datatable');
            // first column should contain a link
            Y.Assert.areSame(Y.all('.yui3-datatable-col-ID a').size(), 3, 'Expecting three results to contain links in the first column');

            // make sure there are pagination buttons
            Y.Assert.isNotNull(Y.one('#nextPage1'));
            Y.Assert.isFalse(Y.one('#nextPage1').hasClass('hidden'));

            Y.Assert.isNotNull(Y.one('#previousPage1'));
            Y.Assert.isFalse(Y.one('#previousPage1').hasClass('hidden'));

            //Make sure warning div is hidden
            Y.Assert.isTrue(Y.one(".descWarning").hasClass('hide'));
            Y.Assert.isTrue(Y.one(".showWarning").hasClass('hide'));

            // check the caption
            Y.Assert.areSame(Y.one('#caption1').get('innerText'), '3 results returned in 0.0061 seconds');

            // test method chaining and clearing results
            editor.setValue('').clearResults();

            // with a non-select query
            results = {
                data: {
                    duration: 0.0025,
                    limit: null,
                    objectName: "Contact",
                    offset: null,
                    query: "DESC Contact",
                    queryType: "describe",
                    total: 2,
                    columns: [
                        {key: "Field"},
                        {key: "Type"},
                        {key: "Null"},
                        {key: "Default"}
                    ],
                    results: [
                        {Default: "NULL", Field: "ID", Null: "No", Type: "long", _ID_: "ID", _LINK_: "Contact.ID", _TITLE_: "Inspect Contact."},
                        {Default: "NULL", Field: "LookupName", Null: "No", Type: "string", _ID_: "LookupName", _LINK_: "Contact.LookupName", _TITLE_: "Inspect Contact."}
                    ]
                },
                callback: {}
            };

            editor.clearResults();
            Y.Assert.areSame(editor.dataTable.data.size(), 0);
            Y.Assert.areSame(editor.dataTable.view.displayColumns.length, 0);

            editor.handleResults.call(editor, results);
            Y.Assert.areSame(editor.dataTable.data.size(), 2);
            Y.Assert.areSame(editor.dataTable.view.displayColumns.length, 4);
            // first column should contain a link
            Y.Assert.areSame(Y.all('.yui3-datatable-col-Field a').size(), 2);

            // make sure there are pagination buttons
            Y.Assert.isNotNull(Y.one('#nextPage1'));
            Y.Assert.isTrue(Y.one('#nextPage1').hasClass('hidden'));

            Y.Assert.isNotNull(Y.one('#previousPage1'));
            Y.Assert.isTrue(Y.one('#previousPage1').hasClass('hidden'));

            //Make sure warning div is present - http://media0.giphy.com/media/Rhhr8D5mKSX7O/giphy.gif
            Y.Assert.isFalse(Y.one(".descWarning").hasClass('hide'));
            Y.Assert.isTrue(Y.one(".showWarning").hasClass('hide'));

            // check the caption
            Y.Assert.areSame(Y.one('#caption1').get('innerText'), '2 results returned in 0.0025 seconds');

            // with columns containing periods
            results = {
                data: {
                    duration: 0.0025,
                    limit: null,
                    objectName: "Contact",
                    offset: null,
                    query: "DESC Contact",
                    queryType: "describe",
                    total: 2,
                    columns: [
                        {key: "ABC"},
                        {key: "ABC.DEF"}
                    ],
                    results: [
                        {'ABC': "ABC1", 'ABC.DEF': "ABC.DEF1", _ID_: "ID", _LINK_: "Contact.ID", _TITLE_: "Inspect Contact."},
                        {'ABC': "ABC2", 'ABC.DEF': "ABC.DEF2", _ID_: "LookupName", _LINK_: "Contact.LookupName", _TITLE_: "Inspect Contact."}
                    ]
                },
                callback: {}
            };

            editor.clearResults();
            Y.Assert.areSame(editor.dataTable.data.size(), 0);
            Y.Assert.areSame(editor.dataTable.view.displayColumns.length, 0);

            editor.handleResults.call(editor, results);
            Y.Assert.areSame(editor.dataTable.data.size(), 2);
            Y.Assert.areSame(editor.dataTable.view.displayColumns.length, 2);
            Y.Assert.areSame(editor.dataTable.view.displayColumns[0].key, 'ABC');
            Y.Assert.areSame(editor.dataTable.view.displayColumns[1].key, 'ABC_DEF');

            // verify column display is changed
            var columnHeadings = Y.all('.yui3-datatable-columns .yui3-datatable-sort-liner').get('text');
            Y.Assert.areSame(columnHeadings.length, 2);
            Y.Assert.areSame(columnHeadings[0], 'ABC');
            Y.Assert.areSame(columnHeadings[1], 'ABC_DEF');

            // verify row data displays and is not changed
            var rowContent = Y.all('.yui3-datatable-data .yui3-datatable-cell').get('text');
            Y.Assert.areSame(rowContent.length, 4);
            Y.Assert.areSame(rowContent[0], 'ABC1');
            Y.Assert.areSame(rowContent[1], 'ABC.DEF1');
            Y.Assert.areSame(rowContent[2], 'ABC2');
            Y.Assert.areSame(rowContent[3], 'ABC.DEF2');

            this.destroyContent();
        },

        'Test warning toggle': function() {
            var editor = this.createContent();
            editor.showWarning('describe');
            Y.Assert.isFalse(Y.one(".descWarning").hasClass('hide'));

            editor.showWarning('show');
            Y.Assert.isFalse(Y.one(".showWarning").hasClass('hide'));

            editor.clearWarnings();
            Y.Assert.isTrue(Y.one(".descWarning").hasClass('hide'));
            Y.Assert.isTrue(Y.one(".showWarning").hasClass('hide'));

            editor.showWarning('whatever');
            Y.Assert.isFalse(Y.one(".showWarning").hasClass('hide'));

            editor.clearWarnings();
        }
    }));

    return suite;
}).run();
