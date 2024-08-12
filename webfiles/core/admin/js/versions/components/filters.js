/**
 * Widget listing filters.
 */
YUI.add('Filters', function(Y) {
    'use strict';

    var Filters = Y.Component({
        init: function() {
            Y.one("#viewOptions").on(
                "click",
                window.hideShowPanel,
                null,
                Y.one("#widgetsFilterDropdown"),
                Y.one("#viewOptions"),
                Y.one("#widgetsFilterDropdown ul a")
            );

            this.listings = Y.all("#widgets .listing-item");

            var selections = Y.all("#widgetsFilterDropdown a.selected").getAttribute("data-filter-value");
            this.selections = {widgetType: selections[0], widgetStatus: selections[1], widgetCategory: selections[2]};
            this.indicators = Y.all("#widgetsFilter .appliedFilter");

            // add categories to filter
            Y.all("#widgetsFilterDropdown > li:last-child a").each(function(node) {
                this.filters.widgetCategory[node.get('text')] = '.' + node.getData('filter-value');
            }, this);
        },
        events: {
            'click #widgetsFilterDropdown a': 'click'
        },
        history: {
            'key':     'filters',
            'tab':     'widgetVersions',
            'handler': 'checkForUrlPreselection'
        },
        filters: {
            widgetType: {
                both:     "",
                custom:   "[data-type='custom']",
                standard: "[data-type='standard']"
            },
            widgetStatus: {
                all:       "",
                inuse:     ".inuse",
                notinuse:  ".disabled",
                outofdate: ".outofdate",
                uptodate:  ".uptodate"
            },
            widgetCategory: {
                any: ""
            }
        },
        checkForUrlPreselection: function(values) {
            if (values && values.indexOf(":") > -1) {
                values = values.split(":");
                if (values[0] && values[0] in this.filters.widgetType) {
                    this.selections.widgetType = values[0];
                }
                if (values[1] && values[1] in this.filters.widgetStatus) {
                    this.selections.widgetStatus = values[1];
                }
                if (values[2] && values[2] in this.filters.widgetCategory) {
                    this.selections.widgetCategory = values[2];
                }
                this.executeFilter();
            }
        },
        click: function(e) {
            var target = e.target,
                filterType = target.getAttribute("data-filter-group"),
                filterValue = target.getAttribute("data-filter-value"),
                filterName = target.get('text');

            if (target.hasClass("selected")) return;

            this.selections[filterType] = function(){
                if(filterType !== 'widgetCategory') {
                    return filterValue;
                }
                if(filterName === 'Any') {
                    filterName = 'any'
                }
                return filterName;
            }();

            this.executeFilter();
        },
        executeFilter: function() {
            this.indicateSelection();

            var selected = Y.all("#widgets .listing-item" + this.filters.widgetType[this.selections.widgetType] + this.filters.widgetStatus[this.selections.widgetStatus] + this.filters.widgetCategory[this.selections.widgetCategory]);

            this.listings.each(function(listingNode) {
                if (selected.indexOf(listingNode) === -1) {
                    listingNode.addClass("hide");
                }
                else {
                    listingNode.removeClass("hide");
                }
            });

            Y.one('#widgets').set('className', 'listing ' + this.selections.widgetType + ' ' + this.selections.widgetStatus + ' ' + this.selections.widgetCategory);
            Y.Helpers.History.addValue('filters', this.selections.widgetType + ':' + this.selections.widgetStatus + ':' + this.selections.widgetCategory);
        },
        indicateSelection: function() {
            var labels = {};

            Y.all("#widgetsFilterDropdown a").removeClass('selected');
            Y.Object.each(this.selections, function(selectionValue, selectionKey) {
                if(selectionKey === 'widgetCategory' && selectionValue !== 'any') {
                    selectionValue = this.filters.widgetCategory[selectionValue].substring(1);
                }

                var node = Y.one("#widgetsFilterDropdown a[data-filter-value='" + selectionValue + "'][data-filter-group='" + selectionKey + "']");
                labels[selectionKey] = node.addClass('selected').get('innerHTML');
            }, this);

            this.indicators.each(function(node) {
                node.one('.value').set('innerHTML', labels[node.getAttribute('data-indicate')]);
            });
        }
    });

    Y.Filters = new Filters();

}, null, {
    requires: ['Helpers', 'Component']
});
