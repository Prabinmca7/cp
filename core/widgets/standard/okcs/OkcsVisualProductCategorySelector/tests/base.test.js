UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'OkcsVisualProductCategorySelector_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/okcs/OkcsVisualProductCategorySelector",

        setUp: function(){
            var testExtender = {
                initValues : function() {

                }
            };

            for (var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    suite.add(new Y.Test.Case({
        name: "Description of this test suite",

        "Description of this test case": function() {
            this.initValues();

        }
    }));

    return suite;
}).run();
