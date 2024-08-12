UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'OkcsSetNotificationFrequency_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/okcs/OkcsSetNotificationFrequency",

        setUp: function(){
            var testExtender = {
                initValues : function() {
                   this.instanceID = 'OkcsSetNotificationFrequency_0';
                   this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                   this.widgetData = this.instance.data;
                   this.submitButton = Y.one("#rn_" + this.instanceID + "_SubmitButton");
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

        "Verify default button label before submit": function() {
            this.initValues();
            Y.Assert.areSame(this.submitButton.getHTML().trim(), widget.data.attrs.label_submit_button, "title is correct!");
        },

        "Verify the response after submit": function() {
            this.initValues();
            var responseData = {};
            var eo = new RightNow.Event.EventObject(null, {
                data: responseData
            });
            widget._subscriptionScheduleDeliveryMessage(responseData);
            Y.Assert.areSame(this.subscribeButton.getHTML().trim(), widget.data.attrs.label_submit_button, "label is correct!");
        },
    }));

    return suite;
}).run();
