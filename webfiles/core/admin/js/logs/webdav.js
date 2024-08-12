//= require admin/js/logs/dataTable.js
YUI().use('adminlogs-datatable', 'datatable', 'datatable-paginator', function(Y) {
    'use strict';

    var logData = window.logData,
        dateSort = function(val1, val2, desc) {
            var date1 = new Date(val1.get('date')),
                date2 = new Date(val2.get('date')),
                order = date1 > date2 ? 1 : -(date1 < date2);

            return desc ? -order : order;
        },
        getColumns = function(columns) {
            return Y.Array.map(columns, function(column, index) {
                if (column.key === 'date') {
                    column.sortFn = dateSort;
                }
                return column;
            });
        };

    Y.on('domready', function() {
        logData.columns = getColumns(logData.columns);
        var dt = Y.AdminLogs.DataTable.initialize(logData, true);
        dt.configs.sortBy = {date: 'desc'};
        dt.render('#webdavLogs');
    });
});
