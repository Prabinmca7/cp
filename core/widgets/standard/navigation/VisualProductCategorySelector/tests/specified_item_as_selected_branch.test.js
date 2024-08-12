UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'VisualProductCategorySelector_0'
}, function(Y, widget, baseSelector){
    var suite = new Y.Test.Suite({
        name: "standard/navigation/VisualProductCategorySelector"
    });

    suite.add(new Y.Test.Case({
        name: "show_sub_items_for (with no branch limiting) Tests",

        setUp: function() {
            this.makeRequest = RightNow.Ajax.makeRequest;
            RightNow.Ajax.makeRequest = function () {
                RightNow.Ajax.makeRequest.calledWith = Array.prototype.slice.call(arguments);
            };
        },

        tearDown: function() {
            RightNow.Ajax.makeRequest = this.makeRequest;
        },

        "Initial set of (legit) items are the immediate children of item specified for show_sub_items_for and breadcrumb is not shown": function() {
            Y.Assert.areSame(3, Y.all('ul .rn_Item').size());

            var itemWithID2 = Y.one('.rn_ItemWithID2');
            Y.assert(itemWithID2);
            Y.assert(itemWithID2.one('.rn_ShowChildren').get('text').indexOf('more') > -1 );

            Y.assert(Y.one('.rn_ItemWithID3'));

            var itemWithID4 = Y.one('.rn_ItemWithID4');
            Y.assert(itemWithID4);
            Y.assert(itemWithID4.one('.rn_ShowChildren').get('text').indexOf('more') > -1 );

            Y.Assert.areSame(1, Y.one('.rn_BreadCrumb').all('span').size());
            Y.Assert.areSame(0, Y.one('.rn_BreadCrumb').all('a').size());
        },

        "Clicking to see child items shows breadcrumb": function() {
            widget._showChildren({
                id: 2,
                label: "Android",
                el: Y.one(baseSelector + "_Base_SubItems")
            });
            
//            this.wait(function(){
//                Y.Assert.areSame(2, RightNow.Ajax.makeRequest.calledWith[1].id);
//            }, 400);            
            var args = RightNow.Ajax.makeRequest.calledWith[2];
            Y.Assert.isNotNull(args);

            args.successHandler.call(args.scope,
                {"result": [[
                    {"id": 8, "label": "Motorola Droid", "hasChildren": false},
                    {"id": 9, "label": "Nexus One", "hasChildren": false},
                    {"id": 10, "label": "HTC", "hasChildren": false}
                ]]}
            );

            Y.Assert.areSame(2, Y.one('.rn_BreadCrumb').all('span').size());
            Y.Assert.areSame(1, Y.one('.rn_BreadCrumb').all('a').size());
        }
    }));

    return suite;
}).run();
