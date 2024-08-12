//= require admin/js/logs/dataTable.js
YUI().use('adminlogs-datatable', 'datatable', 'datatable-paginator', function(Y) {
    'use strict';

    var maxTooltipLength = 500,
        logData = window.logData,
        mapData = function(data) {
            return Y.Array.map(data, function (log) {
                var comment = log.comment;
                if (comment !== null) {
                    var tooltip = (comment.length > maxTooltipLength) ? (comment.slice(0, maxTooltipLength) + '...') : comment;
                    comment = '<span class="ellipsis" title="' + tooltip + '">' + comment + '</span>';
                }
                return {
                    fileName:       "<a href='/ci/admin/logs/viewDeployLog/" + log.name + "'>" + log.name + "</a>",
                    creationTime:   log.time,
                    deployType:     log.deployType,
                    account:        log.byAccount,
                    comment:        comment
                };
            });
        },
        dateSort = function(val1, val2, desc) {
            var date1 = new Date(val1.get('creationTime')),
                date2 = new Date(val2.get('creationTime')),
                order = date1 > date2 ? 1 : -(date1 < date2);

            return desc ? -order : order;
        },
        getColumns = function(columns) {
            return Y.Array.map(columns, function (column) {
                if (column.key === 'creationTime') {
                    column.sortFn = dateSort;
                }
                return column;
            });
        };

    Y.on('domready', function() {
        logData.data = mapData(logData.data);
        logData.columns = getColumns(logData.columns);
        Y.AdminLogs.DataTable.initialize(logData).render('#deployLogs');
    });
});
