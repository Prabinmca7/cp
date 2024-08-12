/* global messages,RightNowCoreAssets */

/**
 * Big ol' widget panel.
 *
 * TODO - needs additional split out refactoring...
 */
YUI.add('WidgetPanel', function(Y) {
    'use strict';

    var WidgetPanel = Y.Component({
        init: function() {
            this.tabs = Y.one("#tabs");
            this.tabs.delegate('click', this.clickDocumentation, 'a.documentation', this);
            this.tabs.delegate('click', this.clickDependencies, 'a.dependencies', this);
            this.tabs.delegate('click', this._widgetNameClick, 'a.keyElement', this);
            this.tabs.delegate('click', this._viewCodeSnippet, 'a.viewCodeSnippet', this);
            this.tabs.delegate('click', Y.Helpers.toggle, 'a.toggleDetails', this);

            this.details = Y.one("#details");
            this.details.delegate('click', this.showThumbnailDialog, 'a.thumbnail', this);

            this.locked = false;

            this.widgetVersionChanges = {};

            Y.on('widgets:select', this.click, this);
            Y.on('widgets:lock', this.setLock, this);
            Y.on('widgets:refresh', this.refresh, this);
            Y.on('widgets:change', this.widgetVersionChanged, this);

            this.focusManager = new Y.ListFocusManager(Y.one('#widgets'));
            this.focusManager.on('select', this.click, this);
        },
        events: {
            'click #widgets .listing-item':  'click',
            'change #updateVersion':         'versionSelected'
        },
        history: [{
            'key':       'widget',
            'tab':       'widgetVersions',
            'handler':   'checkForUrlPreselection'
        }, {
            'key':       'docs',
            'tab':       'widgetVersions',
            'handler':   'checkForDocsDialog'
        }],
        asyncContent: {
            views:          {remote: 'getViewsUsedOn', handler: 'buildViewsUsedOnContent'},
            changelog:      {remote: 'getChangelog', handler: 'buildWidgetVersionContent'},
            changehistory:  {remote: 'getChangeHistory', handler: 'buildRecentWidgetChangesContent'},
            wait: '<div class="bigwait"></div>'
        },

        widgetVersionChanged: function(e) {
            this.widgetVersionChanges[e.name] = e.newVersion;
        },

        checkForDocsDialog: function(openDialog) {
            var name, version;

            if(!(name = Y.Helpers.History.get('widget'))) return;

            if(openDialog) {
                if(version = Y.Helpers.History.get('version')) {
                    this.showDocumentation(name, version);
                }
                else {
                    // Find the latest widget version to be sent to the showDocumentation() when searched from the Search Widget box.
                    var indexLatestVersion = window.allWidgets[name].versions.length - 1;
                    this.showDocumentation(name, window.allWidgets[name].versions[indexLatestVersion].version);
                }
                window.scroll(0, 0); // fix for IE8, reposition after documentation is loaded so header is not cut off
            }
        },
        checkForUrlPreselection: function(name, additional) {
            var node;

            if (name && (node = Y.one("#widgets .listing-item[data-name='" + name + "']"))) {
                Y.Helpers.scrollListItemIntoView(node);
                this.click({target: node}, ((typeof additional === 'number') ? additional : null));
            }
        },
        setLock: function(e) {
            this.locked = e.set;
        },
        listenToScroll: function(name) {
            if (!this.listenToScroll.areas) {
                this.listenToScroll.areas = {};
            }
            if (this.scrollListener) {
                this.scrollListener.cancel();
            }
            var panel = Y.one("#widgetPanel"),
                versions = panel.all("#tabs .version"),
                scrollTop,
                areas = [],
                startingZIndex = 2;

            panel.set("scrollTop", 0);

            areas = this.listenToScroll.areas[name] || [];

            if (!versions.size()) return;

            if (!areas.length) {
                versions.each(function(node, y, h3, area) {
                    y = node.getY();
                    area = {top: y, bottom: y + parseFloat(node.getComputedStyle("height"))};
                    h3 = node.one("h3");
                    h3.setStyles({
                        width: '100%',
                        zIndex: ++startingZIndex
                    });
                    areas.push(area);
                });
                this.listenToScroll.areas[name] = areas;
            }

            this.scrollListener = Y.Lang.later(100, this, function() {
                scrollTop = panel.get("scrollTop") + panel.getY();
                Y.Object.each(areas, function(val, key, item) {
                    item = versions.item(key).one("h3");
                    if (scrollTop > val.top && scrollTop < val.bottom) {
                        item.setStyles({
                            position: "fixed",
                            top: panel.getY() - (document.body.scrollTop || document.documentElement.scrollTop) + "px",
                            width: (parseFloat(item.get('parentNode').getComputedStyle('width'))) + "px"
                        });
                    }
                    else if (item.getStyle("position") === "fixed") {
                        item.setStyles({
                            position: "static",
                            width: '100%'
                        });
                    }
                });
            }, null, true);
        },
        click: function(e, tabIndex) {
            if (this.locked) return;

            this.setLock(true);

            var target = e.target,
                result = Y.Helpers.panelHandler(target);

            if (result.alreadySelected) {
                this.setLock(false);
                return;
            }

            if (result.previous) {
                this.replaceContent(result.previous);
            }

            this.focusManager.fire('newSelection', target);

            target = result.target;

            var name = target.getAttribute("data-name");
            this.setHeading(name);
            this.buildContent(target, name, tabIndex);

            Y.Helpers.History.addValue('widget', name);
        },
        setHeading: function(name) {
            Y.one("#widgetName").set("innerHTML", name);
        },
        getAsyncContent: function(type, data, passThruData, callback) {
            type = this.asyncContent[type];
            Y.Helpers.ajax('/ci/admin/versions/' + type.remote + '/' + encodeURIComponent(data), {
                callback: function(resp) {
                    this[type.handler].call(this, resp, passThruData);
                    if (callback) {
                        callback.call(this, resp, passThruData);
                    }
                },
                context: this
            });
        },
        buildContent: function(target, name, tabIndex) {
            this.details.removeClass('empty').setHTML('').append(target.one('.thumbnail')).append(target.one('.timeline')).append(target.one('.category'));
            this.tabs.setHTML('');

            var contentAlreadyExists = target.one('.versions').get('innerHTML');
            if (!contentAlreadyExists) {
                target.one('.versions').append(this.asyncContent.wait);
                this.getAsyncContent('changelog', name, {disabled: target.hasClass('disabled'), widgetName: name});
            }

            this.tabView = new Y.TabView({
                children: [{
                    label: messages.availableVersions,
                    content: target.one('.versions')
                }, {
                    label: messages.viewsUsedOn,
                    content: target.one('.views')
                }, {
                    label: messages.recentVersionChanges,
                    content: target.one('.changes')
                }]
            }).render(this.tabs);
            this.tabView.on('selectionChange', function(e, panel) {
                panel = e.newVal.get('panelNode').one('*');
                if (panel.hasClass('views')) {
                    if (!panel.get('innerHTML')) {
                        panel.append(this.asyncContent.wait);
                        this.getAsyncContent('views', name, name);
                    }
                }
                else if(panel.hasClass('changes') && (!panel.get('innerHTML') || name in this.widgetVersionChanges)){
                    panel.append(this.asyncContent.wait);
                    this.getAsyncContent('changehistory', name);
                }
            }, this);
            if (tabIndex) {
                this.tabView.selectChild(tabIndex);
            }

            this.updateVersionSelector(name);
            this.addThumbnail(name);
            this.addNotice(target);
            this.addCategory(name);

            if (contentAlreadyExists) {
                this.listenToScroll(name);
                this.setLock(false);
            }
        },
        getDependencies: function(widget, version) {
            var widgetInfo = window.allWidgets[widget],
                versionInfo, i, length, dependencies = {};
            if(widgetInfo) {
                for(i = 0, length = widgetInfo.versions.length; i < length; i++) {
                    versionInfo = widgetInfo.versions[i];
                    if(versionInfo.version === version) {
                        if(versionInfo['extends'])
                            dependencies['extends'] = versionInfo['extends'];
                        if(versionInfo['contains'])
                            dependencies['contains'] = versionInfo['contains'];
                        if(versionInfo['children'])
                            dependencies['children'] = versionInfo['children'];
                        return !Y.Object.isEmpty(dependencies) ? dependencies : null;
                    }
                }
            }
        },
        buildWidgetVersionContent: function(newData, data) {
            var versionContent = this.tabs.one('.versions').setHTML(''),
                timelineEntries = this.details.all('.timeline .label'),
                versionEntry, frameworkVersions, widgetVersion, modesInUse, timelineEntry, modeLabels = '';

            //Create a version entry for each version in the timeline
            newData = newData || [];
            while(timelineEntry = timelineEntries.pop()) {
                frameworkVersions = timelineEntry.getAttribute('data-framework');
                widgetVersion = timelineEntry.getAttribute('data-version');

                //Create the entry and insert the changelog information
                versionEntry = Y.Node.create("<div class='version'>")
                                .append(Y.Node.create("<h3>").set("innerHTML", widgetVersion))
                                .append(Y.Node.create("<div class='versionInfo'>")
                                .append(Y.Node.create("<div class='information'>")
                                .append("<div class='dependency'>" + (frameworkVersions ? messages.requiresFramework.replace('%s', frameworkVersions) : messages.allFrameworksSupported) + "<br/></div>"))
                                .append("<a class='documentation button' role='button' href='javascript:void()'><i class='fa fa-book'></i> " + messages.documentation + "</a>")
                                .append(this.getDependencies(data.widgetName, widgetVersion)
                                    ? "<a class='dependencies button' role='button' href='javascript:void()'><i class='fa fa-puzzle-piece'></i> " + messages.dependencies + "</a>"
                                    : '')
                                .append("<div class='changelog'>"));

                versionEntry.one('.changelog').append(Y.VersionPanel.getChangelogList(newData[widgetVersion], data.widgetName + '_' + widgetVersion));

                //If we have a list of frameworks and it doesn't contain the current framework, mark the entry disabled
                if(!Y.VersionHelper.hasCompatibleFramework(frameworkVersions, window.currentFramework)) {
                    versionEntry.addClass('disabled');
                }

                //Add a label for staging, development or production
                if(modesInUse = timelineEntry.getAttribute('data-inuse')) {
                    modeLabels = '';
                    Y.Array.each(modesInUse.split(','), function(mode) {
                        mode = Y.Lang.trim(mode);
                        modeLabels += '<span data-mode="' + mode + '">' + messages.modeLabels[mode] + '</span>';
                    });
                    versionEntry.one('.versionInfo').insert('<div class="inuse">' + modeLabels + '</div>', 0);
                }
                versionContent.append(versionEntry);
            }

            if (!data.disabled) {
                this.addDeactivateButton(versionContent);
            }
            this.addDeleteButton(versionContent);
            this.listenToScroll(data.widgetName);
            this.setLock(false);
        },
        _widgetNameClick: function(e) {
            this.checkForUrlPreselection(e.currentTarget.getAttribute("data-key"), 1);
        },
        _viewCodeSnippet: function(e) {
            var dialog = Y.Helpers.panel({
                    headerContent: messages.viewCodeSnippetsHeader.replace('%s', e.currentTarget.getAttribute("data-key")),
                    bodyContent: '<div class="wait"></div>',
                    width: '80%'
                }).show();
            Y.Helpers.ajax('/ci/admin/versions/getWidgetFileUsage/' + e.currentTarget.getAttribute("data-arg"), {
                callback: function(resp) {
                    dialog.set('bodyContent', '<div class="snippetDialog"><span class="updateTime">' + messages.lastCheckTime.replace('%s', resp.lastCheckTime) + '</span>' + resp.snippet + '</div>')
                        .centered();
                }
            });
        },

        buildViewsUsedOnContent: function(newData, passThruData) {
            var viewData = newData.references,
                lastCheckTime = newData.lastCheckTime,
                target = this.tabs.one('.views').setHTML('<span class="updateTime">' + messages.lastCheckTime.replace('%s', lastCheckTime) + '</span>');

            if (!viewData) {
                target.append(Y.Node.create('<h3>').set('innerHTML', messages.widgetUnusedInView));
                return;
            }

            var section = ' \
                    <section class="{type}"> \
                        <h2>{title}</h2> \
                        <ul></ul> \
                    </section> \
                ',
                listItem = '<li>{value}</li>',
                viewCodeLink = ' \
                    <a href="javascript:void(0);" role="button" class="viewCodeSnippet" data-arg="{passThru}" data-key="{key}"> \
                    {title} \
                    </a> \
                ',
                noLink = '<span>{key}</span>',
                widgetViewItem = '<a href="javascript:void(0);" class="keyElement" data-key="{key}">{key}</a>',
                link, isViewPartial, listItemText;

            // Render the section for each view type.
            Y.Array.each(Y.Array.dedupe(Y.Object.values(viewData)), function (type) {
                target.append(Y.Lang.sub(section, {
                    title: messages.displayType[type],
                    type: type
                }));
            });

            // Render each view list item.
            Y.Object.each(viewData, function (type, viewPath) {
                link = Y.Lang.sub(viewCodeLink, {
                    passThru: [encodeURIComponent(passThruData), type, encodeURIComponent(viewPath)].join('/'),
                    key: viewPath,
                    title: messages.viewCodeSnippets
                });
                isViewPartial = viewPath.substring(viewPath.length - 9) === '.html.php';
                listItemText = Y.Lang.sub(type === 'view' || isViewPartial ? noLink : widgetViewItem, {
                    key: viewPath
                });

                target.one('.' + type + ' ul').append(
                    Y.Lang.sub(listItem, {
                        value: listItemText + link
                    })
                );
            });
        },

        buildRecentWidgetChangesContent: function(newData){
            var target = this.tabs.one('.changes').set('innerHTML', '');
            if(newData.length === 0){
                target.append(Y.Node.create("<h3>").set("innerHTML", messages.noVersionChanges));
            }
            else{
                var div;
                Y.Object.each(newData, function(val){
                    div = Y.Node.create("<div class='change'>")
                        .append("<h3>" + val.previous + " &#x2192; " + val.newVersion + "</h3>")
                        .append("<div class='changeDetails'><span class='who'>" +  val.user + "</span><br/><span class='date'>" + val.time + "</span></div>");
                    target.append(div);
                }, this);
            }
        },
        replaceContent: function(previousSelection) {
            previousSelection.removeClass("selected").one(".hide")
                .append(this.details.one('.thumbnail'))
                .append(this.details.one('.timeline'))
                .append(this.details.one('.category'))
                .append(this.tabs.one('.versions'))
                .append(this.tabs.one('.views'))
                .append(this.tabs.one('.changes'));
        },
        updateVersionSelector: function(name) {
            var controlsArea = Y.one("#widgetVersions .controls").setStyles({height: 0, opacity: 0}).removeClass("hide"),
                versions = [];

            this.details.one('.timeline').all('.label').each(function(widgetVersion) {
                versions.push({
                    version: widgetVersion.getHTML(),
                    disabled: !Y.VersionHelper.hasCompatibleFramework(widgetVersion.getAttribute('data-framework'), window.currentFramework),
                    inUse: widgetVersion.getAttribute("data-inuse").indexOf(messages.development) > -1
                });
            });

            if(versions.length === 1 && versions[0].inUse) return; //The only available version is the one in use; hide the select and button

            Y.fire('versions:refresh', { name: name, versions: versions });

            new Y.Anim({
                node: controlsArea,
                from: {height: '0px'},
                to: {height: '30px', opacity: 1},
                duration: 1.0,
                easing: 'easeBothStrong'
            }).run();
        },
        showThumbnailDialog: function(e) {
            e.halt();
            var img = e.currentTarget.one('img');
            if (img) {
                new Y.PreviewImageDialog(Y.one('#widgetName').getHTML(), function () {
                    e.currentTarget.focus();
                });
            }

        },
        addCategory: function(name) {
            var category = window.allWidgets[name].category,
                categoryDisplayNode = this.details.one('.category'),
                categoryHeader = categoryDisplayNode.one('b');

            if(category.length > 0) {
                categoryHeader.remove();
                categoryDisplayNode.set('text', '');
                categoryDisplayNode.append(categoryHeader);
                categoryDisplayNode.append('&nbsp;' + category.join(', '));
                categoryDisplayNode.removeClass('hide');
            }
            else {
                categoryDisplayNode.addClass('hide');
            }
        },
        addThumbnail: function(name, forceReload) {
            var thumbnail = this.details.one(".thumbnail");

            if (thumbnail.get("children").size() && !forceReload) return;

            // Thumbnail hasn't already been added or is being forcibly re-added.
            var pic = Y.one(new Image());
            pic.on({
                load: function() {
                    if (parseFloat(pic.get('height')) < parseFloat(thumbnail.getComputedStyle('height')) &&
                        parseFloat(pic.get('width')) < parseFloat(thumbnail.getComputedStyle('width'))) {
                        // Center the image if it's smaller than its parent
                        pic.setStyles({
                            display: 'block',
                            margin: '12% auto 0'
                        });
                    }
                },
                error: function() {
                    thumbnail.addClass('noPreview').append('<span class="fa fa-picture-o">' + messages.noPreview + '</span>');
                    pic.remove();
                    thumbnail.one('span.zoomIcon').remove();
                }
            });
            thumbnail.insert(pic.set('src', "/ci/admin/docs/widgets/previewFile/" +  name + "/preview/preview.png"), 'replace');

            if (Y.one('#widgetPanel .thumbnail img')) {
                thumbnail.insert(Y.Node.create("<span>").addClass("fa fa-search-plus").addClass("zoomIcon"));
            }
        },
        addNotice: function(target) {
            if (target.hasClass("woefully") || target.hasClass("outofdate") || target.hasClass("disabled")) {
                var notice = Y.Node.create("<div>").addClass("notice"),
                    message, className;
                if (target.hasClass("woefully")) {
                    className = "woefully";
                    message = messages.updateFrameworkMessage;
                }
                else if (target.hasClass("outofdate")) {
                    className = "outofdate";
                    message = messages.newUpdates;
                }
                else {
                    className = "disabled";
                    message = messages.notActivated;
                }
                this.details.append(notice.set("innerHTML", message).addClass(className));
            }
        },
        addDeactivateButton: function(target) {
            var extraActions = this.addExtraActions(target);
            if(!extraActions.one('.deactivateButton')) {
                extraActions.insert('<button class="deactivateButton" type="button">' + messages.deactivateThisWidget + '</button>', 0);
                extraActions.one('.deactivateButton').on('click', this.deactivateOrDeletePrompt, this, true);
            }
        },
        addDeleteButton: function(target) {
            var name = Y.one('#widgetName').get('innerHTML'),
                type = name.split('/').shift(),
                extraActions;

            if (type !== 'custom')
                return;

            extraActions = this.addExtraActions(target);
            if(!extraActions.one('.deleteButton')) {
                extraActions.append('<button class="deleteButton" type="button">' + messages.deleteThisWidget + '</button>');
                extraActions.one('.deleteButton').on('click', this.deactivateOrDeletePrompt, this, false);
            }
        },
        addExtraActions: function(target) {
            var extraActions = target.one('.extraActions');
            if (extraActions)
                return extraActions;
            target.append('<div class="extraActions"></div>');
            return target.one('.extraActions');
        },

        refresh: function(e) {
            if (e.thumbnail) {
                this.addThumbnail(e.name, true);
            }
            this.refreshListItems(e.name, e.newVersion, !e.oldVersion);
            this.refreshVersionsList(e.oldVersion, e.newVersion);
        },

        refreshVersionsList: function(oldVersion, newVersion) {
            var updatedHeadings = 0,
                versions = this.tabs.one('.versions');

            //Update all of the headers on each version
            versions.all('h3').each(function(h3, version, next) {
                version = Y.Lang.trim(h3.get("innerHTML"));
                if (version === oldVersion) {
                    h3.next().one('span[data-mode="Development"]').remove();
                    updatedHeadings++;
                }
                else if (version === newVersion) {
                    next = h3.next();
                    ((next.get('firstChild').get('className') === 'inuse')
                        ? next
                        : next.insert('<div class="inuse"></div>', 0)).get('firstChild')
                    .insert('<span data-mode="Development">' + messages.modeLabels[messages.development] + '</span>', 0);
                    updatedHeadings++;
                }
                if (updatedHeadings === 2) {
                    return;
                }
            }, this);

            //Add the deactivate and delete buttons if we haven't already
            this.addDeactivateButton(versions);
            this.addDeleteButton(versions);
        },
        refreshListItems: function(name, newVersion, activating) {
            var listItem = Y.one('#widgets .listing-item[data-name="' + name + '"]'),
                versionInfo = window.allWidgets[name],
                classesToRemove = ['disabled'],
                classesToAdd = ['inuse'],
                tagClasses = [],
                latest = versionInfo.versions[versionInfo.versions.length - 1],
                tooltip = messages.outOfDateTooltip;

            //Update the out of date tags on the widgets
            listItem.one('.tag-outofdate') && listItem.one('.tag-outofdate').remove();
            if(newVersion !== latest.version) {
                classesToRemove.push('uptodate');
                classesToAdd.push('outofdate');
                tagClasses.push('tag-outofdate');

                if(!Y.VersionHelper.hasCompatibleFramework(latest.framework, window.currentFramework)) {
                    classesToAdd.push('woefully');
                    tagClasses.push('tag-woefully');
                    tooltip = messages.woefullyTooltip;
                }
                listItem.one('.main').append('<div data-tooltip="' + tooltip + '" class="' + tagClasses.join(' ') + '">' + messages.outOfDate + '</div>');
            }
            else {
                classesToRemove.push('outofdate', 'woefully');
                classesToAdd.push('uptodate');
            }

            //Change the modes inuse for this widget version
            Y.Array.some(versionInfo.versions, function(item) {
                if (newVersion === item.version) {
                    if (!item.inuse || !item.inuse.length) {
                        item.inuse = [messages.development];
                    }
                    else {
                        item.inuse.push(messages.development);
                    }
                    return true;
                }
            });

            Y.Array.each(classesToRemove, function(className) {
                listItem.removeClass(className);
            });
            Y.Array.each(classesToAdd, function(className) {
                listItem.addClass(className);
            });

            if (activating && listItem.get('parentNode').hasClass('notinuse')) {
                // The not-in-use view filter is currently being applied and the widget
                // has just been activated. Indicate that it no longer applies to this
                // filter by animating it out.
                var anim = new Y.Anim({node: listItem, to: { left: -1000 }});
                anim.on('end', function() {
                    listItem.addClass('hide').setStyle('left', 'auto');
                });
                anim.run();
            }
        },
        deactivateOrDeletePrompt: function(evt, isDeactivate) {
            var action = isDeactivate ? 'deactivate' : 'delete',
                name = Y.one('#widgetName').get('innerHTML'),
                waitDiv = '<div class="wait"></div>',
                deactivatedWidgets = name,
                dialog = Y.Helpers.panel({
                    headerContent: messages[action + 'Widget'].replace('%s', name.split('/').pop())
                }),
                alreadyLoadedViewsTab = this.tabs.one('.views').get('children'),
                initiateDeactivate = function(forceDeactivate) {
                    var self = this,
                        childWidgets = [],
                        childString = '',
                        buttons = [],
                        currentWidgetVersion = window.currentVersions.Development[name],
                        findExtendedWidgets = function(parentWidget){
                            var childWidgetList = [];
                            //Iterate through all of the widgets and find the activated ones which extend from this widget
                            Y.Object.each(window.allWidgets, function(widgetInfo, widgetKey) {
                                Y.Object.each(widgetInfo.versions, function(versionInfo, currentVersion) {
                                    currentVersion = window.currentVersions.Development[widgetKey];
                                    // determine that we are on the widget version active in development (`currentVersion`) and
                                    //  that it extends from the widget we intend to deactivate
                                    if(versionInfo.version === currentVersion && versionInfo['extends'] && versionInfo['extends'][parentWidget]) {
                                        // verify that the version this widget extends is either the one active in development (`currentWidgetVersion`) or
                                        //  the widget extends from 'all' versions
                                        Y.Object.each(versionInfo['extends'][parentWidget], function(versionToExtend) {
                                            if(currentWidgetVersion === versionToExtend || versionToExtend === 'N/A') {
                                                childWidgetList.push(widgetKey);
                                                //Recurse into this widget to see if it has any extended widgets
                                                childWidgetList = childWidgetList.concat(findExtendedWidgets(widgetKey));
                                            }
                                        });
                                    }
                                });
                            });
                            return childWidgetList;
                        },
                        childWidgets = findExtendedWidgets(name);

                    buttons.push({
                        value: messages[action],
                        section: Y.WidgetStdMod.FOOTER,
                        action: function(e) {
                            e.halt();
                            dialog.set('bodyContent', waitDiv).show();
                            if(childWidgets.length > 0)
                                deactivatedWidgets += ',' + childWidgets.join(',');
                            self.deactivateOrDelete(isDeactivate ? deactivatedWidgets : name, function(resp) {
                                var bodyContent = (resp.success) ? messages[action + 'Success'] : messages[action + 'Failure'],
                                    i, length = (resp.files && resp.files.length) ? resp.files.length : 0;

                                if (length > 0) {
                                    bodyContent += '<div class="dialogInnerMessage">' + messages.deleteSuccessFiles + '</div><ul class="childList">';
                                    for (i = 0; i < length; i++) {
                                        bodyContent += '<li>' + resp.files[i] + '</li>';
                                    }
                                    bodyContent += '</ul>';
                                }

                                dialog.set('bodyContent', bodyContent).set('buttons', [{
                                    value: messages.ok,
                                    section: Y.WidgetStdMod.FOOTER,
                                    action: function(e) {
                                        e.halt();
                                        window.location.reload(true);
                                    }
                                }]);
                            }, isDeactivate);
                        },
                        classNames: 'confirmButton'
                    });

                    if(forceDeactivate) {
                        buttons.push({
                            value: messages.displayContainingViews,
                            section: Y.WidgetStdMod.FOOTER,
                            action: function() {
                                Y.one('#widgetPanel').set('scrollTop', 0);
                                self.tabView.selectChild(1);
                                this.hide();
                            },
                            classNames: 'displayViewsButton'
                        });
                    }

                    buttons.push({
                        value: messages.cancel,
                        section: Y.WidgetStdMod.FOOTER,
                        action: function(e) {
                            e.halt();
                            this.hide();
                        },
                        classNames: 'cancelButton'
                    });

                    if(childWidgets.length > 0) {
                        childString += '<div class="dialogInnerMessage">' + messages[action + 'Children'] + '</div><ul class="childList">';
                        for(var i = 0; i < childWidgets.length; i++) {
                            childString += '<li>' + childWidgets[i] + '</li>';
                        }
                        childString += '</ul>';
                    }

                    dialog.set('bodyContent',
                        (isDeactivate ? '' : messages.deleteExplanation)
                            + '<div class="dialogInnerMessage">' + ((forceDeactivate) ? messages[action + 'Continue'] : messages[action + 'Confirm']) + '</div>'
                            + childString).set('buttons', buttons).show();
                };

            if (alreadyLoadedViewsTab.size()) {
                initiateDeactivate.call(this, (alreadyLoadedViewsTab.item(1).get('innerHTML') !== messages.widgetUnusedInView));
            }
            else {
                this.setLock(true);
                dialog.set('bodyContent', waitDiv).show();
                this.getAsyncContent('views', name, name, function(resp) {
                    this.setLock(false);
                    initiateDeactivate.call(this, (resp[0]));
                });
            }
        },
        deactivateOrDelete: function(widgets, callback, isDeactivate) {
            this.setLock(true);
            Y.Helpers.ajax('/ci/admin/versions/' + (isDeactivate ? 'deactivateWidgets' : 'deleteWidget') + '/', {
                method: 'POST',
                data: 'widgets=' + encodeURIComponent(widgets),
                callback: function(resp) {
                    this.setLock(false);
                    callback.call(this, resp);
                },
                context: this
            });
        },
        clickDocumentation: function(e) {
            e.halt();
            var version = Y.Lang.trim(e.target.ancestor('.version').one('h3').getHTML());

            this.showDocumentation(Y.one('#widgetName').getHTML(), version);
        },

        showDocumentation: function(name, version) {
            Y.Helpers.History.add({docs: true, version: version});

            new Y.WidgetDocDialog(name, version, function () {
                Y.Helpers.History.replace({docs: null, version: null});
            });
        },

        clickDependencies: function(e) {
            e.halt();
            var version = Y.Lang.trim(e.target.ancestor('.version').one('h3').get('innerHTML')),
                createWidgetLink = function(widgetName) {
                    return '<a href="/ci/admin/versions/manage/#widget=' + encodeURIComponent(widgetName) + '">' + widgetName + '</a>';
                },
                widgetName = Y.one('#widgetName').getHTML(),
                widgetNameShort = widgetName.split('/').pop(),
                widgetDependencies = this.getDependencies(widgetName, version),
                dialog = Y.Helpers.panel({
                    headerContent: widgetNameShort + ' - ' + version,
                    width: '40%',
                    y: 100
                }),
                bodyContent = Y.Node.create('<div class="widgetDependencies"><h3>' + messages.widgetDependency.replace('%s', widgetNameShort) + '</h3></div>');

            //Iterate through the dependencies 'contains', 'extends', 'children' and list out the widgets and their versions
            Y.Object.each(widgetDependencies, function(items, relationship) {
                if(Y.Object.isEmpty(items)) return;
                var relationshipList = bodyContent.appendChild('<div>')
                                                  .appendChild('<div class="container"><span class="title">' + messages[relationship] + '</span></div>')
                                                  .appendChild('<ul class="relationships">');

                relationshipList.delegate('click', function(){ dialog.hide(); }, 'a');
                Y.Object.each(items, function(dependentVersions, dependentWidget) {
                    //If versions are not specified, just show the widget link
                    var message = (dependentVersions.length === 1 && dependentVersions[0] === 'N/A') ? '%s' : ((dependentVersions.length === 1) ? messages.dependencyVersion : messages.dependencyVersionPlural);
                    message = message.replace('%s', createWidgetLink(dependentWidget))
                                     .replace('%s', (relationship === 'children') ? messages.dependencyProvides : messages.dependencyRequires)
                                     .replace('%s', dependentVersions.join(', '));
                    relationshipList.append('<li>- ' + message + '</li>');
                });
            });

            dialog.set('bodyContent', bodyContent);
            dialog.show();
        }
    });

    Y.WidgetPanel = new WidgetPanel();

}, null, {
    requires: ['node', 'tabview', 'Component', 'Helpers', 'VersionHelper', 'anim', 'VersionPanel', 'VersionUpdater', 'PreviewImageDialog', 'ListFocusManager', 'WidgetDocDialog']
});
