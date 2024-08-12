/**
 * Hidden button that's displayed when the window becomes small enough.
 * Toggles the display of the lefthand column.
 */
YUI.add('PanelToggle', function(Y) {
    'use strict';

    var PanelToggle = Y.Component({
        events: {
            'click #togglePanel button': 'click'
        },
        click: function(e) {
            var classNames = ['on', 'off'],
                panels = Y.all('.leftPanel');

            if (panels.item(0).hasClass('off')) {
                classNames.reverse();
            }
            panels.replaceClass(classNames[0], classNames[1]);
            e.target.ancestor('#togglePanel').replaceClass(classNames[0], classNames[1]);
        }
    });

    Y.PanelToggle = new PanelToggle();

}, null, {
    requires: ['Helpers', 'Component']
});
