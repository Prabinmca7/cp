RightNow.Widgets.CommunitySearchResults = RightNow.ResultsDisplay.extend({
    overrides: {
        constructor: function() {
            this.parent();
            
            this._resultsElement = this.Y.one(this.baseSelector + "_Content");
            this._loadingElement = this.Y.one(this.baseSelector + "_Loading");
            this._searchTerm = this.data.js.searchTerm;
            this._searchedOnKeyword = this.data.js.searchTerm;
        
            if (RightNow.Event.isHistoryManagerFragment())
                this._setLoading(true);

            var source = this.data.attrs.source_id,
                options = {},
                pagesContainer;
            options[source] = {endpoint: this.data.attrs.community_search_ajax};
            this.searchSource(options)
                .on("response", this._onResultsChanged, this)
                .on("search", this._searchRequest, this)
                .on("send", function() { this._setLoading(true); }, this)
                .on("keywordChanged", function(eo, args) { 
                    this._searchTerm = args[0].data || "";
                }, this);

            if (this.data.attrs.pagination_enabled && (pagesContainer = this.Y.one(this.baseSelector + "_Pagination"))) {        
                this._currentPage = this.data.js.currentPage;
                pagesContainer.delegate("click", this._onPageChange, "a", this);
            }
        }
    },

    /**
     * Event handler for when a search is requested.
     */
    _searchRequest: function() {
        if (this._searchedOnKeyword !== this._searchTerm) {
            this._currentPage = 1;
        }
        return new RightNow.Event.EventObject(this, {data: {
            w_id: this.data.info.w_id,
            page: this._currentPage
        }});
    },

    /**
    * Changes the loading icon and hides/unhide the data.
    * @param {Boolean} loading Whether to add or remove the loading indicators
    */
    _setLoading: function(loading) {
        if (this._resultsElement && this._loadingElement) {
            var method, toOpacity, ariaBusy;
            if (loading) {
                ariaBusy = true;
                method = "addClass";
                toOpacity = 0;
                
                //keep height to prevent collapsing behavior
                this._resultsElement.setStyle("height", this._resultsElement.get("offsetHeight") + "px");
            }
            else {
                ariaBusy = false;
                method = "removeClass";
                toOpacity = 1;
                
                //now allow expand/contract
                this._resultsElement.setStyle("height", "auto");
            }
            document.body.setAttribute("aria-busy", ariaBusy + "");
            //IE rendering: so bad it can't handle eye-candy
            if (this.Y.UA.ie && this.Y.UA.ie < 9) {
                this._resultsElement[method]("rn_Hidden");
            }
            else{
                this._resultsElement.transition({
                    opacity: toOpacity,
                    duration: 0.4
                });
            }
            this._loadingElement[method]("rn_Loading");
        }
    },

    /**
     * Event handler received when results are returned.
     * @param {String} type The event name
     * @param {Array} args The event arguments
     */
    _onResultsChanged: function(type, args) {
        args = args[0];
        var newData = args.data.searchResults,
            totalResults = args.data.totalCount,
            widgetElement,
            attributes = this.data.attrs,
            content = '',
            results = newData && newData.length;

        if (!this._resultsElement) return;

        this._resultsElement.set("innerHTML", new EJS({text: this.getStatic().templates.results}).render({
            attributes: attributes,
            ssoToken: args.ssoToken || '',
            newData: newData,
            baseUrl: attributes.author_link_base_url || RightNow.Interface.getConfig("COMMUNITY_BASE_URL", "RNW"),
            fullResultsUrl: this.data.js.fullResultsUrl
        }));

        if (attributes.hide_when_no_results && (widgetElement = this.Y.one(this.baseSelector))) {
            widgetElement[((results) ? "removeClass" : "addClass")]("rn_Hidden");
        }

        this._updatePagination(totalResults);
        this._setLoading(false);
        this._currentlyChangingPage = false;
        this._searchedOnKeyword = args.filters.keyword || "";
    },
    
    /**
    * Updates the pagination links.
    * @param {Number} totalResults The total number of results for the search
    */
    _updatePagination: function(totalResults) {
        var pagesContainer = this.Y.one(this.baseSelector + "_Pages"),
            paginationElement = this.Y.one(this.baseSelector + "_Pagination"),
            attributes = this.data.attrs;
        if (!attributes.pagination_enabled || !pagesContainer) {
            return;
        }
        if (totalResults === 0) {
            RightNow.UI.hide(paginationElement);
            return;
        }

        this._currentPage = this._currentPage || 1;
        var totalPages = Math.ceil(totalResults / attributes.limit),
            maxPagesToDisplay = attributes.maximum_page_links,
            split, offsetFromMiddle, maxOffset,
            cssFunction, navButton;
        //calculate what range of page numbers to display
        if (maxPagesToDisplay === 0) {
            this.data.js.startPage = this.data.js.endPage = this._currentPage;
        }
        else if (totalPages > maxPagesToDisplay) {
            split = Math.round(maxPagesToDisplay / 2);
            if(this._currentPage <= split) {
                this.data.js.startPage = 1;
                this.data.js.endPage = maxPagesToDisplay;
            }
            else {
                offsetFromMiddle = this._currentPage - split;
                maxOffset = offsetFromMiddle + maxPagesToDisplay;
                if (maxOffset <= totalPages) {
                    this.data.js.startPage = 1 + offsetFromMiddle;
                    this.data.js.endPage = maxOffset;
                }
                else {
                    this.data.js.startPage = totalPages - (maxPagesToDisplay - 1);
                    this.data.js.endPage = totalPages;
                }
            }
        }
        else {
            this.data.js.startPage = 1;
            this.data.js.endPage = totalPages;
        }

        pagesContainer.set("innerHTML", new EJS({text: this.getStatic().templates.pagination}).render({
            startPage: this.data.js.startPage,
            endPage: this.data.js.endPage,
            totalPages: totalPages,
            currentPage: this._currentPage,
            pageLabel: attributes.label_page
        }));
        
        //forward button
        if (navButton = this.Y.one(this.baseSelector + "_Forward")) {
            navButton[((totalPages > this._currentPage) ? "removeClass" : "addClass")]("rn_Hidden").setAttribute("data-page", this._currentPage + 1);
        }
        //back button
        if (navButton = this.Y.one(this.baseSelector + "_Back")) {
            navButton[((this._currentPage > 1) ? "removeClass" : "addClass")]("rn_Hidden").setAttribute("data-page", this._currentPage - 1);
        }
        
        paginationElement[((totalPages > 1) ? "removeClass" : "addClass")]("rn_Hidden");
    },
    
    /**
    * Called when a pagination link is clicked.
    * @param {Object} evt click event
    */
    _onPageChange: function(evt) {
        var pageIndex = Math.max(parseInt(evt.target.getAttribute("data-page"), 10), 1);
        if (!this._currentlyChangingPage) {
            this._currentlyChangingPage = true;
            if (pageIndex === this._currentPage) {
                this._currentlyChangingPage = false;
                return;
            }
            this._currentPage = pageIndex;
            this.searchSource(this.data.attrs.source_id).fire("search", new RightNow.Event.EventObject(this));
        }
    }
});
