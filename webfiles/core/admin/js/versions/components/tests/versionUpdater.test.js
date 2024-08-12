!function (doc, win) {
    var button = doc.createElement('button');
    button.id = 'updateWidgetButton';
    doc.body.appendChild(button);

    var select = doc.createElement('select');
    select.id = 'updateVersion';
    doc.body.appendChild(select);

    win.messages = {
        inUse: 'bananas'
    };
}(document, window);

UnitTest.addSuite({
    type: UnitTest.Type.Admin,
    yuiModules: ['VersionUpdater'],
    preloadFiles: [
        '/euf/core/admin/js/versions/components/tests/bootstrap.js',
        '/euf/core/admin/js/versions/components/tabs.js',
        '/euf/core/admin/js/versions/components/component.js',
        '/euf/core/admin/js/versions/components/helpers.js',
        '/euf/core/admin/js/versions/components/versionUpdater.js'
    ]
}, function(Y) {
    var suite = new Y.Test.Suite({ name: "Widget version updater" });

    suite.add(new Y.Test.Case({
        name: "Updating widget versions",

        testCase: function () {
            this.fail("TBI");
        }
    }));

    return suite;
}).run();
