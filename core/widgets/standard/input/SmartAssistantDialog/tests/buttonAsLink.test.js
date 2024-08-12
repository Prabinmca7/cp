UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'SmartAssistantDialog_0'
}, function(Y, widget, baseSelector){
    var smartAssistantTests = new Y.Test.Suite({
        name: "standard/input/SmartAssistantDialog",
        setUp: function(){
            var testExtender = {
                initValues: function() {
                    this.instanceID = 'SmartAssistantDialog_0';
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.widgetData = this.instance.data;
                    this.yuiDialogID = "#rnDialog1";
                }
            };
            for(var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    }),
    testCases = [
        {
            name: "Test that dialog's cancel action is now a link",
            testResponse: function() {
                this.initValues();

                this.instance._displayResults("response", [{
                    data: {
                        result: {
                            sessionParam: "",
                            status: 1,
                            sa: {
                                canEscalate: true
                            }
                        }
                    }
                }]);

                var buttons = Y.all(this.yuiDialogID + ' .yui3-widget-ft button, ' + this.yuiDialogID + ' .yui3-widget-ft a');
                Y.Assert.isObject(buttons, "buttons don't exist!");
                Y.Assert.areSame(buttons.size(), 3);
                Y.Assert.areSame(buttons.item(0).get('tagName'), "BUTTON");
                Y.Assert.areSame(buttons.item(1).get('tagName'), "BUTTON");
                Y.Assert.areSame(buttons.item(2).get('tagName'), "A");
                Y.Assert.areSame(buttons.item(2).get('innerHTML'), this.widgetData.attrs.label_cancel_button);
                try{
                    Y.one(buttons.item(2)).simulate('click');
                    Y.assert(Y.one(this.yuiDialogID).ancestor('.yui3-panel-hidden'), "Dialog wasn't closed!");
                }
                catch(e) {
                    Y.Assert.isTrue(Y.UA.ie > 0);
                }
            }
        }
    ];
    for(var i = 0; i < testCases.length; i++){
        smartAssistantTests.add(new Y.Test.Case(testCases[i]));
    }
    return smartAssistantTests;
});
UnitTest.run();
