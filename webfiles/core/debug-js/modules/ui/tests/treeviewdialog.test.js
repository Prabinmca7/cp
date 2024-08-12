UnitTest.addSuite({
    type: UnitTest.Type.Framework,
    yuiModules: ['RightNowTreeViewDialog'],
    preloadFiles: [
        '/euf/core/debug-js/modules/ui/treeviewdialog.js',
        '/euf/core/debug-js/RightNow.Text.js',
        '/euf/core/debug-js/RightNow.UI.js'
    ]
}, function(Y) {
    function setUp () {
        this.container = Y.Node.create('<div id="container"></div>').appendTo('body');
    }

    function tearDown () {
        this.container.remove();
        if (this.dialog) {
            this.dialog.destroy();
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

        "#render inserts html content into the element specified by `contentBox`": function () {
            this.dialog = new Y.RightNowTreeViewDialog({
                contentBox: this.container
            });
            Y.Assert.areSame('', this.container.getHTML());
            this.dialog.render();
            Y.Assert.areNotSame('', this.container.getHTML());
        },

        "#show creates the dialog if it doesn't exist": function () {
            this.dialog = new Y.RightNowTreeViewDialog({
                render: true,
                contentBox: this.container,
                hierarchyData: [{
                    0: 'label1',
                    1: 1,
                    level: 0,
                    hier_list: [1]
                }, {
                    0: 'label2',
                    1: 2,
                    level: 1,
                    hier_list: [1, 2]
                }]
            }).show();

            Y.Assert.areSame(2, this.container.all('ol').size());
            Y.Assert.areSame(2, this.container.all('li').size());
            var resultText = this.container.get('text');
            Y.assert(resultText.indexOf('label1 1') !== -1);
            Y.assert(resultText.indexOf('label2 2') !== -1);
            Y.Assert.isNull(this.container.ancestor('.yui3-panel-hidden'));
        },

        "#hide hides the dialog": function () {
            this.dialog = new Y.RightNowTreeViewDialog({ render: true, contentBox: this.container }).show();
            this.dialog.hide();
            Y.Assert.isNotNull(this.container.ancestor('.yui3-panel-hidden'));
        },

        "new labels and values are reflected when the dialog is re-shown": function () {
            this.dialog = new Y.RightNowTreeViewDialog({ render: true, contentBox: this.container }).show();
            Y.Assert.areSame(0, this.container.all('ol').size());
            this.dialog.hide();

            this.dialog.set('selectionPlaceholderLabel', 'placeholder %s');
            this.dialog.set('introLabel', 'intro');
            this.dialog.set('levelLabel', 'level');
            this.dialog.set('selectedLabels', ['piano', 'sonata']);
            this.dialog.set('selectedValue', 88);
            this.dialog.set('hierarchyData', [{ 0: 'label1', 1: 88, level: 0, hier_list: [88] }]);

            this.dialog.show();

            Y.Assert.areSame('placeholder piano, sonata', this.container.one('#rn_RightNowTreeViewDialog_IntroCurrentSelection').get('text'));
            Y.Assert.areSame('intro placeholder piano, sonata', this.container.one('.rn_Intro a').get('text'));
            Y.Assert.areSame(1, this.container.all('ol').size());
            Y.Assert.areSame(1, this.container.all('li').size());
            Y.Assert.areSame('level 1', this.container.one('li .rn_ScreenReaderOnly').get('text'));
        },

        "dialog UI labels are immediately updated": function () {
            this.dialog = new Y.RightNowTreeViewDialog({ render: true, contentBox: this.container }).show();
            this.dialog.set('dismissLabel', 'dismiss')
                       .set('titleLabel', 'title');

            Y.Assert.areSame('title', Y.one('.rn_DialogTitle').getHTML());
            Y.Assert.areSame('dismiss', Y.one('.rn_Dialog .yui3-widget-ft button').getHTML());
        }
    }));

    suite.add(new Y.Test.Case({
        name: "Events",

        "`close` event is fired when the dialog is closed": function () {
            this.dialog = new Y.RightNowTreeViewDialog({ render: true, contentBox: this.container }).show();

            var calledEvent = false;

            this.dialog.once('close', function () {
                calledEvent = true;
            });

            Y.one('.rn_Dialog .yui3-widget-ft button').simulate('click');
            Y.Assert.isTrue(calledEvent);
        },

        "`selectionMade` is fired when an item is selected": function () {
            this.dialog = new Y.RightNowTreeViewDialog({
                render: true,
                contentBox: this.container,
                hierarchyData: [{
                    0: 'label1',
                    1: 88,
                    level: 0,
                    hier_list: [88]
                }, {
                    0: 'label2',
                    1: 236,
                    level: 1,
                    hier_list: [88, 236]
                }]
            }).show();

            var selectionChain = [];

            this.dialog.once('selectionMade', function (e) {
                selectionChain = e.valueChain;
            });

            this.container.all('a').slice(-1).item(0).simulate('click');

            Y.Assert.areSame('88', selectionChain[0]);
            Y.Assert.areSame('236', selectionChain[1]);
        }
    }));

    return suite;
}).run();
