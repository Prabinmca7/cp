!function (doc, win) {
    var fixture = doc.createElement('form');
    fixture.innerHTML = '<input type="search" id="widgetsSearch">';
    doc.body.appendChild(fixture);

    var widgets = doc.createElement('div');
    widgets.id = 'widgets';
    widgets.innerHTML = '<div class="listing-item hide" data-name="bananas"></div>';
    doc.body.appendChild(widgets);

    win.widgetNames = [
        "bananas",
        "bamf",
        "Bam"
    ];
}(document, window);

UnitTest.addSuite({
    type: UnitTest.Type.Admin,
    yuiModules: ['WidgetAutocomplete'],
    preloadFiles: [
        '/euf/core/admin/js/versions/components/tests/bootstrap.js',
        '/euf/core/admin/js/versions/components/tabs.js',
        '/euf/core/admin/js/versions/components/component.js',
        '/euf/core/admin/js/versions/components/helpers.js',
        '/euf/core/admin/js/versions/components/autocomplete.js'
    ]
}, function(Y) {
    var suite = new Y.Test.Suite({ name: "Widget autocomplete" });

    suite.add(new Y.Test.Case({
        name: "Autocomplete behavior",

        setUp: function () {
            this.input = Y.one('#widgetsSearch');
        },

        "Autocomplete list appears": function () {
            this.input.set('value', 'b');
            this.input.simulate('keydown', {keyCode: 66});
            this.wait(function () {
                Y.Assert.areSame('bananas', Y.one('.yui3-aclist-item-active').get('text'));
            }, 200);
        },

        "`widgets:select` event is fired when an item is selected": function () {
            var calledWith;
            Y.on('widgets:select', function (e) {
                calledWith = e.target;
            });
            this.input.set('value', 'b');
            this.input.simulate('keydown', {keyCode: 66});
            this.input.simulate('keydown', {keyCode: 13});
            Y.Assert.areSame(Y.one('#widgets').one('*'), calledWith);
        }
    }));

    return suite;
}).run();
