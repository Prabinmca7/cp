//= require admin/js/explorer/util.js
//= require admin/js/explorer/queryHistory.js
//= require admin/js/explorer/dialogs.js
//= require admin/js/explorer/editor.js
//= require admin/js/explorer/tabView.js

YUI().use('connectexplorer-tabview', 'datatable', function(Y) {
    'use strict';

    Y.ConnectExplorer.TabView.initialize();
    Y.ConnectExplorer.ObjectInspector.initialize();

    Y.one('#execute').on('click', Y.ConnectExplorer.TabView.submitQuery, Y.ConnectExplorer.TabView);
    Y.one('#clear').on('click', Y.ConnectExplorer.TabView.clear, Y.ConnectExplorer.TabView);
    Y.one('#export').on('click', Y.ConnectExplorer.TabView.exportResults, Y.ConnectExplorer.TabView);
});
