(function() {
    var html =
    '<style type="text/css">nav div {float:left}</style> \
    <nav> \
    <a class="hide" id="toggleMenu" href="javascript:void(0);">↑</a> \
    <div id="navmenu" class="yui3-menu yui3-menu-horizontal yui3-menubuttonnav"> \
        <div class="yui3-menu-content"> \
            <ul> \
                <li class="yui3-menuitem"> \
                    <a href="/ci/admin/overview">dash</a> \
                </li> \
            </ul> \
        </div> \
    </div> \
    <div id="viewSite" class="collapsible"> \
        <button></button> \
        <ul id="viewSiteDropdown"></ul> \
    </div> \
    <div class="collapsible"> \
        <input type="search" id="widgetInput"> \
    </div> \
    </nav> \
    <div id="sitetitle" tabindex="0"></div>';
    var container = document.createElement('div');
    container.innerHTML = html;
    document.body.appendChild(container);
    window.autoCompleteData = {};
})();

UnitTest.addSuite({
    type: UnitTest.Type.Admin,
    preloadFiles: [
        '/euf/core/admin/js/template.js'
    ]
}, function(Y) {
    var suite = new Y.Test.Suite({ name: "Template JS functionality" });

    suite.add(new Y.Test.Case({
        name: "hideShowPanel",

        "Toggles the hide class on the panel": function () {
            var node = Y.Node.create('<div class="hide"></div>');
            window.hideShowPanel({ halt: function () {} }, node);
            Y.Assert.isFalse(node.hasClass('hide'));
            window.hideShowPanel({ halt: function () {} }, node);
            Y.Assert.isTrue(node.hasClass('hide'));
        },

        "Show panel and focus on a specific element": function () {
            var focused = false,
                node = Y.Node.create('<div class="hide"><a href="#">focus on me</a></div>');
            window.hideShowPanel({ halt: function () {} }, node, null, { focus: function () { focused = true }});
            Y.Assert.isFalse(node.hasClass('hide'));
            Y.Assert.isTrue(focused);
        },

        "Hides the panel and focuses on the specified element": function () {
            var focused = false,
                node = Y.Node.create('<div></div>');

            window.hideShowPanel(false, node, { focus: function () { focused = true; }});

            Y.Assert.isTrue(node.hasClass('hide'));
            Y.Assert.isTrue(focused);
        },
    }));

    suite.add(new Y.Test.Case({
        name: "Menu",

        "Toggle item toggles the menu and overflowed items are visible": function() {
            Y.one('#toggleMenu').simulate('click');
            this.wait(function () {
                Y.assert(Y.one('nav').hasClass('expanded'), "Should be expanded");
                Y.assert(Y.all('nav .overflowed').size());
                Y.assert(Y.all('nav .visible').size());
                Y.Assert.areSame(Y.all('nav .overflowed').size(),
                    Y.all('nav .overflowed.visible').size());
            }, 400);
        },

        "Toggle item toggles the menu and overflowed items are not visible": function() {
            Y.one('#toggleMenu').simulate('click');
            this.wait(function () {
                Y.assert(Y.one('nav').hasClass('collapsed'), "Should be collapsed");
                Y.assert(Y.all('nav .overflowed').size());
                Y.assert(!Y.all('nav .visible').size());
            }, 400);
        }
    }));

    return suite;
}).run();
