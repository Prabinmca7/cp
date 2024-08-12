UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'ContentTypeNotificationManager_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/okcs/ContentTypeNotificationManager",

        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.instanceID = 'ContentTypeNotificationManager_0';
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.widgetData = this.instance.data;
                }
            };

            for (var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    suite.add(new Y.Test.Case({
        name: "Event Handling and Operation",

        "Verify if name input field is throwing error message when empty": function() {
            this.initValues();
            widget._openDialog();
            this.submitButton = Y.one('.yui3-widget-ft .yui3-button');
            this.submitButton.simulate('click');
            if (Y.one("#rn_OkcsProductCategoryInput_1_ButtonVisibleText").get('value') === '') {
                var errorMessage = Y.one("#rn_" + this.instanceID + "_ErrorLocation a").get("innerHTML");
                Y.Assert.areSame(errorMessage, 'Name field is required');
            }
            widget._closeDialog();
        },

        "Verify if content type field is throwing error message when empty": function() {
            this.initValues();
            widget._openDialog();
            this.submitButton = Y.one('.yui3-widget-ft .yui3-button');
            this.subscriptionNameNode = Y.one('.rn_Name');
            this.subscriptionNameNode.set('value','Subscription Name');
            this.submitButton.simulate('click');
            if (Y.one("#rn_OkcsProductCategoryInput_1_ButtonVisibleText").get('value') === '') {
                var errorMessage = Y.one("#rn_" + this.instanceID + "_ErrorLocation a").get("innerHTML");
                Y.Assert.areSame(errorMessage, 'Content Type field is required');
            }
            widget._closeDialog();
        }
    }));

    return suite;
}).run();
