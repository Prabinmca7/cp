UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'SubscriptionButton_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/okcs/SubscriptionButton",

        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.instanceID = 'SubscriptionButton_0';
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.widgetData = this.instance.data;
                    this.subscribeDiv = Y.one("#rn_" + this.instanceID + "_Subscription");
                    this.subscribeButton = Y.one("#rn_" + this.instanceID + "_SubscribeButton");
                    this.Alert = Y.one("#rn_" + this.instanceID + "_Alert");
                }
            };

            for (var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    suite.add(new Y.Test.Case({
        name: "Check the display of button",

        "Verify default button label before subscribing": function() {
            this.initValues();
            Y.Assert.areSame(this.subscribeButton.getHTML().trim(), widget.data.attrs.label_sub_button, "title is correct!");
        },

        "Verify the button label after duplicate subscription": function() {
            this.initValues();
            var responseData = {"result":"OKDOM-USER0019"};
            var eo = new RightNow.Event.EventObject(null, {
                data: responseData
            });
            widget._displaySubscribeMessage(responseData);
            Y.Assert.areSame(this.subscribeButton.getHTML().trim(), widget.data.attrs.label_sub_button, "label is correct!");
        },
        
        "Verify the response after subscription": function() {
            this.initValues();
            var responseData = {"content":{"recordId":"01203604ee8082014e068d4662007937","versionId":"01203604ee8082014e068d4662007935","documentId":"FA8","title":"What preparations should I make before installing Windows?","version":"1.0","answerId":1000007},"owner":{"recordId":"01203604ee8082014e068d4662007f31","name":"Test User","externalId":1,"externalType":"CONTACT"},"categories":[],"recordId":"6F5F93FADEBA445E9CF183B79BA46498","active":true,"dateAdded":1436340582000,"dateModified":1436340582000,"lastCrawl":1436340582665,"name":"FA8","subscriptionType":"SUBSCRIPTIONTYPE_CONTENT"};
            var eo = new RightNow.Event.EventObject(null, {
                data: responseData
            });
            Y.Assert.areSame(1000007,responseData.content.answerId);
            widget._displaySubscribeMessage(responseData);
            Y.Assert.areSame(this.subscribeButton.getHTML().trim(), widget.data.attrs.label_unsub_button, "label is correct!");
        },
        
        "Verify the button label after subscription": function() {
            this.initValues();
            var responseData = {"content":{"recordId":"01203604ee8082014e068d4662007937","versionId":"01203604ee8082014e068d4662007935","documentId":"FA8","title":"What preparations should I make before installing Windows?","version":"1.0","answerId":1000007},"owner":{"recordId":"01203604ee8082014e068d4662007f31","name":"Test User","externalId":1,"externalType":"CONTACT"},"categories":[],"recordId":"6F5F93FADEBA445E9CF183B79BA46498","active":true,"dateAdded":1436340582000,"dateModified":1436340582000,"lastCrawl":1436340582665,"name":"FA8","subscriptionType":"SUBSCRIPTIONTYPE_CONTENT"};
            var eo = new RightNow.Event.EventObject(null, {
                data: responseData
            });
            widget._displaySubscribeMessage(responseData);
            Y.Assert.areSame(this.subscribeButton.getHTML().trim(), widget.data.attrs.label_unsub_button, "label is correct!");
        },

        "Verify the response after unsubscription": function() {
            this.initValues();
            var responseData = true;
            var eo = new RightNow.Event.EventObject(null, {
                data: responseData
            });
            widget._displayUnsubscribeMessage(responseData);
            Y.Assert.areSame(this.subscribeButton.getHTML().trim(), widget.data.attrs.label_sub_button, "label is correct!");
        }    
    }));

    return suite;
}).run();
