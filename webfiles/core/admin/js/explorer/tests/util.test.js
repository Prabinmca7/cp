UnitTest.addSuite({
    type: UnitTest.Type.Admin,
    preloadFiles: [
        '/euf/core/admin/css/explorer/explorer.css',
        '/euf/core/admin/js/explorer/util.js'
    ],
    yuiModules: ['connectexplorer-util']
}, function(Y) {
    var suite = new Y.Test.Suite({name: 'ConnectExplorer.Util'});

    suite.add(new Y.Test.Case({
        name: 'Test Create Panel',

        'Test panels': function() {
            var okActionHandlerCalled = false;
            Y.assert(!Y.one('.yui3-widget-buttons .cancelButton'));

            Y.ConnectExplorer.Util.createPanel({
                title: "Title",
                template: function(){return '<span id="foo">Body content</span>'},
                width: '400px',
                id: 'myCustomID',
                buttonLabel: 'Button',
                okAction: function() {
                    okActionHandlerCalled = true;
                }
            }).show();

            Y.assert(Y.one('.yui3-widget-hd').get('innerText').match(/^Title/)); // has the x button at the end
            Y.Assert.areSame(Y.one('.cancelButton').get('innerText'), "Button");
            Y.Assert.isNotNull(Y.one('#myCustomID'));

            Y.Assert.areSame(okActionHandlerCalled, false);
            Y.one('.cancelButton').simulate('click');
            Y.Assert.areSame(okActionHandlerCalled, true);

            // create a panel via showError
            Y.ConnectExplorer.Util.showError("My error message");
            Y.assert(Y.one('.yui3-widget-hd').get('innerText').indexOf(messages.error) >= 0); // has the x button at the end
            Y.Assert.areSame(Y.one('.yui3-widget-bd').get('innerText'), "My error message");
            Y.one('.cancelButton').simulate('click');
        }
    }));

    suite.add(new Y.Test.Case({
        name: 'Local Storage',

        'Test local storage': function() {
            Y.ConnectExplorer.Util.setLocalStorage("foo", "bar");
            Y.Assert.areSame(Y.ConnectExplorer.Util.getLocalStorage("foo"), "bar");

            // look in local storage and make sure the keys have the right prefix
            Y.Assert.areSame(localStorage.getItem(Y.ConnectExplorer.Util._localStoragePrefix + "foo"), "bar");
        }
    }));

    return suite;
}).run();
