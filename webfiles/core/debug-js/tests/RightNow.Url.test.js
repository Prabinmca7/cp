UnitTest.addSuite({
    type: UnitTest.Type.Framework,
    jsFiles: ['/euf/core/debug-js/RightNow.Url.js', '/euf/core/debug-js/RightNow.Text.js'],
    namespaces: ['RightNow.Url']
}, function(Y){
    var combineProdCats = function(items) {
            return items.join(';');
        },
        rightnowUrlTests = new Y.Test.Suite("RightNow.Url");

    rightnowUrlTests.add(new Y.Test.Case(
    {
        name: "addParameterTests",
        setUp: function ()
        {
            this.Url = RightNow.Url;
        },

        testNewParameters: function ()
        {
            Y.Assert.areSame(this.Url.addParameter('http://ernie.ruby/app', 'test', 1), 'http://ernie.ruby/app/test/1');
            Y.Assert.areSame(this.Url.addParameter('http://ernie.ruby/app/', 'test', 1), 'http://ernie.ruby/app/test/1');
            Y.Assert.areSame(this.Url.addParameter('http://ernie.ruby/app/', 'test', 'value'), 'http://ernie.ruby/app/test/value');
        },

        testExistingParameters: function ()
        {
            Y.Assert.areSame(this.Url.addParameter('http://ernie.ruby/app/test/1', 'test', 1), 'http://ernie.ruby/app/test/1');
            Y.Assert.areSame(this.Url.addParameter('http://ernie.ruby/app/test/1', 'test', 'value'), 'http://ernie.ruby/app/test/value');
            Y.Assert.areSame(this.Url.addParameter('http://ernie.ruby/app/test/1/foo/bar', 'test', 1), 'http://ernie.ruby/app/test/1/foo/bar');
            Y.Assert.areSame(this.Url.addParameter('http://ernie.ruby/app/test/1/foo/bar/', 'test', 2), 'http://ernie.ruby/app/test/2/foo/bar/');
        },

        testPreserveExistingParameters: function ()
        {
            Y.Assert.areSame(this.Url.addParameter('http://ernie.ruby/app/test/1', 'test', 1, true), 'http://ernie.ruby/app/test/1');
            Y.Assert.areSame(this.Url.addParameter('http://ernie.ruby/app/test/1', 'test', 'value', true), 'http://ernie.ruby/app/test/1');
            Y.Assert.areSame(this.Url.addParameter('http://ernie.ruby/app/test/1/foo/bar', 'test', 1, true), 'http://ernie.ruby/app/test/1/foo/bar');
            Y.Assert.areSame(this.Url.addParameter('http://ernie.ruby/app/test/1/foo/bar/', 'test', 2, true), 'http://ernie.ruby/app/test/1/foo/bar/');
        },

        testNewParametersQueryString: function ()
        {
            Y.Assert.areSame(this.Url.addParameter('http://ernie.ruby/app?asdf', 'test', 1), 'http://ernie.ruby/app?asdf&test=1');
            Y.Assert.areSame(this.Url.addParameter('http://ernie.ruby/app/?asdf', 'test', 1), 'http://ernie.ruby/app/?asdf&test=1');
            Y.Assert.areSame(this.Url.addParameter('http://ernie.ruby/app/?asdf', 'test', 'value'), 'http://ernie.ruby/app/?asdf&test=value');
        },

        testExistingParametersQueryString: function ()
        {
            Y.Assert.areSame(this.Url.addParameter('http://ernie.ruby/app/?asdf&test=1', 'test', 1), 'http://ernie.ruby/app/?asdf&test=1');
            Y.Assert.areSame(this.Url.addParameter('http://ernie.ruby/app/?asdf&test=1', 'test', 'value'), 'http://ernie.ruby/app/?asdf&test=value');
            Y.Assert.areSame(this.Url.addParameter('http://ernie.ruby/app/?asdf&test=1&foo=bar', 'test', 1), 'http://ernie.ruby/app/?asdf&test=1&foo=bar');
            Y.Assert.areSame(this.Url.addParameter('http://ernie.ruby/app/?asdf&test=1&foo=bar', 'test', 2), 'http://ernie.ruby/app/?asdf&test=2&foo=bar');
        },

        testPreserveExistingParametersQueryString: function ()
        {
            Y.Assert.areSame(this.Url.addParameter('http://ernie.ruby/app/?asdf&test=1', 'test', 1, true), 'http://ernie.ruby/app/?asdf&test=1');
            Y.Assert.areSame(this.Url.addParameter('http://ernie.ruby/app/?asdf&test=1', 'test', 'value', true), 'http://ernie.ruby/app/?asdf&test=1');
            Y.Assert.areSame(this.Url.addParameter('http://ernie.ruby/app/?asdf&test=1&foo=bar', 'test', 1, true), 'http://ernie.ruby/app/?asdf&test=1&foo=bar');
            Y.Assert.areSame(this.Url.addParameter('http://ernie.ruby/app/?asdf&test=1&foo=bar', 'test', 2, true), 'http://ernie.ruby/app/?asdf&test=1&foo=bar');
        },

        testFragment: function ()
        {
            Y.Assert.areSame(this.Url.addParameter('http://ernie.ruby/app/home#foo=bar', 'test', 'value'), 'http://ernie.ruby/app/home/test/value#foo=bar');
            Y.Assert.areSame(this.Url.addParameter('http://ernie.ruby/app/home/test/value#foo=bar', 'test', 'value'), 'http://ernie.ruby/app/home/test/value#foo=bar');
            Y.Assert.areSame(this.Url.addParameter('http://ernie.ruby/app/home/test/foo/#foo=bar', 'test', 'value'), 'http://ernie.ruby/app/home/test/value/#foo=bar');
            Y.Assert.areSame(this.Url.addParameter('http://ernie.ruby/app/home/test/foo/#foo=bar', 'test', 'value', true), 'http://ernie.ruby/app/home/test/foo/#foo=bar');
        },

        testNullValue: function ()
        {
            Y.Assert.areSame(this.Url.addParameter('http://ernie.ruby/app/home', 'test', null), 'http://ernie.ruby/app/home');
            Y.Assert.areSame(this.Url.addParameter('http://ernie.ruby/app/home', 'test', undefined), 'http://ernie.ruby/app/home');
            Y.Assert.areSame(this.Url.addParameter('http://ernie.ruby/app/home/test/value', 'test', null), 'http://ernie.ruby/app/home/test/value');
        },

        testEmptyValue: function ()
        {
            Y.Assert.areSame(this.Url.addParameter('http://ernie.ruby/app/home/test/value', 'test', ''), 'http://ernie.ruby/app/home');
        },

        testSpecialCharactersInValue: function ()
        {
            Y.Assert.areSame(this.Url.addParameter('http://ernie.ruby/app', 'test', '&?/'), 'http://ernie.ruby/app/test/%26%3F%2F');
        }
    }));

    rightnowUrlTests.add(new Y.Test.Case(
    {
        name: "deleteParameterTests",
        setUp: function ()
        {
            this.Url = RightNow.Url;
        },

        testNormalDelete : function ()
        {
            Y.Assert.areSame(this.Url.deleteParameter('http://ernie.ruby/app/test/1', 'test'), 'http://ernie.ruby/app');
            Y.Assert.areSame(this.Url.deleteParameter('http://ernie.ruby/app/test1/value/test2/value', 'test1'), 'http://ernie.ruby/app/test2/value');
            Y.Assert.areSame(this.Url.deleteParameter('http://ernie.ruby/app/test/1', 'test2'), 'http://ernie.ruby/app/test/1');
            Y.Assert.areSame(this.Url.deleteParameter('http://ernie.ruby/app/tester/1', 'test'), 'http://ernie.ruby/app/tester/1');
            Y.Assert.areSame(this.Url.deleteParameter('http://ernie.ruby/app/test/1/test1', 'test'), 'http://ernie.ruby/app/test1');
        },

        testFragment : function ()
        {
            Y.Assert.areSame(this.Url.deleteParameter('http://ernie.ruby/app/home/test/1#hash', 'test'), 'http://ernie.ruby/app/home#hash');
            Y.Assert.areSame(this.Url.deleteParameter('http://ernie.ruby/app/home/test/1/#hash', 'test'), 'http://ernie.ruby/app/home/#hash');
            Y.Assert.areSame(this.Url.deleteParameter('http://ernie.ruby/app/home/test1/value/test2/value#hash', 'test1'), 'http://ernie.ruby/app/home/test2/value#hash');
        }
    }));

    rightnowUrlTests.add(new Y.Test.Case(
    {
        name: "buildUrlLinkStringTests",
        setUp: function ()
        {
            this.testUrl = "";
        },

        "If there's no format member on filters then no-op": function() {
            Y.Assert.areSame('', RightNow.Url.buildUrlLinkString({}));
        },

        testKeyword: function ()
        {
            var filters = {filters: {keyword: {filters: {data: {val: 'searchTerm'}}}}, format: {parmList: "kw"}},
                url = RightNow.Url.buildUrlLinkString(filters);
            Y.Assert.areSame("/kw/searchTerm", url);

            filters = {filters: {keyword: {filters: {data: {val: 'session'}}}}, format: {parmList: "kw"}};
            url = RightNow.Url.buildUrlLinkString(filters);
            Y.Assert.areSame(url, "/kw/%73%65%73%73%69%6f%6e");

            filters = {filters: {keyword: {filters: {data: {val: ""}}}}, format: {parmList: "kw"}};
            url = RightNow.Url.buildUrlLinkString(filters);
            Y.Assert.areSame("", url);
        },

        testSearchType: function ()
        {
            var filters = {filters: {searchType: {filters: {data: {val: 2}}}}, format: {parmList: "st"}},
                url = RightNow.Url.buildUrlLinkString(filters);
            Y.Assert.areSame("/st/2", url);
        },

        testPage: function ()
        {
            var filters = {filters: {page: 2}, format: {parmList: "page"}},
                url = RightNow.Url.buildUrlLinkString(filters);
            Y.Assert.areSame("/page/2", url);

            filters = {filters: {page: 1}, format: {parmList: "page"}};
            url = RightNow.Url.buildUrlLinkString(filters);
            Y.Assert.areSame("/page/1", url);

            filters = {filters: {page: null}, format: {parmList: "page"}};
            url = RightNow.Url.buildUrlLinkString(filters);
            Y.Assert.areSame("", url);

            filters = {filters: {page: 0}, format: {parmList: "page"}};
            url = RightNow.Url.buildUrlLinkString(filters);
            Y.Assert.areSame("", url);

            filters = {filters: {page: undefined}, format: {parmList: "page"}};
            url = RightNow.Url.buildUrlLinkString(filters);
            Y.Assert.areSame("", url);
        },

        testSortArgs: function ()
        {
            var filters = {filters: {sort_args: {filters: {data: {col_id: 3, sort_direction: 4}}}}, format: {parmList: "sort"}},
                url = RightNow.Url.buildUrlLinkString(filters);
            Y.Assert.areSame("/sort/3%2C4", url);

            filters = {filters: {sort_args: {filters: {data: {sort_direction: 4}}}}, format: {parmList: "sort"}};
            url = RightNow.Url.buildUrlLinkString(filters);
            Y.Assert.areSame("", url);

            filters = {filters: {sort_args: {filters: {data: {col_id: -1, sort_direction: 4}}}}, format: {parmList: "sort"}};
            url = RightNow.Url.buildUrlLinkString(filters);
            Y.Assert.areSame("", url);
        },

        testProduct: function ()
        {
            var filters = {filters: {p: {filters: {data: {0: "10"}}}}, format: {parmList: "p"}},
                url = RightNow.Url.buildUrlLinkString(filters);
            Y.Assert.areSame("/p/10", url);

            filters = {filters: {p: {filters: {data: {0: "30,50"}}}}, format: {parmList: "p"}};
            url = RightNow.Url.buildUrlLinkString(filters);
            Y.Assert.areSame("/p/50", url);

            filters = {filters: {p: {filters: {data: {0: "30,50", 1: "1,2"}}}}, format: {parmList: "p"}};
            url = RightNow.Url.buildUrlLinkString(filters);
            Y.Assert.areSame('/p/' + combineProdCats([50, 2]), url);

            filters = {filters: {p: {filters: {data: {0: '1', 1: '163'}}}}, format: {parmList: 'p'}};
            Y.Assert.areSame('/p/' + combineProdCats([1, 163]), RightNow.Url.buildUrlLinkString(filters));

            filters = {filters: {p: {filters: {data: {0: ""}}}}, format: {parmList: "p"}};
            url = RightNow.Url.buildUrlLinkString(filters);
            Y.Assert.areSame("", url);

            filters = {filters: {p: {filters: {data: {0: "1"}}}}, format: {parmList: "p"}};
            url = RightNow.Url.buildUrlLinkString(filters);
            Y.Assert.areSame("/p/1", url);

            filters = {filters: {p: {filters: {data: {0: "1,2"}}}}, format: {parmList: "p"}};
            url = RightNow.Url.buildUrlLinkString(filters);
            Y.Assert.areSame("/p/2", url);

            filters = {filters: {p: {filters: {data: {0: null}}}}, format: {parmList: "p"}};
            url = RightNow.Url.buildUrlLinkString(filters);
            Y.Assert.areSame("", url);
        },

        testCategory: function ()
        {
            var filters = {filters: {c: {filters: {data: {0: "10"}}}}, format: {parmList: "c"}},
                url = RightNow.Url.buildUrlLinkString(filters);
            Y.Assert.areSame("/c/10", url);

            filters = {filters: {c: {filters: {data: {0: "30,50"}}}}, format: {parmList: "c"}};
            url = RightNow.Url.buildUrlLinkString(filters);
            Y.Assert.areSame("/c/50", url);

            filters = {filters: {c: {filters: {data: {0: "30,50", 1: "1,2"}}}}, format: {parmList: "c"}};
            url = RightNow.Url.buildUrlLinkString(filters);
            Y.Assert.areSame('/c/' + combineProdCats([50, 2]), url);

            filters = {filters: {c: {filters: {data: {0: ""}}}}, format: {parmList: "c"}};
            url = RightNow.Url.buildUrlLinkString(filters);
            Y.Assert.areSame("", url);

            filters = {filters: {c: {filters: {data: {0: "1"}}}}, format: {parmList: "c"}};
            url = RightNow.Url.buildUrlLinkString(filters);
            Y.Assert.areSame("/c/1", url);

            filters = {filters: {c: {filters: {data: {0: "1,2"}}}}, format: {parmList: "c"}};
            url = RightNow.Url.buildUrlLinkString(filters);
            Y.Assert.areSame("/c/2", url);

            filters = {filters: {c: {filters: {data: {0: null}}}}, format: {parmList: "c"}};
            url = RightNow.Url.buildUrlLinkString(filters);
            Y.Assert.areSame("", url);
        },

        testOrg: function ()
        {
            var filters = {filters: {org: {filters: {data: {selected: 7}}}}, format: {parmList: "org"}},
                url = RightNow.Url.buildUrlLinkString(filters);
            Y.Assert.areSame("/org/7", url);

            filters = {filters: {org: {filters: {data: {selected: null}}}}, format: {parmList: "org"}};
            url = RightNow.Url.buildUrlLinkString(filters);
            Y.Assert.areSame("", url);
        },

        testCustomFilter: function ()
        {
            var filters = {filters: {customFilter: {filters: {data: {val: 'customFilterValue'}}}}, format: {parmList: "customFilter"}},
                url = RightNow.Url.buildUrlLinkString(filters);
            Y.Assert.areSame("/customFilter/customFilterValue", url);

            filters = {filters: {customFilter: {filters: {data: 'customFilterValue'}}}, format: {parmList: "customFilter"}};
            url = RightNow.Url.buildUrlLinkString(filters);
            Y.Assert.areSame("/customFilter/customFilterValue", url);
        }
    }));

    rightnowUrlTests.add(new Y.Test.Case(
    {
        name: "getUrlParametersFromListTests",
        setUp: function()
        {
            RightNow.Url.setParameterSegment(5);
        },

        testEmptyParameters: function()
        {
            Y.Assert.areSame("", RightNow.Url.getUrlParametersFromList(""));
            Y.Assert.areSame("", RightNow.Url.getUrlParametersFromList("foo"));
        },

        testNoExclusions: function()
        {
            RightNow.Url.setParameterSegment(1);
            Y.Assert.areSame('/ci/unitTest', RightNow.Url.getUrlParametersFromList("ci"));
            Y.Assert.areSame('/ci/unitTest/javascript/framework', RightNow.Url.getUrlParametersFromList("ci,javascript"));
            Y.Assert.areSame('/ci/unitTest/javascript/framework', RightNow.Url.getUrlParametersFromList("ci, javascript"));
            Y.Assert.areSame('/javascript/framework/ci/unitTest', RightNow.Url.getUrlParametersFromList("javascript,ci"));
        },

        testExclusions: function()
        {
            RightNow.Url.setParameterSegment(1);
            Y.Assert.areSame('/ci/unitTest', RightNow.Url.getUrlParametersFromList("ci", []));
            Y.Assert.areSame('', RightNow.Url.getUrlParametersFromList("ci", ['ci']));
            Y.Assert.areSame('/ci/unitTest', RightNow.Url.getUrlParametersFromList("ci", ['foo']));
            Y.Assert.areSame('/ci/unitTest', RightNow.Url.getUrlParametersFromList("ci, javascript", ['javascript']));
            Y.Assert.areSame('', RightNow.Url.getUrlParametersFromList("ci, javascript", ['javascript', 'ci']));
            Y.Assert.areSame('', RightNow.Url.getUrlParametersFromList("ci, javascript", ['ci', 'javascript']));
        }
    }));

    rightnowUrlTests.add(new Y.Test.Case(
    {
        name: "convertSearchFiltersToParmsTests",
        setUp: function ()
        {
            this.testUrl = "http://ernie.ruby.rightnowtech.com/app/home";
        },

        testKeyword: function ()
        {
            RightNow.Url.setSession("foo");
            var filters = {keyword: {filters: {data: 'searchTerm'}}},
                url = RightNow.Url.convertSearchFiltersToParms(this.testUrl, filters, 1);
            Y.Assert.areSame(this.testUrl + "/kw/searchTerm/sno/1", url);

            filters = {keyword: {filters: {data: ""}}};
            url = RightNow.Url.convertSearchFiltersToParms(this.testUrl, filters);
            Y.Assert.areSame(this.testUrl, url);
        },

        // @@@ QA 130523-000124
        testSearchType: function ()
        {
            var filters = {searchType: {filters: {fltr_id: 7, data: 7, changed: true}}},
                url = RightNow.Url.convertSearchFiltersToParms(this.testUrl, filters, 1);
            Y.Assert.areSame(this.testUrl + "/st/7/sno/1", url);

            var filters = {searchType: {filters: {fltr_id: 3, data: {val: 3, label: 'hey'}, changed: true}}},
                url = RightNow.Url.convertSearchFiltersToParms(this.testUrl, filters, 1);
            Y.Assert.areSame(this.testUrl + "/st/3/sno/1", url);

            var filters = {searchType: {filters: {changed: true}}},
                url = RightNow.Url.convertSearchFiltersToParms(this.testUrl, filters, 1);
            Y.Assert.areSame(this.testUrl + "/sno/1", url);
        },
        testPage: function ()
        {
            var filters = {page: 2},
                url = RightNow.Url.convertSearchFiltersToParms(this.testUrl, filters);
            Y.Assert.areSame(this.testUrl + "/page/2", url);

            filters = {page: 1};
            url = RightNow.Url.convertSearchFiltersToParms(this.testUrl, filters);
            Y.Assert.areSame(this.testUrl + "/page/1", url);

            filters = {page: null};
            url = RightNow.Url.convertSearchFiltersToParms(this.testUrl, filters);
            Y.Assert.areSame(this.testUrl, url);

            filters = {page: 0};
            url = RightNow.Url.convertSearchFiltersToParms(this.testUrl, filters);
            Y.Assert.areSame(this.testUrl, url);

            filters = {page: undefined};
            url = RightNow.Url.convertSearchFiltersToParms(this.testUrl, filters);
            Y.Assert.areSame(this.testUrl, url);
        },
        testSortArgs: function ()
        {
            var filters = {sort_args: {filters: {data: {col_id: 3, sort_direction: 4}}}},
                url = RightNow.Url.convertSearchFiltersToParms(this.testUrl, filters);
            Y.Assert.areSame(this.testUrl + "/sort/3%2C4", url);

            filters = {sort_args: {filters: {col_id: 3, sort_direction: 4}}};
            url = RightNow.Url.convertSearchFiltersToParms(this.testUrl, filters);
            Y.Assert.areSame(this.testUrl + "/sort/3%2C4", url);
        },
        testProduct: function ()
        {
            var filters = {p: {filters: {data: [["10"]]}}},
                url = RightNow.Url.convertSearchFiltersToParms(this.testUrl, filters, 1);
            Y.Assert.areSame(this.testUrl + "/p/10/sno/1", url);

            filters = {p: {filters: {data: [["30", "50"]]}}};
            url = RightNow.Url.convertSearchFiltersToParms(this.testUrl, filters, 1);
            Y.Assert.areSame(this.testUrl + "/p/50/sno/1", url);

            filters = {p: {filters: {data: [[""]]}}};
            url = RightNow.Url.convertSearchFiltersToParms(this.testUrl, filters, 1);
            Y.Assert.areSame(this.testUrl + "/sno/1", url);

            filters = {p: {filters: {data: ["1"]}}};
            url = RightNow.Url.convertSearchFiltersToParms(this.testUrl, filters, 1);
            Y.Assert.areSame(this.testUrl + "/p/1/sno/1", url);

            filters = {p: {filters: {data: ["1,2"]}}};
            url = RightNow.Url.convertSearchFiltersToParms(this.testUrl, filters, 1);
            Y.Assert.areSame(this.testUrl + "/p/2/sno/1", url);

            filters = {p: {filters: {data: ["1;163"]}}};
            url = RightNow.Url.convertSearchFiltersToParms(this.testUrl, filters, 1);
            Y.Assert.areSame(this.testUrl + '/p/' + combineProdCats([1, 163]) + '/sno/1', url);

            filters = {p: {filters: {data: [["1"],["163"]]}}};
            url = RightNow.Url.convertSearchFiltersToParms(this.testUrl, filters, 1);
            Y.Assert.areSame(this.testUrl + '/p/' + combineProdCats([1, 163]) + '/sno/1', url);
        },
        testCategory: function ()
        {
            var filters = {c: {filters: {data: [["10"]]}}},
                url = RightNow.Url.convertSearchFiltersToParms(this.testUrl, filters, 1);
            Y.Assert.areSame(this.testUrl + "/c/10/sno/1", url);

            filters = {c: {filters: {data: [["30", "50"]]}}};
            url = RightNow.Url.convertSearchFiltersToParms(this.testUrl, filters, 1);
            Y.Assert.areSame(this.testUrl + "/c/50/sno/1", url);

            filters = {c: {filters: {data: [[""]]}}};
            url = RightNow.Url.convertSearchFiltersToParms(this.testUrl, filters, 1);
            Y.Assert.areSame(this.testUrl + "/sno/1", url);

            filters = {c: {filters: {data: ["1"]}}};
            url = RightNow.Url.convertSearchFiltersToParms(this.testUrl, filters, 1);
            Y.Assert.areSame(this.testUrl + "/c/1/sno/1", url);

            filters = {c: {filters: {data: ["1,2"]}}};
            url = RightNow.Url.convertSearchFiltersToParms(this.testUrl, filters, 1);
            Y.Assert.areSame(this.testUrl + "/c/2/sno/1", url);
        },
        
        // @@@ QA 131031-000119 E2E ASSETS: CPv3 EU Pages - Results are not filtered correctly when searching by Product Catalog from Advanced Search Dialog
        testProductCatalog: function ()
        {
            var filters = {pc: {filters: {data: [["5"]]}}},
                url = RightNow.Url.convertSearchFiltersToParms(this.testUrl, filters, 1);
            Y.Assert.areSame(this.testUrl + "/pc/5/sno/1", url);
            
            filters = {pc: {filters: {data: [["10"]]}}},
            url = RightNow.Url.convertSearchFiltersToParms(this.testUrl, filters, 1);
            Y.Assert.areSame(this.testUrl + "/pc/10/sno/1", url);
            
            filters = {pc: {filters: {data: [["222004", "222008", "222013", "10"]]}}};
            url = RightNow.Url.convertSearchFiltersToParms(this.testUrl, filters, 1);
            Y.Assert.areSame(this.testUrl + "/pc/10/sno/1", url);

            filters = {asset_id: {filters: {data: [["5"]]}}};
            url = RightNow.Url.convertSearchFiltersToParms(this.testUrl, filters, 1);
            Y.Assert.areSame(this.testUrl + "/asset_id/5/sno/1", url);
        },
        testOrg: function ()
        {
            var filters = {org: {filters: {data: {selected: 7}}}},
                url = RightNow.Url.convertSearchFiltersToParms(this.testUrl, filters);
            Y.Assert.areSame(this.testUrl + "/org/7", url);

            filters = {org: {filters: {data: {selected: null}}}};
            url = RightNow.Url.convertSearchFiltersToParms(this.testUrl, filters);
            Y.Assert.areSame(this.testUrl, url);
        },

        testCustomFilter: function ()
        {
            var filters = {customFilter: {filters: {data: {val: 'customFilterValue'}}}},
                url = RightNow.Url.convertSearchFiltersToParms(this.testUrl, filters);
            Y.Assert.areSame(this.testUrl + "/customFilter/customFilterValue", url);

            filters = {customFilter: {filters: {data: 'customFilterValue'}}};
            url = RightNow.Url.convertSearchFiltersToParms(this.testUrl, filters);
            Y.Assert.areSame(this.testUrl + "/customFilter/customFilterValue", url);

            filters = {customFilter: {filters: {data: {sub: "obj"}}}};
            url = RightNow.Url.convertSearchFiltersToParms(this.testUrl, filters);
            Y.Assert.areSame(this.testUrl, url);
        }
    }));

    rightnowUrlTests.add(new Y.Test.Case({
        name: "convertToSegmentTests",
        testConvertToSegment: function() {
            var obj = {
                foo: "bar",
                bar: "baz",
                banana: "no",
                encode: ">FTW<",
                "no>": "yes"
            },
            result = RightNow.Url.convertToSegment(obj);
            Y.Assert.areSame("/foo/bar/bar/baz/banana/no/encode/%3EFTW%3C/no>/yes", result);
            result = RightNow.Url.convertToSegment({});
            Y.Assert.areSame("", result);
            result = RightNow.Url.convertToSegment("");
            Y.Assert.areSame("", result);
            result = RightNow.Url.convertToSegment(0);
            Y.Assert.areSame("", result);
            Object.prototype.yeah = "no";
            Object.prototype.modifyable = "sure";
            result = RightNow.Url.convertToSegment(obj);
            Y.Assert.areSame("/foo/bar/bar/baz/banana/no/encode/%3EFTW%3C/no>/yes", result);
            delete Object.prototype.yeah;
            delete Object.prototype.modifyable;
            obj = ["foo", "banana"];
            result = RightNow.Url.convertToSegment(obj);
            Y.Assert.areSame("/0/foo/1/banana", result);
            obj.no = "whatever";
            result = RightNow.Url.convertToSegment(obj);
            Y.Assert.areSame("/0/foo/1/banana/no/whatever", result);
        }
    }));

    rightnowUrlTests.add(new Y.Test.Case(
    {
        name: "convertToArrayTests",
        setUp: function()
        {
            RightNow.Url.setParameterSegment(1);
        },

        testDefaultSegment: function()
        {
            var result = RightNow.Url.convertToArray();
            Y.Assert.isArray(result);
            Y.Assert.areSame('unitTest', result.ci);
            Y.Assert.areSame('framework', result.javascript);
            Y.Assert.isUndefined(result['RightNow.Url']);
        },

        testPassedInSegment: function()
        {
            var result = RightNow.Url.convertToArray(0, '/foo/bar/banana/no');
            Y.Assert.areSame("banana", result.bar);
            result = RightNow.Url.convertToArray(1, '/foo/bar/banana/no');
            Y.Assert.areSame("bar", result.foo);
            Y.Assert.areSame("no", result.banana);
            result = RightNow.Url.convertToArray(2, '/foo/bar/banana/no');
            Y.Assert.areSame("banana", result.bar);
        },

        testModifiedSegment: function()
        {
            RightNow.Url.setParameterSegment(2);
            var result = RightNow.Url.convertToArray();
            Y.Assert.isArray(result);
            Y.Assert.areSame('javascript', result.unitTest);
            Y.Assert.areSame('tests', result.framework);
            Y.Assert.isUndefined(result.ci);
        },

        testParameterModification: function()
        {
            var result = RightNow.Url.convertToArray(3);
            Y.Assert.isArray(result);
            Y.Assert.areSame('framework', result.javascript);
            Y.Assert.isUndefined(result['RightNow.Url']);
            Y.Assert.isUndefined(result.ci);
        },

        testAnotherParameterModification: function()
        {
            var result = RightNow.Url.convertToArray(4);
            Y.Assert.isArray(result);
            Y.Assert.areSame('tests', result.framework);
            Y.Assert.isUndefined(result.ci);
            Y.Assert.isUndefined(result.unitTest);
            Y.Assert.isUndefined(result.javascript);
        }

    }));

    rightnowUrlTests.add(new Y.Test.Case(
    {
        name: "isSameUrlTests",
        testSameUrl: function ()
        {
            Y.Assert.isTrue(RightNow.Url.isSameUrl('/ci/unitTest/javascript/framework/tests/RightNow.Url'));
            Y.Assert.isTrue(RightNow.Url.isSameUrl('/ci/unitTest/javascript/framework/tests/RightNow.Url/'));
            Y.Assert.isTrue(RightNow.Url.isSameUrl('/ci/'));
            Y.Assert.isTrue(RightNow.Url.isSameUrl(''));
            Y.Assert.isTrue(RightNow.Url.isSameUrl('/'));
        },

        testDifferentUrl: function ()
        {
            Y.Assert.isFalse(RightNow.Url.isSameUrl('/ci/unitTest/javascript/framework/RightNow.Url/foo'));
            Y.Assert.isFalse(RightNow.Url.isSameUrl('/ci/unitTest/javascript/framework/RightNow.Url//'));
            Y.Assert.isFalse(RightNow.Url.isSameUrl('/cc/unitTest/javascript/framework/'));
        }
    }));

    rightnowUrlTests.add(new Y.Test.Case(
    {
        name: "isExternalUrlTests",
        testExternalUrl: function ()
        {
            Y.Assert.isTrue(RightNow.Url.isExternalUrl('http://google.com'));
            Y.Assert.isTrue(RightNow.Url.isExternalUrl('https://google.com'));
        },

        testInternalUrl: function ()
        {
            var host = window.location.host;
            Y.Assert.isFalse(RightNow.Url.isExternalUrl(host));
            Y.Assert.isFalse(RightNow.Url.isExternalUrl("http://" + host +"/app/answers/list"));
        }
    }));

    rightnowUrlTests.add(new Y.Test.Case(
    {
        name: "sessionAndParameterSegmentTests",
        testSession: function ()
        {
            RightNow.Url.setSession("SESSION");
            Y.Assert.areSame(RightNow.Url.getSession(), "SESSION");
        },

        testParameterSegment: function ()
        {
            RightNow.Url.setParameterSegment(10);
            Y.Assert.areSame(RightNow.Url.getParameterSegment(), 10);
        }
    }));

    var base = 'http://' + window.location.hostname;
    // Setup config access function since the transformLinks function depends on it
    RightNow.Interface.getConfig = function(configSlot){ var configs={'OE_WEB_SERVER': window.location.hostname, 'CP_HOME_URL': 'home'}; return configs[configSlot];};

    rightnowUrlTests.add(new Y.Test.Case({
        name: "transformLinks Tests",
        urls: [{
                href: "#bananas",
                session: base + window.location.pathname + "#bananas",
                transform: base + window.location.pathname + "#bananas"
            }, {
                href: "/app/home",
                session: base + "/app/home/session/p_sess",
                transform: base + "/app/home"
            }, {
                href: "/app/answers/list#details",
                session: base + "/app/answers/list/session/p_sess#details",
                transform: base + "/app/answers/list#details"
            }, {
                href: "/",
                session: base + "/",
                transform: base + "/"
            }, {
                href: "/app",
                session: base + "/app/home/session/p_sess",
                transform: base + "/app"
            }, {
                href: "/app/",
                session: base + "/app/home/session/p_sess",
                transform: base + "/app/"
            }, {
                href: "https://" + window.location.hostname + "/app/home",
                session: "https://" + window.location.hostname + "/app/home/session/p_sess",
                transform: "https://" + window.location.hostname + "/app/home"
            }, {
                href: "/euf/assets/themes/standard/images/layout/contactDiscs.png",
                session: base + "/euf/assets/themes/standard/images/layout/contactDiscs.png",
                transform: base + "/euf/assets/themes/standard/images/layout/contactDiscs.png"
            }, {
                href: "/dav/euf/assets",
                session: base + "/dav/euf/assets",
                transform: base + "/dav/euf/assets"
            }, {
                href: "/foo/bar/baz",
                session: base + "/foo/bar/baz",
                transform: base + "/foo/bar/baz"
            }, {
                href: "/ci/opensearch/banana",
                session: base + "/ci/opensearch/banana/session/p_sess",
                transform: base + "/ci/opensearch/banana"
            }, {
                href: "/ci/pta/logout",
                session: base + "/ci/pta/logout",
                transform: base + "/ci/pta/logout"
            }, {
                href: "/cc/foo/bar",
                session: base + "/cc/foo/bar/session/p_sess",
                transform: base + "/cc/foo/bar"
            }
        ],
        ignored: [{
                href: "/doc_view.php",
                session: base + "/doc_view.php",
                transform: base + "/doc_view.php"
            }, {
                href: "/doc_submit.php",
                session: base + "/doc_submit.php",
                transform: base + "/doc_submit.php"
            }, {
                href: "/doc_serve.php",
                session: base + "/doc_serve.php",
                transform: base + "/doc_serve.php"
            }, {
                href: "/verify_acct_login.php",
                session: base + "/verify_acct_login.php",
                transform: base + "/verify_acct_login.php"
            }, {
                href: "/ci/admin/overview",
                session: base + "/ci/admin/overview",
                transform: base + "/ci/admin/overview"
            }, {
                href: "/qautils.php",
                session: base + "/qautils.php",
                transform: base + "/qautils.php"
            }, {
                href: "http://www.google.com",
                session: "http://www.google.com/",
                transform: "http://www.google.com/"
            }, {
                href: "https://www.google.com",
                session: "https://www.google.com/",
                transform: "https://www.google.com/"
            }, {
                href: "",
                session: base + '/',
                transform: base + '/',
                ie: window.location.href.substr(0, window.location.href.lastIndexOf('/') + 1),
                oldGecko: window.location.href
            }, {
                href: "javascript:void(0);",
                session: "javascript:void(0);",
                transform: "javascript:void(0);"
            }, {
                href: "tel:4068675309",
                session: "tel:4068675309",
                transform: "tel:4068675309"
            }, {
                href: "mailto:jdoe@example.com",
                session: "mailto:jdoe@example.com",
                transform: "mailto:jdoe@example.com"
            }, {
                href: "ftp://some/network/server",
                session: "ftp://some/network/server",
                transform: "ftp://some/network/server"
            }
        ],

        buildLinks: function(list) {
            var parent = document.createElement('div');
            Y.Array.each(list, function(value, link) {
                link = document.createElement('a');
                link.href = value.href;
                parent.appendChild(link);
            });

            return parent;
        },

        verifyLinks: function(parent, toVerify, key) {
            Y.one(parent).all('a').each(function(element, index, expected) {
                expected = toVerify[index][key];
                if (toVerify[index].ie && Y.UA.ie) {
                    expected = toVerify[index].ie;
                }
                else if (toVerify[index].oldGecko && Y.UA.gecko && Y.UA.gecko < 4) {
                    expected = toVerify[index].oldGecko;
                }
               Y.Assert.areSame(expected, element.get('href'), 'for input: ' + (toVerify[index].href || '""'));
            });
        },

        "All links are transformed properly": function () {
            RightNow.Url.setSession('');

            var toVerify = this.urls.concat(this.ignored),
                parent = this.buildLinks(toVerify),
                baseElement = document.createElement('base');
            baseElement.href = base;

            Y.one(document.body).append(baseElement).append(parent);
            RightNow.Url.transformLinks(parent);

            this.verifyLinks(Y.one(parent), toVerify, 'transform');

            Y.one(baseElement).remove();
            Y.one(parent).remove();
        },

        "Session params are appended when there's a session present": function() {
            RightNow.Url.setSession("p_sess");

            var toVerify = this.urls,
                parent = this.buildLinks(toVerify),
                baseElement = document.createElement('base');
            baseElement.href = base;

            Y.one(document.body).append(baseElement).append(parent);
            RightNow.Url.transformLinks(parent);

            this.verifyLinks(Y.one(parent), toVerify, 'session');

            Y.one(baseElement).remove();
            Y.one(parent).remove();
        },

        "Session param isn't appended onto certain hrefs": function() {
            RightNow.Url.setSession("p_sess");

            var toVerify = this.ignored,
                parent = this.buildLinks(toVerify),
                baseElement = document.createElement('base');
            baseElement.href = base;

            Y.one(document.body).append(baseElement).append(parent);
            RightNow.Url.transformLinks(parent);

            this.verifyLinks(Y.one(parent), toVerify, 'session');

            Y.one(baseElement).remove();
            Y.one(parent).remove();
        }
    }));

    rightnowUrlTests.add(new Y.Test.Case(
    {
        name: "privateMembersHiddenTest",

        testPrivateMembers: function()
        {
            UnitTest.recursiveMemberCheck(Y, RightNow.Url);
        }
    }));

    return rightnowUrlTests;
});
UnitTest.run();
