UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'Grid_0',
}, function(Y, widget, baseSelector){
    var gridTests = new Y.Test.Suite({
        name: 'standard/reports/Grid',

        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.instanceID = 'Grid_0';
                    for (var i=0; i < 5; i++) {
                        if (this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID)) {
                            break;
                        }
                    }

                    this.widgetData = this.instance.data;
                    this.baseSelector = '#rn_' + this.instanceID;
                    this.loadingDiv = this.baseSelector + '_Loading';
                    this.reportID = this.widgetData.attrs.report_id;
                }
            };

            for(var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    gridTests.add(new Y.Test.Case({
        name: 'Event Handling and Operation',
        'Initial table load should accurately reflect the original data': function() {
            this.initValues();
            this.verifyData(this.widgetData.js.headers, []);
        },


        'Sorting should work on initial page load and when headers are clicked': function() {
            this.initValues();

            if (!this.widgetData.attrs.headers) return;

            var table = Y.one(this.baseSelector + '_Content table');

            if (this.widgetData.js.rowNumber) {
                Y.Assert.isFalse(table.one('th').hasClass('yui3-datatable-sortable'));
            }

            if (this.widgetData.js.columnID && this.widgetData.js.sortDirection) {
                // Test initial page-load sort
                this.verifySortingClasses(this.widgetData.js.columnID, this.widgetData.js.sortDirection);
            }

            // Test event firing on header click
            var eventData;

            this.instance.searchSource()
                .on('send', function() { return false; })
                .on('search', function(evt, args) {
                        eventData = args[0].filters.data;
                }, this);

            table.all('th').item(1).simulate('click');
            Y.Assert.areSame(1, eventData.col_id);
            Y.Assert.areSame(2, eventData.sort_direction);

            // Test response from server
            var response = this.getData().actual;

            this.instance.searchSource()
                .fire('response', new RightNow.Event.EventObject(null, {
                    data: {
                        report_id: this.reportID,
                        start_num: 1,
                        row_num: 1,
                        total_num: response.length,
                        per_page: response.length,
                        headers: this.getHeaders(),
                        data: response
                      },
                    filters:   {
                        report_id: this.reportID,
                        allFilters: {
                            sort_args: {
                                filters: {
                                    data: {
                                        col_id: 5,
                                        sort_direction: 1
                                    }
                                }
                            }
                        },
                        format: this.widgetData.js.format
                   }
                }));

            this.verifySortingClasses(4, 1);
        },

        'Loading indicator should be present when a search is in progress': function() {
            this.initValues();
            this.instance.searchSource().on('send', function() { return false; });
            this.instance.searchSource().fire('search', new RightNow.Event.EventObject(this, {filters: {report_id: this.reportID}}));
            Y.Assert.isTrue(Y.one(this.loadingDiv).hasClass('rn_Loading'));
            Y.Assert.areSame('true', document.body.getAttribute('aria-busy'), 'aria not busy');
        },

        'Grid should reflect new data following a report change': function() {
            this.initValues();

            var data = this.getData(),
                headers = this.getHeaders();

            this.instance.searchSource().fire('response', new RightNow.Event.EventObject(null, {
                data: {
                    report_id: this.reportID,
                    start_num: 1,
                    row_num: this.widgetData.js.rowNumber,
                    total_num: data.actual.length,
                    per_page: data.actual.length,
                    headers: headers,
                    data: data.actual
                },
                filters:   {
                    report_id: this.reportID,
                    allFilters: this.widgetData.js.filters,
                    format: this.widgetData.js.format
                }
            }));

            Y.Assert.areSame('false', document.body.getAttribute('aria-busy'), 'aria busy');

            this.verifyData(headers, data.expected);
        },

        'Grid should support data set not containing per_page rows': function() {
            this.initValues();

            var data = this.getData(),
                headers = this.getHeaders();

            this.instance.searchSource().fire('response', new RightNow.Event.EventObject(null, {
                data: {
                    report_id: this.reportID,
                    start_num: 1,
                    row_num: this.widgetData.js.rowNumber,
                    total_num: data.actual.length,
                    per_page: data.actual.length,
                    headers: headers,
                    data: data.actual.slice(0, 1)
                },
                filters:   {
                    report_id: this.reportID,
                    allFilters: this.widgetData.js.filters,
                    format: this.widgetData.js.format
                }
            }));

            Y.Assert.areSame('false', document.body.getAttribute('aria-busy'), 'aria busy');

            this.verifyData(headers, data.expected.slice(0, 1));
        },

        'A dialog should display following a response containing an error': function() {
            this.initValues();

            var data = this.getData(),
                headers = this.getHeaders(),
                errorMessage = "Value 'test' must be an integer";

            this.instance.searchSource().fire('response', new RightNow.Event.EventObject(null, {
                data: {
                    error: errorMessage,
                    report_id: this.reportID,
                    start_num: 1,
                    row_num: this.widgetData.js.rowNumber,
                    total_num: data.actual.length,
                    per_page: data.actual.length,
                    headers: headers,
                    data: data.actual
                  },
                filters:   {
                    report_id: this.reportID,
                    allFilters: this.widgetData.js.filters,
                    format: this.widgetData.js.format
               }
            }));

            Y.Assert.areSame(errorMessage, Y.one('#rnDialog1 #rn_Dialog_1_Message').get('innerHTML'));
        },

        'Verify aria label when there is a record': function() {
            this.initValues();

            var data = this.getData(),
                headers = this.getHeaders();

            this.instance.searchSource().fire('response', new RightNow.Event.EventObject(null, {
                data: {
                    report_id: this.reportID,
                    start_num: 1,
                    row_num: this.widgetData.js.rowNumber,
                    total_num: data.actual.length,
                    per_page: data.actual.length,
                    headers: headers,
                    data: data.actual
                },
                filters:   {
                    report_id: this.reportID,
                    allFilters: this.widgetData.js.filters,
                    format: this.widgetData.js.format
                }
            }));

            Y.Assert.areSame('Your search is complete', Y.one(this.baseSelector + '_Alert').get('innerHTML'));
        },

        'Verify aria label when there is no record': function() {
            this.initValues();
            this.instance.searchSource().fire('response', new RightNow.Event.EventObject(null, {
                data: {
                    report_id: 01010,
                    headers: [],
                    data: []
                },
                filters:   {
                    report_id: 01010,
                    allFilters: []
                }
            }));
            Y.Assert.areSame('Your search returned no results', Y.one(this.baseSelector + '_Alert').get('innerHTML'));
        },

        verifyData: function(headers, data) {
            var tableSelector = this.baseSelector + '_Content table',
                headerRows = Y.all(tableSelector + ' thead tr'),
                rows = Y.all(tableSelector + '.yui3-datatable-data tr'),
                index = 0,
                row, content, header, i;

            Y.Assert.isFalse(Y.one(this.loadingDiv).hasClass('rn_Loading'), 'rn_Loading class exists');
            Y.Assert.isObject(headerRows, 'table header not present');
            Y.Assert.isObject(rows, 'table body not present');

            headerRows.each(function (row) {
                row.all('th').each(function (th, i) {
                    if (this.widgetData.js.rowNumber && i === 0) {
                        Y.Assert.areSame(th.get('text'), this.widgetData.attrs.label_row_number);
                    }
                    else {
                        header = this.getHeaderByColumnID(headers, th.getAttribute('data-yui3-col-id').substring(1));
                        Y.Assert.isTrue(th.get('text').indexOf(header.heading) === 0);
                        index++;
                    }
                }, this);
            }, this);

            if (data.length) {
                rows.each(function (row, r) {
                    index = 0;
                    row.all('td').each(function (td, d) {
                        td = td.one('.yui3-datatable-liner') || td;
                        content = td.getHTML();

                        if (this.widgetData.js.rowNumber && d === 0) {
                            Y.Assert.areEqual(content, r+1);
                        }
                        else {
                            if (typeof data[r][index] === 'object') {
                                var tag = data[r][index],
                                    matched = td.one(tag.type);
                                Y.Assert.isNotNull(matched);
                                Y.Assert.areSame(tag.href, matched.getAttribute('href'));
                                Y.Assert.areSame(tag.label, matched.getHTML());
                            }
                            else {
                                if (data[r][index] === '' && this.widgetData.attrs.headers) {
                                    data[r][index] = '&nbsp;';
                                }
                                Y.Assert.areSame(content, data[r][index]);
                            }
                            index++;
                        }
                    }, this);
                }, this);
            }
        },

        verifySortingClasses: function(colIndex, dir) {
            var table = Y.one(this.baseSelector + '_Content table');
            var i = 0, assert;
            table.all('th').each(function(e) {
                assert = (i === colIndex) ? 'isTrue' : 'isFalse';
                Y.Assert[assert](e.hasClass('yui3-datatable-sorted'), 'Column ' + i  + ' has the wrong class');

                //Check the accessibility label
                if(i === colIndex) {
                    if(dir === 2) {
                        Y.Assert.isTrue(e.get('text').indexOf('Descending') > 0);
                    }
                    else {
                        Y.Assert.isTrue(e.get('text').indexOf('Ascending') > 0);
                    }
                }
                i++;
            });
            table.all('tr').each(function(row) {
                i = 0;
                row.all('tr').each(function(cell) {
                    assert = (i === colIndex) ? 'isTrue' : 'isFalse';
                    Y.Assert[assert](cell.hasClass('yui3-datatable-sorted'), 'Cell ' + i + ' with content: ' + cell.getHTML() + ' is wrong');
                    i++;
                });
            });

            var header = table.all('th').item(colIndex);
            if ( dir === 2) {
                assert = (dir === 2) ? 'isTrue' : 'isFalse';
                Y.Assert[assert]((header).hasClass('yui3-datatable-sorted-desc'));
            }
        },

        getData: function() {
            return {
                expected: [
                    [{type: 'a', href: "url1", label: 'iPhone Troubleshooting'}, '', 'Solve your iPhone issues', '3/16/2012'],
                    [{type: 'a', 'href': "url2", label: 'Droid Troubleshooting'}, '',  'Solve your Droid issues', '3/24/2012']
                ],
                actual: [
                    ['<a href="url1">iPhone Troubleshooting</a>', 'hidden', '', 'Solve your iPhone issues', '3/16/2012'],
                    ['<a href="url2">Droid Troubleshooting</a>', 'hidden', '',  'Solve your Droid issues', '3/24/2012']
                ]
            };
        },

        getHeaderByColumnID: function(headers, columnID) {
            for (var i = 0; i < headers.length; i++) {
                if (headers[i].col_id == columnID) {
                    return headers[i];
                }
            }
        },

        getHeaders: function() {
            return [
                {
                    heading: 'Summary',
                    width: 5,
                    data_type: 5,
                    col_id: 1,
                    order: 0,
                    col_definition: 'incidents.subject',
                    visible: true,
                    url_info: '//scott-git.ruby.rightnowtech.com/app/account/questions/detail/i_id/&lt;5&gt;'
                },
                {
                    heading: 'Some Hidden Column',
                    width: 5,
                    col_id: 2,
                    visible: false
                },
                {
                    heading: 'New or Updated',
                    width: 5,
                    col_id: 3,
                    visible: true
                },
                {
                    heading: 'Description',
                    width: 5,
                    data_type: 5,
                    col_id: 4,
                    order: 1,
                    visible: true
                },
                {
                    heading: 'Date Updated',
                    width: 5,
                    data_type: 5,
                    col_id: 5,
                    visible: true
                },
                {
                    heading: 'Interface',
                    width: null,
                    data_type: 1,
                    col_id: 6,
                    order: 5,
                    col_definition: 'incidents.interface_id',
                    visible: false
                },
                {
                    heading: 'Weight',
                    width: null,
                    data_type: 3,
                    col_id: 7,
                    order: 6,
                    col_definition: 'incidents.match_wt',
                    visible: false
                }
            ];
        }

    }));

    return gridTests;
});
UnitTest.run();