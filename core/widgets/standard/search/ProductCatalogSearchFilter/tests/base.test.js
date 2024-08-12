UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'ProductCatalogSearchFilter_0'
}, function(Y, widget, baseSelector){
    var tests = new Y.Test.Suite({
        name: "standard/search/ProductCatalogSearchFilter",
            
        setUp: function(){
            var testExtender = {                
                initValues : function() {
                    this.instanceID = 'ProductCatalogSearchFilter_0';
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.widgetData = this.instance.data;
                    this.button = Y.one("#rn_" + this.instanceID + "_ProductCatalog_Button");
                    this.ajaxCalled = false;
                    this.treeContainer = Y.one("#rn_" + this.instanceID + "_TreeContainer");
                    this.tree = Y.one("#rn_" + this.instanceID + "_Tree");
                    this.buttonLabel = Y.one("#rn_" + this.instanceID + "_Button_Visible_Text");
                    if(this.widgetData.attrs.show_confirm_button_in_dialog)
                    {
                        this.confirmButton = Y.one('#rn_' + this.instanceID + '_ProductCatalog_ConfirmButton');
                        this.cancelButton = Y.one('#rn_' + this.instanceID + '_ProductCatalog_CancelButton');
                    }
                },
                isHidden: function() {
                    return this.treeContainer.get('parentNode').hasClass('yui3-panel-hidden');
                },
                isVisible: function() {
                    return !this.isHidden();
                }
            };
            
            for(var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }            
        }
    });

    tests.add(new Y.Test.Case({
        name: "Event Handling and Operation",
        
        'Verify all reset event goes back to prior search or initial value' : function() {
            var callbackAComplete = false;

            this.initValues();
            RightNow.Event.subscribe('evt_productCatalogFilterSelected', this.ensureNoAjax, this);
        
            //When we reset check what data is coming through
            this.instance.searchSource().once('reset', function(evtName, eo) {
                callbackAComplete = true;
                Y.Assert.areSame("reset", evtName);
                eo = eo[0];
                Y.Assert.isObject(eo.filters);
                Y.Assert.areSame(this.instanceID, eo.w_id);
                Y.Assert.areSame(this.lastValue, this.buttonLabel.get('innerHTML'));
            }, this);
            
            this.lastValue = this.buttonLabel.get('innerHTML');
            this.button.simulate('click');
            Y.assert(this.isVisible(), 'panel is hidden when it shouldn\'t be');
            Y.one('#ygtvlabelel9').simulate('click'); //GS2010 product, no children - Should not trigger an AJAX request.
            if(this.widgetData.attrs.show_confirm_button_in_dialog) {
                this.confirmButton.simulate('click');
            }
            Y.assert(this.isHidden(), 'panel is visible when it shouldn\'t be');
            Y.Assert.areSame('GS2010', this.buttonLabel.get('innerHTML'));
            
            //Fire out a reset to the filter causing it to revert to the prior search
            this.instance.searchSource().fire('reset', new RightNow.Event.EventObject(this, {data: {name: 'all'}}));
            Y.Assert.isTrue(callbackAComplete, "Reset handler wasn't called");
            RightNow.Event.unsubscribe('evt_productCatalogFilterSelected', this.ensureNoAjax, this);
        },

        'Verify empty reset event goes back to initial state' : function() {
            this.initValues();

            RightNow.Event.subscribe('evt_productCatalogFilterSelected', this.ensureNoAjax, this);

            //Make sure we start out with the initial value
            this.instance.searchSource().once('send', function(evtName, eo) {
                eo = eo[0].allFilters[this.widgetData.js.searchName];
                Y.Assert.areSame(this.widgetData.js.oper_id, eo.filters.oper_id);
                Y.Assert.areSame(this.widgetData.js.fltr_id, eo.filters.fltr_id);
                Y.Assert.areSame(this.widgetData.attrs.report_id, eo.filters.report_id);
                Y.Assert.areSame(this.widgetData.js.searchName, eo.filters.searchName);
                Y.Assert.areSame('menufilter', eo.filters.rnSearchType);
                for(var i = 0; i < this.widgetData.js.initial.length; i++) {
                    Y.Assert.areSame(this.widgetData.js.initial[i], eo.filters.data[0][i]);
                }
                if(!this.widgetData.js.initial.length) 
                    Y.Assert.isUndefined(eo.filters.data[0][0]);
    
                return false;
            }, this);
            this.instance.searchSource().fire('search', new RightNow.Event.EventObject(this, { filters: {data: 'test'} }));

            Y.assert(this.isHidden(), 'panel is visible when it shouldn\'t be');
            this.button.simulate('click'); // To halt/cancel the event propagation of the previous this.button.simulate('click') event.
            this.button.simulate('click');
            Y.assert(this.isVisible(), 'panel is hidden when it shouldn\'t be');
            Y.one('#ygtvlabelel9').simulate('click'); 
            if(this.widgetData.attrs.show_confirm_button_in_dialog)
                this.confirmButton.simulate('click');
            Y.assert(this.isHidden(), 'panel is visible when it shouldn\'t be');

            //GS2010
            this.instance.searchSource().once('send', function(evtName, eo) {
                eo = eo[0].allFilters[this.widgetData.js.searchName];
                Y.Assert.areSame(this.widgetData.js.oper_id, eo.filters.oper_id);
                Y.Assert.areSame(this.widgetData.js.fltr_id, eo.filters.fltr_id);
                Y.Assert.areSame(this.widgetData.attrs.report_id, eo.filters.report_id);
                Y.Assert.areSame(this.widgetData.js.searchName, eo.filters.searchName);
                Y.Assert.areSame('menufilter', eo.filters.rnSearchType);
                Y.Assert.areSame(5, eo.filters.data[0][0]);
                return false;
            }, this);
            this.instance.searchSource().fire('search', new RightNow.Event.EventObject(this, { filters: {data: 'test'} }));

            //Reset it back to the default
            this.instance.searchSource().fire('reset');

            //Make sure that it's been reset to what we expect
            this.instance.searchSource().once('send', function(evtName, eo) {
                eo = eo[0].allFilters[this.widgetData.js.searchName];
                Y.Assert.areSame(this.widgetData.js.oper_id, eo.filters.oper_id);
                Y.Assert.areSame(this.widgetData.js.fltr_id, eo.filters.fltr_id);
                Y.Assert.areSame(this.widgetData.attrs.report_id, eo.filters.report_id);
                Y.Assert.areSame(this.widgetData.js.searchName, eo.filters.searchName);
                Y.Assert.areSame('menufilter', eo.filters.rnSearchType);
                for(var i = 0; i < this.widgetData.js.initial.length; i++) {
                    Y.Assert.areSame(this.widgetData.js.initial[i], eo.filters.data[0][i]);
                }
                if(!this.widgetData.js.initial.length) 
                    Y.Assert.isUndefined(eo.filters.data[0][0]);
                return false;
            }, this); 
            this.instance.searchSource().fire('search', new RightNow.Event.EventObject(this, { filters: {data: 'test'} }));

            RightNow.Event.unsubscribe('evt_productCatalogFilterSelected', this.ensureNoAjax, this);
        },

        'Verify when clicked that the request is made and children added' : function() {
            this.initValues();
            var callbackComplete = false;
            RightNow.Event.subscribe('evt_productCatalogFilterSelected', this.ensureNoAjax, this);
            RightNow.Event.subscribe('evt_menuFilterProductCatalogGetResponse', function() {
                if(callbackComplete) return;
                callbackComplete = true; //Do our best to kill this event handler for future tests.
                this.resume(function() {
                    if(this.widgetData.attrs.show_confirm_button_in_dialog) {
                        //This test is failing due to some strange Selenium issue. Comment it out until the server is upgraded and try again.
                        // Y.assert(this.isVisible(), 'TreeView should be visible before clicking the confirm button');
                        this.confirmButton.simulate('click');
                    }

                    //Make sure our children have arrived
                    Y.Assert.isNotNull(Y.one('#ygtvlabelel8')); 
                    Y.Assert.isNotNull(Y.one('#ygtvlabelel9')); 
                    Y.Assert.isNotNull(Y.one('#ygtvlabelel10'));
                    Y.Assert.isNotNull(Y.one('#ygtvlabelel11'));

                    RightNow.Event.unsubscribe('evt_productCatalogFilterSelected', this.ensureNoAjax, this);
                });
            }, this);

            Y.assert(this.isHidden(), 'TreeView should be hidden at the start of the test');
            this.button.simulate('click'); // To halt/cancel the event propagation of the previous this.button.simulate('click') event.
            this.button.simulate('click');
            Y.assert(this.isVisible(), 'TreeView should be visible after clicking the button');
            Y.one('#ygtvlabelel2').simulate('click'); //Printers, has children - Makes an AJAX request

            this.wait();
        },

        'Verify functionality when a search is made' : function() {
            var widgetResponse = null;
            this.initValues();
            RightNow.Event.subscribe('evt_productCatalogFilterSelected', this.ensureNoAjax, this);

            this.button.simulate('click'); // To halt/cancel the event propagation of the previous this.button.simulate('click') event.
            this.button.simulate('click');
            Y.assert(this.isVisible(), 'TreeView should be visible after clicking the button');
            Y.one('#ygtvlabelel9').simulate('click'); //Printers, has children - Makes an AJAX request (already expanded due to the above test)

            if(this.widgetData.attrs.show_confirm_button_in_dialog) {
                //This test is failing due to some strange Selenium issue. Comment it out until the server is upgraded and try again.
                this.confirmButton.simulate('click');
            }
            Y.assert(this.isHidden(), 'TreeView should be hidden at the end of the test');

            this.instance.searchSource().on('send', function(name, eo) { 
                widgetResponse = eo[0].allFilters[this.widgetData.js.searchName];
                return false;
            }, this).fire('search', new RightNow.Event.EventObject(this, { filters: {data: 'test'} }));

            Y.Assert.areSame(this.widgetData.js.oper_id, widgetResponse.filters.oper_id);
            Y.Assert.areSame(this.widgetData.js.fltr_id, widgetResponse.filters.fltr_id);
            Y.Assert.areSame(this.widgetData.attrs.report_id, widgetResponse.filters.report_id);
            Y.Assert.areSame(this.widgetData.js.searchName, widgetResponse.filters.searchName);
            Y.Assert.areSame('menufilter', widgetResponse.filters.rnSearchType);
            Y.Assert.areSame(5, widgetResponse.filters.data[0][0]);
            Y.Assert.areSame(0, Y.one(this.instance.baseSelector + '_Button_Visible_Text').get('innerHTML').indexOf(widgetResponse.filters.data.reconstructData[0].label));
        
            RightNow.Event.unsubscribe('evt_productCatalogFilterSelected', this.ensureNoAjax, this);
        },

        'Verify that an updated response from the server updates the widget': function() {
            this.initValues();
            RightNow.Event.subscribe('evt_productCatalogFilterSelected', this.ensureNoAjax, this);

            var that = this,
                eo = new RightNow.Event.EventObject(this, {filters: {allFilters: {}}});

            //Choose GS2010
            eo.filters.allFilters[this.widgetData.js.searchName] = {filters: { data: [['5']]}};

            //Subscribe to the response event to make sure the widget has been updated correctly. 
            this.instance.searchSource().once('response', function() {
                Y.Assert.isFalse(this.ajaxCalled);
                RightNow.Event.unsubscribe('evt_productCatalogFilterSelected', this.ensureNoAjax, this);
                Y.Assert.areSame('GS2010', Y.one(this.instance.baseSelector + '_Button_Visible_Text').get('innerHTML'));
            }, this);
            this.instance.searchSource().fire('response', eo);
        },

        ensureNoAjax: function() {
            this.ajaxCalled = true;
        }
    })); 
    
    tests.add(new Y.Test.Case({
        name: "UI Functional Tests",

        getGreenServer: function() {
            var found = null;
            this.tree.all('a.ygtvlabel').some(function(node) {
                if (node.getHTML() === 'GS2010') {
                    found = node;
                    return true;
                }
            });
            Y.Assert.isNotNull(found, "GS2010 wasn't found");
            return found;
        },
        
        /**
         * Test of the ProductCatalogSearchFilter's basic UI functionality to ensure
         * that it is working properly. Test the opening and closing of the menu and
         * whether the selection of a product/category will correctly show up as the 
         * button's text.
         */
        testUI: function() {
            this.initValues();

            Y.assert(this.isHidden(), 'TreeView should be hidden at the start of the test');
            this.button.simulate('click'); // To halt/cancel the event propagation of the previous this.button.simulate('click') event.
            this.button.simulate('click');
            Y.assert(this.isVisible(), 'TreeView should be visible after clicking the button');
            this.getGreenServer().simulate('click');
            if(this.widgetData.attrs.show_confirm_button_in_dialog) {
                //This test is failing due to some strange Selenium issue. Comment it out until the server is upgraded and try again.
                this.confirmButton.simulate('click');
            }
            Y.assert(this.isHidden(), 'TreeView should be hidden at the end of the test');
        },
        
        testCancelButton: function() {
            if(this.widgetData.attrs.show_confirm_button_in_dialog) {
                //Open and close clicking cancel button
                Y.assert(this.isHidden(), 'TreeView should be hidden at the start of the test');
                this.button.simulate('click'); // To halt/cancel the event propagation of the previous this.button.simulate('click') event.
                this.button.simulate('click');
                Y.assert(this.isVisible(), 'TreeView should be visible after clicking the button');
                var greenServer = this.getGreenServer();
                greenServer.simulate('click');
                this.cancelButton.simulate('click');
                Y.assert(this.isHidden(), 'TreeView should be hidden after clicking cancel');
                
                //Open and close clicking opener button
                Y.assert(this.isHidden(), 'TreeView should be hidden after clicking the button');
                this.button.simulate('click');
                Y.assert(this.isVisible(), 'TreeView should be visible after clicking the button');
                greenServer.simulate('click');
                
                //This test is failing due to some strange Selenium issue. Comment it out until the server is upgraded and try again.
                this.confirmButton.simulate('click');
                Y.assert(this.isHidden(), 'TreeView should be hidden at the end of the test');
            }
        },
        
        requestSearchEventHandler: function(type, args) {
            Y.Assert.areSame("search", type);
            args = args[0];
            Y.Assert.isObject(args.filters);
            Y.Assert.areSame(this.instanceID, args.w_id);
            
            this.requestSearchEventHandler.called = true;
        }
    }));

    tests.add(new Y.Test.Case({
        // NOTE: this case creates a new widget instance without giving it its own DOM element, which
        // causes all sorts of weirdnesses when testing UI interaction. As such it should appear as the last test case.
        name: 'Tests via extension',
        'Should not respond to non-report search source events': function() {
            this.initValues();

            var widget = RightNow.Widgets.ProductCatalogSearchFilter.extend({
                overrides: {
                    constructor: function() {
                        this.calledSearch = false;
                        this.calledResponse = false;
                        this.parent();
                    },
                    _getFiltersRequest: function() {
                        this.calledSearch = true;
                        this.parent();
                    },
                    _onReportResponse: function() {
                        this.calledResponse = true;
                        this.parent();
                    }
                }
            });
            this.instance.data.attrs.source_id = 'banana';
            var instance = new widget(this.instance.data, this.instance.instanceID, this.instance.Y);
            instance.searchSource('banana').on('send', function () { return false; })
                .fire('search', new RightNow.Event.EventObject()).fire('response', new RightNow.Event.EventObject());
            Y.Assert.isFalse(instance.calledSearch);
            Y.Assert.isFalse(instance.calledResponse);
            Y.all('#' + this.treeContainer).slice(-1).remove();
        }
    }));
    return tests;
});
UnitTest.run();
