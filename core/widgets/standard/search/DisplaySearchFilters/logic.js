RightNow.Widgets.DisplaySearchFilters = RightNow.SearchFilter.extend({
    overrides: {
        constructor: function() {
            this.parent();
            this._filterCache = {};
            this._currentDomID = 0;
            this.searchSource().on('response', this._onReportResponse, this);

            //Add the filter data from the server into our local cache so that we don't need to generate
            //it again when a search request goes out.
            this.Y.Array.each(this.data.js.filters, function(filter, index) {
                var filterData = [],
                    filterValue = filter.data[filter.data.length - 1].id,
                    templateData;
                this.Y.Array.each(filter.data, function(dataElement) {
                    filterData.push(this.buildFilterData(dataElement.id, filter.urlParameter, dataElement.label));
                }, this);
                templateData = this.addFilterToCache(filter.urlParameter, filter.label, filterValue, filterData);
                this.Y.one('#' + templateData.removeLinkID).on('click', this._onFilterRemove, this, templateData.divID, filter.urlParameter);
            }, this);
        }
    },

    _escapeHtml: function(string) {
        var entityMap = {
            '&amp;':    '&',
            '&lt;':     '<',
            '&gt;':     '>',
            '&quot;':   '"',
            '&#x27;':   "'",
            '&#x2F;':   '/',
            '&#x60;':   '`'
        };

        // ensure entities are not double encoded
        return this.Y.Escape.html((string + '').replace(/&[^;]+;/g, function(s) { return entityMap[s] || s; }));
    },

    /**
     * Event handler that is triggered when the 'x' is clicked on a search filter. Fires an event to reset the selected
     * filter to its page load value, removes it from the next search (in case the filter isn't on the page and can't be polled),
     * and performs a fresh search.
     * @param {Object} evt Event name
     * @param {string} filterDomID The DOM ID of the filter being removed
     * @param {string} filterUrlParameter The URL parameter of the filter being removed
     */
    _onFilterRemove: function(evt, filterDomID, filterUrlParameter) {
        var canonicalFilterName = (filterUrlParameter === 'st') ? 'searchType' : filterUrlParameter;
            eo = new RightNow.Event.EventObject(this, {filters: {report_id: this.data.attrs.report_id}, data: {name: canonicalFilterName}});

        this.Y.one('#' + filterDomID).remove(true);
        this.searchSource().fire('reset', eo);
        this.searchSource().fire('excludeFilterFromNextSearch', eo);
        this.searchSource().fire('search', eo);
    },

    /**
     * Event handler that is triggered anytime a new set of search results is sent out to the widgets. Looks through
     * the list of outgoing filters and grabs those that are supported (p, c, st, org). If the supplied filter
     * is new (has a different ID), cache the information necessary for the EJS template so that subsequent history searches don't require
     * label information. If it has already been cached, retrieve it from the cache and use EJS to generate the appropriate
     * HTML, then insert it in the page.
     * @param {Object} evt Event name
     * @param {Object} args Event object containing search data
     */
    _onReportResponse: function(evt, args) {
        var reportFilters = args[0].filters.allFilters,
            filterContainer = this.Y.one(this.baseSelector + '_FilterContainer'),
            hasFilters = false, filterValue, filterType, filterLabel, filter, Y = this.Y;

        //Remove all of the filters, we'll re-add them later
        Y.one(this.baseSelector).removeClass('rn_Hidden');
        filterContainer.get('childNodes').remove(true);

        for(filterType in reportFilters) {
            //Only operate on the allowed filters (This set should be easy to expand in the future)
            if(Y.Array.indexOf(['p', 'c', 'searchType', 'org'], filterType) !== -1) {
                if((filter = reportFilters[filterType]) && (filter = reportFilters[filterType].filters) && filter.data) {
                    //If it's a product or category filter then our ID is the last one in the chain. Otherwise, it's just the value.
                    if(filterType === 'p' || filterType === 'c') {
                        filterValue = (filter.data[0] && filter.data[0].length) ? ((Array.isArray(filter.data[0])) ? (filter.data[0][filter.data[0].length - 1]).toString() : filter.data[0]) : null;
                        if(filterValue && filterValue.indexOf(',') !== -1) {
                            var getLastValue = filterValue.split(',');
                            filterValue = getLastValue[getLastValue.length - 1];
                        }
                    }
                    else if(filterType === 'searchType') {
                        filterType = 'st';
                        filterValue = (Y.Lang.isObject(filter.data)) ? filter.data.val : filter.data;
                    }
                    else if(filterType === 'org') {
                        filterValue = (Y.Lang.isObject(filter.data)) ? filter.data.selected : filter.data;
                    }

                    if(Y.Lang.isValue(filterValue) && !Y.Lang.isObject(filterValue, true) && !this.isDefaultFilter(filterType, filterValue)) {
                        //Check if the filter has a valid set of template data already for that type and value. If so, then all we need to do
                        //is regenerate the HTML and add it to the page. If not, add it to the cache so that we have it in case the history
                        //manager is used.
                        if(!(templateData = this.getFilterFromCache(filterType, filterValue))) {
                            var filterData = [];
                            if(filterType === 'p' || filterType === 'c') {
                                filterLabel = (filterType === 'p') ? RightNow.Interface.getMessage('PRODUCT_LBL') : RightNow.Interface.getMessage('CATEGORY_LBL');
                                Y.Array.each(filter.data.reconstructData, function(element, index) {
                                    var prodcatValue = element.hierList.split(',');
                                    filterData.push(this.buildFilterData(prodcatValue[prodcatValue.length - 1], filterType, element.label));
                                }, this);
                            }
                            else if(filterType === 'st') {
                                filterLabel = RightNow.Interface.getMessage('SEARCH_TYPE_LBL');
                                filterData.push(this.buildFilterData(filterValue, filterType, filter.data.label));
                            }
                            else if(filterType === 'org') {
                                filterLabel = RightNow.Interface.getMessage('ORGANIZATION_LBL');
                                filterData.push(this.buildFilterData(filterValue, filterType, filter.data.label));
                            }
                            templateData = this.addFilterToCache(filterType, filterLabel, filterValue, filterData);
                        }

                        //Now that we have the template data, generate the HTML, add the remove handlers and null the last link in the filter chain.
                        filterContainer.append(new EJS({text: this.getStatic().templates.view}).render(templateData));
                        filterContainer.one('#' + templateData.removeLinkID).on('click', this._onFilterRemove, this, templateData.divID, filterType);

                        var filterElement = filterContainer.one("a[id='" + templateData.filterData[templateData.filterData.length - 1].linkID + "']");
                        if (filterElement) {
                            filterElement.set('href', 'javascript:void(0)');
                        }
                        hasFilters = true;
                    }
                }
            }
        }

        if(!hasFilters) {
            this.Y.one(this.baseSelector).addClass('rn_Hidden');
        }
    },

    /**
     * Used to check if a filter is a 'default' filter. Default filters are chosen by the server on initial page load
     * and do not have a breadcrumb displayed (e.g. if a page is hit with /org/0 the breadcrumb will not be display, since 0 is
     * the default org value).
     * @param {string} filterType The type of filter to compare against (p, c, st, org)
     * @param {number} filterValue The ID for a filter. A single integer.
     */
    isDefaultFilter: function(filterType, filterValue) {
        var defaultFilters = this.data.js.defaultFilters, i;
        for(i = 0; i < defaultFilters.length; i++) {
            if(defaultFilters[i].name === filterType && defaultFilters[i].defaultValue === filterValue) {
                return true;
            }
        }
        return false;
    },

    /**
     * Construct the data object used by the EJS template. Converts the data from the report response into
     * the best format for rendering the template.
     * @param {string} filterValue The ID for a filter. A single integer.
     * @param {number} filterType The URL parameter of the filter
     * @param {string} filterName The label displayed on the breadcrumb (e.g. iPhone, All my Incidents, Custom Search Type)
     * @returns {Object} The filter data for a single breadcrumb element
     */
    buildFilterData: function(filterValue, filterType, filterName) {
        return {
            'linkID': this.baseDomID + '_Filter' + filterValue,
            'linkUrl': this.data.js.searchPage + filterType + '/' + filterValue,
            'label': this._escapeHtml(filterName)
        };
    },

    /**
     * Given all of the data for a filter insert it into the cache so that any subsequent response doesn't need to
     * rebuild the data. As an added bonus, when using History the cache will contain all the data necessary
     * to render a filter and the mess can be avoided. The data is stored in the structure that the EJS template expects.
     * @param {string} filterType The URL parameter of the filter
     * @param {string} filterLabel The heading label displayed above the breadcrumb (e.g. Organization, Product, Search Type, Category)
     * @param {number} filterValue The ID for a filter. A single integer
     * @param {Object} filterData The filter data object created with buildFilterData
     * @returns {Object} The stored template data object with all breadcrumb element data
     */
    addFilterToCache: function(filterType, filterLabel, filterValue, filterData) {
        if(!this._filterCache[filterType]) {
            this._filterCache[filterType] = {};
            this._filterCache[filterType].domID = this._currentDomID;
            this._currentDomID++;
        }

        var domID = this._filterCache[filterType].domID;
        return (this._filterCache[filterType][filterValue] = {
            'divID': this.baseDomID + '_Filter_' + domID,
            'label': filterLabel,
            'removeLinkID': this.baseDomID + '_Remove_' + domID,
            'labelFilterRemove': this.data.attrs.label_filter_remove.replace('%s', filterLabel),
            'removeIconPath': this.data.attrs.remove_icon_path,
            'filterData': filterData
        });
    },

    /**
     * Retrieves a template data object that was stored with addFilterToCache.
     * @param {string} filterType The URL parameter of the filter
     * @param {number} filterValue The ID of the filter to be retrieved
     * @returns {Object} The stored template data object
     */
    getFilterFromCache: function(filterType, filterValue) {
        if(this._filterCache[filterType] && this._filterCache[filterType][filterValue]) {
            return this._filterCache[filterType][filterValue];
        }
        return null;
    }
});
