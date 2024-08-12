/* global messages */

/**
 * Update all widgets button.
 */
YUI.add('UpdateAllButton', function(Y) {
    'use strict';

    var UpdateAllButton = Y.Component({
        events: {
            'click #updateAll': 'showDialog'
        },
        showDialog: function() {
            if (!this.panel) {
                var self = this,
                    content = this.getDialogContents(),
                    buttons;

                if (content) {
                    buttons = [{
                        value: messages.updateAll,
                        section: Y.WidgetStdMod.FOOTER,
                        action: function(e) { e.halt(); self.update.apply(self); self.panel.hide(); },
                        classNames: 'confirmButton'
                    }];
                }
                else {
                    content = messages.widgetsAlreadyUpdated;
                }

                this.panel = Y.Helpers.panel({
                    headerContent: messages.updateAll,
                    bodyContent: content,
                    width: '600px',
                    buttons: buttons
                }, false);
            }
            this.panel.show();
        },
        getDialogContents: function() {
            var frag = Y.one(document.createDocumentFragment()).append('<p>' + messages.updateAllDisclaimer + '</p>'),
                box = Y.Node.create('<div class="widgetUpgradeBox">');

            this.widgets || (this.widgets = this.getAllEligibleWidgets());
            Y.Object.each(this.widgets, function(newVersion, name) {
                box.append('<div><span class="name">' + name + '</span><span class="details">' + window.currentVersions.Development[name] + ' \u2192 ' + newVersion + '</span></div>');
            }, this);

            if (box.get('innerHTML')) {
                return frag.append(box);
            }
            return false;
        },
        update: function() {
            Y.Helpers.ajax('/ci/admin/versions/modifyWidgetVersions', {
                method: 'POST',
                data: 'widget=all',
                callback: this.onResponse,
                context: this
            });
        },
        onResponse: function(resp) {
            Y.Helpers.panel({
                headerContent: ((resp.success) ? messages.success : messages.failure),
                bodyContent: ((resp.success) ? messages.widgetsUpdated : messages.widgetsNotUpdated),
                overrideButtons: [{
                    value: messages.ok,
                    section: Y.WidgetStdMod.FOOTER,
                    action: function(e) {
                        e.halt();
                        window.location.reload(true);
                        this.hide();
                    }
                }]
            }).show();
        },
        getAllEligibleWidgets: function() {
            var widgets = {}, currentWidgetVersion;
            Y.Object.each(window.allWidgets, function(item, name) {
                currentWidgetVersion = window.currentVersions.Development[name];

                if (!currentWidgetVersion) return; // widget isn't activated

                Y.Array.each(item.versions, function(versionInfo) {
                    if ((versionInfo.framework === null || Y.Array.indexOf(versionInfo.framework, window.currentFramework) !== -1) && Y.VersionHelper.compareVersionNumbers(versionInfo.version, currentWidgetVersion) === 1) {
                        widgets[name] || (widgets[name] = {});
                        widgets[name] = versionInfo.version;
                    }
                }, this);
            }, this);
            return widgets;
        }
    });

    Y.UpdateAllButton = new UpdateAllButton();

}, null, {
    requires: ['Helpers', 'VersionHelper', 'Component']
});
