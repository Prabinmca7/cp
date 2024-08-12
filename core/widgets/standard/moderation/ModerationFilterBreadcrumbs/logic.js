RightNow.Widgets.ModerationFilterBreadcrumbs = RightNow.SearchFilter.extend({

    overrides: {
        constructor: function() {
            this.parent();
            this._filterToAdd = {};
            this._currentDomID = 0;
            this.searchSource().on('response', this._onReportResponse, this);

            this.Y.Array.each(this.data.js.filters, function(filter) {
                var filterData = [], filterValue = filter.data[filter.data.length - 1].id, templateData;
                this.Y.Array.each(filter.data, function(dataElement) {
                    filterData.push(this.buildFilterData(dataElement.id, dataElement.label));
                }, this);
                templateData = this.getFilterDataForTemplate(filter.urlParameter, filter.label, filterValue, filterData);
                this.Y.one('#' + templateData.removeLinkID).on('click', this._onFilterClear, this, templateData.divID, filter.urlParameter);
            }, this);
        }
    },

    /**
     * Event handler that is triggered when the 'x' is clicked on a breadcrumb filter. Fires an event to reset the selected
     * filter to its page load value, removes it from the next search and performs a fresh search.
     * @param {Object} evt Event name
     * @param {string} filterDomID The DOM ID of the filter being removed
     * @param {string} filterUrlParameter The URL parameter of the filter being removed
     */
    _onFilterClear: function(evt, filterDomID, filterUrlParameter) {
        evt.preventDefault();
        var eo = new RightNow.Event.EventObject(this, {filters: {report_id: this.data.attrs.report_id}, data: {name: filterUrlParameter,ignoreLastSearch:true,ignoreInitial:true}});
        this.Y.one('#' + filterDomID).remove(true);
        this.searchSource().fire('reset', eo).fire('excludeFilterFromNextSearch', eo).fire('search', eo);
    },

    /**
     * Event handler that is triggered anytime a new set of search results is sent out to the widgets. Looks through
     * the list of outgoing filters and grabs those that are supported (e.g questions.status, questions.updated, comments.status, comments.updated etc), and
     * construct the data for EJS template and create HTML, then insert it in the page.
     * @param {Object} evt Event name
     * @param {Object} args Event object containing search data
     */
    _onReportResponse: function(evt, args) {
        var reportFilters = args[0].filters.allFilters,
            filterContainer = this.Y.one(this.baseSelector + '_FilterContainer'),
            hasFilters = false, filterValueArray, filterType, filterLabel, filter, Y = this.Y, templateData;

        //Remove all the existing filters from DOM
        filterContainer.get('childNodes').remove(true);

        for (filterType in this.data.js.allAvailableFilters) {
        //Process only allowed filters
        if (filterType in reportFilters) {
                filter = reportFilters[filterType] ? reportFilters[filterType].filters : null;
                filterValueArray = this.getFilterValueArray(filterType, filter) || null;
                if (filterValueArray && !this.isDefaultFilter(filterType, filterValueArray)) {
                    var filterData = [], prodcatValue;
                    if (filterType === 'p' || filterType === 'c') {
                        filterLabel = filterType === 'p' ? this.data.attrs.label_product_filter : this.data.attrs.label_category_filter;
                        for (var j = filter.data[0].length - 1; j >= 0; j--) {
                            prodcatValue = filter.data.reconstructData[j].hierList.split(',');
                            filterData.push(this.buildFilterData(prodcatValue[prodcatValue.length - 1], filter.data.reconstructData[filter.data[0].length - j - 1].label));
                        }
                    } else {
                        for (var i = 0; i < filterValueArray.length; i++) {
                            filterLabel = this.data.js.allAvailableFilters[filterType].caption;
                            var formatter = this.getFormatter(filterType);
                            var filterValue = this.data.js.allAvailableFilters[filterType].filters[filterValueArray[i]];
                            filterValue = (!filterValue && formatter) ? formatter(filterValueArray[i]) : (filterValue || filterValueArray[i]);
                            filterData.push(this.buildFilterData(filterValueArray[i], filterValue));
                        }
                    }
                    if (filterData.length > 0) {
                        templateData = this.getFilterDataForTemplate(filterType, filterLabel, filterValueArray[filterValueArray.length - 1], filterData);
                        filterContainer.append(new EJS({text: this.getStatic().templates.view}).render(templateData));
                        filterContainer.one('#' + templateData.removeLinkID).on('click', this._onFilterClear, this, templateData.divID, filterType);
                        hasFilters = true;
                    }
                }
            }
        }
        (hasFilters) ? Y.one(this.baseSelector).removeClass('rn_Hidden') : this.Y.one(this.baseSelector).addClass('rn_Hidden');
    },

    /**
     * Extracts the filter values of a filter from all filters array
     * @param {string} filterName Name of the filter
     * @param {array} filter Array of filter values
     * @return {string|array} Filter value
     */
    getFilterValueArray: function(filterName, filter) {
        var filterValueArray;
        if (filter && !this.Y.Object.isEmpty(filter.data)
                && (!this.Y.Lang.isArray(filter.data) || filter.data.length === 1 && filter.data[0])) {
            if ((filterName === 'p' || filterName === 'c') && filter.data.reconstructData === undefined) {
                return null;
            }
            filterValueArray = this.Y.Lang.isArray(filter.data) ? filter.data[0] : filter.data.split(",");
            filterValueArray = this.Y.Array.map(filterValueArray, function(value) {
                return this.Y.Lang.trim(value);
            }, this);
            return filterValueArray;
        }
        return null;
    },

    /**
     * Used to check if a filter is a 'default' filter. Default filters are chosen by the server on initial page load
     * and do not have a breadcrumb displayed (e.g. if a page is hit with /questions.updated/5 the breadcrumb will not be display, since 5 is
     * the default value for Date filter).
     * @param {string} filterType The type of filter to compare against (e.g questions.status, questions.updated, comments.status, comments.updated, users.status etc)
     * @param {Object} filterValue Array of possible values for a filter.
     */
    isDefaultFilter: function(filterType, filterValue) {
        if(!this.Y.Lang.isArray(this.data.js.defaultFilters[filterType])){
            return filterValue.length === 1 && this.data.js.defaultFilters[filterType] === filterValue[0];
        }
        return this.areArraysEqual(this.data.js.defaultFilters[filterType], filterValue);
    },

    /**
     * Returns True if both array contains the same elements else false
     * @param {array} array1 Array of elements
     * @param {array} array2 Array of elements
     * @return {boolean} true or false
     */
    areArraysEqual: function(array1, array2) {
        if (this.Y.Lang.isArray(array1) && this.Y.Lang.isArray(array2)) {
            if (array1.length !== array2.length) {
                return false;
            }
            array1 = this.toStringArrayElements(array1).sort();
            array2 = this.toStringArrayElements(array2).sort();

            for (var i = 0; i < array1.length; i++) {
                if (array1[i] !== array2[i]) {
                    return false;
                }
            }
            return true;
        }
        return false;
    },

    /**
     * Converts the all the array elements to string
     * @param {array} arr Array of elemtns
     * @return {array} Array of string elements
     */
    toStringArrayElements: function(arr) {
        return this.Y.Array.map(arr, function(value) {
            return this.Y.Lang.trim(String(value));
        }, this);
    },

    /**
     * Construct the data object used by the EJS template. Converts the data from the report response into
     * the best format for rendering the template.
     * @param {string} filterValue The ID for a filter.
     * @param {string} filterName The label displayed on the breadcrumb (e.g. Active Social Question, Active Social User etc)
     * @returns {Object} The filter data for a single breadcrumb element
     */
    buildFilterData: function(filterValue, filterName) {
        return {
            'linkID': this.baseDomID + '_Filter_' + this._currentDomID + '_' + filterValue,
            'label': filterName
        };
    },

    /**
     * Given all of the data for a filter.The data is stored in the structure that the EJS template expects.
     * @param {string} filterType The URL parameter of the filter
     * @param {string} filterLabel The heading label displayed before the breadcrumb (e.g. Status, Date)
     * @param {string} filterValue The ID for a filter.
     * @param {Object} filterData The filter data object created with buildFilterData
     * @returns {Object} The template data object with all breadcrumb element data
     */
   getFilterDataForTemplate: function(filterType, filterLabel, filterValue, filterData) {
       if(!this._filterToAdd[filterType]) {
            this._filterToAdd[filterType] = {'domID': this._currentDomID};
            this._currentDomID++;
        }
        return (this._filterToAdd[filterType][filterValue] = {
            'divID': this.baseDomID + '_Filter_' + this._filterToAdd[filterType].domID,
            'label': filterLabel,
            'removeLinkID': this.baseDomID + '_Remove_' + this._filterToAdd[filterType].domID,
            'labelFilterRemove': this.data.attrs.label_filter_remove,
            'filterData': filterData,
            'filterType': filterType
        });
    },

    /**
     * Creates different filter value formatter which are used to
     * format the filter value to be displayed
     *
     * @param {String} filterName Name of the filter
     */
    getFormatter: function(filterName) {
        if (!this.formatter) {
            var dateFormatter = function(value) {
                var formattedValue;
                var pipeIndex = value.indexOf('|');
                if (pipeIndex !== -1) {
                    formattedValue = value.substring(0, pipeIndex) + ' - ' + value.substring(pipeIndex + 1);
                }
                return formattedValue || value;
            };

            this.formatter = {
                'questions.updated': dateFormatter,
                'comments.updated': dateFormatter
            };
        }
        return this.formatter[filterName];
    }

});