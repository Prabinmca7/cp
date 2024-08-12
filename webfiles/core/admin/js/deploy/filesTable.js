//= require admin/js/deploy/deploy.js

/*global FilesTable*/
YUI().use('node', 'datatable', 'datatable-mutable', function(Y) {
    var table = Y.one('#selectedFilesContent');
    if (typeof FilesTable === 'undefined' || typeof FilesTable.data === 'undefined' || !table) return;

    this.filesDataTable = new Y.DataTable({
        columns:    FilesTable.columns,
        data:       FilesTable.data,
        summary:    FilesTable.summary,
        scrollable: 'y'
    }).render(table.setHTML(''));
});
