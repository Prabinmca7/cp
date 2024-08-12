UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'ProductCategoryInput_0'
}, function(Y, widget, baseSelector){
    var tests = new Y.Test.Suite({
        name: "standard/input/ProductCategoryInput",

        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.selectionEventFired = false;
                    this.button = Y.one("#rn_" + widget.instanceID + "_" + widget.data.js.data_type + "_Button");
                    this.treeContainer = Y.one("#rn_" + widget.instanceID + "_TreeContainer");
                    this.tree = Y.one("#rn_" + widget.instanceID + "_Tree");
                    RightNow.Event.on('evt_productCategorySelected', this.setSelectionEventFired, this);
                },
                setSelectionEventFired: function() {
                    this.selectionEventFired = true;
                }
            };

            for(var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    tests.add(new Y.Test.Case({
        name: "Permissions",

        setUp: function () {
            this.makeRequest = RightNow.Ajax.makeRequest;
            RightNow.Ajax.makeRequest = function () {};
        },

        tearDown: function () {
            RightNow.Ajax.makeRequest = this.makeRequest;
        },

        "Check Permissions on Nodes": function () {
            this.initValues();
            var currentNode = this.tree.all('.ygtvlabel').item(1); // P1

            // userprodonly has permission to nodes 0, 129, 120, 138
            Y.Assert.isTrue(currentNode.hasClass('rn_Disabled'));
            currentNode.simulate('click');

            RightNow.Event.fire("evt_menuFilterGetResponse", new RightNow.Event.EventObject(widget, {
                data: {
                    level: 2,
                    hier_data: [{
                        label: 'has children129',
                        id: 129,
                        hasChildren: true
                    }]
                }
            }));
            currentNode = this.tree.all('.ygtvlabel').item(2); // has children129
            Y.Assert.isFalse(currentNode.hasClass('rn_Disabled'));
            Y.Assert.isTrue(currentNode.get('text') === 'has children129');
            currentNode.simulate('click');

            RightNow.Event.fire("evt_menuFilterGetResponse", new RightNow.Event.EventObject(widget, {
                data: {
                    level: 3,
                    hier_data: [{
                        label: 'has children130',
                        id: 130,
                        hasChildren: true
                    },
                    {
                        label: 'has children135',
                        id: 135,
                        hasChildren: false
                    }]
                }
            }));
            currentNode = this.tree.all('.ygtvlabel').item(3); // has children130
            Y.Assert.isTrue(currentNode.hasClass('rn_Disabled'));
            Y.Assert.isTrue(currentNode.get('text') === 'has children130');
            // Node 135 was pruned because user didn't have permission and the node had no permissioned children
            Y.Assert.isTrue(this.tree.all('.ygtvlabel').size() === 4);
        },

        "Check selection event is fired for products over which user has permission": function() {
            this.initValues();
            // select product over which userprodonly does not have permission
            var currentNode = this.tree.all('.ygtvlabel').item(1); // P1
            currentNode.simulate('click');
            Y.Assert.areSame(false, this.selectionEventFired, 'Selection event should not be fired');

            // select product over which userprodonly has permission
            currentNode = this.tree.all('.ygtvlabel').item(2);
            currentNode.simulate('click');
            Y.Assert.areSame(true, this.selectionEventFired, 'Selection event should be fired');
        },

        "When a user has permissioned prodcats, user must pick a prodcat": function() {
            this.initValues();

            // Empty when no permissions are in play
            widget.data.js.permissionedProdcatIds = [];
            var result = widget._onValidate("submit", [{data: {error_location: true}}]);
            Y.Assert.isObject(result);

            // Not empty when user has permissions applied to them
            widget.data.js.permissionedProdcatIds = [0, 130, 142];
            var result = widget._onValidate("submit", [{data: {error_location: true}}]);
            Y.Assert.isFalse(result);
        }
    }));

    return tests;
}).run();
