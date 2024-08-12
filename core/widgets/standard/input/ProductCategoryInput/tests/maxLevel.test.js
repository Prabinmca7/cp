UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'ProductCategoryInput_0'
}, function(Y, widget, baseSelector){
    var tests = new Y.Test.Suite({
        name: "standard/input/ProductCategoryInput",

        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.button = Y.one("#rn_" + widget.instanceID + "_" + widget.data.js.data_type + "_Button");
                    this.treeContainer = Y.one("#rn_" + widget.instanceID + "_TreeContainer");
                    this.tree = Y.one("#rn_" + widget.instanceID + "_Tree");
                    if(widget.data.attrs.show_confirm_button_in_dialog) {
                        this.confirmButton = Y.one('#rn_' + widget.instanceID + '_' + widget.data.js.data_type +  '_ConfirmButton');
                        this.cancelButton = Y.one('#rn_' + widget.instanceID + '_' + widget.data.js.data_type + '_CancelButton');
                    }
                }
            };

            for(var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    tests.add(new Y.Test.Case({
        name: "Behavior",

        setUp: function () {
            this.makeRequest = RightNow.Ajax.makeRequest;
            RightNow.Ajax.makeRequest = function () {};
        },

        tearDown: function () {
            RightNow.Ajax.makeRequest = this.makeRequest;
        },

        "Parent items are shown as leafs when at the max level": function () {
            this.initValues();

            var mobilePhones = this.tree.all('.ygtvlabel').item(1);

            mobilePhones.simulate('click');

            RightNow.Event.fire("evt_menuFilterGetResponse", new RightNow.Event.EventObject(widget, {
                data: {
                    level: widget.data.attrs.max_lvl,
                    hier_data: [{
                        label: 'has children',
                        id: 'blah',
                        hasChildren: true
                    }]
                }
            }));

            var children = mobilePhones.ancestor('table').next();
            Y.Assert.isTrue(children.hasClass('ygtvchildren'));

            Y.Assert.areSame(1, children.all('.ygtvlabel').size());
            // Childless-nodes have 'ygtvln' instead of 'ygtvtp' class.
            Y.Assert.isNotNull(children.one('a').ancestor('.ygtvln'));
            Y.Assert.isNull(children.one('a').ancestor('.ygtvtp'));
        }

    }));



    return tests;
}).run();
