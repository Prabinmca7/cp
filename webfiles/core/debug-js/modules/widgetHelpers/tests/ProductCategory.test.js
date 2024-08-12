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
    yuiModules: ['widget', 'RightNowTreeView', 'RightNowTreeViewDropdown', 'RightNowTreeViewDialog'],
    preloadFiles: [
        '/euf/core/debug-js/modules/widgetHelpers/EventProvider.js',
        '/rnt/rnw/yui_3.13/gallery-treeview/gallery-treeview.js',
        '/euf/core/debug-js/modules/ui/treeviewdropdown.js',
        '/euf/core/debug-js/modules/ui/treeview.js',
        '/euf/core/debug-js/modules/ui/treeviewdialog.js',
        '/euf/core/debug-js/RightNow.Text.js',
        '/euf/core/debug-js/RightNow.Event.js',
        '/euf/core/debug-js/RightNow.UI.js',
        '/euf/core/debug-js/RightNow.Url.js',
        '/euf/core/debug-js/modules/widgetHelpers/ProductCategory.js'
    ]
}, function(Y) {
    var instance = {
        Y: Y,
        baseSelector: '#pc',
        baseDomID: 'pc',
        instanceID: 'prodcat_widget',
        data: {
            attrs: {},
            js: {}
        }
    };

    var dataType = 'Product';

    var instances = 0;

    function setUp () {
        instances++;

        instance.baseSelector += instances;
        instance.baseDomID += instances;
        this.instanceID = instance.instanceID + instances;
        
        this.pc = new RightNow.ProductCategory();
        this.pc = Y.mix(this.pc, instance, true);
        this.pc.data.js.readableProdcatIds = [];
        this.pc.data.js.hierData = [
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
    }

    function insertDOMElements () {
        var container = Y.Node.create('<div>').set('id', instance.baseDomID);
        this.trigger = Y.Node.create('<button>').set('id', instance.baseDomID + '_' + dataType + '_Button').appendTo(container);
        this.treeContainer = Y.Node.create('<div class="rn_Hidden">').set('id', instance.baseDomID + '_TreeContainer').appendTo(container);
        this.tree = Y.Node.create('<div>').set('id', instance.baseDomID + '_Tree').appendTo(this.treeContainer);
        this.accessibleTrigger = Y.Node.create('<button>').set('id', instance.baseDomID + '_LinksTrigger').appendTo(container);
        Y.one('body').append(container);
    }

    function tearDown () {
        if (this.pc) {
            if (this.pc.tree) {
                this.pc.tree.destroy();
            }
            if (this.pc.dropdown) {
                this.pc.dropdown.destroy();
            }
            if (this.pc.dialog) {
                this.pc.dialog.destroy();
            }
            RightNow.Event.unsubscribe("evt_accessibleTreeViewGetResponse", this.pc.getAccessibleTreeViewResponse, this.pc);
            RightNow.Event.unsubscribe("evt_menuFilterGetResponse", this.pc.getSubLevelResponse, this.pc);
            this.pc = null;
        }
        Y.one(instance.baseSelector).remove();
    }

    var suite = new Y.Test.Suite({
        name: "ProductCategory",
        setUp: function () {
            Y.Array.each(this.items, function (testCase) {
                testCase.setUp = setUp;
                testCase.tearDown = tearDown;
                testCase.insertDOMElements = insertDOMElements;
            });
        }
    });

    suite.add(new Y.Test.Case({
        name: "initialization",

        _should: {
            error: {
                "Fails without expected DOM elements": true
            }
        },

        "Fails without expected DOM elements": function () {
            this.pc.initializeTreeView(dataType);
        },

        "Initializes with the expected DOM elements": function () {
            this.insertDOMElements();
            this.pc.initializeTreeView(dataType);

            Y.Assert.isObject(this.pc.dropdown);
            Y.Assert.isFalse(this.treeContainer.hasClass('rn_Hidden'));
        },

        "Initializes the tree when there's data": function () {
            this.insertDOMElements();
            this.pc.initializeTreeView(dataType);

            Y.Assert.isObject(this.pc.tree);
        }
    }));

    suite.add(new Y.Test.Case({
        name: "Accessible links",

        "Requests data when the dialog doesn't exist": function () {
            var called = 0, callback = function () { called++; };

            RightNow.Ajax = { makeRequest: callback };
            RightNow.Event.on('evt_accessibleTreeViewRequest', callback);

            this.insertDOMElements();
            this.pc.initializeTreeView(dataType);

            Y.one('#' + this.accessibleTrigger.get('id')).simulate('click');
            Y.Assert.areSame(2, called);
        },

        "Displays the dialog immediately on response": function () {
            this.insertDOMElements();
            this.pc.initializeTreeView(dataType);
            this.pc._eo = new RightNow.Event.EventObject(this);

            RightNow.Event.fire('evt_accessibleTreeViewGetResponse', new RightNow.Event.EventObject(this, {
                data: { accessibleLinks: [] }
            }));

            Y.Assert.isObject(Y.one('#rnDialog1'));
            Y.Assert.areSame(1, Y.one('#rnDialog1 ol').all('a').size());
        },
        
        "Don't focus the trigger button when the dialog closes": function () {
            this.insertDOMElements();
            this.pc.initializeTreeView(dataType);
            this.pc._eo = new RightNow.Event.EventObject(this);

            RightNow.Event.fire('evt_accessibleTreeViewGetResponse', new RightNow.Event.EventObject(this, {
                data: { accessibleLinks: [] }
            }));

            Y.Assert.isObject(Y.one('#rnDialog2'));

            Y.one('#rnDialog2 .yui3-button-close').simulate('click');
            Y.assert(Y.one('#rnDialog2').ancestor('.yui3-panel-hidden'));
            Y.Assert.areNotSame(this.trigger, Y.one(document.activeElement));
        },

        "Added widget more than one time": function () {
            this.insertDOMElements();
            this.pc.initializeTreeView(dataType);
            this.pc._eo = new RightNow.Event.EventObject(this);
            
            this.setUp();
            this.insertDOMElements();
            this.pc.initializeTreeView(dataType);
            this.pc._eo = new RightNow.Event.EventObject(this);

            RightNow.Event.fire('evt_accessibleTreeViewGetResponse', new RightNow.Event.EventObject(this, {
                data: { accessibleLinks: [], w_id: this.pc._eo.w_id }
            }));

            Y.Assert.isObject(Y.one('#rnDialog3'));
            Y.Assert.areSame(1, Y.one('#rnDialog3 ol').all('a').size());
            Y.Assert.isNull(Y.one('#rnDialog4'));
        },
        
        "Non-accessible links have messages added to them": function () {
            var accessibleData = [
                {
                    0: "first",
                    1: 12,
                    "hier_list": 0,
                    "level": 1
                },
                {
                    0: "second",
                    1: 88,
                    "hier_list": 1,
                    "level": 2
                }
            ];
            this.insertDOMElements();
            this.pc.initializeTreeView(dataType);
            this.pc._eo = new RightNow.Event.EventObject(this);

            RightNow.Event.fire('evt_accessibleTreeViewGetResponse', new RightNow.Event.EventObject(this, {
                data: { accessibleLinks: accessibleData }
            }));

            this.pc.baseSelector = '#rn_prodcat_widget';
            this.pc.data.js.readableProdcatIds = this.pc.data.js.permissionedProdcatIds = [88];

            Y.Assert.isFalse(this.pc.checkPermissionsOnNode(12)); //Passes through to isPermissionedNode
            this.pc.data.js.permissionedProdcatIds = null;
            Y.Assert.isTrue(this.pc.checkPermissionsOnNode(88)); //Passes through to isRedableNode

            this.pc.disableNonPermissionedAccessibleNodes(accessibleData);
            //Dont display errors since attrs.label_selection_not_valid isn't set
            Y.Assert.areSame(Y.one('#rn_prodcat_widget_AccessibleLink_12').get('childNodes').item(0).get('nodeValue'), "first");
            Y.Assert.areSame(Y.one('#rn_prodcat_widget_AccessibleLink_88').get('childNodes').item(0).get('nodeValue'), "second");

            instance.data.attrs.label_selection_not_valid = "%s is not a valid selection";
            this.pc.disableNonPermissionedAccessibleNodes(accessibleData);
            Y.Assert.areSame(Y.one('#rn_prodcat_widget_AccessibleLink_12').get('childNodes').item(0).get('nodeValue'), "first");
            Y.Assert.areSame(Y.one('#rn_prodcat_widget_AccessibleLink_12').get('childNodes').item(2).get('nodeValue'), " is not a valid selection");
            Y.Assert.areSame(Y.one('#rn_prodcat_widget_AccessibleLink_88').get('childNodes').item(0).get('nodeValue'), "second");
        }
    }));

    suite.add(new Y.Test.Case({
        name: "Node selection",

        "Node with not-loaded children requests them from the server": function () {
            this.insertDOMElements();
            this.pc.initializeTreeView(dataType);

            var parent = null;
            RightNow.Event.on('evt_menuFilterRequest', function (evt, args) {
                this.resume(function () {
                    parent = args[0].data;
                    var expected = this.pc.data.js.hierData[0][2];
                    Y.Assert.areSame(expected.label, parent.label);
                    Y.Assert.areSame(expected.id, parent.value);
                    Y.Assert.areSame(1, parent.level);
                });
            }, this);
            Y.one(instance.baseSelector).all('[aria-expanded="false"]').item(1).simulate('click');
            this.wait();
        }
    }));

    suite.add(new Y.Test.Case({
        name: "Hierarchical data can be updated",

        "Non readable prodcats are removed from hierData": function () {
            this.pc.data.js.readableProdcatIds = [128, 129, 130, 131, 120, 138];

            var result = this.pc.removeNonReadableProdcats([
                {
                    'id': 132,
                    'label': 'p1a',
                    'hasChildren': true
                },
                {
                    'id': 133,
                    'label': 'p1b',
                    'hasChildren': true
                },
                {
                    'id': 129,
                    'label': 'p2',
                    'hasChildren': true
                }
            ]);

            // YUI's asserts aren't liking direct object comparison, so test attributes
            Y.Assert.areSame(result.length, 1);
            Y.Assert.areSame(result[0].id, 129);
            Y.Assert.areSame(result[0].label, 'p2');
            Y.Assert.areSame(result[0].hasChildren, true);
        },

        "Prodcat's hasChildren attribute is correctly updated": function () {
            this.pc.data.js.readableProdcatIdsWithChildren =  [128, 129, 130, 131];

            var result = this.pc.updateProdcatsHasChildrenAttribute([
                {
                    'id': 120,
                    'label': 'p5',
                    'hasChildren': true
                }
            ]);

            // YUI's asserts aren't liking direct object comparison, so test attributes
            Y.Assert.areSame(result.length, 1);
            Y.Assert.areSame(result[0].id, 120);
            Y.Assert.areSame(result[0].label, 'p5');
            Y.Assert.areSame(result[0].hasChildren, false);
        },

        "Prodcats aren't groomed if verify_permissions isn't enabled or readableProdcatIds is empty": function () {
            this.pc.data.attrs.verify_permissions = false;
            this.pc.data.js.readableProdcatIds = [];

            var originalHierData = [
                {
                    'id': 132,
                    'label': 'p1a',
                    'hasChildren': true
                },
                {
                    'id': 133,
                    'label': 'p1b',
                    'hasChildren': true
                },
                {
                    'id': 129,
                    'label': 'p2',
                    'hasChildren': true
                }
            ];

            var hierData = this.pc.groomProdcats(originalHierData);
            Y.Assert.areSame(hierData, originalHierData);

            this.pc.data.attrs.verify_permissions = true;
            this.pc.data.js.readableProdcatIds = [];

            hierData = this.pc.groomProdcats(originalHierData);
            Y.Assert.areSame(hierData, originalHierData);

            this.pc.data.js.readableProdcatIds = [128, 129, 130, 131, 120, 138];
            hierData = this.pc.groomProdcats(originalHierData);

            Y.Assert.isTrue(hierData.length === 1);
            Y.Assert.areSame(hierData[0].id, 129);
            Y.Assert.areSame(hierData[0].label, 'p2');
            Y.Assert.areSame(hierData[0].hasChildren, true);

            this.pc.data.attrs.verify_permissions = 'Read';
            hierData = this.pc.groomProdcats(originalHierData);

            Y.Assert.isTrue(hierData.length === 1);
            Y.Assert.areSame(hierData[0].id, 129);
            Y.Assert.areSame(hierData[0].label, 'p2');
            Y.Assert.areSame(hierData[0].hasChildren, true);

            this.pc.data.js.readableProdcatIds = [];
            this.pc.data.attrs.verify_permissions = 'None';

            hierData = this.pc.groomProdcats(originalHierData);
            Y.Assert.areSame(hierData, originalHierData);
        },

        _should: {
            error: {
                'Error handling on invalid permissions data': "Widget does not have this.data.js.readableProdcatIds attribute set, or its value is not an array"
            }
        },

        "Error handling on invalid permissions data": function () {
            this.pc.data.js.readableProdcatIds = null;
            this.pc.checkIdsAreValid('readableProdcatIds');
        },

        "Error handling on valid permissions data": function () {
            this.pc.data.js.readableProdcatIds = [];
            this.pc.checkIdsAreValid('readableProdcatIds');
        },

        "Sub level responses are handled correctly": function () {
            this.insertDOMElements();
            this.pc.initializeTreeView(dataType);

            // Monkey-wrench some instance methods for testing to ensure they fire
            var buildTreeCalled = false;
            this.pc.buildTree = function() {
                buildTreeCalled = true;
            }

            var insertChildrenForNodeCalled = false;
            this.pc.insertChildrenForNode = function() {
                insertChildrenForNodeCalled = true;
            }

            var originalGetParameterMethod = RightNow.Url.getParameter;
            RightNow.Url.getParameter = function() {
                return 'I am a parameter!';
            }

            // Reset the testing variables after each test
            function resetTestVariables(context) {
                buildTreeCalled = insertChildrenForNodeCalled = false;
                context.pc.dialog = 'is not null';
            }

            // Test throught different combinations of scenarios by building a (unique)
            // event object and setting state on the pc instance before firing getSubLevelResponse.
            var eventObjectArg = {
                data: {
                    reset_linked_category: true,
                    level: 4,
                    hier_data: [1,2,3,4],
                    data_type: 'Product',
                    via_page_load: false
                }
            };
            this.pc.getSubLevelRequestEventObject._origRequest = {};
            this.pc.getSubLevelRequestEventObject._origRequest[dataType] = 'something';
            this.pc.dialog = 'is not null';
            this.pc.getSubLevelResponse(eventObjectArg, 'raddish')

            Y.Assert.isNull(this.pc.dialog);
            Y.Assert.isTrue(buildTreeCalled);
            Y.Assert.isTrue(insertChildrenForNodeCalled);
            resetTestVariables(this);

            eventObjectArg = {
                data: {
                    reset_linked_category: true,
                    level: 4,
                    hier_data: [1,2,3,4],
                    data_type: 'Category',
                    via_page_load: true
                }
            };
            this.pc.getSubLevelRequestEventObject._origRequest = {};
            this.pc.getSubLevelRequestEventObject._origRequest[dataType] = 'something';
            this.pc.dialog = 'is not null';
            this.pc.getSubLevelResponse(eventObjectArg, 'raddish')

            Y.Assert.isNull(this.pc.dialog);
            Y.Assert.isTrue(buildTreeCalled);
            Y.Assert.isTrue(insertChildrenForNodeCalled);
            resetTestVariables(this);

            eventObjectArg = {
                data: {
                    reset_linked_category: false,
                    level: 4,
                    hier_data: [1,2,3,4],
                    data_type: 'Category',
                    via_page_load: true
                }
            };
            this.pc.getSubLevelRequestEventObject._origRequest = {};
            this.pc.dialog = 'is not null';
            this.pc.getSubLevelResponse(eventObjectArg, 'raddish')

            Y.Assert.isNotNull(this.pc.dialog);
            Y.Assert.isTrue(buildTreeCalled);
            Y.Assert.isTrue(insertChildrenForNodeCalled);
            resetTestVariables(this);

            eventObjectArg = {
                data: {
                    reset_linked_category: false,
                    level: 4,
                    hier_data: [1,2,3,4],
                    data_type: 'Product',
                    via_page_load: false
                }
            };
            this.pc.getSubLevelResponse(eventObjectArg, 'raddish')

            Y.Assert.isNotNull(this.pc.dialog);
            Y.Assert.isTrue(buildTreeCalled);
            Y.Assert.isTrue(insertChildrenForNodeCalled);
            resetTestVariables(this);

            eventObjectArg = {
                data: {
                    reset_linked_category: true,
                    level: 8,
                    hier_data: [1,2,3,4],
                    data_type: 'Category',
                    via_page_load: true
                }
            };
            this.pc.getSubLevelResponse(eventObjectArg, 'raddish')

            Y.Assert.isNull(this.pc.dialog);
            Y.Assert.isTrue(buildTreeCalled);
            Y.Assert.isFalse(insertChildrenForNodeCalled);
            resetTestVariables(this);

            eventObjectArg = {
                data: {
                    reset_linked_category: true,
                    level: 4,
                    hier_data: []
                }
            };
            this.pc.getSubLevelResponse(eventObjectArg, 'raddish');

            Y.Assert.isNull(this.pc.dialog);
            Y.Assert.isTrue(buildTreeCalled);
            Y.Assert.isTrue(insertChildrenForNodeCalled);
            resetTestVariables(this);

            // Restore RightNow.Url
            RightNow.Url.getParameter = originalGetParameterMethod;
        }
    }));

     suite.add(new Y.Test.Case({
        name: "Permission function tests",

        "testIsPermissionedNode": function() {
            this.pc.data.attrs.verify_permissions = "Create";
            this.pc.data.js.permissionedProdcatIds = [42];

            Y.Assert.isTrue(this.pc.isPermissionedNode(42));
            Y.Assert.isFalse(this.pc.isPermissionedNode(-1));
            Y.Assert.isFalse(this.pc.isPermissionedNode(null));
        },

        "testUserHasFullProdcatPermissions": function() {
            this.pc.data.attrs.verify_permissions = "Batman";
            this.pc.data.js.permissionedProdcatIds = [42];
            Y.Assert.isFalse(this.pc.userHasFullProdcatPermissions());

            this.pc.data.attrs.verify_permissions = "None";
            Y.Assert.isTrue(this.pc.userHasFullProdcatPermissions());

            this.pc.data.attrs.verify_permissions = "Batman";
            this.pc.data.js.permissionedProdcatIds = [];
            Y.Assert.isTrue(this.pc.userHasFullProdcatPermissions());
        }
     }));

    return suite;
}).run();
