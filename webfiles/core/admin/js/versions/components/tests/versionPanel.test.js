var messages = { levels: {}};

!function (doc, win) {
    win.messages = {
        levels: {
            major: 'maj',
            minor: 'min',
            nano: 'nan'
        },
        noChangelog: 'nope',
        categories: {},
        more: 'more'
    };
}(document, window);

UnitTest.addSuite({
    type: UnitTest.Type.Admin,
    yuiModules: ['VersionPanel'],
    preloadFiles: [
        '/euf/core/admin/js/versions/components/tests/bootstrap.js',
        '/euf/core/admin/js/versions/components/tabs.js',
        '/euf/core/admin/js/versions/components/component.js',
        '/euf/core/admin/js/versions/components/helpers.js',
        '/euf/core/admin/js/versions/components/tooltip.js',
        '/euf/core/admin/js/versions/components/markdown.js',
        '/euf/core/admin/js/versions/components/versionPanel.js'
    ]
}, function(Y) {
    var suite = new Y.Test.Suite({ name: "Version panel" });

    suite.add(new Y.Test.Case({
        name: "changelog construction",

        testCase: function () {
            this.fail("TBI");
        }
    }));

    return suite;
}).run();
