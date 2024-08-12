/* global messages */

/**
 * Upgrade to this version button.
 *
 * Events:
 *
 * - widgets:lock -> widget panel should not be allowed to change
 * - widgets:change -> a widget's version has changed
 * - widgets:refresh -> the widget's display should refresh
 * - timeline:refresh -> the widget's version timeline should refresh
 */
YUI.add('VersionUpdater', function(Y) {
    'use strict';

    // Widget version selector and update button
    var VersionUpdater = Y.Component({
        init: function() {
            this.button = Y.one("#updateWidgetButton");
            this.select = Y.one("#updateVersion");
            this.disabled = true;

            Y.on('versions:refresh', this.refreshVersionSelector, this);
        },
        currentText: '&nbsp;' + messages.inUse,
        events: {
            'click #updateWidgetButton': 'updateWidgetVersion',
            'change #updateVersion':     'versionSelected'
        },
        setDisabled: function(value) {
            this.disabled = value;
            this.button[(value) ? 'addClass' : 'removeClass']('disabled');
        },
        updateWidgetVersion: function() {
            if (this.disabled) return;

            Y.fire('widgets:lock', { set: true });

            var name = this.select.getAttribute("name"),
                newVersion = this.select.get("value"),
                oldVersion = this.select.one("option[data-inuse='true']");

            oldVersion = (oldVersion) ? oldVersion.get("value") : null;

            this.setDisabled(true);

            Y.Helpers.ajax('/ci/admin/versions/modifyWidgetVersions', {
                method: 'POST',
                data: 'widget=' + encodeURIComponent(name) + '&version=' + encodeURIComponent(newVersion),
                callback: function(resp) {
                    if (resp.success) {
                        window.currentVersions.Development[name] = newVersion;

                        if(window.encounteredErrors)
                            window.location.reload(true);

                        this.refreshVersionDisplay(name, oldVersion, newVersion);
                        Y.fire('widgets:change', {'name': name, 'oldVersion': oldVersion, 'newVersion': newVersion});
                    }
                    else {
                        Y.Helpers.panel({
                            headerContent: messages.failure,
                            bodyContent: messages.updateFailure,
                            overrideButtons: [{
                                value: messages.ok,
                                section: Y.WidgetStdMod.FOOTER,
                                action: function(e) {
                                    e.halt();
                                    this.hide();
                                    window.location.reload(true);
                                }
                            }]
                        }).show();
                    }

                    Y.fire('widgets:lock', { set: false });
                },
                context: this
            });
        },
        refreshVersionDisplay: function(name, oldVersion, newVersion) {
            var eventObj = {
                name: name,
                oldVersion: oldVersion,
                newVersion: newVersion
            };

            if(oldVersion) {
                this.select.one('option[value="' + oldVersion + '"]').set('innerHTML', oldVersion).removeAttribute('data-inuse');
            }
            else {
                eventObj.thumbnail = true;
            }
            this.select.one('option[value="' + newVersion + '"]').append(this.currentText).setAttribute('data-inuse', 'true');

            Y.fire('widgets:refresh', eventObj);
            Y.fire('timeline:refresh', eventObj);
        },
        versionSelected: function(e) {
            var target = e.target;
            this.setDisabled(target.get('options').item(target.get('selectedIndex')).getAttribute('data-inuse') !== '');
        },
        refreshVersionSelector: function(e) {
            var name = e.name,
                versions = e.versions,
                html = [],
                node,
                firstSelection,
                alternateSelection,
                isButtonDisabled = true;

            this.select.setHTML('').set('name', name);
            Y.Array.each(versions, function(info) {
                node = Y.Node.create('<option>').setHTML(info.version).set('value', info.version).set('disabled', info.disabled);
                if(info.inUse) {
                    node.append(this.currentText).setAttribute('data-inuse', 'true');
                    firstSelection = info.version;
                }
                if(!info.disabled && !info.inUse) {
                    alternateSelection = info.version;
                    isButtonDisabled = false;
                }
                html.push(node.get('outerHTML') || Y.Node.create('<select>').append(node).getHTML());
            }, this);

            this.select.append(html.reverse().join(''));
            this.select.set('value', firstSelection || alternateSelection);
            this.setDisabled(firstSelection || isButtonDisabled);
        }
    });

    Y.VersionUpdater = new VersionUpdater();

}, null, {
    requires: ['Component', 'Helpers']
});
