UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'CombinedSearchResults_0'
}, function(Y, widget, baseSelector){
    var tests = new Y.Test.Suite({
        name: "standard/reports/CombinedSearchResults - All search sources"
    });

    tests.add(new Y.Test.Case({
        name: "Behavior when the widget is configured for all search sources",

        validateResponse: function() {
            Y.Assert.areSame(this.response.data.length, Y.all(baseSelector + ' .rn_Content a').size());
            Y.Assert.isFalse(Y.one(baseSelector + "_Loading").hasClass('rn_Loading'));
            this.validatedResponse = true;
            Y.Assert.areSame(widget.data.attrs.label_screen_reader_search_success_alert, Y.one(baseSelector + "_Alert").get("innerHTML"));
        },

        "KB results should display if a report search is triggered, but not a combined search": function() {
            var mockResult = this.response;
            // Test for the screen reader alert div text. It should be empty before search.
            Y.Assert.isTrue(Y.one(baseSelector + "_Alert").get("innerHTML") === "");

            RightNow.Ajax.makeRequest = function (url, data, options) {
                RightNow.Ajax.makeRequest.called++;
                RightNow.Ajax.makeRequest.calledWith = Array.prototype.slice.call(arguments);
                setTimeout(function () {
                    options.successHandler(mockResult, options.data, 0);
                }, 1);
                return { id: 0 };
            };

            RightNow.Ajax.makeRequest.called = 0;

            widget.searchSource(widget.data.attrs.report_id).on("response", this.validateResponse, this)
                .fire("search", new RightNow.Event.EventObject(null, {
                filters: {
                    searchName: "keyword",
                    data: "",
                    rnSearchType: "kw",
                    report_id: widget.data.attrs.report_id
                }
            }));

            this.wait(function() {
                Y.Assert.areSame(1, RightNow.Ajax.makeRequest.called);
                Y.Assert.areSame('/ci/ajaxRequest/getReportData', RightNow.Ajax.makeRequest.calledWith[0]);
                Y.assert(this.validatedResponse);
            }, 2);
        },

        response: {
            "data": [
                [
                    "<a href='//placesheen.com/52/52'>What We Done?</a>",
                    "",
                    "Forgive Me",
                    "03/24/2010",
                    52,
                    100,
                    21,
                    48,
                    "03/23/2010",
                    "",
                    1,
                    1,
                    0,
                    0
                ]
            ],
            "headers": [
                {
                    "heading": "Summary",
                    "col_id": 1,
                    "order": 0,
                    "col_definition": "answers.summary",
                    "visible": true
                },
                {
                    "heading": "New or Updated",
                    "col_id": 2,
                    "order": 1,
                    "col_definition": "if (date_diff(date_trunc(sysdate(), DAYS), date_trunc(answers.created, DAYS)) / 86400 < $new, msg_lookup(5064), if(date_diff(date_trunc(sysdate(), DAYS), date_trunc(answers.updated, DAYS)) / 86400 < $updated, msg_lookup(6861)))",
                    "visible": true
                },
                {
                    "heading": "Description",
                    "width": "12.9770994",
                    "data_type": 6,
                    "col_id": 3,
                    "order": 2,
                    "col_definition": "answers.solution",
                    "visible": true
                },
                {
                    "heading": "Date Updated",
                    "width": "27.7353687",
                    "data_type": 4,
                    "col_id": 4,
                    "order": 3,
                    "col_definition": "answers.updated",
                    "visible": true,
                    "col_alias": "updated"
                },
                {
                    "heading": "Answer ID",
                    "width": null,
                    "data_type": 3,
                    "col_id": 5,
                    "order": 4,
                    "col_definition": "answers.a_id",
                    "visible": false
                },
                {
                    "heading": "Weight",
                    "width": null,
                    "data_type": 3,
                    "col_id": 6,
                    "order": 5,
                    "col_definition": "answers.match_wt",
                    "visible": false
                },
                {
                    "heading": "Computed Score",
                    "width": null,
                    "data_type": 3,
                    "col_id": 7,
                    "order": 6,
                    "col_definition": "answers.solved",
                    "visible": false,
                    "col_alias": "score"
                }
            ],
            "per_page": 10,
            "total_pages": 2,
            "total_num": 19,
            "row_num": 1,
            "truncated": 0,
            "start_num": 1,
            "end_num": 10,
            "initial": 0,
            "search_type": 5,
            "search": 0,
            "report_id": 176,
            "search_term": "",
            "grouped": 0,
            "exceptions": [12, 13],
            "page": 1,
            "error": null
        }
    }));

    return tests;
}).run();
