YUI.add('step-one', function(Y) {
    'use strict';

    /**
     * Step 1.
     * Presents the two choices--new or extend
     */
    Y.StepOne = Y.Step({
        name: 'one',
        events: {
            'click .link a': 'click'
        },
        /**
         * When a choice is clicked.
         * @param {object} e DOM Event facade
         */
        click: function(e) {
            e.halt();

            var type = e.target.getAttribute('data-type'),
                parent = e.target.get('parentNode');

            parent.get('parentNode').get('children').removeClass('selected');
            parent.addClass('selected');
            this.fire({ type: type });
        }
    });
}, null, {
    requires: ['step']
});
