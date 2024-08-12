/*global messages*/

YUI.add('sidebar', function(Y) {
    'use strict';

    /**
     * Initializes the sidebar that "sticks" to
     * the top of the viewport as it's scrolled.
     * IE7 positioning is horribly broken, so don't bother.
     */
    if (Y.UA.ie && Y.UA.ie < 8) return;
    /**
     * Create a clone of the sidebar and insert so
     * that the fixed-position & visible sidebar
     * can simply use the x-position of the static
     * and hidden sidebar.
     * This is done because the sidebar &
     * main content area have percent-based
     * widths rather than fixed-width.
     */
    var stick = Y.one('.sidebar'),
        top = stick.getY(),
        margin = parseInt(stick.getComputedStyle('marginLeft'), 10),
        clone = stick.cloneNode();

    clone.setStyle('visibility', 'hidden');
    stick.insert(clone, 'after');
    Y.Lang.later(100, null, function() {
        if ((document.body.scrollTop || document.documentElement.scrollTop) > top) {
            stick.setStyles({
                position: 'fixed',
                top:      '10px',
                left:     clone.getX() - margin - document.body.scrollLeft + 'px',
                width:    clone.getComputedStyle('width')
            });
        }
        else {
            stick.setStyles({position: 'static', width: clone.getComputedStyle('width')});
        }
    }, null, true);

    /**
     * Sidebar.
     * Listens to steps' events and displays the progress.
     */
    Y.Sidebar = Y.Step({
        name: 'sidebar',
        events: {
            'click li[class]': 'scrollToStep'
        },
        /**
         * @constructor
         */
        init: function() {
            Y.Eventbus.on(['step:one', 'step:two', 'step:three', 'step:four', 'step:last'], this.listener, this);
        },
        /**
         * When a step is instantiated.
         * @param {string} step name of the step
         */
        start: function(step) {
            var next = this.content.one('.' + step);
            while (next) {
                next.addClass('hide');
                next = next.next();
            }
        },
        /**
         * Scrolls to a step when it's clicked in the sidebar.
         * @param {object} e DOM Event facade
         */
        scrollToStep: function(e) {
            Y.Step.scrollToNode(Y.one('.main section.' + e.currentTarget.get('className')));
        },
        /**
         * Handles event processing.
         * @param {object} e Event facade
         */
        listener: function(e) {
            var step = e._type.split(':')[1];
            if(step === 'last'){
                this.content.addClass('finished');
            }
            if (e.start) {
                this.start(step);
            }
            else {
                this.content.one('.' + step).set('innerHTML', this.getOutput(step, e)).removeClass('hide');
            }
        },
        /**
         * Gets the output for the specific step being processed.
         * @param {string} step Step name
         * @param {object} input Event
         * @return {string}
         */
        getOutput: function(step, input) {
            step = step.charAt(0).toUpperCase() + step.substr(1);
            return this['step' + step + 'Output'](input);
        },
        /**
         * Output for step 1.
         * @param {object} input event object
         * @return {string}
         */
        stepOneOutput: function(input) {
            return (input.type === 'extend')
                ? messages.newExtendingWidget
                : messages.newWidget;
        },
        /**
         * Output for step 2.
         * @param {object} input event object
         * @return {string}
         */
        stepTwoOutput: function(input) {
            return input.widget.folder + '/' + input.widget.name +
            ((input.extendsFrom) ? '<small>' + messages.extendsFrom.replace('%s', input.widget.extendsFrom) + '</small>'
            : '');
        },
        /**
         * Output for step 3.
         * @param {object} input event object
         * @return {string}
         */
        stepThreeOutput: function(input) {
            var output = messages.components + '<ul>';
            Y.Object.each(input.widget.components, function(item, key) {
                if (item) {
                    output += '<li>' + messages[key] + '</li>';
                }
            });
            return output + '</ul>';
        },
        /**
         * Output for step 4.
         * @param {object} input event object
         * @return {string}
         */
        stepFourOutput: function(input) {
            var numberOfAttributes = Y.Object.size(input.widget.attributes);

            return numberOfAttributes === 1
                ? messages.attribute.replace('%s', numberOfAttributes)
                : messages.attributes.replace('%s', numberOfAttributes);
        }
    });
}, null, {
    requires: ['node', 'step', 'eventbus']
});
