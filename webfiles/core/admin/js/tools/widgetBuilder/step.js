YUI.add('step', function(Y) {
    /**
     * Super minimal template mechanism.
     * @param {String} content template content
     * @constructor
     */
    function Template(content) {
        this.content = content;
    }

    /**
     * Subs out the curly-surrounded stuff in the template content
     * with the supplied corresponding object literal values
     * @param  {Object=} data Object literal with keys mapping to
     *                        curly-wrapped var names in the template
     *                        content; if not supplied, the template
     *                        content is assumed to be good-to-go
     * @return {String}      rendered view
     */
    Template.prototype.render = function (data) {
        return (data) ? Y.Lang.sub(this.content, data) : this.content;
    };

    /**
     * Base Step that all steps use.
     * @param {object} properties
     *   The only required key-val is `name`,
     *   which is the step's DOM element class name.
     *   May also include `events`:
     *    format: {"DOMEvent selector": "event handler method"}
     *   May also include `init`: method that's run as the
     *   step's constructor.
     * @param statics {object} Static properties for the step
     * @class Step
     */
    Y.Step = function(properties, statics) {
        /* @constructor */
        var base = function() {
            this.container = Y.one('.' + this.name);
            this.content = this.container.one('.content');
            this.button = this.content.one('button.continue');

            if (this.events && !this.events.subscribed) {
                // DOM Event subscription
                Y.Object.each(this.events, function(val, key, items) {
                    items = key.split(' ');
                    if (items[0] === 'valueChange') {
                        // valueChange must be subscribed to thru the Node object itself
                        Y.all(items.slice(1).join(' ')).on('valueChange', this.eventProxy(val), this);
                    }
                    else {
                        // Subscribe all events thru a delegate:
                        // - Narrows events to each step's DOM element
                        // - Doesn't matter if the elements exist at subscription time
                        this.content.delegate(items[0], this.eventProxy(val), items.slice(1).join(' '), this);
                    }
                }, this);
                // Only subscribe once, if step is instantiated multiple times
                this.events.subscribed = true;
            }

            if (properties.init) {
                properties.init.apply(this, arguments);
            }

            this.hideNext();
            this.show();

            // Notify sidebar
            this.fire({ start: true });
        };
        base.prototype = {
            hideNext: function() {
                // Hide subsequent steps if the user goes back
                var next = this.container.next('.step');
                while (next) {
                    next.addClass('hide');
                    next = next.next('.step');
                }
            },
            show: function() {
                var focusable;
                // Scroll to step
                Y.Step.scrollToNode(this.container.removeClass('hide'));

                if (properties.focusElement) {
                    focusable = this.content.one(properties.focusElement);
                }
                else {
                    // if no specific element was sent in, focus on first focusable element
                    focusable = this.content.one('select, input, button, a');
                }

                if (focusable) {
                    focusable.focus();
                }
            },

            /**
             * For the events specified in each step's `events` hash, the
             * handler method specified gets wrapped in a filter that:
             * 1. Short circuits and doesn't call the handler if the target element is disabled or has a disabled class.
             * 2. Hides the subsequent steps, to prevent the widget state from getting out of sync
             *     if the user goes back and tweaks an option that a later step uses.
             * 3. Calls the handler.
             * @param {Function} handler Event handler for the step event
             * @return {Function} handler with a filter wrapped around it
             */
            eventProxy: function(handler) {
                return function(e) {
                    if (e.target.get('disabled') || e.target.hasClass('disabled')) return;

                    this.hideNext();
                    this[handler](e);
                };
            },

            /**
             * Gets the specified view template for
             * the current step and optionally renders it.
             * @param  {String} name template id where the
             *                       id = `step-<this.name>-<name>`
             * @param  {Object=} data Data to use to render the template
             * @return {String|Object}      if data is supplied, rendered view string
             *                                 otherwise object with a #render method
             * @throws {Error} If You specify a name that can't be found
             */
            getTemplate: function(name, data) {
                var id = 'step-' + this.name + '-' + name,
                    view = Y.one('#' + id),
                    template;

                if (!view) throw new Error("Couldn't find template with id=" + id);

                template = new Template(view.getHTML());

                return (data) ? template.render(data) : template;
            },

            /**
             * Fires an event on the common event bus.
             * @param  {String|Object} name String name of event to fire or
             *                              Object event object to fire (
             *                              where event name defaults to
             *                              `this.name`)
             * @param  {Object=} args Object to fire to subscribers
             */
            fire: function(name, args) {
                var eventName;

                if (typeof name === 'string') {
                    eventName = name;
                }
                else {
                    eventName = this.name;
                    args = name;
                }
                Y.Eventbus.fire(eventName, args);
            },

            /**
             * Event bus subscriber.
             * @param  {String} name    Event name
             * @param  {Function} handler event handler
             */
            on: function(name, handler) {
                Y.Eventbus.on(name, handler, this);
            }
        };

        Y.mix(base.prototype, properties);
        if (statics) {
            Y.mix(base, statics);
        }
        base.prototype.constructor = base;
        return base;
    };

    /**
     * Scrolls to the given node's y-coordinate.
     * @param {object} node Node to scroll to
     */
    Y.Step.scrollToNode = function(node) {
        Y.Step.scrollToNode.bodyNode || (Y.Step.scrollToNode.bodyNode = Y.one((Y.UA.webkit) ? document.body : document.documentElement));
        new Y.Anim({node: Y.Step.scrollToNode.bodyNode, to: {scrollTop: node.getY()}, duration: 0.5}).run();
    };
}, null, {
    requires: ['eventbus', 'node', 'anim-scroll', 'event-valuechange']
});
