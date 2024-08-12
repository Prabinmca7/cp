UnitTest.addSuite({
    type: UnitTest.Type.Admin,
    yuiModules: ['PreviewImageDialog'],
    preloadFiles: [
        '/euf/core/admin/js/versions/components/tests/bootstrap.js',
        '/euf/core/admin/js/versions/components/tabs.js',
        '/euf/core/admin/js/versions/components/component.js',
        '/euf/core/admin/js/versions/components/helpers.js',
        '/euf/core/admin/js/versions/components/previewImageDialog.js'
    ]
}, function(Y) {
    var suite = new Y.Test.Suite({ name: "Preview image dialog" });

    suite.add(new Y.Test.Case({
        name: "Behavior",

        testCase: function () {
            this.fail("TBI");
        }
    }));

    return suite;
}).run();
