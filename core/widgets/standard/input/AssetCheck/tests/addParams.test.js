UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'AssetCheck_0',
    jsFiles: [
        '/cgi-bin/{cfg}/php/cp/core/widgets/standard/input/ProductCatalogInput/logic.js'
    ]
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

        "Serial Number Input Textbox is disabled for non-serialized Products": function() {
            Y.Assert.areSame(Y.one(baseSelector + '_AssetSerialNumberInput').get("disabled"), true);
        },

        "An event is raised when product is not selected, errors are cleared once a product is selected": function() {
            var actualWarning,
                button = Y.one('#rn_ProductCatalogInput_1_ProductCatalog_Button'),
                label = Y.one('#rn_ProductCatalogInput_1_Label');

            var noSelectionHandler = function(type, args) {
                actualWarning = args[0].data.errorMsg;
            };

            Y.Assert.isFalse(button.hasClass('rn_ErrorField'));
            Y.Assert.isFalse(label.hasClass("rn_ErrorLabel"));

            RightNow.Event.subscribe("evt_noProductSelected", noSelectionHandler, this);

            // should not fire an ajax request because no product ID has been selected
            Y.one(baseSelector + '_SerialNumberSubmit').simulate('click');

            Y.Assert.isTrue(button.hasClass('rn_ErrorField'));
            Y.Assert.isTrue(label.hasClass("rn_ErrorLabel"));
            Y.Assert.areSame(actualWarning, "Please select a Product.");

            RightNow.Event.fire("evt_productSelectedFromCatalog", new RightNow.Event.EventObject(this, {data: {
                productID: 20,
                serialized: false
            }}));

            Y.Assert.isFalse(button.hasClass('rn_ErrorField'));
            Y.Assert.isFalse(label.hasClass("rn_ErrorLabel"));
        },

        "Show message when non-serialized product is selected": function() {
            RightNow.Event.fire('evt_WidgetInstantiationComplete');

            RightNow.Event.fire("evt_productSelectedFromCatalog", new RightNow.Event.EventObject(this, {data: {
                productID: 20,
                serialized: false
            }}));

            this.wait(function() {
                Y.Assert.areSame(widget.data.attrs.label_product_does_not_require_serial_number, Y.one('#rn_AssetCheck_0_ProductSelectedMsg').get('innerHTML'));
            }, 1);
        },

        "Form should go to asset registration page for a non-serialized asset": function() {
            widget.serialized = false;
            widget.productID = 20;

            var navigateUrl;

            RightNow.Url.navigate = function(url) {
                navigateUrl = url;
            };

            var expectedPostData = {
                productID: widget.productID
            };

            UnitTest.overrideMakeRequest(
                widget.data.attrs.serial_number_validate_ajax,
                expectedPostData
            );

            Y.one(baseSelector + '_SerialNumberSubmit').simulate('click');

            Y.Assert.areSame(widget.data.attrs.redirect_register_non_serialized_asset + widget.productID + widget.data.attrs.add_params_to_url + '/' + widget.data.attrs.additional_parameters, navigateUrl);
        }
    }));
    return assetCheckTests;
});
UnitTest.run();
