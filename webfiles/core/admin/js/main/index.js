/*global hideShowPanel*/
YUI().use('node-base', 'panel', 'FormToken', function(Y) {
    var trigger = Y.one('.versionInfoToggle');
    trigger.on('click', function(e, panel) {
        hideShowPanel(e, panel);
    }, null, trigger.next('.hide'));

    var requesting = false;
    Y.Helpers = {};
    Y.Helpers.ajax = function (url, options) {
        var ajaxMethod = (!options.method || options.method === 'GET') ? Y.io : Y.FormToken.makeAjaxWithToken;
        ajaxMethod(url, {
            method: options.method || 'GET',
            data: options.data || undefined,
            on: {
                success: function(id, resp) {
                    options.callback.call(options.context,
                        (options.raw) ? resp.responseText : Y.JSON.parse(resp.responseText),
                        options.selectedLabel
                    );
                }
            }
        });
    };

    Y.Helpers.panel = function (options, destroyOnClose) {
        if (typeof destroyOnClose === "undefined") {
            destroyOnClose = true;
        }
        Y.Helpers.panel.defaultHandler || (Y.Helpers.panel.defaultHandler = function(e, destroy) {
            e.halt();
            this.hide();
            destroy && this.destroy();
        });
        Y.Helpers.panel.defaultOpts || (Y.Helpers.panel.defaultOpts = {
            centered: true,
            constrain: true,
            modal: true,
            render: true,
            visible: false,
            width: '450px',
            zIndex: 10000
        });
        var defaultButtons = [{
            value: phpVersionUpdateDialogVars.closeLabel,
            section: Y.WidgetStdMod.FOOTER,
            action: function(e) { Y.Helpers.panel.defaultHandler.call(this, e, destroyOnClose); },
            classNames: 'cancelButton'
        }, {
            template: '<button><span class="screenreader">' + phpVersionUpdateDialogVars.closeLabel + '</span><span aria-hidden="true" role="presentation">\u00D7</span></button>',
            section: Y.WidgetStdMod.HEADER,
            action: function(e) { Y.Helpers.panel.defaultHandler.call(this, e, destroyOnClose); }
        }];

        options = Y.mix(options, Y.Helpers.panel.defaultOpts);
        if (options.buttons) {
            options.buttons = options.buttons.concat(defaultButtons);
        }
        else if (options.overrideButtons) {
            options.buttons = options.overrideButtons;
            delete options.overrideButtons;
        }
        else {
            options.buttons = defaultButtons;
        }
        var panel = new Y.Panel(options);
        panel.get('boundingBox').addClass('versionDialog');
        //By default, the escape key will only hide the panel. Subscribe to the hide event so we can destroy the panel if destroyOnClose is true
        panel.after('visibleChange', function(e) {
            if(e.newVal === false){
                // false = hide; true = show
                Y.Helpers.panel.defaultHandler.call(this, e, destroyOnClose);
                if(options.closeCallback) {
                    options.closeCallback();
                }
            }
        });
        return panel;
    };

    //phpVersionUpdateDialogVars global variable
    var dialog = new Y.Panel({
        contentBox: Y.Node.create('<div id="rn_DialogVersionUpdate" />'),
        bodyContent: phpVersionUpdateDialogVars.dialogContent,
        headerContent: phpVersionUpdateDialogVars.headerLabel,
        width: "600px",
        zIndex: 9999,
        centered: true,
        modal: true,
        render: Y.one(document.body),
        visible: false, // make visible explicitly with .show()
        constraintoviewport: true,
        centered: true,
        hideOn: [],
        buttons    : {
            footer: [
                {
                    name: 'cancel',
                    label: phpVersionUpdateDialogVars.cancelLabel,
                    action: 'onCancel'
                },
                {
                    name: 'update',
                    label: phpVersionUpdateDialogVars.updateLabel,
                    action: 'onUpdate'
                }
            ]
        }
    });

    dialog.onCancel = function (e) {
        e.preventDefault();
        this.hide();
        Y.all('.yui3-widget-mask').setStyle('display','none');
    }

    dialog.onUpdate = function (e) {
        e.preventDefault();
        if(requesting == true) {
            return;
        }
        requesting = true;
        //this.hide();
        //Y.all('.yui3-widget-mask').setStyle('display','none');
        selectList = document.querySelector('#phpVersionstList');
        Y.Helpers.ajax('/ci/admin/versions/updatePhpVersion/', {
            method: 'POST',
            data: 'newVersion=' + encodeURIComponent(selectList.value),
            callback: onResponse,
            context: this,
            selectedLabel: selectList.options[selectList.selectedIndex].text
        });
    }

    onResponse = function(resp, phpVersion) {
        var bodyContent = (resp.success) ? phpVersionUpdateDialogVars.successText.replace("{phpVersion}", phpVersion) : phpVersionUpdateDialogVars.failedText;
        Y.Helpers.panel({
            headerContent: ((resp.success) ? phpVersionUpdateDialogVars.successLabel : phpVersionUpdateDialogVars.failedLabel),
            bodyContent: bodyContent,
            overrideButtons: [{
                value: phpVersionUpdateDialogVars.okLabel,
                section: Y.WidgetStdMod.FOOTER,
                action: function(e) {
                    e.halt();
                    window.location.reload(true);
                    this.hide();
                }
            }]
        }).show();
    }    

    Y.one('#phpVersion').on('click', function (){
      if(requesting == true) {
          return;
      }
      Y.all('.yui3-widget-mask').setStyle('display','block');
      dialog.show();
      selectList = document.querySelector('#phpVersionstList');
      if(selectList.options.length == 0) {
        selectedIndex = 0; 
        i = 0;
        for(vInfo in phpVersionUpdateDialogVars.phpVersions){
            selectedIndex = (vInfo == phpVersionUpdateDialogVars.phpCurrentVerion) ? i : 0; 
            selectList.appendChild(new Option(phpVersionUpdateDialogVars.phpVersions[vInfo], vInfo));
            i++
        }
      }
      selectList.selectedIndex = selectedIndex;
    });
});
