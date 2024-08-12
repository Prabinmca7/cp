/**#nocode+*/
(function() {
    // The sub-modules defined in this unit
    var History, Search, Helpers, SourceConductor, isRestoring = false;
    RightNow.ParameterContext = {};

    /**
     * Handles saving / restoring the search state in the browser's
     * history as well as caching all search responses in a local object.
     * @param {Object} Y YUI instances with the HistoryHTML5 module attached.
     * @return {Object} History module to use throughout this file
     */
    function initializeHistory(Y) {
        var cachedResponses = {},
            history = new Y.HistoryHTML5();

        /**
        * Called when the history manager's state changes. Only responds when the state change
        * is a popState change.
        * This occurs when the browser's back / forward button is clicked in
        * a [modern browser](http://caniuse.com/#feat=history).
        * @private
        */
        history.on('change', function(e) {
            if(e.src !== Y.HistoryHTML5.SRC_POPSTATE) return;

            isRestoring = true;
            var state = e.changed.state;

            if (state) {
                SourceConductor.RefreshSources(state.newVal);
            }
            else {
                e.halt(true);
                window.history.go(-1);
            }
            isRestoring = false;
        });

        return {
            enabled: true,

            /**
             * Adds the given state to the YUI history manager.
             * @param {string} key Key to use for the browser URL
             * @param {Object} state State to save
             * @param {Boolean} isRestoring Whether the state is currently being restored
             * @private
             */
            addState: function (key, state, isRestoring) {
                history[(isRestoring) ? 'replace' : 'add']({ state : state }, { url : Helpers.getCurrentPage() + key });
            },

            /**
             * Checks the local object cache for the given key and state key. Returns the state if it exists.
             * @param {string} key The state key (url path)
             * @return {?Object} The found state or null if not found
             * @private
             */
            checkCache: function(key) {
                return (key in cachedResponses) ? cachedResponses[key] : null;
            },

            /**
             * Sets the given state object as a keyed member of a local object cache.
             * @param {string} key The state key (url path)
             * @param {Object} state The state to cache
             * @private
             */
            setCache: function(key, state) {
                cachedResponses[key] = state;
            }
        };
    }

    if ('pushState' in window.history) {
        YUI().use('history-html5', function(Y) {
            History = initializeHistory(Y);
        });
    }
    else {
        History = { enabled: false };
    }

    /**
     * Search
     * @namespace Provides functions for searching reports & generic sources via ajax or page flip.
     * @requires RightNow.Ajax, RightNow.Text, RightNow.Url
     */
    Search = (function() {
        var Y = YUI();
        this.completedSearches = this.sourceCount = 0;
        this.sourceCollection = {};

        /**
         * Callback function for ajax requests. Fires the 'response' event on the correct searchSource objects
         * when all ajax requests have completed (for each source ID)
         * @private
         * @param {Object} response The response object from the server (assert: already JSON-decoded)
         * @param {Object} args The filters from the search:
         *              'searchSource' {string} The id of the searchSource that triggered the request
         *              'cacheKey' {string} The cache key to locally cache the response object under
         *              'allFilters' {Object} The state of all filters that triggered the search
         */
        function _ajaxCallback(response, args) {
            this.sourceCollection[args.sourceID] = {response: response, filters: args.filters, sourceID: args.sourceID};
            this.completedSearches++;
            History.setCache(args.sourceID + args.historyKey, this.sourceCollection[args.sourceID]);
            if(this.completedSearches === this.sourceCount) {
                RightNow.Event.fire("evt_searchRequest", new RightNow.Event.EventObject(this, {filters: args.filters}));
                SourceConductor.RefreshSources(this.sourceCollection);
                History.addState(args.historyKey, this.sourceCollection, args.isRestoring);
                this.completedSearches = 0;
                this.sourceCollection = {};
            }
        }

        /**
         * Fires off the ajax request to do a search.
         * @private
         * @param {string} url The endpoint url
         * @param {Object} params The post params
         * @param {Object} callbackData Callback data to receive in the ajax callback function
         */
        function _ajaxSearch(url, params, callbackData) {
            RightNow.Ajax.makeRequest(url, params, {
                successHandler: _ajaxCallback,
                failureHandler: function(response){
                    var message = response.suggestedErrorMessage || RightNow.Interface.getMessage('THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG');
                    if(response.status === 403 && response.responseText !== undefined){
                        message = response.responseText;
                    }
                    RightNow.UI.Dialog.messageDialog(message, {"icon": "WARN", exitCallback: function(){window.location.reload(true);}});
                },
                json: true,
                data: callbackData,
                timeout: 10000 // 10 secs
            });
        }

        /**
         * Fires off the ajax request to do a search.
         * @private
         * @param {Object} filters The filters
         * @param {Object} options AJAX options
         * @param {string} sourceID Source to use for history state
         * @param {Number} sourceCount The count of sources on the page
         * @param {Object} searchSource SearchSource object
         */
        function _searchSourceViaAjax(filters, options, sourceID, sourceCount, searchSource) {
            this.sourceCount = sourceCount;
            var filterUrl = Helpers.getFilterUrl(filters),
                cachedSearch = History.checkCache(sourceID + filterUrl);

            //We're searching without filters, return no results and skip the request
            if(filterUrl === '/') {
                searchSource.fire('response', new RightNow.Event.EventObject(null, {data: {}}));
                return;
            }

            filterUrl = RightNow.Url.addParameter(filterUrl, "session", RightNow.Url.getSession());
            //Lucky! this exact state has already been seen. Just return the cached state
            if(cachedSearch) {
                this.completedSearches++;
                this.sourceCollection[cachedSearch.sourceID] = cachedSearch;
                if(this.completedSearches === this.sourceCount) {
                    SourceConductor.RefreshSources(this.sourceCollection);
                    History.addState(filterUrl, this.sourceCollection, isRestoring);
                    this.completedSearches = 0;
                    this.sourceCollection = {};
                }
                return;
            }

            if(!options.endpoint) {
                throw Error("An endpoint hasn't been specified");
            }

            _ajaxSearch(options.endpoint, Y.mix({
                sourceID: sourceID,
                filters: RightNow.JSON.stringify(filters),
                limit: options.limit,
                includeTopicWords: (options.includeTopicWords) ? 1 : 0
            }, options.params), {
                sourceID: sourceID,
                historyKey: filterUrl,
                filters: filters,
                isRestoring: isRestoring
            });
        }

        function _searchSourceOnNewPage(filters, options) {
            // Since there can be multiple search sources(eg. KF, Social),
            // only accept the first request, and ignore all subsequent requests
                var newUrl = RightNow.Url.addParameter((options.new_page || Helpers.getCurrentPage()) + Helpers.getFilterUrl(filters), 'session', RightNow.Url.getSession());
                if(options.target === '_self') {
                    RightNow.Url.navigate(newUrl, true);
                }
                else {
                    window.open(newUrl, options.target || '_self');
                }
        }

        return {
            go: function(filters, options, sourceID, sourceCount, searchSource) {
                if(!History.enabled || (options && (options.new_page || !options.endpoint))) {
                    _searchSourceOnNewPage(filters, options);
                }
                else {
                    _searchSourceViaAjax(filters, options, sourceID, sourceCount, searchSource);
                }
            }
        };
    })();

    Helpers = {
        alphanumericSort: function(a, b) {
            return (a < b ? -1 : (a > b ? 1 : 0));
        },

        getFilterUrl: function(filters) {
            var filterKeys = [],
                keysToValues = {},
                type, filter;

            for(type in filters) {
                if (filters.hasOwnProperty(type)) {
                    filter = filters[type];

                    if(filter.key && !keysToValues[filter.key] && (filter.value || filter.value === 0 || filter.value === false)) {
                        filterKeys.push(filter.key);
                        keysToValues[filter.key] = filter.value;
                    }
                }
            }

            filterKeys.sort(Helpers.alphanumericSort);
            var url = '', filterKey;
            while(filterKey = filterKeys.shift()) {
                url += '/' + filterKey + '/' + encodeURIComponent(keysToValues[filterKey]);
            }

            return url;
        },

        getFlattenedFilters: function(filters) {
            var result = {}, filter;

            for (var key in filters) {
                if (filters.hasOwnProperty(key) && typeof filters[key] === 'object') {
                    filter = Helpers.getFlattenedFilter(filters[key]);
                    if (filter) {
                        result[key] = filter;
                    }
                }
            }

            return result;
        },

        /**
         * Returns widget-specific data to pass into the AJAX request
         * @param {Object} eo Event Object passed in by the calling widget
         * @return {Object} Object containing the widget-specific data
         */
        getWidgetData: function(eo) {
            var result = {};

            if (eo && eo.data) {
                result.w_id = eo.w_id;
                result.rn_contextData = eo.data.rn_contextData;
                result.rn_contextToken = eo.data.rn_contextToken;
                result.rn_timestamp = eo.data.rn_timestamp;
                result.rn_formToken = eo.data.rn_formToken;
            }

            return result;
        },

        getFlattenedFilter: function(filter) {
            if('value' in filter && 'type' in filter && 'key' in filter) {
                return {
                    value: filter.value,
                    key: filter.key,
                    type: filter.type
                };
            }
        },

        /**
         * Returns the current page path.
         * * Accounts for the implicit home mapping if the current page doesn't have a path or the path is '/app'.
         * * Removes all URL parameters.
         * @return {String} Current page
         */
        getCurrentPage: function() {
            var location = window.location,
                pathname = location.pathname,
                // window.location.origin is currently only supported in webkit browsers
                origin = location.origin || location.protocol + '//' + location.host;

            // If there's no path, (i.e. site.com homepage) the history url must be a FQDM otherwise the browser throws a cross-origin exception.
            // If the search is on the implicit hompage, make it the explicit homepage so that if the page is refreshed, an illegal param error isn't encounted.
            // If there's already a path, remove any parameters from it.
            return (pathname === "/" || pathname === "/app" || pathname === "/app/")
                ? origin + "/app/" + RightNow.Interface.getConfig("CP_HOME_URL")
                : pathname.split("/").slice(0, RightNow.Url.getParameterSegment() - 1).join("/");
        }
    };

    SourceConductor = (function(){
        var sources = {},
            hasHistorySource = false;

        var _searchSource = RightNow.EventProvider.extend({
            overrides: {
                constructor: function(Y, sourceID, options) {
                    this.parent();

                    this.sourceID = sourceID;
                    this.Y = Y;
                    this.initialFilters = {};
                    this.filters = {};
                    this.widgetData = {};
                    this.cancelSearch = false;
                    this.options = {};

                    delete this.data;
                    delete this.baseDomID;
                    delete this.baseSelector;

                    //Collect is only triggered by the search button
                    this._addEventHandler('collect', {
                        pre: function() {
                            this.filters = this.initialFilters;
                        },
                        during: function(eo) {
                            var filter;
                            if(eo && eo instanceof RightNow.Event.EventObject) {
                                filter = Helpers.getFlattenedFilter(eo.data);
                                if(filter && filter.value) {
                                    this.filters[filter.type] = filter;
                                }
                                else {
                                    delete this.filters[filter.type];
                                }
                            }
                        }
                    });

                    //Search can be triggered either by the pagination widget or the search button
                    this._addEventHandler('search', {
                        pre: function(eo) {
                            if(RightNow.isInstance(eo, RightNow.Event.EventObject)) {
                                // If widget's limit is set (via its per_page attribute), don't override it in search options
                                var limitAttr = (this.options.limit !== eo.data.limit) ? this.options.limit : null;

                                this.options = Y.merge(this.options, eo.data);

                                if(limitAttr) {
                                    this.options.limit = limitAttr;
                                }
                            }

                            this.options.params = Y.merge(this.options.params, this.widgetData);

                            // Super fun special casey case.
                            if ('page' in this.options) {
                                if (this.Y.Object.isEmpty(this.filters)) {
                                    this.filters = this.initialFilters;
                                }
                                this.filters.page = this.options.page;
                            }

                            this.cancelSearch = false;
                        },
                        during: function(eo) {
                            if(eo === false) {
                                this.cancelSearch = true;
                            }
                        },
                        post: function() {
                            if(!this.cancelSearch) {
                                if(this.includeTopicWords) {
                                    this.options.includeTopicWords = this.includeTopicWords;
                                }
                                Search.go(this.filters, this.options, sourceID, this.options.sourceCount || Object.keys(sources).length, this);
                            }
                            else {
                                this.fire('searchCancelled', this.filters, this.options);
                            }
                            delete this.options.sourceCount;
                        }
                    });

                    //Fired on page load to setup the history state
                    this._addEventHandler('initializeFilters', {
                        pre: function(eo) {
                            if(RightNow.isInstance(eo, RightNow.Event.EventObject)) {
                                RightNow.ParameterContext[this.instanceID] = this.initialFilters = Helpers.getFlattenedFilters(eo.data);
                                this.widgetData = Helpers.getWidgetData(eo);
                                
                                if(eo.data.f_tok) {
                                    this.widgetData.f_tok = eo.data.f_tok;
                                }
                                
                                if(!History.enabled) return;

                                // If last one on page, cache initial search (on page load)
                                var sourceNames = Object.keys(sources);
                                if(sourceNames[sourceNames.length - 1] === this.instanceID) {
                                    // Use all filters in URL so none are removed
                                    var keyCount = 0,
                                        parameterContext = {};
                                    for(var instanceID in RightNow.ParameterContext) {
                                        parameterContext = Y.merge(parameterContext, RightNow.ParameterContext[instanceID]);
                                    }

                                    var historyKey = Helpers.getFilterUrl(parameterContext),
                                        sourceCollection = {};
                                    historyKey = RightNow.Url.addParameter(historyKey, "session", RightNow.Url.getSession());

                                    this.Y.Object.each(sources, function(sourceInfo, sourceName) {
                                        var scriptNode = this.Y.one('#rn_' + sourceInfo.widgetData.w_id + '_HistoryData'),
                                            contentNode = this.Y.one('#rn_' + sourceInfo.widgetData.w_id + '_Content');

                                        if(scriptNode && contentNode) {
                                            var initialHistoryData = JSON.parse(scriptNode.getHTML());
                                            initialHistoryData.html = contentNode.getHTML();
                                            initialHistoryData._isParsed = true;
                                            sourceCollection[sourceName] = {response: initialHistoryData, filters: this.initialFilters, sourceID: sourceName};
                                            History.setCache(historyKey + sourceName, sourceCollection[sourceName]);
                                        }
                                    }, this);
                                    RightNow.Event.fire("evt_searchRequest", new RightNow.Event.EventObject(this, {filters: this.filters}));
                                    History.addState(historyKey, sourceCollection, true);
                                }
                            }
                        }
                    });

                    //Fired on search result click to update history state with latest filters
                    this._addEventHandler('updateHistoryEntry', {
                        pre: function(eo) {
                            if (!History.enabled || !eo || !(eo instanceof RightNow.Event.EventObject)) {
                                return;
                            }

                            this.options = Y.merge(this.options, eo.data.data);
                            this.options.params = Y.merge(this.options.params, this.widgetData);

                            if ('page' in this.options) {
                                if (this.Y.Object.isEmpty(this.filters)) {
                                    this.filters = this.initialFilters;
                                }
                                this.filters.page = this.options.page;
                            }
                            for (var property in eo.data.update) {
                                this.filters[property] = eo.data.update[property];
                            }
                            var historyKey = Helpers.getFilterUrl(this.filters);
                            historyKey = RightNow.Url.addParameter(historyKey, "session", RightNow.Url.getSession());
                            History.addState(historyKey, null, true);
                    }});

                    //Fired by the history to set a group of filters to a specific state
                    this._addEventHandler('updateFilters');

                    //Fired for ajax calls
                    this._addEventHandler('response');

                    //Fired by the history to set a group of filters to their initial value
                    this._addEventHandler('reset');
                    this.on('reset', function() {
                        this.filters = this.initialFilters;
                        this.page = 0;
                    }, this);
                },

                /**
                 * Sets search options.
                 * @param {Object} options Hash of options:
                 *                         - page: {int} Page number to fetch for the current
                 *                                 set of search filters
                 *                         - new_page: {string} A page path to execute the search via page navigation
                 *                         - target: {string} If `new_page` is specified, this target value is used on `window.open`
                 *                         - endpoint: {string} AJAX endpoint to search
                 *                         - params: {object} Hash of post parameters to add to the search request
                 */
                setOptions: function (options) {
                    if (options && this.sourceID in options && options[this.sourceID]) {
                        options = options[this.sourceID];
                    }

                    this.options = this.Y.mix(this.options, options, true);

                    return this;
                }
            }
        });

        /**
        * Gets a SearchSource object via its ID.
        * @param {string} sourceID A source ID.
        * @return {Object} SearchSource object.
        * @private
        */
        function _getSource(sourceID) {
            return (sourceID) ? sources[sourceID] : sources;
        }

        /**
        * Refreshes all sources in a source collection.
        * @param {Object} sourceCollection An object with keys representing source IDs and
        *   values representing the source details.
        * @private
        */
        function _refreshSources(sourceCollection) {
            var source;
            for(var sourceID in sourceCollection) {
                if(sourceCollection.hasOwnProperty(sourceID)) {
                    source = _getSource(sourceID);
                    if(source) {
                        source
                            .fire('response', new RightNow.Event.EventObject(null, {data: sourceCollection[sourceID].response}))
                            .fire('updateFilters', new RightNow.Event.EventObject(null, {data: sourceCollection[sourceID].filters}));
                    }
                }
            }
        }

        /**
         * @private
         * @constructor
         * @see SourceConductor.multipleSourcesWrapper
         */
        function _multipleSourcesWrapper(sources) {
            if (!sources || !sources.length || sources.length < 2)
                throw new Error("You're doing it wrong");
            this.multiple = true;
            this.sources = sources;
        }

        function delegate (method) {
            return function () {
                for (var i = 0, args = arguments, src; i < this.sources.length; i++) {
                    src = this.sources[i];
                    src[method].apply(src, args);
                }
                return this;
            };
        }

        _multipleSourcesWrapper.prototype = {
            on: delegate('on'),
            fire: delegate('fire'),
            setOptions: delegate('setOptions')
        };

        return {
            SearchSource: _searchSource,
            MultipleSourcesWrapper: _multipleSourcesWrapper,
            RefreshSources: _refreshSources,
            get: _getSource,
            getSearchSources: function(sourceID) {
                return (sourceID) ? (sourceID + "").split(",") : [];
            },
            add: function(sourceID, source) {
                if(sourceID && RightNow.isInstance(source, _searchSource) && !sources[sourceID]) {
                    return (sources[sourceID] = source);
                }
            }
        };
    })();
    /**#nocode-*/

    /**
     * The RightNow.SearchFilter module provides common functionality for all SearchFilter widgets via a
     * searchSource() EventProvider instance. The search source allows widgets to hook into the search specific
     * event bus for a given report ID or source ID (or combination). The following events are provided by this
     * interface:
     * @example
     * Use searchSource().on('eventName', handlerFunction) in the extending widget to subscribe to these events:
     *     'search' - Triggered when a search is performed by the SearchButton widget. Gathers all of the
     *                search filters on the page and fires a send event.
     *     'send' - Validates that all of the widgets are ready for the search to be performed and submits
     *              the filters to the server
     *     'reset' - Reset all of the filters to their initial state (when the page was loaded)
     *     'setInitialFilters' - Set the initial page load filters.
     * @requires RightNow.Widgets
     * @constructor
     */
    //Used by any widget which can only work with a single source
    RightNow.SearchConsumer = RightNow.Widgets.extend(/**@lends RightNow.SearchConsumer*/{
        /**
         * Creates a searchSource method that can be used to subscribe to search filter events.
         */
        constructor: function() {
            var sources = SourceConductor.getSearchSources(this.data.attrs.source_id),
                existingSource = SourceConductor.get(sources[0]);

            if(sources.length > 1) {
                RightNow.UI.DevelopmentHeader.addJavascriptError("A search consumer cannot have more than one search source");
            }

            if(!existingSource) {
                existingSource = new SourceConductor.SearchSource(this.Y, sources[0]);
                SourceConductor.add(sources[0], existingSource);
            }

            if(this.includeTopicWords) {
                existingSource.includeTopicWords = this.includeTopicWords;
                delete this.includeTopicWords;
            }

            this.searchSource = function() {
                return existingSource;
            };
        }
    });

    /**
     * The RightNow.SearchProducer module provides common functionality for all SearchProducer widgets via a
     * searchSource() EventProvider instance. Search 'producers' are widgets that initiate a search (e.g.
     * Search button, search filter dropdown, pagination links, etc.). The search source allows widgets to
     * hook into the search-specific event bus for a given source ID.
     * The following events are provided by this interface:
     * @example
     * Use searchSource().on('eventName', handlerFunction) in the extending widget to subscribe to these events:
     *     'collect' - Gathers all of the search filters on the page as a prelude to the 'search' event.
     *     'search' - Triggered when a search is to be performed.
     *     'send' - Validates that all of the widgets are ready for the search to be performed and submits
     *              the filters to the server.
     *     'reset' - Reset all of the filters to their initial state (when the page was loaded)
     *     'setInitialFilters' - Set the initial page load filters.
     * @requires RightNow.Widgets
     * @constructor
     */
    RightNow.SearchProducer = RightNow.Widgets.extend(/**@lends RightNow.SearchProducer*/{
        //Used by any widget which can interact with or initialize sources (button, filter, pagination)
        constructor: function() {
            var sources = SourceConductor.getSearchSources(this.data.attrs.source_id),
                searchSources = [];

            for(var i = 0; i < sources.length; i++) {
                var existingSource = SourceConductor.get(sources[i]);
                if(!existingSource) {
                    existingSource = new SourceConductor.SearchSource(this.Y, sources[i]);
                    SourceConductor.add(sources[i], existingSource);
                }
                searchSources.push(existingSource);
            }

            if (!searchSources.length) {
                throw new Error("No search sources were given");
            }

            var source = (searchSources.length === 1) ? searchSources[0] : new SourceConductor.MultipleSourcesWrapper(searchSources);
            this.searchSource = function() {
                return source;
            };
        }
    });
})();
