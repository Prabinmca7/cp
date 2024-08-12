/* global messages */

/**
 * Version panel used display version log
 * entries for widgets and frameworks.
 */
YUI.add('VersionPanel', function(Y) {
    'use strict';

    var VersionPanel = Y.Component({
        icons: {
            major: {icon: 'fa fa-exclamation-circle', title: messages.levels.major},
            minor: {icon: 'fa fa-circle', title: messages.levels.minor},
            nano:  {icon: 'fa fa-circle-o', title: messages.levels.nano}
        },

        getChangelogList: function(entries, key) {
            return (entries)
                ? this._createList(entries, this._normalizeKey(key))
                : Y.Node.create('<div>' + messages.noChangelog + '</div>');
        },

        _createList: function (entries, key) {
            var node = Y.Node.create('<div>');

            Y.Object.each(entries, function(items, category) {
                node.append(this._createGroup(items, category, key));
            }, this);

            return node;
        },

        _createGroup: function (entries, category, key) {
            var list = Y.Node.create('<ul class="icons"></ul>');

            Y.Array.each(entries, function (entry, index) {
                list.append(this._createGroupListItem(entry, index, category, key));
            }, this);

            return Y.Node.create('<div class="changelogCategory"><h4>' + messages.categories[category] + '</h4></div>').append(list);
        },

        _createGroupListItem: function (entry, index, category, key) {
            var icon = this.icons[entry.level],
                item = Y.Node.create('<li><span class="screenreader">' + icon.title + '</span><i class="' + icon.icon + '" role="presentation" title="' + icon.title + '"></i>' + Y.MarkdownToHTML(entry.description) + '</li>'),
                details = entry.details;

            if (details) {
                this._addMoreDetails(item, details, this._createDetailsToggleID(key, category, index + 1));
            }
            Y.Tooltip.bind(item.all('[title]'));
            return item;
        },

        _addMoreDetails: function (parent, details, toggleID) {
            parent.append(Y.Node.create('<a href="javascript:void(0);" class="toggleDetails" id="' + toggleID + '">' + messages.more + '</a>'));
            var detailSublist =
            '<ul class="details">' +
                Y.Array.map(details, function(detail) {
                    return '<li>' + Y.MarkdownToHTML(detail) + '</li>';
                }).join('') +
            '</ul>';
            parent.append('<div id="toggleDetails_' + toggleID + '" class="hide">' + detailSublist + '</div>');
        },

        _createDetailsToggleID: function (key, category, index) {
            return [key, category, index].join('_');
        },

        _normalizeKey: function (key) {
            return key.replace(/(\.|\/)/g, '');
        }
    });

    Y.VersionPanel = new VersionPanel();

}, null, {
    requires: ['Helpers', 'Component', 'basic-markdown', 'Tooltip']
});
