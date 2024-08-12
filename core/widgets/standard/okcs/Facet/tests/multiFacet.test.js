UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'Facet_0'
}, function(Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/okcs/Facet",
        
        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.instanceID = 'Facet_0';
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.widgetData = this.instance.data;
                }
            };

            for (var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    suite.add(new Y.Test.Case({
        name: "Event Handling and Operation",

        setUp: function () {
            widget.searchSource().on('search', function() {
                // Prevent the search from happening.
                return false;
            });
        },

        "Validate data and DOM": function() {
            this.initValues();
            var content = Y.one('.rn_FacetsList'),
                ulNode = Y.all('ul');
                Y.Assert.isNotNull(content);
                Y.Assert.areSame(ulNode._nodes[0].children.length, 4);
                Y.Assert.areSame(ulNode._nodes[0].children[0].className, 'rn_DocTypes');
                Y.Assert.areSame(ulNode._nodes[0].children[1].className, 'rn_Collections');
                Y.Assert.areSame(ulNode._nodes[0].children[2].className, 'rn_FacetProduct');
                Y.Assert.areSame(ulNode._nodes[0].children[3].className, 'rn_FacetCategory');
        }
    }));

    return suite;
});
UnitTest.run();
