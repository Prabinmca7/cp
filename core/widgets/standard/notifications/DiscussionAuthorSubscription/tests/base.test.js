UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'DiscussionAuthorSubscription_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/notifications/DiscussionAuthorSubscription"
    });

    suite.add(new Y.Test.Case({
        name: "Discussion Author Subscription",
        setUp: function () {
            this.subscribedDiv = Y.one(baseSelector + '_Subscribed');
            this.subscribeMeDiv = Y.one(baseSelector + '_SubscribeMe');
            this.subscribeMeCheckbox = Y.one(baseSelector + '_SubscribeMe_Check');
            RightNow.Ajax.makeRequest = Y.bind(this.makeRequestMock, this);
            RightNow.Event.on('evt_formToggleButton', this.checkFormToggleEventButtonFired, this);
        },

        tearDown: function () {
            this.makeRequestCalledWith = null;
        },

        makeRequestMock: function () {
            this.makeRequestCalledWith = Array.prototype.slice.call(arguments);
        },

        checkFormToggleEventButtonFired: function() {
            this.formToggleEventButtonFired = true;
        },

        "Product subscription check is submitted to the server": function() {
            RightNow.Event.fire("evt_productCategorySelected", new RightNow.Event.EventObject(widget, {data: {hierChain: [6]}}));
            var request = this.makeRequestCalledWith;
            Y.Assert.areSame(widget.data.attrs.fetch_prodcat_subscription_ajax, request[0]);
            Y.Assert.areSame(true, this.formToggleEventButtonFired, 'Form toggle event is not fired');
        },

        "User is not subscribed to the product": function() {
            widget._onResponse(false);
            Y.Assert.isTrue(this.subscribedDiv.hasClass("rn_Hidden"), "Subscribed to product div should be hidden");
            Y.Assert.areSame(widget.data.attrs.subscribe_me_default, this.subscribeMeCheckbox.get('checked'));
        },

        "User is subscribed to the product": function() {
            widget._onResponse(true);
            Y.Assert.isTrue(this.subscribeMeDiv.hasClass("rn_Hidden"), "Subscribe me checkbox should be hidden");
            Y.Assert.isFalse(this.subscribeMeCheckbox.get('checked'), "Subscribe checkbox should be unchecked");
        }
    }));
    return suite;
}).run();