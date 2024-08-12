!function (doc, win) {
    var div = doc.createElement('div');
    div.id = 'togglePanel';
    div.innerHTML = '<button></button>';
    doc.body.appendChild(div);

    div = doc.createElement('div');
    div.className = 'leftPanel';
    doc.body.appendChild(div);
}(document, window);

UnitTest.addSuite({
    type: UnitTest.Type.Admin,
    yuiModules: ['PanelToggle'],
    preloadFiles: [
        '/euf/core/admin/js/versions/components/tests/bootstrap.js',
        '/euf/core/admin/js/versions/components/tabs.js',
        '/euf/core/admin/js/versions/components/component.js',
        '/euf/core/admin/js/versions/components/helpers.js',
        '/euf/core/admin/js/versions/components/panelToggle.js'
    ]
}, function(Y) {
    var suite = new Y.Test.Suite({ name: "Panel toggle" });

    suite.add(new Y.Test.Case({
        name: "Behavior",

        "Swaps class names": function () {
            Y.one('#togglePanel button').simulate('click');
            Y.Assert.isTrue(Y.one('.leftPanel').hasClass('off'));
            Y.Assert.isTrue(Y.one('#togglePanel').hasClass('off'));

            Y.one('#togglePanel button').simulate('click');
            Y.Assert.isTrue(Y.one('.leftPanel').hasClass('on'));
            Y.Assert.isTrue(Y.one('#togglePanel').hasClass('on'));
        }
    }));

    return suite;
}).run();
