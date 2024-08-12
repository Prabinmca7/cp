UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'Polling_0'
}, function(Y, widget, baseSelector){
    var instanceCount = 0;

    var pollingTests = new Y.Test.Suite({
        name: "standard/surveys/Polling",
        setUp: function(){
            var testExtender = {
                createInstance : function() {
                    this.initValues();

                    // Generate a new instance if not the first instance, in order to circumvent purged buttons
                    if (instanceCount >= 1) {
                        this.widgetData = this.instance.data;
                        this.instance = new RightNow.Widgets.Polling(this.widgetData, this.instanceID, Y);
                    }
                    ++instanceCount;
                },

                initValues : function() {
                    this.instanceID = 'Polling_0';
                    this.baseID = 'rn_' + this.instanceID + '_';
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.widgetData = this.instance.data;
                },

            };

            for(var item in this.items)
            {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    pollingTests.add(new Y.Test.Case(
    {
        name : "Test: Submit Button Tests",

        ///@@@ QA#140127-000045 ensure that submit button is disabled for admin console preview
        'Verify that the submit button is disabled for the admin_console mode': function() {
            this.initValues();
            if (this.widgetData.attrs.admin_console === 'true')
                Y.Assert.isFalse(this.instance._actionDialogButtonClicked());
        }
    }));

    return pollingTests;
});
UnitTest.run();
