YUI.add('adminlogs-datatable', function(Y) {
    'use strict';

    Y.namespace('AdminLogs');

    Y.AdminLogs.DataTable = function() {
        var rowsPerPage = 10,
            searchForm = null,
            dataTable = null,
            unfilteredData = [],
            paginatorClass = 'yui3-datatable-paginator-control',
            templates = {
                button: '<button class="' + paginatorClass + ' ' + paginatorClass +
                        '-{type}" data-type="{type}">{label}</button>',
                page:   '<form action="#" class="yui3-datatable-paginator-group">' +
                        '<label for="paginatorPage">{page}:</label>' +
                        '<input id="paginatorPage" type="text" value="{pageNum}">' +
                        '<button>{go}</button></form>',
                rows:   '<div class="' + paginatorClass + ' ' + paginatorClass + '-per-page">' +
                        '<label for="paginatorRows">{rows}:</label>' +
                        '<select id="paginatorRows">{options}</select>' +
                        '<span class="total">{total}: {count}</span></div>',
                option: '<option value="{value}" {selected}>{label}</option>'
            },
            setPaginatorStrings = function(strings, recordCount) {
                var ui = Y.DataTable.Templates.Paginator;
                ui.button = function(o) {
                    return Y.Lang.sub(templates.button, {type: o.type, label: strings[o.type]});
                };
                ui.gotoPage = function(o) {
                    return Y.Lang.sub(templates.page, {page: strings.page, pageNum: o.page, go: strings.go});
                };
                ui.perPage = function(o) {
                    var options = '';
                    Y.Array.each(o.options, function (option) {
                        options += Y.Lang.sub(templates.option, option);
                    });
                    return Y.Lang.sub(templates.rows, {rows: strings.rows, options: options, total: strings.total, count: recordCount});
                };
            },
            onSearch = function(e) {
                var query = searchForm.one('input').get('value'),
                    reset = (query === '');

                if (!reset && e.target.get('nodeName') === 'INPUT') {
                    // function was fired on blur from the input field
                    // Nothing to do until search submitted if a non-blank value is entered.
                    return;
                }

                var column = searchForm.one('select').get('value'),
                    recordCount = dataTable.data.size();

                if (recordCount > rowsPerPage) {
                    if (reset) {
                        dataTable.set('rowsPerPage', rowsPerPage);
                        dataTable.firstPage();
                    }
                    else {
                        // Disable pagination so we have the entire data set and subsequent
                        // pagination actions don't conflict with hidden rows.
                        dataTable.set('rowsPerPage', null);
                    }
                }

                filter(column, query);
            },
            filter = function(column, query) {
                var messageNode = Y.one('.yui3-datatable-message-content'),
                    content = null;

                if (messageNode) {
                    // Workaround for an issue where the 'No records found.' message remains post filter.
                    messageNode.set('innerHTML', '');
                }

                dataTable.set('data', unfilteredData);
                if (query !== '') {
                    dataTable.set('data', dataTable.data.filter({asList: true}, function (list) {
                        content = list.get(column);
                        return content !== null && content.toLowerCase().indexOf(query.toLowerCase()) !== -1;
                    }));
                }
            },
            mapData = function(data, columns) {
                return Y.Array.map(data, function (record) {
                    var records = {};
                    Y.Array.each(columns, function (column, index) {
                        records[column.key] = record[index];
                    });
                    return records;
                });
            };

        return {
            configs: {
                columns: [],
                data: [],
                scrollable: 'y',
                sortable: true
            },
            /**
             * Initializes the data table according to the log object.
             * @param {Object} log
             * @param {Boolean} mapDataToColumns If true, map data to columns.
             * @return {Object}
             */
            initialize: function(log, mapDataToColumns) {
                var strings = log.strings,
                    configs = this.configs,
                    recordCount = 0;
                configs.columns = log.columns;
                configs.summary = strings.summary;
                configs.strings = {
                    asc: strings.ascending,
                    desc: strings.descending,
                    sortBy: strings.sortBy,
                    reverseSortBy: strings.reverseSortBy,
                    emptyMessage: strings.empty
                };

                if (log.data && (recordCount = log.data.length)) {
                    configs.data = unfilteredData = mapDataToColumns ? mapData(log.data, log.columns) : log.data;
                    if (recordCount > rowsPerPage) {
                        // Add paginator configs
                        configs.rowsPerPage = rowsPerPage;
                        configs.pageSizes = [10, 50, 100, { label: strings.showAll, value: -1 }];
                        setPaginatorStrings(strings, recordCount);
                    }
                    if (log.searchForm && (searchForm = Y.one(log.searchForm))) {
                        searchForm.one('button').on('click', onSearch);
                        searchForm.one('input').on('blur', onSearch);
                    }
                }

                return this;
            },
            /**
             * Returns a new Y.DataTable instance based on this.configs
             * @return {Object}
             */
            getTable: function() {
                return dataTable = new Y.DataTable(this.configs);
            },
            /**
             * Renders the DataTable in the given selector
             * @param {String} selector
             * @return {Object}
             */
            render: function(selector) {
                return this.getTable().render(selector);
            }
        };
    }();
}, null, {
    requires: ['datatable', 'datatable-paginator']
});
