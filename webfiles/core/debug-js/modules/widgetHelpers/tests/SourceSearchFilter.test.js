UnitTest.addSuite({
    type: UnitTest.Type.Framework,
    jsFiles: [
        '/euf/core/debug-js/RightNow.Text.js',
        '/euf/core/debug-js/RightNow.Url.js',
        '/euf/core/debug-js/RightNow.Ajax.js',
        '/euf/core/debug-js/RightNow.UI.AbuseDetection.js',
        '/euf/core/debug-js/RightNow.Event.js',
        '/euf/core/debug-js/modules/widgetHelpers/EventProvider.js',
        '/euf/core/debug-js/modules/widgetHelpers/SourceSearchFilter.js'
    ],
    yuiModules: ['history']
}, function(Y) {

    var suite = new Y.Test.Suite({ name: "Search Module" });

    var Helpers = (function() {
        var instanceID = 0,
            body = Y.one('body'),
            oldAjax,
            ajaxData;

        return {
            getFilterObject: function(key, value, type) {
                return new RightNow.Event.EventObject(null, {
                    data: {
                        key: key,
                        value: value,
                        type: type
                    }
                });
            },
            getNextInstanceID: function() {
                var widgetID = 'testWidgetID_' + instanceID++,
                    widgetNodeID = '#rn_' + widgetID,
                    widgetContentID = widgetNodeID + '_Content',
                    widgetNode = Y.one(widgetNodeID);

                if(!widgetNode) {
                    widgetNode = new Y.Node.create('<div id="' + widgetNodeID + '"><script type="text/json">{}</script></div>');
                    body.append(widgetNode);
                }
                if(!Y.one(widgetContentID)) {
                    widgetNode.append('<div id="' + widgetContentID + '">foo</div>');
                }
                return widgetID;
            },
            getNextSourceID: function() {
                return 'testSourceID_' + instanceID++;
            },
            getTestAttributes: function(object) {
                return {
                    attrs: Y.merge({source_id: Helpers.getNextSourceID()}, object || {})
                };
            },
            mockMakeRequest: function() {
                if(oldAjax) {
                    throw new Error("MakeRequest is already mocked");
                }

                oldAjax = RightNow.Ajax.makeRequest;
                RightNow.Ajax.makeRequest = function(url, postData, requestOptions) {
                    ajaxData = {url: url, postData: postData, requestOptions: requestOptions};
                };
            },
            getAjaxResults: function() {
                return ajaxData;
            },
            unmockMakeRequest: function() {
                if(!oldAjax) {
                    throw new Error("MakeRequest is not mocked");
                }

                RightNow.Ajax.makeRequest = oldAjax;
                oldAjax = null;
            }
        };
    })();

    suite.add(new Y.Test.Case({
        name: "Test Events",
        "The collect event should gather all filters on the page": function() {
            var queryFilter = Helpers.getFilterObject('kw', 'phone', 'query'),
                productFilter = Helpers.getFilterObject('p', 58, 'product');

            var Test = RightNow.SearchProducer.extend({
                overrides: {
                    constructor: function() {
                        this.parent();

                        this.searchSource()
                            .on('collect', function() {
                                return queryFilter;
                            })
                            .on('collect', function() {
                                return productFilter;
                            })
                            .fire('collect');
                    }
                }
            });

            var instance = new Test(Helpers.getTestAttributes(), Helpers.getNextInstanceID(), Y),
                collectedFilters = instance.searchSource().filters,
                initialFilters = instance.searchSource().initialFilters;

            Y.Assert.areSame(initialFilters, collectedFilters);
            Y.assert(collectedFilters.query);
            Y.assert(collectedFilters.product);
            Y.Assert.areSame(collectedFilters.query.key, queryFilter.data.key);
            Y.Assert.areSame(collectedFilters.query.value, queryFilter.data.value);
            Y.Assert.areSame(collectedFilters.query.type, queryFilter.data.type);

            Y.Assert.areSame(collectedFilters.product.key, productFilter.data.key);
            Y.Assert.areSame(collectedFilters.product.value, productFilter.data.value);
            Y.Assert.areSame(collectedFilters.product.type, productFilter.data.type);
        },

        "The search event should collect data and preserve data without an event object provided with the search event": function() {
            if (!('pushState' in window.history)) return;

            var queryFilter = Helpers.getFilterObject('kw', 'phone_test', 'query'),
                endpoint = '/ci/ajaxRequest/search';

            var Test = RightNow.SearchProducer.extend({
                overrides: {
                    constructor: function() {
                        this.parent();

                        this.searchSource()
                            .setOptions({
                                endpoint: endpoint,
                                new_page: false
                            })
                            .on('collect', function() {
                                return queryFilter;
                            })
                            .fire('initializeFilters', new RightNow.Event.EventObject(this))
                            .fire('collect')
                            .fire('search');
                    }
                }
            });

            Helpers.mockMakeRequest();
            var instance = new Test(Helpers.getTestAttributes({ endpoint: endpoint }), Helpers.getNextInstanceID(), Y);
            Helpers.unmockMakeRequest();

            var results = Helpers.getAjaxResults();
            Y.Assert.areSame(results.url, endpoint);
            Y.Assert.areSame(results.postData.sourceID, 'testSourceID_2');
            Y.Assert.areSame(results.postData.w_id, 'testWidgetID_3');
            Y.Assert.areSame(results.postData.filters, '{"query":{"value":"phone_test","key":"kw","type":"query"}}');
            Y.Assert.areSame(results.requestOptions.data.filters.query.value, 'phone_test');
            Y.Assert.areSame(results.requestOptions.data.filters.query.key, 'kw');
            Y.Assert.areSame(results.requestOptions.data.filters.query.type, 'query');
        },

        "The search event should kick off an AJAX request to the provided endpoint": function() {
            if (!('pushState' in window.history)) return;

            var queryFilter = Helpers.getFilterObject('kw', 'phone', 'query'),
                productFilter = Helpers.getFilterObject('p', 58, 'product'),
                endpoint = '/ci/ajaxRequest/search';

            var Test = RightNow.SearchProducer.extend({
                overrides: {
                    constructor: function() {
                        this.parent();

                        this.searchSource()
                            .on('collect', function() {
                                return queryFilter;
                            })
                            .on('collect', function() {
                                return productFilter;
                            })
                            .fire('collect')
                            .fire('search', new RightNow.Event.EventObject(this, {
                                data: {
                                    endpoint: this.data.attrs.endpoint,
                                    new_page: false
                                }
                            }));
                    }
                }
            });

            Helpers.mockMakeRequest();
            var instance = new Test(Helpers.getTestAttributes({ endpoint: endpoint }), Helpers.getNextInstanceID(), Y);
            Helpers.unmockMakeRequest();

            var results = Helpers.getAjaxResults();
            Y.Assert.areSame(results.url, endpoint);
            Y.Assert.areSame(results.postData.sourceID, 'testSourceID_4');
        },

        "The search event shouldn't override the limit when set in the widget": function() {
            if (!('pushState' in window.history)) return;

            var queryFilter = Helpers.getFilterObject('kw', 'walkie_talkie_test', 'query'),
                endpoint = '/ci/ajaxRequest/search';

            var Test = RightNow.SearchProducer.extend({
                overrides: {
                    constructor: function() {
                        this.parent();

                        this.searchSource()
                            .setOptions({
                                limit: 22,
                                endpoint: endpoint,
                                new_page: false
                            })
                            .on('collect', function() {
                                return queryFilter;
                            })
                            .fire('initializeFilters', new RightNow.Event.EventObject(this))
                            .fire('collect')
                            .fire('search');
                    }
                }
            });

            Helpers.mockMakeRequest();
            var instance = new Test(Helpers.getTestAttributes({ endpoint: endpoint }), Helpers.getNextInstanceID(), Y);
            Helpers.unmockMakeRequest();

            var results = Helpers.getAjaxResults();
            Y.Assert.areSame(results.postData.limit, 22);
        },

        "Filters in URL shouldn't be removed by multiple SearchProducers": function() {
            if (!('pushState' in window.history)) return;

            var queryFilter = Helpers.getFilterObject('kw', 'rotary_phone_test', 'query'),
                endpoint = '/ci/ajaxRequest/search';

            var SearchProducer1 = RightNow.SearchProducer.extend({
                overrides: {
                    constructor: function() {
                        this.parent();

                        this.searchSource()
                        .setOptions({
                            endpoint: endpoint,
                        })
                        .fire('initializeFilters', new RightNow.Event.EventObject(this, {
                            data: {
                                vegetable: {
                                    key: 'v',
                                    type: 'leafy',
                                    value: 'kale'
                                }
                            }
                        }))
                    }
                }
            });

            Helpers.mockMakeRequest();
            var instance = new SearchProducer1(Helpers.getTestAttributes({ endpoint: endpoint }), Helpers.getNextInstanceID(), Y);
            Helpers.unmockMakeRequest();

            var SearchProducer2 = RightNow.SearchProducer.extend({
                overrides: {
                    constructor: function() {
                        this.parent();

                        this.searchSource()
                        .setOptions({
                            endpoint: endpoint,
                        })
                        .fire('initializeFilters', new RightNow.Event.EventObject(this, {
                            data: {
                                fruit: {
                                    key: 'f',
                                    type: 'berry',
                                    value: 'raspberry'
                                }
                            }
                        }))
                        .fire('collect')
                        .fire('search');
                    }
                }
            });

            Helpers.mockMakeRequest();
            var instance = new SearchProducer2(Helpers.getTestAttributes({ endpoint: endpoint }), Helpers.getNextInstanceID(), Y);
            Helpers.unmockMakeRequest();

            Y.Assert.isTrue(document.documentURI.indexOf('/f/raspberry') > -1);
            Y.Assert.isTrue(document.documentURI.indexOf('/v/kale') > -1);
        },

        "Update history key on search with new filter values when updateHistoryEntry event is fired": function() {
            if (!('pushState' in window.history)) return;

            var kw = 'Hi `~!#<b>%^$@%*()_{}[  |\\-+"=&',
                queryFilter = Helpers.getFilterObject('kw', kw, 'query'),
                productFilter = Helpers.getFilterObject('p', 58, 'product'),
                endpoint = '/ci/ajaxRequest/search';

            var Test = RightNow.SearchProducer.extend({
                overrides: {
                    constructor: function() {
                        this.parent();

                        this.searchSource()
                            .on('collect', function() {
                                return queryFilter;
                            })
                            .on('collect', function() {
                                return productFilter;
                            })
                            .fire('collect')
                            .fire('updateHistoryEntry', new RightNow.Event.EventObject(this, {
                                data: {
                                    update: {
                                        direction: {value: 'current', key: 'dir', type: 'direction'},
                                        page: {value: 1, key: 'page', type: 'page'}
                                    }
                                }
                            }))
                            .fire('search', new RightNow.Event.EventObject(this, {
                                data: {
                                    endpoint: this.data.attrs.endpoint,
                                    new_page: false
                                }
                            }));
                    }
                }
            });

            Helpers.mockMakeRequest();
            var instance = new Test(Helpers.getTestAttributes({ endpoint: endpoint }), Helpers.getNextInstanceID(), Y);
            Helpers.unmockMakeRequest();
            var results = Helpers.getAjaxResults();
            Y.Assert.areSame(endpoint, results.url);
            Y.Assert.areSame(kw, results.requestOptions.data.filters.query.value);
            Y.Assert.areSame('current', results.requestOptions.data.filters.direction.value);
            Y.Assert.areSame(1, results.requestOptions.data.filters.page.value);
            Y.Assert.areSame('/dir/current/kw/' + encodeURIComponent(kw) + '/p/58/page/1', results.requestOptions.data.historyKey);
        }
    }));

    suite.add(new Y.Test.Case({
        name: "Test Helpers",

        "The URL created by the getFilterUrl helper should always be sorted": function() {
            if (!('pushState' in window.history)) return;

            var kw = 'phone',
                queryFilter = Helpers.getFilterObject('zebra', kw, 'query'),
                productFilter = Helpers.getFilterObject('apple', 58, 'product'),
                categoryFilter = Helpers.getFilterObject('monkey', 580, 'category'),
                endpoint = '/ci/ajaxRequest/search';

            var Test = RightNow.SearchProducer.extend({
                overrides: {
                    constructor: function() {
                        this.parent();

                        this.searchSource()
                            .on('collect', function() {
                                return queryFilter;
                            })
                            .on('collect', function() {
                                return productFilter;
                            })
                            .on('collect', function() {
                                return categoryFilter;
                            })
                            .fire('collect')
                            .fire('search', new RightNow.Event.EventObject(this, {
                                data: {
                                    endpoint: this.data.attrs.endpoint,
                                    new_page: false
                                }
                            }));
                    }
                }
            });

            Helpers.mockMakeRequest();
            var instance = new Test(Helpers.getTestAttributes({ endpoint: endpoint }), Helpers.getNextInstanceID(), Y);
            Helpers.unmockMakeRequest();
            var results = Helpers.getAjaxResults();
            Y.Assert.areSame('/apple/58/monkey/580/zebra/phone', results.requestOptions.data.historyKey);
        },

        "The URL created by the getFilterUrl helper should url encode values": function() {
            if (!('pushState' in window.history)) return;

            var kw = 'Hi `~!#<b>%^$@%*()_{}[  |\\-+"=&',
                queryFilter = Helpers.getFilterObject('kw', kw, 'query'),
                productFilter = Helpers.getFilterObject('p', 58, 'product'),
                endpoint = '/ci/ajaxRequest/search';

            var Test = RightNow.SearchProducer.extend({
                overrides: {
                    constructor: function() {
                        this.parent();

                        this.searchSource()
                            .on('collect', function() {
                                return queryFilter;
                            })
                            .on('collect', function() {
                                return productFilter;
                            })
                            .fire('collect')
                            .fire('search', new RightNow.Event.EventObject(this, {
                                data: {
                                    endpoint: this.data.attrs.endpoint,
                                    new_page: false
                                }
                            }));
                    }
                }
            });

            Helpers.mockMakeRequest();
            var instance = new Test(Helpers.getTestAttributes({ endpoint: endpoint }), Helpers.getNextInstanceID(), Y);
            Helpers.unmockMakeRequest();
            var results = Helpers.getAjaxResults();
            Y.Assert.areSame(endpoint, results.url);
            Y.Assert.areSame(kw, results.requestOptions.data.filters.query.value);
            Y.Assert.areSame('/kw/' + encodeURIComponent(kw) + '/p/58', results.requestOptions.data.historyKey);
        }
    }));

    return suite;
}).run();
