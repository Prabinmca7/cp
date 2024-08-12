/*global hideShowPanel*/
(function(global) {
    var html =
    '<button class="versionInfoToggle">Trigger</button> \
     <div class="hide testPanel">Panel</div> \
    ';
    var container = document.createElement('div');
    container.innerHTML = html;
    document.body.appendChild(container);

    var called = 0,
        args;
    global.hideShowPanel = function(a, b) {
        called++;
        args = [a, b];
    };
    global.hideShowPanel.called = function() { return called; };
    global.hideShowPanel.args = function() { return args; };
})(window);

UnitTest.addSuite({
    type: UnitTest.Type.Admin,
    preloadFiles: [
        '/euf/core/admin/js/main/index.js'
    ]
}, function(Y) {
    var suite = new Y.Test.Suite({ name: "Index page functionality" });

    suite.add(new Y.Test.Case({
        name: "Version info toggle",

        "Toggler is called correctly on click": function() {
            Y.Assert.areSame(0, hideShowPanel.called());
            Y.one('.versionInfoToggle').simulate('click');
            Y.Assert.areSame(1, hideShowPanel.called());
            Y.Assert.areSame('click', hideShowPanel.args()[0].type);
            Y.assert(hideShowPanel.args()[1].hasClass('testPanel'));
        }
    }));

    return suite;
}).run();
