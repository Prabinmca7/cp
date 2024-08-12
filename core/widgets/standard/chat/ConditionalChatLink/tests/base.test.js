UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'ConditionalChatLink_0'
}, function (Y, widget, baseSelector) {
    var conditionalChatLinkTests = new Y.Test.Suite({
        name: "standard/chat/ConditionalChatLink",

        setUp: function() {
            this.origMakeRequest = RightNow.Ajax.makeRequest;
            RightNow.Ajax.makeRequest = function () {
                RightNow.Ajax.makeRequest.calledWith = Array.prototype.slice.call(arguments);
            };
        },

        tearDown: function() {
            RightNow.Ajax.makeRequest = this.origMakeRequest;
        },
    });

    conditionalChatLinkTests.add(new Y.Test.Case({
        name: "Tests for Conditional Chat Link (CCL)",

        // @@@ QA 160624-000170
        "Verify that the generated ChatData parameter is different with every call": function()
        {
            var firstCall = widget._generateEncodedChatData();
            var secondCall = widget._generateEncodedChatData();

            Y.Assert.areNotSame(firstCall, secondCall);
        },

        // @@@ QA 170104-000058
        "Verify that the link url does not lose product and category": function()
        {
            var initialResult = {};
            initialResult.stats = { availableSessionCount: 2, expectedWaitSeconds: 30 };
            widget._eo.data.prod = 2;
            widget._eo.data.cat = 1;
            widget._onQueueReceived(initialResult);
            Y.Assert.isTrue(widget._linkUrl.search("p/2") > 0);
            Y.Assert.isTrue(widget._linkUrl.search("c/1") > 0);
        },

        // @@@ QA 180119-000001
        "Verify that the link url does not lose product and category when enable_availability_check is false": function()
        {
            var initialResult = {};
            widget.data.attrs.enable_availability_check = false;
            widget._eo.data.prod = 4;
            widget._eo.data.cat = 3;
            widget._onQueueReceived(initialResult);
            Y.Assert.isTrue(widget._linkUrl.search("p/4") > 0);
            Y.Assert.isTrue(widget._linkUrl.search("c/3") > 0);
        },

        "Verify that for Conditional Chat Unavailable link pointer-events is set none": function()
        {
            var initialResult = {};
            initialResult.stats = { availableSessionCount: 0, expectedWaitSeconds: 50 };
            widget._onQueueReceived(initialResult);
            var transcript = Y.one(widget.baseSelector)
            var post = transcript.getById("rn_ConditionalChatLink_0");
            Y.Assert.isNotNull(post,"Failed to find rn_ConditionalChatLink_0");
            Y.Assert.isTrue(post.get('innerHTML').indexOf('pointer-events: none;cursor: default;') > 0);
        },

         "Verify that for Conditional Chat Unavailable hours link pointer-events is set none": function()
        {
            var initialResult = {};
            initialResult.stats = { availableSessionCount: 2, expectedWaitSeconds: 30 };
            initialResult.out_of_hours = 1;
            widget._onQueueReceived(initialResult);
            var transcript = Y.one(widget.baseSelector)
            var post = transcript.getById("rn_ConditionalChatLink_0");
            Y.Assert.isNotNull(post,"Failed to find rn_ConditionalChatLink_0");
            Y.Assert.isTrue(post.get('innerHTML').indexOf('pointer-events: none;cursor: default;') > 0);
        },

         "Verify that the default message is displayed when enable_availability_check is set to false": function()
        {
            var initialResult = {};
            widget.data.attrs.enable_availability_check = false;
            widget._onQueueReceived(initialResult);
            var transcript = Y.one(widget.baseSelector);
            var post = transcript.getById("rn_ConditionalChatLink_0_DefaultMessage");
            Y.Assert.isNotNull(post,"Failed to find rn_ConditionalChatLink_0_DefaultMessage.");
        },

         "Verify that context info is not sent to the ajax endpoint": function()
        {
            widget._eo = new RightNow.Event.EventObject(widget, {data: {
                wait_threshold: 0,
                min_agents_avail: 0,
                interface_id: 1,
                contact_email: 'contact_email',
                contact_fname: 'contact_fname',
                contact_lname: 'contact_lname',
                prod: 1,
                cat: 2,
                c_id: 11,
                org_id: 0,
                cacheable: true,
                avail_type: 'sessions',
                ccl: true,
                name: 'ConditionalChatLink'
            }});

            widget._onPollingTimerElapsed();

            // original event object should not be changed
            Y.Assert.isNotNull(widget._eo.data['rn_contextData']);
            Y.Assert.isNotNull(widget._eo.data['rn_contextToken']);
            Y.Assert.isNotNull(widget._eo.data['rn_formToken']);
            Y.Assert.isNotNull(widget._eo.data['rn_timestamp']);
            Y.Assert.isNotNull(widget._eo.data['w_id']);

            // ajaxRequest should not have context information
            Y.Assert.isUndefined(RightNow.Ajax.makeRequest.calledWith[1].rn_contextData);
            Y.Assert.isUndefined(RightNow.Ajax.makeRequest.calledWith[1].rn_contextToken);
            Y.Assert.isUndefined(RightNow.Ajax.makeRequest.calledWith[1].rn_formToken);
            Y.Assert.isUndefined(RightNow.Ajax.makeRequest.calledWith[1].rn_timestamp);
            Y.Assert.isUndefined(RightNow.Ajax.makeRequest.calledWith[1].w_id);

            // contact_lname, contac_fname, and contact_email is removed
            // as per QA 210903-000085
            Y.Assert.isNotNull(widget._eo.data['contact_email']);
            Y.Assert.isNotNull(widget._eo.data['contact_fname']);
            Y.Assert.isNotNull(widget._eo.data['contact_lname']);
        },
    }));
    return conditionalChatLinkTests;
}).run();
