RightNow.Widgets.OkcsManageRecommendations = RightNow.SearchConsumer.extend({
    overrides: {
        constructor: function() {
            this.parent();
            this._contentName = this.baseSelector + '_Content';
            this._contentDiv = this.Y.one(this._contentName);
            this._data = this.data.js.articles;
            this._isTable = this.data.js.viewType === 'table';
            this.searchSource().setOptions(this.data.js.sources);
            this._loadingDiv = this.Y.one(this.baseSelector + '_Loading');
            if (this._isTable) {
                this._gridName = this.baseSelector + '_Grid';
                this._dataTable = null;
                this._columns = [];
                this._sortOn = null;
            }
            this.Y.one(this.baseSelector).delegate('click', this._onRecommendationClick, 'a.rn_RecommendationLink', this);
            this.searchSource().setOptions({"history_source_id": this.data.attrs.source_id});
            if(typeof this.searchSource().options.params === 'undefined'){
                this.searchSource().options.params = {};
            }
            this.searchSource().options.params = this.Y.mix(this.searchSource().options.params, {"manageRecommendationsApiVersion": this.data.js.manageRecommendationsApiVersion}, true);
            RightNow.Event.subscribe('evt_getFiltersRequest', this._fireSortEvent, this);
            RightNow.Event.subscribe('evt_sortTypeResponse', this._onSortTypeResponse, this);
            this.searchSource().on('response', this._onReportChanged, this)
                                .on('send', this._searchInProgress, this)
                                .on('collect', this.sendOkcsRecommendationsLimit, this)
                                .on('collect', this.sendOkcsRecommendationsPageSize, this)
                                .on('collect', this.sendOkcsRecommendationsTruncateSize, this)
                                .on('collect', this.sendOkcsRecommendationsSortDirection, this)
                                .on('collect', this.sendIsRecommendations, this)
                                .on('collect', this.sendOkcsRecommendationsSortColumn, this);
            this._setFilter();
            if (this._isTable)
                this._generateYUITable(this.data.js.headers);
        }
    },
    
    /**
     * Event handler executed to show progress icon during searches
     * @param {string} evt Event name
     * @param {args} args Arguments provided from event fire
     */
    _searchInProgress: function(evt, args) {
        if(!args[1] || !args[1].newPage) {
            document.body.setAttribute('aria-busy', 'true');
        }
    },

    /**
     * Fire specified event with this._sortFilters.
     *
     * @param {String} eventName Defaults to evt_searchFiltersResponse
     */
    _fireSortEvent: function() {
        RightNow.Event.fire('evt_searchFiltersResponse', new RightNow.Event.EventObject(this, {filters: this._sortFilters}));
    },
    
    /**
     * Initialization function to set up search filters for report
     */
    _setFilter: function() {
        this._sortFilters = new RightNow.Event.EventObject(this, {
            filters: {
                searchName: this.data.js.searchName,
                data: {}
            }
        });
        this._setSortData();

        var eo = new RightNow.Event.EventObject(this, {filters: {
            allFilters: this.data.js.filters
        }});
    },

    /**
     * Event handler executed when the sort type is changed
     * @param {String} type Event type
     * @param {Object} args Arguments passed with event
     */
    _onSortTypeResponse: function(type, args) {
        var evt = args[0];
        this._setSortData(evt.filters.data.col_id, evt.filters.data.sort_direction);
    },

    /**
     * initializes sort event object data
     *
     * @param {Integer} columnID
     * @param {Integer} sortDirection
     */
    _setSortData: function(columnID, sortDirection) {
        this._sortFilters.filters.data.col_id  = (columnID || this.data.js.columnID);
        this._sortFilters.filters.data.sort_direction = (sortDirection || this.data.js.sortDirection);
    },
    
    /**
     * Event handler executed to display new results
     *
     * @param {String} type Event type
     * @param {Object} args Arguments passed with event
     */
    _onReportChanged: function(type, args) { 
        var articles = (args[0].data && args[0].data.articles) ? args[0].data.articles : '';
        if (this._isTable) {
            this._onReportChangedTableView(args);
        }
    },
    
    /**
     * Event handler executed to display new results when view_type is table
     * @param {Object} args Arguments passed with event
     */
    _onReportChangedTableView: function(args) { 
        var sortData = RightNow.Event.getDataFromFiltersEventResponse(args, this.data.js.searchName);
        if (sortData)
            this._sortFilters.filters.data = sortData;
        else
            this._setSortData();

        var newdata = args[0].data;
        if(!newdata.headers) 
            newdata.headers = this.data.js.headers;

        var currentPageSize = this.data.attrs.per_page,
            cols = newdata.headers.length,
            data = {
                tableID: this.baseDomID + '_Grid',
                headers: [],
                rows: []
            }, anchor, width, row, td;

        if (newdata.error) {
            RightNow.UI.Dialog.messageDialog(newdata.error, {"icon": "WARN"});
        }
        this.data.js.sortColumn = newdata.columnID;
        this.data.js.sortDirection = newdata.sortDirection;

        // Build up table data and add the new results to the widget's DOM
        for (var i = 0; i < cols; i++) {
            td = {label: newdata.headers[i].label};
            if (width = newdata.headers[i].width) {
                td.style = 'width: "' + width + '%"';
            }
            data.headers.push(td);
        }

        for (var i = 0; i < newdata.recommendations.length; i++) {
            row = [];
            for(var j = 0; j < cols; j++) {
                var field = newdata.headers[j].name,
                    value = newdata.recommendations[i][newdata.headers[j].name],
                    item = {};
                item.name = field;
                item.value = value;
                row.push(item);
            }
            data.rows.push(row);
        }
        
        if (newdata.recommendations.length === 0 && this.data.attrs.hide_when_no_results) {
            RightNow.UI.hide(this.baseSelector);
        }
        
        this._contentDiv.set('innerHTML', new EJS({text: this.getStatic().templates.table}).render({
                data: data.rows,
                headers: this.data.js.headers,
                recommendations: newdata.recommendations,
                tableID: this.baseDomID + '_Grid',
                caption: this.data.attrs.label_caption,
                url: this.data.js.answerUrl,
                target: this.data.attrs.target
            })
        );

        this._generateYUITable(newdata.headers);

        // now allow expand/contract
        this._contentDiv.setStyle('height', 'auto');
        RightNow.Url.transformLinks(this._contentDiv);
        document.body.setAttribute('aria-busy', 'false');

        if(newdata.recommendations.length > 0) {
            this._updateAriaAlert(this.data.attrs.label_screen_reader_search_success_alert);
            // focus on the first result
            anchor = (this._dataTable)
                ? this._dataTable.getRow(0).one('a')
                : this.Y.one(this._gridName).one('a');
            if (anchor) {
                anchor.focus();
            }
        }
        else {
            //don't focus anywhere, stay where you are so you can perhaps try a new search
            this._updateAriaAlert(this.data.attrs.label_screen_reader_search_no_results_alert);
        }
    },
    
    /**
     * Updates the text for the ARIA alert div that appears above result listing
     * @private
     * @param {String} text The text to update the div with
     */
    _updateAriaAlert: function(text) {
        this._ariaAlert = this._ariaAlert || this.Y.one(this.baseSelector + '_Alert');
        if(this._ariaAlert) {
            this._ariaAlert.set('innerHTML', text);
        }
    },
    
    /**
     * Sort event handler executed when the column header is clicked
     * @param {Object} evt Event
     */
    _sortRecommendationsList: function(evt) { 
        var sortTargetNode = evt.target;
        if(evt.target.hasClass('rn_SortIndicator')){
            sortTargetNode = evt.target.get('parentNode');
        }
        this.data.js.sortColumn = this._columns[sortTargetNode._node.cellIndex].value;
        if (this.data.js.sortColumn.toLowerCase() !== 'casenumber') {
            this._setLoading(true);
            var sortColumnNode = '.yui3-datatable-col-c' + sortTargetNode._node.cellIndex + ' .rn_SortIndicator';
            var headerNode = this.Y.one(sortColumnNode);
            if(headerNode.hasClass('rn_RecommendationsSortDesc')) {
                this.data.js.sortDirection = this._directions.asc;
            }
            else if(headerNode.hasClass('rn_RecommendationsSortAsc')){
                this.data.js.sortDirection = this._directions.desc;
            }
            else {
                this.data.js.sortDirection = (this.data.js.sortColumn === 'dateAdded' || this.data.js.sortColumn === 'dateModified')
                    ? this._directions.desc
                    : this._directions.asc;
            }
            this.searchSource().fire('collect');
            this.searchSource().fire('search', new RightNow.Event.EventObject(this, {
                data: {
                    sourceCount: 1
                }
            }));
        }
    },
    
    /**
     * Initializes the data necessary to generate a data table:
     *    this._columns - e.g. [{key: 'Subject', columnID: 1, sortable: true},{key: 'Reference #', columnID: 2, sortable: true}...]
     *    this._data    - e.g. [{'Subject': '<a href="/i_id/148">Droid - Touchscreen</a>', 'Reference #': '120320-000001', 'Status': 'Unresolved'}...]
     *
     * @param {Array} headers An array of header information used to build column data.
     */
    _setTableData: function(headers) {
        var dataTypes = this.data.js.dataTypes,
            sortDirection = this.data.js.sortDirection,
            sortColumnID = this.data.js.sortColumn,
            sortDirectivesPresent = (sortDirection !== null && sortColumnID !== null),
            key, columnID, header, rowObj, rows, i, sortIndicator = classNameHeader = sortScreenReader = "";

        this._columns = [];
        this._data = [];

        for (i = 0; i < headers.length; i++) {
            header = headers[i];
            columnID = header.columnID;
            key = 'c' + columnID;
            sortScreenReader = '<span class="rn_ScreenReaderOnly">' + '&nbsp;' + this.data.attrs.label_sortable + '</span>';
            // Include sorting indicators only if more than one recommendation is present
            if (this.data.js.recommendations.length > 1) {
                if (sortDirectivesPresent && sortColumnID === header.name ) {
                    classNameHeader = "rn_sort";
                    this._sortOn = {key: key, dir: sortDirection};
                    sortIndicator = sortDirection == this._directions.desc ? '<span class="rn_SortIndicator rn_RecommendationsSortDesc"></span>' : '<span class="rn_SortIndicator rn_RecommendationsSortAsc"></span>';
                }
                else {
                    if (header.name.toLowerCase() !== 'casenumber') {
                        classNameHeader = "rn_sort";
                        sortIndicator = '<span class="rn_SortIndicator yui3-datatable-sort-indicator"></span>';
                    }
                    else {
                        classNameHeader = "";
                        sortIndicator = '<span class="rn_SortIndicatorNewStyle"></span>';
                        sortScreenReader = "";
                    }
                }
            }
            this._columns.push({
                key: key,
                label: header.label + sortIndicator + sortScreenReader,
                columnID: columnID,
                emptyCellValue: "&nbsp;",
                allowHTML: true,
                value: header.name,
                className: classNameHeader
            });
        }

        // traverse html table to obtain data
        if (rows = this.Y.all(this._gridName + ' tbody tr')) {
            rows.each(function (row) {
                rowObj = {};
                row.all('td').each(function (td, i) {
                    rowObj[this._columns[i].key] = td.getContent();
                }, this);
                this._data.push(rowObj);
            }, this);
        }
    },
    
    /**
     * Renders a YUI data table built from supplied headers and the html data table specified in this._gridName.
     *
     * @param {Array} headers An array of header information used to build column data.
     */
    _generateYUITable: function(headers) {
        this._setTableData(headers);
        if (this.data.attrs.hide_when_no_results && !this._data.length) return;

        var type = this.data.attrs.type;
        this._dataTable = new this.Y.DataTable({data:this._data,
            columns: this._columns,
            caption: this.data.attrs.label_screen_reader_table_title
        });
        this._dataTable.on('sort', this._onSort, this);
        this._dataTable.after('render', this._afterRenderDataTable, this);
        this._dataTable.after('sort', this._afterRenderDataTable, this);
        if(this.Y.one(this._gridName))
            this.Y.one(this._gridName).remove();
        this._dataTable.render(this._contentName);
        this.Y.one(this.baseSelector + ' caption').addClass('rn_ScreenReaderOnly');
        
        if(!this.data.attrs.show_headers) {
            this.Y.one(this.baseSelector + ' thead').addClass('rn_ScreenReaderOnly');
        }

        this.Y.all(this.baseSelector + ' th').setAttribute('tabindex', '0').setAttribute('scope', 'col');
        this.Y.all('.rn_OkcsManageRecommendations .yui3-datatable-message-content').addClass('rn_Hidden');
        
        if (!this._data.length){
            this.Y.all('.rn_OkcsManageRecommendations .yui3-datatable-message-content').removeClass('rn_Hidden');
            this._dataTable.showMessage(this.data.attrs.label_no_results);
        }
        this._setLoading(false);
    },

    /**
     * The sort function for the DataTable.
     * @param  {Object} sortEvent Sort event
     */
    _onSort: function(sortEvent) {
        if (!sortEvent.sortBy) return;

        this.Y.Object.each(sortEvent.sortBy[0], function(value, columnID) {
            this._setSortData(parseInt(RightNow.Text.getSubstringAfter(columnID, 'c'), 10), this._getDirectionToSort(columnID));
        }, this);
    },
    
    /**
     * Direction defines: what the report model expects
     */
    _directions: { desc: "DESC", asc: "ASC" },

    /**
     * Returns the direction to sort the column based on the direction it's currently
     * been sorted.
     * @param {String} columnID the id of the column (prefixed with 'c')
     * @return {Number} the ascending or descending direction to sort with
     */
    _getDirectionToSort: function(columnID) {
        var yuiPrefix = 'yui3-datatable-',
            header = this.Y.one('#' + this._dataTable.get('id')).one('th.' + yuiPrefix + 'col-' + columnID + '.' + yuiPrefix + 'sortable-column');

        return (header && header.hasClass(yuiPrefix + 'sorted-desc'))
            ? this._directions.asc
            : this._directions.desc;
    },
    
    /**
     * Sets the sorted CSS class names on the column and removes them
     * from the previously sorted column (if any).
     * @param {String} columnID the id of the column (prefixed with 'c')
     * @param {Number} dir the direction to sort (according to `this._directions`)
     */
    _setSortedColumn: function(columnID, dir) {
        var yuiPrefix = 'yui3-datatable-',
            sortedClass = yuiPrefix + 'sorted',
            descClass = yuiPrefix + 'sorted-desc',
            table = this.Y.one('#' + this._dataTable.get('id')),
            header = table.one('th.' + yuiPrefix + 'col-' + columnID + '.' + yuiPrefix + 'sortable-column');

        if (!this._prevSorted && this.data.js.columnID) {
            this._prevSorted = 'c' + this.data.js.columnID;
        }
        if (this._prevSorted) {
            table.all('.' + yuiPrefix + 'col-' + this._prevSorted).removeClass(sortedClass).removeClass(descClass);
        }

        if (dir === this._directions.asc) {
            header.removeClass(descClass);
        }
        else {
            header.addClass(descClass);
        }
        header.addClass(sortedClass);
        table.all('td.' + yuiPrefix + 'col-' + columnID).addClass(sortedClass);
        this._prevSorted = columnID;
    },
    
    /**
     * Function to be called after rendering datatable. Row headers are added to each datatable cell for accessibility compliance.
     */
    _afterRenderDataTable: function() {
        var tableHeaders = this.Y.all(".yui3-datatable-header");
        for (var i = 0; i < tableHeaders.size(); i++) {
            if(this._contentDiv.one(".yui3-datatable-header.yui3-datatable-col-c" + i))
                this.Y.all(".yui3-datatable-cell.yui3-datatable-col-c" + i).setAttribute('headers', this._contentDiv.one(".yui3-datatable-header.yui3-datatable-col-c" + i).getAttribute('id'));
        }
        if (this.data.js.recommendations.length > 1) {
            this.Y.all('.rn_OkcsManageRecommendations th.yui3-datatable-header').on("click", this._sortRecommendationsList, this);
        }
    },
    
    /**
     * Event Handler fired when a Recommendation is selected
     * @param {Object} evt Event object
     */
    _onRecommendationClick: function(evt)
    {
        evt.preventDefault();
        var recordId = evt.target.get('id');
        this._setLoading(true);
        var eventObject = new RightNow.Event.EventObject(this, {data: {
                    recordId: recordId
                }});
        RightNow.Ajax.makeRequest(this.data.attrs.recommendations_view_ajax, eventObject.data, {
            successHandler: this._showRecommendation,
            json: true, scope: this
        });
    },
    
    _showRecommendation: function(response){
        if(!response.failure) {
            var notificationList = this.Y.one(this.baseSelector + '_Body');
            var notificationViewDiv = this.Y.Node.create("<div class='rn_OpenLogin rn_OpenLoginDialog'></div>");
            notificationViewDiv.set("innerHTML", new EJS({text: this.getStatic().templates.recommendationsView}).render({notificationView:response, attrs: this.data.attrs}));
            var dialog = RightNow.UI.Dialog.actionDialog(response.title,
                    notificationViewDiv, {
                        buttons: [
                            {text: RightNow.Interface.getMessage("CLOSE_LBL"), handler: function(){this.hide();}}
                        ],
                        width: '100%',
                        height: '100%'
                    });
            dialog.show();
        }
        this._setLoading(false);
    },
    
    /**
     * Adds the RecommendationsList limit to the filter list
     */
    sendOkcsRecommendationsLimit: function() {
        if(!this.data.attrs.limit){
            return;
        }
        return new RightNow.Event.EventObject(this, {
            data: {value: this.data.attrs.limit, key: 'limit', type: 'limit'}
        });
    },

    /**
     * Adds the RecommendationsList page size to the filter list
     */
    sendOkcsRecommendationsPageSize: function() {
        if(!this.data.attrs.per_page){
            return;
        }
        return new RightNow.Event.EventObject(this, {
            data: {value: this.data.attrs.per_page, key: 'pageSize', type: 'pageSize'}
        });
    },

    /**
     * Adds the RecommendationsList title truncate size to the filter list
     */
    sendOkcsRecommendationsTruncateSize: function() {
        if(!this.data.attrs.truncate_size){
            return;
        }
        return new RightNow.Event.EventObject(this, {
            data: {value: this.data.attrs.truncate_size, key: 'truncate', type: 'truncate'}
        });
    },

    /**
     * Adds the RecommendationsList sort direction to the filter list
     */
    sendOkcsRecommendationsSortDirection: function() {
        if(!this.data.js.sortDirection){
            return;
        }
        return new RightNow.Event.EventObject(this, {
            data: {value: this.data.js.sortDirection, key: 'sortDirection', type: 'sortDirection'}
        });
    },

    /**
     * Adds the RecommendationsList sort column id to the filter list
     */
    sendOkcsRecommendationsSortColumn: function() {
        if(!this.data.js.sortColumn){
            return;
        }
        return new RightNow.Event.EventObject(this, {
            data: {value: this.data.js.sortColumn, key: 'sortColumn', type: 'sortColumn'}
        });
    },
    
    /**
     * Adds the isRecommendations flag to the filter list
     */
    sendIsRecommendations: function() {
        return new RightNow.Event.EventObject(this, {
            data: {value: true, key: 'isRecommendations', type: 'isRecommendations'}
        });
    },
    
    /**
    * changes the loading icon and hides/unhide the data
    * @param {Bool} loading
    */
    _setLoading: function(loading) {
        var toOpacity = 1,
            method = 'removeClass';
        if(loading) {
            //keep height to prevent collapsing behavior
            this._contentDiv.setStyle('height', this._contentDiv.get('offsetHeight') + 'px');
            toOpacity = 0;
            method = 'addClass';
        }
        this._contentDiv.transition({
            opacity: toOpacity,
            duration: 0.4
        });
        this._loadingDiv[method]('rn_Loading');
    }
});
