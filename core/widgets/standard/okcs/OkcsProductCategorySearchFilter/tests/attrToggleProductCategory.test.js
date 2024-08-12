UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'OkcsProductCategorySearchFilter_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/okcs/OkcsProductCategorySearchFilter",

        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.instanceID = 'OkcsProductCategorySearchFilter_0'
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.toggleCategory = Y.one('#rn_AccordTriggerCategory');
                    this.categoryContainer = Y.one('#rn_ContainerCategory');
                    this.toggleProduct = Y.one('#rn_AccordTriggerProduct');
                    this.productContainer = Y.one('#rn_ContainerProduct');
                }
            };

            for (var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    suite.add(new Y.Test.Case({
        name: "Event Handling and Operation",

        "Verify widget displays when category toggle element is clicked": function() {
            this.initValues();
            this.toggleCategory.simulate('click');
            Y.Assert.areSame(this.categoryContainer.getStyle('display'), 'block');
        },

        "Verify widget displays when product toggle element is clicked": function() {
            this.initValues();
            this.toggleProduct.simulate('click');
            Y.Assert.areSame(this.productContainer.getStyle('display'), 'block');
        }
    }));

    return suite;
}).run();
