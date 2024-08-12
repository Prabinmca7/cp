/**
 * Tab manager.
 */
YUI.add('Tabs', function(Y) {
    'use strict';

    Y.Tabs = (function() {
        var TAB_LIST = {WIDGET: 0, FRAMEWORK: 1, HISTORY: 2},
            topTabs = new Y.TabView({srcNode: '#mainTabs'});

        topTabs.render().selectChild(Y.Helpers.History.get('tab') || TAB_LIST.WIDGET);

        topTabs.after("render", function() {
            Y.all("#widgetVersions, #frameworkVersions, #history").removeClass("hide");
            Y.Lang.later(400, null, function() { Y.one("div.loading").remove(); });
        });
        topTabs.after("selectionChange", function(e) {
            Y.Helpers.History.addValue('tab', (e.newVal) ? e.newVal.get('index') : TAB_LIST.WIDGET);
        });

        Y.Helpers.History.on('change', function(e) {
            if (e.src !== Y.HistoryHash.SRC_HASH) return;

            if (e.changed.tab) {
                topTabs.selectChild(e.changed.tab.newVal);
            }
            else if (e.removed.tab) {
                topTabs.selectChild(TAB_LIST.WIDGET);
            }
        });

        return topTabs;
    })();

}, null, {
    requires: ['tabview', 'Helpers', 'Component']
});
