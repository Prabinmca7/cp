UnitTest.addSuite({
    type: UnitTest.Type.Admin,
    preloadFiles: [
        '/euf/core/admin/js/logs/dataTable.js',
        '/euf/core/admin/css/logs/logs.css'
    ],
    yuiModules: ['adminlogs-datatable']
}, function(Y) {
    Y.one(document.body).append('<div id="tableContainer"></div>');
    var Utils = {
        searchTemplate: '<form id="search" onsubmit="return false;">' +
            '<label for="searchTerm" class="screenreader">Search</label>' +
            '<input type="search" id="searchTerm" placeholder="Search Log"/>' +
            '<select>' +
            '<option value="fileName">File Name</option>' +
            '<option value="date">Date Created</option>' +
            '<option value="account">Account</option>' +
            '<option value="operation">Operation</option>' +
            '</select>' +
            '<button>Search</button>' +
            '</form>',
        getData: function() {
            return {
                columns: [
                    {key: 'fileName', label: 'File Name'},
                    {key: 'date', label: 'Date Created'},
                    {key: 'account', label: 'Account'},
                    {key: 'operation', label: 'Operation'}
                ],
                data: [
                    ['info12345.log', '1/2/2013 6:00', 'abc', 'copy'],
                    ['info12346.log', '1/3/2013 6:01', 'bcd', 'copy'],
                    ['info12347.log', '1/4/2013 6:02', 'cde', 'copy'],
                    ['info12348.log', '1/4/2013 6:03', 'def', 'copy'],
                    ['info12349.log', '1/4/2013 6:04', 'efg', 'move'],
                    ['info12350.log', '1/4/2013 6:05', 'fgh', 'move'],
                    ['info12351.log', '1/4/2013 6:06', 'ghi', 'move'],
                    ['info12352.log', '1/4/2013 6:07', 'hij', 'move'],
                    ['info12353.log', '1/4/2013 6:08', 'ijk', 'delete'],
                    ['info12354.log', '1/4/2013 6:09', 'jkl', 'delete'],
                    ['info12355.log', '1/4/2013 6:10', 'klm', 'delete'],
                    ['info12356.log', '1/4/2013 6:11', 'lmn', 'delete'],
                    ['info12357.log', '1/4/2013 6:12', 'mno', 'copy'],
                    ['info12358.log', '1/4/2013 6:13', 'nop', 'copy'],
                    ['info12359.log', '1/4/2013 6:14', 'opq', 'copy'],
                    ['info12360.log', '1/4/2013 6:15', 'pqr', 'copy'],
                    ['info12361.log', '1/4/2013 6:16', 'qrs', 'move'],
                    ['info12362.log', '1/4/2013 6:17', 'rst', 'move'],
                    ['info12363.log', '1/4/2013 6:18', 'stu', 'move'],
                    ['info12364.log', '1/4/2013 6:19', 'tuv', 'move'],
                    ['info12365.log', '1/4/2013 6:20', 'uvw', 'delete'],
                    ['info12366.log', '1/4/2013 6:21', 'vwx', 'delete'],
                    ['info12367.log', '1/4/2013 6:22', 'wxy', 'delete'],
                    ['info12368.log', '1/4/2013 6:23', 'xyz', 'delete']
                ],
                strings: {
                    summary: 'A summary',
                    asc: 'Ascending',
                    desc: 'Descending',
                    sortBy: 'Sort by whatever',
                    reverseSortBy: 'Reverse sort by whatever',
                    emptyMessage: 'no data'
                }
            }
        },
        getColumnData: function(dt, column, rowCount) {
            for (var i = 0, columnData = [], data, row; i < rowCount; i++) {
                if (row = dt.getRow(i)) {
                    columnData.push(dt.data.item(i).get(column));
                }
            }
            return columnData;
        },
        selector: '#tableContainer',
        tableDiv: Y.one('#tableContainer')
    }
    suite = new Y.Test.Suite({name: "dataTable tests"});

    suite.add(new Y.Test.Case({
        name: "General functionality tests",

        "Initialize should return an object containing expected datatable configurations": function() {
            var logData = Utils.getData(),
                dt = Y.AdminLogs.DataTable.initialize(logData);
            Y.Assert.isObject(dt);
            var configs = dt.configs;
            Y.Assert.areEqual(logData.columns, configs.columns);
            Y.Assert.areEqual(logData.data, configs.data);
            Y.Assert.areEqual(true, configs.sortable);
            Y.Assert.areEqual('y', configs.scrollable);
        },
        "Setting mapDataToColumns at time of initialization should result in data being an object with colummn names as keys": function() {
            var logData = Utils.getData(),
                dt = Y.AdminLogs.DataTable.initialize(logData, true);
            var expected = {fileName: "info12345.log", date: "1/2/2013 6:00", account: "abc"},
                actual = dt.configs.data[0];
            Y.Assert.areEqual(expected.fileName, actual.fileName);
            Y.Assert.areEqual(expected.date, actual.date);
            Y.Assert.areEqual(expected.account, actual.account);
        },
        "getTable should return a DataTable": function() {
            var logData = Utils.getData(),
                dt = Y.AdminLogs.DataTable.initialize(logData, true);
            var dataTable = dt.getTable();
            Y.Assert.isObject(dataTable);
            Y.Assert.areEqual('datatable', dataTable.name);
        },
        "render should render the DataTable": function() {
            Y.AdminLogs.DataTable.initialize(Utils.getData(), true).render(Utils.selector);
            Y.Assert.isNotNull(Y.one('.yui3-datatable'));
            Utils.tableDiv.set('innerHTML', '');
        },
        "Search should display all matching rows and clear results on blur": function() {
            Y.one(document.body).append('<div id="searchContainer"></div>');
            var searchSelector = '#searchContainer',
                searchNode = Y.one(searchSelector);

            searchNode.set('innerHTML', Utils.searchTemplate);
            var logData = Utils.getData();
            logData.searchForm = searchSelector;

            var dt = Y.AdminLogs.DataTable.initialize(logData, true),
                dataTable = dt.getTable(),
                totalRows = logData.data.length;

            dataTable.render(Utils.selector);

            // Initial rows
            Y.Assert.areEqual(totalRows, dataTable.data.size());

            // Search for 'copy' in 'operation' column
            searchNode.one('input').set('value', 'copy');
            searchNode.one('select').set('value', 'operation');
            searchNode.one('button').simulate('click');
            Y.Assert.areEqual(8, dataTable.data.size());

            // clearing the search input resets rows
            searchNode.one('input').set('value', '').simulate('blur');
            Y.Assert.areEqual(totalRows, dataTable.data.size());

            Utils.tableDiv.set('innerHTML', '');
            searchNode.remove(true);
        },
        "Search should display all matching rows and clear results without blur being explicitly called": function() {
            Y.one(document.body).append('<div id="searchContainer"></div>');
            var searchSelector = '#searchContainer',
                searchNode = Y.one(searchSelector);

            searchNode.set('innerHTML', Utils.searchTemplate);
            var logData = Utils.getData();
            logData.searchForm = searchSelector;

            var dt = Y.AdminLogs.DataTable.initialize(logData, true),
                dataTable = dt.getTable(),
                totalRows = logData.data.length;

            dataTable.render(Utils.selector);

            // Initial rows
            Y.Assert.areEqual(totalRows, dataTable.data.size());

            // Search for 'copy' in 'operation' column
            searchNode.one('input').set('value', 'copy');
            searchNode.one('select').set('value', 'operation');
            searchNode.one('button').simulate('click');
            Y.Assert.areEqual(8, dataTable.data.size());

            // clearing the search input resets rows, even if blur isn't called
            searchNode.one('input').set('value', '');
            searchNode.one('button').simulate('click');
            Y.Assert.areEqual(totalRows, dataTable.data.size());

            Utils.tableDiv.set('innerHTML', '');
            searchNode.remove(true);
        },
        "Clicking on a sortable column following a search should maintain the search filtering": function() {
            Y.one(document.body).append('<div id="searchContainer"></div>');
            var searchSelector = '#searchContainer',
                searchNode = Y.one(searchSelector);

            searchNode.set('innerHTML', Utils.searchTemplate);
            var logData = Utils.getData();
            logData.searchForm = searchSelector;

            var dt = Y.AdminLogs.DataTable.initialize(logData, true),
                dataTable = dt.getTable(),
                totalRows = logData.data.length,
                rowCountPostFilter = 8,
                sortColumn = 'date',
                firstItem;

            dataTable.render(Utils.selector);

            // Initial rows
            Y.Assert.areEqual(totalRows, dataTable.data.size());
            Y.Assert.isTrue(totalRows > rowCountPostFilter);

            // Do initial sort so we can verify the subsequent sort works.
            dataTable.set('sortBy', {sortColumn: 'asc'});
            firstItem = Utils.getColumnData(dataTable, sortColumn, rowCountPostFilter)[0];

            // Search for 'copy' in 'operation' column
            searchNode.one('input').set('value', 'copy');
            searchNode.one('select').set('value', 'operation');
            searchNode.one('button').simulate('click');
            Y.Assert.areEqual(rowCountPostFilter, dataTable.data.size());

            // Sort again following filter operation
            dataTable.set('sortBy', {sortColumn: 'desc'});
            Y.Assert.areNotEqual(firstItem, Utils.getColumnData(dataTable, sortColumn, rowCountPostFilter)[0]);

            // Verify rows are still filtered
            Y.Assert.areEqual(rowCountPostFilter, dataTable.data.size());

            Utils.tableDiv.set('innerHTML', '');
            searchNode.remove(true);
        }
    }));

    return suite;

}).run();
