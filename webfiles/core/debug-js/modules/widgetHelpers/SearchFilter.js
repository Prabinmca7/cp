/**#nocode+*/
(function() {
// The sub-modules defined in this unit
var History, Search, Helpers, SourceConductor;

/**
 * Handles saving / restoring the search state in the browser's
 * history as well as caching all search responses in a local object.
 * @param {Object} Y YUI instances with the HistoryHTML5 module attached.
 * @return {Object} History module to use throughout this file
 */
function initializeHistory(Y) {
    var
    // Cache of all search responses for a particular state of search filters.
    cachedResponses = {},
    // Cache of search resquests. Cleared out after every search.
    requests = {},
    // Cache of search responses. Cleared out before every search.
    responses = {},
    // The url for the history manager state
    cacheKey = '',
    // So that we don't attempt to restore the state while in the process of restoring the state
    restoring = false,
    history = new Y.HistoryHTML5();

    /**
    * Called when the history manager's state changes. Only responds when the state change
    * is a popState change.
    * This occurs when the browser's back / forward button is clicked in
    * a [modern browser](http://caniuse.com/#feat=history).
    * @private
    */
    history.on('change', function(e) {
        var src = e.src,
            state, sources;

        if (src !== Y.HistoryHTML5.SRC_POPSTATE) return;

        state = e.changed.state || e.changed[RightNow.Event.browserHistoryManagementKey];
        sources = SourceConductor.get();

        restoring = true;
        if (state) {
            Y.Object.each(state.newVal, function(stashed) {
                Search.ajaxCallback(stashed.response, stashed.data);
            });
        }
        else {
            // going back to initial page state
            Y.Object.each(sources, function(source) {
                source.fire('reset').fire('send');
            });
        }
        restoring = false;
    });

    /**
     * Adds the given state to the YUI history manager.
     * @param {Object} state State to save
     * @param {string} key   Key to use for the browser URL
     * @private
     */
    function addState(state, key) {
        // Change the url for the search response: remove any duplicates in the segment string by converting it to an object then back to a segment
        key = ((key) ? RightNow.Url.convertToSegment(RightNow.Url.convertToArray(1, key)) : "");
        history.add({ state: state }, { url: Helpers.getCurrentPage() + key });
    }

    /**
     * Appends the given string onto the current
     * cache key and returns the result
     * @param  {string} toAppend String to append
     * @return {string}          Concated cache key
     * @private
     */
    function appendToCacheKey(toAppend) {
        return cacheKey += toAppend;
    }

    /**
     * Resets the cache key.
     * @private
     */
    function resetCacheKey() {
        cacheKey = '';
    }

    /**
     * Deals with saving a new ajax response.
     * Pairs the response with its request.
     * If there are no pending requests, the state
     * of the response is saved in the browser's history.
     * @param {Object} response      Response object
     * @param {string} transactionID Ajax transaction id
     * @private
     */
    function addResponse(response, transactionID) {
        var request = requests[transactionID];
        if (request) {
            var cacheKey = appendToCacheKey(request.friendly);

            request = Y.merge(request, response);
            responses[transactionID] = request;
            delete requests[transactionID];

            if (Y.Object.isEmpty(requests)) {
                addState(responses, cacheKey);
            }
        }
    }

    /**
     * Deals with saving off a new ajax request.
     * @param {Object} request       Request object
     * @param {string} transactionID Ajax transaction id
     * @private
     */
    function addRequest(request, transactionID) {
        if (Y.Object.isEmpty(requests)) {
            // new search
            Search.searchesPerformed++;
            responses = {};
            resetCacheKey();
        }

        requests[transactionID] = request;
    }

    /**
     * Restores the object (pulled from the local cache) into
     * the browser's history state.
     * @param  {Object} cached Object pulled from cache
     * @private
     */
    function restoreCache(cached) {
        if (Y.Object.isEmpty(requests)) {
            var currentIndex = Object.keys(responses)[0];

            if(currentIndex && responses[currentIndex]) {
                // currentIndex is a string, so `+` will automatically cast it to an integer, while ''
                // will cast it back to a string for the purpose of saving it in the response
                var newTransactionID = ++currentIndex + 1 + '';
                responses = {newTransactionID: cached};
            }

            addState(responses, appendToCacheKey(cached.friendly));
        }

        resetCacheKey();
    }

    /**
     * Checks the local object cache for the given key and state key. Returns the state if it exists.
     * @param {string} key The key of the search source (e.g. "report176")
     * @param {string} stateKey The JSON stringified, base64-encoded state of the request filters
     * @return {?Object} The found state or null if not found
     * @private
     */
    function checkCache(key, stateKey) {
        return ((cachedResponses[key]) ? cachedResponses[key][stateKey] : null);
    }

    /**
     * Sets the given state object as a keyed member of a local object cache.
     * @param {string} key The key of the search source (e.g. "report176")
     * @param {string} stateKey The JSON stringified, base64-encoded state of the request filters
     * @param {Object} state The state to cache
     * @private
     */
    function setCache(key, stateKey, state) {
        cachedResponses[key] || (cachedResponses[key] = {});
        cachedResponses[key][stateKey] = state;
    }

    /**
     * Checks if local object cache for the given key and return true if exists
     * @param {string} key The key of the search source (e.g. "report176")
     * @return {boolean} True if given key exist else false
     * @private
     */
    function doesCacheExist(key) {
        return ((cachedResponses[key]) ? true : false);
    }

    /**
     * Ensures that the given function is only
     * called when the restoring variable is false.
     * @param  {Function} func Function to call
     * @return {Function}      Wrapper function
     */
    function whenNotAlreadyRestoring(func) {
        return function() {
            if (restoring) return;

            func.apply(this, arguments);
        };
    }

    /**
     * Remove the cached data for a given cache key
     * @param {string} key The key of the search source (e.g. "report176")
     * @private
     */
    function clearCache(key) {
        cachedResponses[key] = null;
    }

    return {
        enabled:      true,
        setCache:     setCache,
        checkCache:   checkCache,
        restoreCache: whenNotAlreadyRestoring(restoreCache),
        addRequest:   whenNotAlreadyRestoring(addRequest),
        addResponse:  whenNotAlreadyRestoring(addResponse),
        clearCache:   clearCache,
        doesCacheExist: doesCacheExist
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
 * @version 1.0
 * @requires RightNow.Ajax, RightNow.Text, RightNow.Url
 */
Search = (function() {
    var Y = YUI(),
    // Keep track of the number of searches performed
        _searchesPerformed = 0;

    /**
     * Callback function for ajax requests. Fires the 'response' event on the correct searchSource object.
     * @private
     * @param {Object} response The response object from the server (assert: already JSON-decoded)
     * @param {Object} args The filters from the search:
     *              'searchSource' {string} The id of the searchSource that triggered the request
     *              'cacheKey' {string} The cache key to locally cache the response object under
     *              'allFilters' {Object} The state of all filters that triggered the search
     * @param {number=} transactionID Transaction id for the request
     */
    function _ajaxCallback(response, args, transactionID) {
        // If this is being directly called by History's state restoration then a Y.IO ajax transaction id won't exist.
        var restoring = typeof transactionID === 'undefined';

        if (response && !restoring) {
            History.addResponse({response: response, data: args}, transactionID);
        }
        History.setCache(args.searchSource, args.cacheKey, response);
        var source = SourceConductor.get(args.searchSource);
        if (restoring) {
            // Since the state is being restored, we already have the necessary data--so don't allow the 'send' event to go thru--
            // but notify subscribers that a new search has commenced for a given state of search filters.
            source.once('send', function() { return false; }).fire('send', args.allFilters);
            response.fromHistoryManager = true;
        }
        source.fire("response", new RightNow.Event.EventObject(null, {data: response, filters: args.allFilters}));
    }

    /**
     * Fires off the ajax request to do a search.
     * @private
     * @param {string} url The endpoint url
     * @param {Object} params The post params
     * @param {Object} callbackData Callback data to receive in the ajax callback function
     * @param {Object} historyKey State of requesting object to store for the request
     */
    function _ajaxSearch(url, params, callbackData, historyKey) {
        var handle = RightNow.Ajax.makeRequest(url, params, {
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
        History.addRequest(historyKey, handle.id + "");
    }

    /**
     * Takes filter and parameter data and formats for searching a generic source.
     * @private
     * @param {Object} sourceObject The search source object
     * @param {Object} searchFilters All search filters that were returned during the 'search' event
     * @param {Object} params All generic EventObjects that were returned during the 'search' event
     * @param {boolean} isViaAjax Whether the search will be via AJAX (basically, if isViaAjax is false, the 'keyword' filter name should become 'kw')
     * @return {Object} Object containing the filters to include in the request and updated parameters (keys: 'filtersToInclude', 'params')
     */
    function _searchGenericSourcePrepare(sourceObject, searchFilters, params, isViaAjax) {
        var defaultFilters = ["keyword"].concat(sourceObject.filters || []), // at this point, only keyword
            length = defaultFilters.length,
            i, filterName, reportFilter, filtersToInclude = {};

        params = RightNow.Lang.cloneObject(params);
        if (sourceObject.params) {
            params = Y.mix(params, sourceObject.params);
        }

        // pull any report filter values that should also be used for the generic search
        if (searchFilters && searchFilters.allFilters) {
            for (i = 0; i < length; i++) {
                filterName = defaultFilters[i];
                reportFilter = searchFilters.allFilters[filterName];
                if (reportFilter && reportFilter.filters && reportFilter.filters.data && !params[filterName]) {
                    // convert 'keyword' filter to 'kw' for the URL parameter if this isn't an AJAX request
                    if (filterName === 'keyword' && !isViaAjax)
                        filterName = 'kw';
                    filtersToInclude[filterName] = params[filterName] = reportFilter.filters.data;
                }
            }
        }

        if (params.page && !filtersToInclude.page) {
            filtersToInclude.page = params.page;
        }

        return {filtersToInclude: filtersToInclude, params: params};
    }

    /**
     * Does an ajax search for a custom (i.e. non-report) search source.
     * @private
     * @param {Object} sourceObject The search source object
     * @param {Object} searchFilters All search filters that were returned during the 'search' event
     * @param {Object} searchSource The search source being searched upon e.g. {type: "generic", id: "foo"}
     * @param {Object} params All generic EventObjects that were returned during the 'search' event
     */
    function _searchGenericSourceViaAjax(sourceObject, searchFilters, searchSource, params) {
        var endpoint = sourceObject.endpoint,
            genericSourcePrepared = _searchGenericSourcePrepare(sourceObject, searchFilters, params, true),
            filtersToInclude = genericSourcePrepared.filtersToInclude,
            params = genericSourcePrepared.params;

        if (!endpoint) {
            throw Error("An endpoint hasn't been specified");
        }

        var cacheKey = searchSource.type + searchSource.id,
            serializedState = RightNow.JSON.stringify(params),
            cachedSearch = History.checkCache(cacheKey, serializedState),
            requestObject = {
                key: cacheKey,
                friendly: RightNow.Url.convertToSegment(filtersToInclude)
            };

        if (cachedSearch) {
            History.restoreCache(requestObject);
            SourceConductor.get(cacheKey).fire("response", new RightNow.Event.EventObject(null, {data: cachedSearch, filters: params}));
            return;
        }

        _ajaxSearch(endpoint, params, {
            searchSource: cacheKey,
            allFilters: params,
            cacheKey: serializedState
        }, requestObject);
    }

    /**
     * Performs a page-flip search for custom (i.e. non-report) search sources.
     * @private
     * @param {Object} sourceObject The search source object
     * @param {Object} searchFilters All search filters that were returned during the 'search' event
     * @param {Object} params All generic EventObjects that were returned during the 'search' event
     */
    function _searchGenericSourceOnNewPage(sourceObject, searchFilters, params) {
        var Url = RightNow.Url,
            newUrl = Helpers.getCurrentPage(),
            genericSourcePrepared = _searchGenericSourcePrepare(sourceObject, searchFilters, params, false),
            filtersToInclude = genericSourcePrepared.filtersToInclude;

        newUrl = newUrl + Url.convertToSegment(filtersToInclude);
        newUrl = Url.addParameter(newUrl, "session", Url.getSession());

        newUrl = _addSearchParameterForPageFlips(newUrl, filtersToInclude.page);

        window.open(newUrl, '_self');
    }

    /**
     * Performs an ajax search for reports.
     * @private
     * @param {Object} searchFilters All search filters that were returned during the 'search' event
     * @param {Object} searchSource The search source being searched upon e.g. {type: "report", id: "176"}
     * @param {Object} triggerObjectFilters The filters of the EventObject that was used to invoke the search. Used to
     *                                      correctly set the search parameter so we record searching and paging differently
     */
    function _searchReportViaAjax(searchFilters, searchSource, triggerObjectFilters) {
        if (!triggerObjectFilters.page) {
            // If this isn't a paging event then a different set of
            // results is being requested, so reset the page being requested.
            searchFilters.allFilters.page = 1;
        }
        else {
            delete searchFilters.allFilters.search;
        }

        var reportID = searchSource.id,
            cacheKey = searchSource.type + searchSource.id,
            requestData = Helpers.buildReportRequestParameters(
                reportID,
                searchFilters.allFilters,
                searchFilters.format,
                searchFilters.token,
                _searchesPerformed
            ),
            segment = RightNow.Url.convertSearchFiltersToParms("", searchFilters.allFilters, ""),
            filters = requestData.sf;

        if(filters.page === 1){
            // Set a flag to tell the server to record a new search.
            // This isn't performed above along with resetting the page
            // because `search` isn't a "real" search filter and
            // we don't want "/search/1" tacked onto the URL.
            filters.search = 1;
        }

        var serializedState = RightNow.JSON.stringify(filters),
            cachedSearchObject = History.checkCache(cacheKey, serializedState),
            requestObject = {
                friendly: segment,
                response: cachedSearchObject,
                data: {
                    allFilters: searchFilters,
                    cacheKey: serializedState,
                    key: cacheKey
                }
            };

        //If we already have this search cached, just modify the URL and fire off the data.
        if (cachedSearchObject) {
            History.restoreCache(requestObject);
            SourceConductor.get(cacheKey).fire("response", new RightNow.Event.EventObject(null, {data: cachedSearchObject, filters: searchFilters}));
            return;
        }

        _ajaxSearch("/ci/ajaxRequest/getReportData", {
                filters: serializedState,
                report_id: reportID,
                r_tok: requestData.token,
                format: RightNow.JSON.stringify(requestData.fmt)
            }, {
                searchSource: cacheKey,
                allFilters: searchFilters,
                cacheKey: serializedState
            }, requestObject);
    }

    /**
     * Performs a page-flip search for reports.
     * @private
     * @param {Object} triggerObject The original EventObject that triggered the search
     *      (presumed to be fired from the SearchButton widget); contains any info about
     *      the results page:
     *      'reportPage' {string} Path of the search page
     *      'target' {string} (optional) target of the search page; defaults to '_self'
     *      'popupWindow' {boolean} (optional) Whether the search page should launch in a popup window
     * @param {Object} searchFilters All of the search filters to use on the resulting search page
     */
    function _searchReportOnNewPage(triggerObject, searchFilters) {
        var Url = RightNow.Url,
            newUrl = triggerObject.reportPage || Helpers.getCurrentPage(),
            target = triggerObject.target || "_self";

        if (!triggerObject.page && searchFilters.allFilters.page) {
            triggerObject.page = 1;
            searchFilters.allFilters.page = 1;
        }

        newUrl = Url.convertSearchFiltersToParms(newUrl, searchFilters.allFilters, _searchesPerformed);
        newUrl = Url.addParameter(newUrl, "session", Url.getSession());

        newUrl = _addSearchParameterForPageFlips(newUrl, searchFilters.allFilters.page);

        if(triggerObject.popupWindow) {
            target = '_blank';
            var position = {
                left: window.screenX || window.screenLeft,
                top: window.screenY || window.screenTop
            },
            body = document.body;
            position.right = position.left + body.clientWidth;
            position.bottom = position.top + body.clientHeight;

            var width = screen.width * triggerObject.width / 100,
                height = screen.height * triggerObject.height / 100,
                leftPos = ((position.left > (screen.width - position.right)) ? position.left - width - 15 : position.right + 15),
                topPos = window.screenY ? window.screenY : window.screenTop;
            if(leftPos < 0)
                leftPos = position.left + 100;
            if(topPos < 0)
                topPos = position.top + 100;

            window.open(newUrl, target, 'scrollbars=1,resizable=1,left=' + leftPos + ',top=' + topPos + ',width=' + width + 'px,height=' + height + 'px');
         }
         else {
            window.open(newUrl, target);
         }
     }

    /**
     * Based on the value of the page parameter, a '/search/1' will be appended to the given
     *  URL to indicate to the server (clickstreams) that this should be recorded as a new search.
     *  This function should only be used when the search is going to cause a page-flip.
     * @private
     * @param {string} url The URL to redirect to for a page-flip search
     * @param {*} page Either a specific integer page number or undefined
     * @return {string} The URL, possibly with '/search/1' appended to it
     */
     function _addSearchParameterForPageFlips(url, page) {
        // assume that if page is not set or it's set to 1,
        // then we need to indicate to the server (clickstreams)
        // that this is a new search
        if (!page || page === 1) {
            url = RightNow.Url.addParameter(url, "search", "1");
        }
        return url;
     }

return {
    /**
     * Keep track of the number of searches performed
     * @type {number}
     */
    searchesPerformed: _searchesPerformed,

    /**
     * Calls the appropriate functions to do a search.
     * @param {Object} triggerObject The original EventObject that triggered the search
     *  (presumably from a SearchButton widget); only used for report searches
     * @param {Object} searchFilters All search filters that were returned during the 'search' event
     * @param {Object} params All generic EventObjects that were returned during the 'search' event
     *  (only used for non-report searches)
     * @param {Object} searchSource The search source being searched upon e.g. {type: "report", id: "176"}
     * @param {Object} sourceObject The search source object
     */
    go: function(triggerObject, searchFilters, params, searchSource, sourceObject) {
        if (searchSource.type === "report") {
            if (typeof searchFilters === 'undefined') {
                throw Error('No search filters have been defined for report ' + searchSource.id);
            }
            // Fire global "search performed" event.
            RightNow.Event.fire("evt_searchRequest", new RightNow.Event.EventObject(this, {filters: searchFilters}));
            if (!History.enabled || triggerObject.newPage === true || (triggerObject.reportPage && !RightNow.Url.isSameUrl(triggerObject.reportPage))) {
                _searchReportOnNewPage(triggerObject, searchFilters);
            }
            else {
                _searchReportViaAjax(searchFilters, searchSource, triggerObject);
            }
        }
        else if (params && typeof params === "object" && searchSource.id) {
            // Fire global "search performed" event.
            RightNow.Event.fire("evt_searchRequest", new RightNow.Event.EventObject(this, {filters: searchFilters}));
            if (!History.enabled || triggerObject.newPage === true) {
                _searchGenericSourceOnNewPage(sourceObject, searchFilters, params);
            }
            else {
                _searchGenericSourceViaAjax(sourceObject, searchFilters, searchSource, params);
            }
        }
    },
    ajaxCallback: _ajaxCallback
};
})();

Helpers = {
    /**
     * Builds the request object that's sent to the server for report searches.
     * @param {string} reportID The report ID of the report requested
     * @param {Object} allFilters All filters to send to the server
     * @param {Object} format Format options for the requested report
     * @param {string} token Request token
     * @param {number} searchesPerformed The number of searches performed
     * @return {Object} Request object that's ready to be sent in an ajax request
     */
    buildReportRequestParameters: function(reportID, allFilters, format, token, searchesPerformed) {
        var filters = RightNow.Lang.cloneObject(allFilters),
            filter;

        for (filter in filters) {
            if (filters.hasOwnProperty(filter)) {
                if (filters[filter].filters) {
                    filters[filter].filters.report_id = parseInt(reportID, 10);
                }
                if (filters[filter].data) {
                    delete filters[filter].data;
                }
            }
        }
        //Generate the URL parameter string that we want to tack onto the end of each result URL. The
        //server side will handle the appending of this string.
        format || (format = {});
        format.urlParms = RightNow.Url.buildUrlLinkString(allFilters, format.parmList);

        return {
            c: searchesPerformed,
            id: reportID,
            sf: filters,
            fmt: format,
            token: token
        };
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

SourceConductor = (function() {
    var
        // Keep track of each search source being used on the page
        _searchSources = {},

        /**
        * @private
        * @see SourceConductor.searchSource
        */
        _searchSource = RightNow.EventProvider.extend({
            overrides: {
                constructor: function(Y, instanceID) {
                    this.parent();

                    this.Y = Y;
                    delete this.data;
                    delete this.baseDomID;
                    delete this.baseSelector;
                    this.searchSource = instanceID;
                    this.instanceID = instanceID.id;
                    /**
                     *  Triggered before a search occurs. Widgets subscribe to this event so that they can add their search filters
                     *  into the collection before the search request is actually made to the server. Typically this event is fired
                     *  in two places. The searchButton widget, when a new search is made, and the displaySearchFilters widget when
                     *  a filter is removed or reset.
                     */
                    this._addEventHandler("search", {
                        pre: function(eo) {
                            this._params || (this._params = {});
                            if (eo instanceof RightNow.Event.EventObject) {
                                this._originalEventObject = RightNow.Lang.cloneObject(eo);
                                this._collectSearchFilters(eo);
                                this._params.newPage = eo.filters.newPage;
                            }
                        },
                        during: function(eo) {
                            if (eo instanceof RightNow.Event.EventObject) {
                                this._collectSearchFilters(eo);
                            }
                        },
                        post: function() {
                            var i, excludedFilterName, searchFilter;
                            if(this._excludedFilters) {
                                for(i = 0; i < this._excludedFilters.length; i++) {
                                    excludedFilterName = this._excludedFilters[i];
                                    if(this._respondingFilters && !this._respondingFilters[excludedFilterName]) {
                                        searchFilter = this._filters.allFilters[excludedFilterName];
                                        if(searchFilter && searchFilter.filters) {
                                            if(searchFilter.filters.data[0])
                                                searchFilter.filters.data[0] = null;
                                            else
                                                searchFilter.filters.data = null;
                                        }
                                    }
                                }
                            }
                            this.fire("send", this._filters, this._params);
                        }
                    })
                    /**
                     *  Triggered immediately before a search. This gives widgets an opportunity to prevent a search from taking
                     *  place and prepare for the search to occur (i.e. the reportDisplay widgets will render their waiting .gif)
                     */
                    ._addEventHandler("send", {
                        pre: function(eo) {
                            //If we don't have an eo then a search hasn't been previously performed. Use AJAX as our default search method.
                            if(!this._originalEventObject) {
                                this._originalEventObject = {filters: {page: 1, newPage: false}};
                            }
                            this._eventCancelled = false;
                        },
                        during: function(eo) {
                            if (eo === false) {
                                this._eventCancelled = true;
                            }
                        },
                        post: function(eo) {
                            if (!this._eventCancelled) {
                                this._excludedFilters = [];
                                Search.go(this._originalEventObject.filters, this._filters, this._params, this.searchSource, this);
                            }
                        }
                    })
                    /**
                     *  Triggered when the the page is loaded by the resultDisplay widgets so that the initial filter values can be
                     *  stored in case a user is using the history to navigate through pages and needs to revert to the initial state.
                     */
                    ._addEventHandler("setInitialFilters", {
                        post: function(eo) {
                            this._setInitialFilters(eo);
                        }
                    })._addEventHandler("appendFilter", {
                        post: function(eo) {
                            if (!this._originalEventObject) {
                                this._originalEventObject = RightNow.Lang.cloneObject(eo);
                            }
                            this._mergeFilters(eo);
                        }
                    })
                    /**
                     *  Used by the displaySearchFilters widget to notify us if a widget is being removed from a search. In rare cases,
                     *  if a search filter widget isn't on a page, but has been applied to a prior search (typically via URL parameter)
                     *  then the displaySearchFilters widget needs to be able to remove that filter from a search. This is the mechanism
                     *  allowing that use case.
                     */
                    ._addEventHandler("excludeFilterFromNextSearch", {
                        post: function(eo) {
                            if(!this._excludedFilters)
                                this._excludedFilters = [];
                            this._excludedFilters.push(eo.data.name);
                        }
                    })._addEventHandler("reset")
                    .on("reset", function() {
                        // Restore filter and generic parameter states to their initial page-load state.
                        this._filters = RightNow.Lang.cloneObject(this._initialFilters);

                        if (this._params && this._filters && this._filters.allFilters) {
                            var filters = this._filters.allFilters;
                            if (this._initialParams) {
                                this._params = RightNow.Lang.cloneObject(this._initialParams);
                            }
                            // Set all params that're mirroring search filter values to the reset search filter value
                            this.Y.Object.each(this._params, function(value, key, params) {
                                if (filters[key] && filters[key].filters && 'data' in filters[key].filters) {
                                    params[key] = filters[key].filters.data;
                                }
                            });
                        }
                    }, this);
                }
            },
            clearCache: function() {
                if (History.enabled) {
                    var cacheKey = this.searchSource.type + this.searchSource.id;
                    History.clearCache(cacheKey);
                }
            },
            doesCacheExist: function() {
                if (History.enabled) {
                    return History.doesCacheExist(this.searchSource.type + this.searchSource.id);
                }
                return false;
            },
            _setFilters: function(filters) {
                this._filters = filters;
            },
            _setInitialFilters: function(eo) {
                if (eo instanceof RightNow.Event.EventObject) {
                    if (!this.Y.Object.isEmpty(eo.filters)) {
                        this._filters = eo.filters;
                        this._initialFilters = RightNow.Lang.cloneObject(eo.filters);
                    }
                    if (!this.Y.Object.isEmpty(eo.data)) {
                        this._params = eo.data;
                        this._initialParams = RightNow.Lang.cloneObject(eo.data);
                    }
                }
            },
            _mergeFilters: function(eo) {
                //Use a special case of mix that will deep merge all the properties of allFilters and filters, but give
                //precedence to those properties in filters.
                this._filters.allFilters = this.Y.mix(this._filters.allFilters, eo.filters, true, null, 0, true);
            },
            _collectSearchFilters: function(eo) {
                //Clone the passed in search filters so that we don't inadvertently modify them
                eo = new RightNow.Event.EventObject({instanceID: eo.w_id}, RightNow.Lang.cloneObject(eo));
                if (eo.filters.searchName) {
                    this._respondingFilters || (this._respondingFilters = {});
                    this._respondingFilters[eo.filters.searchName] = true;

                    this._filters || (this._filters = {});
                    this._filters.allFilters || (this._filters.allFilters = {});
                    this._filters.allFilters[eo.filters.searchName] = eo;
                }
                else if (this.searchSource.type === "generic") {
                    // generic search params for searches other than reports
                    this._params = this.Y.mix(this._params || {}, eo.data, true);
                }
            }
        });

    /**
     * @private
     * @constructor
     * @see SourceConductor.multipleSourcesWrapper
     */
    function _multipleSourcesWrapper(sources) {
        if (!sources || !sources.length || sources.length < 2)
            throw new Error("You're doing it wrong");
        this.sources = sources;
        this.multiple = true;
    }
    _multipleSourcesWrapper.prototype = {
        _invoke: function(method, name, eventObject, args) {
            for (var i = 0; i < this.sources.length; i++) {
                this.sources[i][method](name, eventObject, args);
            }
            return this;
        },
        on: function(name, eventObject, args) {
            return this._invoke("on", name, eventObject, args);
        },
        fire: function(name, eventObject, args) {
            return this._invoke("fire", name, eventObject, args);
        }
    };

return {
    /**
     * Wraps search sources for widget instances that have more than one search source.
     */
    multipleSourcesWrapper: _multipleSourcesWrapper,

    /**
     * Returns the search source specified by the key, or returns all search sources if no key specified
     * @param {string} key Key of the desired search source
     * @return {(Object|Array)} The desired search source or all search sources
     */
    get: function(key) {
        return ((typeof key === "undefined") ? _searchSources : _searchSources[key]);
    },

    /**
     * Adds a new searchSource instance to the collection of search sources.
     * @param {string} key Key of the new search source (e.g. "report176")
     * @param {Object} source A new searchSource instance to add
     * @return {?Object} source or null if a key wasn't supplied, source isn't a searchSource instance or the searchSource already exists in the list
     */
    add: function(key, source) {
        if (key && source instanceof _searchSource && !_searchSources[key]) {
            return (_searchSources[key] = source);
        }
    },

    /**
    * Given report and source strings, returns an object.
    * @param {string} reportID Contains one or more comma-separated report ids
    * @param {string} sourceID Contains one or more comma-separated generic search source names
    * @return {Object} contains the following keys:
    *   'report': {Array} report ids (e.g. ["176", "192"])
    *   'source': {Array} generic source ids (e.g. ["foo", "social"])
    *   'keys': {Array} All report and generic search sources (e.g. [{type:"report",id:"176"},{type:"report",id:"192"},{type:"generic",id:"foo"},{type:"generic",id:"social"}])
    */
    getSearchSources: function(reportID, sourceID) {
        var report = ((reportID) ? (reportID + "").split(",") : []),
            source = ((sourceID) ? (sourceID + "").split(",") : []),
            keys = [], i;
        // this order (reports before generics) is very important, since report sources contain a
        // superset of the filters in generic sources
        // if we're going to do a page flip, we want to process a report source and hope that all relevant filters
        // are added before we navigate to the new page
        for (i = 0; i < report.length; i++) {
            keys.push({type: "report", id: report[i]});
        }
        for (i = 0; i < source.length; i++) {
            keys.push({type: "generic", id: source[i]});
        }
        return {
            report: report,
            source: source,
            keys: keys
        };
    },

    /**
     * Search source object that provides events:
     *  'setInitialFilters': Usually fired by a ResultsDisplay widget (Grid, Multiline) when the page initially loads to set up the initial state of all search filters
     *  'search': Usually fired by a SearchButton widget; subscribed to by search filter widgets to report back their filter state
     *  'send': Fired just prior to sending the request to the server; may be cancelled by returning false from a subscriber
     *  'reset': Fired should the source be needed to reset to its initial page-load state
     * @version 1.0
     * @requires RightNow.EventProvider
     */
    searchSource: _searchSource,

    /**
     * Given the name (id) of a source, returns the source / collection of sources.
     * @param {Object} widgetSources Contains all sources for the widget instance
     * @param {Object|string=} namedSource If unspecified, returns all sources for the widget instance;
     *      If a string, assumed to be name(s) of a widget instance's source (e.g. "172" or "172,social"); named source(s) are returned
     *      If an object, assumed to be keyed by names of sources; all named sources are returned
     *          keys of value object may be:
     *          'endpoint': {string} The server endpoint for the search ajax request (only for generic/custom search sources)
     *          'filters': {Array} Names of report filters whose names-values should be sent to the server as part of the request
     *          'params': {Object} key-vals that should be sent to the server as part of the request
     *      E.g. {"social": {"endpoint": "/cc/ajaxCustom/foo", "filters": ["keyword", "sort"], "params": {"send": "this"}}}
     * @return {Object} All relevant search sources based on the value of namedSource
     * @throws {Error} if widgetSources is null or undefined
     * @version 1.0
     */
    findNamedSource: function(widgetSources, namedSource) {
        if (namedSource) {
            this.findNamedSource.findSource || (this.findNamedSource.findSource = function(name, widgetSources) {
                if (widgetSources.multiple) {
                    for (var i = 0; i < widgetSources.sources.length; i++) {
                        if (widgetSources.sources[i].instanceID === name) {
                            return widgetSources.sources[i];
                        }
                    }
                }
                else if (widgetSources.instanceID === name) {
                    return widgetSources;
                }
            });
            this.findNamedSource.warning || (this.findNamedSource.warning = function(id) {
                if (RightNow.UI.DevelopmentHeader) {
                    RightNow.UI.DevelopmentHeader.addJavascriptWarning(
                        RightNow.Text.sprintf(RightNow.Interface.getMessage("PCT_D_SRCH_SRC_ID_RPT_ID_SRC_ID_LBL"), id)
                    );
                }
            });

            var foundSources = [],
                notFound = [];

            if (typeof namedSource === "string" || typeof namedSource === "number") {
                namedSource = (namedSource + "").split(",");
                if (namedSource.length > 1) {
                    for (var i = 0, found; i < namedSource.length; i++) {
                        if (found = this.findNamedSource.findSource(namedSource[i], widgetSources)) {
                            foundSources.push(found);
                        }
                        else {
                            notFound.push(namedSource[i]);
                        }
                    }
                    if (notFound.length) {
                        // One or more of the called-out source ids doesn't exist.
                        // Issue a warning and default to returning the found ones.
                        this.findNamedSource.warning(notFound.join(', '));
                    }
                    if (foundSources.length) {
                        return ((foundSources.length > 1) ? new _multipleSourcesWrapper(foundSources) : foundSources[0]);
                    }
                }
                else {
                    // called out a single search source
                    var singleSource = this.findNamedSource.findSource(namedSource[0], widgetSources);
                    if (singleSource) return singleSource;
                }

                // The called-out source doesn't exist. Issue a warning (if we haven't already) and default to returning all of the widget's sources
                !notFound.length && this.findNamedSource.warning(namedSource[0]);
                return widgetSources;
            }
            else if (typeof namedSource === "object") {
                // Expecting:
                // {mySourceName: {endpoint: "/cc/foo", filters: "keyword,sort", params: {foo: "bar"}}}
                var source, name;
                for (name in namedSource) {
                    if (namedSource.hasOwnProperty(name)) {
                        if ((source = this.findNamedSource.findSource(name, widgetSources))) {
                            source = YUI().mix(source, namedSource[name]);
                            foundSources.push(source);
                        }
                        else {
                            notFound.push(name);
                        }
                    }
                }
                if (notFound.length) {
                    // One or more of the called-out source ids doesn't exist.
                    // Issue a warning and default to returning the found ones.
                    this.findNamedSource.warning(notFound.join(', '));
                }
                if (foundSources.length) {
                    return ((foundSources.length > 1) ? new _multipleSourcesWrapper(foundSources) : foundSources[0]);
                }
            }
        }
        if (!widgetSources) {
            throw Error("The widget extending from RightNow.SearchFilter doesn't have report_id or source_id attributes.");
        }
        return widgetSources;
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
 *     'appendFilter' - Append a filter to the list of active filters
 *     'setInitialFilters' - Set the initial page load filters.
 * @requires RightNow.Widgets
 * @constructor
 */
RightNow.SearchFilter = RightNow.Widgets.extend(/**@lends RightNow.SearchFilter*/{
    /**
     * Creates a searchSource method that can be used to subscribe to search filter events.
     */
    constructor: function() {
        var source = SourceConductor.getSearchSources(this.data.attrs.report_id, this.data.attrs.source_id),
            sources = [];

        for (var i = 0, existingSource, key; i < source.keys.length; i++) {
            key = source.keys[i];
            existingSource = SourceConductor.get(key.type + key.id);
            if (!existingSource) {
                existingSource = new SourceConductor.searchSource(this.Y, key);
                SourceConductor.add(key.type + key.id, existingSource);
            }
            sources.push(existingSource);
        }
        if (sources.length > 1) {
            existingSource = new SourceConductor.multipleSourcesWrapper(sources);
        }
        this.searchSource = function(namedSource) {
            return SourceConductor.findNamedSource(existingSource, namedSource);
        };
    }
});
RightNow.ResultsDisplay = RightNow.SearchFilter;
})();
