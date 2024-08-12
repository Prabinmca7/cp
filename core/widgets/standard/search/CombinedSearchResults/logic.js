RightNow.Widgets.CombinedSearchResults = RightNow.Widgets.Multiline.extend({
    overrides: {
        constructor: function() {
            this.parent();

            if (RightNow.Event.isHistoryManagerFragment()) {
                var results = this._getWidgetElement("Results");
                this._updateAriaAlert((results && results.all("li").size() > this.data.js.displayedComponents)
                    ? this.data.attrs.label_screen_reader_search_success_alert
                    : this.data.attrs.label_screen_reader_search_no_results_alert);
            }

            this._initializeCombinedSource();

            this._searchTerm = this.data.js.searchTerm;
            this._parameterList = this._numberOfResults = null;
            this._searching = this._searchedAdditionalSources = false;
            delete this.data.js.searchTerm;
            delete this.data.js.format;
        },

        /**
        * Event handler received when search data is changing.
        * Shows progress icon during searches.
        */
        _searchInProgress: function(evt, args) {
            this._searching = true;
            this._searchAdditionalSources(evt, args);
            this.parent(evt, args);
        },

        /**
         * Event handler received when report data changes.
         * @private
         * @param {String} type Event name
         * @param {Array} args Arguments passed with event
         */
        _onReportChanged: function(type, args) {
            var newData = args[0].data;

            if ('social' in newData) return;

            this._parameterList = (this.data.attrs.add_params_to_url && args[0].filters.allFilters)
                ? RightNow.Url.buildUrlLinkString(args[0].filters.allFilters, this.data.attrs.add_params_to_url)
                : '';

            var currentPageSize = newData.per_page,
                cols = newData.headers.length,
                report = this._getWidgetElement("Content"),
                answerIDIndex = this.data.js.answerIDIndex,
                html = "",
                answerIDs = {};

            if(!report) return;

            if (newData.total_num > 0) {
                newData.hide_empty_columns = this.data.attrs.hide_empty_columns;
                html = new EJS({text: this.getStatic().templates.view}).render(newData);

                if (answerIDIndex) {
                    for (var i = 0; i < currentPageSize; i++) {
                        answerIDs[newData.data[i][answerIDIndex]] = i;
                    }
                }
            }
            report.set("innerHTML", html);
            this._numberOfResults += currentPageSize;
            this._kbLoaded = true;
            if(this._searching && !this._searchedAdditionalSources) {
                this._searchCompleted();
            }
        }
    },

    /**
     * Sets up the searchSource instance and DOM listeners for social results.
     */
    _initializeCombinedSource: function() {
        var attributes = this.data.attrs,
            source = attributes.source_id,
            sources = false,
            options = {},
            initial = new RightNow.Event.EventObject(this, {data: {
                w_id: this.data.info.w_id
            }});

        if (attributes.social_results) {
            sources = true;
            initial.data.social = true;
            this._watchExpanders(this._getWidgetElement("Social", true));
        }

        options[source] = {endpoint: attributes.combined_search_ajax};
        this.searchSource(options)
                .on('send', this._searchAdditionalSources, this)
                .on('response', this._searchResponse, this);

        if (sources && RightNow.Event.isHistoryManagerFragment()) {
            this._searchedAdditionalSources = true;
        }
        else {
            this.searchSource(source).fire('setInitialFilters', initial);
        }
    },

    /**
     * Event handler that's called when a search is triggered for social
     * @return {object|null} EventObject or false if no search sources are configured
     *  to display for the widget
     */
    _searchAdditionalSources: function(evt, args) {
        // Don't search additional sources on pages other than the first.
        // Returning `false` cancels the 'send' event.
        var additionalSearchSource = args[1] && (args[1].w_id !== 0);
        var args = args[0],
            page = (args && args.filters) ? args.filters.page : ((args && args.allFilters) ? args.allFilters.page : null);

        if (page && page > 1) {
            this._searchedAdditionalSources = false;
            return false;
        }

        if (additionalSearchSource && this.data.attrs.social_results) {
            this._searching = true;
            this._searchedAdditionalSources = true;
        }
        else {
            return false;
        }
    },

    /**
     * Event handler received when new social data is returned.
     * @private
     * @param {String} type Event name
     * @param {Array} args Arguments passed with event
     */
    _searchResponse: function(type, args) {
        var data = args[0].data,
            results = 0;
        if (data.social) {
            results++;
            this._showSocialResults(data.social);
        }
        if (!results) {
            this._searchCompleted();
        }
    },

    /**
     * Central method for onResponse methods to report back in that a response was
     * received for each result component. Hides the loading icon, adds top-level class
     * names, adds aria alerts, and focuses the first link.
     * @private
     */
    _searchCompleted: function() {
        RightNow.Url.transformLinks(this._getWidgetElement("Content"));
        this._updateAriaAlert((this._numberOfResults)
            ? this.data.attrs.label_screen_reader_search_success_alert
            : this.data.attrs.label_screen_reader_search_no_results_alert);
        this._setLoading(false);
        var anchor = this._getWidgetElement().one('a');
        if(anchor) {
            anchor.focus();
        }

        this._searchedAdditionalSources = false;
        this._searching = false;
        this._kbLoaded = false;
        this._numberOfResults = 0;
    },

    /**
     * Event handler received when social data changes.
     * @private
     * @param {Object} response Search result data
     */
    _showSocialResults: function(response) {
        var newData = response.data.results,
            html = "",
            containerElement = this._createSubBlockContainer("Social");

        if (newData && newData.length) {
            html = new EJS({text: this.getStatic().templates.socialView}).render({
               message: {
                   expand: RightNow.Interface.getMessage("CLICK_TO_EXPAND_CMD")
               },
               attrs: this.data.attrs,
               ssoToken: response.ssoToken || '',
               data: newData,
               authorLink: ((this.data.attrs.author_link_base_url) ? (this.data.attrs.social_author_link_base_url || (RightNow.Interface.getConfig("COMMUNITY_BASE_URL", "RNW") + response.ssoToken || '')) : false),
               sprintf: RightNow.Text.sprintf
            });
        }
        containerElement.set("innerHTML", html);
        this._numberOfResults += (newData) ? newData.length : 0;
        this._queueInjection({element: containerElement, position: this.data.attrs.social_results});
    },

    /**
     * Inserts sub-blocks of results. Notifies searchCompleted when all results have returned.
     * @private
     */
    _performInjection: function() {
        if(this._queue.length) {
            var i = 0,
                queue = this._queue;
            for(i = 0; i < queue.length; i++) {
                this._injectBlockIntoResults(queue[i].element, queue[i].position, (i !== queue.length - 1) ? queue[i + 1].position : null);
            }
            this._searchCompleted();
            this._queue = [];
        }
    },

    /**
    * Stashes the given object for later retrieval.
    * @private
    * @param {Object} procedure Contains element & position keys
    * @return {Number|Boolean} Length of the queue or false if not currently searching
    */
    _queueInjection: function(procedure) {
        if(this._searching) {
            this._queue = this._queue || [];
            this._queue.push(procedure);

            if(this._queue.length === 1) {
                var readyToGo = function() {
                    return this._queue.length === this.data.js.displayedComponents && (!this.data.attrs.display_knowledgebase_results || this._kbLoaded);
                },
                interval,
                allowedTime = 5000 /* 5 seconds */,
                queueIsReady,
                startTime = new Date().getTime();

                interval = this.Y.Lang.later(50, this, function(time) {
                    queueIsReady = readyToGo.call(this);
                    if(queueIsReady || ((new Date().getTime() - startTime) >= allowedTime)) {
                        interval.cancel();
                        // tell method what the situation is:
                        // queue is full or it's been waited on long enough
                        this._performInjection();
                    }
                }, null, true);
            }
            return this._queue.length;
        }
        return false;
    },

    /**
    * Injects an HTML element into the outer list of KB results.
    * If there aren't any KB results, element is inserted as a
    * new list within the widget content.
    * @private
    * @param {Object} toInject HTML element to insert
    * @param {Number} position Position to insert the element at within KB results
    * @param {?Number} positionOfNextBlock Position of next block or null if there is no next block
    */
    _injectBlockIntoResults: function(toInject, position, positionOfNextBlock) {
        var mainResults = this._getWidgetElement("Content"),
            list,
            listItems;
        if(mainResults) {
            position = (positionOfNextBlock && positionOfNextBlock < position) ? position - 1 : position;
            if (toInject.get("innerHTML") && mainResults.get("children").size() && (list = mainResults.all('ol, ul')) && list.size() === 1) {
                listItems = list.item(0).get("children");
                if (listItems.size() > (position - 1)) {
                    listItems.item(position - 1).insert(toInject, "before");
                }
                else {
                    list.item(0).get("lastChild").insert(toInject, "after");
                }
            }
            else {
                if (mainResults.get("children").size()) {
                    var existing = mainResults.all("li." + toInject.get("className"));
                    list = existing.get('parentNode');
                    list = (list.item) ? list.item(0) : null;
                    existing.remove();
                }
                if (!list) {
                    list = this.Y.Node.create("<ul>");
                    if(mainResults.get("children").get("length") > (position - 1)) {
                        mainResults.get("children").item(position - 1).insert(list, "before");
                    }
                    else {
                        mainResults.appendChild(list);
                    }
                }
                list.appendChild(toInject);
            }
        }
    },

    /**
     * Adds event listeners to sub-blocks of result content to show any initially-hidden results.
     * @param {String} parentID The id of the parent to watch for result-block expansion
     * @private
     */
    _watchExpanders: function(parentID) {
        this.Y.one(this.baseSelector).delegate("click", function(e) {
            e.halt();
            var clicked = e.target,
                method = (clicked.get("className").indexOf("rn_Heading") > -1) ? "next" : "previous",
                list = clicked[method]("ul.rn_Links"),
                i, collapsed, firstLink, moreLink;
            if (list) {
                var count = 0;
                list.all('li.rn_Hidden, li.rn_Shown').each(function(li) {
                    var swap;
                    if (li.hasClass('rn_Hidden')) {
                        swap = ['rn_Hidden', 'rn_Shown'];
                        if (!firstLink) {
                            firstLink = li;
                        }
                    }
                    else {
                        swap = ['rn_Shown', 'rn_Hidden'];
                        collapsed = true;
                    }
                    li.replaceClass(swap[0], swap[1]);
                });
            }

            firstLink = (firstLink || list.get("children").item(0)).one("a");
            if (firstLink) {
                firstLink.focus();
            }
            moreLink = clicked.ancestor('ul').one('.rn_More');
            if (moreLink) {
                moreLink.toggleClass('rn_Hidden');
            }
        }, parentID + ' a.rn_More,' + parentID + ' a.rn_Heading');
    },

    /**
     * Returns the element specified by a suffixed string. Caches a found
     * element so that the DOM is queried for the ID only once for subsequent requests.
     * @private
     * @param {String=} idSuffix The suffix (after 'rn_widgetName_id_') (optional)
     *      if unspecified the top-level widget container is returned.
     * @param {Boolean=} justID Whether to return just the id rather than the Node
     * @return {?Object} The element or null if not found
     */
    _getWidgetElement: function(idSuffix, justID) {
        this._elements = this._elements || {};
        var domID = this.baseSelector + ((idSuffix) ? "_" + idSuffix : "");
        if (justID) return domID;

        if(idSuffix && (idSuffix.indexOf("Social") > -1)) {
            // since these container elements are liable to be nuked and re-added elsewhere
            // don't cache the dom elements
            return this.Y.one(domID) || this._createSubBlockContainer(idSuffix);
        }
        return this._elements[idSuffix || "topMostWidgetDiv"] ||
            (this._elements[idSuffix || "topMostWidgetDiv"] = this.Y.one(domID));
    },

    /**
    * Creates an li HTML element with the proper id and className.
    * @private
    * @param {String} idSuffixAndClassName Describes the element; used
    *       as the suffix of the element's id and as the className
    * @return {Object} The li element
    */
    _createSubBlockContainer: function(idSuffixAndClassName) {
        return this.Y.Node.create("<li></li>").set("id", this.baseDomID + "_" + idSuffixAndClassName).set("className", "rn_" + idSuffixAndClassName);
    }
});
