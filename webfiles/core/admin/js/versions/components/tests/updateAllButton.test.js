UnitTest.addSuite({
    type: UnitTest.Type.Admin,
    yuiModules: ['UpdateAllButton'],
    preloadFiles: [
        '/euf/core/admin/js/versions/components/tests/bootstrap.js',
        '/euf/core/admin/js/versions/components/tabs.js',
        '/euf/core/admin/js/versions/components/component.js',
        '/euf/core/admin/js/versions/components/helpers.js',
        '/euf/core/admin/js/versions/components/versionHelper.js',
        '/euf/core/admin/js/versions/components/updateAllButton.js'
    ]
}, function(Y) {
    var suite = new Y.Test.Suite({ name: "Update all button" });

    suite.add(new Y.Test.Case({
        name: "behavior",

        testCase: function () {
            this.fail("TBI");
        }
    }));

    return suite;
}).run();
