UnitTest.addSuite({
    type: UnitTest.Type.Admin,
    yuiModules: ['VersionHelper'],
    preloadFiles: ['/euf/core/admin/js/versions/components/versionHelper.js']
}, function(Y) {
    var suite = new Y.Test.Suite({ name: "Version helpers" });

    suite.add(new Y.Test.Case({
        name: "compareVersionNumbers",

        "Returns zero when versions are the same": function () {
            Y.Assert.areSame(0, Y.VersionHelper.compareVersionNumbers('0.0.0', '0.0.0'));
            Y.Assert.areSame(0, Y.VersionHelper.compareVersionNumbers('1.0.1', '1.0.1'));
            Y.Assert.areSame(0, Y.VersionHelper.compareVersionNumbers('1.0', '1.0'));
        },

        "Returns -1 when first param is less than second param": function () {
            Y.Assert.areSame(-1, Y.VersionHelper.compareVersionNumbers('1.0.1', '1.0.2'));
            Y.Assert.areSame(-1, Y.VersionHelper.compareVersionNumbers('1.0.1', '1.1'));
            Y.Assert.areSame(-1, Y.VersionHelper.compareVersionNumbers('1.0.1', '1.1.0'));
        },

        "Returns 1 when first param is greater than second param": function () {
            Y.Assert.areSame(1, Y.VersionHelper.compareVersionNumbers('1.0.2', '1.0.1'));
            Y.Assert.areSame(1, Y.VersionHelper.compareVersionNumbers('1.1', '1.0.1'));
            Y.Assert.areSame(1, Y.VersionHelper.compareVersionNumbers('1.1.0', '1.0.1'));
        }
    }));

    suite.add(new Y.Test.Case({
        name: "hasCompatibleFramework",

        "Returns true by default": function () {
            Y.Assert.isTrue(Y.VersionHelper.hasCompatibleFramework());
        },

        "Returns false when first param doesn't contain second param": function () {
            Y.Assert.isFalse(Y.VersionHelper.hasCompatibleFramework('3.1.0', '4.2.0'));
            Y.Assert.isFalse(Y.VersionHelper.hasCompatibleFramework(['3.1.0', '4.2.2'], '4.2.0'));
            Y.Assert.isFalse(Y.VersionHelper.hasCompatibleFramework('3.1.0, 4.2.2', '4.2.0'));
        },

        "Returns true when first param contains second param": function () {
            Y.Assert.isTrue(Y.VersionHelper.hasCompatibleFramework('3.1.0', '3.1.0'));
            Y.Assert.isTrue(Y.VersionHelper.hasCompatibleFramework(['3.1.0', '4.2.0'], '4.2.0'));
            Y.Assert.isTrue(Y.VersionHelper.hasCompatibleFramework('3.1.0, 4.2.0', '4.2.0'));
        }
    }));

    return suite;
}).run();
