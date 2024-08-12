/* global messages */

/**
 * Provides common utils and helpers needed in
 * a number of components.
 * - ajax
 * - panel
 * - scrollListItemIntoView
 * - panelHandler
 * - toggle
 */
YUI.add('Helpers', function(Y) {
    'use strict';

    Y.Helpers = {};

    Y.Helpers.History = new Y.HistoryHash();

    Y.Helpers.ajax = function (url, options) {
        var ajaxMethod = (!options.method || options.method === 'GET') ? Y.io : Y.FormToken.makeAjaxWithToken;
        ajaxMethod(url, {
            method: options.method || 'GET',
            data: options.data || undefined,
            on: {
                success: function(id, resp) {
                    options.callback.call(options.context,
                        (options.raw) ? resp.responseText : Y.JSON.parse(resp.responseText)
                    );
                }
            }
        });
    };

    Y.Helpers.panel = function (options, destroyOnClose) {
        if (typeof destroyOnClose === "undefined") {
            destroyOnClose = true;
        }
        Y.Helpers.panel.defaultHandler || (Y.Helpers.panel.defaultHandler = function(e, destroy) {
            e.halt();
            this.hide();
            destroy && this.destroy();
        });
        Y.Helpers.panel.defaultOpts || (Y.Helpers.panel.defaultOpts = {
            centered: true,
            constrain: true,
            modal: true,
            render: true,
            visible: false,
            width: '300px',
            zIndex: 10000
        });
        var defaultButtons = [{
            value: messages.close,
            section: Y.WidgetStdMod.FOOTER,
            action: function(e) { Y.Helpers.panel.defaultHandler.call(this, e, destroyOnClose); },
            classNames: 'cancelButton'
        }, {
            template: '<button><span class="screenreader">' + messages.close + '</span><span aria-hidden="true" role="presentation">\u00D7</span></button>',
            section: Y.WidgetStdMod.HEADER,
            action: function(e) { Y.Helpers.panel.defaultHandler.call(this, e, destroyOnClose); }
        }];

        options = Y.mix(options, Y.Helpers.panel.defaultOpts);
        if (options.buttons) {
            options.buttons = options.buttons.concat(defaultButtons);
        }
        else if (options.overrideButtons) {
            options.buttons = options.overrideButtons;
            delete options.overrideButtons;
        }
        else {
            options.buttons = defaultButtons;
        }
        var panel = new Y.Panel(options);
        panel.get('boundingBox').addClass('versionDialog');
        //By default, the escape key will only hide the panel. Subscribe to the hide event so we can destroy the panel if destroyOnClose is true
        panel.after('visibleChange', function(e) {
            if(e.newVal === false){
                // false = hide; true = show
                Y.Helpers.panel.defaultHandler.call(this, e, destroyOnClose);
                if(options.closeCallback) {
                    options.closeCallback();
                }
            }
        });
        return panel;
    };

    Y.Helpers.scrollListItemIntoView = function (node, past) {
        if (typeof past === "undefined") {
            past = 4;
        }

        if (!node) {
            return;
        }
        if (!past) {
            return node.scrollIntoView();
        }
        return Y.Helpers.scrollListItemIntoView((Y.UA.ie && Y.UA.ie < 9) ? node.previous() : node.next(), past - 1) || node.scrollIntoView();
    };

    // Event handling for panels
    Y.Helpers.panelHandler = function (target) {
        if (!target.hasClass('listing-item')) {
            target = target.ancestor('.listing-item');
        }

        if (target.hasClass("selected")) return {alreadySelected: true};

        var previousSelection = target.get("parentNode").one(".listing-item.selected");

        target.addClass("selected");

        return {
            target: target,
            previous: previousSelection
        };
    };

    /**
     * Hide and show the specified element, alternating the link message between 'more' and 'less'.
     * @param {Object} e The element being toggled.
     */
    Y.Helpers.toggle = function (e) {
        var target = e.target, div;
        if (div = Y.one('#' + target.get('className') + '_' + target.getAttribute('id'))) {
            if (div.get('className') === 'hide'){
                div.removeClass('hide');
                target.setContent(messages.less);
            }
            else {
                div.addClass('hide');
                target.setContent(messages.more);
            }
        }
    };

}, null, {
    requires: ['node', 'io-base', 'json', 'panel', 'history', 'FormToken']
});
