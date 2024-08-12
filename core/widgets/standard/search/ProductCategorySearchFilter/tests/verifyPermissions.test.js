UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'ProductCategorySearchFilter_0'
}, function(Y, widget, baseSelector){
    var tests = new Y.Test.Suite({
        name: "standard/search/ProductCategorySearchFilter",

        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.instanceID = 'ProductCategorySearchFilter_0';
                    this.ajaxCalled = false;
                    this.treeContainer = Y.one("#rn_" + this.instanceID + "_TreeContainer");
                    this.tree = Y.one("#rn_" + this.instanceID + "_Tree");
                    this.buttonLabel = Y.one("#rn_" + this.instanceID + "_ButtonVisibleText");
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
            RightNow.Ajax.makeRequest = function () {
                RightNow.Ajax.makeRequest.calledWith = Array.prototype.slice.call(arguments);
            };
        },

        tearDown: function () {
            RightNow.Ajax.makeRequest = this.makeRequest;
        },

        'Verify when clicked that the request is made and children added' : function() {
            this.initValues();
            var currentNode = this.tree.all(".ygtvlabel").item(1); // P1

            currentNode.simulate("click");
            Y.Assert.areSame(128, RightNow.Ajax.makeRequest.calledWith[1].id);

            widget.getSubLevelResponse("evt_menuFilterGetResponse", [new RightNow.Event.EventObject(widget, {
                data: {
                    data_type: "Product",
                    level: 2,
                    hier_data: [{
                        label: "has children129",
                        id: 129,
                        hasChildren: true
                    }]
                },
                filters: {
                    report_id: 176
                }
            })]);

            currentNode = this.tree.all(".ygtvlabel").item(2); // has children129
            Y.Assert.isFalse(currentNode.hasClass("rn_Disabled"));
            Y.Assert.isTrue(currentNode.get("text") === "has children129");
            Y.Assert.isTrue(Y.one('#ygtvt3').hasClass('ygtvlp'));

            currentNode.simulate("click");
            Y.Assert.areSame(129, RightNow.Ajax.makeRequest.calledWith[1].id);

            widget.getSubLevelResponse("evt_menuFilterGetResponse", [new RightNow.Event.EventObject(widget, {
                data: {
                    data_type: "Product",
                    level: 3,
                    hier_data: [{
                        label: 'has children130',
                        id: 130,
                        hasChildren: true
                    },
                    {
                        label: 'has no children135',
                        id: 135,
                        hasChildren: false
                    }]
                },
                filters: {
                    report_id: 176
                }
            })]);

            currentNode = this.tree.all('.ygtvlabel').item(3); // has children130
            Y.Assert.isFalse(currentNode.hasClass('rn_Disabled'));
            Y.Assert.isTrue(currentNode.get('text') === 'has children130');
            Y.Assert.isTrue(Y.one('#ygtvt4').hasClass('ygtvlp'));
            Y.Assert.isNull(Y.one('#ygtvt5'));
        }
    }));

    return tests;
});
UnitTest.run();
