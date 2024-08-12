messages = { print: 'print' };

UnitTest.addSuite({
    type: UnitTest.Type.Admin,
    yuiModules: ['WidgetDocDialog'],
    preloadFiles: [
        '/euf/core/admin/js/versions/components/tests/bootstrap.js',
        '/euf/core/admin/js/versions/components/tabs.js',
        '/euf/core/admin/js/versions/components/component.js',
        '/euf/core/admin/js/versions/components/helpers.js',
        '/euf/core/admin/js/versions/components/widgetDocDialog.js'
    ]
}, function(Y) {
    var suite = new Y.Test.Suite({ name: "Widget docs dialog" });

    suite.add(new Y.Test.Case({
        name: "Behavior",

        tearDown: function () {
            Y.all('.yui3-panel,.yui3-widget-mask').remove();
        },

        "Opens and requests doc content when instantiated": function () {
            var called = false;

            Y.Helpers.ajax = function () {
                called = true;
            };

            new Y.WidgetDocDialog('foo/bar/Baz', '3.4');

            Y.assert(called);
            var panels = Y.all('.yui3-panel');
            Y.Assert.areSame(1, panels.size());
            Y.assert(Y.one('.yui3-widget-hd').get('text').indexOf('Baz - 3.4') > -1);
            Y.Assert.areSame(Y.WidgetDocDialog.templates.waiting, Y.one('.yui3-widget-bd').getHTML());
        },

        "Calls the callback when the dialog closes": function () {
            var called = false;
            new Y.WidgetDocDialog('foo/bar/Baz', '3.4', function () {
                called = true;
            });

            Y.assert(!called);

            Y.one('.yui3-panel button').simulate('click');

            Y.assert(called);

            Y.Assert.areSame(0, Y.all('.yui3-panel').size());
        },

        "Closes when a no-op link is clicked on": function () {
            var dialog = new Y.WidgetDocDialog('foo/bar/Baz', '3.4');

            dialog._onDocContentReceived('<a class="bananas" href="javascript:;">hullo</a>');
            Y.one('.bananas').simulate('click');
            Y.Assert.areSame(1, Y.all('.yui3-panel').size());

            dialog._onDocContentReceived('<a class="bananas" href="#">hullo</a>');
            Y.one('.bananas').simulate('click');
            Y.Assert.areSame(0, Y.all('.yui3-panel').size());
        },

        "Hides / shows accordion divs": function () {
            var dialog = new Y.WidgetDocDialog('foo/bar/Baz', '3.4');

            dialog._onDocContentReceived('<h2>hullo</h2><div class="hide"></div><div class="bucketName">bucket</div><div class="hide"></div><div class="attributeToggle">bucket</div><div class="hide"></div>');

            Y.Array.each(['h2', '.bucketName', '.attributeToggle'], function (selector) {
                var target = Y.one(selector);
                target.simulate('click');

                Y.assert(target.hasClass('selected'), 'Error with ' + selector);
                Y.assert(!target.next().hasClass('hide'), 'Error with ' + selector + '\'s sibling');
            });
        },

        "Resizes properly": function () {
            var dialog = new Y.WidgetDocDialog('foo/bar/Baz', '3.4');
            dialog._onDocContentReceived('<div></div>');
            Y.Assert.areSame('0px', Y.one('.widgetDetails').getStyle('height'));
 
            dialog._onDocContentReceived(new Array(500).join('<br>'));
            Y.assert(parseInt(Y.one('.widgetDetails').getStyle('height'), 10) > 100);
        },

        // for QA 200224-000123
        "Can handle html markup in version": function () {
            var version = 'test<img src="x" onerror="alert(document.domain);">end'
            var dialog = new Y.WidgetDocDialog('foo/bar/Baz', version);
            Y.assert(dialog.widgetName    , "Baz");
            Y.assert(dialog.version , "test%3Cimg+src%3D%22x%22+onerror%3D%22alert(document.domain)%3B%22%3Eend");
        }

    }));

    return suite;
}).run();
