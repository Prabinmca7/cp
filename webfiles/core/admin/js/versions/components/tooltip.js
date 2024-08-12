/**
 * General use tooltip.
 *
 *     Y.Tooltip.bind(node);
 *
 * `node` should have a `title` attribute that will be displayed
 * as a rich tooltip when moused over.
 */
YUI.add('Tooltip', function(Y) {
    'use strict';

    var Tooltip = Y.Component({
        init: function() {
            this.tt = Y.Node.create("<div>").addClass("tooltip");
            Y.one(document.body).append(this.tt);
        },
        events: {
            'mouseenter .line div[title], .tag-woefully[data-tooltip], .tag-outofdate[data-tooltip]': '_mouseenter',
            'mouseleave .line div[title], .tag-woefully[data-tooltip], .tag-outofdate[data-tooltip]': '_mouseleave'
        },

        _mouseenter: function(e) {
            var el = e.target, pos, title;

            if (!el.getAttribute("title") && !el.getAttribute("data-tooltip")) {
                el = el.ancestor("[title],[data-tooltip]");
            }
            pos = el.getXY();
            title = el.getAttribute("title");

            if (title) {
                el.removeAttribute("title").setAttribute("data-tooltip", title);
            }
            else {
                title = el.getAttribute("data-tooltip");
            }

            if (title) {
                this._reposition(el, title, { top: pos[1], left: pos[0] });
            }

        },

        _mouseleave: function() {
            this.tt.removeClass("active");
        },

        _reposition: function(el, title, pos) {
            var bottom = pos.top + el.get('offsetHeight') + 4,
                target = pos.left + (el.get('offsetWidth') / 2),
                arrowStyle = pos.left + 'px',
                left, width;

            this.tt.set("innerHTML", title);

            width = parseInt(this.tt.getComputedStyle("width"), 10) || 0;
            left = target - (width / 2);

            left = Math.max(0, left);

            if (pos.left > width) {
                arrowStyle = left + (width / 2);
            }

            this.tt.setStyles({
                top: bottom + "px",
                left:  left + "px"
            }).addClass("active").append("<div class='arrow' style='left:" + arrowStyle + "'></div>");
        },

        bind: function(node) {
            Y.on('mouseenter', this._mouseenter, node, this);
            Y.on('mouseleave', this._mouseleave, node, this);
        }
    });

    Y.Tooltip = new Tooltip();

}, null, {
    requires: ['node', 'event-base', 'event-mouseenter', 'Helpers', 'Component']
});
