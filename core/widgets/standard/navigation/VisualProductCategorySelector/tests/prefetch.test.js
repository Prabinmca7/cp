UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'VisualProductCategorySelector_0'
}, function(Y, widget, baseSelector){
    var suite = new Y.Test.Suite({
        name: "standard/navigation/VisualProductCategorySelector"
    });

    suite.add(new Y.Test.Case({
        name: "Prefetch Behavior",

        setUp: function() {
            this.link = Y.one('.rn_ShowChildren');
            this.makeRequest = RightNow.Ajax.makeRequest;
            RightNow.Ajax.makeRequest = function () {
                RightNow.Ajax.makeRequest.called || (RightNow.Ajax.makeRequest.called = 0);
                RightNow.Ajax.makeRequest.called++;
                RightNow.Ajax.makeRequest.calledWith = Array.prototype.slice.call(arguments);
            };
        },

        tearDown: function() {
            Y.all('.rn_Loading').addClass('rn_Hidden');

            RightNow.Ajax.makeRequest = this.makeRequest;
        },

        "Sub-items are prefetched": function() {
            this.instance = new RightNow.Widgets.VisualProductCategorySelector(widget.data, widget.instanceID, Y);

            // Sub-items were fetched immediately when the widget is
            // instantiated.
            Y.Assert.areSame(1, RightNow.Ajax.makeRequest.called);

            // Call the widget's callback.
            var args = RightNow.Ajax.makeRequest.calledWith[2],
                items = args.data.data.items.split(','),
                link;

            args.successHandler.call(args.scope,
                {"result": {
                    "1": [[
                        {"id": 2,"label": "Android","hasChildren": true},
                        {"id": 3,"label": "Blackberry","hasChildren": false},
                        {"id": 4,"label": "iPhone","hasChildren": true}
                    ]],
                    "128": [[
                        {"id": 132,"label": "p1a","hasChildren": false},
                        {"id": 133,"label": "p1b","hasChildren": false},
                        {"id": 129,"label": "p2","hasChildren": true}
                    ]]
                }}
            );

            link = Y.one(baseSelector + ' .rn_ShowChildren[data-id="' + items[0] + '"]');
            Y.Assert.areSame(RightNow.Text.sprintf(widget.data.attrs.label_prefetched_sub_items, 3), link.get('text'));
        }
    }));

    return suite;
}).run();
