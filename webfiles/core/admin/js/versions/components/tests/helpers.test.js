messages = {};

UnitTest.addSuite({
    type: UnitTest.Type.Admin,
    yuiModules: ['Helpers'],
    preloadFiles: [
        '/euf/core/admin/js/versions/components/tests/bootstrap.js',
        '/euf/core/admin/js/versions/components/helpers.js',
    ]
}, function(Y) {
    var suite = new Y.Test.Suite({ name: "Helpers" });

    suite.add(new Y.Test.Case({
        name: "ajax",

        testCase: function () {
            this.fail("TBI");
        }
    }));

    suite.add(new Y.Test.Case({
        name: "panel",

        "closeCallback function is called when the dialog is closed": function () {
            var called = 0;
            function callback () {
                called++;
            }

            Y.Helpers.panel({
                closeCallback: callback
            }).show();

            Y.Assert.areSame(0, called);
            Y.one('.yui3-panel button').simulate('click');
            Y.Assert.areSame(1, called);
        }
    }));

    suite.add(new Y.Test.Case({
        name: "scrollListItemIntoView",

        testCase: function () {
            this.fail("TBI");
        }
    }));

    suite.add(new Y.Test.Case({
        name: "panelHandler",

        testCase: function () {
            this.fail("TBI");
        }
    }));

    suite.add(new Y.Test.Case({
        name: "toggle",

        testCase: function () {
            this.fail("TBI");
        }
    }));

    return suite;
}).run();
