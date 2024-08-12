/* global messages,CodeMirror */
YUI.add('connectexplorer-editor', function(Y) {
    'use strict';

    Y.namespace('ConnectExplorer');

    /**
     * CodeMirror editor and results display
     */
    Y.ConnectExplorer.Editor = function(instanceID, tabView) {
        this.instanceID = instanceID;
        this.tabView = tabView;
        this.cm = new CodeMirror(document.getElementById("editor" + this.instanceID), {
            mode: "text/x-roql",
            smartIndent: true,
            lineNumbers: true,
            matchBrackets: true,
            autofocus: true,
            theme: Y.ConnectExplorer.SettingsDialog.getTheme(),
            extraKeys: {
                "Ctrl-Enter": Y.bind(this.submitQuery, this),
                "Esc": function(cm) {
                    cm.getInputField().blur();
                }
            }
        });
        this.dataTable = new Y.DataTable({
            sortable: true,
            height: '450px',
            width: '100%',
            showMessages: false,
            summary: messages.summary
        });
        Y.one('#editorLabel' + this.instanceID).setAttribute('for', this.cm.getInputField().id);
        this.dataSource = new Y.DataSource.Get({source: Y.ConnectExplorer.requestUrl + 'query/'});
        this.dataTable.plug(Y.Plugin.DataTableDataSource, {datasource: this.dataSource});
        this.dataTable.get('boundingBox').addClass('resultsTable');
        this.dataTable.render('#results' + this.instanceID);
        this.dataTable.delegate( ['mouseenter','mouseleave'], Y.ConnectExplorer.Util.highlightCell, '.yui3-datatable-data td', this.dataTable);
        this.dataTable.delegate('click', Y.ConnectExplorer.ObjectInspector.clickInspect, '.yui3-datatable-data td');

        this.nextPageButton = Y.one('#nextPage' + this.instanceID);
        this.prevPageButton = Y.one('#previousPage' + this.instanceID);
        this.resultDetails = Y.one('#caption' + this.instanceID);

        this.nextPageButton.on('click', this.onPageClick, this, 1);
        this.prevPageButton.on('click', this.onPageClick, this, -1);
    };

    Y.ConnectExplorer.Editor.prototype = {
        setValue: function(value) {
            this.cm.setValue(value);
            this.focus();
            return this;
        },
        setTheme: function(theme) {
            this.cm.setOption("theme", theme);
            this.refresh();
            return this;
        },
        refresh: function() {
            this.cm.refresh();
            return this;
        },
        focus: function(){
            this.cm.focus();
            return this;
        },
        getValue: function() {
            return encodeURIComponent(Y.Lang.trim(this.cm.getValue(" ")));
        },
        submitQuery: function() {
            this.makeRequest();
            return this;
        },
        exportResults: function() {
            if (this.dataTable.get('data').size() <= 0) {
                Y.ConnectExplorer.Util.showError(messages.noResultsToExport);
                return;
            }
            window.location.href = Y.ConnectExplorer.requestUrl + "export/query/" + this.getValue();
            return this;
        },
        onPageClick: function(event, pageNumber) {
            this.makeRequest(pageNumber);
            return this;
        },
        setLoading: function(loading) {
            var results = Y.one('#results' + this.instanceID),
                loadingDiv = Y.one('#resultsLoading' + this.instanceID),
                toOpacity = loading ? 0 : 1,
                method = loading ? 'addClass' : 'removeClass';

            results.transition({
                opacity: toOpacity,
                duration: 0.4
            });
            loadingDiv[method]('rn_Loading');
            return this;
        },
        showWarning: function(type){
            Y.one('#query' + this.instanceID + (type === 'describe' ? ' .descWarning' : ' .showWarning')).removeClass('hide');
        },
        clearWarnings: function(){
            Y.all('#query' + this.instanceID + ' .descWarning, #query' + this.instanceID + ' .showWarning').addClass('hide');
        },
        formatter: function(o) {
            var title = o.data._TITLE_ || "";
            if (title !== "") {
                title = ' title="' + title + o.data._ID_ + '"';
            }
            return '<a href="javascript:void(0);" class="link" data-field="' + o.data._LINK_ + '" data-id="' + o.data._ID_ + '"' + title + '>' + o.value + '</a>';
        },
        formatColumns: function(columns) {
            //Allow the first row to be a clickable link
            var column = columns[0],
                columnName = column.key;
            column.formatter = this.formatter;
            column.allowHTML = true;
            column.sortFn = function(a, b, desc) {
                var numeric, order;
                a = a.get(columnName);
                b = b.get(columnName);
                a = isNaN(numeric = parseFloat(a)) ? a : numeric;
                b = isNaN(numeric = parseFloat(b)) ? b : numeric;
                order = a > b ? 1 : a < b ? -1 : 0;
                return desc ? -order : order;
            };
            return columns;
        },
        makeRequest: function(page) {
            if(!page) {
                page = 0;
            }
            var query = this.getValue();
            if (!query) {
                Y.ConnectExplorer.Util.showError(messages.noQuery);
                return;
            }

            this.setLoading(true);
            this.dataTable.datasource.load({
                request: "?query=" + query + '&limit=' + Y.ConnectExplorer.SettingsDialog.getQueryLimit() + '&page=' + page,
                callback: {
                    success: Y.bind(this.handleResults, this),
                    failure: Y.bind(this.handleFailure, this),
                    argument: page
                }
            });
            return this;
        },
        handleResults: function(results) {
            this.setLoading(false);

            // set the results here
            var response = results.data;
            if (response.error) {
                Y.ConnectExplorer.Util.showError(response.error);
                return;
            }

            if (response.results && response.results.length > 0) {
                // DataTable doesn't play nicely with column that contain periods, so replace them with underscores
                Y.Array.each(response.columns, function(column) {
                    var originalKey = column.key,
                        goodKey = originalKey.replace('.', '_');
                    if (originalKey !== goodKey) {
                        column.key = goodKey;
                        Y.Array.each(response.results, function(result) {
                            result[goodKey] = result[originalKey];
                            delete result[originalKey];
                        });
                    }
                });
                this.dataTable.set('columns', this.formatColumns(response.columns));
                this.resultDetails.set('innerHTML', response.total + ' results returned in ' + response.duration + ' seconds');
                this.dataTable.set('data', response.results);
                // Update the query to reflect any potential changes/corrections
                this.cm.setValue(response.query); // TODO: add a transition or some indicator to make it obvious the query was updated..
                this.focus();

                // add the item to local history and the history select box but not if it was just a pagination request
                if(!results.callback.argument) {
                    Y.ConnectExplorer.QueryHistory.addItem(response.query);
                }
                // enable pagination buttons if applicable
                if (response.query.match(/^SELECT/i)) {
                    this.prevPageButton.removeClass('hidden').set('disabled', response.offset <= 0);
                    this.nextPageButton.removeClass('hidden').set('disabled', response.total < response.limit);
                }
                else {
                    this.prevPageButton.addClass('hidden');
                    this.nextPageButton.addClass('hidden');
                }
                this.clearWarnings();
                //Show warning div if query was a show/describe
                if(response.queryType === 'describe' || response.queryType === 'show'){
                    this.showWarning(response.queryType);
                }
            }
            else {
                this.clearResults();
                Y.ConnectExplorer.Util.showError(messages.noResults);
            }

            if (this.tabView) {
                this.tabView.setSelectedTabLabel(response.objectName);
            }
        },
        clearResults: function() {
            this.resultDetails.empty();
            this.dataTable.data.reset();
            this.clearWarnings();
            try{
                this.dataTable.set('columns', []);
            }
            catch(e){
                //IE doesn't like this somewhere, but still clears our the columns correctly
            }
            this.prevPageButton.addClass('hidden');
            this.nextPageButton.addClass('hidden');
            return this;
        },
        handleFailure: function() {
            this.setLoading(false);
            Y.ConnectExplorer.Util.showError(messages.genericError);
        }
    };
}, null, {
    requires: ['connectexplorer-util', 'connectexplorer-query-history', 'connectexplorer-dialogs', 'widget-position-align', 'json-parse', 'node', 'datatable', 'datasource', 'plugin', 'transition', 'event-mouseenter']
});
