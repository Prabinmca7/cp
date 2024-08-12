UnitTest.addSuite({
    type: UnitTest.Type.Admin,
    preloadFiles: [
        '/euf/core/admin/js/deploy/deploy.js',
        '/euf/core/admin/js/deploy/filesTable.js',
        '/euf/core/admin/js/deploy/versionsTable.js',
        '/euf/core/admin/js/deploy/pageSetTable.js',
        '/euf/core/admin/js/deploy/stage.js',
        '/euf/core/admin/css/deploy/deploy.css'
    ]
}, function(Y) {
    var suite = new Y.Test.Suite({ name: "UI Behavior tests for the step 4 - staging page" });

    // Ideally this could always assert a pristine state where there's no touched files in development,
    // but in practice, when running on dev sites there's usually always dirty files.
    suite.add(new Y.Test.Case({
        name: "Initial page state",

        "Staging tab is selected": function() {
            Y.assert(Y.one('#stage').hasClass('selected'));
        },

        "File table either doesn't exist (no files to stage) or has files to stage": function() {
            if (window.fileData) {
                Y.assert(Y.one('#selectedFilesContent'));
                Y.assert(Y.one('#selectedFilesContent table'));
            }
            else {
                Y.assert(!Y.one('#selectedFilesContent'));
                Y.assert(!Y.one('#selectedFilesContent table'));
                Y.assert(Y.one('#fileTable .info'));
            }
        },

        "Page set table either doesn't exist (no mappings to stage) or has mappings to stage": function() {
            if (window.pageSetColumns) {
                Y.assert(Y.one('#pageSetContent'));
                Y.assert(Y.one('#pageSetContent table'));
            }
            else {
                Y.assert(!Y.one('#pageSetContent'));
                Y.assert(!Y.one('#pageSetContent table'));
                Y.assert(Y.one('#pageSetTable .info'));
            }
        },

        "Framework table either doesn't exist (no version to stage) or has a version to stage": function() {
            if (window.frameworkDestinationVersions) {
                Y.assert(Y.one('#frameworkVersionContent'));
                Y.assert(Y.one('#frameworkVersionContent table'));
            }
            else {
                Y.assert(!Y.one('#frameworkVersionContent'));
                Y.assert(!Y.one('#frameworkVersionContent table'));
                Y.assert(Y.one('#versionsTable .info'));
            }
        },

        "Widget table either doesn't exist (no versions to stage) or has versions to stage": function() {
            if (window.versionDataSelection) {
                Y.assert(Y.one('#widgetVersionsContent'));
                Y.assert(Y.one('#widgetVersionsContent table'));
            }
            else {
                Y.assert(!Y.one('#widgetVersionsContent'));
                Y.assert(!Y.one('#widgetVersionsContent table'));
                Y.assert(Y.one('#versionsTable .info'));
            }
        },

        "Submit button is disabled because the page wasn't POSTed to": function() {
            Y.assert(Y.one('#stageSubmit').get('disabled'));
            Y.assert(Y.one('#commentEnter').get('disabled'));
        }
    }));

    suite.add(new Y.Test.Case({
        name: "UI tests",

        "Stage button and comment entry are enabled when re-initialize checkbox is checked": function() {
            Y.assert(Y.one('#stageSubmit').get('disabled'));
            Y.assert(Y.one('#commentEnter').get('disabled'));
            Y.one('#stageInitialize').simulate('click');
            Y.assert(!Y.one('#stageSubmit').get('disabled'));
            Y.assert(!Y.one('#commentEnter').get('disabled'));
        },

        "Stage button stays enabled after cancelling the confirm dialog": function() {
            Y.one('#stageSubmit').simulate('click');
            // Wait for the ajax request to get a lock completes
            this.wait(function() {
                Y.assert(Y.one('.yui3-panel'));
                Y.one('.yui3-panel button.cancelButton').simulate('click');
                // wait for ajax request to unlock completes
                this.wait(function() {
                    Y.Assert.isFalse(Y.one('#stageSubmit').get('disabled'));
                }, 1000);
                // Panel is destroyed
                Y.assert(!Y.one('.yui3-panel'));
            }, 1000);
        }
    }));

    return suite;
}).run();
