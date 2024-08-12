//= require admin/js/deploy/deploy.js
var configurationsDataTable;
/*global PageSet,cellFormatter*/
YUI().use('node', 'datatable', 'datatable-mutable', function(Y) {
    var rowCounter = 0,
        thisId = null,
        lastId = null;

    /*Caution: do not change or remove the following variable; it's referenced in deploy.php...*/
    cellFormatter = function(o) {
        var className = '';
        thisId = o.data.id;
        if (thisId !== lastId) {
            rowCounter++;
        }
        lastId = thisId;
        switch(rowCounter) {
            case 1:
                className = 'even1';
                break;
            case 2:
                className = 'even2';
                break;
            case 3:
                className = 'odd1';
                break;
            default:
                className = 'odd2';
                rowCounter = 0;
                break;
        }
        if (className) {
            o.className += className;
        }
        return o.value || '';
    };

    Y.on("domready", function() {
        var table = Y.one("#pageSetContent");
        if (typeof PageSet === 'undefined' || !table) return;

        configurationsDataTable = new Y.DataTable({
            columns:    PageSet.columns,
            data:       PageSet.pagesets,
            summary:    PageSet.summary
        }).render(table.setHTML(''));
    });
});
