UnitTest.addSuite({ type: UnitTest.Type.Widget, instanceID: 'VisualProductCategorySelector_0'
}, function(Y, widget, baseSelector){
    var suite = new Y.Test.Suite({
        name: "standard/navigation/VisualProductCategorySelector"
    });

    function getItemVisibility() {
        var items = {
            visibleItems: [],
            invisibleItems: []
        };

        Y.all('.rn_Item').each(function(node, index) {
            if(node.hasClass("rn_Hidden")) {
                items.invisibleItems.push(index);
            }
            else {
                items.visibleItems.push(index);
            }
        });

        items.visibleItems = items.visibleItems.toString();
        items.invisibleItems = items.invisibleItems.toString();
        return items;
    }

    function getPaginationLinkInfo() {
        var linkInfo = {
            linkText: [],
            activeLink: null
        };

        Y.all('.rn_ItemPagination a').each(function(node, index) {
            linkInfo.linkText.push(Y.Lang.trim(node.get("text")));
            if(node.hasClass("rn_CurrentPage")) {
                linkInfo.activeLink = index;
            }
        });

        linkInfo.linkText = linkInfo.linkText.toString();
        return linkInfo;
    }

    suite.add(new Y.Test.Case({
        name: "Paged pagination Tests",

        "Initially the previous button is not visible": function() {
            Y.Assert.isTrue(Y.one(".rn_ItemPagination a.rn_PreviousPage").hasClass("rn_Hidden"));
        },

        "Clicking next reveals both previous and next buttons, updates active link, and 'scrolls'": function() {
            var itemVisibility = getItemVisibility(),
                linkInfo = getPaginationLinkInfo();

            Y.one("a.rn_ForwardPage").simulate("click");

            var itemVisibilityAfterClick = getItemVisibility(),
                linkInfoAfterClick = getPaginationLinkInfo();

            Y.Assert.areNotSame(itemVisibility.visibleItems, itemVisibilityAfterClick.visibleItems);
            Y.Assert.areNotSame(itemVisibility.invisibleItems, itemVisibilityAfterClick.invisibleItems);
            Y.Assert.areSame(linkInfo.linkText, linkInfoAfterClick.linkText);
            Y.Assert.areNotSame(linkInfo.activeLink, linkInfoAfterClick.activeLink);

            Y.Assert.isFalse(Y.one(".rn_ItemPagination a.rn_ForwardPage").hasClass("rn_Hidden"));
            Y.Assert.isFalse(Y.one(".rn_ItemPagination a.rn_PreviousPage").hasClass("rn_Hidden"));
        },

        "Clicking the current 'page' has no effect": function() {
            var itemVisibility = getItemVisibility(),
                linkInfo = getPaginationLinkInfo();

            Y.one(".rn_ItemPagination a.rn_CurrentPage").simulate("click");

            var itemVisibilityAfterClick = getItemVisibility(),
                linkInfoAfterClick = getPaginationLinkInfo();

            Y.Assert.areSame(itemVisibility.visibleItems, itemVisibilityAfterClick.visibleItems);
            Y.Assert.areSame(itemVisibility.invisibleItems, itemVisibilityAfterClick.invisibleItems);
            Y.Assert.areSame(linkInfo.linkText, linkInfoAfterClick.linkText);
            Y.Assert.areSame(linkInfo.activeLink, linkInfoAfterClick.activeLink);
        },

        "Clicking on a 'page' which is not the 'current' one 'scrolls'; clicking on the first 'page' hides previous link": function() {
            var itemVisibility = getItemVisibility(),
                linkInfo = getPaginationLinkInfo();

            Y.one(".rn_ItemPagination a:not(.rn_CurrentPage):not(.rn_PreviousPage)").simulate("click");

            var itemVisibilityAfterClick = getItemVisibility(),
                linkInfoAfterClick = getPaginationLinkInfo();

            Y.Assert.areNotSame(itemVisibility.visibleItems, itemVisibilityAfterClick.visibleItems);
            Y.Assert.areNotSame(itemVisibility.invisibleItems, itemVisibilityAfterClick.invisibleItems);
            Y.Assert.areSame(linkInfo.linkText, linkInfoAfterClick.linkText);
            Y.Assert.areNotSame(linkInfo.activeLink, linkInfoAfterClick.activeLink);
            Y.Assert.areSame(1, linkInfoAfterClick.activeLink);

            Y.Assert.isTrue(Y.one("a.rn_PreviousPage").hasClass("rn_Hidden"));
        },

        "Clicking on the last 'page' hides the next link": function() {
            // Second to the last, becuase the last is the 'next' link
            var links = Y.all(".rn_ItemPagination a"),
                linkCount = links.size();

            links.item(linkCount - 2).simulate("click");

            Y.Assert.isTrue(Y.one(".rn_ItemPagination a.rn_ForwardPage").hasClass("rn_Hidden"));
        }
    }));

    return suite;
}).run();
