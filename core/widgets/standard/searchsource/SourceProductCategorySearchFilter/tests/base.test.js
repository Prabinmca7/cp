UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'SourceProductCategorySearchFilter_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/searchsource/SourceProductCategorySearchFilter",

        setUp: function(){
            var testExtender = {
                initValues : function() {

                }
            };

            for (var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    suite.add(new Y.Test.Case({
        name: "Behavior",

        setUp: function () {
            // Don't navigate away from this page.
            widget.data.attrs.search_on_select = false;
            widget.data.attrs.label_nothing_selected = RightNow.Interface.getMessage("SELECT_A_PRODUCT_LBL");

            this.makeRequest = RightNow.Ajax.makeRequest;

            RightNow.Ajax.makeRequest = Y.bind(function () {
                this.makeRequestCalledWith = [].slice.call(arguments);
            }, this);
        },

        tearDown: function () {
            RightNow.Ajax.makeRequest = this.makeRequest;
            this.makeRequestCalledWith = null;
        },

        selectVoicePlans: function () {
            // Click on 'Voice Plans'.
            var topLevelItems = Y.one(baseSelector + '_Tree .ygtvchildren').all('> .ygtvitem');
            topLevelItems.item(2).one('a').simulate('click');
        },

        selectNoValue: function () {
            // Click on 'All'
            Y.one(baseSelector + '_Tree #ygtvlabelel1').simulate('click');
        },

        "Fires the evt_productCategoryFilterSelected event when an item is chosen": function () {
            var expected = {
                value: 6,
                label: 'Voice Plans',
                hierChain: [6],
                level: 1
            };
            var selected = null;
            RightNow.Event.on('evt_productCategoryFilterSelected', function (evt, args) {
                selected = Y.clone(args[0].data);
            });

            this.selectVoicePlans();

            Y.Assert.areSame(expected.value, selected.value);
            Y.Assert.areSame(expected.label, selected.label);
            Y.Assert.areSame(expected.level, selected.level);
            Y.Assert.areSame(expected.hierChain.length, selected.hierChain.length);
            Y.Assert.areSame(expected.hierChain[0], selected.hierChain[0]);
        },

        "Returns the selected value in the collect event": function () {
            var expected = {
                key: widget.data.js.filter.key,
                type: widget.data.js.filter.type,
                value: 6
            };
            var searchFilter;
            widget.searchSource().on('search', function () { return false; })
                .once('searchCancelled', function (evt, args) {
                    searchFilter = Y.clone(args[0].product);
                });

            this.selectVoicePlans();

            // Search.
            widget.searchSource().fire('collect').fire('search');

            Y.Assert.areSame(expected.value, searchFilter.value);
            Y.Assert.areSame(expected.key, searchFilter.key);
            Y.Assert.areSame(expected.type, searchFilter.type);
        },

        "Resets to the intial value during the 'reset' event": function () {
            this.selectVoicePlans();

            widget.searchSource().fire('reset', new RightNow.Event.EventObject(this, {
                data: {
                    filters: {
                        'product': { key: 'p', value: '7' },
                        'category': { key: 'c', value: '777' }
                    }
                }
            }));

            Y.Assert.isTrue(Y.one('.rn_DisplayButton').get('text').indexOf(widget.data.attrs.label_nothing_selected) > -1);
        },

        "Updates the tree in response to the 'response' event": function () {
            this.selectVoicePlans();

            widget.searchSource().fire('response', new RightNow.Event.EventObject(this, {
                data: {
                    filters: {
                        'product': { key: 'p', value: '7' },
                        'category': { key: 'c', value: '777' },
                    }
                }
            }));

            Y.Assert.isTrue(Y.one('.rn_DisplayButton').get('text').indexOf('Replacement/Repair Coverage') > -1);
        },

        "Responds to the `collect` event with the 'no value' value when 'no value' is selected": function () {
            this.selectNoValue();

            var searchFilter = 'replaced';
            widget.searchSource().once('searchCancelled', function (evt, args) {
                searchFilter = Y.clone(args[0].product);
            });

            // Search.
            widget.searchSource().fire('collect').fire('search');

            // Filter wasn't collected because it has a falsey value.
            Y.Assert.isUndefined(searchFilter);
        },

        "Sends the search_results_url attribute values as a search option": function () {
            var expected = widget.data.attrs.search_results_url || '';
            widget.data.attrs.search_on_select = true;

            var searchPage = '';
            widget.searchSource().once('searchCancelled', function (evt, args) {
                searchPage = args[1].new_page;
            });

            this.selectVoicePlans();

            Y.Assert.areSame(expected, searchPage);
        },

        "Accessible request is made with the request `hm_type` value": function () {
            var expected = widget.data.js.hm_type;

            Y.one(baseSelector + '_LinksTrigger').simulate('click');

            Y.Assert.areSame('number', typeof expected);
            Y.Assert.areSame(expected, this.makeRequestCalledWith[1].hm_type);
        },

        "Widget's getSubLevelResponse method fires prototype's method when expected": function () {
            var getSubLevelResponseCalled = false;
            RightNow.ProductCategory.prototype.getSubLevelResponse = function() {
                getSubLevelResponseCalled = true;
            }

            widget.dataType = 'Cucumber';

            var args = [{
                data: {
                    linking_on: true,
                    value: 42,
                    via_hier_request: true,
                    via_filter_request: false,
                    data_type: 'Cucumber'
                }
            }];
            widget.getSubLevelResponse('veggies', args);
            Y.Assert.isTrue(getSubLevelResponseCalled);
            getSubLevelResponseCalled = false;

            args = [{
                data: {
                    linking_on: true,
                    value: null,
                    via_hier_request: true,
                    via_filter_request: false,
                    data_type: 'Cucumber'
                }
            }];
            widget.getSubLevelResponse('veggies', args);
            Y.Assert.isFalse(getSubLevelResponseCalled);
            getSubLevelResponseCalled = false;

            args = [{
                data: {
                    linking_on: true,
                    value: 42,
                    via_hier_request: false,
                    via_filter_request: false,
                    data_type: 'Cucumber'
                }
            }];
            widget.getSubLevelResponse('veggies', args);
            Y.Assert.isTrue(getSubLevelResponseCalled);
            getSubLevelResponseCalled = false;

            args = [{
                data: {
                    linking_on: true,
                    value: 42,
                    via_hier_request: false,
                    via_filter_request: true,
                    data_type: 'Cucumber'
                }
            }];
            widget.getSubLevelResponse('veggies', args);
            Y.Assert.isTrue(getSubLevelResponseCalled);
            getSubLevelResponseCalled = false;

            args = [{
                data: {
                    linking_on: false,
                    value: 42,
                    via_hier_request: false,
                    via_filter_request: true,
                    data_type: 'Cucumber'
                }
            }];
            widget.getSubLevelResponse('veggies', args);
            Y.Assert.isTrue(getSubLevelResponseCalled);
            getSubLevelResponseCalled = false;

            args = [{
                data: {
                    linking_on: false,
                    value: 42,
                    via_hier_request: false,
                    via_filter_request: true,
                    data_type: 'Asparagus'
                }
            }];
            widget.getSubLevelResponse('veggies', args);
            Y.Assert.isFalse(getSubLevelResponseCalled);
        }
    }));

    return suite;
}).run();
