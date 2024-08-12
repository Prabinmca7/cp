UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'LoadingIndicator_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/okcs/LoadingIndicator",

        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.instanceID = "LoadingIndicator_0";
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
        name: "Description of this test suite",

        "Verify Page Loading Event": function() {
            this.initValues();
            RightNow.Event.fire("evt_pageLoading");
            Y.Assert.isTrue(Y.one("#"+this.widgetData.attrs.dom_id_loading_icon).hasClass('rn_OkcsLoading'));
        },

        "Verify Page Loaded Event": function() {
            this.initValues();
            RightNow.Event.fire("evt_pageLoaded");
            Y.Assert.isFalse(Y.one("#"+this.widgetData.attrs.dom_id_loading_icon).hasClass('rn_OkcsLoading'));
            Y.Assert.isTrue(Y.one("#"+this.widgetData.attrs.dom_id_loading_icon).get('clientHeight') === 0);
        }
    }));

    return suite;
}).run();
