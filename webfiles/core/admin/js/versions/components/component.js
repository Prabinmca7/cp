/**
 * Provides the base boilerplate "class" for each UI piece.
 */
YUI.add('Component', function(Y) {
    Y.Component = (function() {
        function eventListener() {
            // Event subscription
            Y.Object.each(this.events, function(val, key, items) {
                items = key.split(' ');
                Y.all(items.slice(1).join(' ')).on(items[0], this[val], this);
            }, this);
        }

        function historyListener() {
            var history = this.history,
            tabIsActive = function(tabID) {
                return (tabID && Y.Tabs.get('selection').get('panelNode').get('id') === tabID);
            };

            Y.Array.each(Y.Lang.isArray(history) ? history : [history], function(settings) {
                if(tabIsActive(settings.tab)) {
                    this[settings.handler](Y.Helpers.History.get(settings.key));
                }

                Y.Helpers.History.on('change', function(e) {
                    if (e.src === Y.HistoryHash.SRC_HASH && e.changed[settings.key]) {
                        this[settings.handler](e.changed[settings.key].newVal);
                    }
                }, this);
            }, this);
        }

        return function(properties) {
            function component() {
                Y.augment(this, Y.EventTarget);
                eventListener.call(this);

                this.init && this.init();

                this.history && historyListener.call(this);
            }
            Y.mix(component.prototype, properties);
            component.prototype.constructor = component;
            return component;
        };
    })();

}, null, {
    requires: ['node', 'event-base', 'event-custom', 'event-delegate', 'event-mouseenter', 'history', 'Helpers', 'Tabs']
});
