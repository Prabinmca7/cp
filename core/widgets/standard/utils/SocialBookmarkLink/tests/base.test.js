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
        name: "Event Handling and Operation",

        /**
         * Tests if request is sent and panel displays if response is error free
         */
        testClick: function() {
            this.initValues();
            var trigger = Y.one('#rn_' + this.instanceID + '_Link');
            trigger.simulate('click');
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
        }
    }));

    return socialBookmarkLinkTests;
});
UnitTest.run();
