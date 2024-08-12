YUI_config.groups = {
    'gallery-treeview': {
        'base': '/rnt/rnw/yui_3.13/gallery-treeview/',
        'modules': {
            'gallery-treeview': { 'path': 'gallery-treeview.js' }
        }
    }
};

UnitTest.addSuite({
    type: UnitTest.Type.Framework,
    yuiModules: ['widget', 'RightNowTreeView'],
    preloadFiles: [
        '/rnt/rnw/yui_3.13/gallery-treeview/gallery-treeview.js',
        '/euf/core/debug-js/modules/ui/treeview.js'
    ]
}, function(Y) {
    function setUp () {
        this.container = Y.Node.create('<div id="container"></div>').appendTo('body');
        this.tree = new Y.RightNowTreeView({
            hierarchyData: treeData,
            contentBox: this.container,
            render: true
        });
    }

    function tearDown () {
        this.refreshContainer();
        if (this.tree) {
            this.tree.destroy();
        }
        this.container.remove();
    }

    function createTree () {
        this.wait(function () {
            this.tree.clear();
            this.tree._createTree(treeData);
            this.refreshContainer();
        }, 10);
    }

    /**
     * Refresh the container node reference.
     * When there's a ton of destructive DOM operations
     * happening within a short timespan and outside of
     * the YUI Node API, YUI Node references
     * and their properties tend to get out of date.
     */
    function refreshContainer () {
        this.container = Y.one('#container');
    }

    var treeData = [
        [
            {
                id: 12,
                label: 'first'
            },
            {
                id: 1,
                label: 'second',
                hasChildren: true
            },
            {
                id: 23,
                label: 'third',
                hasChildren: true
            },
            {
                id: 2,
                label: 'fourth',
                hasChildren: true
            }
        ],
        [
            {
                id: 88,
                label: 'child of second'
            }
        ],
        [
            {
                id: 33,
                label: 'child of fourth'
            },
            {
                id: 34,
                label: 'child of fourth'
            }
        ]
    ];

    var suite = new Y.Test.Suite({
        name: "RightNowTreeViewDialog",
        setUp: function () {
            Y.Array.each(this.items, function (testCase) {
                testCase.setUp = setUp;
                testCase.tearDown = tearDown;
                testCase.createTree = createTree;
                testCase.refreshContainer = refreshContainer;
            });
        }
    });

    suite.add(new Y.Test.Case({
        name: "Public interface",

        "TreeView is constructed from data": function () {
            this.createTree();

            Y.Assert.areSame('tree', this.container.get('firstChild').getAttribute('role'));
            Y.Assert.areSame(7, this.container.all('.ygtvlabel').size());
        },

        "#clear clears out all nodes": function () {
            this.createTree();
            this.tree.clear();
            this.refreshContainer();
            Y.Assert.areSame(0, this.container.all('.ygtvlabel').size());
        },

        "#clear with label adds a single node with that label": function () {
            this.createTree();
            this.tree.clear('all');
            this.refreshContainer();
            Y.Assert.areSame(1, this.container.all('.ygtvlabel').size());
            Y.Assert.areSame('all', this.container.one('.ygtvlabel').get('text'));
        },

        "#collapseAll collapses all expanded nodes": function () {
            this.createTree();

            this.container.one('[aria-expanded="false"]').simulate('click');

            this.refreshContainer();

            Y.Assert.areSame('block', this.container.all('.ygtvchildren .ygtvchildren').item(1).getComputedStyle('display'));

            this.tree.collapseAll();

            this.refreshContainer();

            Y.Assert.areSame('none', this.container.all('.ygtvchildren .ygtvchildren').item(1).getComputedStyle('display'));
        },

        "#focusOnSelectedNode focuses on a selected node": function () {
            this.createTree();

            var node = this.tree.selectNodeWithValue(treeData[0][0].id);
            this.tree.focusOnSelectedNode();
            var focused = Y.one(document.activeElement);
            Y.Assert.areSame(treeData[0][0].label, Y.Lang.trim(focused.ancestor('tr').get('text')));
        },

        "#selectNodeWithValue selects a node with a value": function () {
            this.createTree();

            this.tree.selectNodeWithValue(treeData[0][1].id);
            Y.Assert.areSame(treeData[0][1].id, this.tree.get('value'));
        },

        "#resetSelectedNode selects the first node": function () {
            this.createTree();
            this.tree.selectNodeWithValue(treeData[0][1].id);
            this.tree.resetSelectedNode();
            Y.Assert.areSame(0, this.tree.get('value'));
        },

        "#getNodeByValue returns a node object": function () {
            this.createTree();
            var expected = treeData[0][0];
            var node = this.tree.getNodeByValue(expected.id);

            Y.Assert.areSame(false, node.expanded);
            Y.Assert.areSame(expected.id, node.value);
            Y.Assert.isNumber(node.index);
            Y.Assert.areSame(0, node.depth);
            Y.Assert.areSame(expected.label, node.label);
            Y.Assert.isTrue(node.loaded);
            Y.Assert.isFalse(node.hasChildren);
            Y.Assert.areSame(1, node.valueChain.length);
            Y.Assert.areSame(expected.id, node.valueChain[0]);
        },

        "#getFocusedNode returns a node object": function () {
            this.createTree();
            var expected = treeData[0][0];
            this.tree.selectNodeWithValue(expected.id, true);
            var node = this.tree.getFocusedNode();

            Y.Assert.areSame(false, node.expanded);
            Y.Assert.areSame(expected.id, node.value);
            Y.Assert.isNumber(node.index);
            Y.Assert.areSame(0, node.depth);
            Y.Assert.areSame(expected.label, node.label);
            Y.Assert.isTrue(node.loaded);
            Y.Assert.isFalse(node.hasChildren);
            Y.Assert.areSame(1, node.valueChain.length);
            Y.Assert.areSame(expected.id, node.valueChain[0]);
        },

        "#getSelectedNode returns the selected node": function () {
            this.createTree();

            var node = this.tree.getSelectedNode();
            Y.Assert.isNull(node);

            this.tree.selectNodeWithValue(treeData[0][0].id);

            node = this.tree.getSelectedNode();
            Y.Assert.areSame(treeData[0][0].id, node.value);
        },

        "getNumberOfNodes returns the number of nodes": function () {
            this.createTree();

            Y.Assert.areSame(7, this.tree.getNumberOfNodes());
        },

        "#expandAndCreateNodes selects already-loaded nodes": function () {
            this.createTree();

            var selected = null;
            this.tree.once('click', function (e) {
                // Child already exists in the tree so it's simply selected.
                selected = e.value;
            });
            Y.Assert.isTrue(this.tree.expandAndCreateNodes([2, 33]));
            Y.Assert.areSame(33, selected);
        },

        "#expandAndCreateNodes requests not-loaded nodes": function () {
            this.createTree();

            var clickCalled = false;
            this.tree.once('click', function (e) {
                clickCalled = true;
            });
            var expanding = null;
            this.tree.once('dynamicNodeExpand', function (e) {
                // Child doesn't exist in the tree so parent's children are requested.
                this.resume(function () {
                    Y.Assert.areSame(23, e.value);
                });
            }, this);
            Y.Assert.isTrue(this.tree.expandAndCreateNodes([23, 888]));
            this.wait();
            Y.Assert.isFalse(clickCalled);
        },

        "#expandAndCreateNodes returns false when a requested item doesn't exist": function () {
            this.createTree();

            var subscriberCalled = false;
            function subscriber () { subscriberCalled = true; }

            this.tree.once('click', subscriber);
            this.tree.once('dynamicNodeExpand', subscriber);

            Y.Assert.isFalse(this.tree.expandAndCreateNodes([-43]));
            Y.Assert.isFalse(subscriberCalled);
        }
    }));

    suite.add(new Y.Test.Case({
        name: "Events",

        "`click` event is fired when a node is clicked": function () {
            this.createTree();

            var clicked = null;
            this.tree.once('click', function (e) {
                clicked = e.value;
            });

            this.container.one('.ygtvchildren .ygtvitem a').simulate('click');

            Y.Assert.areSame(treeData[0][0].id, clicked);
        },

        "`dynamicLoadExpand` event fires when a node with children that aren't loaded is expanded": function () {
            this.createTree();

            var expanded = null;
            this.tree.once('dynamicNodeExpand', function (e) {
                this.resume(function () {
                    Y.Assert.areSame(treeData[0][2].id, e.value);
                });
            }, this);

            // Second expandable item doesn't have children that are already loaded.
            this.container.all('[aria-expanded="false"]').item(1).simulate('click');
            this.wait();
        }
    }));

    return suite;
}).run();
