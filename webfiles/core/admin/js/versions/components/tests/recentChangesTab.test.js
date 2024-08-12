var messages = { noRecentChanges: 'nope' };

UnitTest.addSuite({
    type: UnitTest.Type.Admin,
    yuiModules: ['RecentChangesTab'],
    preloadFiles: [
        '/euf/core/admin/js/versions/components/tests/bootstrap.js',
        '/euf/core/admin/js/versions/components/tabs.js',
        '/euf/core/admin/js/versions/components/component.js',
        '/euf/core/admin/js/versions/components/helpers.js',
        '/euf/core/admin/js/versions/components/recentChangesTab.js'
    ]
}, function(Y) {
    var suite = new Y.Test.Suite({ name: "Recent changes tab" });
    Y.one('#history').setHTML('<div id="historyPanel"></div>');

    suite.add(new Y.Test.Case({
        name: "Rendering",

        "Change details are rendered": function () {
            var input = [
                // no deets
                { type: 'type', time: 'time', user: 'user' },
                // deets
                { type: 'type', time: 'time', user: 'user', previous: 'previous', newVersion: 'newVersion' }
            ];

            Y.RecentChangesTab.renderHistoryEntries(input);

            var entries = Y.all('#historyPanel .log');

            Y.Assert.areSame(2, entries.size());

            var output = entries.item(0),
                expected = input[0];

            Y.Assert.areSame(expected.type, output.one('.subject').getHTML());
            Y.Assert.areSame(expected.time, output.one('.date').getHTML());
            Y.Assert.areSame(expected.user, output.one('.who').getHTML());
            Y.Assert.areSame(expected.user, output.one('.details').get('text'));

            output = entries.item(1);
            expected = input[1];

            Y.Assert.areSame(expected.type, output.one('.subject').getHTML());
            Y.Assert.areSame(expected.time, output.one('.date').getHTML());
            Y.Assert.areSame(expected.user, output.one('.who').getHTML());
            Y.Assert.areSame(expected.previous + ' → ' + expected.newVersion + expected.user, output.one('.details').get('text'));
        },

        "A message is rendered when no changes are available": function () {
            Y.RecentChangesTab.renderHistoryEntries([]);

            Y.Assert.areSame(messages.noRecentChanges, Y.one('#historyPanel .none').getHTML());
        }
    }));

    suite.add(new Y.Test.Case({
        name: "Focusing Behavior",

        tearDown: function () {
            window.location.hash = '';
        },

        "Content area is focused when content is retrieved after clicking on the tab": function () {
            var called = true,
                callback;

            Y.Helpers.ajax = function (endpoint, options) {
                called = true;
                callback = [ options.callback, options.context ];
            };

            Y.one('a[href="#history"]').simulate('click');

            Y.assert(called);

            callback[0].call(callback[1], []);

            Y.Assert.areSame(document.activeElement, document.getElementById('historyPanel'));
        },

        "Content area is focused after clicking on the tab when content is already cached": function () {
            Y.one('a[href="#history"]').simulate('click');

            this.wait(function () {
                Y.Assert.areSame(document.activeElement, document.getElementById('historyPanel'));
            }, 100);
        }
    }));

    return suite;
}).run();
