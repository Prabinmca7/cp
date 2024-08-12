UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'FacetFilter_0'
}, function(Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/okcs/FacetFilter",
        
        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.instanceID = 'FacetFilter_0';
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
            var content = Y.one(baseSelector + '_Content'),
                span = Y.all('span')._nodes;
            Y.Assert.isNotNull(content);
            Y.Assert.isTrue(span[2].hasAttribute('class', 'rn_Product'));
            Y.Assert.areSame(span[2].innerHTML, 'Document Types: ');
            Y.Assert.isTrue(span[3].hasAttribute('class', 'rn_filterChoice'));
            Y.Assert.areSame(span[3].innerHTML, 'ARTICLES');
            Y.Assert.isTrue(span[4].hasAttribute('class', 'rn_FacetFilterClearIcon'));

            Y.Assert.isTrue(span[6].hasAttribute('class', 'rn_Product'));
            Y.Assert.areSame(span[6].innerHTML, 'Product: ');
            Y.Assert.isTrue(span[7].hasAttribute('class', 'rn_filterChoice'));
            Y.Assert.areSame(span[7].innerHTML, 'Mobile');
            Y.Assert.isTrue(span[8].hasAttribute('class', 'rn_FacetFilterClearIcon'));

            Y.Assert.isTrue(span[10].hasAttribute('class', 'rn_Product'));
            Y.Assert.areSame(span[10].innerHTML, 'Category: ');
            Y.Assert.isTrue(span[11].hasAttribute('class', 'rn_filterChoice'));
            Y.Assert.areSame(span[11].innerHTML, 'Cat1');
            Y.Assert.isTrue(span[12].hasAttribute('class', 'rn_FacetFilterClearIcon'));
        },

        "validate facet reset after click event": function() {
            this.initValues();
            var content = Y.one(baseSelector + '_Content'),
                span = Y.all('span')._nodes;
            Y.Assert.isNotNull(content);
            var resetButton = Y.one('.rn_ResetFilterBtn');
            //before reset button click
            Y.Assert.areSame(content._node.getAttribute('class'), 'rn_FacetFilter_Content ');

            resetButton.simulate('click');

            //After reset button click
            Y.Assert.areSame(content._node.getAttribute('class'), 'rn_FacetFilter_Content  rn_Hidden');
            
        }
    }));

    return suite;
});
UnitTest.run();
