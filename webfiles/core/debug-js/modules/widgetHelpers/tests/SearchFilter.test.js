UnitTest.addSuite({
    type: UnitTest.Type.Framework,
    jsFiles: ['/euf/core/debug-js/RightNow.Text.js',
              '/euf/core/debug-js/RightNow.Url.js',
              '/euf/core/debug-js/RightNow.Ajax.js',
              '/euf/core/debug-js/RightNow.UI.AbuseDetection.js',
              '/euf/core/debug-js/RightNow.Event.js',
              '/euf/core/debug-js/modules/widgetHelpers/EventProvider.js',
              '/euf/core/debug-js/modules/widgetHelpers/SearchFilter.js'],
    yuiModules: ['history']
}, function(Y){
    var originalLocation = window.location.href,
        ajaxSearchMode = 'pushState' in window.history,
        windowOpenerMock,
        tests;

    //Strip an irksome trailing slash to make the tests more consistent
    originalLocation = (originalLocation.charAt(originalLocation.length - 1) === '/')
        ? originalLocation.slice(0, -1)
        : originalLocation;

    function windowOpener() {
        var self = this;
        this.mock = function(url, target, options) {
            self.url = url;
            self.target = target;
            self.options = options;
        };
    }

    function mockWindowOpener() {
        windowOpenerMock = new windowOpener();
        window.open = windowOpenerMock.mock;
    }

    if (!ajaxSearchMode) {
        mockWindowOpener();
    }

    function verifyBrowserLocation(changedUrl) {
        var expected = originalLocation + (changedUrl || '');

        if (ajaxSearchMode) {
            Y.Assert.areSame(expected, window.location.href);
            // it would be nice to call window.history.back if changedUrl is truthy,
            // but it seems to confound PhantomJS and the SearchFilter's restoration code
        }
        else {
            if (typeof changedUrl === 'string') {
                Y.Assert.areSame(window.location.pathname + changedUrl + (changedUrl.indexOf('/page/2') !== -1 ? '' : '/search/1'), windowOpenerMock.url);
                Y.Assert.areSame('_self', windowOpenerMock.target);
            }
            else {
                Y.Assert.areSame(window.location.pathname + '/search/1', windowOpenerMock.url);
                Y.Assert.areSame('_self', windowOpenerMock.target);
            }
            mockWindowOpener();
        }
    }

    var transactionID = 0;
    RightNow.Ajax.makeRequest = function(url, data, options) {
        Y.Assert.isTrue(options.json);
        Y.Assert.areSame(10000, options.timeout);
        Y.Assert.isNotNull(data);
        //@@@ QA 130304-000072 Ensure that if the page is set to 1, we've set the search flag properly
        if(data.filters){
            var filters = RightNow.JSON.parse(data.filters);
            if(filters.page === 1){
                Y.Assert.areSame(1, filters.search);
            }
        }
        (function(url, id, params) {
            setTimeout(function() {
                if (url === '/ci/ajaxRequest/getAnswer') {
                    options.successHandler.call(window, { result: { ID: parseInt(params.answerID, 10) } }, options.data, id);
                }
                else {
                    options.successHandler.call(window, { per_page: 0, data: [], headers: [], end_num: 0, start_num: 0, total_pages: 0, report_id: parseInt(params.report_id, 10) }, options.data, id);
                }
            }, 900);
        })(url, transactionID, data);

        return {id: transactionID++};
    };

    tests = new Y.Test.Suite({
        name: "SearchFilter",
        setUp: function() {
            RightNow.Url.setParameterSegment(10);

            var testExtender = {
                hurryUpAndWait: function(expectedUrl) {
                    if (ajaxSearchMode) {
                        this.wait();
                    }
                    else {
                        verifyBrowserLocation(expectedUrl);
                    }
                }
            };

            for (var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    tests.add(new Y.Test.Case({
        name: "Tests various search source scenarios",

        _should: {
            error: {
                testNoReportsOrSources: true,
                testEmptyReportsAndSources: true,
                testZeroReportsAndSources: true,
                testNullReportsAndSources: true,
                testNoFiltersForReport: true
            }
        },

        testNoReportsOrSources: function() {
            var widget = RightNow.SearchFilter.extend({
                overrides: {
                    constructor: function() {
                        this.parent();
                        var source = this.searchSource();
                    }
                }
            });
            var instance = new widget({
                attrs: {}
            }, "foo_7", Y);
        },

        testEmptyReportsAndSources: function() {
            var widget = RightNow.SearchFilter.extend({
                overrides: {
                    constructor: function() {
                        this.parent();
                        var source = this.searchSource();
                    }
                }
            });
            var instance = new widget({
                attrs: {
                    report_id: "",
                    source_id: ""
                }
            }, "foo_7", Y);
        },

        testZeroReportsAndSources: function() {
            var widget = RightNow.SearchFilter.extend({
                overrides: {
                    constructor: function() {
                        this.parent();
                        var source = this.searchSource();
                    }
                }
            });
            var instance = new widget({
                attrs: {
                    report_id: 0,
                    source_id: 0
                }
            }, "foo_7", Y);
        },

        testNullReportsAndSources: function() {
            var widget = RightNow.SearchFilter.extend({
                overrides: {
                    constructor: function() {
                        this.parent();
                        var source = this.searchSource();
                    }
                }
            });
            var instance = new widget({
                attrs: {
                    report_id: null,
                    source_id: null
                }
            }, "foo_7", Y);
        },

        testNoFiltersForReport: function() {
            var widget = RightNow.SearchFilter.extend({
                overrides: {
                    constructor: function() {
                        this.parent();
                        this.searchSource().fire('search');
                    }
                }
            });
            new widget({
                attrs: {
                    report_id: 123432
                }
            });
        },

        testSingleReport: function() {
            var widget = RightNow.SearchFilter.extend({
                overrides: {
                    constructor: function() {
                        this.parent();
                        var source = this.searchSource();
                        Y.Assert.areSame("2000", source.instanceID);
                        Y.Assert.areSame("2000", source.searchSource.id);
                        Y.Assert.areSame("report", source.searchSource.type);
                    }
                }
            });
            var instance = new widget({
                attrs: {
                    report_id: 2000,
                    source_id: ""
                }
            }, "foo_7", Y);
        },

        testMultipleReports: function() {
            var widget = RightNow.SearchFilter.extend({
                overrides: {
                    constructor: function() {
                        this.parent();
                        var source = this.searchSource();
                        Y.Assert.areSame(true, source.multiple);
                        Y.Assert.areSame(2, source.sources.length);
                        Y.Assert.areSame("2000", source.sources[0].instanceID);
                        Y.Assert.areSame("2000", source.sources[0].searchSource.id);
                        Y.Assert.areSame("report", source.sources[0].searchSource.type);
                        Y.Assert.areSame("197", source.sources[1].instanceID);
                        Y.Assert.areSame("197", source.sources[1].searchSource.id);
                        Y.Assert.areSame("report", source.sources[1].searchSource.type);
                    }
                }
            });
            var instance = new widget({
                attrs: {
                    report_id: "2000,197",
                    source_id: ""
                }
            }, "foo_7", Y);
        },

        testSingleGeneric: function() {
            var widget = RightNow.SearchFilter.extend({
                overrides: {
                    constructor: function() {
                        this.parent();
                        var source = this.searchSource();
                        Y.Assert.areSame("foo", source.instanceID);
                        Y.Assert.areSame("foo", source.searchSource.id);
                        Y.Assert.areSame("generic", source.searchSource.type);
                    }
                }
            });
            var instance = new widget({
                attrs: {
                    report_id: "",
                    source_id: "foo"
                }
            }, "foo_7", Y);
        },

        testMultipleGeneric: function() {
            var widget = RightNow.SearchFilter.extend({
                overrides: {
                    constructor: function() {
                        this.parent();
                        var source = this.searchSource();
                        Y.Assert.areSame(true, source.multiple);
                        Y.Assert.areSame(2, source.sources.length);
                        Y.Assert.areSame("foo", source.sources[0].instanceID);
                        Y.Assert.areSame("foo", source.sources[0].searchSource.id);
                        Y.Assert.areSame("generic", source.sources[0].searchSource.type);
                        Y.Assert.areSame("banana", source.sources[1].instanceID);
                        Y.Assert.areSame("banana", source.sources[1].searchSource.id);
                        Y.Assert.areSame("generic", source.sources[1].searchSource.type);
                    }
                }
            });
            var instance = new widget({
                attrs: {
                    report_id: "",
                    source_id: "foo,banana"
                }
            }, "foo_7", Y);
        },

        testAll: function() {
            var widget = RightNow.SearchFilter.extend({
                overrides: {
                    constructor: function() {
                        this.parent();
                        var source = this.searchSource();
                        Y.Assert.areSame(true, source.multiple);
                        Y.Assert.areSame(4, source.sources.length);
                        Y.Assert.areSame("2000", source.sources[0].instanceID);
                        Y.Assert.areSame("2000", source.sources[0].searchSource.id);
                        Y.Assert.areSame("report", source.sources[0].searchSource.type);
                        Y.Assert.areSame("197", source.sources[1].instanceID);
                        Y.Assert.areSame("197", source.sources[1].searchSource.id);
                        Y.Assert.areSame("report", source.sources[1].searchSource.type);
                        Y.Assert.areSame("foo", source.sources[2].instanceID);
                        Y.Assert.areSame("foo", source.sources[2].searchSource.id);
                        Y.Assert.areSame("generic", source.sources[2].searchSource.type);
                        Y.Assert.areSame("banana", source.sources[3].instanceID);
                        Y.Assert.areSame("banana", source.sources[3].searchSource.id);
                        Y.Assert.areSame("generic", source.sources[3].searchSource.type);
                    }
                }
            });
            var instance = new widget({
                attrs: {
                    report_id: "2000,197",
                    source_id: "foo,banana"
                }
            }, "foo_7", Y);
        },

        "A specified source ID that doesn't exist for the widget should trigger a dev header warning": function() {
            var warning = null;
            RightNow.UI.DevelopmentHeader = {addJavascriptWarning: function(what) {
                warning = what;
            }};
            // Message bases aren't pre-processed; but all we really care about are the args.
            var origSprintf = RightNow.Text.sprintf;
            RightNow.Text.sprintf = function() {
                return Array.prototype.join.call(arguments);
            };

            var widget = RightNow.SearchFilter.extend({
                overrides: {
                    constructor: function() {
                        this.parent();
                        this.returned = this.searchSource('2345');
                    }
                },
                multiple: function() {
                    this.returned = this.searchSource('23445,reward');
                },
                withOptions: function() {
                    this.returned = this.searchSource({
                        '57': {
                            endpoint: '/cc/foo/bar',
                            filters: 'keyword,sort',
                            params: { key: 'val' }
                        }
                    });
                }
            });

            // Single report id
            var instance = new widget({
                attrs: {
                    report_id: 123
                }
            }, 'foo_7', Y);
            Y.Assert.isString(warning);
            Y.Assert.isTrue(warning.indexOf('2345') > -1, 'message should contain erroneous value');
            Y.Assert.areSame('123', instance.returned.instanceID);
            instance.multiple();
            Y.Assert.isTrue(warning.indexOf('23445') > -1, 'message should contain erroneous value');
            Y.Assert.isTrue(warning.indexOf('reward') > -1, 'message should contain second entry even if there\'s a single source');
            Y.Assert.areSame('123', instance.returned.instanceID);
            instance.withOptions();
            Y.Assert.isTrue(warning.indexOf('57') > -1, 'message should contain erroneous value');
            Y.Assert.areSame('123', instance.returned.instanceID);

            // Multiple report ids
            instance = new widget({
                attrs: {
                    report_id: '123,124'
                }
            }, 'foo_7', Y);
            Y.Assert.isString(warning);
            Y.Assert.isTrue(warning.indexOf('2345') > -1, 'message should contain erroneous value');
            Y.Assert.isTrue(instance.returned.multiple);
            Y.Assert.areSame('123', instance.returned.sources[0].instanceID);
            Y.Assert.areSame('124', instance.returned.sources[1].instanceID);
            instance.multiple();
            Y.Assert.isTrue(warning.indexOf('23445') > -1, 'message should contain erroneous value');
            Y.Assert.isTrue(warning.indexOf('reward') > -1, 'message should contain second entry for multiple sources');
            Y.Assert.isTrue(instance.returned.multiple);
            Y.Assert.areSame('123', instance.returned.sources[0].instanceID);
            Y.Assert.areSame('124', instance.returned.sources[1].instanceID);
            instance.withOptions();
            Y.Assert.isTrue(warning.indexOf('57') > -1, 'message should contain erroneous value');
            Y.Assert.isTrue(instance.returned.multiple);
            Y.Assert.areSame('123', instance.returned.sources[0].instanceID);
            Y.Assert.areSame('124', instance.returned.sources[1].instanceID);

            // Report and generic
            instance = new widget({
                attrs: {
                    report_id: 123,
                    source_id: 'banana'
                }
            }, 'foo_7', Y);
            Y.Assert.isString(warning);
            Y.Assert.isTrue(warning.indexOf('2345') > -1, 'message should contain erroneous value');
            Y.Assert.isTrue(instance.returned.multiple);
            Y.Assert.areSame('123', instance.returned.sources[0].instanceID);
            Y.Assert.areSame('banana', instance.returned.sources[1].instanceID);
            instance.multiple();
            Y.Assert.isTrue(warning.indexOf('23445') > -1, 'message should contain erroneous value');
            Y.Assert.isTrue(warning.indexOf('reward') > -1, 'message should contain second entry for multiple sources');
            Y.Assert.isTrue(instance.returned.multiple);
            Y.Assert.areSame('123', instance.returned.sources[0].instanceID);
            Y.Assert.areSame('banana', instance.returned.sources[1].instanceID);
            instance.withOptions();
            Y.Assert.isTrue(warning.indexOf('57') > -1, 'message should contain erroneous value');
            Y.Assert.isTrue(instance.returned.multiple);
            Y.Assert.areSame('123', instance.returned.sources[0].instanceID);
            Y.Assert.areSame('banana', instance.returned.sources[1].instanceID);

            RightNow.Text.sprintf = origSprintf;
        }
    }));

    tests.add(new Y.Test.Case({
        name : "Tests arbitrary event firing",

        "An arbitrarily-named event should properly fire to search source instances": function() {
            var reportHandlerCalled = false,
                reportHandler = function(name, args) {
                    reportHandlerCalled = true;
                    Y.Assert.areSame("go", name);
                    Y.Assert.isUndefined(this.data);
                    Y.Assert.isUndefined(this.instanceID);
                    Y.Assert.isArray(args);
                    Y.Assert.areSame("bar", args[0].data.foo);
                },
                sourceHandlerCalled = false,
                sourceHandler = function(name, args) {
                    sourceHandlerCalled = true;
                    Y.Assert.areSame("go", name);
                    Y.Assert.areSame("banana", this.data.attrs.source_id);
                    Y.Assert.areSame(2000, this.data.attrs.report_id);
                    Y.Assert.areSame("foo_7", this.instanceID);
                    Y.Assert.isArray(args);
                    Y.Assert.areSame("bar", args[0].data.foo);
                },
                allHandlerCalled = false,
                allHandler = function(name, args) {
                    allHandlerCalled = true;
                    Y.Assert.areSame("go", name);
                    Y.Assert.areSame("foo_7", this.instanceID);
                    Y.Assert.isArray(args);
                    Y.Assert.areSame("bar", args[0].data.foo);
                },
                someOtherHandlerCalled = false,
                someOtherHandler = function() {
                    someOtherHandlerCalled = true;
                },
                widget = RightNow.SearchFilter.extend({
                    overrides: {
                        constructor: function() {
                            this.parent();
                            this.searchSource(this.data.attrs.report_id).on("go", reportHandler /* no scope passed */);
                            this.searchSource(this.data.attrs.source_id).on("go", sourceHandler, this);
                            this.searchSource().on("go", allHandler, this).fire("go", new RightNow.Event.EventObject(this, {data: {foo: "bar"}}));
                        }
                    }
                });
            new widget({
                attrs: {
                    report_id: 2000,
                    source_id: "banana"
                }
            }, "foo_7", Y);
            Y.Assert.isTrue(reportHandlerCalled);
            Y.Assert.isTrue(sourceHandlerCalled);
            Y.Assert.isTrue(allHandlerCalled);
            Y.Assert.isFalse(someOtherHandlerCalled);
        }
    }));

    tests.add(new Y.Test.Case({
        name: "Tests generic search sources",

        _should: {
            error: {
                testSettingOptionsForNonExistentSource: ajaxSearchMode
            }
        },

        testSettingGenericSourceOptions: function() {
            var that = this,
                widget = RightNow.SearchFilter.extend({
                overrides: {
                    constructor: function() {
                        this.parent();
                        var source = this.searchSource({
                            banana: {
                                endpoint: "/ci/ajaxRequest/getAnswer",
                                params: {
                                    banana: "no",
                                    something: true
                                },
                                filters: "keyword,sort,nothing"
                            }
                        }).once("search", function testSearch() {
                            return new RightNow.Event.EventObject(this, {data: {somethingElse: "yes", answerID: "1"}});
                        }).once("response", function(name, args) {
                            that.resume(function() {
                                Y.Assert.areSame("response", name);
                                Y.Assert.isArray(args);
                                Y.Assert.areSame(1, args.length);
                                Y.Assert.areSame(1, args[0].data.result.ID);
                                Y.Assert.areSame("1", args[0].filters.answerID);
                                Y.Assert.areSame("no", args[0].filters.banana);
                                Y.Assert.areSame(true, args[0].filters.something);
                                Y.Assert.areSame("yes", args[0].filters.somethingElse);
                                verifyBrowserLocation();
                            });
                        }).fire("search", new RightNow.Event.EventObject(this, {data: {}}));
                    }
                }
            });
            var instance = new widget({
                attrs: {
                    report_id: "",
                    source_id: "banana"
                }
            }, "foo_7", Y);

            this.hurryUpAndWait();
        },

        testSettingGenericSourceOptionsWithCacheHit: function() {
            //Due to the previous test the cache should be primed and this test will fire synchronously
            var widget = RightNow.SearchFilter.extend({
                overrides: {
                    constructor: function() {
                        this.parent();
                        var source = this.searchSource({
                            banana: {
                                endpoint: "/ci/ajaxRequest/getAnswer",
                                params: {
                                    banana: "no",
                                    something: true
                                },
                                filters: "keyword,sort,nothing"
                            }
                        }).once("search", function() {
                            return new RightNow.Event.EventObject(this, {data: {somethingElse: "yes", answerID: "1"}});
                        }).once("response", function(name, args) {
                            Y.Assert.areSame("response", name);
                            Y.Assert.isArray(args);
                            Y.Assert.areSame(1, args.length);
                            Y.Assert.areSame(1, args[0].data.result.ID);
                            Y.Assert.areSame("1", args[0].filters.answerID);
                            Y.Assert.areSame("no", args[0].filters.banana);
                            Y.Assert.areSame(true, args[0].filters.something);
                            Y.Assert.areSame("yes", args[0].filters.somethingElse);
                            verifyBrowserLocation();
                        }).fire("search", new RightNow.Event.EventObject(this, {data: {}}));
                    }
                }
            });
            var instance = new widget({
                attrs: {
                    report_id: "",
                    source_id: "banana"
                }
            }, "foo_7", Y);
        },
        testSettingOptionsForNonExistentSourceButWithExistingDefault: function() {
            var that = this,
                widget = RightNow.SearchFilter.extend({
                overrides: {
                    constructor: function() {
                        this.parent();
                        this.searchSource({
                            //These value are being set for a non-existent filter, so the value from the previous default
                            //in the last test - Banana, end up being used.
                            nonono: {
                                endpoint: "/ci/ajaxRequest/getAnswer",
                                params: {
                                    banana: "yes",
                                    something: false
                                },
                                filters: "keyword,sort,nothing"
                            }
                        }).once("search", function() {
                            return new RightNow.Event.EventObject(this, {data: {somethingElse: "yes", answerID: "2"}});
                        }).once("response", function(name, args) {
                            that.resume(function() {
                                Y.Assert.areSame("response", name);
                                Y.Assert.isArray(args);
                                Y.Assert.areSame(1, args.length);
                                Y.Assert.areSame(2, args[0].data.result.ID);
                                Y.Assert.areSame("2", args[0].filters.answerID);
                                Y.Assert.areSame("no", args[0].filters.banana);
                                Y.Assert.areSame(true, args[0].filters.something);
                                Y.Assert.areSame("yes", args[0].filters.somethingElse);
                                verifyBrowserLocation();
                            });
                        }).fire("search", new RightNow.Event.EventObject(this, {data: {}}));
                    }
                }
            });
            var instance = new widget({
                attrs: {
                    report_id: "",
                    source_id: "banana"
                }
            }, "foo_7", Y);

            this.hurryUpAndWait();
        },

        "Searching with a generic source will put keyword in URL for AJAX and kw in URL for page flip": function() {
            var that = this,
                widget = RightNow.SearchFilter.extend({
                overrides: {
                    constructor: function() {
                        this.parent();
                        this.searchSource({
                            sourceAjax: {
                                endpoint: "/ci/ajaxRequest/getAnswer"
                            }
                        }).once("response", function(name, args) {
                            that.resume(function() {
                                Y.Assert.areSame("response", name);
                                Y.Assert.isArray(args);
                                Y.Assert.areSame(1, args.length);
                                // note that 'keyword' is here (AJAX call) and 'kw' below (page flip)
                                verifyBrowserLocation("/keyword/full/page/2");
                            });
                        })
                        .fire("setInitialFilters", new RightNow.Event.EventObject(this, {
                            "filters":{
                                "allFilters":{
                                    "keyword":{
                                        "filters":{
                                            "rnSearchType":"keyword",
                                            "data":"full"
                                        },
                                        "type":"keyword"
                                    },
                                    "kw":{
                                        "filters":{
                                            "rnSearchType":"keyword",
                                            "data":"partial"
                                        },
                                        "type":"keyword"
                                    }
                                }
                            }
                        }))
                        .fire("search", new RightNow.Event.EventObject(this, {data: {page: 2}}));
                    }
                }
            });
            var instance = new widget({
                attrs: {
                    report_id: "",
                    source_id: "sourceAjax"
                }
            }, "foo_7", Y);

            // note that 'kw' is here (page flip) and 'keyword' above (AJAX call)
            this.hurryUpAndWait("/kw/full/page/2");
        },

        testSettingOptionsForNonExistentSource: function() {
            var widget = RightNow.SearchFilter.extend({
                overrides: {
                    constructor: function() {
                        this.parent();
                        var source = this.searchSource({
                            nonono: {
                                endpoint: "/ci/ajaxRequest/getAnswer",
                                params: {
                                    banana: "no",
                                    something: true
                                },
                                filters: "keyword,sort,nothing"
                            }
                        }).on("search", function() {
                            return new RightNow.Event.EventObject(this, {data: {somethingElse: "yes", answerID: "1"}});
                        }).on("response", function(name, args){
                            Y.assert.fail(); //If this is encountered fail the test because the non-existent source should never trigger a response
                        }).fire("search", new RightNow.Event.EventObject(this, {data: {}}));
                    }
                }
            });
            var instance = new widget({
                attrs: {
                    report_id: "",
                    source_id: "grapefruit"
                }
            }, "foo_7", Y);
        }
    }));

    tests.add(new Y.Test.Case({
        name: "testSearchForReport",

        'A search should clone all of the collected filters': function() {
            var that = this,
                originalEventObject,
                widget = RightNow.SearchFilter.extend({
                overrides: {
                    constructor: function() {
                        var widgetInstance = this;
                        this.parent();

                        this.searchSource()
                        .once('search', function() {
                            //Just return the event object that we represent, it will be collected and cloned.
                            //Save it in a variable so that we can test if it is a clone later.
                            originalEventObject = new RightNow.Event.EventObject(widgetInstance, {
                                filters: {
                                    data: {
                                        fltr_id: 9,
                                        oper_id: 1,
                                        val: '~any~'
                                    },
                                    report_id: '101456',
                                    rnSearchType: 'filterDropdown',
                                    searchName: 'custom_filter'
                                }
                            });
                            return originalEventObject;
                        })
                        .once('response', function(evtName, args) {
                            //Okay, the response has come through, lets check if the EO is a clone or the real thing
                            that.resume(function() {
                                //Check that all of the data is the same
                                args = args[0].filters.allFilters.custom_filter;
                                Y.Assert.areSame(9, args.filters.data.fltr_id);
                                Y.Assert.areSame(1, args.filters.data.oper_id);
                                Y.Assert.areSame('~any~', args.filters.data.val);
                                Y.Assert.areSame('101456', args.filters.report_id);
                                Y.Assert.areSame('filterDropdown', args.filters.rnSearchType);
                                Y.Assert.areSame('custom_filter', args.filters.searchName);
                                Y.Assert.areSame('foo_7', args.w_id);

                                //Ensure that our originalEventObject hasn't been altered
                                Y.Assert.areSame('foo_7', originalEventObject.w_id);
                                Y.Assert.areSame('filterDropdown', originalEventObject.filters.rnSearchType);
                                Y.Assert.areSame(9, originalEventObject.filters.data.fltr_id);

                                //Alter the newly returned event object
                                args.w_id = 'aFake';
                                args.filters.rnSearchType = 'anotherFake';
                                args.filters.data.fltr_id = 47;

                                //Verify that updating the new event object doesn't modify the original
                                Y.Assert.areSame('foo_7', originalEventObject.w_id);
                                Y.Assert.areSame('filterDropdown', originalEventObject.filters.rnSearchType);
                                Y.Assert.areSame(9, originalEventObject.filters.data.fltr_id);
                                verifyBrowserLocation("/custom_filter/~any~/page/1");
                            });
                        })
                        .fire('search', new RightNow.Event.EventObject(this, {data: {}}));
                    }
                }
            });

            var instance = new widget({
                attrs: {
                    report_id: 101456
                }
            }, 'foo_7', Y);

            this.hurryUpAndWait("/custom_filter/~any~");
        },
        testSearchScenario: function() {
            var that = this,
                triggerObject,
                widget = RightNow.SearchFilter.extend({
                overrides: {
                    constructor: function() {
                        this.parent();
                        triggerObject = new RightNow.Event.EventObject(this, {});
                        this.searchSource()
                            .once("search", function() {
                                return new RightNow.Event.EventObject(this, {data: "iphone", filters: {
                                    searchName: "keyword",
                                    data: "iphone",
                                    rnSearchType: "keyword",
                                    report_id: this.data.attrs.report_id
                                }});
                            }, this)
                            .once("response", function(name, args) {
                                that.resume(function() {
                                    // Widget's trigger object wasn't touched; its clone was.
                                    Y.Assert.isUndefined(triggerObject.filters.page);

                                    Y.Assert.areSame("response", name);
                                    Y.Assert.isArray(args);
                                    Y.Assert.areSame(1, args.length);
                                    Y.Assert.areSame(0, args[0].data.per_page);
                                    Y.Assert.areSame('/kw/iphone', args[0].filters.format.urlParms);
                                    Y.Assert.areSame(200, args[0].filters.format.truncate_size);
                                    Y.Assert.areSame('kw', args[0].filters.format.parmList);
                                    Y.Assert.isTrue(args[0].filters.format.emphasisHighlight);
                                    Y.Assert.isNull(args[0].filters.format.tabindex);
                                    Y.Assert.isUndefined(args[0].filters.search);
                                    Y.Assert.isUndefined(args[0].filters.allFilters.search);

                                    verifyBrowserLocation("/kw/iphone/page/1");
                                });
                            }, this)
                            .fire("setInitialFilters", new RightNow.Event.EventObject(this, {
                                "filters":{
                                    "token": "bJ3_k2iZaJlymWSZfpl_mUqZSJlCmUiZUgfyB1oHXgc!",
                                    "format":{
                                        "tabindex":null,
                                        "truncate_size":200,
                                        "emphasisHighlight":true,
                                        "urlParms":"",
                                        "parmList":"kw"
                                    },
                                    "report_id":"2000",
                                    "allFilters":{
                                        "recordKeywordSearch":true,
                                        "per_page":0,
                                        "keyword":{
                                            "filters":{
                                                "rnSearchType":"keyword",
                                                "data":"",
                                                "report_id":"2000"
                                            },
                                            "type":"keyword"
                                        },
                                        "page":1
                                    }
                                }
                            }))
                            .fire("appendFilter", new RightNow.Event.EventObject(this, {filters: { per_page: 4}}))
                            .fire("search", triggerObject);
                    }
                }
            });
            var instance = new widget({
                attrs: {
                    report_id: 2000
                }
            }, "foo_7", Y);

            this.hurryUpAndWait("/kw/iphone/page/1");
        },

        testScenarioWithPagination: function() {
            var that = this,
                testResponse = function(name, args) {
                    Y.Assert.areSame("response", name);
                    Y.Assert.isArray(args);
                    Y.Assert.areSame(1, args.length);
                    Y.Assert.areSame(0, args[0].data.per_page);
                    Y.Assert.areSame(200, args[0].filters.format.truncate_size);
                    Y.Assert.areSame('kw,p', args[0].filters.format.parmList);
                    Y.Assert.isTrue(args[0].filters.format.emphasisHighlight);
                    //@@@ QA 130812-000097
                    Y.Assert.isUndefined(args[0].filters.allFilters.search);
                    Y.Assert.isNull(args[0].filters.format.tabindex);
                    verifyBrowserLocation("/st/5/page/2");
                },
                widget = RightNow.SearchFilter.extend({
                overrides: {
                    constructor: function() {
                        this.parent();
                        this.searchSource(this.data.attrs.report_id)
                            .once("response", function(name, args) {
                                that.resume(function() {testResponse(name, args);});
                            }, this)
                            .fire("setInitialFilters", new RightNow.Event.EventObject(this, {
                                "filters":{
                                    "token": "bJ3_k2iZaJlymWSZfpl_mUqZSJlCmUiZUgfyB1oHXgc!",
                                    "format":{
                                        "tabindex":null,
                                        "truncate_size":200,
                                        "emphasisHighlight":true,
                                        "urlParms":"",
                                        "parmList":"kw,p"
                                    },
                                    "report_id":"2000",
                                    "allFilters":{
                                        "recordKeywordSearch":true,
                                        "per_page":0,
                                        "searchType":{
                                            "filters":{
                                                "rnSearchType":"searchType",
                                                "fltr_id":5,
                                                "data":5,
                                                "oper_id":1,
                                                "report_id":"176"
                                            },
                                            "type":"searchType"
                                        },
                                        "keyword":{
                                            "filters":{
                                                "rnSearchType":"keyword",
                                                "data":"",
                                                "report_id":"2000"
                                            },
                                            "type":"keyword"
                                        },
                                        "page":1,
                                        //@@@ QA 130812-000097
                                        "search":1
                                    }
                                }
                            }))
                            .fire("appendFilter", new RightNow.Event.EventObject(this, {filters: {page:2}}))
                            .fire("search", new RightNow.Event.EventObject(this, {filters: {page:2}}));
                    }
                }
            });
            var instance = new widget({
                attrs: {
                    report_id: 2000
                }
            }, "foo_7", Y);

            this.hurryUpAndWait("/st/5/page/2");
        },

        testScenarioWithAdditionalFilters: function() {
            var that = this,
                testResponse = function(name, args) {
                    Y.Assert.areSame("response", name);
                    Y.Assert.isArray(args);
                    Y.Assert.areSame(1, args.length);
                    Y.Assert.areSame(0, args[0].data.per_page);
                    Y.Assert.areSame('/kw/iphone', args[0].filters.format.urlParms);
                    Y.Assert.areSame(200, args[0].filters.format.truncate_size);
                    Y.Assert.areSame('kw,p', args[0].filters.format.parmList);
                    Y.Assert.isTrue(args[0].filters.format.emphasisHighlight);
                    Y.Assert.isNull(args[0].filters.format.tabindex);
                    verifyBrowserLocation("/st/5/kw/iphone/page/1");
                },
                widget = RightNow.SearchFilter.extend({
                overrides: {
                    constructor: function() {
                        this.parent();
                        this.searchSource(this.data.attrs.report_id)
                            .once("search", function() {
                                return new RightNow.Event.EventObject(this, {data: "iphone", filters: {
                                    searchName: "keyword",
                                    data: "iphone",
                                    rnSearchType: "keyword",
                                    report_id: this.data.attrs.report_id
                                }});
                            }, this)
                            .once("response", function(name, args) {
                                that.resume(function() { testResponse(name, args); });
                            }, this)
                            .fire("setInitialFilters", new RightNow.Event.EventObject(this, {
                                "filters":{
                                    "token": "bJ3_k2iZaJlymWSZfpl_mUqZSJlCmUiZUgfyB1oHXgc!",
                                    "format":{
                                        "tabindex":null,
                                        "truncate_size":200,
                                        "emphasisHighlight":true,
                                        "urlParms":"",
                                        "parmList":"kw,p"
                                    },
                                    "report_id":"2000",
                                    "allFilters":{
                                        "recordKeywordSearch":true,
                                        "per_page":0,
                                        "searchType":{
                                            "filters":{
                                                "rnSearchType":"searchType",
                                                "fltr_id":5,
                                                "data":5,
                                                "oper_id":1,
                                                "report_id":"176"
                                            },
                                            "type":"searchType"
                                        },
                                        "keyword":{
                                            "filters":{
                                                "rnSearchType":"keyword",
                                                "data":"",
                                                "report_id":"2000"
                                            },
                                            "type":"keyword"
                                        },
                                        "page":1
                                    }
                                }
                            }))
                            .fire("search", new RightNow.Event.EventObject(this, {}));
                    }
                }
            });
            var instance = new widget({
                attrs: {
                    report_id: 2000
                }
            }, "foo_7", Y);

            this.hurryUpAndWait("/st/5/kw/iphone/page/1");
        },

        "Searching a report without specifying initial filters shouldn't throw a JS error": function() {
            var that = this,
                widget = RightNow.SearchFilter.extend({
                overrides: {
                    constructor: function() {
                        this.parent();
                        this.searchSource()
                            .once("search", function() {
                                return new RightNow.Event.EventObject(this, {data: "banana", filters: {
                                    searchName: "keyword",
                                    rnSearchType: "keyword",
                                    report_id: this.data.attrs.report_id
                                }});
                            }, this)
                            .once("response", function(name, args) {
                                that.resume(function() {
                                    Y.Assert.areSame("response", name);
                                    Y.Assert.isArray(args);
                                    Y.Assert.areSame(1, args.length);
                                    args = args[0];
                                    if (Object.keys) {
                                        var keys = Object.keys(args.filters.allFilters);
                                        Y.Assert.areSame(2, keys.length);
                                        Y.Assert.areSame('keyword', keys[0]);
                                        Y.Assert.areSame('page', keys[1]);
                                    }
                                    // Basically get back an empty response
                                    Y.Assert.areSame('banana', args.filters.allFilters.keyword.data);
                                    Y.Assert.isArray(args.data.data);
                                    Y.Assert.areSame(0, args.data.data.length);
                                    Y.Assert.isArray(args.data.headers);
                                    Y.Assert.areSame(0, args.data.headers.length);
                                    Y.Assert.areSame(0, args.data.end_num);
                                    Y.Assert.areSame(0, args.data.start_num);
                                    Y.Assert.areSame(0, args.data.total_pages);
                                    Y.Assert.areSame(98542, args.data.report_id);

                                    verifyBrowserLocation('/page/1');
                                });
                            })
                            .fire("search", new RightNow.Event.EventObject(this, {}));
                    }
                }
            });
            var instance = new widget({
                attrs: {
                    report_id: 98542
                }
            }, "foo_7", Y);

            this.hurryUpAndWait('');
        },

        "The filters in the original evt object firing the 'search' event should be applied toward the search": function() {
            var assert = {};
            var widget = RightNow.SearchFilter.extend({
                overrides: {
                    constructor: function() {
                        this.parent();
                        this.searchSource().on('send', this.onSend, this);
                    }
                },
                search: function() {
                    this.searchSource().fire('search', new RightNow.Event.EventObject(this, {
                        filters: {
                            searchName: 'sort_or_whatever',
                            report_id: this.data.attrs.report_id,
                            data: {
                                col_id: 45,
                                sort_direction: 2
                            }
                        }
                    }));
                },
                onSend: function(evt, args) {
                    assert.called = true;
                    assert.given = args[0];
                    return false;
                }
            });
            var instance = new widget({
                attrs: {
                    report_id: 123433
                }
            }, 'bar_34', Y);
            instance.search();
            this.wait(function() {
                Y.Assert.isTrue(assert.called);
                Y.Assert.isObject(assert.given);
                Y.Assert.isObject(assert.given.allFilters);
                Y.Assert.isObject(assert.given.allFilters.sort_or_whatever);
                Y.Assert.areSame(45, assert.given.allFilters.sort_or_whatever.filters.data.col_id);
                Y.Assert.areSame(2, assert.given.allFilters.sort_or_whatever.filters.data.sort_direction);
            }, 500);
        },

        "If a non-EventObject is used to fire the 'search' and 'setInitialFilters' events then no JS errors are thrown": function() {
            var widget = RightNow.SearchFilter.extend({
                overrides: {
                    constructor: function() {
                        this.parent();
                        this.searchSource().on('send', this.send, this);
                        this.searchSource().fire('setInitialFilters');
                        this.searchSource().fire('setInitialFilters', {data: 'foo', filters: { banana: 'nope' }});
                        this.searchSource().fire('setInitialFilters', new RightNow.Event.EventObject());
                        this.searchSource().fire('search');
                        this.searchSource().fire('search', {data: 'foo', filters: { banana: 'nope' }});
                    }
                },
                sent: 0,
                send: function() {
                    this.sent++;
                    Y.Assert.isUndefined(this.searchSource()._filters);
                    Y.Assert.isUndefined(this.searchSource()._respondingFilters);
                    return false;
                }
            });
            var instance = new widget({
                attrs: { report_id: 33333 }
            }, 'heyheyhey', Y);
            Y.Assert.areSame(2, instance.sent);
        },

        "If 'reset' is fired and the source doesn't have a _params member then no JS errors are thrown": function() {
            var widget = RightNow.SearchFilter.extend({
                overrides: {
                    constructor: function() {
                        this.parent();
                        this.searchSource().fire('reset');
                        Y.Assert.isObject(this.searchSource()._filters);
                        this.hurrah = 'hooray';
                    }
                }
            });
            var instance = new widget({
                attrs: { report_id: 329182 }
            }, 'nonononono', Y);
            Y.Assert.areSame('hooray', instance.hurrah);
        }
    }));

    tests.add(new Y.Test.Case({
        name : "Test generic and report searches",

        testBoth: function() {
            var that = this,
                widget = RightNow.SearchFilter.extend({
                overrides: {
                    constructor: function() {
                        this.parent();
                        this.searchSource(this.data.attrs.report_id)
                            .once("response", function(name, args) {
                                Y.Assert.areSame("response", name);
                                Y.Assert.isArray(args);
                                Y.Assert.areSame(1, args.length);
                                Y.Assert.areSame(0, args[0].data.per_page);
                                verifyBrowserLocation("/page/1/st/5/kw/iphone");
                            })
                            .fire("search", new RightNow.Event.EventObject(this, {}));

                        this.searchSource(this.data.attrs.source_id)
                            .once("response", function(name, args) {
                                Y.Assert.areSame("response", name);
                                Y.Assert.isArray(args);
                                Y.Assert.areSame(1, args.length);
                                Y.Assert.areSame(2, args[0].data.result.ID);
                                Y.Assert.areSame("2", args[0].filters.answerID);
                                Y.Assert.areSame("no", args[0].filters.banana);
                                Y.Assert.areSame(true, args[0].filters.something);
                                Y.Assert.areSame("yes", args[0].filters.somethingElse);
                            })
                            .fire("search", new RightNow.Event.EventObject(this, {data: {}}));
                    }
                }
            });
            var instance = new widget({
                attrs: {
                    report_id: 2000,
                    source_id: "banana"
                }
            }, "foo_7", Y);
        },

       "Test clearCache() is clearing the cached data": function() {
            var widget = RightNow.SearchFilter.extend({
                overrides: {
                    constructor: function() {
                        this.parent();
                        this.searchSource()
                            .once("search", function() {
                                return new RightNow.Event.EventObject(this, {data: "banana_cache", filters: {
                                    searchName: "keyword",
                                    rnSearchType: "keyword",
                                    report_id: this.data.attrs.report_id
                                }});
                            }, this)
                            .once("response", function(name, args) {
                                Y.Assert.areSame(true, this.searchSource().doesCacheExist());
                                this.searchSource().clearCache();
                                Y.Assert.areSame(false, this.searchSource().doesCacheExist());
                            }, this)
                            .fire("search", new RightNow.Event.EventObject(this, {}));
                    }
                }
            });
            var instance = new widget({
                attrs: {
                    report_id: 98543
                }
            }, "foo_7", Y);
        }

    }));

    tests.add(new Y.Test.Case({
        name : "Test history manager",

        "Consumers should know when the history manager is providing a response": function() {
            if (!ajaxSearchMode)
                return;

            var that = this,
                widget = RightNow.SearchFilter.extend({
                    overrides: {
                        constructor: function() {
                            this.parent();
                            this.searchSource(this.data.attrs.report_id)
                                .once("response", function(name, args) {
                                    Y.Assert.areSame("response", name);
                                    Y.Assert.isArray(args);
                                    Y.Assert.areSame(1, args.length);
                                    Y.Assert.areSame('undefined', typeof args[0].data.fromHistoryManager);
                                })
                                .fire("response", new RightNow.Event.EventObject(this, {data: {}}));
                            this.searchSource(this.data.attrs.report_id)
                                .once("response", function(name, args) {
                                    Y.Assert.areSame("response", name);
                                    Y.Assert.isArray(args);
                                    Y.Assert.areSame(1, args.length);
                                    Y.Assert.areSame(true, args[0].data.fromHistoryManager);
                                });
                        }
                    }
                });
            var instance = new widget({
                attrs: {
                    report_id: 2000
                }
            }, "foo_7", Y);

            // I'm not sure why I need to pushState twice, but that keeps the tests happy
            window.history.pushState({state: {searchSource: {response: {}, data: {searchSource: 'report2000'}}}}, null, '/ci/unitTest/javascript/framework/modules/widgetHelpers/tests/SearchFilter/random/random');
            window.history.pushState({state: {searchSource: {response: {}, data: {searchSource: 'report2002'}}}}, null, '/ci/unitTest/javascript/framework/modules/widgetHelpers/tests/SearchFilter/random2/random2');
            window.history.back();
        }
    }));

    return tests;
});

// This suite runs after the previous suite completes
UnitTest.addSuite({
    type: UnitTest.Type.Framework
}, function(Y){
    var suite = new Y.Test.Suite("Additional Search Filter Testing - new page searches");
    suite.add(new Y.Test.Case({
        name: 'Test new page searching',
        setUp: function() {
            this.origOpen = window.open;
            var self = this, windowOpenCalled = false;
            window.open = function(url, target, options) {
                if (windowOpenCalled)
                    return;
                windowOpenCalled = true;
                self.url = url;
                self.target = target || 'None specified';
                self.options = options || 'None specified';
            };
            this.widget = RightNow.SearchFilter.extend({
                overrides: {
                    constructor: function() {
                        this.parent();
                    }
                },
                doASearch: function(newPage) {
                    var attrs = this.data.attrs;
                    this.searchSource().fire('search', new RightNow.Event.EventObject(this, { filters: {
                        report_id: attrs.report_id,
                        source_id: attrs.source_id,
                        reportPage: attrs.search_page,
                        newPage: (typeof newPage === 'undefined') ? true : newPage,
                        target: attrs.target,
                        popupWindow: attrs.popup_window,
                        width: attrs.width,
                        height: attrs.height,
                        searchName: 'keyword',
                        data: 'banana',
                        rnSearchType: 'keyword'
                    }}));
                },
                doASearchWithPage: function(isReportSearch, includePageInSearch) {
                    var attrs = this.data.attrs,
                        filters = { filters: {
                            report_id: attrs.report_id,
                            source_id: attrs.source_id,
                            reportPage: attrs.search_page,
                            newPage: true,
                            searchName: 'keyword',
                            data: 'banana',
                            rnSearchType: 'keyword'
                        }},
                        searchSource = this.searchSource();

                    if (isReportSearch)
                        // fire event for report filtering
                        searchSource.fire("appendFilter", new RightNow.Event.EventObject(this, { filters: {
                            report_id: attrs.report_id,
                            source_id: attrs.source_id,
                            page: 42
                        }}));
                    else
                        // fire event for generic source filtering
                        searchSource.fire("setInitialFilters", new RightNow.Event.EventObject(this, {
                            "data": {"page": 37}
                        }));

                    // if 'page' key is not in search filters, we consider this a new search
                    if (includePageInSearch)
                        filters.filters.page = 425;

                    searchSource.fire('search', new RightNow.Event.EventObject(this, filters));
                }
            });
        },

        tearDown: function() {
            window.open = this.origOpen;
            this.widget = null;
        },

        _should: {
            ignore: {
                "Current page's URL params are removed before new search's filters are added": !('pushState' in window.history)
            }
        },

        //@@@ QA 130213-000091
        'Simple report search with keyword and product': function() {
            var instance = new this.widget({attrs: {
                report_id: 12345,
                search_page: ''
            }}, 'abc', Y);
            var handle = instance.searchSource().on('search', function() { return new RightNow.Event.EventObject(this, { filters: {
                searchName: 'p',
                data: [7],
                rnSearchType: 'menufilter'
            }})});
            instance.doASearch();
            Y.Assert.areSame('/ci/unitTest/javascript/framework/modules/widgetHelpers/tests/SearchFilter/kw/banana/p/7/search/1', this.url);
            Y.Assert.areSame('_self', this.target);
            Y.Assert.areSame('None specified', this.options);
        },
        //@@@ QA 130213-000091
        'Simple generic search with keyword and ignored product': function() {
            var instance = new this.widget({attrs: {
                source_id: 123,
                search_page: ''
            }}, 'abc', Y);
            var handle = instance.searchSource().on('search', function() { return new RightNow.Event.EventObject(this, { filters: {
                searchName: 'p',
                data: [7],
                rnSearchType: 'menufilter'
            }})});
            instance.doASearch();
            Y.Assert.areSame('/ci/unitTest/javascript/framework/modules/widgetHelpers/tests/SearchFilter/kw/banana/search/1', this.url);
            Y.Assert.areSame('_self', this.target);
            Y.Assert.areSame('None specified', this.options);
        },
        //@@@ QA 130213-000091
        'Simple report and generic search with keyword and product': function() {
            var instance = new this.widget({attrs: {
                report_id: 123456,
                source_id: 1234,
                search_page: ''
            }}, 'abc', Y);
            var handle = instance.searchSource().on('search', function() { return new RightNow.Event.EventObject(this, { filters: {
                searchName: 'p',
                data: [7],
                rnSearchType: 'menufilter'
            }})});
            instance.doASearch();
            Y.Assert.areSame('/ci/unitTest/javascript/framework/modules/widgetHelpers/tests/SearchFilter/kw/banana/p/7/search/1', this.url);
            Y.Assert.areSame('_self', this.target);
            Y.Assert.areSame('None specified', this.options);
        },
        'Search should be on same page, Given no report page': function() {
            var instance = new this.widget({attrs: {
                report_id: 2321,
                search_page: ''
            }}, 'abc', Y);
            instance.doASearch();
            Y.Assert.areSame('/ci/unitTest/javascript/framework/modules/widgetHelpers/tests/SearchFilter/kw/banana/search/1', this.url);
            Y.Assert.areSame('_self', this.target);
            Y.Assert.areSame('None specified', this.options);
        },
        'Search should go to given report page': function() {
            var instance = new this.widget({attrs: {
                report_id: 2321,
                search_page: '/app/foo/bar'
            }}, 'abc', Y);
            instance.doASearch();
            Y.Assert.areSame('/app/foo/bar/kw/banana/search/1', this.url);
            Y.Assert.areSame('_self', this.target);
            Y.Assert.areSame('None specified', this.options);
        },
        'Search should go to new page without having to specify `newPage`': function() {
            var instance = new this.widget({attrs: {
                report_id: 2321,
                search_page: '/app/foo/bar'
            }}, 'abc', Y);
            instance.doASearch(null);
            Y.Assert.areSame('/app/foo/bar/kw/banana/search/1', this.url);
            Y.Assert.areSame('_self', this.target);
            Y.Assert.areSame('None specified', this.options);
        },
        'Search should use target': function() {
            var instance = new this.widget({attrs: {
                report_id: 2321,
                search_page: 'http://placesheen.com/',
                target: '_blank'
            }}, 'abc', Y);
            instance.doASearch();
            Y.Assert.areSame('http://placesheen.com/kw/banana/search/1', this.url);
            Y.Assert.areSame('_blank', this.target);
            Y.Assert.areSame('None specified', this.options);
        },
        'Search should use popup window options': function() {
            var instance = new this.widget({attrs: {
                report_id: 2321,
                search_page: 'http://placesheen.com/',
                popup_window: true,
                width: 10,
                height: 80
            }}, 'abc', Y);
            instance.doASearch();
            Y.Assert.areSame('http://placesheen.com/kw/banana/search/1', this.url);
            Y.Assert.areSame('_blank', this.target);
            Y.Assert.isTrue(this.options.indexOf('scrollbars=1,resizable=1') > -1);
            Y.Assert.isTrue(this.options.indexOf('top') > -1);
            Y.Assert.isTrue(this.options.indexOf('left') > -1);
            Y.Assert.isTrue(this.options.indexOf('width') > -1);
            Y.Assert.isTrue(this.options.indexOf('height') > -1);
            Y.Assert.isFalse(this.options.indexOf('NaN') > -1);
        },
        "Current page's URL params are removed before new search's filters are added": function() {
            window.history.pushState({}, null, '/ci/unitTest/javascript/framework/modules/widgetHelpers/tests/SearchFilter/p/1,2/c/67');

            var instance = new this.widget({attrs: {
                report_id: 2321,
                search_page: ''
            }}, 'abc', Y);
            instance.doASearch();
            Y.Assert.areSame('/ci/unitTest/javascript/framework/modules/widgetHelpers/tests/SearchFilter/kw/banana/search/1', this.url);
            Y.Assert.areSame('_self', this.target);
            Y.Assert.areSame('None specified', this.options);

            window.history.back();
        },
        'Report searches with pages specified should add /search/1': function() {
            var instance = new this.widget({attrs: {
                report_id: 2321,
                search_page: '/app/foo/bar'
            }}, 'abc', Y);
            instance.doASearch();
            Y.Assert.areSame('/app/foo/bar/kw/banana/search/1', this.url);
            Y.Assert.areSame('_self', this.target);
            Y.Assert.areSame('None specified', this.options);
        },
        'Report searches with pages specified, but not included in search event, should add /search/1': function() {
            var instance = new this.widget({attrs: {
                report_id: 2321,
                search_page: '/app/foo/bar'
            }}, 'abc', Y);
            instance.doASearchWithPage(true, false);
            Y.Assert.areSame('/app/foo/bar/kw/banana/page/1/search/1', this.url);
            Y.Assert.areSame('_self', this.target);
            Y.Assert.areSame('None specified', this.options);
        },
        'Source searches with pages specified should add /search/1': function() {
            var instance = new this.widget({attrs: {
                source_id: 'social',
                search_page: '/app/foo/bar'
            }}, 'abc', Y);
            instance.doASearch();
            Y.Assert.areSame('/ci/unitTest/javascript/framework/modules/widgetHelpers/tests/SearchFilter/kw/banana/search/1', this.url);
            Y.Assert.areSame('_self', this.target);
            Y.Assert.areSame('None specified', this.options);
        },
        'Report searches with pages specified should not add /search/1': function() {
            var instance = new this.widget({attrs: {
                report_id: 2321,
                search_page: '/app/foo/bar'
            }}, 'abc', Y);
            instance.doASearchWithPage(true, true);
            Y.Assert.areSame('/app/foo/bar/kw/banana/page/42', this.url);
            Y.Assert.areSame('_self', this.target);
            Y.Assert.areSame('None specified', this.options);
        },
        'Source searches with pages specified should not add /search/1': function() {
            var instance = new this.widget({attrs: {
                source_id: 'social',
                search_page: '/app/foo/bar'
            }}, 'abc', Y);
            instance.doASearchWithPage(false, true);
            Y.Assert.areSame('/ci/unitTest/javascript/framework/modules/widgetHelpers/tests/SearchFilter/kw/banana/page/37', this.url);
            Y.Assert.areSame('_self', this.target);
            Y.Assert.areSame('None specified', this.options);
        }
    }));
    return suite;
});

UnitTest.addSuite({
    type: UnitTest.Type.Framework
}, function(Y){
    var suite = new Y.Test.Suite("Basic Sanity");
    suite.add(new Y.Test.Case({
        name : "Test privates are private",
        testPrivateMembers: function() {
            UnitTest.recursiveMemberCheck(Y, RightNow.SearchFilter);
            UnitTest.recursiveMemberCheck(Y, RightNow.ResultsDisplay);
        }
    }));
    return suite;
});
UnitTest.run();
