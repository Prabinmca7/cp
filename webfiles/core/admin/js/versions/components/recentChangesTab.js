/* global messages */

/**
 * Lists recent version changes.
 */
YUI.add('RecentChangesTab', function(Y) {
    'use strict';

    var HISTORY_TAB = 2;

    var RecentChangesTab = Y.Component({
        init: function () {
            this.haveRetrievedHistory = false;

            if(parseInt(Y.Helpers.History.get('tab'), 10) === HISTORY_TAB){
                this.retrieveHistoryEntries();
            }

            Y.Tabs.on('selectionChange', function (e) {
                if(e.newVal && e.newVal.get('index') === HISTORY_TAB){
                    this.retrieveHistoryEntries();
                }
            }, this);

            Y.on('widgets:change', function () {
                // After retrieving the recent changes, the content
                // can be cached, until a widget changes versions.
                this.haveRetrievedHistory = true;
            }, this);
        },

        /**
         * Due to the absolute positioning of the management UI,
         * when <spacebar> is hit, the entire page doesn't scroll like
         * users would expect. In order to scroll the recent changes list
         * via the keyboard, it must be focused. So focus on it.
         */
        focusOnTab: function () {
            Y.one('#historyPanel').set('tabIndex', 0).focus();
        },

        /**
         * Even when subscribing as the `after` callback of the
         * "selectionChange" event on tabs, the tab's content is still
         * not yet visible when the event fires. When we're trying to
         * focus on the tab's content, this is unfortunate. So we can
         * wait until the next tick to focus on the tab's contents.
         * @param  {number=} when When to focus; if omitted, the focusing
         *                        happens as soon as the browser devotes
         *                        a next tick in the event loop
         */
        focusOnTabSoon: function (when) {
            Y.later(when || 0, null, this.focusOnTab);
        },

        /**
         * If the change history hasn't already be retrieved,
         * retrieves the change history.
         */
        retrieveHistoryEntries: function () {
            if (this.haveRetrievedHistory) {
                return this.focusOnTabSoon();
            }

            Y.Helpers.ajax('/ci/admin/versions/getChangeHistory', {
                callback: this.historyEntriesRetrieved,
                context: this
            });
        },

        /**
         * Ajax callback for retrieving change history.
         * @param  {array} data changes
         */
        historyEntriesRetrieved: function (data) {
            this.renderHistoryEntries(data);

            this.haveRetrievedHistory = true;

            this.focusOnTab();
        },

        /**
         * Renders the changes inside the tab content.
         * @param  {array} data recent changes; where ea. item:
         *                      {
         *                          newVersion: optional string,
         *                          previous: optional string,
         *                          user: string,
         *                          type: widget path string
         *                          time: date + time string
         *                      }
         */
        renderHistoryEntries: function(data) {
            var logEntries = Y.Array.map(data, function(logEntry) {
                var details = (logEntry.previous && logEntry.newVersion) ? Y.Lang.sub(RecentChangesTab.templates.details, logEntry) : '';

                return Y.Lang.sub(RecentChangesTab.templates.entry, Y.merge(logEntry, { details: details }));
            }).join('');

            Y.one('#historyPanel').set('innerHTML', logEntries || RecentChangesTab.templates.noChanges);
        }
    });

    RecentChangesTab.templates = {
        noChanges: "<div class='none'>" + messages.noRecentChanges + "</div>",
        entry: "<div class='log'><span class='subject'>{type}</span><div class='date'>{time}</div><div class='details'>{details}<span class='who'>{user}</span></div></div>",
        details: "<div class='what'>{previous} &#x2192; {newVersion}</div>"
    };

    Y.RecentChangesTab = new RecentChangesTab();

}, null, {
    requires: ['Tabs', 'Helpers', 'Component']
});
