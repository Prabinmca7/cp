RightNow.Widgets.AnswerList = RightNow.SearchConsumer.extend({
    overrides: {
        constructor: function() {
            this.parent();
            this._contentName = this.baseSelector + '_Content';
            this._ariaNoResultsDiv = this.Y.one(this.baseSelector + '_Alert_NoResults');
            this._ariaResultsDiv = this.Y.one(this.baseSelector + '_Alert_Results');
            this._contentDiv = this.Y.one(this._contentName);
            this._data = this.data.js.articles;
            this._noOfArticles = this.data.js.articles.length;
            this._isTable = this.data.js.viewType === 'table';

            if (this._isTable) {
                this._gridName = this.baseSelector + '_Grid';
                this._dataTable = null;
                this._columns = [];
                this._sortOn = null;
            }
            this.searchSource().setOptions(this.data.js.sources);
            this.searchSource().options.history_source_id = this.data.attrs.source_id;
            if(this.searchSource().options.params === undefined){
                this.searchSource().options.params = {};
            }
            this.searchSource().options.params = this.Y.mix(this.searchSource().options.params, {"answerListApiVersion": this.data.js.answerListApiVersion}, true);

            RightNow.Event.subscribe('evt_getFiltersRequest', this._fireSortEvent, this);
            RightNow.Event.subscribe('evt_sortTypeResponse', this._onSortTypeResponse, this);
            this.searchSource().on('response', this._onResponse, this)
                                .on('send', this._searchInProgress, this)
                                .on('collect', this.sendOkcsArticlesType, this)
                                .on('collect', this.sendOkcsArticlesLimit, this)
                                .on('collect', this.sendOkcsArticlesStatus, this)
                                .on('collect', this.sendOkcsArticlesPageSize, this)
                                .on('collect', this.sendOkcsArticlesCategory, this)
                                .on('collect', this.sendOkcsArticlesContentType, this)
                                .on('collect', this.sendOkcsArticlesTruncateSize, this)
                                .on('collect', this.sendOkcsArticlesSortDirection, this)
                                .on('collect', this.sendOkcsArticlesSortColumn, this);
            this._setFilter();
            if (this._isTable)
                this._generateYUITable(this.data.js.headers);

            if(this.data.attrs.internal_pagination) {
                //Ajax call to fetch all 200 articles
                var eventObject = new RightNow.Event.EventObject(this, {data: {
                   type: this.data.js.filters.type,
                   limit: this.data.js.filters.limit,
                   contentType: this.data.js.filters.contentType,
                   category: this.data.js.filters.category,
                   pageNumber: 1,
                   pageSize: 200,
                   truncate: this.data.js.filters.truncate,
                   sortColumnId: this.data.js.sortColumnId,
                   sortDirection: this.data.js.sortDirection,
                   showDraft: this.data.js.filters.status,
                   answerListApiVersion: this.data.js.filters.answerListApiVersion,
                   fetchForInternalPagination: true
                }});

                RightNow.Ajax.makeRequest(this.data.attrs.get_okcs_data_ajax, eventObject.data, {
                    successHandler: this._onInternalPaginationResponse,
                    json: true,
                    scope: this
                });
            }
        }
    },

    /**
    * Success handler executed when the response for
    * fetch of articles is obtained
    *
    * @param {Object} response Response object
    */
    _onInternalPaginationResponse: function(response) {
        this.data.js.pageArticles.items = response.articles;
        var pageArticles = response.articles,
            articles = Array({'data' : response });
        articles[0].data.articles = articles[0].data.articles.slice(0, this.data.attrs.per_page);
        if(this.data.attrs.view_type === 'list') {
            this._onReportChanged(this.data.attrs.view_type, articles);
        }
        else if(this.data.attrs.view_type === 'table') {
            this._onReportChangedTableView(articles);
        }
        this.endPage = pageArticles.length % this.data.attrs.per_page === 0 ? pageArticles.length / this.data.attrs.per_page : (Math.floor(pageArticles.length / this.data.attrs.per_page) + 1);
        this.currentPage = 1;
        this._showPagination();
    },

    /**
    * Renders internal pagination for list of answers
    */
    _showPagination: function() {
        if (this.endPage > 1) {
            paginationHtml = "<ul>";
            paginationHtml += '<li class="rn_Paginate rn_PreviousPage ' + (this.currentPage > 1 ? '' : 'rn_Hidden') + '" data-pageID="' + (this.currentPage - 1) + '"><span data-rel="previous" tabindex="0" data-pageID="' + (this.currentPage - 1) + '">' + this.data.attrs.label_back + '</span></li>';
            for (var pageNumber = 1; pageNumber <= this.endPage; pageNumber++) {
                var title = this.data.attrs.label_page;
                title = title.replace(title.substr(title.indexOf('%s'), 2), pageNumber);
                title = title.replace(title.substr(title.indexOf('%s'), 2), this.endPage);

                if (pageNumber === this.currentPage) {
                    var title = this.data.attrs.label_current_page;
                    title = title.replace(title.substr(title.indexOf('%s'), 2), pageNumber);
                    title = title.replace(title.substr(title.indexOf('%s'), 2), this.endPage);
                    paginationHtml += '<li class="rn_CurrentPage"><span tabindex="0" title="' + title + '" aria-label="' + title + '">' + pageNumber + '</span></li>';
                }
                else if (this.shouldShowPageNumber(pageNumber, this.currentPage, this.endPage)) {
                    paginationHtml += '<li class="rn_Paginate" data-pageID="' + pageNumber + '"><span id="rn_this_instanceID_PageLink_' + pageNumber + '" tabindex="0" data-rel="' + pageNumber + '" data-pageID="' + (this.currentPage + 1) + '" title="' + title + '" aria-label="' + title + '">' + pageNumber + '</span></li>';
                }
                else if (this.shouldShowHellip(pageNumber, this.currentPage, this.endPage)) {
                    paginationHtml += '<li class="rn_PageHellip"><span class="rn_PageHellip">&hellip;</span></li>';
                }
            }
            paginationHtml += '<li class="rn_Paginate rn_NextPage ' + (this.endPage > this.currentPage ? '' : 'rn_Hidden') + '" data-pageID="' + (this.currentPage + 1) + '"><span data-rel="next" tabindex="0" data-pageID="' + (this.currentPage + 1) + '">' + this.data.attrs.label_forward + '</span></li>';
            paginationHtml += '</ul>';
            this.Y.one("#rn_" + this.instanceID + "_PaginateDiv").setHTML(paginationHtml);
            this.Y.all("#rn_" + this.instanceID + "_PaginateDiv ul li").on('click', this._paginateClick, this);
            this.Y.all("#rn_" + this.instanceID + "_PaginateDiv ul li").on('key', this._paginateClick, 'enter', this);
        }
        else {
            this.Y.one(this.baseSelector + " #rn_" + this.instanceID + "_PaginateDiv").setHTML('');
        }
    },

    /**
    * Page click event handler executed when the page number is clicked
    * @param {Object} evt Event
    */
    _paginateClick: function(evt) {
        evt.preventDefault();
        var paginateLink = evt.currentTarget;
        if(paginateLink.hasClass('rn_Paginate')) {
            var pageId = paginateLink.getAttribute('data-pageID');
            var pageArticles = this.data.js.pageArticles.items;
            var articles = Array({'data' : {'articles' : pageArticles.slice((pageId - 1) * this.data.attrs.per_page, ((pageId - 1) * this.data.attrs.per_page) + this.data.attrs.per_page)}});
            if(this.data.attrs.view_type === 'list') {
                this._onReportChanged(this.data.attrs.view_type, articles);
            }
            else if(this.data.attrs.view_type === 'table') {
                this._onReportChangedTableView(articles);
            }
            this.currentPage = parseInt(pageId, 10);
            this._showPagination();
        }
    },

    /**
    * Returns if the page number is to be displayed
    * @param {Integer} pageNumber Page number to navigate
    * @param {Integer} currentPage Current page selected
    * @param {Integer} endPage Last page
    */
    shouldShowPageNumber: function(pageNumber, currentPage, endPage) {
        return pageNumber === 1 || (pageNumber === endPage) || (Math.abs(pageNumber - currentPage) <= ((currentPage === 1 || currentPage === endPage) ? 2 : 1));
    },

    /**
    * Returns if the html hellip is to be displayed
    * @param {Integer} pageNumber Page number to navigate
    * @param {Integer} currentPage Current page selected
    * @param {Integer} endPage Last page
    */
    shouldShowHellip: function(pageNumber, currentPage, endPage) {
        return Math.abs(pageNumber - currentPage) === ((currentPage === 1 || currentPage === endPage) ? 3 : 2);
    },

    /**
    * Sort event handler executed when the column header is clicked
    * @param {Object} evt Event
    */
    _sortAnswerList: function(evt) {
        evt.preventDefault();
        if(evt.sortBy) {
            var sortTargetNode = this.Y.one(this.baseSelector + ' .yui3-datatable-col-' + Object.keys(evt.sortBy[0])[0]);
        }
        else {
            var sortTargetNode = evt.target;
        }
        if(sortTargetNode.hasClass('rn_SortIndicator')){
            sortTargetNode = sortTargetNode.get('parentNode');
        }
        if(this.isSortable(sortTargetNode)) {
            RightNow.Event.fire("evt_pageLoading");
            this.data.js.sortColumn = this._columns[sortTargetNode._node.cellIndex].value;
            var sortColumnNode = '.yui3-datatable-col-c' + sortTargetNode._node.cellIndex + ' .rn_SortIndicator';
            var headerNode = this.Y.one(sortColumnNode);
            if(headerNode.hasClass('rn_ArticlesSortDesc')) {
                this.data.js.sortDirection = this._directions.asc;
            }
            else if(headerNode.hasClass('rn_ArticlesSortAsc')){
                this.data.js.sortDirection = this._directions.desc;
            }
            else {
                this.data.js.sortDirection = (this.data.js.sortColumn === 'publishDate' || this.data.js.sortColumn === 'createDate' || this.data.js.sortColumn === 'dateModified')
                    ? this._directions.desc
                    : this._directions.asc;
            }
            this.searchSource().fire('collect');
            this.searchSource().fire('search', new RightNow.Event.EventObject(this, {data: {sourceCount: 1}}));
        }
        return false;
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
            key, columnID, header, rowObj, rows, i, sortIndicator = classNameHeader = '';

        this._columns = [];
        this._data = [];

        for (i = 0; i < headers.length; i++) {
            header = headers[i];
            columnID = header.columnID;
            key = 'c' + columnID;
            if(this.data.attrs.type === 'browse' && this._noOfArticles > 1) {
                classNameHeader = "rn_sort";
                if (sortDirectivesPresent && sortColumnID === header.name) {
                    this._sortOn = {key: key, dir: sortDirection};
                    sortIndicator = sortDirection == this._directions.desc ? '<span class="rn_SortIndicator rn_ArticlesSortDesc"></span>' : '<span class="rn_SortIndicator rn_ArticlesSortAsc"></span>';
                }
                else {
                    sortIndicator = '<span class="rn_SortIndicator"></span>';
                }
            }
            this._columns.push({
                key: key,
                label: header.label + sortIndicator,
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

        var type = this.data.attrs.type,
            tableCaption = type === 'recent' ? this.data.attrs.label_recent_list_title : this.data.attrs.label_popular_list_title;
        this._dataTable = new this.Y.DataTable({
            data:    this._data,
            columns: this._columns,
            caption: type === 'browse' ? this.data.attrs.label_table_title : tableCaption
        });
        this._dataTable.after('render', this._afterRenderDataTable, this);
        this._dataTable.after('sort', this._afterRenderDataTable, this);
        if(this.Y.one(this._gridName))
            this.Y.one(this._gridName).remove();
        this._dataTable.render(this._contentName);

        if(!this.data.attrs.show_headers) {
            this.Y.one(this.baseSelector + ' caption').addClass('rn_ScreenReaderOnly');
            this.Y.one(this.baseSelector + ' thead').addClass('rn_ScreenReaderOnly');
        }

        var tableHeaders = this.Y.all(this.baseSelector + ' th');
        tableHeaders.setAttribute('role', 'link');
        for(var count = 0; count < tableHeaders.size(); count++) {
            if(this.isSortable(tableHeaders.item(count))) {
                tableHeaders.item(count).appendChild('<span class="rn_ScreenReaderOnly">' + this.data.attrs.label_sortable + '</span>');
            }
        }
        this.Y.all(this.baseSelector + ' th').setAttribute('tabindex', '0');
        this.Y.one('.yui3-datatable-message').addClass('rn_Hidden');

        if (!this._data.length){
            this.Y.one('.yui3-datatable-message').removeClass('rn_Hidden');
            this._dataTable.showMessage(this.data.attrs.label_no_results);
        }
        RightNow.Event.fire("evt_pageLoaded");
    },

    /**
     * Initilization function to set up search filters for report
     */
    _setFilter: function() {
        this._sortFilters = new RightNow.Event.EventObject(this, {
            filters: {
                searchName: this.data.js.searchName,
                report_id: this.data.attrs.report_id,
                data: {}
            }
        });
        this._setSortData();

        var eo = new RightNow.Event.EventObject(this, {filters: {
            report_id: this.data.attrs.report_id,
            allFilters: this.data.js.filters
        }});
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
     * Event handler executed to show progress icon during searches
    * @param {string} evt Event name
    * @param {args} args Arguments provided from event fire
     */
    _searchInProgress: function(evt, args) {
        var params = args[1];

        if(!params || !params.newPage) {
            document.body.setAttribute('aria-busy', 'true');
        }
    },

    /**
     * Event handler executed to display new results when view_type is table
     * @param {Object} args Arguments passed with event
     */
    _onReportChangedTableView: function(args) {
        var sortData = RightNow.Event.getDataFromFiltersEventResponse(args, this.data.js.searchName, this.data.attrs.report_id);
        if (sortData)
            this._sortFilters.filters.data = sortData;
        else
            this._setSortData();

        this._ariaNoResultsDiv.setStyle('display', 'none');
        this._ariaResultsDiv.setStyle('display', 'none');
        var newdata = args[0].data;
        if(!newdata.headers)
            newdata.headers = this.data.js.headers;

        var currentPageSize = this.data.attrs.per_page,
            cols = newdata.headers.length,
            data = {
                tableID: this.baseDomID + '_Grid',
                headers: [],
                rows: []
            }, anchor, width, row, i, j, td;

        if (newdata.error) {
            RightNow.UI.Dialog.messageDialog(newdata.error, {"icon": "WARN"});
        }
        this.data.js.sortColumn = newdata.columnID;
        this.data.js.sortDirection = newdata.sortDirection;

        // Build up table data and add the new results to the widget's DOM
        for (i = 0; i < cols; i++) {
            td = {label: newdata.headers[i].label};
            if (width = newdata.headers[i].width) {
                td.style = 'width: "' + width + '%"';
            }
            data.headers.push(td);
        }
        this._noOfArticles = newdata.articles.length;
        for (i = 0; i < newdata.articles.length; i++) {
            row = [];
            for(j = 0; j < cols; j++) {
                var field = newdata.headers[j].name,
                    value = newdata.articles[i][newdata.headers[j].name],
                    item = [];
                item.name = field;
                if (field === 'contentType') {
                    item.value = value.referenceKey;
                }
                else if (field === 'locale') {
                    item.value = value.recordID;
                }
                else if (field === 'owner' || field === 'lastModifier' || field === 'creator') {
                    item.value = value.name;
                }
                else {
                    item.value = value;
                }
                row.push(item);
            }
            data.rows.push(row);
        }

        if (newdata.articles.length === 0 && this.data.attrs.hide_when_no_results) {
            RightNow.UI.hide(this.baseSelector);
        }
        else {
            RightNow.UI.show(this.baseSelector);
        }

        this._contentDiv.set('innerHTML', new EJS({text: this.getStatic().templates.table}).render({
                data: data.rows,
                headers: this.data.js.headers,
                articles: newdata.articles,
                tableID: this.baseDomID + '_Grid',
                caption: this.data.attrs.label_caption,
                url: this.data.js.answerUrl,
                session: RightNow.Url.getSession(),
                type: this.data.attrs.type.charAt(0).toUpperCase(),
                target: this.data.attrs.target
            })
        );

        this._generateYUITable(newdata.headers);

        // now allow expand/contract
        this._contentDiv.setStyle('height', 'auto');
        RightNow.Url.transformLinks(this._contentDiv);
        document.body.setAttribute('aria-busy', 'false');

        if(newdata.articles.length > 0) {
            this._updateAriaAlert(false);
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
            this._updateAriaAlert(true);
        }
    },

    _onResponse: function(type, args){
        var pageArticles = this.data.js.pageArticles.items = args[0].data.articles;
        if(this.data.attrs.internal_pagination){
            var articles = JSON.parse(JSON.stringify(args));
            articles[0].data.articles = articles[0].data.articles.slice(0, this.data.attrs.per_page);
            this._onReportChanged(type, articles);
            this.endPage = pageArticles.length % this.data.attrs.per_page === 0 ? pageArticles.length / this.data.attrs.per_page : (Math.floor(pageArticles.length / this.data.attrs.per_page) + 1);
            this.currentPage = 1;
            this._showPagination();
        }
        else{
            this._onReportChanged(type, args);
        }
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
        else {
            if (articles.length > 0) {
                var titleLabel = 'label_' + this.data.attrs.type + '_list_title';
                this._contentDiv.set('innerHTML', new EJS({text: this.getStatic().templates.list}).render({
                        data: articles,
                        show_headers: this.data.attrs.show_headers,
                        header: this.data.attrs[titleLabel],
                        url: this.data.js.answerUrl,
                        session: RightNow.Url.getSession(),
                        type: this.data.attrs.type.charAt(0).toUpperCase(),
                        target: this.data.attrs.target
                    })
                );
            }
            else {
                this._contentDiv.set('innerHTML', this.data.attrs.label_no_results);
            }
        }
    },

    /**
     * Function to be called after rendering datatable. Row headers are added to each datatable cell for accessibility compliance.
     */
    _afterRenderDataTable: function() {
        var tableHeaders = this.Y.all(".yui3-datatable-header");
        for (var i = 0; i < tableHeaders.size(); i++) {
            var sortColumn = this.Y.one(".yui3-datatable-header.yui3-datatable-col-c" + i);
            if(sortColumn) {
                this.Y.all(".yui3-datatable-cell.yui3-datatable-col-c" + i).setAttribute('headers', sortColumn.getAttribute('id'));
            }
        }
        if(this.data.attrs.type === 'browse' && this._noOfArticles > 1) {
            this.Y.all('th.yui3-datatable-header').on("click", this._sortAnswerList, this);
        }
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
     * Fire specified event with this._sortFilters.
     *
     * @param {String} eventName Defaults to evt_searchFiltersResponse
     */
    _fireSortEvent: function() {
        RightNow.Event.fire('evt_searchFiltersResponse', new RightNow.Event.EventObject(this, {filters: this._sortFilters}));
    },

    /**
    * Event handler executed when the sort type is changed
    *
    * @param {String} type Event type
    * @param {Object} args Arguments passed with event
    */
    _onSortTypeResponse: function(type, args) {
        var evt = args[0];
        this._setSortData(evt.filters.data.col_id, evt.filters.data.sort_direction);
    },

    /**
     * Updates the text for the ARIA alert div that appears above result listing
     * @private
     * @param {boolean} isEmpty Flag to determine if results returned were empty or not
     */
    _updateAriaAlert: function(isEmpty) {
        if(isEmpty) {
            this._ariaNoResultsDiv.setStyle('display', 'inline');
        }
        else {
            this._ariaResultsDiv.setStyle('display', 'inline');
        }
    },

    /**
     * Adds the answerlist limit to the filter list
     */
    sendOkcsArticlesLimit: function() {
        if(!this.data.attrs.limit){
            return;
        }
        return new RightNow.Event.EventObject(this, {
            data: {value: this.data.attrs.limit, key: 'limit', type: 'limit'}
        });
    },

    /**
     * Adds the answerlist type to the filter list
     */
    sendOkcsArticlesType: function() {
        return new RightNow.Event.EventObject(this, {
            data: {value: this.data.attrs.type, key: 'type', type: 'type'}
        });
    },

    /**
     * Adds the answerlist status to the filter list
     */
    sendOkcsArticlesStatus: function() {
        var articleStatus = this.data.attrs.show_draft ? 'draft' : 'published';
        return new RightNow.Event.EventObject(this, {
            data: {value: articleStatus, key: 'a_status', type: 'a_status'}
        });
    },

    /**
     * Adds the answerlist page size to the filter list
     */
    sendOkcsArticlesPageSize: function() {
        if(!this.data.attrs.per_page){
            return;
        }
        var pageSize = this.data.attrs.internal_pagination ? 200 : this.data.attrs.per_page;
        return new RightNow.Event.EventObject(this, {
            data: {value: pageSize, key: 'pageSize', type: 'pageSize'}
        });
    },

    /**
     * Adds the answerlist title truncate size to the filter list
     */
    sendOkcsArticlesTruncateSize: function() {
        if(!this.data.attrs.truncate_size){
            return;
        }
        return new RightNow.Event.EventObject(this, {
            data: {value: this.data.attrs.truncate_size, key: 'truncate', type: 'truncate'}
        });
    },

    /**
     * Adds the answerlist category to the filter list
     */
    sendOkcsArticlesCategory: function() {
        if(!this.data.attrs.product_category){
            return;
        }
        return new RightNow.Event.EventObject(this, {
            data: {value: this.data.attrs.product_category, key: 'productCategoryAnsList', type: 'productCategoryAnsList'}
        });
    },

    /**
     * Adds the answerlist content type to the filter list
     */
    sendOkcsArticlesContentType: function() {
        if(!this.data.attrs.content_type){
            return;
        }
        return new RightNow.Event.EventObject(this, {
            data: {value: this.data.attrs.content_type, key: 'contentTypeAnsList', type: 'contentTypeAnsList'}
        });
    },

    /**
     * Adds the answerlist sort direction to the filter list
     */
    sendOkcsArticlesSortDirection: function() {
        if(!this.data.js.sortDirection){
            return;
        }
        return new RightNow.Event.EventObject(this, {
            data: {value: this.data.js.sortDirection, key: 'sortDirection', type: 'sortDirection'}
        });
    },

    /**
     * Adds the answerlist sort column id to the filter list
     */
    sendOkcsArticlesSortColumn: function() {
        if(!this.data.js.sortColumn){
            return;
        }
        return new RightNow.Event.EventObject(this, {
            data: {value: this.data.js.sortColumn, key: 'sortColumn', type: 'sortColumn'}
        });
    },

    /**
     * Function to check whether the column is sortable
     */
    isSortable: function(sortTargetNode) {
        var sortColumn = this._columns[sortTargetNode._node.cellIndex].value;
        if (this.data.js.doNotSortList.indexOf(sortColumn.toLowerCase()) === -1){
            return true;
        }
        return false;
    }
});
