YUI.add('connectexplorer-util', function(Y) {
    'use strict';

    Y.namespace('ConnectExplorer');
    Y.ConnectExplorer.requestUrl = '/ci/admin/explorer/';

    /**
     * Useful utility methods
     */
    Y.ConnectExplorer.Util = {
        _localStoragePrefix: "ConnectExplorer-",
        createPanel: function(options) {
            return new Y.Panel({
                headerContent: options.title,
                zIndex: 9999,
                srcNode: options.srcNode || null,
                bodyContent: options.template
                    ? options.template()
                    : function() {return '<div></div>';},
                width: options.width,
                centered: true,
                visible: false,
                modal: true,
                render: true,
                id: options.id || Y.guid(),
                constraintoviewport: true,
                buttons: [
                    {
                        value: '\u00D7',
                        section: Y.WidgetStdMod.HEADER,
                        action: function(e) {
                            e.preventDefault();
                            this.hide();
                        }},
                    {
                        value: options.buttonLabel || messages.OK,
                        section: Y.WidgetStdMod.FOOTER,
                        action: function(e) {
                            e.preventDefault();
                            if(options.okAction) {
                                options.okAction();
                            }
                            this.hide();
                        },
                        classNames: 'cancelButton'
                    }]
            });
        },
        getLocalStorage: function(key) {
            return localStorage.getItem(this._localStoragePrefix + key);
        },
        setLocalStorage: function(key, value) {
            return localStorage.setItem(this._localStoragePrefix + key, value);
        },
        showError: function(error) {
            if(!this.panel) {
                this.panel = this.createPanel({title: messages.error, width: '480px'});
            }
            this.panel.set('bodyContent', error).show();
        },
        ajax: function(url, options) {
            Y.io(url, {
                method: options.method || 'GET',
                data: options.data || undefined,
                on: {
                    success: function(id, resp) {
                        options.callback.call(options.context,
                            (options.raw) ? resp.responseText : Y.JSON.parse(resp.responseText)
                        );
                    }
                }
            });
        },
        highlightCell: function(e) {
            var td = e.currentTarget,
                link = td.one('a');
            if (link) {
                if (e.type === 'mouseenter') {
                    td.addClass('cellHover');
                    link.addClass('cellHover');
                    td.setAttribute('title', link.getAttribute('title'));
                }
                else {
                    td.removeClass('cellHover');
                    link.removeClass('cellHover');
                }
            }
        }
    };
}, null, {
    requires: ['panel', 'io', 'json-parse']
});
