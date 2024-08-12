YUI.add('tip-it', function(Y) {
    'use strict';

    var tt = Y.Node.create('<div class="tooltip"></div>');
    Y.one(document.body).append(tt);

    function getTitle (el) {
        var title = el.getAttribute('title');
        if (title) {
            el.removeAttribute('title').setAttribute('data-tooltip', title);
        }
        else {
            title = el.getAttribute('data-tooltip');
        }

        return title;
    }

    function getPosition (el) {
        var pos = el.getXY(),
            top = pos[1],
            left = pos[0],
            bottom = top + el.get('offsetHeight') + 4,
            target = left + (el.get('offsetWidth') / 2);

        left = (target - ((parseInt(tt.getComputedStyle('width'), 10) || 0) / 2));

        return { y: bottom, x: Math.max(left, 0) };
    }

    function displayTooltip (el) {
        var title, pos;

        if (!el) return;

        if (!el.getAttribute('title') && !el.getAttribute('data-tooltip')) {
            el = el.ancestor('[title],[data-tooltip]');
        }

        title = getTitle(el);
        if (!title) return;

        tt.set('innerHTML', title).append('<div class="arrow"></div>').addClass('active');
        pos = getPosition(el);
        tt.setStyles({
            top:    pos.y + 'px',
            left:   pos.x + 'px'
        })
    }

    /**
     * Tooltip handler
     * @param  {String} nodes Selector for items with tooltips. Should contain a child sup element.
     */
    Y.TipIt = function(nodes) {
        Y.on(['mouseenter', 'focus'], function(e) {
            displayTooltip(e.currentTarget.one('sup'));
        }, nodes);

        Y.on(['mouseleave', 'blur'], function() {
            tt.removeClass('active').setStyles({top: -10000, left: -10000});
        }, nodes);
    };
}, null, {
    requires: ['node', 'event-mouseenter']
});
