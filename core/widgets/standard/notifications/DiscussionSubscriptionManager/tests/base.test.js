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
        "Question unsubscribe action is submitted to the server": function() {
            this.unsubscribeButton.simulate('click');
            var request = this.makeRequestCalledWith;
            Y.Assert.areSame(widget.data.attrs.delete_social_subscription_ajax, request[0]);
            Y.Assert.areSame(request[1].type,"Question");
            widget._onResponse({success: false, errors: [{externalMessage: "User is not logged in", errorCode: "ERROR_USER_NOT_LOGGED_IN"}]}, {data: {action: 'unsubscribe'}});
            Y.Assert.isNull(Y.one('.rn_BannerAlert'));
        },
        "Unsubscribe All action link is present": function() {
            Y.Assert.isObject(this.unsubscribeAll, 'No unsubscribe all link exist');
        },
        "Question unsubscribe all action is submitted to the server": function() {
            this.unsubscribeAll.simulate('click');
            Y.Assert.isFalse(Y.one('#rnDialog1').hasClass("rn_Hidden"));
            Y.all("#rnDialog1 .yui3-widget-ft button").item(0).simulate('click');
            var request = this.makeRequestCalledWith;
            Y.Assert.areSame(widget.data.attrs.delete_social_subscription_ajax, request[0]);
            Y.Assert.areSame(request[1].id, "-1");
        },
        "Question unsubscribe all action is not submitted to the server": function() {
            this.unsubscribeAll.simulate('click');
            Y.Assert.isFalse(Y.one('#rnDialog1').hasClass("rn_Hidden"));
            Y.all("#rnDialog1 .yui3-widget-ft button").item(1).simulate('click');
            var request = this.makeRequestCalledWith;
            Y.Assert.isNull(request);
        }
    }));
    return suite;
}).run();