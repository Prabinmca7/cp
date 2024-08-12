UnitTest.addSuite({
    type: UnitTest.Type.Admin,
    preloadFiles: [
        '/euf/core/admin/js/deploy/deploy.js',
        '/euf/core/admin/js/deploy/filesTable.js',
        '/euf/core/admin/js/deploy/versionsTable.js',
        '/euf/core/admin/js/deploy/pageSetTable.js',
        '/euf/core/admin/js/deploy/promote.js',
        '/euf/core/admin/css/deploy/deploy.css'
    ]
}, function(Y) {
    var suite = new Y.Test.Suite({ name: "UI Behavior tests promote page" });

    suite.add(new Y.Test.Case({
        name: "UI tests",

        "Promote button and comment entry are enabled to start with": function() {
            Y.Assert.isFalse(Y.one('#promoteSubmit').get('disabled'));
            Y.Assert.isFalse(Y.one('#commentEnter').get('disabled'));
        },

        "Promote button stays enabled after cancelling the confirm dialog": function() {
            Y.one('#promoteSubmit').simulate('click');
            // Wait for the ajax request to get a lock completes
            this.wait(function() {
                Y.assert(Y.one('.yui3-panel'));
                Y.one('.yui3-panel button.cancelButton').simulate('click');
                // wait for ajax request to unlock completes
                this.wait(function() {
                    Y.Assert.isFalse(Y.one('#promoteSubmit').get('disabled'));
                }, 1000);
                // Panel is destroyed
                Y.assert(!Y.one('.yui3-panel'));
            }, 1000);
        }
    }));

    return suite;
}).run();
