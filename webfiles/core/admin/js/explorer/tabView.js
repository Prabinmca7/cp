YUI.add('connectexplorer-tabview', function(Y) {
    'use strict';

    Y.namespace('ConnectExplorer');

    /**
     * Tabview module
     */
    Y.ConnectExplorer.TabView = function() {
        var AddableTab = function(config) {
                this._host = config.host;
                AddableTab.superclass.constructor.apply(this, arguments);
            },
            RemovableTab = function(config) {
                RemovableTab.superclass.constructor.apply(this, arguments);
            },
            getConfiguration = function() {
                var configuration = {
                    id: editorCount,
                    label: messages.newQuery,
                    content: template({index: editorCount})
                };
                editorCount++;
                return configuration;
            },
            getSelectedEditor = function() {
                return editorInstances[tabView.get('selection').get('id')];
            },
            addEditor = function(index) {
                var editor = editorInstances[index] = new Y.ConnectExplorer.Editor(index, Y.ConnectExplorer.TabView);
                if (Y.ConnectExplorer.QueryHistory.size()) {
                    // Set most recent query from history
                    var historyItems = Y.ConnectExplorer.QueryHistory.getItems();
                    editor.setValue(historyItems[0]);
                }
                return editor;
            },
            template = new Y.Template().compile(Y.one('#editorView').getHTML()),
            editorInstances = [],
            editorCount = 0,
            tabView;

        //Define the Addable plugin
        AddableTab.NAME = 'addableTabs';
        AddableTab.NS = 'addable';

        Y.extend(AddableTab, Y.Plugin.Base, {
            ADD_TMPL: '<li class="yui3-tab" title="' + messages.add + '"><a class="yui3-tab-label yui3-tab-add" tabindex="0"><i class="fa fa-plus"></i></a></li>',

            initializer: function(config) {
                var tabView = this.get('host');
                tabView.after('render', this.afterRender, this);
                tabView.get('contentBox').delegate('click', this.onAddClick, '.yui3-tab-add', this);
            },

            onAddClick: function(e) {
                e.stopPropagation();
                var tabView = this.get('host'),
                    configuration = getConfiguration(),
                    editor;

                tabView.add(configuration);
                editor = addEditor(configuration.id);
                tabView.selectChild(tabView.size() - 1);
                editor.refresh();
                editor.focus();
            },

            afterRender: function(e) {
                this.get('host').get('contentBox').one('> ul').append(this.ADD_TMPL);
            }
        });

        // define the RemovableTab plugin
        RemovableTab.NAME = 'removableTabs';
        RemovableTab.NS = 'removable';

        Y.extend(RemovableTab, Y.Plugin.Base, {
            REMOVE_TMPL: '<a class="yui3-tab-remove"><i class="fa fa-times"></i></a>',

            initializer: function(config) {
                var tabView = this.get('host'),
                    contentBox = tabView.get('contentBox');

                contentBox.addClass('yui3-tabview-removable');
                contentBox.delegate('click', this.onRemoveClick, '.yui3-tab-remove', this);

                // tab events bubble to TabView
                tabView.after('tab:render', this.afterRender, this);
            },

            afterRender: function(e) {
                if (this.get('host')._items.length > 1) {
                    e.target.get('boundingBox').append(this.REMOVE_TMPL);
                }
            },

            onRemoveClick: function(e) {
                e.stopPropagation();
                Y.Widget.getByNode(e.target).remove();
            }
        });

        return {
            initialize: function() {
                tabView = new Y.TabView({
                    children: [getConfiguration()],
                    plugins: [AddableTab, RemovableTab]
                });
                tabView.render('#queryTabs');
                addEditor(0);
            },
            setQuery: function(query) {
                getSelectedEditor().setValue(query);
            },
            submitQuery: function() {
                getSelectedEditor().submitQuery();
            },
            clear: function() {
                getSelectedEditor().setValue('').clearResults();
                this.setSelectedTabLabel(messages.newQuery);
            },
            exportResults: function() {
                getSelectedEditor().exportResults();
            },
            setSelectedTabLabel: function(label) {
                tabView.get('selection').set('label', label);
            },
            setEditorTheme: function(theme) {
                Y.Array.each(editorInstances, function(instance) {
                    instance.setTheme(theme);
                }, this);
            }
        };
    }();
}, null, {
    requires: ['connectexplorer-editor', 'connectexplorer-query-history', 'node', 'plugin', 'overlay', 'tabview', 'template']
});
