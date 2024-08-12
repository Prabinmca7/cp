RightNow.Widgets.OkcsSuggestions = RightNow.SearchProducer.extend({
    overrides: {
        constructor: function() {
            this.parent();
            this._inputNodes = this.Y.all("." + this.data.attrs.parent_selector + " input");
            var that = this;
            document.onmousemove = function(event){that._docMouseMove(event);};
            if (this._inputNodes) {
                this._inputNodes.each(function(currentNode) {
                    this._searchField = currentNode;
                    this._pageLoadQuery = this._queryParam = this._searchField.get('value');
                    this._onLoad = false, this.invalidQuery, this.isActiveItem, this._ssacActiveItem, this.getSuggestions = true;
                    this.inputValueChange, this.navigateSuggestion, this._inputClick, this.onSearchResponse = this.hoveredActiveItem = false;
                    this.activeItemValue;
                    this._acListSelector = '#' + this.instanceID + ' ul';
                    currentNode.addClass('yui3-skin-sam');
                    this._ssac = new this.Y.AutoCompleteList(
                        {inputNode: "#" + currentNode._node.id, id: this.instanceID, render: true});

                    //Fetch suggestions on page load
                    if(this._pageLoadQuery.length >= 3) {
                        this._validateQuery();
                    }

                    RightNow.Event.subscribe("evt_sendProdCatRefKey", function(evt, evtData) {
                        this._prodCatRefKey = evtData[0].data.refKey;
                        this.fetchProdCatRefKey();
                    }, this);

                    currentNode.after('valueChange', this._validateQuery, this);
                    currentNode.on('click', this._clickInput, this);
                    this._ssac.on("resultsChange", this._resultsChange, this);
                    this._ssac.after('select', this._performNavigation, this);
                    this._ssac.after('activeItemChange', this._setInputField, this);
                    this._ssac.after('mousemove', this._mouseMove, this);
                    this._ssac.on('mouseover', this._mouseOver, this);
                    this._ssac.on('hoveredItemChange', this._updateSelection, this);
                    this.searchSource().on('collect', this.onCollect, this)
                                       .on('collect', this.updateFacet, this);
                    this.searchSource().on('response', this.onResponse, this);
                }, this);
                this._inputNodes.on('windowresize', this._resizeSuggestedSearchDiv, this);
            }
        }
    },

    /**
    * Event Handler fired on mouse click
    */
    _clickInput: function() {
        var newQuery = this._searchField.get('value').trim();
        if(this._onLoad) {
            this._showSuggestions(this.data.js.suggestedSearches);
            this._onLoad = false;
        }
        if(newQuery.length >= 3){
            if(this.data.js.suggestedSearches && this.data.js.suggestedSearches.length > 0) {
                this._inputClick = true;
                this._showAutoComplete();
            }
            else
                this._hideAutoComplete();
        }
        else {
                this._hideAutoComplete();
                this._ssac._clear();
                this.invalidQuery = true;
                if (!this._onLoad)
                    RightNow.Event.fire("evt_disableSuggestedArticles");
        }
    },

    /**
    * Event Handler fired for results changed
    * @param {Object} evt Event object
    */
    _resultsChange: function(evt){
        if(this.activeItem) {
            this.activeItem = false;
            return false;
        }
        if(this._ssac._lastInputKey === 27) {
            return false;
        }
        if(this.invalidQuery){
            this.invalidQuery = false;
            return false;
        }
        this._ssac._syncResults(evt.newVal), this._ssac.get("alwaysShowList") || this._ssac.set("visible", !!evt.newVal.length)
    },

    /**
    * Event Handler fired when navigating through suggestions list using arrow keys
    * @param {Object} evt Event object
    */
    _setInputField: function(evt) {
        if(evt.newVal != null && !this.hoveredActiveItem) {
            this._selectedItemVal = evt.newVal._data.result.raw.title;
            this._searchField.set('value',this._selectedItemVal);
            this.activeItemValue = this._selectedItemVal;
            this.getSuggestions = false;
            this.activeItem = true;
            this._ssacActiveItem = false;
        }
        if(this.hoveredActiveItem) {
            this.hoveredActiveItem = false;
        }
        if(evt.newVal === null && this._ssac._lastInputKey === 27) {
            this._ssacActiveItem = false;
            this._searchField.set('value',this._queryParam);
            this.isActiveItem = false;
            return false;
        }
        if(this._ssac._boundingBox.hasClass('yui3-aclist-hidden')) {
            return false;
        }
        if(!this.inputValueChange) {
            return false;
        }
        if(this._inputClick) {
            this._inputClick = false;
            return false;
        }
        var newQuery = this._searchField.get('value').trim();
        if(newQuery.length === 0) {
            return false;
        }
        if(evt.newVal === null) {
            this._ssacActiveItem = false;
            this._searchField.set('value',this._queryParam);
            this.isActiveItem = false;
            return false;
        }
    },

    /**
    * Event Handler fired on mouse click or keyup event on search text field
    * Function to validate the input query and invoke getSuggestions only on valid query change
    * @param {Object} evt Event object
    */
    _validateQuery: function(evt) {
        if(this._prodCatRefKey === undefined)
            this.fetchProdCatRefKey();
        this.inputValueChange = true;
        if(evt && evt.newVal !== evt.prevVal) {
            this.inputValue = evt.newVal;
        }
        if(evt && evt.newVal.trim() === evt.prevVal.trim()) {
            this.inputValueChange = false;
            return false;
        }
        if(this.getSuggestions && this._ssac._lastInputKey !== 27) {
            var newQuery = this._searchField.get('value').trim();
            if(newQuery.length >= 3) {
                RightNow.Event.fire("evt_enableSuggestedArticles");
                this._queryParam = newQuery;
                this.invalidQuery = false;
                this._onLoad = false;
                if(this._pageLoadQuery.length >= 3) {
                    this._onLoad = true;
                    this.inputValueChange = false;
                    this._pageLoadQuery = '';
                }
                this._getSuggestions(newQuery, this._onLoad);
            }
            else {
                this._hideAutoComplete();
                this._ssac._clear();
                this.invalidQuery = true;
                if(!this._onLoad || !this._onSearchResponse) {
                    RightNow.Event.fire("evt_disableSuggestedArticles");
                    this._onLoad = this._onSearchResponse = false;
                }
            }
        }
        else {
            this.getSuggestions = true;
        }
    },

    /**
    * Event Handler fired when mouse is hovered over a suggestion
    * @param {Object} evt Event object
    */
    _updateSelection: function(evt) {
        if(!this._ssacActiveItem){
            return false;
        }
        var newVal = evt.newVal;
        if(newVal !== null && newVal !== undefined) {
            this.hoveredActiveItem = true;
            this._ssac.set('activeItem',newVal._node);
        }
    },

    /**
    * Event Handler fired when mouse is over a suggestion
    * @param {Object} evt Event object
    */
    _mouseOver: function(evt) {
        if(!this._ssacActiveItem){
            return false;
        }
        this.activeItemValue = this._searchField.get('value').trim();
    },

    /**
    * Event Handler fired when mouse is moved over a suggestion
    * @param {Object} evt Event object
    */
    _mouseMove: function(evt) {
        if(!this._ssacActiveItem){
            return false;
        }
        this.xposition = evt.domEvent.clientX;
        this.yposition = evt.domEvent.clientY;
    },

    /**
    * Event Handler fired when mouse is moved anywhere in the document
    * @param {Object} evt Event object
    */
    _docMouseMove: function(evt) {
        if(evt.clientX === this.xposition && evt.clientY === this.yposition) {
            return false;
        }
        this._ssacActiveItem = true;
    },

    /**
     * Function to invoke suggested articles api
     * @param {String} ssQuery query string
     * @param {boolean} onload boolean indicator for page load
     */
    _getSuggestions: function(ssQuery, onload) {
        var eventObject = new RightNow.Event.EventObject(this, {
            data: {
                ssQuery: ssQuery.trim(),
                suggestionCount: this.data.attrs.suggestion_count,
                productCategory: this.data.attrs.product_category,
                matchAllCategories: this.data.attrs.match_all_categories,
                applyCtIndexStatus: this.data.js.applyCtIndexStatus
            }
        });
        RightNow.Ajax.makeRequest(this.data.attrs.get_okcs_data_ajax, eventObject.data, {
            successHandler: function(response) {
                this.data.js.suggestedSearches = response;
                if(!onload){
                    if(this._searchField.get('value').trim() === ssQuery.trim()) {
                        this._selectedItemVal = this._queryParam;
                        this._showSuggestions(this.data.js.suggestedSearches);
                        this._onLoad = false;
                    }
                }
            },
            failureHandler: function(response){
                var message = response.suggestedErrorMessage || RightNow.Interface.getMessage('THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG');
                if(response.status === 403 && response.responseText !== undefined){
                    message = response.responseText;
                }
                RightNow.UI.Dialog.messageDialog(message, {"icon": "WARN", exitCallback: function(){window.location.reload(true);}});
            },
            json: true,
            scope: this,
            ignoreFailure: true
        });
    },

    /**
     * Function to display the autocomplete list
     */
    _showAutoComplete: function() {
        if(!this.navigateSuggestion) {
            this._ssac.show();
            this.Y.one('#' + this.instanceID).show();
            this._ssac.syncUI();
            this._ssac._syncPosition();
        }
        else {
            this.navigateSuggestion = false;
            this._ssac.hide();
        }
    },

    /**
     * Function to hide the autocomplete list
     */
    _hideAutoComplete: function() {
        this._ssac.hide();
        this.Y.one('#' + this.instanceID).hide();
    },

   /*
   * Function to render the suggestions
   * @param {Array} results array of results
   */
    _showSuggestions: function(response){
        if(response && response.length > 0) {
            this._ssac.set('maxResults', this.data.attrs.suggestion_count);
            this._ssac.set('minLength', 0);

            if(this.data.attrs.suggestions_as.toLowerCase() === 'search') {
                this._dropDownPlaceHolder = this.data.attrs.label_suggested_search;
            }
            else if(this.data.attrs.suggestions_as.toLowerCase() === 'answer') {
                this._dropDownPlaceHolder = this.data.attrs.label_suggested_answer;
                this.Y.one(this._acListSelector).addClass('rn_Answer');
            }
              this.Y.one(this._acListSelector)
                .setAttribute('aria-label', this._dropDownPlaceHolder)
                .setHTML(this._dropDownPlaceHolder)
                .ancestor().ancestor().addClass('rn_SearchInput rn_OkcsSuggestions');
            this._setResultFormatter(response);
            this._ssac.set('source', response);
            this._ssac.fire('query');
            this._showAutoComplete();
        }
        else {
            this._hideAutoComplete();
            this._setResultFormatter(response);
            this._ssac._clear();
        }
    },

   /*
   * Function to format the results
   * @param {Array} results array of results
   */
   _setResultFormatter: function(results){
        var that = this;
        var htmlTemplate = '<li class="rn_SuggestionItem" data-id="{_id}" data-text="{_title}">{_highlightedTitle}</li>';
        if(this.data.attrs.display_tooltip) {
           htmlTemplate = htmlTemplate + '<div class="list-item-tooltip">{_title}</div>';
        }

        function resultFormatter(query, results) {
            return that.Y.Array.map(results, function (result) {
                return that.Y.Lang.sub(htmlTemplate, {
                    _id : result.raw.answerId,
                    _title : result.raw.title,
                    _highlightedTitle : result.raw.highlightedTitle
                });
            });
        }
        this._ssac.set('resultFormatter',resultFormatter);
    },

    /**
    * Event Handler fired when a suggestion is selected from the list
    * @param {Object} evt Event object
    */
    _performNavigation: function(evt){
        this._searchField.set('value',this.activeItemValue);
        if(this.Y.one(this._acListSelector).getAttribute('aria-label') === this._dropDownPlaceHolder){
            this._hideAutoComplete();
            if(this.data.attrs.suggestions_as.toLowerCase() === 'answer') {
                this._viewAnswerDetail(evt);
            }
            else {
                this._getSearchResults(evt);
            }
        }
    },

    /**
     * Returns a RightNow.Event.EventObject with the filter value filled in
     * @return {object} RightNow.Event.EventObject with the filter value filled in
     */
    onCollect: function () {
        this.navigateSuggestion = true;
        return new RightNow.Event.EventObject(this, {
            data: this.Y.merge(this.data.js.filter.query, { value: this.Y.Lang.trim(this._searchField.get('value')).substr(0,this._truncateSize)})
        });
    },

    /**
    * Collects facet filter
    * @return {object} Event object
    */
    updateFacet: function() {
        return new RightNow.Event.EventObject(this, {
            data: {value: this._prodCatRefKey, key: 'facet', type: 'facet'}
        });
    },

    /**
     * Method to set product category reference key.
     */
    fetchProdCatRefKey: function() {
        if(this._prodCatRefKey === undefined){
            RightNow.Event.fire("evt_getProdCatRefKey");
            return;
        }
    },

    /**
    * Event Handler fired when search response is obtained
    */
    onResponse: function() {
        this.getSuggestions = true;
        this.navigateSuggestion = true;
        this._onLoad = this._onSearchResponse = true;
        this._validateQuery();
    },

    /**
    * Function to fire search when the suggestion is clicked
    * @param {Object} evt Event object
    */
    _getSearchResults: function(evt){
        if(this.hoveredActiveItem) {
            this.hoveredActiveItem = false;
        }
        if(this.activeItemValue) {
            this._inputNodes.set('value', this.activeItemValue);this.activeItemValue = null;
        }
        else
            this._inputNodes.set('value', evt.itemNode.getDOMNode().childNodes[0].textContent);
        if (this.data.attrs.search_results_url)
            this.searchSource().setOptions({new_page: this.data.attrs.search_results_url});
        this.searchSource().fire('collect');
        if(this.searchSource().multiple) {
            this.filters = this.searchSource().sources[0].filters;
        }
        else {
            this.filters = this.searchSource().filters;
        }
        if(this.filters.query.value === null)
            this.filters.query.value = this._queryParam;
        if(this.filters.query && this.Y.Lang.trim(this.filters.query.value) !== '') {
            if (this.filters.query.key === "kw" && this.Y.Lang.trim(this.filters.query.value) === "*") {
                this.filters.direction = this.filters.direction || {};
                this.filters.direction.value = 0;
                this.filters.sort = this.filters.sort || {};
                this.filters.sort.value = 1;
            }
            this.searchSource().fire('search', new RightNow.Event.EventObject(this, {
               page: this.data.js.filter,
               sourceCount: 1
           }));
        }
        else {
            RightNow.UI.displayBanner(this.data.attrs.label_enter_search_keyword, {
                type: 'WARNING',
                focusElement: this.searchField
            });
        }
    },

    /**
    * Function to navigate to the answer detail page when a suggestion is clicked
    * @param {Object} evt Event object
    */
    _viewAnswerDetail: function(evt) {
        var answerId = evt.details[0].result.raw.answerId;
        window.location.href = '/app/' + RightNow.Interface.getConfig('CP_ANSWERS_DETAIL_URL') + '/a_id/' + answerId;
    },

    /*
     * This function to resize the suggested search div based upon the window display.
     */
    _resizeSuggestedSearchDiv: function() {
        this.Y.all('.yui3-aclist').set('offsetWidth', this._inputNodes.get('offsetWidth'));
    }
});
