UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'ProductCategorySearchFilter_0'
}, function(Y, widget, baseSelector){
    var tests = new Y.Test.Suite({
        name: "standard/search/ProductCategorySearchFilter",

        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.instanceID = 'ProductCategorySearchFilter_0';
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.widgetData = this.instance.data;
                    this.searchOnSelect = this.widgetData.attrs.search_on_select;
                    this.button = Y.one("#rn_" + this.instanceID + "_Product_Button");
                    this.ajaxCalled = false;
                    this.treeContainer = Y.one("#rn_" + this.instanceID + "_TreeContainer");
                    this.tree = Y.one("#rn_" + this.instanceID + "_Tree");
                    this.buttonLabel = Y.one("#rn_" + this.instanceID + "_ButtonVisibleText");
                    if(this.widgetData.attrs.show_confirm_button_in_dialog)
                    {
                        this.confirmButton = Y.one('#rn_' + this.instanceID + '_' + this.widgetData.attrs.filter_type +  '_ConfirmButton');
                        this.cancelButton = Y.one('#rn_' + this.instanceID + '_' + this.widgetData.attrs.filter_type + '_CancelButton');
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

        setUp: function () {
            this.makeRequest = RightNow.Ajax.makeRequest;
            RightNow.Ajax.makeRequest = function () {
                RightNow.Ajax.makeRequest.calledWith = Array.prototype.slice.call(arguments);
            };
        },

        tearDown: function () {
            RightNow.Ajax.makeRequest = this.makeRequest;
        },

        'Verify all reset event goes back to prior search or initial value' : function() {
            var callbackAComplete = false;

            this.initValues();
            RightNow.Event.subscribe('evt_menuFilterRequest', this.ensureNoAjax, this);

            //When we reset check what data is coming through
            this.instance.searchSource().once('reset', function(evtName, eo) {
                callbackAComplete = true;
                Y.Assert.areSame("reset", evtName);
                eo = eo[0];
                Y.Assert.isObject(eo.filters);
                Y.Assert.areSame(this.instanceID, eo.w_id);
            }, this);

            //If search on select is on, the click will cause this search to fire
            if(this.widgetData.attrs.search_on_select) {
                this.instance.searchSource().on('send', function(evtName, eo) {
                    this.lastValue = this.buttonLabel.get('innerHTML');
                    return false;
                }, this);
            }

            this.lastValue = this.buttonLabel.get('innerHTML');
            this.button.simulate('click');
            Y.assert(this.isVisible(), 'panel is hidden when it shouldn\'t be');
            Y.one('#ygtvlabelel5').simulate('click'); //Mobile Broadband product, no children - Should not trigger an AJAX request.
            if(this.widgetData.attrs.show_confirm_button_in_dialog) {
                this.confirmButton.simulate('click');
            }
            Y.assert(this.isHidden(), 'panel is visible when it shouldn\'t be');
            Y.Assert.areSame('Mobile Broadband', this.buttonLabel.get('innerHTML'));

            //Fire out a reset to the filter causing it to revert to the prior search
            this.instance.searchSource().fire('reset', new RightNow.Event.EventObject(this, {data: {name: 'all'}}));
            Y.Assert.isTrue(callbackAComplete, "Reset handler wasn't called");
            RightNow.Event.unsubscribe('evt_menuFilterRequest', this.ensureNoAjax, this);
            Y.Assert.isFalse(this.ajaxCalled, "An ajax request shouldn't have been made");
        },

        'Verify empty reset event goes back to initial state' : function() {
            this.initValues();

            RightNow.Event.subscribe('evt_menuFilterRequest', this.ensureNoAjax, this);

            //Make sure we start out with the initial value
            this.instance.searchSource().once('send', function(evtName, eo) {
                eo = eo[0].allFilters[this.widgetData.js.searchName];
                Y.Assert.areSame(this.widgetData.js.oper_id, eo.filters.oper_id);
                Y.Assert.areSame(this.widgetData.js.fltr_id, eo.filters.fltr_id);
                Y.Assert.areSame(this.widgetData.attrs.report_id, eo.filters.report_id);
                Y.Assert.areSame(this.widgetData.js.searchName, eo.filters.searchName);
                Y.Assert.areSame('menufilter', eo.filters.rnSearchType);
                if(!this.widgetData.attrs.search_on_select) {
                    for(var i = 0; i < this.widgetData.js.initial.length; i++) {
                        Y.Assert.areSame(this.widgetData.js.initial[i], eo.filters.data[0][i]);
                    }
                    if(!this.widgetData.js.initial.length)
                        Y.Assert.isUndefined(eo.filters.data[0][0]);
                }
                else {
                    //If search on select is on, the search above already fired.
                    Y.Assert.areSame(163, eo.filters.data[0][0]);
                }
                return false;
            }, this);
            this.instance.searchSource().fire('search', new RightNow.Event.EventObject(this, { filters: {data: 'test'} }));

            //Select Mobile Broadband
            Y.assert(this.isHidden(), 'panel is visible when it shouldn\'t be');
            this.button.simulate('click'); // To halt/cancel the event propagation of the previous this.button.simulate('click') event.
            this.button.simulate('click');
            Y.assert(this.isVisible(), 'panel is hidden when it shouldn\'t be');
            Y.one('#ygtvlabelel5').simulate('click'); //Mobile Broadband product, no children - Should not trigger an AJAX request.
            if(this.widgetData.attrs.show_confirm_button_in_dialog)
                this.confirmButton.simulate('click');
            Y.assert(this.isHidden(), 'panel is visible when it shouldn\'t be');

            //Check that the value is now set to Mobile Broadband
            this.instance.searchSource().once('send', function(evtName, eo) {
                eo = eo[0].allFilters[this.widgetData.js.searchName];
                Y.Assert.areSame(this.widgetData.js.oper_id, eo.filters.oper_id);
                Y.Assert.areSame(this.widgetData.js.fltr_id, eo.filters.fltr_id);
                Y.Assert.areSame(this.widgetData.attrs.report_id, eo.filters.report_id);
                Y.Assert.areSame(this.widgetData.js.searchName, eo.filters.searchName);
                Y.Assert.areSame('menufilter', eo.filters.rnSearchType);
                Y.Assert.areSame(163, eo.filters.data[0][0]);
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

            RightNow.Event.unsubscribe('evt_menuFilterRequest', this.ensureNoAjax, this);
            Y.Assert.isFalse(this.ajaxCalled, "An ajax request shouldn't have been made");
        },

        'Verify when clicked that the request is made and children added' : function() {
            this.initValues();

            Y.assert(this.isHidden(), 'TreeView should be hidden at the start of the test');
            this.button.simulate('click'); // To halt/cancel the event propagation of the previous this.button.simulate('click') event.
            this.button.simulate('click');
            Y.assert(this.isVisible(), 'TreeView should be visible after clicking the button');

            Y.one('#ygtvlabelel2').simulate('click'); //Mobile Phones, has children - Makes a fake ajax request

            if (this.widgetData.attrs.show_confirm_button_in_dialog) {
                Y.assert(this.isVisible(), 'TreeView should be visible before clicking the confirm button');
                this.confirmButton.simulate('click');
            }

            RightNow.Event.fire("evt_menuFilterGetResponse", new RightNow.Event.EventObject(widget, {
                data: {
                    data_type: "Product",
                    level: 2,
                    hier_data: [{
                        label: 'has children129',
                        id: 129,
                        hasChildren: true
                    }]
                },
                filters: {
                    report_id: 176
                }
            }));

            Y.Assert.isNotNull(Y.one(".ygtvitem .ygtvchildren"));
            Y.assert(this.isHidden(), 'TreeView should be hidden at the end of the test');
        },

        'Verify functionality when a search is made' : function() {
            var widgetResponse = null;
            this.initValues();
            RightNow.Event.subscribe('evt_menuFilterRequest', this.ensureNoAjax, this);

            Y.assert(this.isHidden(), 'TreeView should be hidden at the start of the test');
            this.button.simulate('click'); // To halt/cancel the event propagation of the previous this.button.simulate('click') event.
            this.button.simulate('click');
            Y.assert(this.isVisible(), 'TreeView should be visible after clicking the button');
            Y.one('#ygtvlabelel2').simulate('click');

            if(this.widgetData.attrs.show_confirm_button_in_dialog) {
                //This test is failing due to some strange Selenium issue. Comment it out until the server is upgraded and try again.
                //Y.Assert.areSame('visible', this.treeContainer.getComputedStyle('visibility'), 'TreeView should be visible before clicking the confirm button');
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
            Y.Assert.areSame(1, widgetResponse.filters.data[0][0]);
            Y.Assert.areSame(0, Y.one(this.instance.baseSelector + '_ButtonVisibleText').get('innerHTML').indexOf(widgetResponse.filters.data.reconstructData[0].label));

            RightNow.Event.unsubscribe('evt_menuFilterRequest', this.ensureNoAjax, this);
        },

        'Verify that an updated response from the server updates the widget': function() {
            this.initValues();
            RightNow.Event.subscribe('evt_menuFilterRequest', this.ensureNoAjax, this);

            var dataAttr = [[163]];
            dataAttr.reconstructData = {
                hierList: '163',
                label: 'Mobile Broadband',
                level: 1
            };

            var eo = new RightNow.Event.EventObject(this, {
                data : {},
                filters : {
                    allFilters: {
                        p: {
                            data: {
                                cache: [],
                                data_type: "Product",
                                hm_type: 14,
                                label: "Mobile Broadband",
                                level: 0,
                                linkingProduct: 0,
                                linking_on: 0,
                                reset: false,
                                value: 163,
                                w_id: 0
                            },
                            filters: {
                                data: dataAttr,
                                fltr_id: 2,
                                oper_id: 10,
                                report_id: 176,
                                rnSearchType: "menufilter",
                                searchName: this.widgetData.js.searchName,
                            }
                        }
                    }
                }
            });

            this.instance.searchSource().on('response', function(evtName, eo) {
                Y.Assert.isFalse(this.ajaxCalled);
                // 'data' arrays have slightly different prototypes; use to toString() for string representation for comparisions
                Y.Assert.areSame(this.instance._eo.filters.data[0].toString(), eo[0].filters.allFilters.p.filters.data[0].toString());
                Y.Assert.areSame(this.instance._lastSearchValue.toString(), eo[0].filters.allFilters.p.filters.data[0].toString());
            }, this).fire('response', eo);

            this.instance._eo.filters.data = eo.filters.allFilters.p.filters.data;
            this.instance.searchSource().on('response', function(evtName, eo) {
                Y.Assert.areSame(this.instance._eo.filters.data.level, 1);
                Y.Assert.areSame(this.instance._eo.filters.data.label, 'Mobile Broadband');
            }, this).fire('response', eo);

            RightNow.Event.unsubscribe('evt_menuFilterRequest', this.ensureNoAjax, this);
        },

        "Widget's getSubLevelResponse method fires prototype's method when expected": function () {
            var getSubLevelResponseCalled = false;
            RightNow.ProductCategory.prototype.getSubLevelResponse = function() {
                getSubLevelResponseCalled = true;
            }

            widget._dataType = 'Cucumber';
            widget.data.attrs.report_id = 43;

            var args = [{
                data: {
                    linking_on: true,
                    value: 42,
                    via_hier_request: true,
                    via_product_click: false,
                    data_type: 'Cucumber'
                },
                filters: {
                    report_id: 43
                }
            }];
            widget.getSubLevelResponse('veggies', args);
            Y.Assert.isTrue(getSubLevelResponseCalled);
            getSubLevelResponseCalled = false;

            args = [{
                data: {
                    linking_on: false,
                    value: 42,
                    via_hier_request: true,
                    via_product_click: false,
                    data_type: 'Tomato'
                },
                filters: {
                    report_id: 43
                }
            }];
            widget.getSubLevelResponse('veggies', args);
            Y.Assert.isFalse(getSubLevelResponseCalled);
            getSubLevelResponseCalled = false;

            args = [{
                data: {
                    linking_on: false,
                    value: 42,
                    via_hier_request: true,
                    via_product_click: false,
                    data_type: 'Cucumber'
                },
                filters: {
                    report_id: 41
                }
            }];
            widget.getSubLevelResponse('veggies', args);
            Y.Assert.isFalse(getSubLevelResponseCalled);
            getSubLevelResponseCalled = false;

            args = [{
                data: {
                    linking_on: false,
                    value: 85,
                    via_hier_request: true,
                    via_product_click: false,
                    data_type: 'Tomato'
                },
                filters: {
                    report_id: 42
                }
            }];
            widget.getSubLevelResponse('veggies', args);
            Y.Assert.isFalse(getSubLevelResponseCalled);
            getSubLevelResponseCalled = false;

            args = [{
                data: {
                    linking_on: true,
                    value: 33,
                    via_hier_request: false,
                    via_product_click: false,
                    data_type: 'Cucumber'
                },
                filters: {
                    report_id: 43
                }
            }];
            widget.getSubLevelResponse('veggies', args);
            Y.Assert.isTrue(getSubLevelResponseCalled);
            getSubLevelResponseCalled = false;

            args = [{
                data: {
                    linking_on: true,
                    value: 33,
                    via_hier_request: false,
                    via_product_click: true,
                    data_type: 'Cucumber'
                },
                filters: {
                    report_id: 43
                }
            }];
            widget.getSubLevelResponse('veggies', args);
            Y.Assert.isFalse(getSubLevelResponseCalled);
            getSubLevelResponseCalled = false;

            args = [{
                data: {
                    linking_on: true,
                    value: null,
                    via_hier_request: true,
                    via_product_click: false,
                    data_type: 'Cucumber'
                },
                filters: {
                    report_id: 43
                }
            }];
            widget.getSubLevelResponse('veggies', args);
            Y.Assert.isFalse(getSubLevelResponseCalled);
        },

        ensureNoAjax: function() {
            this.ajaxCalled = true;
        }
    }));

    tests.add(new Y.Test.Case({
        name: "UI Functional Tests",

        getVoicePlans: function() {
            var found = null;
            this.tree.all('a.ygtvlabel').some(function(node) {
                if (node.getHTML() === 'Voice Plans') {
                    found = node;
                    return true;
                }
            });
            Y.Assert.isNotNull(found, "Voice plans wasn't found");
            return found;
        },

        /**
         * Test of the ProductCategorySearchFilter's basic UI functionality to ensure
         * that it is working properly. Test the opening and closing of the menu and
         * whether the selection of a product/category will correctly show up as the
         * button's text.
         */
        testUI: function() {
            this.initValues();

            if (this.searchOnSelect) {
                this.instance.searchSource().on('search', this.requestSearchEventHandler, this)
                                            .on('send', function() { return false; }); // Prevent the search from actually running
            }

            Y.assert(this.isHidden(), 'TreeView should be hidden at the start of the test');
            this.button.simulate('click'); // To halt/cancel the event propagation of the previous this.button.simulate('click') event.
            this.button.simulate('click');
            Y.assert(this.isVisible(), 'TreeView should be visible after clicking the button');
            this.getVoicePlans().simulate('click');
            if(this.widgetData.attrs.show_confirm_button_in_dialog) {
                //This test is failing due to some strange Selenium issue. Comment it out until the server is upgraded and try again.
                //Y.Assert.areSame('visible', this.treeContainer.getComputedStyle('visibility'), 'TreeView should be visible before clicking the confirm button');
                this.confirmButton.simulate('click');
            }
            Y.assert(this.isHidden(), 'TreeView should be hidden at the end of the test');

            if (this.searchOnSelect) {
                Y.Assert.isTrue(this.requestSearchEventHandler.called, 'Request handler wasn\'t called');
            }
        },

        testCancelButton: function() {
            if(this.widgetData.attrs.show_confirm_button_in_dialog) {
                //Open and close clicking cancel button
                Y.assert(this.isHidden(), 'TreeView should be hidden at the start of the test');
                this.button.simulate('click'); // To halt/cancel the event propagation of the previous this.button.simulate('click') event.
                this.button.simulate('click');
                Y.assert(this.isVisible(), 'TreeView should be visible after clicking the button');
                var voicePlans = this.getVoicePlans();
                voicePlans.simulate('click');
                this.cancelButton.simulate('click');
                Y.assert(this.isHidden(), 'TreeView should be hidden after clicking cancel');

                //Open and close clicking opener button
                Y.assert(this.isHidden(), 'TreeView should be hidden after clicking the button');
                this.button.simulate('click');
                Y.assert(this.isVisible(), 'TreeView should be visible after clicking the button');
                voicePlans.simulate('click');

                //This test is failing due to some strange Selenium issue. Comment it out until the server is upgraded and try again.
                //Y.Assert.areSame('visible', this.treeContainer.getComputedStyle('visibility'), 'TreeView should be visible before clicking the confirm button');
                this.confirmButton.simulate('click');
                Y.assert(this.isHidden(), 'TreeView should be hidden at the end of the test');
            }
        },

        requestSearchEventHandler: function(type, args) {
            Y.Assert.areSame("search", type);
            args = args[0];
            Y.Assert.isObject(args.filters);
            Y.Assert.areSame(this.instanceID, args.w_id);

            Y.Assert.areSame(this.widgetData.attrs.report_page_url, args.filters.reportPage);
            this.requestSearchEventHandler.called = true;
        }
    }));

    tests.add(new Y.Test.Case({
        // NOTE: this case creates a new widget instance without giving it its own DOM element, which
        // causes all sorts of weirdnesses when testing UI interaction. As such it should appear as the last test case.
        name: 'Tests via extension',
        'Should not respond to non-report search source events': function() {
            this.initValues();

            var widget = RightNow.Widgets.ProductCategorySearchFilter.extend({
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
