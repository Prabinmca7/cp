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
            this.subscribeDiv = Y.one(baseSelector+'_Subscribe');
            this.unsubscribeDiv = Y.one(baseSelector+'_Unsubscribe');
            RightNow.Ajax.makeRequest = Y.bind(this.makeRequestMock, this);
        },

        tearDown: function () {
            this.makeRequestCalledWith = null;
        },

        makeRequestMock: function () {
            this.makeRequestCalledWith = Array.prototype.slice.call(arguments);
        },


        "Subscribe action menu is submitted to the server": function() {
            this.subscribeDiv.simulate('click');
            var request = this.makeRequestCalledWith;
            Y.Assert.areSame(widget.data.attrs.add_social_subscription_ajax, request[0]);
            Y.Assert.isTrue(Y.one(baseSelector + '_Subscription').hasClass("rn_Hidden"));
            widget._onResponse({success: false, errors: [{externalMessage: "User is not logged in", errorCode: "ERROR_USER_NOT_LOGGED_IN"}]}, {data: {action: 'subscribe'}});
            Y.Assert.isNull(Y.one('.rn_BannerAlert'));
        },

        "Hide Subscribe on successful subscription": function () {
            widget._onResponse({success: true}, {data: {action: 'subscribe'}});
            Y.Assert.isFalse(this.unsubscribeDiv.hasClass("rn_Hidden"), "Unsubcribe div should be hidden");
        }
    }));
    return suite;
}).run();