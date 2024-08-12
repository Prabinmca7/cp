UnitTest.addSuite({
    type: UnitTest.Type.Admin,
    yuiModules: ['Timeline'],
    preloadFiles: [
        '/euf/core/admin/js/versions/components/tests/bootstrap.js',
        '/euf/core/admin/js/versions/components/tabs.js',
        '/euf/core/admin/js/versions/components/component.js',
        '/euf/core/admin/js/versions/components/helpers.js',
        '/euf/core/admin/js/versions/components/tooltip.js',
        '/euf/core/admin/js/versions/components/timeline.js'
    ]
}, function(Y) {
    var suite = new Y.Test.Suite({ name: "Timeline" });

    suite.add(new Y.Test.Case({
        name: "Behavior",

        testCase: function () {
            this.fail("TBI");
        }
    }));

    return suite;
}).run();
