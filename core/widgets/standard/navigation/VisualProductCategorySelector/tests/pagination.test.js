UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'VisualProductCategorySelector_0'
}, function(Y, widget, baseSelector){
    var suite = new Y.Test.Suite({
        name: "standard/navigation/VisualProductCategorySelector"
    });

    function checkInitialItemsForProperVisibility () {
        Y.Assert.isFalse(widget.paginationNode.hasClass('rn_Hidden'));
        Y.all('.rn_Item').each(function(node, index) {
            if(index < 2){
                Y.Assert.isTrue(!node.hasClass("rn_Hidden"));
            }
            else {
                Y.Assert.isTrue(node.hasClass("rn_Hidden"));
            }
        });
    }

    suite.add(new Y.Test.Case({
        name: "pagination Tests",

        "Pagination does not automatically capture focus on page load when initial_focus is false": function() {
            Y.Assert.areNotSame(Y.Node(document.activeElement).getHTML(), Y.one(".rn_ItemLink").getHTML());
        },

        "Initially only the first two items are visible": function() {
            checkInitialItemsForProperVisibility();

            Y.Assert.isFalse(Y.one("a.rn_ForwardPage").hasClass("rn_Disabled"));
            Y.Assert.isTrue(Y.one("a.rn_PreviousPage").hasClass("rn_Disabled"));
        },

        "Previous button should not effect visibility when on first page (or item set)": function() {
            Y.one("a.rn_PreviousPage").simulate("click");
            checkInitialItemsForProperVisibility();

            Y.Assert.isFalse(Y.one("a.rn_ForwardPage").hasClass("rn_Disabled"));
            Y.Assert.isTrue(Y.one("a.rn_PreviousPage").hasClass("rn_Disabled"));
        },

        "Clicking next shows the next two items, hides all others": function() {
            Y.one("a.rn_ForwardPage").simulate("click");

            Y.all('.rn_Item').each(function(node, index) {
                if(index > 1 && index < 4){
                    Y.Assert.isFalse(node.hasClass("rn_Hidden"));
                }
                else {
                    Y.Assert.isTrue(node.hasClass("rn_Hidden"));
                }
            });
            Y.Assert.isFalse(Y.one("a.rn_ForwardPage").hasClass("rn_Disabled"));
            Y.Assert.isFalse(Y.one("a.rn_PreviousPage").hasClass("rn_Disabled"));
        },

        "Clicking next another time shows the next two items, hides all others, disables next button": function() {
            Y.one("a.rn_ForwardPage").simulate("click");

            Y.all('.rn_Item').each(function(node, index) {
                if(index > 3){
                    Y.Assert.isTrue(!node.hasClass("rn_Hidden"));
                }
                else {
                    Y.Assert.isTrue(node.hasClass("rn_Hidden"));
                }
            });

            Y.Assert.isTrue(Y.one("a.rn_ForwardPage").hasClass("rn_Disabled"));
            Y.Assert.isFalse(Y.one("a.rn_PreviousPage").hasClass("rn_Disabled"));
        },

        "Clicking previous shows the previous two items, hides all others": function() {
            Y.one("a.rn_PreviousPage").simulate("click");

            Y.all('.rn_Item').each(function(node, index) {
                if(index > 1 && index < 4){
                    Y.Assert.isTrue(!node.hasClass("rn_Hidden"));
                }
                else {
                    Y.Assert.isTrue(node.hasClass("rn_Hidden"));
                }
            });

            Y.Assert.isTrue(!Y.one("a.rn_ForwardPage").hasClass("disabled"));
            Y.Assert.isTrue(!Y.one("a.rn_PreviousPage").hasClass("disabled"));
        }
    }));

    return suite;
}).run();
