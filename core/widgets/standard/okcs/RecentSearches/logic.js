RightNow.Widgets.RecentSearches = RightNow.SearchProducer.extend({
    overrides: {
        constructor: function() {
            this.parent();
            RightNow.Event.subscribe("evt_enableSuggestedArticles", this._hideRecentList, this);
            RightNow.Event.subscribe("evt_disableSuggestedArticles", this._showRecentList, this);
            this._inputNodes = this.Y.all("." + this.data.attrs.parent_selector + " input");
            if (this._inputNodes) {
                this._inputNodes.each(function(currentNode) {
                    this._searchField = currentNode;
                    this._recentAcListSelector = '#' + this.instanceID + ' ul';
                    this._isSuggestions = false;
                    currentNode.addClass('yui3-skin-sam');
                    currentNode.plug(this.Y.Plugin.AutoCompleteList, {id : this.instanceID});
                    currentNode.on('click', this._showRecentSearches, this);
                    this.Y.one(this._recentAcListSelector).setAttribute('aria-label', this.data.attrs.label_recent_search);
                    this._getLatestRecentSearches(this);
                    currentNode.ac.after('select', function(e) {
                        if(e.itemNode._node.childNodes[0].className !== 'rn_SuggestionItem') {
                            this._inputNodes.set('value', e.itemNode.getDOMNode().childNodes[0].textContent);
                            this.searchSource().fire('collect');
                            if(this.searchSource().multiple) {
                                this.filters = this.searchSource().sources[0].filters;
                            }
                            else {
                                this.filters = this.searchSource().filters;
                            }
                            if(this.filters.query && this.Y.Lang.trim(this.filters.query.value) !== '') {
                                if (this.filters.query.key === "kw" && this.Y.Lang.trim(this.filters.query.value) === "*") {
                                    this.filters.direction = this.filters.direction || {};
                                    this.filters.direction.value = 0;
                                    this.filters.sort = this.filters.sort || {};
                                    this.filters.sort.value = 1;
                                }
                                //this.disableClickListener();
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
                        }
                    }, this);
                    this.searchSource().setOptions(this.data.js.sources).on('response', this._getLatestRecentSearches, this);
                }, this);
                this._inputNodes.on('windowresize', this._resizeRecentSearchDiv, this);
            }
        }
    },

    /*
     * Function to render the recent searches display.
     */
    _showRecentSearches: function() {
        if(!this._isSuggestions){
            this.Y.one('#' + this.instanceID).show();
            this._searchField.ac.set('maxResults', this.data.attrs.no_of_suggestions);
            this._searchField.ac.set('minLength', 0);
            if (this.data.js.recentSearches && this.data.js.recentSearches.length > 0) {
                this.Y.one(this._recentAcListSelector).setHTML(this.data.attrs.label_recent_search);
                this.Y.one(this._recentAcListSelector).setAttribute('aria-label', this.data.attrs.label_recent_search);
                this.Y.one(this._recentAcListSelector).removeClass('rn_Answer');
                this._setResultFormatter(this.data.js.recentSearches);
                this._searchField.ac.set('source', this.data.js.recentSearches);
                this._searchField.ac.fire('query');
            }
        }
    },

    /**
     * This function to resize the recent search div based upon the window display.
     */
    _resizeRecentSearchDiv: function() {
        this.Y.all('.yui3-aclist').set('offsetWidth', this._inputNodes.get('offsetWidth'));
    },

    /**
     * This function is to show the recent searches dropdown list
     */
    _showRecentList: function() {
        this._isSuggestions = false;
        this._showRecentSearches();
    },

    /**
     * This function is to hide the recent searches dropdown list
     */
    _hideRecentList: function() {
        this._isSuggestions = true;
        this.Y.one('#' + this.instanceID).hide();
    },

    /**
     * Function to get the latest recent searches after the search response is triggered.
     * @param {object} filter object
     */
    _getLatestRecentSearches: function() {
        this._searchField.ac.hide();
        var eventObject = new RightNow.Event.EventObject(this, {
            data: {
                noOfSuggestions: this.data.attrs.no_of_suggestions
            }
        });
        RightNow.Ajax.makeRequest(this.data.attrs.get_okcs_data_ajax, eventObject.data, {
            successHandler: function(response) {
                this._searchField.ac.hide();
                this.data.js.recentSearches = response;
                if (this.data.js.recentSearches && this.data.js.recentSearches.length > 0) {
                    this.Y.one(this._recentAcListSelector).setHTML(this.data.attrs.label_recent_search);
                    this.Y.one(this._recentAcListSelector).setAttribute('aria-label', this.data.attrs.label_recent_search);
                    this.Y.one(this._recentAcListSelector).removeClass('rn_Answer');
                    this._setResultFormatter(this.data.js.recentSearches);
                    this._searchField.ac.set('source', response);
                }
            },
            json: true,
            scope: this,
            ignoreFailure: true
        });
    },
    
    /*
   * This function to set the result formatter.
   */    
   _setResultFormatter: function(results){
       var that = this;
       var htmlTemplate = '<li class="rn_ResultFormat" >{_array}</li>';
       if(this.data.attrs.display_tooltip) {
           htmlTemplate = htmlTemplate + '<div class="list-item-tooltip">{_array}</div>';
       }
          function resultFormatter(query, results) {
          return that.Y.Array.map(results, function (result) {
            return that.Y.Lang.sub(htmlTemplate, {
              _array : result.raw
            });
          });
        } 
       this._searchField.ac.set('resultFormatter',resultFormatter);
   }
});