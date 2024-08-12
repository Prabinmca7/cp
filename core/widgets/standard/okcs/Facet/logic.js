RightNow.Widgets.Facet = RightNow.SearchProducer.extend({
    overrides: {
        constructor: function() {
            this.parent();
            if(this.data.attrs.toggle_title) {
                this.toggleEvent = true;
                this._addToggle();
                if (this.data.attrs.toggle_state === 'collapsed' && this._toggle !== null) {
                    this._toggle.addClass(this.data.attrs.collapsed_css_class);
                    this._onToggle(this);
                }
            }
            this._newSearch = true;
            this._orderedFacets = this.data.js.orderedFacets;
            this._searchType = 'PAGE';
            this._moreFacet = '';
            this._query = '';
            this._selectedFacetDetails = this.data.js.selectedFacetDetails ? this.data.js.selectedFacetDetails : [];
            this._categArr = [], this._prodArr = [], this._docTypeArr = [], this._collArr = [];
            this._allFacetsList = new Array();
            this._retainFacetOnNewSearch = this.data.attrs.retain_facet_on_new_search;
            RightNow.Event.subscribe('evt_getIsMultiSelectFlag', function() {
                RightNow.Event.fire("evt_sendIsMultiSelectFlag", new RightNow.Event.EventObject(this, {data: { enable_multi_select : this.data.attrs.enable_multi_select}}));
            }, this);

            if(this.data.attrs.enable_multi_select) {
                this._multiFacet = (this.data.js.selectedFacetsUrl) ? this.data.js.selectedFacetsUrl : '';
                this.Y.one(this.baseSelector).delegate('click', this._expandCategory, 'a.rn_CategoryExplorerCollapsed', this);
                this.Y.one(this.baseSelector).delegate('click', this._collapseCategory, 'a.rn_CategoryExplorerExpanded', this);
                this.Y.one(this.baseSelector).delegate('click', this._onCategoryClick, 'li.rn_CategoryExplorerItem', this);
                this.Y.one(this.baseSelector).delegate('click', this._onCategoryClick, 'span.rn_FacetClearIcon', this);
                this.Y.one(this.baseSelector).delegate('click', this._onFacetResetClick, 'a.rn_ClearFacets', this);
                RightNow.Event.subscribe("evt_clearSelectedMultiFacets", this._clearSelectedMultiFacets, this);

                this.searchSource().setOptions(this.data.js.sources)
                    .on('collect', this.sendOkcsNewSearch, this)
                    .on('collect', this.updateFacet, this)
                    .on('collect', this.updateSearchType, this)
                    .on('collect', this.retainFacetOnNewSearchFlag, this)
                    .on('collect', this.updateMultiFacetToRetain, this)
                    .on('response', this._onMultiFacetResultChanged, this)
                    .on('send', this._searchInProgress, this);
            }
            else {
                this.Y.one(this.baseSelector).delegate('click', this._onFacetClick, 'a.rn_FacetLink', this);
                this.Y.one(this.baseSelector).delegate('click', this._onFacetResetClick, 'a.rn_ClearFacets', this);
                this.Y.all(".rn_ToggleExpandCollapse").on('click', this.toggleExpandCollapse, this);

                this.searchSource().setOptions(this.data.js.sources)
                    .on('collect', this.sendOkcsNewSearch, this)
                    .on('collect', this.updateFacet, this)
                    .on('collect', this.updateSearchType, this)
                    .on('collect', this.updateProduct, this)
                    .on('collect', this.updateCategory, this)
                    .on('collect', this.retainFacetOnNewSearchFlag, this)
                    .on('send', this._searchInProgress, this)
                    .on('response', this._onResultChanged, this);
            }
            RightNow.Event.subscribe("evt_selectedFacet", this._getFacetRequest, this);
            RightNow.Event.subscribe("evt_ResetFacets", this._onFacetResetClick, this);
            RightNow.Event.subscribe("evt_ClearFacetFilter", this._onClearFacetFilter, this);
        }
    },

    /**
    * Event handler to pass list of all selected facets.
    * @param {string} evt Event name
    * @param {args} args Arguments provided from event fire
    */
    _getFacetRequest: function(evt, args) 
    {
        var facetNodes = this.Y.all('.rn_ActiveFacet');
        var facet = '';
        var size = facetNodes.size();
        facetNodes.each(function (facetNode){
            facet = facet + facetNode.getAttribute('id') + ',';
        });
        facet = facet.substring(0, facet.length - 1);
        RightNow.Event.fire("evt_facetResponse", new RightNow.Event.EventObject(this, {data : { facet : facet, pageLink : args[0].data.pageLink }}));
    },

    /**
    * Event handler to clear specified facet.
    * @param {string} evt Event name
    * @param {args} args Arguments provided from event fire
    */
   _onClearFacetFilter: function(evt, args) {
        var clearRefKey = args[0].data.clearFacetFilterRefKey;
        this._multiFacet = this._facet = args[0].data.facet;
        var selectedNode = this.Y.one(this.baseSelector + '_' + clearRefKey.split('.')[clearRefKey.split('.').length - 1]);

        if(!this.data.attrs.enable_multi_select) { // For single facet flow, invocation from facetFilter
            this._selectedFacetDetails = args[0].data.selectedFacetDetails;
            this._searchType = 'FACET';
            // remove the clearRefKey from corresponding instance array also
            if(clearRefKey.indexOf('CMS-CATEGORY_REF') !== -1) {
                for(var key in this._categArr) {
                    if(this._categArr[key].split(':')[0] === clearRefKey)
                        this._categArr.splice(key,1);
                }
            }
            else if(clearRefKey.indexOf('CMS-PRODUCT') !== -1) {
                for(var key in this._prodArr) {
                    if(this._prodArr[key].split(':')[0] === clearRefKey)
                        this._prodArr.splice(key,1);
                }
            }
            else if(clearRefKey.indexOf('DOC_TYPES') !== -1) {
                for(var key in this._docTypeArr) {
                    if(this._docTypeArr[key].split(':')[0] === clearRefKey)
                        this._docTypeArr.splice(key,1);
                }
            }
            else if(clearRefKey.indexOf('COLLECTIONS') !== -1) {
                for(var key in this._collArr) {
                    if(this._collArr[key].split(':')[0] === clearRefKey)
                        this._collArr.splice(key,1);
                }
            }
        }
        else { // For multi-facet flow only
            var refKeyToClear = clearRefKey.split('.')[clearRefKey.split('.').length - 1],
                reqArr;
            if(clearRefKey.indexOf('CMS-CATEGORY_REF') !== -1)
                reqArr = this.data.js.urlCategory;
            else if(clearRefKey.indexOf('CMS-PRODUCT') !== -1)
                reqArr = this.data.js.urlProduct;

            for(key in reqArr) {
                if(reqArr[key] === refKeyToClear) {
                    reqArr.splice(key, 1);
                    break;
                }
            }
        }

        delete this._selectedFacetDetails[clearRefKey];
        if(Object.keys(this._selectedFacetDetails).length !== 0 ) {
            var categArr = [], prodArr = [], docTypeArr = [], collArr = [], facetData = {};
            for(key in this._selectedFacetDetails) {
                if(key.indexOf('CMS-CATEGORY_REF') !== -1) {
                    categArr.push(key + ':' + this._selectedFacetDetails[key]);
                }
                if(key.indexOf('CMS-PRODUCT') !== -1) {
                    prodArr.push(key + ':' + this._selectedFacetDetails[key]);
                }
                if(key.indexOf('DOC_TYPES') !== -1) {
                    docTypeArr.push(key + ':' + this._selectedFacetDetails[key]);
                }
                if(key.indexOf('COLLECTIONS') !== -1) {
                    collArr.push(key + ':' + this._selectedFacetDetails[key]);
                }
            }
            if(prodArr.length === 0 && categArr.length === 0 && docTypeArr.length === 0 && collArr.length === 0) {
                facetData = {};
            }
            else {
                facetData = {
                    'CMS-PRODUCT' : prodArr,
                    'CMS-CATEGORY_REF' : categArr,
                    'DOC_TYPES' : docTypeArr,
                    'COLLECTIONS' : collArr
                };
            }
        }
        RightNow.Event.fire("evt_updateFacetFilter", new RightNow.Event.EventObject(this, {data : { selectedFacetDetails : facetData, facet: this._facet}}));
        if(selectedNode && selectedNode.hasClass('rn_ActiveFacet')) {
            selectedNode.removeClass('rn_ActiveFacet');
            selectedNode._node.children[selectedNode._node.children.length - 1].remove();// removes the spanIcon
            selectedNode._node.children[selectedNode._node.children.length - 1].remove();// removes the screenreader selectedFacetText
        }
   },

    /**
    * Toggles the display of the element.
    */
    _addToggle: function() {
        this._toggle = this.Y.one(".rn_FacetsTitle");
        if (this._toggle !== null ) {
            this._toggle.appendChild(this.Y.Node.create("<span class='rn_Expand'></span>"));
            var current = this._toggle.next();
            if(current)
                this._itemToToggle = current;
            else
                return;
            this._currentlyShowing = this._toggle.hasClass(this.data.attrs.expanded_css_class) ||
                this._itemToToggle.getComputedStyle("display") !== "none";

            //trick to get voiceover to announce state to screen readers.
            this._screenReaderMessageCarrier = this._toggle.appendChild(this.Y.Node.create(
                "<img style='opacity: 0;' src='/euf/core/static/whitePixel.png' alt='" +
                    (this._currentlyShowing ? this.data.attrs.label_expanded : this.data.attrs.label_collapsed) + "'/>"));

            if(this.toggleEvent) {
                this.Y.one(this.baseSelector).delegate('click', this._onToggle, 'div.rn_FacetsTitle', this);
                this.toggleEvent = false;
            }
        }
    },
 
    /**
    * Toggles the display of the element.
    * @param clickEvent Event Click event
    */
    _onToggle: function(clickEvent) {
        var target = clickEvent.target, cssClassToAdd, cssClassToRemove;
        if(this._currentlyShowing) {
            cssClassToAdd = this.data.attrs.collapsed_css_class;
            cssClassToRemove = this.data.attrs.expanded_css_class;
            this._itemToToggle.setStyle("display", "none");
            this._screenReaderMessageCarrier.set("alt", this.data.attrs.label_collapsed);
        }
        else {
            cssClassToAdd = this.data.attrs.expanded_css_class;
            cssClassToRemove = this.data.attrs.collapsed_css_class;
            this._itemToToggle.setStyle("display", "block");
            this._screenReaderMessageCarrier.set("alt", this.data.attrs.label_expanded);
        }
        if(target) {
            target.addClass(cssClassToAdd)
                .removeClass(cssClassToRemove);
        }
        this._currentlyShowing = !this._currentlyShowing;
    },
    
    /**
    * Event handler received when search data is changing.
    * Clears the content during searches.
    * @param {string} evt Event name
    * @param {args} args Arguments provided from event fire
    */
    _searchInProgress: function(evt, args) 
    {
        var params = args[0],
            newSearch = (params.allFilters.facet === undefined);
        if (params && newSearch)
            this.Y.one(this.baseSelector + "_Content").get('childNodes').remove();
    },

   /**
    * Event Handler fired when a Clear facet Link is Clicked
    * @param {Object} evt Event object
    */
    _onFacetResetClick: function(evt)
    {
        if(evt !== 'evt_ResetFacets')
            evt.preventDefault();
        RightNow.Event.fire("evt_pageLoading");
        this._newSearch = false;
        this._facet = '';
        this._retainFacetOnNewSearch = false;
        this._searchType = 'clearFacet';
        this._categArr = [], this._prodArr = [], this._docTypeArr = [], this._collArr = [];this._selectedFacetDetails = [];

        RightNow.Event.fire("evt_updateFacetFilter", new RightNow.Event.EventObject(this, {data : { selectedFacetDetails : [], facet: this._facet }}));
        this.searchSource().fire('collect').fire('search', new RightNow.Event.EventObject(this, {
            data: {
                page: this.data.js.filter,
                sourceCount: 1
            }
        }));
        if(this.data.attrs.enable_multi_select) {
            this._multiFacet = '';
            this._selectedFacetDetails = [];
            var nodeArray = this.Y.all('.rn_ActiveFacet')._nodes;
            for(nodeKey in nodeArray) {
                var node = nodeArray[nodeKey];
                node.classList.remove('rn_ActiveFacet');
                node.children[node.children.length - 1].remove(true);
                node.children[node.children.length - 1].remove(true);
            }
        }
    },
    
    /**
    * Event Handler to clear specified facet(Clear clicked from Search results).
    * @param {Object} evt Event object
    */
    _clearSelectedMultiFacets: function(evt) {
        if(this.data.attrs.enable_multi_select) {
            this._categArr = [], this._prodArr = [], this._docTypeArr = [], this._collArr = [], this._selectedFacetDetails = [];
            this._multiFacet = '';
            this._facet = '';
            this._retainFacetOnNewSearch = false;
            RightNow.Event.fire("evt_updateFacetFilter", new RightNow.Event.EventObject(this, {data : { selectedFacetDetails : [], facet: this._facet }}));
            var nodeArray = this.Y.all('.rn_ActiveFacet')._nodes;
            for(nodeKey in nodeArray) {
                    var node = nodeArray[nodeKey];
                    node.classList.remove('rn_ActiveFacet');
                    node.children[node.children.length - 1].remove(true);
                    node.children[node.children.length - 1].remove(true);
            }
        }
    },

    /**
    * Event Handler fired when a facet is selected
    * @param {Object} evt Event object
    */
    _onFacetClick: function(evt)
    {
        evt.preventDefault();
        
        var selectedFacet = (evt.target.get('id').indexOf('yui') !== -1) ? evt.target.ancestor().get('id') : evt.target.get('id');
         /**
        * If user clicks on More link instead of actual facet.
        * More link has format of F:<parentFacetId>
        * In this case, we display children of <parentFacet> on the UI.
        * otherwise in else section, we just append facet filter and fire 'search' event to pull search results.
        */
        if(selectedFacet) {
            if (selectedFacet.indexOf('F:') !== -1) {
                selectedFacet = selectedFacet.substring(2);
                this._onFacetMoreClick(selectedFacet, evt.target);
            }
            else
            {
                RightNow.Event.fire("evt_pageLoading");
                var facetName = evt.target._node.textContent.trim();
                if(evt.target.ancestor().hasClass('rn_ActiveFacet')){
                    selectedFacet = selectedFacet.split(".")[0];
                }
                this._retainFacetOnNewSearch = this.data.attrs.retain_facet_on_new_search;
                this._facet = selectedFacet;
                this._searchType = 'FACET';
                var facetData = {},
                    value1 = selectedFacet + ':' + facetName;
                if(selectedFacet.indexOf('CMS-CATEGORY_REF') !== -1) {
                    this._categArr = [];
                    if(this._facet.indexOf('.') !== -1) {
                        this._categArr.push(value1);
                    }
                }
                else if(selectedFacet.indexOf('CMS-PRODUCT') !== -1) {
                    this._prodArr = [];
                    if(this._facet.indexOf('.') !== -1) {
                        this._prodArr.push(value1);
                    }
                }
                else if(selectedFacet.indexOf('DOC_TYPES') !== -1) {
                    this._docTypeArr = [];
                    if(this._facet.indexOf('.') !== -1) {
                        this._docTypeArr.push(value1);
                    }
                }
                else if(selectedFacet.indexOf('COLLECTIONS') !== -1) {
                    this._collArr = [];
                    if(this._facet.indexOf('.') !== -1) {
                        this._collArr.push(value1);
                    }
                }
                if(this._prodArr.length === 0 && this._categArr.length === 0 && this._docTypeArr.length === 0 && this._collArr.length === 0) {
                    facetData = {};
                }
                else {
                    facetData = {
                        'CMS-PRODUCT' : this._prodArr,
                        'CMS-CATEGORY_REF' : this._categArr,
                        'DOC_TYPES' : this._docTypeArr,
                        'COLLECTIONS' : this._collArr
                    };
                }
                RightNow.Event.fire("evt_updateFacetFilter", new RightNow.Event.EventObject(this, {data : { selectedFacetDetails : facetData, facet: this._facet}}));
                this.searchSource().fire('collect').fire('search', new RightNow.Event.EventObject(this, {
                    data: {
                        page: this.data.js.filter,
                        sourceCount: 1
                    }
                }));
                this._facet = '';
            }
        }
    },

    /** 
    * This method is called when more facet link is clicked.
    * @param {String} current facet in effect
    * @param {object} target node to load more facets
    */
    _onFacetMoreClick: function(selectedFacet, targetNode){
        var facets = RightNow.JSON.parse(this.data.js.facets);
        var facetSelected = selectedFacet.split(".")[1];
        selectedFacet = selectedFacet.indexOf('.') !== -1 ? selectedFacet.substr(0, selectedFacet.indexOf('.')) : selectedFacet;
        var targetLi = facetSelected !== undefined ? new this.Y.Node(document.getElementById(selectedFacet + "." + facetSelected)) : targetNode;
        var list = this.Y.Node.create("<div class='rn_FacetsList'></div>");
        var title = this.Y.Node.create("<div class='rn_FacetsTitle'></div>");
        title.append(this.data.attrs.label_filter);
        var clearLink = this.Y.Node.create("<span class='rn_ClearContainer'>[<a role='button' class='rn_ClearFacets'><span class='rn_ScreenReaderOnly'>" + this.data.attrs.label_clear_screenreader + "</span>" + this.data.attrs.label_clear + "</a>]</span>");
        title.append(clearLink);

        for (var i = 0; facets[i]; i++) {
            if (facets[i].id === selectedFacet) {

                if(!facetSelected && this._allFacetsList.indexOf(selectedFacet) < 0 && facets[i].incomplete) {
                    RightNow.Event.fire("evt_pageLoading");
                    this._facet = this._moreFacet = selectedFacet;
                    this._searchType = 'FACET';
                    this.searchSource().fire('collect').fire('search', new RightNow.Event.EventObject(this, {
                        data: {
                            page: this.data.js.filter,
                            sourceCount: 1
                        }
                    }));
                    this._facet = '';
                    return;
                }

                var parentLi = null,
                    item = parentLi = this.Y.Node.create("<li>" + facets[i].desc + "</li>"),
                    ul = this.Y.Node.create("<ul></ul>");
                ul.append(item);
                parentLi = targetLi.ancestor().ancestor();
                var parentLiSize = targetNode.ancestor().ancestor().all('LI').size() - 1;
                parentLi.setHTML('');//targetLi.innerHTML = '';
                if (facets[i].children.length !== 0)
                    this._findChildren(facets[i], parentLi, parentLiSize + this.data.attrs.max_sub_facet_size);
            }
        }
        this.Y.all(".rn_ToggleExpandCollapse").on('click', this.toggleExpandCollapse, this);

        if(this.data.attrs.toggle_title && this.Y.one(".rn_Expand") == null)
            this._addToggle();
    },

    /**
    * This method is called when response event is fired..
    * @param {object} filter object
    * @param {object} event object
    */
    _onResultChanged: function(type, args) {
        this._searchType = 'PAGE';
        this._facet = '';

        this.Y.one(this.baseSelector + "_Content").get('childNodes').remove();
        if (!args[0] || !args[0].data.searchResults) return;
        if (args[0].data.error === undefined || args[0].data.error.length === 0) {
            if (args[0] && (args[0].data.searchResults === null || args[0].data.searchResults.results === null || args[0].data.searchResults.results.facets === null || args[0].data.searchResults.results.facets.length === 0)) return;
            var facets = args[0].data.searchResults.results.facets;
            if(typeof this.data.attrs.top_facet_list !== 'undefined' && this.data.attrs.top_facet_list !== null && this.data.attrs.top_facet_list.length > 0) {
                facets = this._facetsOrderbyList(facets);
            }
            this.data.js.facets = JSON.stringify(facets);
            var title = this.Y.Node.create("<div class='rn_FacetsTitle'></div>");
            title.append(this.data.attrs.label_filter);
            var clearLink = this.Y.Node.create("<span class='rn_ClearContainer'>[<a role='button' class='rn_ClearFacets'><span class='rn_ScreenReaderOnly'>" + this.data.attrs.label_clear_screenreader + "</span>" + this.data.attrs.label_clear + "</a>]</span>");
            title.append(clearLink);
            var list = this.Y.Node.create("<div class='rn_FacetsList'></div>");
            var ul = this.Y.Node.create("<ul></ul>"),
                parentLi = null,
                item = null,
                facetInEffect = false;

            for (var i = 0; i < facets.length; i++) {
                var currentFacet = facets[i],
                    displayText = currentFacet.desc,
                    facetChildren = currentFacet.children.length;

                    if(currentFacet.inEffect) {
                    facetInEffect = true;
                }
                if(!facetInEffect && facetChildren > 0) {
                    facetInEffect = this._appliedFacetInHierarchy(currentFacet.children[0]);
                }

                if (facetChildren !== 0) {
                    item = this.Y.Node.create("<li>" + displayText + "</li>");
                    if (displayText) {
                        parentLi = this.Y.Node.create("<ul></ul>");
                        item = item.append(parentLi);
                    }
                    ul.append(item);
                    this._findChildren(currentFacet, parentLi, this.data.attrs.max_sub_facet_size);
                }
            }

            if(!facetInEffect) {
                this._resetSingleFacetOnNewSearch();
            }
            list.append(ul);
            this.Y.one(this.baseSelector + "_Content").append(title).append(list);
            this.Y.all(".rn_ToggleExpandCollapse").on('click', this.toggleExpandCollapse, this);
            RightNow.Event.fire("evt_pageLoaded");

            if(this._moreFacet !== ''){
                this._allFacetsList.push(this._moreFacet);
                this._onFacetMoreClick(this._moreFacet, new this.Y.Node(document.getElementById("F:" + this._moreFacet)));
                this._moreFacet = '';
            } else{
                this._allFacetsList = [];
                if(this.data.attrs.toggle_title) {
                    this._addToggle();
                    if (this.data.attrs.toggle_state === 'collapsed' && this._toggle !== null) {
                        this._toggle.addClass(this.data.attrs.collapsed_css_class);
                        this._onToggle(this);
                    }
                }
            }
        }
        else {
            var error = args[0].data.error,
                errorMessage = '<div id="' + this.baseDomID + '_Error" class="rn_ErrorMessage">' + error.errorCode + ': ' + error.externalMessage + ' - ' + error.source + '</div>';
            this.Y.one(this.baseSelector + "_Content").append(errorMessage);
        }
    },

    /**
    * This method handles facet display for multi-facet on a fresh search being fired
    */
    _resetSelectedFacetsOnNewSearch: function() {
        if(!this.data.attrs.retain_facet_on_new_search) {
            var nodeArray = this.Y.all('.rn_ActiveFacet')._nodes;
            for(nodeKey in nodeArray) {
                var node = nodeArray[nodeKey];
                node.classList.remove('rn_ActiveFacet');
                node.children[node.children.length - 1].remove(true);
                node.children[node.children.length - 1].remove(true);
            }
            this._facet = this._multiFacet = '', this._selectedFacetDetails = [];
            this._categArr = [], this._prodArr = [], this._docTypeArr = [], this._collArr = [];
            RightNow.Event.fire("evt_updateFacetFilter", new RightNow.Event.EventObject(this, {data : { selectedFacetDetails : [], facet: this._facet }}));
        }
    },

    /**
    * This method handles facet display for single-facet on a fresh search being fired
    */
    _resetSingleFacetOnNewSearch: function() {
        if(!this.data.attrs.retain_facet_on_new_search) {
            this._categArr = [], this._prodArr = [], this._docTypeArr = [], this._collArr = [];
        }
    },

    /**
    * This method is called when response event is fired..
    * @param {object} filter object
    * @param {object} event object
    */
    _onMultiFacetResultChanged: function(type, args) {
        this._searchType = 'PAGE';
        if(this.Y.one('.rn_DocTypes'))
            this.Y.one('.rn_DocTypes')._node.innerHTML = '';
        if(this.Y.one('.rn_Collections'))
            this.Y.one('.rn_Collections')._node.innerHTML = '';
        //If no results, hide entire facet
        if (!args[0] || !args[0].data.searchResults) {
            this.Y.one(this.baseSelector + "_Content").addClass("rn_Hidden");
            return;
        }
        else if (args[0].data.searchResults && args[0].data.searchResults.results && args[0].data.searchResults.results.facets && args[0].data.searchResults.results.facets.length === 0) {
            this.Y.one(this.baseSelector + "_Content").addClass("rn_Hidden");
            return;
        }
        //No error scenario
        else if (args[0].data.error === undefined || args[0].data.error.length === 0) {
            if (args[0] && (args[0].data.searchResults === null || args[0].data.searchResults.results === null || (args[0].data.searchResults.results.facets === null && args[0].data.searchResults.results.facets.length === 0))) {
                this.Y.one(this.baseSelector + "_Content").addClass("rn_Hidden");return;
            }
            var facets = args[0].data.searchResults.results.facets;
            if(this.Y.one('.rn_FacetsList') && this.Y.one('.rn_FacetsList').hasClass('rn_Hidden'))
                this.Y.one('.rn_FacetsList').removeClass('rn_Hidden');
            if(facets !== undefined && typeof this.data.attrs.top_facet_list !== 'undefined' && this.data.attrs.top_facet_list !== null && this.data.attrs.top_facet_list.length > 0) {
                facets = this._facetsOrderbyList(facets);
            }
            this.data.js.facetsFromResponse = JSON.stringify(facets);
            if(this.Y.one('.rn_ActiveFacet') && this._clearLink)
                this._clearLink.remove('rn_Hidden');
            var list = this.Y.one('.rn_FacetsList'),
                docTypeUl = this.Y.one('.rn_DocTypes'),
                collectionUl = this.Y.one('.rn_Collections'),
                parentLi = null,
                item = null,
                facetInEffect = false;

            for (var i = 0; i < facets.length; i++) {
                var currentFacet = facets[i],
                    displayText = currentFacet.desc,
                    facetChildren = currentFacet.children.length;
                if(currentFacet.inEffect) {
                    facetInEffect = true;
                }
                if(!facetInEffect && facetChildren > 0) {
                    if(currentFacet.children && currentFacet.children.length > 0) {
                        for(key in currentFacet.children) {
                            facetInEffect = this._appliedFacetInHierarchy(currentFacet.children[key]);
                            if(facetInEffect)
                                break;
                        }
                    }
                }
                if(currentFacet.id === 'DOC_TYPES' || currentFacet.id === 'COLLECTIONS') {
                    if (facetChildren !== 0) {
                        item = this.Y.Node.create("<li>" + displayText + "</li>");
                        if (displayText) {
                            parentLi = this.Y.Node.create("<ul></ul>");
                            item = item.append(parentLi);
                        }
                        this._findChildren(currentFacet, parentLi, this.data.attrs.max_sub_facet_size);
                        if(currentFacet.id === 'DOC_TYPES')
                            docTypeUl._node.innerHTML = item._node.innerHTML;
                        else if(currentFacet.id === 'COLLECTIONS')
                            collectionUl._node.innerHTML = item._node.innerHTML;
                    }
                }
            }
            if(!facetInEffect) {
                this._resetSelectedFacetsOnNewSearch();
            }
            if(this.Y.one(this.baseSelector + "_Content").getAttribute('class').indexOf('rn_Hidden') !== -1 ) {
                this.Y.one(this.baseSelector + "_Content").removeClass("rn_Hidden");
            }
        }
        else {
            var error = args[0].data.error,
                errorMessage = '<div id="' + this.baseDomID + '_Error" class="rn_ErrorMessage">' + error.errorCode + ': ' + error.externalMessage + ' - ' + error.source + '</div>';
            this.Y.one(this.baseSelector + "_Content").append(errorMessage);
        }
    },

    /** 
    *   This method renders child facets
    *   @param {Object} current selected facet
    *   @param {boolean} true if current facet has child facet.
    */
    _processChildren : function(currentFacet, hasChildren) {
        var facetLinkClass = currentFacet.inEffect ? 'rn_FacetLink rn_ActiveFacet' : 'rn_FacetLink',
            selectedFacetText = " <span class='rn_ScreenReaderOnly'>" + this.data.attrs.label_active_filter_screenreader + "</span>",
            currentFacetDescription = currentFacet.inEffect ? "<span class='rn_FacetText'>" + currentFacet.desc + "</span>" : "<span title='" + currentFacet.desc + "' class='rn_FacetText'>" + currentFacet.desc + "</span>",
            currentFacetDescription = (facetLinkClass === 'rn_FacetLink rn_ActiveFacet') ? currentFacetDescription + selectedFacetText : currentFacetDescription,
            item = this.data.attrs.enable_multi_select ? this.Y.Node.create("<li class='rn_CategoryExplorerItem'></li>") : this.Y.Node.create("<li></li>"),
            spanExp = hasChildren ? "<span class='rn_ToggleExpandCollapse rn_FacetExpanded'></span>" : "",
            spanIcon = "<span class='rn_FacetClearIcon'></span>";

        item.append(this.Y.Node.create("<a role='button' class='" + facetLinkClass + "' id='" + currentFacet.id + "' href='javascript:void(0)'>" + spanExp + currentFacetDescription + spanIcon + "</a>"));

        return item;
    },

    /** 
    *   This method iterates the child facets recursively
    *   @param {Object} current selected facet
    *   @param {String} parent facet list node
    *   @param {int} maximum depth of facet to be looked
    */
    _findChildren : function(currentFacet, parentLi, maxFacetLength) {
        var currFacet, len = currentFacet.children.length;
        len = (maxFacetLength !== undefined && maxFacetLength !== '') ? maxFacetLength : len;
        for (var i = 0; i < len; ++i) {
            currFacet = currentFacet.children[i];
            if (currFacet !== undefined) {
                if (currFacet.children.length !== 0) {
                    var childLi = this._processChildren(currFacet, true);
                    parentLi.append(childLi);
                    var childUl = this.Y.Node.create("<ul data-id='" + currFacet.id + "' class='rn_FacetTreeIndent'></ul>");
                    childLi.append(childUl);
                    if (typeof maxFacetLength === 'undefined') {
                        this._findChildren(currFacet, childUl);
                    }
                    else {
                        this._findChildren(currFacet, childUl, len);
                    }
                }
                else {
                    parentLi.append(this._processChildren(currFacet, false));
                }
            }
        }
        if (currentFacet.children.length > maxFacetLength) {
            var item = this.Y.Node.create("<li></li>")
                    .append(new EJS({text: this.getStatic().templates.facetLink}).render({currentFacet: currentFacet, attrs: this.data.attrs}));
            parentLi.append(item);
        }
    },

    /**
    * Collects newSearch filter
    * @return {object} Event object
    */
    sendOkcsNewSearch: function() {
        return new RightNow.Event.EventObject(this, {
            data: {value: this._newSearch, key: 'okcsNewSearch', type: 'okcsNewSearch'}
        });
    },

    /**
    * Collects facet filter
    * @return {object} Event object
    */
    updateFacet: function() {
        return new RightNow.Event.EventObject(this, {
            data: {value: this._facet, key: 'facet', type: 'facet'}
        });
    },

    /**
    * Collects searchType filter
    * @return {object} Event object
    */
    updateSearchType: function() {
        return new RightNow.Event.EventObject(this, {
            data: {value: this._searchType, key: 'searchType', type: 'searchType'}
        });
    },

    /**
    * Collects product filter
    * @return {object} Event object
    */
    updateProduct: function() {
        if(this._searchType === 'clearFacet') {
        return new RightNow.Event.EventObject(this, {
            data: {value: null, key: 'prod', type: 'prod'}
        });}
    },

    /**
    * Collects category filter
    * @return {object} Event object
    */
    updateCategory: function() {
        if(this._searchType === 'clearFacet') {
        return new RightNow.Event.EventObject(this, {
            data: {value: null, key: 'cat', type: 'cat'}
        });}
    },

    /**
    * Collects retain facet on new search flag
    * @return {object} Event object
    */
    retainFacetOnNewSearchFlag: function() {
        return new RightNow.Event.EventObject(this, {
            data: {value: this._retainFacetOnNewSearch, key: 'collectFacet', type: 'collectFacet'}
        });
    },

    /**
    * Collects multiFacetsToRetain only for multi facet flow in retain facet true scenario
    * @return {object} Event object
    */
    updateMultiFacetToRetain: function() {
        if(this._retainFacetOnNewSearch && this.data.attrs.enable_multi_select) {
            return new RightNow.Event.EventObject(this, {
                data: {value: this._facet, key: 'multiFacetsToRetain', type: 'multiFacetsToRetain'}
            });
        }
    },

    /**
    * Toggles the expand/collapse view for facet hierarchy
    */
    toggleExpandCollapse: function(e) {
        e.preventDefault();
        e.stopPropagation();
        if(e.target.hasClass('rn_FacetExpanded')) {
            this.Y.one("ul[data-id='" + e.target.get('parentNode').get('id') + "']").hide();
            e.target.removeClass('rn_FacetExpanded');
            e.target.addClass('rn_FacetCollapsed');
        }
        else if(e.target.hasClass('rn_FacetCollapsed')) {
            this.Y.one("ul[data-id='" + e.target.get('parentNode').get('id') + "']").show();
            e.target.removeClass('rn_FacetCollapsed');
            e.target.addClass('rn_FacetExpanded');
        }
    },

    /**
    * sorting the order of the list based on top_facet_list attribute
    * @param  {object} facets in default order
    * @return {object} after sorting the order of the list based on top_facet_list attribute
    */
    _facetsOrderbyList: function(facets) {
        var facetOrder = [],
            tempFacets = [],
            facetOrderDesc = [],
            descFound = false,
            descItemsCount = this.data.attrs.top_facet_list.split(",").length - 1;
        if(descItemsCount <= 0) {
            facetOrder = this.data.attrs.top_facet_list.split("|");
        }
        if((descItemsCount) > 0 && ((descItemsCount) - (this.data.attrs.top_facet_list.split("|").length - 1)) === 1) {
            var facetOrderPairs = this.data.attrs.top_facet_list.split("|");
            for(var i = 0; i < facetOrderPairs.length; i++)
            {
                var facetItemPair = facetOrderPairs[i].split(",");
                facetOrder.push(facetItemPair[0]);
                facetOrderDesc.push(facetItemPair[1]);
                if(!descFound) {
                    descFound = true;
                }
            }
        }
        for (var j = 0; j < facetOrder.length; j++) {
            for (var i = 0; i < facets.length; i++) {
                if(facets[i].id === facetOrder[j].trim()) {
                    if(descFound) {
                        facets[i].desc = facetOrderDesc[j].trim();
                    }
                tempFacets.push(facets[i]);
                }
            }
        }
        return tempFacets;
    },

    /**
    * Event Handler fired when a product or category is selected for multi-select flow only
    * @param {Object} evt Event object
    */
    _onCategoryClick: function(event){
        event.preventDefault();
        event.stopPropagation();
        var selectedFacet = event.target._node.getAttribute('data-id'),
            type = event.target._node.getAttribute('data-type'),
            selectedClass = event.target._node.getAttribute('class');
        if(selectedClass === 'rn_FacetClearIcon') {
            selectedFacet = event.target._node.parentElement.getAttribute('data-id');
            type = event.target._node.parentElement.getAttribute('data-type');
            if(! (selectedFacet && type) ) {
                selectedFacet = event.target._node.parentElement.getAttribute('id');
                type = 'DC';//DocTypes and Collections
            }
        }
        else if(selectedClass === 'rn_FacetText') {
            selectedFacet = event.currentTarget._node.firstElementChild.getAttribute('id');
            type = 'DC';//DocTypes and Collections
            if(event.target.ancestor().hasClass('rn_ActiveFacet')) //For an already chosen DocType or Collection
                selectedClass = 'rn_ActiveFacet';
        }
        if(selectedFacet && type) {
            var prefix = type === 'Product' ? 'CMS-PRODUCT' : 'CMS-CATEGORY_REF';
            if(selectedFacet === 'MoreTopLevels')
                this._renderMoreTopLevels(selectedFacet, event);
            else if(selectedFacet === 'MoreChildLevels')
                this._renderMoreChildLevels(selectedFacet, event);
            else if(selectedFacet === 'MoreNestedChildLevels')
                this._invokeMoreNestedChildLevels(selectedFacet, event);
            //Invoke search for facet selection
            else {
                var appendFacet;
                if(type === 'DC')
                    appendFacet = selectedFacet;
                else
                    appendFacet = prefix + '.' + selectedFacet;
                this._facet = this._multiFacet;
                if(selectedClass.indexOf('rn_ActiveFacet') !== -1 || selectedClass.indexOf('rn_FacetClearIcon') !== -1) {
                //already active facet being clicked again; remove this value from facet filter
                    var facetArr = this._facet.split(',');
                    for(facetKey in facetArr) {
                        if(facetArr[facetKey] === appendFacet) {
                            facetArr.splice(facetKey, 1);
                            break;
                        }
                    }
                    this._facet = facetArr.join();
                }
                else
                    this._facet = (this._facet === '') ? appendFacet : this._facet + ',' + appendFacet;
                this._searchType = 'multiFacet';
                this._retainFacetOnNewSearch = this.data.attrs.retain_facet_on_new_search;
                if(Object.keys(this._selectedFacetDetails).length !== 0 && this._selectedFacetDetails[appendFacet] !== undefined)
                    delete this._selectedFacetDetails[appendFacet];
                else
                    this._selectedFacetDetails[appendFacet] = event.target._node.textContent.trim();
                if(Object.keys(this._selectedFacetDetails).length !== 0 ) {
                    var categArr = [], prodArr = [], docTypeArr = [], collArr = [], facetData = {};
                        for(key in this._selectedFacetDetails) {
                        if(key.indexOf('CMS-CATEGORY_REF') !== -1) {
                            categArr.push(key + ':' + this._selectedFacetDetails[key]);
                        }
                        if(key.indexOf('CMS-PRODUCT') !== -1) {
                            prodArr.push(key + ':' + this._selectedFacetDetails[key]);
                        }
                        if(key.indexOf('DOC_TYPES') !== -1) {
                            docTypeArr.push(key + ':' + this._selectedFacetDetails[key]);
                        }
                        if(key.indexOf('COLLECTIONS') !== -1) {
                            collArr.push(key + ':' + this._selectedFacetDetails[key]);
                        }
                    }
                    facetData = {
                        'CMS-PRODUCT' : prodArr,
                        'CMS-CATEGORY_REF' : categArr,
                        'DOC_TYPES' : docTypeArr,
                        'COLLECTIONS' : collArr
                    };
                }
                RightNow.Event.fire("evt_updateFacetFilter", new RightNow.Event.EventObject(this, {data : { selectedFacetDetails : facetData, facet: this._facet}}));
                this.searchSource().fire('collect').fire('search', new RightNow.Event.EventObject(this, {
                    data: {
                        page: this.data.js.filter,
                        sourceCount: 1
                    }
                }));
                this._multiFacet = this._facet;
                if(!this.data.attrs.retain_facet_on_new_search)
                    this._facet = '';

                if(selectedClass.indexOf('rn_ActiveFacet') > -1) {
                    event.target._node.classList.remove('rn_ActiveFacet');
                    event.target._node.children[event.target._node.children.length - 1].remove(true);// removes the spanIcon
                    event.target._node.children[event.target._node.children.length - 1].remove(true);// removes the screenreader selectedFacetText
                }
                else if(selectedClass === 'rn_FacetClearIcon') {
                    event.target._node.parentElement.classList.remove('rn_ActiveFacet');
                    event.target._node.parentElement.children[event.target._node.parentElement.children.length - 2].remove(true);
                    event.target._node.parentElement.children[event.target._node.parentElement.children.length - 1].remove(true);
                }
                else {
                    var selectedFacetText = "<span class='rn_ScreenReaderOnly'>" + this.data.attrs.label_active_filter_screenreader + "</span>",
                        spanIcon = "<span class='rn_FacetClearIcon'></span>";
                    event.target.insert(selectedFacetText + spanIcon);
                    event.target._node.classList.add('rn_ActiveFacet');
                }
            }
        }
    },

    /**
     * Event handler when a facet node is expanded.
     * Requests the next sub-level of items from the server.
     * @param {object} event Event
     */
    _expandCategory: function(event) {
        event.preventDefault();
        event.stopPropagation();
        var collapsedLink = event.target,
            selectedCategoryID = collapsedLink.getAttribute('id').replace("_Collapsed", ""),
            expandedLink = this.Y.one("#" + selectedCategoryID + "_Expanded"),
            categoryLink = this.Y.one("#" + selectedCategoryID);
        collapsedLink.setAttribute('class', 'rn_CategoryExplorerCollapsedHidden');
        expandedLink.setAttribute('class', 'rn_CategoryExplorerExpanded');
        if (categoryLink.next()) {
            categoryLink.next().setAttribute('class', 'rn_CategoryExplorerList');
            this.Y.all('.rn_CategoryExplorerExpanded').on("click", this._collapseCategory, this);
            return false;
        }
        //only allow one node at-a-time to be expanded
        if (this._nodeBeingExpanded || (categoryLink.expanded && !this.data.js.linkingOn)) return;
        this._nodeBeingExpanded = true;
        this._invokeMoreNestedChildLevels(categoryLink, event);
        this._nodeBeingExpanded = false;
        return false;
    },

    /**
     * Method to invoke child categories for nested levels 3 and beyond.
     * Requests the next sub-level of items from the server.
     * @param {object} Selected Node
     * @param {object} event Event
     */
    _invokeMoreNestedChildLevels: function(selectedNode, event) {
        RightNow.Event.fire("evt_pageLoading");
        if(selectedNode === 'MoreNestedChildLevels') {
            this.parentKey = event.target._node.getAttribute('data-key').split('/')[0];
            var categArr = event.target._node.getAttribute('data-key').split('.'),
                categIdCombo = categArr[categArr.length - 1],
                categId = categIdCombo.split('/')[0],
                type = event.target._node.getAttribute('data-type'),
                nodeList = event.target._node.parentElement.parentElement.parentElement,
                offset = categIdCombo.split('/')[1],
                limit = this.data.attrs.max_sub_facet_size + parseInt(offset, 10);
        }
        else {
            this.parentKey = selectedNode.getAttribute('data-id');
            var categArr = selectedNode.getAttribute('data-id').split('.'),
                categId = categArr[categArr.length - 1],
                type = selectedNode.getAttribute('data-type'),
                nodeList = event.target._node.parentElement,
                offset = 0,
                limit = this.data.attrs.max_sub_facet_size;
        }
        var eventObject = new RightNow.Event.EventObject(this, {data: { categoryId: categId, offset: offset, limit: this.data.attrs.max_sub_facet_size}});
        RightNow.Ajax.makeRequest(this.data.attrs.get_okcs_data_ajax, eventObject.data, {
            successHandler: function(response,args){
                var hasMore = response.hasMore,
                    categories = response.items;

                if(selectedNode === 'MoreNestedChildLevels')
                    event.target._node.remove(true);// Remove more link
                if(hasMore) {
                    var dataId = "MoreNestedChildLevels",
                        dataKey = (this.parentKey + '/' + limit),
                        moreLinkObj = {'externalType' : type.toUpperCase(), 'hasChildren' : false, 'dataId' : dataId, 'name' : this.data.attrs.label_more, 'dataKey' : dataKey};
                    categories.push(moreLinkObj);
                }
                var categoryListNode = this._createCategoryListNode(categories, type, 0, false, false)
                if(nodeList) {
                    nodeList.appendChild(categoryListNode._node);
                }
                this._addCssForUrlCategories(type);
                RightNow.Event.fire("evt_pageLoaded");
            },
            json: true,
            scope: this
        });
    },

    /** Method called to collapse a category
    * @param {object} event object
    */
    _collapseCategory: function(event) {
        event.preventDefault();
        event.stopPropagation();
        var expandedLink = event.target,
            categoryID = expandedLink.getAttribute('id').replace("_Expanded", ""),
            categoryLink = this.Y.one("#" + categoryID),
            collapsedLink = this.Y.one("#" + categoryID + "_Collapsed");
        expandedLink.setAttribute('class', 'rn_CategoryExplorerExpandedHidden');
        collapsedLink.setAttribute('class', 'rn_CategoryExplorerCollapsed');
        categoryLink.next().setAttribute('class', 'rn_CategoryExplorerListHidden');
    },

    /** Method called to add selected facet style
    * @param {object} type Category Type
    */
    _addCssForUrlCategories: function(type) {
        var selectedFacetText = "<span class='rn_ScreenReaderOnly'>" + this.data.attrs.label_active_filter_screenreader + "</span>",
            spanIcon = "<span class='rn_FacetClearIcon'></span>";
        // Applying active facet style for a child level (if present in URL)
        if(type === 'Product'){
            if(this.data.js.urlProduct.length > 0) {
                for(i = 0; i <= this.data.js.urlProduct.length; ++i) {
                    var urlNode = this.Y.one(this.baseSelector + '_' + this.data.js.urlProduct[i]);
                    if(urlNode && !urlNode.hasClass('rn_ActiveFacet')) {
                        urlNode.addClass('rn_ActiveFacet').insert(selectedFacetText + spanIcon);
                    }
                }
            }
        }
        else if(type === 'Category'){
            if(this.data.js.urlCategory.length > 0) {
                for(i = 0; i <= this.data.js.urlCategory.length; ++i) {
                    var urlNode = this.Y.one(this.baseSelector + '_' + this.data.js.urlCategory[i]);
                    if(urlNode && !urlNode.hasClass('rn_ActiveFacet')) {
                        urlNode.addClass('rn_ActiveFacet').insert(selectedFacetText + spanIcon);;
                    }
                }
            }
        }
    },

    /** Method to create an iterative array of products and categories
    **  for multiselect flow only
    * @param (object) inputArray Input Array
    * @param (string) refKey Category Reference Key
    * @param (int) prodCategCount Category Length
    * @param (boolean) isTopLevel Flag to determine if request is for top or nested levels
    * @return {object} categories Categories object
    */
    _createProdCatIterArray: function(inputArray, refKey, prodCategCount, isTopLevel) {
        var categories = [], count = 0, isPopulate = false;
        var inputArrayLength = Object.keys(inputArray).length;
        var type = '';
        var limit = (inputArrayLength > prodCategCount) ? prodCategCount : inputArrayLength;
        for(key in inputArray){
            if(key === refKey) {
                isPopulate = true;
            }
            if(count < limit && isPopulate) {
                categories.push(inputArray[key]);
                type = inputArray[key].externalType;
            }
            if(count === limit)
                break;
            ++count;
        }
        if(inputArrayLength > prodCategCount) {
                var dataId = isTopLevel ? "MoreTopLevels" : "MoreChildLevels";
                var dataKey = isTopLevel ? (key + '/' + prodCategCount) : (this.parentKey + '/' + key + '/' + prodCategCount);
            var moreLinkObj = {'externalType' : type, 'hasChildren' : false, 'dataId' : dataId, 'name' : this.data.attrs.label_more, 'dataKey' : dataKey};
            categories.push(moreLinkObj);
        }
        return categories;
    },

    /** Method called to display more top level products for multiselect flow only
    * @param {object} Selected Node
    * @param (object) event Click event object
    */
    _renderMoreTopLevels: function(selectedFacet, event) {
        event.preventDefault();

        var refKey = event.target._node.getAttribute('data-key'),
            type = event.target._node.getAttribute('data-type'),
            keys = refKey.split('/'),
            limit = this.data.attrs.max_sub_facet_size + parseInt(keys[1], 10),
            categories;

        if(type === 'Product')
            categories = this._createProdCatIterArray(this.data.js.products.topLevelProduct, keys[0], limit, true);
        else
            categories = this._createProdCatIterArray(this.data.js.categories.topLevelCategory, keys[0], limit, true);

        var nodeList = event.target._node.parentElement.parentElement;
        if(event.target._node)
            event.target._node.remove(true);
        var categoryListNode = this._createCategoryListNode(categories, type, 0, true, true);
        for(i = 0; i < categoryListNode._nodes.length; i++) {
                nodeList.appendChild(categoryListNode._nodes[i]);
        }
        this._addCssForUrlCategories(type);
    },

    /** Method called to display more child level products and categories for multiselect flow only
    * @param {object} Selected Node
    * @param (object) event Click event object
    */
    _renderMoreChildLevels: function(selectedFacet, event) {
        event.preventDefault();
        var refKey = event.target._node.getAttribute('data-key'),
            type = event.target._node.getAttribute('data-type'),
            keys = refKey.split('/'),
            limit = this.data.attrs.max_sub_facet_size + parseInt(keys[2], 10),
            categories;
        this.parentKey = keys[0];
        var nodeList = event.target._node.parentElement.parentElement;
        if(nodeList) {
            if(type === 'Product')
                categories = this._createProdCatIterArray(this.data.js.products.topLevelProduct[keys[0]].children, keys[1], limit, false);
            else
                categories = this._createProdCatIterArray(this.data.js.categories.topLevelCategory[keys[0]].children, keys[1], limit, false);
            if(event.target._node)
                event.target._node.remove(true);// destroy the entire li item
            var categoryListNode = this._createCategoryListNode(categories, type, 0, true, false);
            for(i = 0; i < categoryListNode._nodes.length; i++) {
                nodeList.appendChild(categoryListNode._nodes[i]);
            }
            this._addCssForUrlCategories(type);
        }
    },

    /** This method return a list of category nodes
    *   @param {list} categories category list
    *   @param {String} categoryType Product or Category
    *   @param {int} depth maximum depth of product or category
    *   @param {boolean} appendList Flag to determine if new category list is to be created or category list is to be appended
    *   @param {boolean} isTopLevel Flag to determine if new node is at toplevel or not
    */
    _createCategoryListNode : function(categories, categoryType, depth, appendList, isTopLevel) {
        if(categories === undefined || categories === null) {
            return null;
        }
        var nodeList = new this.Y.NodeList(this.Y.Array.map(categories, function (category) {
                                                return this._createCategoryNode(category, categoryType, depth, isTopLevel);
                                            }, this));
        if(!appendList)
            return this.Y.Node.create('<ul class="rn_CategoryExplorerList"></ul>').append(nodeList);
        else
            return nodeList;
    },

    /** This method category a node element.
    * @param {object} category object
    * @param {String} categoryType Product or Category
    * @param {int} depth maximum depth of product or category
    */
    _createCategoryNode : function(category, categoryType, depth, isTopLevel) {
        var currentCategoryType = (category.externalType === 'PRODUCT' ? 'Product' : 'Category');
        var item = this.Y.Node.create('<b>'),
            id = this.baseDomID + "_" + category.referenceKey,
            dataId = (isTopLevel) ? category.referenceKey : (this.parentKey + '.' + category.referenceKey);
            isNested = false;
        if(currentCategoryType === categoryType) {
            if (!category.hasChildren) {
                if(category.dataKey)
                    var categoryLink = this.Y.Node.create('<a href="javascript:void(0)" class="rn_LeafNode rn_CategoryExplorerLink" id="' + this.baseDomID + "_" + category.dataId + '" data-id="' + category.dataId + '" data-key="' + category.dataKey + '" data-depth="' + depth + '" data-type="' + categoryType + '">' + category.name + '</a>');
                else
                    var categoryLink = this.Y.Node.create('<a href="javascript:void(0)" class="rn_LeafNode rn_CategoryExplorerLink" id="' + id + '" data-id="' + dataId + '" data-depth="' + depth + '" data-type="' + categoryType + '">' + category.name + '</a>');
                item = this.Y.Node.create('<li class="rn_CategoryExplorerItem"><div class="rn_CategoryExplorerLeaf"></div><a role="button" id="' + id + '_Collapsed" class="rn_CategoryExplorerCollapsedHidden" href="javascript:void(0)"><span class="rn_ScreenReaderOnly">' + this.data.attrs.label_expand_icon + '</span></a></li>');
            }
            else {
                var categoryLink = this.Y.Node.create('<a href="javascript:void(0)" class="rn_CategoryExplorerLink" id="' + id + '" data-id="' + dataId + '" data-depth="' + depth + '" data-type="' + categoryType + '">' + category.name + '</a>');
                item = this.Y.Node.create('<li class="rn_CategoryExplorerItem"><a role="button" id="' + id + '_Expanded" class="rn_CategoryExplorerExpandedHidden" href="javascript:void(0)"><span class="rn_ScreenReaderOnly">' + this.data.attrs.label_expand_icon + '</span></a><a role="button" id="' + id + '_Collapsed" class="rn_CategoryExplorerCollapsed" href="javascript:void(0)"><span class="rn_ScreenReaderOnly">' + this.data.attrs.label_collapse_icon + '</span></a></li>');
            }
            item.append(categoryLink);
        }
        if(category.children && Object.keys(category.children).length > 0) {
            this.parentKey = category.referenceKey;
            isNested = true;
        }
        return (isNested ? item.append(this._createCategoryListNode(this._createProdCatIterArray(category.children, Object.keys(category.children)[0], this.data.attrs.max_sub_facet_size, false),category.type, 0, false)) : item);
    },

    /** This method checks if any facet in the hierarchy is in effect
    * and returns the appropriate flag.
    * @param {object} facetList Facet object
    * @param {boolean} true if any facet is in effect.
    */
    _appliedFacetInHierarchy: function(facetList) {
        if(facetList.inEffect) {
            return true;
        }
        else if(facetList.children && facetList.children.length > 0) {
            return this._appliedFacetInHierarchy(facetList.children[0]);
        }
        return false;
    }
});
