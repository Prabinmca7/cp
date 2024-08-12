UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'MobileProductCategoryInput_0',
    jsFiles: ['/euf/core/debug-js/RightNow.UI.Mobile.js']
}, function(Y, widget, baseSelector) {
    var tests = new Y.Test.Suite({
        name: "standard/input/MobileProductCategoryInput",

        setUp: function() {
            var testExtender = {
                initValues: function() {
                    this.button = Y.one("#rn_" + widget.instanceID + ((widget.data.attrs.name === 'Incident.Product') ? '_Product' : '_Category') + "_Launch");
                }
            };

            for (var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    tests.add(new Y.Test.Case({
        name: "Permissions",

        setUp: function() {
            this.makeRequest = RightNow.Ajax.makeRequest;
            RightNow.Ajax.makeRequest = function() {};
        },

        tearDown: function() {
            RightNow.Ajax.makeRequest = this.makeRequest;
        },

        "Check Permissions on Nodes": function() {
            this.initValues();

            if (widget.data.js.permissionedProdcatIds.indexOf(parseInt(widget.data.attrs.default_value, 10)) === -1) {
                // Default value was set to a value the user does not have permission to select. Fall back to default
                Y.Assert.areSame(this.button.get('textContent'), 'Select a product');
            }
            else {
                Y.Assert.areSame(this.button.get('textContent'), 'p1p2');
            }

            this.button.simulate('click');
            this.button = Y.one("#rn_" + widget.instanceID + ((widget.data.attrs.name === 'Incident.Product') ? '_Product' : '_Category') + '_Level1Input'); // p1
            Y.Assert.isNotNull(this.button);
            Y.Assert.areSame(this.button.one('input').get('value'), '128');
            Y.Assert.isTrue(this.button.one('label').hasClass('rn_HasChildren'));
            this.button.one('input').simulate('click');

            RightNow.Event.fire("evt_menuFilterGetResponse", new RightNow.Event.EventObject(widget, {
                data: {
                    level: 2,
                    hier_data: [{
                        label: '129',
                        id: 129,
                        hasChildren: true
                    }]
                }
            }));

            this.button = Y.one("#rn_" + widget.instanceID + ((widget.data.attrs.name === 'Incident.Product') ? '_Product' : '_Category') + '_Level2Input_128'); // 129
            Y.Assert.isNotNull(this.button);
            Y.Assert.areSame(this.button.one('input').get('value'), '129');
            Y.Assert.isTrue(this.button.one('label').hasClass('rn_HasChildren'));
            this.button.one('input').simulate('click');

            RightNow.Event.fire("evt_menuFilterGetResponse", new RightNow.Event.EventObject(widget, {
                data: {
                    level: 3,
                    hier_data: [{
                        label: '130',
                        id: 130,
                        hasChildren: true
                    }, {
                        label: '135',
                        id: 135,
                        hasChildren: false
                    }]
                }
            }));

            this.parentDiv = Y.one("#rn_" + widget.instanceID + ((widget.data.attrs.name === 'Incident.Product') ? '_Product' : '_Category') + '_Level3Input_129');
            Y.Assert.areSame(this.parentDiv.get('children').size(), 2); //Parent item and one permissioned item

            //All 129
            this.button = this.parentDiv.one('.rn_Parent');
            Y.Assert.isNotNull(this.button);
            Y.Assert.areSame(this.button.one('input').get('value'), '129');
            Y.Assert.isFalse(this.button.one('label').hasClass('rn_HasChildren'));

            //130
            this.button = this.parentDiv.one('.rn_SubItem')
            Y.Assert.isNotNull(this.button);
            Y.Assert.areSame(this.button.one('input').get('value'), '130');
            Y.Assert.isTrue(this.button.one('label').hasClass('rn_HasChildren'));
            // Node 135 was pruned because user didn't have permission and the node had no permissioned children
        }
    }));

    return tests;
}).run();
