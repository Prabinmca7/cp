//= require admin/js/tools/widgetBuilder/tooltip.js
//= require admin/js/tools/widgetBuilder/eventbus.js
//= require admin/js/tools/widgetBuilder/step.js
//= require admin/js/tools/widgetBuilder/sidebar.js
//= require admin/js/tools/widgetBuilder/stepOne.js
//= require admin/js/tools/widgetBuilder/stepTwo.js
//= require admin/js/tools/widgetBuilder/stepThree.js
//= require admin/js/tools/widgetBuilder/stepFour.js
//= require admin/js/tools/widgetBuilder/stepFive.js
//= require admin/js/tools/widgetBuilder/finalStep.js

YUI().use('eventbus', 'sidebar',
    'step-one', 'step-two', 'step-three', 'step-four', 'step-five', 'final-step', function(Y) {
    'use strict';

    // State of the widget being built.
    // Populated and sent to the server.
    var Widget = {
        name: null,
        folder: null,
        extendsFrom: null,
        components: {
            php: false,
            ajax: false,
            view: false,
            jsView: false,
            js: false
         /* overrideView: optionally specified, if `extendsFrom` */
         /* parentCss: optionally specified, if `extendsFrom` */
        },
        attributes: {}
     /* yuiModules: array, optionally specified, if `js` component */
     /* info: object, optionally specified */
    };

    /**
     * Subscribe to the events that each step fires when they're ready
     * to go onto the next step.
     * @param {Object} events contents: eventName → handler
     */
    function subscribe(events) {
        function wrapper(eventName, handler) {
            return Y.Eventbus.on(eventName, function (e) {
                // Each step fires a start event when it's instantiated
                // but we don't care about that one.
                if (e.start) return;

                if (e.widget) Widget = Y.mix(Widget, e.widget, true);

                handler(e);
            });
        }

        Y.Object.each(events, function(handler, eventName) {
            wrapper(eventName, handler);
        });
    }

    /**
     * Validates that the requirements for
     * Steps 1 - 3 are met. Called after Steps 3 and 4.
     * @param {Object} widget Current state of Widget
     * @return Boolean True if everything's valid, False otherwise
     */
    function validateStepThreeProgress(widget) {
        return ((widget.extendsFrom)
            ? widget.extendsFrom in window.allWidgets
            : true)
        && (widget.folder && widget.name && !(('custom/' + widget.folder + '/' + widget.name) in window.allWidgets))
        && Y.Object.some(widget.components, function(value, key) {
                return value && key.indexOf('Css') === -1;
            });
    }

    subscribe({
        'step:one': function(e) {
            new Y.StepTwo(e.type);
        },
        'step:two': function() {
            new Y.StepThree(Widget, validateStepThreeProgress);
        },
        'step:three': function () {
            new Y.StepFour(Widget, validateStepThreeProgress);
        },
        'step:four': function () {
            new Y.StepFive(Widget);
        },
        'step:five': function () {
            new Y.FinalStep(Widget);
        }
    });

    new Y.StepOne();
    if (Y.Sidebar) new Y.Sidebar();
});
