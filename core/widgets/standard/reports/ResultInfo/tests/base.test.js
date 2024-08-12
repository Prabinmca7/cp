 /*global UnitTest:false*/
UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'ResultInfo_0'
}, function(Y, widget, baseSelector){
    var resultInfoTests = new Y.Test.Suite({
        name: "standard/reports/ResultInfo",

        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.instanceID = 'ResultInfo_0';
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.widgetData = this.instance.data;
                },

                checkEventParameters: function(eventName, type, args) {
                    Y.Assert.areSame(eventName, type);
                    Y.Assert.isInstanceOf(RightNow.Event.EventObject, args);
                    Y.Assert.isObject(args.filters);
                    Y.Assert.areSame(this.instanceID, args.w_id);
                },
                checkNoResultsIsDisplayed: function() {
                    var selector = '#rn_' + this.instanceID + '_NoResults',
                        div = Y.one(selector);
                    Y.Assert.isNotNull(div, selector + ' does not exist');
                    Y.Assert.isFalse(div.hasClass('rn_Hidden'), selector + ' is hidden');
                    Y.Assert.areSame(div.get('innerHTML'),
                        this.widgetData.attrs.label_no_results + "<br><br>" + this.widgetData.attrs.label_no_results_suggestions);
                }
            };

            for(var item in this.items)
            {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    resultInfoTests.add(new Y.Test.Case(
    {
        name: "Event Handling and Operation",

        /**
         * Tests that the widget's reponse to correct data and an response event is to display the suggested word element
         */
        testReportResponseCase1: function() {
            this.initValues();
            var eventObject = new RightNow.Event.EventObject(this, {
                data: {
                    search_term: "test",
                    stopword: "stop",
                    not_dict: "some words",
                    total_num: 10,
                    topics: "phone",
                    truncated: true,
                    ss_data: ["ssTest1", "ssTest2", "ssTest3"],
                    report_id: this.widgetData.attrs.report_id
                },
                filters: {allFilters: {format: "something"}}
            });
            this.instance.searchSource().fire("response", eventObject);

            var suggestedDiv = Y.one("#rn_" + this.instanceID + "_Suggestion");
            if (suggestedDiv) {
                Y.Assert.isFalse(suggestedDiv.hasClass('rn_Hidden'));
                var children = suggestedDiv.all('li');
                Y.Assert.areSame(3, children.size());

                Y.Assert.areSame("ssTest1", children.item(0).get('childNodes').item(0).get('text'));
                Y.Assert.areSame("ssTest2", children.item(1).get('childNodes').item(0).get('text'));
                Y.Assert.areSame("ssTest3", children.item(2).get('childNodes').item(0).get('text'));
            }
            else {
                Y.Assert.fail("suggested div element not found");
            }
        },

        /**
         * Tests that the widget's response to certain data and the response Event is to diplay the spelling element
         */
        testReportResponseCase2: function() {
            this.initValues();

            var eventObject = new RightNow.Event.EventObject(this, {
                data: {
                    search_term: "tesst",
                    stopword: "stop",
                    not_dict: "some words",
                    total_num: 0,
                    truncated: false,
                    spelling: "tests",
                    report_id: this.widgetData.attrs.report_id
                },
                filters: {allFilters: {format: "something"}}
            });

            this.instance.searchSource().fire("response", eventObject);

            var spellingDiv = Y.one('#rn_' + this.instanceID + '_Spell');
            if (spellingDiv) {
                Y.Assert.isFalse(spellingDiv.hasClass('rn_Hidden'));
                Y.Assert.areSame(1, spellingDiv.get('children').size());
                Y.Assert.areSame("tests ", spellingDiv.get('children').item(0).get('text'));
            }
            else {
                Y.Assert.fail("spelling div element not found");
            }
        },

        /**
         * Tests the widget's response to display the no result element in response to the correct data and an response event
         */
        testReportResponseCase3: function() {
            this.initValues();

            var eventObject = new RightNow.Event.EventObject(this, {
                data: {
                    search_term: "test",
                    stop_word: "stop",
                    not_dict: "some words",
                    total_num: 0,
                    truncated: true,
                    report_id: this.widgetData.attrs.report_id
                },
                filters: {allFilters: {format: "something"}}
            });

            this.instance.searchSource().fire("response", eventObject);

            this.checkNoResultsIsDisplayed();
        },

        /**
         * Tests the widget's response is to display the results element in response to the correct data and an response event
         */
        testReportResponseCase4: function() {
            this.initValues();

            var eventObject = new RightNow.Event.EventObject(this, {
                data: {
                    search_term: "test",
                    stop_word: "stop",
                    not_dict: "some words",
                    total_num: 15,
                    truncated: false,
                    topics: "test",
                    report_id: this.widgetData.attrs.report_id,
                    start_num: 1,
                    end_num: 10
                },
                filters: {allFilters: {format: "something"}}
            });

            this.instance.searchSource().fire("response", eventObject);

            var resultsDiv = Y.one('#rn_' + this.instanceID + '_Results');
            if (resultsDiv) {
                Y.Assert.isFalse(resultsDiv.hasClass('rn_Hidden'));
            }
            else {
                Y.Assert.fail("Results Div not found");
            }
        },

        "Adding params to generated links": function() {
            var origAttr = this.instance.data.attrs.add_params_to_url;

            this.instance.data.attrs.add_params_to_url = 'p,kw';
            this.instance.searchSource().fire('response', new RightNow.Event.EventObject(null, {data: {
                ss_data: ['ufo'],
                spelling: ['saucer'],
                search_term: 'greeeeen',
                start_num: 1,
                total_num: 1,
                end_num: 1
            }, filters: {
                allFilters: {
                    p: {
                        filters: {
                            data: ['200']
                        }
                    },
                    keyword: { filters: { data: 'yeah'} }
                }
            }}));

            var links = Y.all('#rn_' + this.instanceID + ' a');
            // Suggested + Spelling + Search Term
            Y.Assert.areSame(3, links.size());
            links.each(function(a) {
                Y.Assert.isTrue(/\/p\/200\/kw\/yeah/.test(a.get('href')));
            });

            this.instance.data.attrs.add_params_to_url = origAttr;
        },

        "Topics as an empty list displays no results found": function() {
            this.initValues();
            this.instance.data.js.totalResults = 0;
            this.instance._updateSearchResults({
                userSearchedOn: 'An empty list',
                topics: []
            });
            this.checkNoResultsIsDisplayed();
        }

    }));

    resultInfoTests.add(new Y.Test.Case({
        // NOTE: this case creates a new widget instance without giving it its own DOM element, which
        // causes all sorts of weirdnesses when testing UI interaction. As such it should appear as the last test case.
        name: 'Test report error',
        // @@@ QA 130707-000022
        'An initial report error should display a warning dialog': function() {
            this.initValues();
            var error = "Value 'abc' must be an integer.";
            this.instance.data.js.error = error;
            widget = RightNow.Widgets.ResultInfo.extend();
            var instance = new widget(this.instance.data, this.instanceID, Y);
            Y.Assert.areSame(error, Y.one('#rn_Dialog_1_Message').get('innerHTML'));
        }
    }));

    return resultInfoTests;
});
UnitTest.run();
