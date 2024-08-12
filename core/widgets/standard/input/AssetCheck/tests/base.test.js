UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: "AssetCheck_0"
}, function(Y, widget, baseSelector){
    var assetCheckTests = new Y.Test.Suite({
        name: "standard/input/AssetCheck",
        setUp: function(){
            Y.one(document.body).append('<div id="rn_ErrorLocation">');
        }
    });

    assetCheckTests.add(new Y.Test.Case(
    {
        name: "Event Handling and Operation",

        "An event is raised when product is not selected and the form is submitted": function() {
        	var actualWarning = null;
            var test = function(type, args) {
            	actualWarning = args[0].data.errorMsg;
            };

            Y.Assert.isFalse(Y.one("#rn_ProductCatalogInput_1_ProductCatalog_Button").hasClass("rn_ErrorField"));
            Y.Assert.isFalse(Y.one("#rn_ProductCatalogInput_1_Label").hasClass("rn_ErrorLabel"));

            RightNow.Event.subscribe("evt_noProductSelected", test, this);
            Y.one(baseSelector + "_SerialNumberSubmit").simulate("click");

            Y.Assert.areSame(widget.data.attrs.label_invalid_product_warning, actualWarning);
        },

        "Serial number should be url encoded": function() {
            widget.serialized = true;
            widget.productID = 7;

            var serialNo = "123456789!@#$%^&*()",
                navigateUrl;

            Y.one(baseSelector + "_AssetSerialNumberInput").set("value", serialNo);

            RightNow.Url.navigate = function(url) {
                navigateUrl = url;
            };

            var expectedPostData = {
                productID: widget.productID,
                serialNumber: serialNo
            };

            UnitTest.overrideMakeRequest(
                widget.data.attrs.serial_number_validate_ajax,
                expectedPostData,
                "_onSerialNumberValidationResponse",
                widget,
                widget.productID
            );

            Y.one(baseSelector + "_SerialNumberSubmit").simulate("click");

            Y.Assert.areSame(widget.data.attrs.redirect_register_asset + "/asset_id/" + widget.productID + "/serial_no/123456789!%40%23%24%25%5E%26*()" + widget.data.attrs.add_params_to_url, navigateUrl);
        }
    }));
    return assetCheckTests;
});
UnitTest.run();
