YUI().use('node-base', function(Y) {
    'use strict';

    /**
     * Callback to attach as a trigger's click handler to
     * add toggle behavior to a panel.
     *
     *      myTrigger.on('click', hideShowPanel, null, panelNode, myTrigger);
     *
     * @param  {Object} e            click event
     * @param  {Object} panel        Y.Node panel
     * @param  {Object=} focusOnClose Y.Node to focus on when the panel toggles closed
     * @param  {Object=} focusOnOpen Y.Node to focus on when the panel toggles open
     */
    window.hideShowPanel = function(e, panel, focusOnClose, focusOnOpen) {
        if (e === false) {
            panel.addClass("hide");
            if (focusOnClose && focusOnClose.focus) {
                focusOnClose.focus();
            }
            return;
        }
        panel.toggleClass("hide");
        if (!panel.hasClass("hide")) {
            e.halt();

            if (focusOnOpen && focusOnOpen.focus) {
                focusOnOpen.focus();
            }

            Y.one(document.body).once("click", function() {
                window.hideShowPanel(false, panel, focusOnClose);
            });
        }
    };
    window.toggle = function(elementOrID) {
        Y.all(elementOrID).toggleClass("hide");
    };

    if(!('placeholder' in document.createElement('input'))) {
        //fallback for non-native placeholder support
        var labelVal = Y.one('#widgetInputLabel').getHTML(),
            widgetSearch = Y.one('#widgetInput'),
            setVal = function(e, compare) {
                if (e.target.get('value') === compare[0]) {
                    e.target.set('value', compare[1]);
                }
            };
        widgetSearch.set('value', labelVal)
                    .on('focus', setVal, null, [labelVal, '']);
        widgetSearch.on('blur', setVal, null, ['', labelVal]);
    }

    Y.one("#viewSite button").on("click", window.hideShowPanel, null, Y.one("#viewSiteDropdown"));

    var langSelect = Y.one("#langSelect button");
    if (langSelect) {
        langSelect.on("click", window.hideShowPanel, null, Y.one("#langSelectDropdown"));
        Y.one("#langSelectDropdown").on('click', function(e) {
            window.open('/ci/admin/overview/setLanguage/' +
                encodeURIComponent(e.target.getAttribute('data-intf')) + '/' +
                e.target.getAttribute('data-lang') + '/' +
                encodeURIComponent(window.location.pathname + window.location.hash), '_self');
        });
    }

    Y.one('#sitetitle').focus();
});

YUI().use('node-menunav', 'node-base', 'transition', 'event-mouseenter', function(Y) {
    'use strict';

    // For smaller viewports, allow elements to overflow out of the 35px-height nav container
    // but provide a control to expand the nav menu to show those overflowed elements. Dropdown
    // menus are shown as always-displayed, full-width list-items in this expanded mode.
    function AdaptableMenu () {
        this.nav = Y.one('nav');
        this.menuButton = Y.one('#toggleMenu');
        this.collapsedHeight = this.nav.getComputedStyle('height');
        this.collapsibleItems = this.nav.all('> .collapsible, #navmenu > .yui3-menu-content > ul > li');
        this.widthWhenExpanded = null;

        this.initMenuNav();
        this.initDropdownHandler();

        this.adaptMenu();
        Y.Lang.later(300, this, this.adaptMenu, null, true);
    }
    AdaptableMenu.prototype = {
        isExpanded: function () {
            return this.nav.hasClass('expanded');
        },

        initMenuNav: function () {
            this.nav.one('#navmenu').plug(Y.Plugin.NodeMenuNav, { submenuHideDelay: 500, submenuShowDelay: 0 });
            this.menuButton.on('click', function () { this.toggleNavState(); }, this);
        },

        initDropdownHandler: function () {
            // Top-level tabs with dropdown menus.
            var dropdowns = this.nav.all('#navmenu > .yui3-menu-content > ul > li > a.yui3-menu-label');
            dropdowns.on('mouseenter', this.onDropdownTrigger, this);
            dropdowns.on('focus', this.onDropdownTrigger, this);
        },

        // For any top-level, non-overflowed tabs with dropdowns, when those dropdowns are triggered,
        // hide the expanded nav menu.
        onDropdownTrigger: function (e) {
            if (this.isExpanded() && !e.currentTarget.ancestor('.overflowed')) {
                Y.Lang.later(200, this, function (dropdownForLink) {
                    !dropdownForLink.hasClass('.yui3-menu-hidden') && this.toggleNavState(true);
                }, e.currentTarget.next('.yui3-menu'));
            }
        },

        adaptMenu: function () {
            if (this.isExpanded() && this.widthWhenExpanded
                && this.nav.get('offsetWidth') !== this.widthWhenExpanded) {
                // Collapse the menu if the window's being resized.
                return this.toggleNavState(true);
            }

            this.overflowed = false;
            this.collapsibleItems.each(this.adaptCollapsibleItem, this);
            this.menuButton.toggleClass('hide', !this.overflowed);
        },

        adaptCollapsibleItem: function (node) {
            var navTop = this.nav.getY() + 10;

            if (node.getY() > navTop) {
                // node has overflowed to a new line.
                node.addClass('overflowed');
                if (!this.overflowed && node.previous() !== this.menuButton) {
                    // Insert the menu button after the last non-overflowed node.
                    node.previous().insert(this.menuButton, 'after');
                }
                this.overflowed = true;
            }
            else {
                node.removeClass('overflowed');
            }
        },

        toggleNavState: function (collapse) {
            var newHeight,
                classNames = [ 'collapsed', 'expanded' ],
                overflowed = this.nav.all('.overflowed');

            if (!collapse && !this.isExpanded()) {
                // Expand
                var lowest = overflowed.toggleClass('visible').slice(-1).item(0);
                newHeight = lowest.get('offsetHeight') + lowest.getY() + 'px';
                this.widthWhenExpanded = this.nav.get('offsetWidth');
            }
            else {
                // Collapse
                newHeight = this.collapsedHeight;
                classNames.reverse();
                this.widthWhenExpanded = null;
            }

            this.nav.transition({
                height:   newHeight,
                duration: 0.3
            }, function () {
                if (classNames[0] === 'expanded') {
                    // Turn off the visibility of overflowing items after the
                    // collapse animation completes otherwise you can see elements
                    // disappearing as the nav rolls back up.
                    overflowed.toggleClass('visible');
                }
                this.replaceClass(classNames[0], classNames[1]);
            });
        }
    };

    new AdaptableMenu();
});

