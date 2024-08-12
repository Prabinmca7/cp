RightNow.Widgets.SearchResult = RightNow.SearchConsumer.extend({
    overrides: {
        constructor: function() {
            this.parent();
            this._contentDiv = this.Y.one(this.baseSelector + "_Content");
            this._okcsSearchSession = this.data.js.okcsSearchSession;
            this._transactionID = this.data.js.transactionID;
            this._priorTransactionID = this.data.js.priorTransactionID;
            this._truncateSize = this.data.js.truncateSize;
            this._answerPageUrl = this.data.js.answerPageUrl;
            this._regExPattern = this.data.attrs.document_id_reg_ex;
            this._docIdNavigation = this.data.attrs.doc_id_navigation;
            this.resultCount = this.resultCountFilter = this.data.js.defaultCount;
            this._resultCountType = '';
            this._totalResults = this.data.js.totalResults;
            if(this.resultCount)
                this._initEvents();

            var searchSourceOptions = this.data.js.filter;
            this.Y.one(this.baseSelector).delegate('click', this._onFacetResetClick, 'a.rn_ClearResultFilters', this);
            searchSourceOptions.endpoint = this.data.js.sources[this.searchSource().sourceID].endpoint;
            this.searchSource().setOptions(searchSourceOptions)
                .on('collect', this.sendOkcsSession, this)
                .on('collect', this.sendTruncateSize, this)
                .on('collect', this.sendPriorTransactionID, this)
                .on('collect', this.sendSearchCacheId, this)
                .on('collect', this.sendTransactionID, this)
                .on('collect', this.sendRegExPattern, this)
                .on('collect', this.sendDocIdNavigation, this)
                .on('collect', this.sendResultCount, this)
                .on('collect', this.updateResultCountType, this)
                .on('collect', this.sendQuerySource, this)
                .on('search', this._searchInProgress, this)
                .on('searchCancelled', this._loadPageEvent, this)
                .on('response', this._onReportChanged, this)
                .fire('initializeFilters', new RightNow.Event.EventObject(this, {data: this.data.js.filter}));

            this._displayDialogIfError(this.data.js.error);
            this.Y.one(this.baseSelector).delegate('click', this.onResultClick, '.rn_Element1 a', this);

            this.Y.on('domready', function () {
                RightNow.Event.fire("evt_pageLoaded");
            });
        }
    },

    /**
    This method initializes events on the result count dropdown elements
    */
    _initEvents: function() {
        this._displayFieldVisibleText = this.Y.one(this.baseSelector + "_ResultCount");
        this._toggleElement = this.Y.one(this.baseSelector + "_DropdownButton");
        this._dropdownElement = this.Y.one(this.baseSelector + "_SubNavigation");
        this._dropdownElementHidden = this.Y.one(this.baseSelector + "_SubNavigationHidden");
        if(this._toggleElement && this._dropdownElement)
            this._keyEventTriggers = new this.Y.NodeList(this._toggleElement).concat(this._dropdownElement.all('li').setAttribute('tabindex', -1));
        this._listElement = this.Y.all('.rn_Item');
        if(this._keyEventTriggers)
            this._keyEventTriggers.on('keydown', this._onKeydown, this);
        if(this._listElement)
            this._listElement.on('mouseover', this._onMouseOver, this);

        if(!this._toggleElement || !this._dropdownElement) return;
        this._toggleElement.on('click', this._toggleDropdown, this);
        this._dropdownElement.on('click', this._updateResultCount, this);
    },

    /**
    This method destroys events on the result count dropdown elements
    */
    _destroyEvents: function() {
        if(this._displayFieldVisibleText) {
            this._displayFieldVisibleText.destroy;
            this._toggleElement.destroy;
            this._dropdownElement.destroy();
            this._dropdownElementHidden.destroy;
            this._keyEventTriggers.destroy;
            this._listElement.destroy;
        }
    },

   /**
    * Event Handler fired when a Clear facet Link is Clicked
    * @param {Object} evt Event object
    */
    _onFacetResetClick: function(evt)
    {
        evt.preventDefault();
        RightNow.Event.fire("evt_pageLoading");
        this._newSearch = false;
        this._facet = '';
        this._searchType = 'clearFacet';
        RightNow.Event.fire("evt_clearSelectedMultiFacets", evt);
        this.searchSource().fire('collect').fire('search', new RightNow.Event.EventObject(this, {
            data: {
                page: this.data.js.filter,
                sourceCount: 1
            }
        }));
    },

    /**
    * Event handler received when search has been cancelled.
    */
    _loadPageEvent: function() {
        RightNow.Event.fire("evt_pageLoaded");
    },
    
    /**
    * Event handler received when search data is changing.
    * Shows progress icon during searches.
    * @param {string} evt Event name
    * @param {args} args Arguments provided from event fire
    */
    _searchInProgress: function(evt, args) {
        RightNow.UI.hide(this._dropdownElement);
        RightNow.Event.fire("evt_pageLoading");
        this._reportResults = null;
        var newSearch = false;
        if (args[0] !== undefined) {
            var params = args[1];
            newSearch = (args[0].allFilters && args[0].allFilters.facet) === undefined ? true : false;
        }
    },

    /**
     * Event handler received when report data is changed.
     * @param {String} type Event name
     * @param {Array} args Arguments passed with event
     */
    _onReportChanged: function(type, args) {
        this._resultCountType = '';
        this.resultCountFilter = this.resultCount;
        this._dropdownOpen = false;
        if(this._docIdNavigation === 'true' && Array.isArray(args[0].data.results) && args[0].data.results.length > 0 && args[0].data.results[0].docIdSearch) {
            window.location.href = args[0].data.results[0].redirectUrl;
            return;
        }

        var newContent = "", results = undefined;
        RightNow.Event.fire("evt_pageLoaded");
        if (args[0] !== undefined && args[0].data.error !== undefined) {
            error = args[0].data.error;
            newContent = '<div id="' + this.baseDomID + '_Error" class="rn_ErrorMessage">' + error.errorCode + ': ' + error.externalMessage + ' - ' + error.source + '</div>';
        }
        else if(args[0] && args[0].data && args[0].data.filters !== undefined && args[0].data.filters !== null && args[0].data.filters.errors !== undefined && args[0].data.filters.errors !== null && args[0].data.filters.errors.indexOf("HTTP 0") !== -1) {
            newContent = '<div class="rn_NoSearchResultMsg">' + this.data.attrs.request_time_out_msg + '</div>';
        }
        else {
            if (args[0] && args[0].data.searchResults !== undefined && args[0].data.searchResults !== null && args[0].data.searchResults.results !== null) {
                results = args[0].data.searchResults.results.results;
            }
            if(args[0].data.searchState !== undefined && args[0].data.searchState !== null) {
                this._okcsSearchSession = args[0].data.searchState.session;
                this._transactionID = args[0].data.searchState.transactionID;
                this._priorTransactionID = args[0].data.searchState.priorTransactionID;
            }
            if (results && results.length > 0) {
                this.Y.one(this.baseSelector).removeClass("rn_NoSearchResult");
                this.Y.one(this.baseSelector + "_NoSearchResult").addClass("rn_Hidden");
                this._totalResults = results[0].totalResults;
                //detach old nodes and events
                if(this.resultCount)
                    this._destroyEvents();
                newContent = new EJS({text: this.getStatic().templates.view}).render({
                                        data: results[0].resultItems,
                                        title: this.data.attrs.label_results,
                                        answerPageUrl: this._answerPageUrl, 
                                        fileDescription: this.data.js.fileDescription, 
                                        searchSession: this._okcsSearchSession, 
                                        session: RightNow.Url.getSession(), 
                                        transactionID: this._transactionID, 
                                        getUrlData: this.getUrlData, 
                                        yuiObj: this.Y, 
                                        widgetInstanceID: this.baseDomID, 
                                        defaultCount: this.resultCount, 
                                        resultCountArray : this.data.js.perPageList, 
                                        attrs: this.data.attrs});
            }
            else {
                if(!this.data.attrs.hide_when_no_results) {
                    this.Y.one(this.baseSelector + "_NoSearchResult").removeClass("rn_Hidden");
                    if(args[0].data.filters && args[0].data.filters.facet.value !== null) {
                        this.Y.one(".rn_ClearFilterMsg").removeClass("rn_Hidden");
                    }
                    else{
                        this.Y.one(".rn_ClearFilterMsg").addClass("rn_Hidden");
                    }
                    this.Y.one(this.baseSelector).addClass("rn_NoSearchResult");
                }
            }
        }
        if (this.Y.one(this.baseSelector + "_Content")) {
            if(this.data.attrs.hide_when_no_results && newContent === '') {
                this.Y.one(this.baseSelector).removeClass("rn_SearchResult");
            }
            else if(!this.Y.one(this.baseSelector).hasClass("rn_SearchResult")) {
                this.Y.one(this.baseSelector).addClass("rn_SearchResult");
            }
            this.Y.one(this.baseSelector + "_Content").set("innerHTML", newContent);
            this.Y.one(this.baseSelector).removeClass("rn_Hidden");
            if(results && results.length > 0 && this.resultCount)
                this._initEvents();
        }
        if(results && results.length > 0) {
            this._updateAriaAlert(this.data.attrs.label_screen_reader_search_success_alert);
            anchor = (this.Y.one('.rn_Element1 a'));
            if (anchor) {
                anchor.focus();
            }
        }
        else {
            this._updateAriaAlert(this.data.attrs.label_screen_reader_search_no_results_alert);
        }
    },
    
    /**
     * Displays a warning dialog when a report error is encountered.
     * @private
     * @param {null|String} error
     */
    _displayDialogIfError: function(error) {
        if (error) {
            RightNow.UI.Dialog.messageDialog(error, {"icon": "WARN"});
        }
    },
    
    /**
     * Updates the text for the ARIA alert div that appears above the results listings.
     * @private
     * @param {String} text The text to update the div with
     */
    _updateAriaAlert: function(text) {
        if (!text) return;
        this._ariaAlert = this._ariaAlert || this.Y.one(this.baseSelector + "_Alert");
        if(this._ariaAlert) {
            this._ariaAlert.set("innerHTML", text);
        }
    },

    /**
     * Click handler for result div.
     * Makes the result href easier to click
     * by making the entire result div clickable.
     * @param {object} evt Click event
     */
    onResultClick: function(evt) {
        evt.halt();
        var resultLink = evt.currentTarget.hasClass('rn_SearchResultIcon') ? evt.currentTarget.next() : evt.currentTarget,
            dataHref = resultLink.getAttribute('data-href'),
            href = resultLink.getAttribute('href'),
            isHighlightingEnabled = resultLink.getAttribute('data-isHighlighted'),
            dataType = resultLink.getAttribute('data-type').toUpperCase(),
            txnParam = dataHref.indexOf("/s/") > 0 ? '/txnId/' + this._transactionID : '/' + this._transactionID,
            direction = RightNow.Url.getParameter('dir'),
            page = parseInt(RightNow.Url.getParameter('page'), 10),
            currentPageNumber;

        if(direction === 'forward'){
            currentPageNumber = page + 1;
        }
        else if(direction === 'backward'){
            currentPageNumber = page - 1;
        }
        else {
            currentPageNumber = page;
        }
        if (this.data.attrs.target !== '_blank' &&
            ((['PDF', 'TEXT', 'XML', 'INTENT', 'CMS-XML'].indexOf(dataType) !== -1) || (dataType === 'HTML' && dataHref.indexOf("okcsFile") > 0))) {
            this.searchSource().fire('collect').fire('updateHistoryEntry', new RightNow.Event.EventObject(this, {
                data: {
                    data: this.data.js.filter,
                    update: {
                        direction: {value: 'current', key: 'dir', type: 'direction'},
                        page: {value: currentPageNumber, key: 'page', type: 'page'},
                        facet: {value: this.data.js.filter.facet.value, key: 'facet', type: 'facet'}
                    }
                }
            }));
        }
        var navigationUrl = '';
        if(dataType === 'CMS-XML' && this.data.attrs.append_search_query) {
            navigationUrl = dataHref.indexOf("#__highlight") > 0 ? (dataHref.substring(0, dataHref.indexOf("#__highlight")) + '/kw/' + this.data.js.filter.query.value + txnParam + "#__highlight") : (dataHref + txnParam);
        }
        else {
            navigationUrl = dataHref.indexOf("#__highlight") > 0 ? (dataHref.substring(0, dataHref.indexOf("#__highlight")) + '/accessType/' + encodeURIComponent(this.data.attrs.access_type) + txnParam + "/" + encodeURIComponent(href) + "#__highlight") : (dataHref + txnParam);
        }
        if(this.data.js.isValidVersion && (isHighlightingEnabled === 'false' || isHighlightingEnabled === '')) {
            var clickthruEventObject = new RightNow.Event.EventObject(this, {data: {
                    answerType: dataType,
                    clickThruLink: navigationUrl
                }});
            RightNow.Ajax.makeRequest('/ci/okcsAjaxRequest/recordClickThru', clickthruEventObject.data, {
                scope: this, json: true
            });
            navigationUrl = href;
        }
        if(this.data.attrs.target === '_blank')
            window.open(navigationUrl, '_blank');
        else
            RightNow.Url.navigate(navigationUrl, true);
    },

    /**
    * Called when a new search is triggered by a widget with the same source_id attribute.
    * Resets the member keeping track of additional results and responds with an event object
    * containing the widget's id.
    * @return {object} EventObject
    */
    onSearch: function() {
        this._additionalResults = null;
        return new RightNow.Event.EventObject(this, { data: {
            // Since the keyword being searched on is automatically included
            // (see the constructor comments above),
            // the only thing we need to send to the server is the widget's id
            // which is used to sort out which widget instance's method should
            // be used to handle the AJAX request.
            w_id: this.data.info.w_id
        }});
    },

    /**
     * This method returns the iq action from the url parameter
     * @param {string} url Document URL
     * @param {string} parameterName Request parameter to be fetched
     * @return {string|null} action name
     */
    _getUrlParameter: function(url, parameterName ) {
        parameterName = parameterName.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
        var regexS = "[\\?&]" + parameterName + "=([^&#]*)";
        var regex = new RegExp( regexS );
        var results = regex.exec( url );
        return results ? results[1] : null;
    },

    /**
     * Adds the session key to the filter list
     */
    sendOkcsSession: function() {
        return new RightNow.Event.EventObject(this, {
            data: {value: this._okcsSearchSession, key: 'okcsSearchSession', type: 'okcsSearchSession'}
        });
    },

    /**
     * Adds the truncate size to the filter list
     */
    sendTruncateSize: function() {
        return new RightNow.Event.EventObject(this, {
            data: {value: this._truncateSize, key: 'truncate', type: 'truncate'}
        });
    },

    /**
     * Adds the transaction id to the filter list
     */
    sendTransactionID: function() {
        this._transactionID = this._transactionID + 1;
        return new RightNow.Event.EventObject(this, {
            data: {value: this._transactionID, key: 'transactionID', type: 'transactionID'}
        });
    },

    /**
     * Adds the regexpattern to the filter list
     */
    sendRegExPattern: function() {
        return new RightNow.Event.EventObject(this, {
            data: {value: this._regExPattern, key: 'docIdRegEx', type: 'docIdRegEx'}
        });
    },

    /**
     * Adds docIdNavigation to the filter list
     */
    sendDocIdNavigation: function() {
        return new RightNow.Event.EventObject(this, {
            data: {value: this._docIdNavigation, key: 'docIdNavigation', type: 'docIdNavigation'}
        });
    },

    /**
     * Adds the prior transaction id to the filter list
     */
    sendPriorTransactionID: function() {
        this._priorTransactionID = this._transactionID;
        return new RightNow.Event.EventObject(this, {
            data: {value: this._priorTransactionID, key: 'priorTransactionID', type: 'priorTransactionID'}
        });
    },

    /**
     * Adds the search cache id to the filter list
     */
    sendSearchCacheId: function() {
        return new RightNow.Event.EventObject(this, {
            data: {value: this.data.js.filter.searchCacheId.value, key: 'searchCacheId', type: 'searchCacheId'}
        });
    },

    /**
     * Adds the ResultCount to the filter list
     */
    sendResultCount: function() {
        return new RightNow.Event.EventObject(this, {
            data: {value: this.resultCountFilter, key: 'resultCount', type: 'resultCount'}
        });
    },
    
    /**
    * Collects searchType filter
    * @return {object} Event object
    */
    updateResultCountType: function() {
        return new RightNow.Event.EventObject(this, {
            data: {value: this._resultCountType, key: 'resultCountType', type: 'resultCountType'}
        });
    },
    /**
     * Adds the QuerySource to the filter list
     */
    sendQuerySource: function() {
        return new RightNow.Event.EventObject(this, {
            data: {value: this.data.attrs.query_source, key: 'querySource', type: 'querySource'}
        });
    },

    /**
     * Event handler to remove all the list items and 
     * disable result count dropdown after selection of an item
     * @param {object} e Event name
     */
    _updateResultCount: function(e) {
        if(this.resultCount == e.target._node.innerText) {
            return;
        }
        this._resultCountType = 'RC';
        this.resultCount = e.target._node.innerText;
        this._displayFieldVisibleText.setHTML(this.resultCount);
        this._dropdownElement.setHTML('');
        this._toggleElement.addClass('rn_Disabled');
        this.resultCountFilter = (this.resultCount > this._totalResults) ? this._totalResults : this.resultCount;
        this.searchSource().fire('collect').fire('search', new RightNow.Event.EventObject(this, {
               page: this.data.js.filter,
               sourceCount: 1
           }));
        return;
    },

    _onMouseOver: function(e) {
        this.Y.all('.rn_SelectedLink').removeClass('rn_SelectedLink');
        e.currentTarget.addClass('rn_SelectedLink');
    },

    /**
     * Handles keydown events for menuitems.
     * Tab: closes the menu
     * Esc: closes the menu and focuses on the trigger
     * ↑:   focuses on the previous element in the menu
     * ↓:   focuses on the next element in the menu
     * @param {object} e Keydown event
     */
    _onKeydown: function(e) {
        if (!this._closeDropdownKeypress(e)) {
            this._dropdownNavKeypress(e);
        }
    },

    /**
     * Closes the dropdown on TAB or ESC.
     * @param {object} e Keydown event
     * @return {boolean} True if the dropdown was closed; False if not
     */
    _closeDropdownKeypress: function (e) {
        if (this.Y.Array.indexOf([RightNow.UI.KeyMap.ESCAPE, RightNow.UI.KeyMap.TAB], e.keyCode) === -1) return false;

        // Close the dropdown on TAB or ESC.
        this._closeDropdown();

        if (e.keyCode === RightNow.UI.KeyMap.ESCAPE) {
            this._toggleElement.focus();
        }

        return true;
    },

    /**
     * Handles ↑ and ↓ key events.
     * @param {object} e Keydown event
     */
    _dropdownNavKeypress: function (e) {
        if (this.Y.Array.indexOf([RightNow.UI.KeyMap.UP, RightNow.UI.KeyMap.DOWN], e.keyCode) > -1) {
            e.halt();
            this._focusAdjacentElement(this._keyEventTriggers, e.keyCode === RightNow.UI.KeyMap.UP ? -1 : 1);
        }

        if(RightNow.UI.KeyMap.ENTER === e.keyCode) {
            if(this._dropdownOpen) {
                if(this.Y.one('.rn_SelectedLink').getAttribute('data-answer-id') === "") {
                    this._toggleDropdown(e);
                }
                this.Y.one('.rn_SelectedLink').simulate("click");
            }
        }
    },

    /**
     * Focuses on the sibling of the currently-focused node
     * in the given nodelist specified by the given index.
     * @param {object} nodeList Y.NodeList
     * @param {number} adjacentIndex Either 1 (next sibling) or -1 (previous sibling)
     */
    _focusAdjacentElement: function (nodeList, adjacentIndex) {
        var selectedNode = this.Y.one('.rn_SelectedLink');

        if(adjacentIndex === -1 && selectedNode.previous()) {// ↑ scenario
            selectedNode.removeClass('rn_SelectedLink');
            selectedNode.previous().addClass('rn_SelectedLink');
        }
        else if(adjacentIndex === 1 && selectedNode.next()) {// ↓ scenario
            selectedNode.removeClass('rn_SelectedLink');
            selectedNode.next().addClass('rn_SelectedLink');
        }
    },

    /**
     * Stops the default event and toggles between
     * displaying and hiding the dropdown list of links.
     * @param {Object} evt Click or Focus event.
     */
    _toggleDropdown: function(evt) {
        this.Y.all('.rn_SelectedLink').removeClass('rn_SelectedLink');
        this.Y.all('.rn_DefaultCount').addClass('rn_SelectedLink');
        evt.halt();

        (this._closeDropdown() || this._openDropdown());
    },

    /**
     * Displays the dropdown list of sublinks and subscribes
     * to appropriate events that dictate the proper closing of
     * the dropdown.
     */
    _openDropdown: function() {
        if(!this._dropdownOpen) {
            RightNow.UI.show(this._dropdownElement);
            this._toggleElement.setAttribute('aria-expanded', 'true');

            // JAWS support - focus on parent tag of menu list first and then focus back on dropdown button.
            this.Y.one(this.baseSelector + "_SubNavigationParent").focus();
            this._toggleElement.focus();

            this.Y.one(document.body).on('click', this._closeDropdown, this);
            this._dropdownOpen = true;
            return true;
        }
        return false;
    },

    /**
     * Hides the dropdown list of sublinks and optionally
     * purges the element that triggered the event.
     */
    _closeDropdown: function() {
        if(this._dropdownOpen) {
            this.Y.one(document.body).detach("click", this._closeDropdown, this);
            RightNow.UI.hide(this._dropdownElement);
            this._toggleElement.setAttribute('aria-expanded', 'false');
            this._dropdownOpen = false;
            return true;
        }
        return false;
    }
});
