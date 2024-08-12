YUI.add('eventbus', function (Y) {
    'use strict';

    // Event object for communication
    // between steps and the sidebar
    Y.Eventbus = new Y.EventTarget({
        prefix:     'step',
        emitFacade: true
    });
}, null, {
    requires: ['event-custom']
});
