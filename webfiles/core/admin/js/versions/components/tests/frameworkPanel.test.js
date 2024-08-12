!function (doc, win) {
    var fixture = doc.createElement('button');
    fixture.id = 'frameworkDetails';
    doc.body.appendChild(fixture);

    fixture = doc.createElement('div');
    fixture.id = 'frameworks';
    doc.body.appendChild(fixture);

}(document, window);

UnitTest.addSuite({
    type: UnitTest.Type.Admin,
    yuiModules: ['Tabs', 'FrameworkPanel'],
    preloadFiles: [
        '/euf/core/admin/js/versions/components/tests/bootstrap.js',
        '/euf/core/admin/js/versions/components/versionHelper.js',
        '/euf/core/admin/js/versions/components/listFocusManager.js',
        '/euf/core/admin/js/versions/components/tabs.js',
        '/euf/core/admin/js/versions/components/component.js',
        '/euf/core/admin/js/versions/components/helpers.js',
        '/euf/core/admin/js/versions/components/frameworkPanel.js'
    ]
}, function(Y) {
    var suite = new Y.Test.Suite({ name: "Framework version panel" });

    suite.add(new Y.Test.Case({
        name: "Behavior",

        testCase: function () {
            this.fail("TBI");
        }
    }));

    return suite;
}).run();
