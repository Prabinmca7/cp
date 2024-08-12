YUI.add('connectexplorer-dialogs', function(Y) {
    'use strict';

    Y.namespace('ConnectExplorer');

    /**
     * Settings dialog to allow users to clear their history and set the default query limit.
     */
    Y.ConnectExplorer.SettingsDialog = function() {
        var maxQueryLimit = 10000,
            defaultLimit = 25,
            settingsButton = Y.one('#settings'),
            userSpecifiedQueryLimit = Y.ConnectExplorer.Util.getLocalStorage('queryLimit') || defaultLimit,
            userSpecifiedTheme = Y.ConnectExplorer.Util.getLocalStorage('theme') || 'default',
            template = new Y.Template().compile(Y.one('#settingsDialogTemplate').getHTML()),
            dialog = Y.ConnectExplorer.Util.createPanel({title: messages.settings, template: template, width: '50%', id:'settingsDialogContainer', okAction: function() {
                //Save query limit
                var selectedQueryLimit = parseInt(queryLimit.get('value'), 10);
                //Fix up limit parameter in case they went under 1 or over the max of 10000 results
                if(selectedQueryLimit > maxQueryLimit){
                    queryLimit.set('value', maxQueryLimit);
                    selectedQueryLimit = maxQueryLimit;
                }
                if(selectedQueryLimit < 1){
                    queryLimit.set('value', 1);
                    selectedQueryLimit = 1;
                }
                if (!selectedQueryLimit) {
                    queryLimit.set('value', defaultLimit);
                    selectedQueryLimit = defaultLimit;
                }
                Y.ConnectExplorer.Util.setLocalStorage('queryLimit', selectedQueryLimit);
                queryLimit.set('value', selectedQueryLimit);
                userSpecifiedQueryLimit = selectedQueryLimit;

                //Save theme and load CSS
                var selectedTheme = editorTheme.get('value');
                Y.ConnectExplorer.Util.setLocalStorage('theme', selectedTheme);
                userSpecifiedTheme = selectedTheme;
                addThemeCss(selectedTheme);
                Y.ConnectExplorer.TabView.setEditorTheme(selectedTheme);
            }}),
            addThemeCss = function(themeName) {
                var cssFileName = themeName;
                if(themeName === 'solarized dark' || themeName === 'solarized light') {
                    cssFileName = 'solarized';
                }
                if(Y.Array.indexOf(loadedThemes, cssFileName) === -1) {
                    var themeCss = Y.Node.create("<link rel='stylesheet' type='text/css'/>");
                    Y.one('head').append(themeCss);
                    // Set href *after* appending to head so IE will recognize the new stylesheet.
                    themeCss.setAttribute('href', 'thirdParty/codemirror/theme/' + cssFileName + '.css');
                    loadedThemes.push(cssFileName);
                }
            },
            queryLimit = Y.one('#defaultQueryLimit'),
            clearHistory = Y.one('#clearQueryHistory'),
            editorTheme = Y.one('#editorTheme'),
            loadedThemes = ['default'];

        if(userSpecifiedTheme && userSpecifiedTheme !== 'default') {
            editorTheme.set('value', userSpecifiedTheme);
            addThemeCss(userSpecifiedTheme);
        }

        clearHistory.on('click', function() {
            Y.ConnectExplorer.QueryHistory.clear();
            this.set('disabled', true).set('innerHTML', messages.noQueryHistory);
        });
        settingsButton.on('click', function() {
            //Enable or disable the clear history button depending on whether we have history
            if(Y.ConnectExplorer.QueryHistory.size()) {
                clearHistory.set('disabled', false).set('innerHTML', messages.clearHistory);
            }
            else{
                clearHistory.set('disabled', true).set('innerHTML', messages.noQueryHistory);
            }
            queryLimit.set('value', userSpecifiedQueryLimit);
            dialog.show();
        });

        return {
            getQueryLimit: function() {
                return userSpecifiedQueryLimit;
            },
            getTheme: function() {
                return userSpecifiedTheme;
            }
        };
    }();

    /**
     * Help dialog
     */
    Y.ConnectExplorer.HelpDialog = function() {
        var helpButton = Y.one('#help'),
            template = new Y.Template().compile(Y.one('#helpDialogTemplate').getHTML()),
            dialog = Y.ConnectExplorer.Util.createPanel({title: messages.help, template: template, width: '75%'});
        helpButton.on('click', function() {
            dialog.show();
        });
    }();

    Y.ConnectExplorer.History = ('pushState' in window.history)
                                ? (new Y.HistoryHTML5({initialState: {field: '', id: ''}}))
                                : null;

    /**
     * Object inspector dialog
     */
    Y.ConnectExplorer.ObjectInspector = function() {
        var metaPanel = null,

            /**
             * Inspect an object or field
             * @param {String} fieldName
             * @param {Number|null} objectID
             */
            inspect = function(fieldName, objectID) {
                objectID = (objectID && objectID !== '0') ? parseInt(objectID, 10) : null;
                var header = (fieldName === 'showObjects')
                        ? messages.primaryObjects
                        : messages.metaHeaderName + ' <span class="metaHeader">' + fieldName + '</span> ' +
                            (objectID ? (messages.metaHeaderID + ' ' + objectID) : '');

                metaPanel = metaPanel || Y.ConnectExplorer.Util.createPanel({
                    srcNode: '#inspect',
                    title: '',
                    buttonLabel: messages.close,
                    width: '85%'
                });
                metaPanel.get('boundingBox').addClass('inspect');
                metaPanel.set('headerContent', header)
                    .set('bodyContent', '<div class="bigwait"></div>')
                    .set('align', {node: Y.one("#explorer"), points: [Y.WidgetPositionAlign.TL, Y.WidgetPositionAlign.TL]})
                    .show();

                metaPanel.on('visibleChange', function(e) {
                    if (!e.newVal) {
                        self.updateHistory();
                    }
                }, this);

                Y.ConnectExplorer.Util.ajax(Y.ConnectExplorer.requestUrl + 'inspect/' + encodeURIComponent(fieldName) + '/' + (objectID || '0') + '/0', {
                    callback: function(resp) {
                        var html, meta;
                        try {
                            html = Y.JSON.parse(resp).html;
                        }
                        catch (err) {
                            html = messages.invalidFieldError + ': ' + fieldName + (objectID ? '/' + objectID : '');
                        }
                        meta = Y.Node.create('<div>' + html + '</div>');
                        meta.delegate('click', self.clickInspect, 'table.objectExplorer td');
                        meta.delegate('click', self.clickInspect, 'a.link'); // Breadcrumb links
                        meta.delegate( ['mouseenter','mouseleave'], Y.ConnectExplorer.Util.highlightCell, 'table.objectExplorer td', this);
                        metaPanel.set('bodyContent', meta);
                        self.updateHistory(fieldName, objectID);
                    },
                    raw: true,
                    context: this
                });
            },

            self = {
                /**
                 * Initializes the object
                 */
                initialize: function() {
                    // Launch meta details panel if url specifies inspect/{fieldName}/[{id}]
                    if (fieldData.fieldName) {
                        inspect(fieldData.fieldName, fieldData.objectID);
                    }

                    Y.one('#objects').on('click', function() {
                        inspect('showObjects');
                    }, this);

                    Y.on('history:change', function (e) {
                        if (e.src === Y.HistoryHTML5.SRC_POPSTATE && e.changed) {
                            var data = e.newVal,
                            field = (data && data.field) ? data.field : '',
                            id = (data && data.id) ? data.id : '';
                            this.updateHistory(field, id, 'replace');
                            if (field) {
                                inspect(field, id);
                            }
                        }
                    }, this);
                },

                /**
                 * Adds or replaces a browser history entry and updates the url.
                 * @param {String} fieldName
                 * @param {Integer|null} objectID
                 * @param {String|null} method One of 'replace' or 'add'. Defaults to 'add'.
                 */
                updateHistory: function(fieldName, objectID, method) {
                    if (Y.ConnectExplorer.History) {
                        fieldName = fieldName || '';
                        objectID = (objectID && !isNaN(objectID)) ? objectID : '';
                        method = method === 'replace' ? method : 'add';
                        if (method === 'replace' || fieldName !== Y.ConnectExplorer.History.get('field') || objectID !== Y.ConnectExplorer.History.get('id')) {
                            Y.ConnectExplorer.History[method]({field: fieldName, id: objectID}, {
                                url: Y.ConnectExplorer.requestUrl + (fieldName ? 'inspect/' + encodeURIComponent(fieldName) + '/' + objectID : '')
                            });
                        }
                    }
                },

                /**
                 * Click handler to update object inspector to currrent selection
                 * @param {Object} e Event object
                 */
                clickInspect: function(e) {
                    var target = e.currentTarget,
                        link = (target.hasClass('link')) ? target : target.one('a'), field;
                    if (link && (field = link.getAttribute('data-field'))) {
                        inspect(field, link.getAttribute('data-id'));
                    }
                }
            };

        return self;
    }();
}, null, {requires: ['node', 'connectexplorer-util', 'connectexplorer-query-history', 'template', 'history']});