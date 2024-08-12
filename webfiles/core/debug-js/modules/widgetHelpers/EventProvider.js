/**
 * The RightNow.EventProvider module can be used to provide a group of widgets with a common
 * event handling container. The EventProvider can be used in two ways. As a base class for 
 * a module, similar to how SearchFilter.js is implemented with search source or as a 
 * base class for a widget, similar to the FormSubmit widget.
 * @constructor
 */
RightNow.EventProvider = RightNow.Widgets.extend(/** @lends RightNow.EventProvider */{
    constructor: function() {
        this._events = {};
        this._eventHandlers = {};
        this._eventFilters = {};
    },
    
    /**
     * Adds a handler object to be run during an event.
     * @private
     * @param {String} eventName The event the handler should be run for
     * @param {Object} handler Object containing named properties to run at various
     *  points during the lifecycle of the event:
     *      pre: {Function} Called prior to notifying subscribers; passed the EventObject that the firer sent
     *      during: {Function} Called immediately after each subscriber; passed what each subscriber returned
     *      post: {Function} Called after each subscriber has been called; passed the EventObject that the firer sent
     * @return {Object} This method is chainable
     */
    _addEventHandler: function(eventName, handler) {
        this._eventHandlers[eventName] = handler;
        return this;
    },
    
    /**
     * Returns the object set with _addEventHandler.
     * @private
     * @param {String} eventName The name of the event the handler is for
     * @return {Object} The handler object or an empty object if none have been set
     */
    _getEventHandlers: function(eventName) {
        return this._eventHandlers[eventName] || {};
    },

    /**
     * Add a filter function which will be used to filter the list of subscribers
     * for a specific event.
     * @param {String} eventName The name of the event
     * @param {Function} filter The filter function. Given a list of subscribers, it should return a list of subscriber indices to be removed from the next event trigger.
     * @param {Object} context The scope of the filter function
     * @return {Object} Chainable 
     */
    _addSubscribersFilter: function(eventName, filter, context) {
        if(!filter || typeof filter !== "function") {
            throw new Error("Subscriber filters must be functions.");
        }

        if(!this._eventFilters[eventName]) {
            this._eventFilters[eventName] = [];
        }

        this._eventFilters[eventName].push({handler: filter, context: context});
        return this;
    },
        
    /**
     * Apply a list of filters against the subscriber list and get 
     * the indices which should be excluded from the next event trigger.
     * @param {String} eventName The name of the event
     * @return {Array} A list of indices which should be excluded
     */
    _getFilteredSubscriberIndices: function(eventName) {
        var subscribers = this._events[eventName],
            filters = this._eventFilters[eventName],
            allIndices = [], excludedIndices;

        if(filters) {
            for(var i = 0, filter; i < filters.length; i++) {
                filter = filters[i];
                excludedIndices = filter.handler.call(filter.context, subscribers);

                this.Y.Array.each(excludedIndices, function(index) {
                    if(isNaN(index)) return;

                    allIndices.push(index);
                }, this);
            }
        }

        return allIndices;
    },
    
    /**
     * Notifies all subscribers of the event.
     * @param {String} eventName The name of the event
     * @param {Object} eo A RightNow.Event.EventObject to pass to subscribers
     * @param {?Object} args An optional parameter to pass to subscribers
     * @return {Object} This method is chainable
     */
    fire: function(eventName, eo, args) {
        var handler = this._getEventHandlers(eventName),
            excludedIndices = this._getFilteredSubscriberIndices(eventName),
            subscribers = this._events[eventName],
            executeEvent = true,
            detach = [];

        if(handler.pre) 
            executeEvent = handler.pre.call(this, eo);
        if(executeEvent !== false) {
            if (subscribers) {
                var parameters = (typeof args !== "undefined") ? [eo, args] : [eo];
                for (var i = 0, length = subscribers.length, subscriber, returnObject; i < length; i++) {
                    //Skip over any excluded handlers
                    if(this.Y.Array.indexOf(excludedIndices, i) !== -1) continue;

                    subscriber = subscribers[i];
                    returnObject = subscriber.handler.call(subscriber.context || window, eventName, parameters);
                    if (subscriber.once) {
                        detach.push(i);
                    }
                    (handler.during && handler.during.call(this, returnObject));
                }

                if (length = detach.length) {
                    for (i = 0; i < length; i++) {
                        // Index decreases by the number of items already removed.
                        subscribers.splice(detach[i] - i, 1);
                    }
                }
            }
            (handler.post && handler.post.call(this, eo));
        }
        return this;
    },
    
    /**
     * Subscribes a function to be called when an event is fired.
     * @param {String} eventName The name of the event
     * @param {Function} handler Function to call when the event is fired
     * @param {?Object} context The value of 'this' in handler; defaults to window if not specified
     * @param  {?Boolean} once Whether to subscribe the handler for only the first time that
     *      the event is triggered
     * @return {Object} This method is chainable
     */
    on: function(eventName, handler, context, once) {
        this._events[eventName] = this._events[eventName] || [];
        if (handler && typeof handler === "function") {
            this._events[eventName].push({handler: handler, context: context, once: once});
            return this; // chainable
        }
        throw Error("Handler specified isn't a callable function");
    },

    /**
     * Subscribes a function to be called a single time when an event is fired.
     * After the event fires once, the handler is unsubscribed and is no longer
     * triggered for all subsequent times that the event is fired.
     * @param {String} eventName The name of the event
     * @param {Function} handler Function to call when the event is fired
     * @param {?Object} context The value of 'this' in handler; defaults to window if not specified
     * @return {Object} This method is chainable
     */
    once: function(eventName, handler, context) {
        return this.on(eventName, handler, context, true);
    },

    /**
     * Unsubscribes a handler function that's been subscribed with #on.
     * @param {String} event Event name
     * @param {Function} handler Callback function that's been subscribed to the event
     * @param {?Object} context The context that was supplied to the event subscription;
     *  if null, removes all callbacks with the supplied handler
     * @return {Object} This method is chainable
     */
    detach: function(event, handler, context) {
        if (handler && typeof handler === 'function') {
            var i, handlers;
            if (handlers = this._events[event]) {
                for (i = handlers.length - 1; i >= 0; i--) {
                    if (handlers[i].handler === handler && (!context || context === handlers[i].context)) {
                        handlers.splice(i, 1);
                    }
                }
            }

            return this;
        }
        throw Error("Handler specified isn't a callable function");
    }
});
