//= require admin/js/logs/dataTable.js
YUI().use('adminlogs-datatable', 'datatable', 'datatable-paginator', 'io', 'json', 'FormToken', function(Y) {
    'use strict';

    var logData = window.logData,
        strings = logData.strings,
        formatDeleteLink = function() {
            return "<a class='delete' href='javascript:void(0);'><i class='fa fa-times-circle-o fa fa-large'><span class='screenreader'>" + strings.remove + "</span></i></a>";
        },
        formatDate = function(o) {
            return "<a href='/ci/admin/logs/viewDebugLog/" + o.data.name + "'>" + o.data.name + "</a>";
        },
        dateSort = function(val1, val2, desc) {
            var date1 = val1.get('epoch'),
                date2 = val2.get('epoch'),
                order = date1 > date2 ? 1 : -(date1 < date2);
            return desc ? -order : order;
        },
        getColumns = function(columns) {
            return Y.Array.map(columns, function (column) {
                if (column.key === 'name') {
                    column.formatter = formatDate;
                }
                else if (column.key === 'date') {
                    column.sortFn = dateSort;
                }
                else if (column.key === 'delete') {
                    column.formatter = formatDeleteLink;
                }
                return column;
            });
        },
        deleteFailureHandler = function() {
            Y.one('#logControls').setHTML(strings.error);
        },
        deleteLog = function(logName, callback) {
            Y.FormToken.makeAjaxWithToken('/ci/admin/logs/deleteDebugLog', {
                method: 'POST',
                data: 'logName=' + logName,
                on: {
                    success: function(id, response) {
                        if (response.responseText) {
                            callback(Y.JSON.parse(response.responseText));
                        }
                        else {
                            deleteFailureHandler();
                        }
                    },
                    failure: deleteFailureHandler
                }
            }, this);
        },
        onDeleteLogClick = function(e) {
            var target = e.currentTarget,
                icon = target.one('i'),
                logName = target.ancestor('td').previous('.yui3-datatable-col-name').one('a').getHTML().replace(/<a[^>]+>/, '');

            icon.one('.screenreader').setHTML(strings.loading);
            target.replace(icon.set('className', 'fa fa-spinner fa fa-spin fa-lg'));

            deleteLog(logName, function(deleteResult) {
                if (deleteResult.result) {
                    icon.ancestor('tr').remove();
                }
            });
        };

    Y.on('domready', function() {
        logData.columns = getColumns(logData.columns);
        var dt = Y.AdminLogs.DataTable.initialize(logData);
        dt.configs.sortable = 'n';
        dt.configs.sortBy = {date: 'desc'};
        dt.render('#debugLogs').delegate('click', onDeleteLogClick, 'a.delete');
    });
});
