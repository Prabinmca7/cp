//= require admin/js/deploy/deploy.js

/*global messages,VersionData*/
YUI().use('node', 'datatable', function(Y) {
    function constructVersionData(source, dest) {
        var versionData = [];
        for (var entity in source) {
            if (source.hasOwnProperty(entity)) {
                versionData.push({
                    entityName:         entity,
                    sourceVersion:      dest ? (source[entity] || messages.notApplicableLabel) : messages.notApplicableLabel,
                    destinationVersion: dest ? (dest[entity] || messages.notApplicableLabel) : source[entity]
                });
                if (dest) {
                    delete dest[entity];
                }
            }
        }

        return versionData;
    }

    function frameworkColumnNames(columnNames) {
        return [
            {
                key: "sourceVersion",
                sortable: true,
                label: columnNames[0]
            },
            {
                key: "destinationVersion",
                sortable: true,
                label: columnNames[1]
            }
        ];
    }

    function phpColumnNames(columnNames) {
        return [
            {
                key: "sourceVersion",
                sortable: true,
                label: columnNames[0]
            },
            {
                key: "destinationVersion",
                sortable: true,
                label: columnNames[1]
            }
        ];
    }

    function widgetColumnNames(columnNames) {
        return [
            {
                key:        "entityName",
                sortable:   true,
                label:      columnNames[0]
            },
            {
                key:        "sourceVersion",
                sortable:   true,
                label:      columnNames[1]
            },
            {
                key:        "destinationVersion",
                sortable:   true,
                label:      columnNames[2]
            }
        ];
    }

    function renderTable(versionInfo) {
        var node = Y.one(versionInfo.selector);

        if (!node || !versionInfo.source || !versionInfo.dest) return;

        new Y.DataTable({
            columns:    (versionInfo.columns.length === 2 && versionInfo.label === 'frameworkVersion') ? frameworkColumnNames(versionInfo.columns) : (versionInfo.columns.length === 2 && versionInfo.label === 'phpVersion') ? phpColumnNames(versionInfo.columns) : widgetColumnNames(versionInfo.columns),
            data:       constructVersionData(versionInfo.source, versionInfo.dest).concat(constructVersionData(versionInfo.dest)),
            summary:    versionInfo.summary
        }).render(node.setHTML(''));
    }

    Y.on("domready", function() {
        VersionData.framework.selector = '#frameworkVersionContent';
        renderTable(VersionData.framework);

        VersionData.php.selector = '#phpVersionContent';
        renderTable(VersionData.php);

        VersionData.widgets.selector = '#widgetVersionsContent';
        renderTable(VersionData.widgets);
    });
});
function versionActionChange(selectBox, rowIndex){
    VersionData.selection = selectBox.options[selectBox.selectedIndex].value;
}