YUI().use('node-base', 'autocomplete', 'autocomplete-highlighters', 'array-extras', function(Y) {
    'use strict';

    function Autocomplete (inputElement, dataSource) {
        this.input = inputElement;
        this.dataSource = dataSource;
        this.initAutocomplete();
    }
    Autocomplete.resultFilters = function (query, results) {
        //Get all results that contain the phrases
        var initialResults = Y.Array.filter(results, function(result) {
            return result.text.toLowerCase().indexOf(query.toLowerCase()) !== -1;
        });
        //Reorder the results to place the earliest phrase occurrence first in the list
        //(e.g. TextInput will appear before BasicTextInput)
        return initialResults.sort(function(first, second) {
            var firstPosition = first.text.toLowerCase().indexOf(query.toLowerCase()),
                secondPosition = second.text.toLowerCase().indexOf(query.toLowerCase());

            return (firstPosition === secondPosition) ? 0 : (firstPosition < secondPosition) ? -1 : 1;
        });
    };
    Autocomplete.prototype = {
        resultTemplate:     "<div class='richResult'>{name} <small>({type})</small><span><a class='doc' href='{link}&docs=true&tab=0'>{docLabel}</a><a class='version' href='{link}'>{versionLabel}</a></span></div>",
        widgetLink:         '/ci/admin/versions/manage/#widget=',
        probablyAWidget:    /.*\/.*\/.*/,
        searched:           null,
        initAutocomplete: function () {
            this.input.plug(Y.Plugin.AutoComplete, {
                maxResults:         5,
                activateFirstItem:  true,
                resultHighlighter:  'phraseMatch',
                resultFilters:      Autocomplete.resultFilters,
                resultFormatter:    Y.bind(this.resultFormatter, this),
                source:             this.dataSource.folderSearchList,
                width:              'auto'
            });
            this.input.ac.on('select', Y.bind(this.resultSelected, this));
            this.input.ac.on('query', Y.bind(this.queryPerformed, this));
        },
        resultFormatter: function (query, results) {
            return Y.Array.map(results, function (item) {
                if (this.probablyAWidget.test(item.raw)) {
                    var segments = item.raw.split('/');

                    return Y.Lang.sub(this.resultTemplate, {
                        type:           segments[0],
                        name:           segments[segments.length - 1],
                        link:           this.widgetLink + item.raw,
                        docLabel:       this.dataSource.labels.docs,
                        versionLabel:   this.dataSource.labels.versions
                    });
                }
                return item.raw;
            }, this);
        },
        resultSelected: function (e) {
            e.preventDefault();
            this.searched = e.result.text;
            this.input.set('value', '').blur();
            window.location = ((this.probablyAWidget.test(this.searched))
                ? this.widgetLink
                : "/ci/admin/docs/widgets/") + this.searched;
        },
        // Hide the list when something's been selected
        queryPerformed: function (e) {
            if (e.query === this.searched) {
                this.searched = null;
                this.input.ac.hide();
                e.halt();
            }
        }
    };
    new Autocomplete(Y.one('#widgetInput'), window.autoCompleteData);
});
