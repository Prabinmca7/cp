UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'ContentType_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/okcs/ContentType",

        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.instanceID = 'ContentType_0'
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.contentTypes = Y.all(baseSelector + ' a');
                    this.toggle = Y.one('#rn_AccordTriggerContentType');
                    this.widgetContainer = Y.one('#rn_ContainerContentType');
                }
            };

            for (var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    suite.add(new Y.Test.Case({
        name: "Event Handling and Operation",

        "Verify widget displays when toggle element is clicked": function() {
            this.initValues();
            this.toggle.simulate('click');
            Y.Assert.areSame(this.widgetContainer.getStyle('display'), 'block');
        }
    }));

    return suite;
}).run();
