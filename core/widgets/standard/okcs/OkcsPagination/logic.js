RightNow.Widgets.OkcsPagination = RightNow.Widgets.SourcePagination.extend({
    overrides: {
        constructor: function () {
            this.parent();
            this._pageDirection = '';
            this._resultCount = '';
            var direction = RightNow.Url.getParameter('dir');
            if(!!direction) {
                this.currentPageNumber = direction === 'forward' ? parseInt(RightNow.Url.getParameter('page'), 10) + 1 : parseInt(RightNow.Url.getParameter('page'), 10) - 1;
            }
            else {
                this.currentPageNumber = 1;
            }
            this._loadingDiv = this.Y.one('#' + this.data.attrs.dom_id_loading_icon);
            this.Y.one(this.baseSelector).delegate('click', this.onPageClick, 'a', this);
            this.searchSource().setOptions(this.data.js.sources).on('response', this.onSearchComplete, this);
            if (this.data.js.okcsAction !== 'browse') {
                RightNow.Event.subscribe("evt_facetResponse", this._selectedFacetResponse, this);
                this.searchSource().on('collect', this.sendOkcsPageDirection, this)
                                   .on('collect', this.sendFilterPage, this);
            }
            if(this.data.js.okcsAction === 'browse') {
                this.searchSource().on('collect', this.sendOkcsBrowseAction, this)
                .on('collect', this.sendOkcsBrowsePage, this);
            }

            this.Y.one(this.baseSelector).delegate('mouseup', this._onRightClick, 'li a', this);
        },

        /**
         * Fires an event to fetch list of selected factes.
         * @param  {object} e Click event
        */
        _onRightClick: function (e) {
            if (e.button === 3) {
                if(this.data.js.okcsAction === 'browse') {
                    if (this.data.attrs.data_source === "authoring_recommendations") {
                        this.searchSource().fire('collect');
                        this._updateBrowsePageNavigationUrl('', e);
                    }
                    else {
                        this.searchSource().fire('collect');
                        this.contentType = this.searchSource().filters.channelRecordID.value;
                        this._updateBrowsePageNavigationUrl(this.contentType, e);
                    }
                }
                else {
                    RightNow.Event.fire("evt_selectedFacet", new RightNow.Event.EventObject(this, {data : { pageLink : e }}));
                }
            }
        },

        /**
         * Updates the browse page Hypertext Reference
         * @param {string} contentType Content Type
         * @param {object} e Click event
         */
        _updateBrowsePageNavigationUrl: function(contentType, e) {
            var currentPageNumber = parseInt(this.data.js.currentPage, 10);
            var urlParameters = location.pathname.split("/");
            currentPageNumber = currentPageNumber > 0 ? currentPageNumber : 1;
            this._pageDirection = e.currentTarget.getAttribute('data-rel');
            var navigatePage = this._pageDirection === 'next' ? (currentPageNumber + 1) : (currentPageNumber - 1);
            if (!window.location.origin) {
                window.location.origin = window.location.protocol + "//" + window.location.hostname + (window.location.port ? ':' + window.location.port : '');
            }
            var navigationUrl = location.origin + location.pathname.split("/").slice(0, RightNow.Url.getParameterSegment() - 1).join("/");
            var validParameters = ['pageSize', 'limit', 'sortColumn', 'sortDirection', 'truncate', 'categoryRecordID', 'isCategorySelected', 'productRecordID', 'isProductSelected'],
                paramIndex;
            for(var i = 1; i < urlParameters.length; i = i + 2) {
                paramIndex = this.Y.Array.indexOf(validParameters, urlParameters[i]);
                if(paramIndex !== -1) {
                    navigationUrl += ('/' + validParameters[paramIndex] + '/' + urlParameters[i + 1]);
                }
            }
            if(contentType !== '')
                navigationUrl = RightNow.Url.addParameter(navigationUrl, 'channelRecordID', contentType);
            navigationUrl += '/browsePage/' + navigatePage + '/browseType/newTab';
            navigationUrl = RightNow.Url.addParameter(navigationUrl, "session", RightNow.Url.getSession());
            e.currentTarget.setAttribute('href', navigationUrl);
        },

        /**
         * Event handler when returning from ajax data request
         * @param {string} type Event name
         * @param {array} args Event arguments
         */
        _selectedFacetResponse: function(type, args) {
            var facet = args[0].data.facet,
                e = args[0].data.pageLink,
                currentPageNumber = parseInt(this.data.js.currentPage, 10) + 1;
            var urlParameters = location.pathname.split("/");
            if(this.data.js.okcsAction !== "browse"){
                if(RightNow.Url.getParameter('searchType') === 'newTab'){
                    currentPageNumber = parseInt(RightNow.Url.getParameter('page'), 10);
                }
            }
            this._pageDirection = e.currentTarget.getAttribute('data-rel');
            var navigatePage = this._pageDirection === 'next' ? (currentPageNumber + 1) : (currentPageNumber - 1);
            //Fix to support window.location.origin in IE 9
            if (!window.location.origin) {
                window.location.origin = window.location.protocol + "//" + window.location.hostname + (window.location.port ? ':' + window.location.port : '');
            }
            var navigationUrl = location.origin + location.pathname.split("/").slice(0, RightNow.Url.getParameterSegment() - 1).join("/");
            for(var i = 1; i < urlParameters.length; i = i + 2) {
                if(urlParameters[i] === 'kw')
                    navigationUrl += '/kw/' + urlParameters[i + 1];
                else if(urlParameters[i] === 'loc')
                    navigationUrl += '/loc/' + urlParameters[i + 1];
            }
            if(facet !== '')
                navigationUrl = RightNow.Url.addParameter(navigationUrl, 'facet', facet);
            navigationUrl += '/page/' + navigatePage + '/searchType/newTab';
            navigationUrl = RightNow.Url.addParameter(navigationUrl, "session", RightNow.Url.getSession());
            if(this._resultCount)
                navigationUrl += '/resultCount/' + this._resultCount;
            e.currentTarget.setAttribute('href', navigationUrl); 
        },

        /**
         * Triggers a new search when a pagination link is clicked.
         * @param  {object} e Click event
         */
        onPageClick: function (e) {
            e.halt();
            this.searchSource().initialFilters = {};
            RightNow.Event.fire("evt_pageLoading");
            var pageNumber = this.data.js.currentPage;
            var urlParameters = location.pathname.split("/");
            if (this.data.js.okcsAction === "browse") {
                this._pageDirection = e.currentTarget.getAttribute('data-rel');
                if(pageNumber === 0 && this._pageDirection === 'next' && ('pushState' in window.history)){
                    pageNumber = pageNumber + 1;
                }
                var navigatePage = this._pageDirection === 'next' ? (pageNumber + 1) : (pageNumber - 1);
                this._pageDirection = (this._pageDirection === 'next') ? 'forward' : 'backward';
                this.triggerOkcsBrowsePaginate(navigatePage);
                this.data.js.browsePaginate = false;
                var pageNumber = this.determinePageNumber(e.currentTarget.getAttribute('data-rel'));
                return false;
            }
            if(RightNow.Url.getParameter('searchType') === 'newTab') {
                pageNumber = parseInt(RightNow.Url.getParameter('page'), 10) - 1;
            }
            this._pageDirection = e.currentTarget.getAttribute('data-rel');
            this._pageDirection = (this._pageDirection === 'next') ? 'forward' : 'backward';
            var navigatePage = this._pageDirection === 'forward' ? pageNumber + 1 : pageNumber - 1;
            if (this.data.js.pageMore === 0) {
                navigatePage = navigatePage + 2;
            }
            if(navigatePage === 0)
                navigatePage = '';
            this.data.js.filter.value = navigatePage;
            this.searchSource().fire('collect');
            this.searchSource().fire('search', new RightNow.Event.EventObject(this, {
                data: {
                    page: { value: this.currentPageNumber,
                            key: "page",
                            type: "page"},
                    sourceCount: 1
                }
            }));
            return false;
        },
        
        /**
         * Re-renders when new search results arrive.
         * @param  {string} evt  Event name
         * @param  {object} args Event object
         */
        onSearchComplete: function (evt, args) {
            this._pageDirection = '0';
            if(args[0].data && args[0].data.filters && args[0].data.filters.resultCount)
                this._resultCount = args[0].data.filters.resultCount.value;
            var result = args[0].data;

            if (!result.error) {
                if (result.searchResults) {
                    this.fetchSearchResults(result);
                }
                else if (result.articles) {
                    this.fetchArticles(result);
                }
                else if (result.recommendations) {
                    this.fetchArticles(result);
                }
            }
        }
    },
    
    /**
    * This function collects all the filters and perform search.
    * @param navigatePage string requestedPage
    */
    triggerOkcsBrowsePaginate: function (navigatePage){
        this.data.js.browsePaginate = true;
        this.searchSource().fire('collect').fire("search", new RightNow.Event.EventObject(this, {data: {sourceCount: 1}}));
    },
    
    /**
    * Collects page direction filter
    * @return {object} Event object
    */
    sendOkcsPageDirection: function() {
        return new RightNow.Event.EventObject(this, {
            data: {value: this._pageDirection, key: 'dir', type: 'direction'}
        });
    },
    
    /**
    * Collects selected channelRecordID filter
    * @return {object} Event object
    */
    sendOkcsBrowseChannel: function() {
        if(!this.data.js.browsePaginate) return;
        return new RightNow.Event.EventObject(this, {
            data: {value: this.Y.one('.rn_ChannelSelected')._node.id, key: 'channelRecordID', type: 'channelRecordID'}
        });
    },
    
    /**
    * Collects browseAction filter
    * @return {object} Event object
    */
    sendOkcsBrowseAction: function() {
        if(!this.data.js.browsePaginate) return;
        return new RightNow.Event.EventObject(this, {
            data: {value: 'paginate', key: 'browseAction', type: 'browseAction'}
        });
    },
    
    /**
    * Collects browsePage filter
    * @return {object} Event object
    */
    sendOkcsBrowsePage: function() {
        if(!this.data.js.browsePaginate) return;
        var currPageNumber = this.data.js.currentPage;
        if(currPageNumber === 0 && this._pageDirection === 'forward'){
            currPageNumber = currPageNumber + 1;
        }
        var navigatePage = this._pageDirection === 'forward' ? (currPageNumber + 1) : (currPageNumber - 1);
        return new RightNow.Event.EventObject(this, {
            data: {value: navigatePage, key: 'browsePage', type: 'browsePage'}
        });
    },

    /**
    * Collects pagedirection filter
    * @return {object} Event object
    */
    sendFilterPage: function() {
        return new RightNow.Event.EventObject(this, {
            data: {value: this.currentPageNumber, key: 'page', type: 'page'}
        });
    },
    
    /**
    * Fetches results for SearchResult page
    * @param {object} result Search results object
    * @return void
    */
    fetchSearchResults: function(result) {
        var page = result.searchResults.page;
        this.data.js.currentPage = page;
        this.currentPageNumber = parseInt(page, 10) + 1;
        this.data.js.pageMore = result.searchResults.pageMore;
        if (result.searchResults.page !== this.data.js.filter.value)
            this.data.js.filter.value = result.searchResults.page;
    
        this.createPageLink(this.data.js.filter.value, result.searchResults.pageMore, false);
    },
    
    /**
    * Fetches articles from Result for Browse page
    * @param {object} result Search results object
    * @return void
    */
    fetchArticles: function(result) {
        var page = result.currentPage;
        this.data.js.currentPage = page;
        
        this.createPageLink(page, result.hasMore, true);
    },
    
    /**
    * Generates the previous or Next link depending on the page data
    * @param {string} page Page number
    * @param {boolean} hasMore 
    * @param {boolean} hasArticles 
    * @return void
    */
    createPageLink: function(page, hasMore, hasArticles) {
        var previousLink = '',
            nextLink = '',
            pages = '';
        this.data.js.currentPage = page;
    
        if ((page > 0 && this.data.js.okcsAction !== "browse") || (page > 1 && this.data.js.okcsAction === "browse")) {
            previousLink = new EJS({ text: this.getStatic().templates.navigationLink }).render({
                href: this.url(this.data.js.filter.value - 1),
                iconClass: 'icon-chevron-left',
                rel: 'previous',
                label: this.data.attrs.label_previous_page_link,
                className: 'rn_PreviousPage'
            });
        }
        if (hasMore > 0) {
            nextLink = new EJS({ text: this.getStatic().templates.navigationLink }).render({
                href: this.url(this.data.js.filter.value + 1),
                iconClass: 'icon-chevron-right',
                rel: 'next',
                label: this.data.attrs.label_next_page_link,
                className: 'rn_NextPage'
            });
        }
    
        if(hasArticles) {
            RightNow.Event.fire("evt_pageLoaded");
            if (this.data.attrs.data_source === "authoring_recommendations"){
                this.Y.one("#rn_OkcsManageRecommendations").show();
            }
            else {
                this.Y.one("#rn_OkcsAnswerList").show();
            }
        }
        this.Y.one(this.baseSelector + ' ul').setHTML(previousLink + pages + nextLink);
    }
});
