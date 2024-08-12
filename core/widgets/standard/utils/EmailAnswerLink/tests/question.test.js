UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'EmailAnswerLink_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/utils/EmailAnswerLink"
    });

    suite.add(new Y.Test.Case({
        name: "Email Discussion",
        setUp: function () {
            this.emailDiscussionLink = Y.one(baseSelector + '_Link');
            RightNow.Ajax.makeRequest = Y.bind(this.makeRequestMock, this);
        },

        tearDown: function () {
            this.makeRequestCalledWith = null;
        },

        makeRequestMock: function () {
            this.makeRequestCalledWith = Array.prototype.slice.call(arguments);
        },


        "Dialog shows when link is clicked": function() {
            this.emailDiscussionLink.simulate('click');
            Y.Assert.isNull(Y.one('#rnDialog1').ancestor('.yui3-panel-hidden'));
            Y.one('#rnDialog1').all('.yui3-widget-ft button').item(1).simulate('click');
            Y.Assert.isObject(Y.one('#rnDialog1').ancestor('.yui3-panel-hidden'));
        },

        "Email discussion request is sent to server": function() {
            this.emailDiscussionLink.simulate('click');
            Y.one('#rnDialog1').all('.yui3-widget-ft button').item(0).simulate('click');
            var request = this.makeRequestCalledWith;
            Y.Assert.isNull(request);
            Y.one(baseSelector + '_InputRecipientEmail').set('value', 'bar@foo.com');
            Y.one('#rnDialog1').all('.yui3-widget-ft button').item(0).simulate('click');
            request = this.makeRequestCalledWith;
            Y.Assert.areSame(widget.data.attrs.send_discussion_email_ajax, request[0]);
        },

        "Emain discussion link hidden if question's status is not active": function() {
            Y.Assert.isFalse(Y.one(baseSelector).hasClass("rn_Hidden"));
            RightNow.Event.fire("evt_inlineModerationStatusUpdate", new RightNow.Event.EventObject(widget, {data: {object_data: {updatedObject: {objectType: "CommunityQuestion", statusWithTypeID: 23}}}}));
            Y.Assert.isTrue(Y.one(baseSelector).hasClass("rn_Hidden"));
            RightNow.Event.fire("evt_inlineModerationStatusUpdate", new RightNow.Event.EventObject(widget, {data: {object_data: {updatedObject: {objectType: "CommunityQuestion", statusWithTypeID: 22}}}}));
            Y.Assert.isFalse(Y.one(baseSelector).hasClass("rn_Hidden"));
        }
    }));
    return suite;
}).run();