UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'ProductCatalogInput_0',
    jsFiles: [
        '/cgi-bin/{cfg}/php/cp/core/widgets/standard/input/ProductCatalogInput/logic.js'
    ]
}, function(Y, widget, baseSelector){
    var productCatalogInputTests = new Y.Test.Suite({
        name: 'standard/input/ProductCatalogInput',

        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.instanceID = 'ProductCatalogInput_0';
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.widgetData = this.instance.data;
                    this.ajaxCalled = false;
                    this.iterations = 2;
                    this.treeContainer = Y.one("#rn_" + this.instanceID + "_TreeContainer");
                    this.tree = Y.one("#rn_" + this.instanceID + "_Tree");
                    this.button = Y.one("#rn_" + this.instanceID + "_ProductCatalog_Button");
                    this.buttonLabel = Y.one("#rn_" + this.instanceID + "_ButtonVisibleText");
                    if(this.widgetData.attrs.show_confirm_button_in_dialog)
                    {
                        this.confirmButton = Y.one('#rn_' + this.instanceID + '_ProductCatalog_ConfirmButton');
                        this.cancelButton = Y.one('#rn_' + this.instanceID + '_ProductCatalog_CancelButton');
                    }
                }
            };

            for(var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });
    productCatalogInputTests.add(new Y.Test.Case({
        name: "Events",

        testGetNoSelectionResponse: function() {
            this.initValues();
            RightNow.Event.fire('evt_WidgetInstantiationComplete');
            this.validateResponse(this.instance._onValidate("submit", [{data: {error_location: true}}]), null);
        },

        validateResponse: function(returnVal, selected) {
            if (selected === false) {
                Y.Assert.isFalse(returnVal);
            }
            else {
                if (selected === null) {
                    // Validating against no-selection.
                    // But if an item was previously set via url param or attribute,
                    // get that.
                    var defaultVal = this.widgetData.attrs.default_value ||
                        RightNow.Url.getParameter('product_id');
                    if (defaultVal) {
                        defaultVal = defaultVal.split(',');
                        selected = parseInt(defaultVal[defaultVal.length - 1], 10);
                    }
                }
                Y.Assert.isInstanceOf(RightNow.Event.EventObject, returnVal);
                Y.Assert.areSame(this.widgetData.attrs.name, returnVal.data.name);
                Y.Assert.areSame(selected, returnVal.data.value);
            }
        }
    }));
    productCatalogInputTests.add(new Y.Test.Case({
        name: "UI Functional Tests",

        isHidden: function() {
            var treeParent = this.treeContainer.get('parentNode');
            return treeParent.hasClass('yui3-panel-hidden') && this.tree.getStyle("display") === "none";
        },

        isVisible: function() {
            return !this.isHidden();
        },

        /**
         * Test of the ProductCatalogSearchFilter's basic UI functionality to ensure
         * that it is working properly. Test the opening and closing of the menu and
         * whether the selection of a product/Catalog will correctly show up as the
         * button's text.
         */
    //@@@ QA 130402-000072 Task 50281 Test UI
        testUI: function() {
            this.initValues();
            for (var i = 1; i < this.iterations; ++i) {
                this.button.simulate('click');
                Y.assert(this.isVisible(), 'panel is hidden when it shouldn\'t be');
                Y.one('#ygtvlabelel3').simulate('click');
                if(this.widgetData.attrs.show_confirm_button_in_dialog) {
                    //Ensure the panel is still visible until we click OK
                    Y.assert(this.isVisible(), 'panel is hidden when it shouldn\'t be');
                    Y.Assert.areSame(2, parseInt(Y.one('.yui3-panel').getStyle('zIndex'), 10));
                    this.confirmButton.simulate('click');
                }
            }
        },

    //@@@ QA 130402-000072 Task 50281 Test Cancel Button
        testCancelButton: function() {
            this.initValues();
            if(this.widgetData.attrs.show_confirm_button_in_dialog) {
                //Open and close clicking cancel button
                this.button.simulate('click'); // To halt/cancel the event propagation of the previous this.button.simulate('click') event.
                this.button.simulate('click');
                Y.assert(this.isVisible(), 'panel is hidden when it shouldn\'t be');
                Y.one('#ygtvlabelel3').simulate('click');
                this.cancelButton.simulate('click');
                Y.assert(this.isHidden(), 'panel is visible when it shouldn\'t be');
            }
        }
    }));

    productCatalogInputTests.add(new Y.Test.Case(
    {
        name: "Accessibility Dialog Tests",

        setUp: function () {
            this.mockIDs = [222005,222010,13];

            // Make the widget think it already has the flat tree data
            // so it doesn't make an AJAX request to retrieve it.
            widget._flatTreeViewData = [{
                '0': 'Laptops',
                '1': 222005,
                '2': [222005],
                '3': true,
                'level': 0,
                'hier_list': '222005',
            }, {
                '0': 'Dell',
                '1': 222010,
                '2': [222005, 222010],
                '3': true,
                'level': 1,
                'hier_list': '222005,222010'
            }, {
                '0': 'Dell Latitude E6430',
                '1': 13,
                '2': [222005, 222010, 13],
                '3': false,
                'level': 2,
                'hier_list': '222005,222010,13'
            }

            ];
        },

        tearDown: function () {
            widget._flatTreeViewData = null;
        },

        "Button is focused when dialog is closed": function () {
            Y.assert(!Y.one('.rn_Dialog'));
            Y.one(baseSelector + '_LinksTrigger').simulate('click');
            Y.assert(Y.one('.rn_Dialog'));
            Y.one('.rn_Dialog button').simulate('click');
            Y.assert(Y.one('.rn_Dialog').ancestor('.yui3-panel-hidden'));
            Y.Assert.areSame(Y.one(document.activeElement), Y.one(baseSelector + '_' + 'ProductCatalog_Button'));
        },

        "Multi-level items can be selected": function () {
            RightNow.Event.on("evt_productSelectedFromCatalog", function (evt, args) {
                Y.Assert.areSame(this.mockIDs[2], Y.clone(args[0]).data['productID']);
                this.selectedItem = Y.clone(args[0]);

            }, this);

            var eo = Y.clone(widget._eo);
            eo.data.accessibleLinks = widget._flatTreeViewData;
            widget._getAccessibleTreeViewResponse({}, [ eo ]);
            Y.one('.rn_AccessibleHierLink[data-hierlist="' + this.mockIDs.join(',') + '"]').simulate('click');

            var buttonText = Y.one(baseSelector + '_' + 'ProductCatalog_Button').get('text');
            Y.Assert.areSame(-1, buttonText.indexOf(widget.data.attrs.label_all_values));
        }
    }));
    return productCatalogInputTests;
});
UnitTest.run();
