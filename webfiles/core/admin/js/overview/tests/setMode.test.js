YUI().use('node', function(Y) {
    // Redefine hideShowPanel and toggle from template.js, otherwise we have to include template.js and create all the HTML it expects.
    window.hideShowPanel = function(e, panel) {
        if (e === false) {
            panel.addClass("hide");
            return;
        }
        panel.toggleClass("hide");
        if (!panel.hasClass("hide")) {
            e.halt();
            Y.one(document.body).once("click", function() {
                window.hideShowPanel(false, panel);
            });
        }
    };
    window.toggle = function(elementOrID) {
        Y.all(elementOrID).toggleClass("hide");
    };
    
    UnitTest.addSuite({
        type: UnitTest.Type.Admin,
        preloadFiles: [
            '/euf/core/admin/js/overview/setMode.js',
            '/euf/core/admin/css/overview/setMode.css'
        ]
    }, function(Y) {
        var suite = new Y.Test.Suite({name: 'setMode tests'});

        suite.add(new Y.Test.Case({
            name: 'General functionality tests',

            "Abuse detection checkbox should get unchecked when an option other than development is selected from the site mode menu": function() {
                Y.one('#modeSelection a[data-value="development"]').simulate('click');
                Y.one('#enableAbuse').set('checked', true);
                Y.Assert.isTrue(Y.one('#enableAbuse').get('checked'));
                Y.one('#modeSelection a[data-value="production"]').simulate('click');
                Y.Assert.isFalse(Y.one('#enableAbuse').get('checked'));
            },
            "Abuse detection checkbox should remain checked when an option is selected from the page set menu": function() {
                Y.one('#modeSelection a[data-value="development"]').simulate('click');
                Y.one('#enableAbuse').set('checked', true);
                Y.one('#userAgent').set('checked', false);
                Y.one('#pageSelection a[data-value="/"]').simulate('click');
                Y.Assert.isTrue(Y.one('#enableAbuse').get('checked'));
            },
            "Abuse detection label should be grayed out when an option other than development is selected from the site mode menu": function() {
                var label = Y.one('#enableAbuseLabel');
                Y.one('#modeSelection a[data-value="development"]').simulate('click');
                Y.Assert.isFalse(label.hasClass('disabled'));
                Y.one('#modeSelection a[data-value="production"]').simulate('click');
                Y.Assert.isTrue(label.hasClass('disabled'));
            },
        }));

        return suite;

    }).run();
});
