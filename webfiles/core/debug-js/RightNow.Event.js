if(RightNow.Event) throw new Error("The RightNow.Event namespace variable has already been defined somewhere.");
YUI().use('event-base', 'event-custom-base', function(Y) {
/**
 * This namespace contains functions and classes related to the CP JavaScript event bus.
 * @namespace
 */
RightNow.Event = (function(){
    var _eventInstances = {},
        _noSessionCookies = false,
        _widgetsSubscribedToFTokUpdate = [];

    return {
        /**
         * Creates a new YUI custom event
         * @param {string} name The name of the custom event to create
         * @param {Object} scope The scope that the event will fire from (window object if none is set)
         */
        create: function(name, scope)
        {
            if(!_eventInstances[name])
                _eventInstances[name] = new Y.CustomEvent(name, {context: scope || window});
            return _eventInstances[name];
        },

        /**
         * Method for firing custom events. If the event doesn't exist, no event will
         * be fired
         *
         * @param {string} name The name of the custom event to fire
         * @param {...*} oneOrMoreEventArguments One or more arguments you want passed to the callback
         * @return {boolean} false if one of the subscribers returned false, true otherwise
         */
        fire: function(name, oneOrMoreEventArguments)
        {
            if(_eventInstances[name])
            {
                return _eventInstances[name].fire(name, [].slice.call(arguments, 1));
            }
            return true;
        },

        /**
         * Returns an instance of a YUI custom event given the name of the event
         * @param {string} name The name of the event to retrieve
         * @return {?Object} The event instance or null if it doesn't exist
         */
        get: function(name)
        {
            return _eventInstances[name] || null;
        },

        /**
         * Returns an array of widget instance IDs that are subscribed to the event evt_formTokenUpdate
         * @return (array) The instance IDs of widgets subscribed to evt_formTokenUpdate
         */
        getWidgetsSubscribedToFTokUpdate: function()
        {
            return _widgetsSubscribedToFTokUpdate;
        },

        /**
         * Method for subscribing to a custom event If the event doesn't exist,
         * it will be created first.
         *
         * @param {string} name The name of the custom event
         * @param {function()} callback The callback function to be executed when the event is fired
         * @param {Object} scope The scope of the callback
         * @param {Object=} parameter Optional object that will be passed to the callback when the event is later fired
         * @return {Object} Event handle object with a detach method to detch the handler(s).
         */
        on: function(name, callback, scope, parameter)
        {
            return (_eventInstances[name] || this.create(name, scope)).on(callback, scope, parameter);
        },

        /**
         * Alias of RightNow.Event.on
         * @see RightNow.Event.on
         */
        subscribe: function(name, callback, scope, parameter)
        {
            if (name === "evt_formTokenUpdate") {
                _widgetsSubscribedToFTokUpdate.push(scope.instanceID);
            }
            return this.on(name, callback, scope, parameter);
        },

        /**
         * Unsubscribes the function handler for the given event.
         *
         * @param {string} name The name of the event to unsubscribe from
         * @param {function()} functionHandler The function handler to unsubscribe
         * @param {Object=} customObject object passed to subscribe function. Used to disambiguate multiple listeners to the same function
         */
        unsubscribe: function(name, functionHandler, customObject)
        {
            _eventInstances[name] && _eventInstances[name].detach(functionHandler, customObject);
        },

        /**
         * Returns a function which will execute 'callback' with 'context' as its 'this'.
         * Useful for replacing an event handler with a function that's a member of
         * a different object.
         * @param {Object} context object The context to execute the function within
         * @param {function()} callback The function to execute
         * @return {function()} The defined function
         */
        createDelegate: function(context, callback)
        {
            return function()
            {
                return callback.apply(context, arguments);
            };
        },

        /**
        * Common method to check for correct report id
        * @param {Object} eventArguments Report filters object
        * @param {number} reportID The report ID to match against
        * @return {boolean} bool True if reportIDs match
        */
        isSameReportID: function(eventArguments, reportID)
        {
            return (eventArguments && eventArguments[0] && eventArguments[0].filters && (eventArguments[0].filters.report_id == reportID));
        },

        /**
        * Used to get the correct data structure for search/report filters
        * @param {Object} eventArguments Report filters object
        * @param {string} searchName The array item being requested
        * @param {number} reportID The report id of the requesting widget. It must match what is in the eventArguments[0].filters.report_id to get data back
        * @return {*} The value that was in the filters.data structure for that item
        */
        getDataFromFiltersEventResponse: function(eventArguments, searchName, reportID)
        {
            var element = eventArguments[0],
                returnData = null;
            if(element.filters && (!reportID || element.filters.report_id == reportID))
            {
                var allFilters = element.filters.allFilters;
                if(allFilters)
                {
                    if (allFilters.filters && allFilters.filters[searchName] && allFilters.filters[searchName].filters)
                        returnData = allFilters.filters[searchName].filters.data;
                    else if (allFilters[searchName] && allFilters[searchName].filters)
                        returnData = allFilters[searchName].filters.data;
                }
                else
                {
                    returnData = element.filters.data;
                }
            }
            return returnData;
        },

        /**
         * Sets whether or not the user is allowing session cookies
         * @param {boolean} value Value denoting if session cookies are enabled or not
         */
        setNoSessionCookies: function(value)
        {
            _noSessionCookies = value;
        },

        /**
         * Returns whether or not the user is allowing session cookies
         * @return {boolean}
         */
        noSessionCookies: function()
        {
            return _noSessionCookies;
        },

        /**
         * Sets a temporary cookie if session cookies are not allowed.
         * This is used to verify that login cookies can be set for the user.
         */
        setTestLoginCookie: function()
        {
            if(this.noSessionCookies()) {
                //Attempt to set a test login cookie
                document.cookie = "cp_login_start=1;path=/;HttpOnly;secure";
            }
        },

        /**
        * Delimiter used for the history manager
        * @type string
        */
        browserHistoryManagementKey: "s",

        /**
        * Checks if a history management state for a report is within the URL.
        * Use YAHOO.util.HistoryManager.getCurrentState() to actually get the state.
        * Only use this to check that the fragment actually exists before the history manager is initialized.
        * @return {boolean} Whether or not the current URL fragment is the historyManagementKey
        */
        isHistoryManagerFragment: function()
        {
            this.isHistoryManagerFragment._valueToMatch = this.isHistoryManagerFragment._valueToMatch ||
                new RegExp("#" + this.browserHistoryManagementKey + "=[A-Za-z0-9\+\/]+");
            return window.location.hash.match(this.isHistoryManagerFragment._valueToMatch) !== null;
        },

        /**
         * The EventObject object that is passed around with CP events.
         * @constructor
         * @param {Object=} widgetReference Reference to a widget
         * @param {Object=} dataToSet Data to set
         * @class
         */
        EventObject: function(widgetReference, dataToSet)
        {
            /**
             * Unique ID of the widget that is generated from controller
             * @type {?number}
             */
            this.w_id = (widgetReference && widgetReference.instanceID) ? widgetReference.instanceID : null;
            /**
             * Object of data to be passed
             * @type {Object}
             */
            this.data = (dataToSet && dataToSet.data) ? dataToSet.data : {};
            if(widgetReference && widgetReference.data && widgetReference.data.info && widgetReference.instanceID) {
                this.data.w_id = widgetReference.data.info.w_id;
                this.data.rn_contextData = widgetReference.data.contextData;
                this.data.rn_contextToken = widgetReference.data.contextToken;
                this.data.rn_timestamp = widgetReference.data.timestamp;
                this.data.rn_formToken = widgetReference.data.formToken;
                this.data.formTokenUpdateList = _widgetsSubscribedToFTokUpdate;
            }
            /**
             * Object of search filter specific data
             * @type {Object}
             */
            this.filters = (dataToSet && dataToSet.filters) ? dataToSet.filters : {};
        },

        /**
         * Controls the event logic for the framework. All event handlers are within this class
         * and it does the work of aggregating data and making requests to the server to get data,
         * which it then passes back to the widgets.
         * @class
         */
        EventBus: function()
        {
            var _productLinkingMap = null;

            /**
             * Instantiates all events and hooks them up to their handler functions
             * @private
             */
            this.initializeEventBus = function()
            {
                //notifications
                var s = 'on';
                this[s]("evt_menuFilterSelectRequest", onMenuFilterSelectRequest, this);
                //forms
                this[s]("evt_menuFilterRequest", onMenuFilterRequest, this);
                this[s]("evt_menuFilterRequestProductCatalog", onMenuFilterRequestProductCatalog, this);
                this[s]("evt_menuFilterGetResponse", onMenuFilterReset, this);
                this[s]("evt_resetFilterRequest", onMenuFilterReset, this);
                this[s]("evt_accessibleTreeViewRequest", onAccessibleTreeViewRequest, this);
                this[s]("evt_accessibleProductCatalogTreeViewRequest", onAccessibleProductCatalogTreeViewRequest, this);
                this[s]("evt_formTokenRequest", onFormTokenRequest, this);
            };

            /**
             * event handler
             * @param {string} type The event object type
             * @param {Object} eventObject
             * @private
             */
            function onAccessibleTreeViewRequest(type, eventObject)
            {
                eventObject = eventObject[0];
                var postData = {"hm_type": eventObject.data.hm_type, "linking_on": eventObject.data.linkingProduct};
                RightNow.Ajax.makeRequest("/ci/ajaxRequestMin/getAccessibleTreeView", postData, {"successHandler": accessibleTreeViewGetSuccess, "scope":this, "data": eventObject, "type":"GETPOST"});
            }

            /**
             * event handler
             * @param {string} type The event object type
             * @param {Object} eventObject
             * @private
             */
            function onAccessibleProductCatalogTreeViewRequest(type, eventObject)
            {
                eventObject = eventObject[0];
                var postData = {"isSearchRequest": eventObject.data.isSearchRequest};
                RightNow.Ajax.makeRequest("/ci/ajaxRequestMin/getAccessibleProductCatalogTreeView", postData, {"successHandler" : accessibleProductCatalogTreeViewGetSuccess, "scope" : this, "data" : eventObject, "type" : "GETPOST"});
            }

            /**
             * Event handler
             * @param {string} type The event object type
             * @param {Object} eventObject The request's EventObject
             * @private
             */
            function onFormTokenRequest(type, eventObject)
            {
                eventObject = eventObject[0];
                var postData = {"formToken": eventObject.data.formToken};
                RightNow.Ajax.makeRequest("/ci/ajaxRequest/getNewFormToken", postData, {
                    "successHandler": formTokenSuccessHandler, "scope": this, "data": eventObject, "json": true});
            }

            /**
             * Function handler for successful form token ajax request
             * @param {Object} response Object from the server
             * @param {Object} eventObject Original request's Event Object
             */
            function formTokenSuccessHandler(response, eventObject)
            {
                eventObject.data.newToken = response.newToken;
                eventObject.data.formTokenUpdateList = this.getWidgetsSubscribedToFTokUpdate();
                this.fire("evt_formTokenUpdate", eventObject);
            }

            /**
             * event return handler
             * @param {Object} response server response
             * @param {Object} eventObject
             * @private
             */
            function accessibleTreeViewGetSuccess(response, eventObject)
            {
                if (response && response.result) {
                    eventObject.data.accessibleLinks = response.result;
                    this.fire("evt_accessibleTreeViewGetResponse", eventObject);
                }
                else {
                    RightNow.UI.Dialog.messageDialog(response.suggestedErrorMessage || RightNow.Interface.getMessage('THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG'), {"icon": "WARN"});
                }
            }

             /**
             * event return handler
             * @param {Object} response server response
             * @param {Object} eventObject
             * @private
             */
            function accessibleProductCatalogTreeViewGetSuccess(response, eventObject)
            {
                if (response && response.result) {
                    eventObject.data.accessibleLinks = response.result;
                    this.fire("evt_accessibleProductCatalogTreeViewGetResponse", eventObject);
                }
                else {
                    RightNow.UI.Dialog.messageDialog(response.suggestedErrorMessage || RightNow.Interface.getMessage('THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG'), {"icon": "WARN"});
                }
            }

            /**
             * event handler
             * @param {string} type the event object type
             * @param {Object} eventObject
             * @private
             */
            function onMenuFilterRequest(type, eventObject)
            {
                eventObject = eventObject[0];
                var eo;

                if(eventObject.data.link_map)
                    _productLinkingMap = eventObject.data.link_map;

                //Currently only doing cache checking on non-linked menu filters
                if(!eventObject.data.linking_on)
                {
                    if(eventObject.data.level > 5)
                        return;

                    if(eventObject.data.value < 1)
                    {
                        eventObject.data.level = 1;
                        eventObject.data.hier_data = [];
                        this.fire("evt_menuFilterGetResponse", eventObject);
                        return;
                    }
                    if(eventObject.data.cache[eventObject.data.value])
                    {
                        //create new evt obj so that request evt obj isn't modified
                        eo = new this.EventObject();
                        eo.data = {"hier_data" : eventObject.data.cache[eventObject.data.value], "level" : eventObject.data.level + 1};
                        eo.w_id = eventObject.w_id;
                        this.fire("evt_menuFilterGetResponse", eo);
                        return;
                    }
                }
                else if((eventObject.data.data_type.toLowerCase().indexOf("cat") > -1) && _productLinkingMap && _productLinkingMap[eventObject.data.value])
                {
                    eo = RightNow.Lang.cloneObject(eventObject);
                    if(!eo.data.reset)
                        eo.data.level++;
                    eo.data.hier_data = RightNow.Lang.cloneObject(_productLinkingMap[eo.data.value]);
                    eo.data.via_filter_request = true;
                    eo.data.via_hier_request = true;
                    this.fire("evt_menuFilterGetResponse", eo);
                    return;
                }

                RightNow.Ajax.makeRequest("/ci/ajaxRequestMin/getHierValues", {
                    linking: eventObject.data.linking_on,
                    filter: eventObject.data.data_type,
                    id: eventObject.data.value
                }, {
                    successHandler: menuFilterGetSuccess,
                    data: eventObject,
                    type:"GETPOST",
                    scope: this,
                    json: true
                });
            }

            /**
             * event handler
             * @param {string} type the event object type
             * @param {Object} eventObject
             * @private
             */
            function onMenuFilterRequestProductCatalog(type, eventObject)
            {
                eventObject = eventObject[0];

                if(eventObject.data.value < 1)
                {
                    eventObject.data.level = 1;
                    eventObject.data.hier_data = [];
                    this.fire("evt_menuFilterProductCatalogGetResponse", eventObject);
                    return;
                }

                RightNow.Ajax.makeRequest("/ci/ajaxRequestMin/getHierValuesForProductCatalog", {
                    id: eventObject.data.value,
                    level: eventObject.data.level,
                    isSearchRequest: eventObject.data.isSearchRequest
                }, {
                    successHandler: menuFilterGetSuccessProductCatalog,
                    data: eventObject,
                    type:"GETPOST",
                    scope: this,
                    json: true
                });
            }

            /**
             * Function handler for successful hier menu ajax request
             * @private
             * @param {Object} results object from the server
             * @param {Object} eventObject Original request's EventObject
             */
            function menuFilterGetSuccessProductCatalog(results, eventObject)
            {
                results = results.result;
                if (!results) return;

                eventObject.data.hier_data = results;
                eventObject.data.level++;
                this.fire("evt_menuFilterProductCatalogGetResponse", eventObject);
            }

            /**
             * event handler to reset _productLinkingMap
             * @param {string} type the event object type
             * @param {Object} eventObject
             * @private
             */
            function onMenuFilterReset(type, eventObject)
            {
                eventObject = eventObject[0];
                if(eventObject.data.reset &&
                    ((eventObject.data.reset_linked_category && eventObject.data.data_type === "Category")
                        || (eventObject.data.name === "c")))
                    _productLinkingMap = null;
            }

            /**
             * Function handler for successful hier menu ajax request
             * @private
             * @param {Object} results object from the server
             * @param {Object} eventObject Original request's EventObject
             */
            function menuFilterGetSuccess(results, eventObject)
            {
                results = results.result;
                if (!results) return;
                //results[0] - Actual filter results
                //results[link_map] - Linking results if neccesary
                eventObject.data.cache[eventObject.data.value] = results[0];
                eventObject.data.hier_data = results[0];
                eventObject.data.level++;
                eventObject.data.via_hier_request = true;
                this.fire("evt_menuFilterGetResponse", eventObject);

                //If linking is on, populate link_map and fire event to category hier menus
                if (eventObject.data.linking_on && eventObject.data.data_type.toLowerCase().indexOf("prod") > -1)
                {
                    _productLinkingMap = results.link_map;
                    this.fire("evt_menuFilterGetResponse", new this.EventObject(null, {data: {
                        level: 1,
                        hier_data: _productLinkingMap[0],
                        data_type: eventObject.data.data_type.replace("Product", "Category"),
                        reset_linked_category: true,
                        via_linking: true
                    }, filters: {
                        report_id: eventObject.filters.report_id
                    }}));
                    //If product changed to none selected, clear out link map
                    if (!eventObject.data.value || eventObject.data.value === -1)
                        _productLinkingMap = null;
                }
            }

            /**
             * event handler
             * @param {string} type the event object type
             * @param {Object} eventObject
             * @private
             */
            function onMenuFilterSelectRequest(type, eventObject)
            {
                var postData = {"filter_type": eventObject[0].data.data_type, "id": eventObject[0].data.id, "f_tok": eventObject[0].data.f_tok};
                this.fire("evt_menuFilterSettingResponse");
                RightNow.Ajax.makeRequest("/ci/ajaxRequest/addOrRenewNotification", postData, {"data": {"eventName": "evt_menuFilterSelectResponse"}});
            }
        }
    };
}());
Y.on('domready', function(){var E = RightNow.Event;E.EventBus = new E.EventBus();E.EventBus.initializeEventBus.call(E);});
});
