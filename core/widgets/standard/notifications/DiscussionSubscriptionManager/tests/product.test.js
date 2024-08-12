UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'DiscussionSubscriptionManager_0'
}, function(Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/notifications/DiscussionSubscriptionManager"
    });

    suite.add(new Y.Test.Case({
        name: "Discussion Notification listing",
        setUp: function() {
            this.unsubscribeButton = Y.one(baseSelector).one("button[class=rn_Discussion_Delete]");
            this.unsubscribeAll = Y.one(baseSelector + '_UnsubscribeAll').one('a');
            this.addProdNotifButton = Y.one(baseSelector + '_AddButton');
            this.productSelector = Y.one(baseSelector + '_Dialog  button[id^="rn_ProductCategoryInput_"][id$="_Button"]')
            this.errorDisplay = Y.one(baseSelector + "_ErrorMessage");
            RightNow.Ajax.makeRequest = Y.bind(this.makeRequestMock, this);
        },
        tearDown: function() {
            this.makeRequestCalledWith = null;
        },
        makeRequestMock: function() {
            this.makeRequestCalledWith = Array.prototype.slice.call(arguments);
        },
        "Test delete action button is present": function() {
            Y.Assert.isObject(this.unsubscribeButton, 'No Button exist');
        },
        "Product unsubscribe action is submitted to the server": function() {
            this.unsubscribeButton.simulate('click');
            var request = this.makeRequestCalledWith;
            Y.Assert.areSame(widget.data.attrs.delete_social_subscription_ajax, request[0]);
            Y.Assert.areSame(request[1].type,"Product");
        },
        "Unsubscribe All action link is present": function() {
            Y.Assert.isObject(this.unsubscribeAll, 'No unsubscribe all link exist');
        },
        "Product unsubscribe all action is submitted to the server": function() {
            this.unsubscribeAll.simulate('click');
            Y.Assert.isFalse(Y.one('#rnDialog1').hasClass("rn_Hidden"));
            Y.all("#rnDialog1 .yui3-widget-ft button").item(0).simulate('click');
            var request = this.makeRequestCalledWith;
            Y.Assert.areSame(widget.data.attrs.delete_social_subscription_ajax, request[0]);
            Y.Assert.areSame(request[1].id, "-1");
        },
        "Product unsubscribe all action is not submitted to the server": function() {
            this.unsubscribeAll.simulate('click');
            Y.Assert.isFalse(Y.one('#rnDialog1').hasClass("rn_Hidden"));
            Y.all("#rnDialog1 .yui3-widget-ft button").item(1).simulate('click');
            var request = this.makeRequestCalledWith;
            Y.Assert.isNull(request);
        },
        "Add product notifications button is present": function() {
            Y.Assert.isObject(this.addProdNotifButton, 'No Add Notifications button exist');
        },
        "Add product notification action is submitted to the server": function() {
            /*var dialog = Y.one(baseSelector + '_Dialog');
            Y.Assert.isTrue(dialog.hasClass("rn_Hidden"));
            this.addProdNotifButton.simulate('click');
            // select the product voice plans
            Y.one('#ygtvlabelel3').simulate('click');
            var prodVoicePlan = dialog.all(".rn_DisplayButton span").item(0).get('text');

            Y.Assert.isFalse(dialog.hasClass("rn_Hidden"));
            var buttons =  Y.all("#rnDialog2 .yui3-widget-ft button");

            // cancel button
            Y.one(buttons.item(1)).simulate('click');
            var request = this.makeRequestCalledWith;
            Y.Assert.isNull(request);

            // click on add without selecting a product
            this.addProdNotifButton.simulate('click');
            // check whether default selected product on opening the dialog box is 0 i.e "Select a Product"
            Y.Assert.areSame(widget.prodCatID, 0);
            // fetch the text without selecting any product
            var noProdSelected = dialog.all(".rn_DisplayButton span").item(0).get('text');
            // since product selection is not retained the values should be different
            Y.Assert.areNotSame(prodVoicePlan, noProdSelected);
            // click on add button
            Y.one(buttons.item(0)).simulate('click');
            request = this.makeRequestCalledWith;
            // since no product is selected there should not be an ajax request
            Y.Assert.isNull(request);

            // click on add after selecting a product
            this.addProdNotifButton.simulate('click');
            // select a product
            this.wait(function(){
                Y.one('#ygtvlabelel3').simulate('click');
            }, 300);
            Y.one(buttons.item(0)).simulate('click');
            request = this.makeRequestCalledWith;
            // verify the url of the ajax request
            Y.Assert.areSame(widget.data.attrs.add_social_subscription_ajax, request[0]);*/
        },
        "Add product without selection shows error": function() {
            // click the outermost add button to make the product selector show up - no errors yet
            this.addProdNotifButton.simulate('click');
//            Y.Assert.isFalse(this.errorDisplay.hasClass("rn_ErrorMessage"));
            Y.Assert.areNotSame(Y.Node.getDOMNode(this.productSelector), document.activeElement);

            // click the inner add button without selecting a product - should display the error and focus the error link
            innerAddButton = Y.one('#rnDialog2 div.yui3-widget-ft button:first-child')
            innerAddButton.simulate('click');
            Y.Assert.isTrue(this.errorDisplay.hasClass("rn_ErrorMessage"));
            
            // error link isn't created until there is an error
            errorLink = this.errorDisplay.one('a')
            Y.Assert.isNotNull(errorLink);
            Y.Assert.areSame(Y.Node.getDOMNode(errorLink), document.activeElement);
            
            // now click the error link - should focus the selector
            errorLink.simulate('click');
            Y.Assert.areSame(Y.Node.getDOMNode(this.productSelector), document.activeElement);
            
        }
    }));
    return suite;
}).run();
