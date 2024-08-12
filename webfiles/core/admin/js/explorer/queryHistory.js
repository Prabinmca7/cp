YUI.add('connectexplorer-query-history', function(Y) {
    'use strict';

    Y.namespace('ConnectExplorer');

    /**
     * Methods to interact with the query history dropdown
     */
    Y.ConnectExplorer.QueryHistory = function() {
        var buttonSelector = Y.one('.queryHistory button'),
            listWrapperSelector = Y.one('.queryHistory div'),
            listSelector = Y.one('.queryHistory ul'),
            items = Y.ConnectExplorer.Util.getLocalStorage('historyData') || [],
            itemTemplate = new Y.Template().compile('<li><span tabindex="0" title="<%=this.title%>"><%=this.value%></span> <a href="javascript:void(0);" tabindex="0"><i class="fa fa-times-circle"></i></a></li>'),
            historyLimit = 35,
            save = function() {
                Y.ConnectExplorer.Util.setLocalStorage('historyData', Y.JSON.stringify(items));
            },
            refresh = function() {
                listSelector.empty();
                Y.Array.each(items, function(item) {
                    var data = {title: item, value: item};

                    // truncate the items for the span, if they are more than 81 characters long
                    if (item.length > 81) {
                        var position = item.toUpperCase().lastIndexOf("LIMIT"),
                            limitSection = "";

                        if (position >= 0) {
                            limitSection = " " + item.substring(position);
                        }

                        // 82 is the max number of characters allowed, add 5 on to the length for " ... "
                        data.value = item.substring(0, (82 - (limitSection.length + 5))) + " ...";

                        if (limitSection != "") {
                            data.value += limitSection;
                        }
                    }
                    listSelector.appendChild(itemTemplate(data));
                });
            },
            selectItem = function(title) {
                Y.ConnectExplorer.TabView.setQuery(title);
                Y.ConnectExplorer.QueryHistory.hide();
            };

        if(Y.Lang.isString(items)) {
            try{
                items = Y.JSON.parse(items);
            }
            catch(e) {
                items = [];
            }
        }

        // Build up the initial listing of history items
        refresh();

        // handler for when a query is clicked on
        listWrapperSelector.delegate('click', function(e) {
            e.stopPropagation();
            selectItem(e.currentTarget.get('title'));
        }, 'span');

        // handler for when a query is accessed by keyboard
        listWrapperSelector.delegate('keyup', function(e) {
            if (e.keyCode === 13) {
                e.stopPropagation();
                selectItem(e.currentTarget.get('title'));
            }
        }, 'span');

        // handler for when an individual query is removed
        listWrapperSelector.delegate('click', function(e) {
            e.stopPropagation();

            Y.ConnectExplorer.QueryHistory.removeItem(e.currentTarget.previous('span').get('title'));
            e.currentTarget.ancestor('li').transition({
                duration: 0.4,
                easing: 'ease-out',
                opacity: 0
            }, function() {
                this.remove();
            });
        }, 'a');

        buttonSelector.on('click', function(e) {
            e.stopPropagation();
            if (listWrapperSelector.getStyle('display') !== 'block') {
                Y.ConnectExplorer.QueryHistory.show();
            }
            else {
                Y.ConnectExplorer.QueryHistory.hide();
            }
        });

        Y.one(document).on('click', function() {
            Y.ConnectExplorer.QueryHistory.hide();
        });

        return {
            getItems: function() {
                return items;
            },
            size: function() {
                return items.length;
            },
            clear: function() {
                items = [];
                save();
                listSelector.empty();
            },
            addItem: function(value) {
                var existingIndex = Y.Array.indexOf(items, value);
                if(existingIndex >= 0) {
                    items.splice(existingIndex, 1);
                }
                items.unshift(value);

                // limit the history to the last 35 items
                if (items.length > historyLimit)
                    items.splice(historyLimit, items.length);
                save();

                //Build up the select box again
                refresh();
            },
            removeItem: function(value) {
                var existingIndex = Y.Array.indexOf(items, value);
                if (existingIndex >= 0) {
                    items.splice(existingIndex, 1);
                }
                save();
            },
            hide: function() {
                if (listWrapperSelector.getStyle('display') !== 'none') {
                    listWrapperSelector.transition({
                        duration: 0.4,
                        easing: 'ease-out',
                        opacity: 0
                    }, function() {
                        this.setStyle('display', 'none');
                    });
                }
            },
            show: function() {
                listWrapperSelector.setStyle('display', 'block').transition({
                    duration: 0.4,
                    easing: 'ease-in',
                    opacity: 1
                });
            }
        };
    }();
}, null, {
    requires: ['node', 'json', 'connectexplorer-util', 'transition', 'template']
});
