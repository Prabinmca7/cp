UnitTest.addSuite({
    type: UnitTest.Type.Framework,
    yuiModules: ['RightNowTreeViewDropdown'],
    preloadFiles: [
        '/euf/core/debug-js/modules/ui/treeviewdropdown.js',
        '/euf/core/debug-js/RightNow.UI.js'
    ]
}, function(Y) {
    function setUp () {
        this.container = Y.Node.create('<div id="container"></div>').appendTo('body');
        this.trigger = Y.Node.create('<button>trigger</button>').appendTo(this.container);
        this.content = Y.Node.create('<div>allegretto</div>').appendTo(this.container);

        this.panel = new Y.RightNowTreeViewDropdown({
            render: this.container,
            srcNode: this.content,
            trigger: this.trigger,
            visible: false
        });
    }

    function tearDown () {
        this.container.remove();
        if (this.panel) {
            this.panel.destroy();
        }
    }

    var suite = new Y.Test.Suite({
        name: "RightNowTreeViewDialog",
        setUp: function () {
            Y.Array.each(this.items, function (testCase) {
                testCase.setUp = setUp;
                testCase.tearDown = tearDown;
            });
        }
    });

    suite.add(new Y.Test.Case({
        name: "Public interface",

        verifyPanelIsVisible: function () {
            Y.Assert.isNull(this.content.ancestor('.yui3-panel-hidden'));
            Y.Assert.isTrue(this.panel.isVisible());
        },

        verifyPanelIsHidden: function () {
            Y.Assert.isNotNull(this.content.ancestor('.yui3-panel-hidden'));
            Y.Assert.isFalse(this.panel.isVisible());
        },

        "#show shows the panel, #hide hides the panel, #toggle toggles": function () {
            this.panel.show();

            Y.Assert.isNotNull(this.content.ancestor('.yui3-panel'));
            this.verifyPanelIsVisible();

            this.panel.hide();

            this.verifyPanelIsHidden();

            this.panel.toggle();

            this.verifyPanelIsVisible();

            this.panel.toggle();

            this.verifyPanelIsHidden();
        },

        "Clicking the trigger element shows the panel": function () {
            this.trigger.simulate('click');

            this.verifyPanelIsVisible();
        }
    }));

    suite.add(new Y.Test.Case({
        name: "Events",

        "`show` event fires when the panel shows": function () {
            var fired = false;
            this.panel.once('show', function () {
                fired = true;
            });
            this.panel.show();

            Y.Assert.isTrue(fired);
        },

        "`hide` event fires when the panel hides": function () {
            var fired = false;
            this.panel.once('hide', function () {
                fired = true;
            });
            this.panel.show().hide();

            Y.Assert.isTrue(fired);
        },

        "`confirm` event fires when the panel's confirm button is clicked": function () {
            var fired = false;
            this.panel.once('confirm', function () {
                fired = true;
            });

            var confirmButton = Y.Node.create('<button>Confirm</button>').appendTo(this.container);
            this.panel.set('confirmButton', confirmButton).show();
            confirmButton.simulate('click');

            Y.Assert.isTrue(fired);
        }
    }));

    return suite;
}).run();
