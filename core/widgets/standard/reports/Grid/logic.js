RightNow.Widgets.Grid = RightNow.ResultsDisplay.extend({
    overrides: {
        constructor: function() {
            this.parent();
            this._modActionPerformed = false;
            this._contentName = this.baseSelector + '_Content';
            this._contentDiv = this.Y.one(this._contentName);
            this._gridName = this.baseSelector + '_Grid';
            this._loadingDiv = this.Y.one(this.baseSelector + '_Loading');
            this._dataTable = null;
            this._columns = [];
            this._data = [];
            this._sortOn = null;

            RightNow.Event.subscribe('evt_getFiltersRequest', this._fireSortEvent, this);
            RightNow.Event.subscribe('evt_sortTypeResponse', this._onSortTypeResponse, this);
            this.searchSource().on('response', this._onReportChanged, this)
                               .on('send', this._searchInProgress, this);

            this._setFilter();

            if (this.data.attrs.headers) {
                this._generateYUITable(this.data.js.headers);
                this._setSortableLabel(this.data.js.headers);
            }
            else {
                this._contentDiv.addClass('rn_NoHeader');
            }

            if (RightNow.Event.isHistoryManagerFragment()) {
                this._setLoading(true);
            }
            else {
                this._updateAriaAlert((this._dataTable && this._data.length > 0)
                    ? this.data.attrs.label_screen_reader_search_success_alert
                    : this.data.attrs.label_screen_reader_search_no_results_alert);
            }
        }
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
            token: this.data.js.token,
            allFilters: this.data.js.filters,
            format: this.data.js.format
        }});
        eo.filters.format.parmList = this.data.attrs.add_params_to_url;
        this.searchSource().fire('setInitialFilters', eo);
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

        if(!params || !params.newPage)
        {
            document.body.setAttribute('aria-busy', 'true');
            this._setLoading(true);
        }
    },

    /**
    * changes the loading icon and hides/unhide the data
    * @param {Bool} loading
    */
    _setLoading: function(loading) {
        var toOpacity = 1,
            method = 'removeClass';
        if (loading) {
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
    },

    /**
     * Event handler executed to display new results
     *
     * @param {String} type Event type
     * @param {Object} args Arguments passed with event
     */
    _onReportChanged: function(type, args) {
        var sortData = RightNow.Event.getDataFromFiltersEventResponse(args, this.data.js.searchName, this.data.attrs.report_id);

        if (sortData)
            this._sortFilters.filters.data = sortData;
        else
            this._setSortData();

        var newdata = args[0].data;
        this._setLoading(false);
        var currentPageSize = newdata.per_page,
            cols = newdata.headers.length,
            data = {
                instanceID: this.instanceID,
                tableID: this.baseDomID + '_Grid',
                caption: this.data.attrs.label_caption,
                attrs: this.data.attrs,
                headers: [],
                rows: [],
                js: this.data.js,
                hiddenHeaders: [],
                hiddenRows: []
            }, anchor, width, row, hiddenRow, i, j, td, currentDataLength;

        if (newdata.error) {
            RightNow.UI.Dialog.messageDialog(newdata.error, {"icon": "WARN"});
        }

        // Build up table data and add the new results to the widget's DOM
        if (this.data.attrs.headers) {
            if (newdata.row_num) {
                data.headers.push({label: this.data.attrs.label_row_number});
            }
            for (i = 0; i < cols; i++) {
                td = {label: newdata.headers[i].heading};

                if (width = newdata.headers[i].width) {
                    td.style = 'width: "' + width + '%"';
                }

                if (newdata.headers[i].visible) {
                    data.headers.push(td);
                }
                else {
                    data.hiddenHeaders[i] = td;
                }
            }
        }
        if (newdata.total_num > 0) {
            // in case the data returned does not actually match the current page size
            // do not exceed the length of data returned
            currentDataLength = newdata.data.length;
            for (i = 0; i < currentPageSize && i < currentDataLength; i++) {
                row = [];
                hiddenRow = [];
                if (newdata.row_num) {
                    row.push(i + 1);
                }
                for(j = 0; j < cols; j++) {
                    if(!newdata.headers[j].visible) {
                        hiddenRow[j] = newdata.data[i][j];
                    }
                    else {
                        row.push(newdata.data[i][j]);
                    }
                }
                data.rows.push(row);
                data.hiddenRows.push(hiddenRow);
            }

            if (this.data.attrs.hide_when_no_results)
                RightNow.UI.show(this.baseSelector);
        }
        else if (this.data.attrs.hide_when_no_results) {
            RightNow.UI.hide(this.baseSelector);
        }

        this._contentDiv.set('innerHTML', new EJS({text: this.getStatic().templates.dataTable}).render(data));

        if (this.data.attrs.headers) {
            this._generateYUITable(newdata.headers);
            this._setSortableLabel(newdata.headers);
            if (!this._modActionPerformed) {
                this._setFocusAfterSort(this._sortFilters.filters.data.col_id);
            }
        }

        // now allow expand/contract
        this._contentDiv.setStyle('height', 'auto');
        RightNow.Url.transformLinks(this._contentDiv);
        document.body.setAttribute('aria-busy', 'false');

        if (!this._modActionPerformed) {
            this._updateAriaAlert((newdata.total_num > 0) ? this.data.attrs.label_screen_reader_search_success_alert :
                this.data.attrs.label_screen_reader_search_no_results_alert);
        }
        else {
            this._modActionPerformed = false;
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
            sortDirection = this._sortFilters.filters.data.sort_direction,
            sortColumnID = this._sortFilters.filters.data.col_id,
            sortDirectivesPresent = (sortDirection !== null && sortColumnID !== null),
            rowNumbersPresent = this.data.js.rowNumber,
            key, columnID, header, rowObj, rows, i, tableHeaderID,
            iconColumns = this.data.attrs.icon_cols.split(",") || [];

        this._columns = [];
        this._data = [];
        if (rowNumbersPresent) {
            this._columns.push({key: 'c0', label: this.data.attrs.label_row_number, sortable: false});
        }

        for (i = 0; i < headers.length; i++) {
            header = headers[i];
            if (!header.visible)
                continue;
            columnID = header.col_id;
            key = 'c' + columnID;
            tableHeaderID = this.instanceID + '_' + key;
            if (sortDirectivesPresent && sortColumnID === columnID) {
                this._sortOn = {key: key, dir: sortDirection};
            }
            var isSortable = !this.Y.Lang.isArray(this.data.attrs.exclude_from_sorting) || this.Y.Array.indexOf(this.data.attrs.exclude_from_sorting, (i + 1).toString()) === -1;

            this._columns.push({
            id: tableHeaderID,
            key: key,
            label: (iconColumns.indexOf(columnID.toString()) !== -1) ? "<span class='rn_ScreenReaderOnly'>" + header.heading + "</span>" : header.heading,
            columnID: columnID,
            sortable: isSortable,
            emptyCellValue: "&nbsp;",
            allowHTML: true,
            className: "rn_GridColumn_" + columnID,
            title: isSortable ? header.heading : "",
            cellTemplate: '<td class="{className}" headers="' + tableHeaderID + '">{content}</td>'
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

        this._dataTable = new this.Y.DataTable({
            data:    this._data,
            columns: this._columns,
            caption:  this.data.attrs.label_caption,
            strings: {
                asc:            RightNow.Interface.getMessage('ASCENDING_LBL'),
                desc:           RightNow.Interface.getMessage('DESCENDING_LBL'),
                sortBy:         RightNow.Text.sprintf(RightNow.Interface.getMessage('SORT_BY_S_LBL'), '{title}'),
                reverseSortBy:  RightNow.Interface.getMessage('REVERSE_SORT_BY_COLUMN_LBL')
            }
        });
        this._dataTable.on('sort', this._onSort, this);

        this.Y.one(this._gridName).remove();
        this._dataTable.render(this._contentName);

        if (!this._data.length) {
            this._dataTable.showMessage(RightNow.Interface.getMessage('NO_RECORDS_FOUND_MSG'));
            this._dataTable.setAttrs({'sortable': false});
            this._sortOn = null;
        }
        else {
            this.Y.one(this._contentName + ' .yui3-datatable-message-content').remove();
        }

        if (this._sortOn) {
            this._setSortedColumn(this._sortOn.key, this._sortOn.dir);
        }

        // adds an aria-labelledby tag to the liner div to appease the oghag toolbar
        this.Y.all(this._contentName + " th .yui3-datatable-sort-liner").each(function(node) {
            node.setAttribute('aria-labelledby', node.ancestor('th').get('id'));
        }, this);
    },

    /**
     * Adds a screen reader label to the sortable columns, indicating they are sortable
     * @param {Array} headers An array of header information to add the label to.
     */
    _setSortableLabel: function(headers) {
        var yuiHeaderPrefix = 'yui3-datatable-header.rn_GridColumn_',
            table = this.Y.one('#' + this._dataTable.get('id')),
            columnID = 0;

        for (var i = 0; i < headers.length; i++) {
            if (headers[i].visible) {
                columnID = i + 1;
                header = table.one('th.' + yuiHeaderPrefix + columnID);
                if(header !== null) {
                    headerLiner = header.one('.yui3-datatable-sort-liner');
                    if (headerLiner !== null) {
                        headerLiner.append('<span class="rn_ScreenReaderOnly">' + '&nbsp;' + this.data.attrs.label_sortable + '</span>');
                    }
                }
            }
        }
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

        RightNow.Event.fire("evt_sortChange", this._sortFilters);
        this.searchSource().fire('search', this._sortFilters);
        //Stop the datatable from sorting itself
        sortEvent.halt();
    },

    /**
     * Sets focus on the sorted column header liner
     * @param {String} columnID The id of the column
     */
    _setFocusAfterSort: function(columnID) {
        var yuiHeaderPrefix = 'yui3-datatable-header.rn_GridColumn_',
            header = this.Y.one('#' + this._dataTable.get('id')).one('th.' + yuiHeaderPrefix + columnID);
        if (header) {
            headerLiner = header.one('.yui3-datatable-sort-liner');
            if (headerLiner !== null) {
                headerLiner.focus();
            }
        }
    },

    /**
     * Direction defines: what the report model expects
     */
    _directions: { desc: 2, asc: 1 },

    /**
     * Returns the direction to sort the column based on the direction it's currently
     * been sorted.
     * @param {String} columnID the id of the column (prefixed with 'c')
     * @return {Number} the ascending or descending direction to sort with
     */
    _getDirectionToSort: function(columnID) {
        var yuiPrefix = 'yui3-datatable-',
            header = this.Y.one('#' + this._dataTable.get('id')).one('th.' + yuiPrefix + 'col-' + columnID + '.' + yuiPrefix + 'sortable-column');

        return (header && header.hasClass(yuiPrefix + 'sorted-desc')) ? this._directions.asc : this._directions.desc;
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

        if (header) {
            var headerLiner = header.one('.yui3-datatable-sort-liner'),
                sortLabel = (dir === this._directions.asc) ?
                    RightNow.Interface.getMessage('SORTED_ASCENDING_LBL') :
                    RightNow.Interface.getMessage('SORTED_DESCENDING_LBL');
            headerLiner.append('<span class="rn_ScreenReaderOnly">' + sortLabel + '</span>');
        }

        if (!this._prevSorted && this.data.js.columnID) {
            this._prevSorted = 'c' + this.data.js.columnID;
        }
        if (this._prevSorted) {
            table.all('.' + yuiPrefix + 'col-' + this._prevSorted).removeClass(sortedClass).removeClass(descClass);
        }
        if (header) {
            if (dir === this._directions.asc) {
                header.removeClass(descClass);
            }
            else {
                header.addClass(descClass);
            }
        header.addClass(sortedClass);
        }
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
     * @param {String} text The text to update the div with
     */
    _updateAriaAlert: function(text) {
        this._ariaAlert = this._ariaAlert || this.Y.one(this.baseSelector + '_Alert');
        if(this._ariaAlert) {
            this._ariaAlert.set('innerHTML', text);
        }
    }
});
