UnitTest.addSuite({
    type: UnitTest.Type.Admin,
    preloadFiles: [
        '/euf/core/admin/css/explorer/explorer.css',
        '/euf/core/admin/js/explorer/util.js',
        '/euf/core/admin/js/explorer/queryHistory.js',
        '/euf/core/admin/js/explorer/dialogs.js'
    ],
    yuiModules: ['connectexplorer-dialogs']
}, function(Y) {
    var suite = new Y.Test.Suite({name: 'ConnectExplorer.Dialogs'});

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
        },

        'Test query max limit': function(){
            var settingsDialogLink = Y.one('#settings'),
                queryLimitInput = Y.one('#defaultQueryLimit'),
                dialogCloseButton;

            //Fake this class so we don't get erroneous JS errors
            Y.ConnectExplorer.TabView = {setEditorTheme: function(){}};

            settingsDialogLink.simulate('click');

            dialogCloseButton = Y.one('#settingsDialogContainer').one('.cancelButton');

            queryLimitInput.set('value', 9000);
            dialogCloseButton.simulate('click');
            Y.Assert.areSame(parseInt(queryLimitInput.get('value'), 10), 9000);

            settingsDialogLink.simulate('click');
            queryLimitInput.set('value', 1);
            dialogCloseButton.simulate('click');
            Y.Assert.areSame(parseInt(queryLimitInput.get('value'), 10), 1);

            settingsDialogLink.simulate('click');
            queryLimitInput.set('value', 0);
            dialogCloseButton.simulate('click');
            Y.Assert.areSame(parseInt(queryLimitInput.get('value'), 10), 1);

            settingsDialogLink.simulate('click');
            queryLimitInput.set('value', -10);
            dialogCloseButton.simulate('click');
            Y.Assert.areSame(parseInt(queryLimitInput.get('value'), 10), 1);

            settingsDialogLink.simulate('click');
            queryLimitInput.set('value', 10001);
            dialogCloseButton.simulate('click');
            Y.Assert.areSame(parseInt(queryLimitInput.get('value'), 10), 10000);

            settingsDialogLink.simulate('click');
            queryLimitInput.set('value', 9999999999);
            dialogCloseButton.simulate('click');
            Y.Assert.areSame(parseInt(queryLimitInput.get('value'), 10), 10000);

            // @@@ QA 130827-000137 - if value is NaN set to default limit
            settingsDialogLink.simulate('click');
            queryLimitInput.set('value', '');
            dialogCloseButton.simulate('click');
            Y.Assert.areSame(parseInt(queryLimitInput.get('value'), 10), 25);

            settingsDialogLink.simulate('click');
            queryLimitInput.set('value', 'adsf');
            dialogCloseButton.simulate('click');
            Y.Assert.areSame(parseInt(queryLimitInput.get('value'), 10), 25);
        },

        'Test inspect making sure history is updated': function() {
            var originalUrl = window.location.href,
                linkNode = Y.Node.create('<a href="javascript:void(0)" class="link" data-field="Contact.ID" data-id="1">Contact 1</a>');
            Y.ConnectExplorer.ObjectInspector.initialize();

            // open the dialog for Contact.ID 1
            linkNode.on('click', Y.ConnectExplorer.ObjectInspector.clickInspect);
            linkNode.simulate('click');

            // make sure dialog was created correctly
            Y.Assert.isNotNull(Y.one('.inspect .yui3-widget-bd div'));
            Y.Assert.isTrue(Y.one('.inspect .yui3-widget-bd div').get('innerText').indexOf(messages.invalidFieldError) <= 0);
            Y.one('.inspect .cancelButton').simulate('click');

            // ensure history was updated correctly
            this.wait(function() {
                Y.Assert.isTrue(window.location.href.indexOf('/ci/admin/explorer/inspect/Contact.ID/1') > 0);
                Y.Assert.areNotSame(window.location.href, originalUrl);

                // reset url
                Y.ConnectExplorer.History['add']({field: null, id: null}, {url: originalUrl});
            }, 1000);
        }
    }));

    return suite;
}).run();

