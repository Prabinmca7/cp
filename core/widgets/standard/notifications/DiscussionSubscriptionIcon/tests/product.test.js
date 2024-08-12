UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'DiscussionSubscriptionIcon_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/notifications/DiscussionSubscriptionIcon"
    });

    suite.add(new Y.Test.Case({
        name: "Inline Content Subscription",
        setUp: function () {
            this.unsubscribeDiv = Y.one(baseSelector+'_Unsubscribe');
            this.subscribedToProdDiv = Y.one(baseSelector + '_ProdSubscribed');
            RightNow.Ajax.makeRequest = Y.bind(this.makeRequestMock, this);
        },

        tearDown: function () {
            this.makeRequestCalledWith = null;
        },

        makeRequestMock: function () {
            this.makeRequestCalledWith = Array.prototype.slice.call(arguments);
        },


       "Unsubscribe action menu is submitted to the server": function() {
            this.unsubscribeDiv.simulate('click');
            var request = this.makeRequestCalledWith;
            Y.Assert.areSame(widget.data.attrs.delete_social_subscription_ajax, request[0]);
            widget._onResponse({success: false}, {data: {action: 'unsubscribe'}});
        },

        "Hide Unsubscribe on successfully unsubscribing": function () {
            widget._onResponse({success: true}, {data: {action: 'unsubscribe'}});
            Y.Assert.isTrue(this.unsubscribeDiv.hasClass("rn_Hidden"), "Unsubcribe div should be hidden");
            Y.Assert.isTrue(this.subscribedToProdDiv.hasClass("rn_Show"), "Subscribed to product div should be visible");
        }

    }));
    return suite;
}).run();