UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'OpenLogin_0'
}, function(Y, widget, baseSelector){
    var openLoginTests = new Y.Test.Suite({
        name: "standard/login/OpenLogin",
        setUp: function(){
            var testExtender = {
                initValues: function() {
                    this.instanceID = 'OpenLogin_0';
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.widgetData = this.instance.data;
                    this.selector = "#rn_" + this.instanceID;
                }
            };
            for(var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    openLoginTests.add(new Y.Test.Case({
        name: "OneClick",

        "Page navigates to proper endpoint when provider button is clicked": function() {
            this.initValues();

            if (this.widgetData.attrs.one_click_access_enabled) {
                if (Y.UA.ie) return; // IE builds up and clicks a link in order to capture referrer

                RightNow.Url.navigate = RightNow.Event.createDelegate(this, function(url) {
                    Y.Assert.areSame(this.widgetData.attrs.controller_endpoint + encodeURIComponent(this.widgetData.attrs.redirect_url), url);
                });
                Y.one("#rn_" + this.instanceID + "_ProviderButton").simulate('click');
            }
            else {
                Y.Assert.isTrue(false, "One click enabled attribute is not set");
            }
        }
    }));

    return openLoginTests;
});
UnitTest.run();
