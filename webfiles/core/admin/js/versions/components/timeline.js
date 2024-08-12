/* global allWidgets,messages */

/**
 * Widget version timeline that's displayed
 * for every widget.
 */
YUI.add('Timeline', function(Y) {
    'use strict';

    var Timeline = Y.Component({
        init: function() {
            Y.on('timeline:refresh', this.refreshVersions, this);
            Y.on('timeline:refresh', this.refreshBanners, this);
        },
        events: {
            'click .circle': 'click'
        },
        click: function(e) {
            e.halt();
            var target = e.target,
                version = Y.Lang.trim(target.next('.label').get('innerHTML'));
            Y.all("#tabs h3").some(function(node) {
                if (Y.Lang.trim(node.get("innerHTML")) === version) {
                    node.get("parentNode").scrollIntoView();
                    return true;
                }
            }, this);
        },
        refreshBanners: function(e) {
            var oldVersion = e.oldVersion,
                newVersion = e.newVersion,
                name = e.name,
                isLatestVersion = true,
                currentBanner = Y.one('#details .notice.outofdate');

            //Check if they've moved to the latest version of the widget on this framework
            Y.Array.some(allWidgets[name].versions, function(item) {
                if(item.version > newVersion && (item.framework === null || Y.Array.indexOf(item.framework, window.currentFramework) !== -1)) {
                    return !(isLatestVersion = false);
                }
            });

            // Update the 'out-of-date' banner. If they are on the latest version remove it. Otherwise, add it.
            if(isLatestVersion && currentBanner) {
                currentBanner.remove();
            }
            else if(!isLatestVersion && !currentBanner) {
                Y.one('#details').append(Y.Node.create('<div class="notice outofdate">' + messages.newUpdates + '</div>'));
            }

            //If the widget doesn't have an old version, then it was previously deactivated. Remove the banner.
            if(!oldVersion) {
                Y.one('#details .notice.disabled').remove();
            }
        },
        /**
         * Update the timeline dots and tooltips to correspond with the new version.
         * There are three specific cases that can occur:
         * - From a deactivated state to an activated state. Add classes and tooltips to all later version.
         * - From a current version to a newer version. Remove classes and tooltips from previous dots.
         * - From a current version to an older version. Update classes and tooltips to all later versions.
         */
        refreshVersions: function(e) {
            //There is no need to refresh if there is only one version because there is no timeline
            if(!Y.one('#details .timeline .line')) return;

            var oldVersion = e.oldVersion,
                newVersion = e.newVersion,
                newDot = Y.one('#details .timeline .label[data-version="' + newVersion + '"]'),
                newParent = newDot.get('parentNode').addClass('currentVersion'),
                oldDot, oldValue, newValue, newTitle, current;

            //Update any of the old dots
            if(oldVersion) {
                oldDot = Y.one('#details .timeline .label[data-version="' + oldVersion + '"]');

                // Remove attributes indicating dev-mode from the old dot
                oldValue = oldDot.getAttribute('data-inuse');
                oldValue = oldValue.split(',');
                newValue = [];
                newTitle = [];
                Y.Array.each(oldValue, function(item) {
                    item = Y.Lang.trim(item);
                    if (item !== messages.development) {
                        newValue.push(item);
                        newTitle.push(messages.modeLabels[item]);
                    }
                }, this);
                newValue = newValue.join(', ');
                newTitle = newTitle.join(', ');
                oldDot.setAttribute('data-inuse', newValue)
                      .get('parentNode').setAttribute('title', messages.currentVersion.replace('%s', newTitle))
                      .one('.circle').setAttribute('data-inuse', newValue);

                // If the old dot no longer has any in-use version then remove the tooltip and indicator.
                if (!newValue) {
                    oldDot.get('parentNode').removeClass('currentVersion').removeAttribute('title').removeAttribute('data-tooltip');
                }

                // Update all the dots from the old dot forward
                current = oldDot.get('parentNode');
                while(current = current.next()) {
                    current.removeClass('newerVersion').removeAttribute('title').removeAttribute('data-tooltip');
                }
            }

            // Update / add attributes indicating dev-mode onto the new dot.
            if (!newParent.getAttribute('title') && !newParent.getAttribute('data-tooltip')) {
                Y.Tooltip.bind(newParent);
            }
            newValue = newDot.getAttribute('data-inuse');
            newValue = (newValue) ? newValue.split(',') : [];
            newValue.push(messages.development);
            newTitle = Y.Array.map(newValue, function(item) {
                return messages.modeLabels[Y.Lang.trim(item)];
            });
            newValue = newValue.join(', ');
            newTitle = newTitle.join(', ');
            newDot.setAttribute('data-inuse', newValue)
                .get('parentNode').setAttribute('title', messages.currentVersion.replace('%s', newTitle))
                .one('.circle').addClass('pulse').setAttribute('data-inuse', newValue);

            // Update all of the dots from the newly selected version forward
            current = newParent;
            while(current = current.next()) {
                Y.Tooltip.bind(current);
                if(current.next()) {
                    current.addClass('newerVersion').setAttribute('title', messages.newerVersion);
                }
                else {
                    current.addClass('newestVersion').setAttribute('title', messages.newestVersion);
                }
            }
        }
    });

    Y.Timeline = new Timeline();

}, null, {
    requires: ['Helpers', 'Component', 'Tooltip']
});
