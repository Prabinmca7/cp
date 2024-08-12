/* global messages */

/**
 * Framework panel.
 */
YUI.add('FrameworkPanel', function(Y) {
    'use strict';

    var FrameworkPanel = Y.Component({
        init: function() {
            this.frameworkDetails = Y.one("#frameworkDetails");
            this.button = Y.one('#updateFrameworkButton');
            this.disabled = false;
            this.newFrameworkHasCompatibleWidgets = false;
            this.frameworkDetails.delegate('click', Y.Helpers.toggle, 'a.toggleDetails', this);

            new Y.ListFocusManager(Y.one('#frameworks')).on('select', this.click, this);
        },
        events: {
            'click #frameworks .listing-item': 'click',
            'click #updateFrameworkButton': 'updateFramework'
        },
        history: {
            'key':       'framework',
            'tab':       'frameworkVersions',
            'handler':   'checkForUrlPreselection'
        },
        setDisabled: function(value) {
            this.disabled = value;
            this.button[(value) ? 'addClass' : 'removeClass']('disabled');
        },
        checkForUrlPreselection: function(name) {
            if (name && (name = Y.one("#frameworks .listing-item[data-name='" + name + "']"))) {
                Y.Helpers.scrollListItemIntoView(name.removeClass("hide"));
                this.click({target: name});
            }
        },
        click: function(e) {
            var target = e.target,
                result = Y.Helpers.panelHandler(target),
                name;

            if (result.alreadySelected) return;

            if (result.previous) {
                this.replaceContent(result.previous);
            }
            else {
                Y.one("#frameworkVersions .controls").removeClass("hide");
            }

            target = result.target;
            name = target.getAttribute("data-name");
            this.selectedFrameworkVersion = name;

            this.buildContent(target, name);
            this.setDisabled(name === window.currentFramework);
            Y.Helpers.History.addValue('framework', name);
        },
        replaceContent: function(previousSelected) {
            previousSelected.removeClass("selected").one(".hide").append(this.frameworkDetails.one(".version"));
        },
        buildContent: function(target, name) {
            Y.one("#frameworkName").setHTML(name);
            this.frameworkDetails.setHTML('').removeClass('empty');
            var contentAlreadyExists = target.one('.version');
            if (!contentAlreadyExists) {
                var version = encodeURIComponent(name);
                this.frameworkDetails.append("<div class='bigwait'></div>");
                Y.Helpers.ajax("/ci/admin/versions/getChangelog/framework/" + version + '/' + version, {
                    callback: function(resp) {
                        var div = Y.Node.create("<div class='version'>")
                            .append("<div class='versionInfo'>")
                            .append("<div class='changelog'></div>");

                        div.one('.changelog').append(Y.VersionPanel.getChangelogList(resp[version], 'framework' + '_' + version));
                        if (name !== window.currentFramework) {
                            div.addClass("disabled");
                        }
                        this.frameworkDetails.setHTML('').append(div);
                    },
                    context: this
                });
            }
            else {
                this.frameworkDetails.setHTML('').append(contentAlreadyExists);
            }
        },
        updateFramework: function(e) {
            var target = e.target;

            e.halt();

            if (this.disabled) return;

            var collateral = this.getCollateral();
            (Y.Object.isEmpty(collateral))
                ? this.performUpdate()
                : this.displayPrompt(this.buildDialogContent(collateral));
        },
        displayPrompt: function(content) {
            var self = this,
                buttons = [{
                    value: messages.updateFramework,
                    section: Y.WidgetStdMod.FOOTER,
                    action: function(e) { e.halt(); self.performUpdate(); this.hide(); }
                }];
            if (this.newFrameworkHasCompatibleWidgets) {
                buttons.unshift({
                    value: messages.updateWidgetsAndFramework,
                    section: Y.WidgetStdMod.FOOTER,
                    action: function(e) { e.halt(); self.performUpdate(true); this.hide(); },
                    classNames: 'confirmButton'
                });
            }
            Y.Helpers.panel({
                headerContent: messages.updateToFramework.replace('%s', this.selectedFrameworkVersion),
                bodyContent: content,
                width: '600px',
                buttons: buttons
            }).show();
        },
        buildDialogContent: function(listings) {
            var content = Y.Node.create('<div class="widgetUpgradeBox"></div>');
            Y.Object.each(listings, function(info, name, node) {
                node = Y.Node.create('<div>')
                    .append('<span class="name">' + name + '</span>');
                if (info.newVersion) {
                    this.newFrameworkHasCompatibleWidgets = true;
                    node.append('<span class="details">' + info.currentVersion + ' \u2192 ' + info.newVersion + '</span>');
                }
                else {
                    node.append('<span class="details highlight">' + messages.versionNotSupported + '</span>').addClass('widgetMessage');
                }

                content.append(node);
            }, this);
            var message = (this.newFrameworkHasCompatibleWidgets) ? messages.updateFrameworkWithWidgets : messages.updateFrameworkWithoutWidgets,
                frag = Y.one(document.createDocumentFragment()).append('<p>' + message + '</p>');
            return frag.append(content);
        },
        getCollateral: function() {
            var incompatibleWidgets = {}, widgetVersions, isCompatible, versionDetails,
                desiredVersion = this.selectedFrameworkVersion;

            //Look through all of the widgets and try to find versions that work with the new framework selection
            Y.Object.each(window.currentVersions.Development, function(currentVersion, name) {
                //Skip any widgets which exist in widgetVersions, but don't exist in allWidgets (they aren't on disk)
                if(!window.allWidgets[name]) return;

                //If the current version supports the desired framework, or doesn't have a list of framework requirements, the version is compatible
                isCompatible = false;
                widgetVersions = window.allWidgets[name].versions;
                isCompatible = Y.Array.some(widgetVersions, function(versionDetails) {
                    if (currentVersion === versionDetails.version) {
                        return Y.VersionHelper.hasCompatibleFramework(versionDetails.framework, desiredVersion);
                    }
                });

                //If the version wasn't compatible, work backwards through the versions until we find one that is and mark it incompatible
                if(!isCompatible) {
                    for(var i = widgetVersions.length - 1; i >= 0; i--) {
                        versionDetails = widgetVersions[i];
                        if(Y.VersionHelper.hasCompatibleFramework(versionDetails.framework, desiredVersion)) {
                            incompatibleWidgets[name] = {currentVersion: currentVersion, newVersion: versionDetails.version};
                            break;
                        }
                    }
                    //A compatible version wasn't found, so indicate that
                    if(!incompatibleWidgets[name]) {
                        this.foundIncompatibilityWithNoVersion = true;
                        incompatibleWidgets[name] = {currentVersion: currentVersion, newVersion: null};
                    }
                }
            }, this);

            this.foundIncompatibility = !Y.Object.isEmpty(incompatibleWidgets);
            return incompatibleWidgets;
        },
        performUpdate: function(updateWidgets) {
            this.setDisabled(true);
            Y.Helpers.ajax('/ci/admin/versions/modifyFrameworkVersion/', {
                method: 'POST',
                data: 'version=' + encodeURIComponent(this.selectedFrameworkVersion) + '&oldVersion=' + encodeURIComponent(window.currentFramework) + ((updateWidgets) ? '&updateWidgets=true' : ''),
                callback: this.onResponse,
                context: this
            });
            this.updatedWidgets = updateWidgets;
        },
        onResponse: function(resp) {
            var bodyContent = (resp.success)
                                    ? (this.updatedWidgets)
                                        ? (this.foundIncompatibilityWithNoVersion)
                                            ? messages.widgetsUpdatedWithIncompatible
                                            : messages.widgetsUpdated
                                        : (this.foundIncompatibility)
                                            ? messages.frameworkUpdatedWithoutWidgets
                                            : messages.frameworkUpdated
                                    : messages.widgetsNotUpdated;

            Y.Helpers.panel({
                headerContent: ((resp.success) ? messages.success : messages.failure),
                bodyContent: bodyContent,
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
        }
    });

    Y.FrameworkPanel = new FrameworkPanel();

}, null, {
    requires: ['Helpers', 'VersionHelper', 'Component', 'ListFocusManager']
});
