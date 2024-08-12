UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'SocialBookmarkLink_0'
}, function(Y, widget, baseSelector){
    var socialBookmarkLinkTests = new Y.Test.Suite({
        name: "standard/search/SocialBookmarkLink",

        setUp: function(){
            var testExtender = {
                initValues: function() {
                    this.instanceID = 'SocialBookmarkLink_0';
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.widgetData = this.instance.data;
                },
                dialogIsHidden: function() {
                    return !!Y.one('#rnDialog1').ancestor('.yui3-panel-hidden');
                },
                dialogIsShown: function() {
                    return !this.dialogIsHidden();
                }
            };

            for(var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    socialBookmarkLinkTests.add(new Y.Test.Case({
        name: "Event Handling and Operation for social discussion",

        /**
         * Tests if request is sent and panel displays if response is error free
         */
        testClick: function() {
            this.initValues();
            var trigger = Y.one('#rn_' + this.instanceID + '_Link');
            UnitTest.overrideMakeRequest(widget.data.attrs.check_question_exist_ajax);
            trigger.simulate('click');
            widget._onResponseReceived({errors: null});
            var container = Y.one('#rn_' + this.instanceID + '_Panel').get('parentNode');
            Y.assert(!container.hasClass('yui3-panel-hidden'));
        },

        /**
         * Tests if a dialog displays when the response contains error
         */
        "A response indicating an error displays custom error message": function() {
            var selector = '#rnDialog1';
            widget._onResponseReceived({result: false, errors: [{externalMessage: "Cannot find question"}]});

            Y.assert(this.dialogIsShown());
            Y.assert(Y.one(selector));
            Y.assert(Y.one(selector).get('text').indexOf('Cannot find question') > -1);
        },

        "Social discussion link is hidden if question's status is not active": function() {
            Y.Assert.isFalse(Y.one(baseSelector).hasClass("rn_Hidden"));
            RightNow.Event.fire("evt_inlineModerationStatusUpdate", new RightNow.Event.EventObject(widget, {data: {object_data: {updatedObject: {objectType: "CommunityQuestion", statusWithTypeID: 23, ID: 5}}}}));
            Y.Assert.isTrue(Y.one(baseSelector).hasClass("rn_Hidden"));
            RightNow.Event.fire("evt_inlineModerationStatusUpdate", new RightNow.Event.EventObject(widget, {data: {object_data: {updatedObject: {objectType: "CommunityQuestion", statusWithTypeID: 22, ID: 5}}}}));
            Y.Assert.isFalse(Y.one(baseSelector).hasClass("rn_Hidden"));
        }

    }));

    return socialBookmarkLinkTests;
});
UnitTest.run();
