/**
 * Creates a visual product or category ("item") chooser intended to help drill
 * down into the desired item's landing page.
 * @type {Object}
 */
RightNow.Widgets.VisualProductCategorySelector = RightNow.Widgets.extend({
    /**
     * Renders the group of base items supplied in `data.js.items` and sets up event handlers.
     *
     * @constructor
     */
    constructor: function() {
        this.widgetElement = this.Y.one(this.baseSelector);
        this.paginationNode = this.widgetElement.one('.rn_ItemPagination');

        // Keeps track of the current hierarchy level we're currently at.
        this.currentLevel = 0;
         // Keeps track of info needed to navigate up and down the hierarchy of items.
        this.itemLevels = [];
        // Contains prefetched sub-items. Keys are parent item ids.
        if (this.data.attrs.prefetch_sub_items_non_ajax && !this.data.attrs.prefetch_sub_items) {
            this.prefetched = this.data.js.subItems;
        }
        else {
            this.prefetched = {};
        }

        if (this.widgetElement) {
            this._displayInitialItemSet(this.data.js.items);

            this._attachEventHandlers(this.widgetElement, [
                ['click', 'a.rn_ShowChildren', this._onShowChildrenClick],
                ['click', 'a.rn_BreadCrumbLink', this._goToPreviousLevel],
                ['click', 'a.rn_ForwardPage', this._goForwardPage],
                ['click', 'a.rn_PreviousPage', this._goPreviousPage]
            ]);
        }
    },

    /**
     * Simulates node.one('.rn_ItemGroup:not(.rn_Hidden)') without needing the selector-css3 module
     *
     * @param  {Object} nodeList A list of notes, probably from a .all call or similar
     */
    _getVisibleNodes: function(nodeList) {
        return nodeList.filter(function(node) { return node.className.search(/rn_Hidden/g) === -1; });
    },

    /**
     * Click handler when a 'show more of this item' link is clicked.
     *
     * @param {Object} e Click event
     */
    _onShowChildrenClick: function(e) {
        e.halt();

        var target = e.target,
            id = parseInt(target.getAttribute('data-id'), 10),
            el = target.ancestor('.rn_ItemGroup'),
            label = this.Y.Lang.trim(target.ancestor('.rn_VisualItemContainer > .rn_ActionContainer').one('.rn_ItemLink').get('text').replace(/(<([^>]+)>)/ig,""));

        if (!isNaN(id)) {
            this._showChildren({ id: id, label: label, el: el });
        }
    },

    /**
     * Shows the children item group for the given item id, or initiates a request to retrieve them
     * if they aren't already loaded.
     *
     * @param {Object} parent Hash that must contain the following properties:
     *    - id: {Number} Parent's item id
     *    - el: {Object} YUI Parent Node
     *    - label: {String} Parent item's label
     */
    _showChildren: function(parent) {
        var subItemsAlreadyRendered = this._showSubItemsFor(parent.id);

        if (subItemsAlreadyRendered) {
            this._setCurrentItemLevel(parent);
            this._updateBreadCrumb(this.currentLevel, this.itemLevels);
        }
        else if (parent.id in this.prefetched) {
            // Group of items was fetched to retrieve sub-item count but never rendered.
            this._setCurrentItemLevel(parent);
            this._insertNewGroupAtCurrentLevel(this.prefetched[parent.id]);
        }
        else if (this.requestChildren(parent.id, this._childrenResponse)) {
            this._toggleLoading(true);
            this._setCurrentItemLevel(parent);
        }
    },

    /**
     * Makes the AJAX request to get the group of sub-items for a given item id.
     *
     * @param {Number} id Item id
     * @param {Function} callback Success callback
     * @return {Boolean} Whether the request was actually made
     */
    requestChildren: function(id, callback) {
        var eo = new RightNow.Event.EventObject(this, { data: {
            id:      id,
            filter:  this.data.attrs.type,
            linking: false
        }});

        if (!RightNow.Event.fire('evt_ItemRequest', eo)) return false;

        RightNow.Ajax.makeRequest(this.data.attrs.sub_item_ajax, eo.data, {
            json:           true,
            scope:          this,
            data:           eo,
            successHandler: callback
        });

        return true;
    },

    /**
     * Callback for response from server for sub-items.
     *
     * @param {Object} response Response object from server
     * @param {Object} origEventObject Original event object from the request
     */
    _childrenResponse: function(response, origEventObject) {
        if (!response || !RightNow.Event.fire('evt_ItemResponse', { data: origEventObject, response: response })) return;
        this._insertNewGroupAtCurrentLevel(response.result[0]);
    },

    /**
     * Inserts the given item group.
     * @param  {Array} items List of new items to insert
     */
    _insertNewGroupAtCurrentLevel: function (items) {
        var previousLevel = this.itemLevels[this.currentLevel - 1] || {};

        this._showNewGroup(items, this.Y.mix(previousLevel, { level: this.currentLevel + 1, focusOnFirst: true }));
    },

    /**
     * Does all the various updating to display a new group of items.
     *
     * @param {Array} items List of new items to insert and show
     * @param {Object} options Hash containing the following keys:
     *    - id: {Number|String} Parent id for the sub-group or 'Base' for the top-level group
     *    - level: {Number} Level of the group
     *    - focusOnFirst: {Boolean} Whether or not to focus on the first item
     *    - el: {Object=} Node that's the currently-showing group of items to hide
     *    - direction: {String=} Direction for which to navigate
     *    - isInitialLoad: {Boolean=} Whether or not this is happening on the page's intial load
     */
    _showNewGroup: function(items, options) {
        if (items.length > this.data.attrs.maximum_items) {
            items = items.slice(0, this.data.attrs.maximum_items);
        }

        var newItemGroup = this._renderNewItemGroup(items, options.level, options.id);

        this._hide(this.widgetElement.all('.rn_ItemGroup'));
        this.widgetElement.one('.rn_Items').append(newItemGroup);
        this._replaceBrokenImages(newItemGroup);
        this._normalizeDescriptionHeights(newItemGroup);
        this._setPaginationVisibility(newItemGroup);
        this._updateBreadCrumb(this.currentLevel, this.itemLevels);

        if(this.data.attrs.per_page > 0) {
            this._paginateItems(newItemGroup, options);
        }
        if (this.data.attrs.prefetch_sub_items && !this.data.attrs.prefetch_sub_items_non_ajax) {
            this._prefetchSubItems(items);
        }
        else if (this.data.attrs.prefetch_sub_items_non_ajax && !this.data.attrs.prefetch_sub_items) {
            if (options.level > 1) {
                this._prefetchSubItems(items);
            }
            else {
                this._setShowMoreNumber(this.prefetched);
            }
        }
        else if (this.data.attrs.prefetch_sub_items_non_ajax && this.data.attrs.prefetch_sub_items) {
            RightNow.UI.displayBanner(this.data.attrs.label_error, { type: 'ERROR'});
        }
        this._toggleLoading(false);
        this._updateAriaAlert(this.data.attrs.label_screen_reader_new_results);

        if (options.focusOnFirst) {
            this._focusFirstLink(newItemGroup);
        }
    },

    /**
     * Sets the given item in the `itemLevels` property that's used for rendering the
     * breadcrumb levels and increments the `currentLevel` property.
     *
     * @param {Object} parent Hash that must contain the following properties:
     *    - id: {Number} Parent's item id
     *    - el: {Object} YUI Parent Node
     *    - label: {String} Parent item's label
     */
    _setCurrentItemLevel: function(item) {
        this.itemLevels[this.currentLevel++] = {
            id:    item.id,
            el:    item.el,
            label: item.label
        };
    },

    /**
     * Sets the number of sub items on the show more link. Used when fetching the sub items for the 
     * initial items without an AJAX request
     * @param {Object} items that have sub items
     */
    _setShowMoreNumber: function(items) {
        this.Y.Object.each(items, function (items, parentID) {
            if (showMoreLink = this.Y.one(this.baseSelector + ' .rn_ShowChildren[data-id="' + parentID + '"]')) {
                showMoreLink.set('text', RightNow.Text.sprintf(this.data.attrs.label_prefetched_sub_items, this.prefetched[parentID].length));
            }
        }, this);
    },

    /**
     * Get current page for pagination
     *
     * @param {Number} itemsPerPage Number of items per page
     * @param {Int} items NodeList object representing a collection of items (items)
     */
    _getCurrentPageNumber: function(itemsPerPage, items) {
        var itemGroupPass = 0, pageNumber;
        items.some(function(node, index) {
            if(index % itemsPerPage === 0) {
                ++itemGroupPass;
                if(!node.hasClass('rn_Hidden')) {
                    pageNumber = itemGroupPass;
                    return true;
                }
            }
        });
        return pageNumber;
    },

    /**
     * Show or hide items for/during pagination
     *
     * @param {Int} pageNumber Page navigating to
     * @param {Number} itemsPerPage Number of items per page
     * @param {Int} items NodeList object representing a collection of items (items)
     */
    _hideShowItemsForPagination: function(pageNumber, itemsPerPage, items) {
        var visibleItemUpperIndex = (itemsPerPage * pageNumber) - 1,
            visibleItemLowerIndex = visibleItemUpperIndex - itemsPerPage;

        items.each(function(node, index) {
            if(index > visibleItemLowerIndex && index <= visibleItemUpperIndex) {
                node.removeClass('rn_Hidden');
            }
            else {
                node.addClass('rn_Hidden');
            }
        });
    },

    /**
     * Set disabled status class for default pagination
     *
     * @param {Int} pageNumber Page navigated/navigating to
     * @param {Number} itemsPerPage Number of items per page
     * @param {Number} itemsCount Count of items in total pagination set
     * @param {Object} container Node object representing a collection of items (items)
     */
    _setButtonDisabledClass: function(pageNumber, itemsPerPage, itemsCount, container) {
        var forwardButton = this.Y.one(this.baseSelector + ' a.rn_ForwardPage'),
            forwardButtonSpan = forwardButton.one('span.rn_ScreenReaderOnly'),
            previousButton = this.Y.one(this.baseSelector + ' a.rn_PreviousPage'),
            previousButtonSpan = previousButton.one('span.rn_ScreenReaderOnly'),
            attrs = this.data.attrs;

        if(pageNumber === Math.ceil(itemsCount / itemsPerPage)) {
            if(attrs.numbered_pagination) {
                forwardButton.addClass('rn_Hidden');
            }
            else {
                forwardButton.addClass('rn_Disabled').setAttribute('tabIndex', '-1');
                forwardButtonSpan.setContent(attrs.label_screen_reader_forward_page_disabled);
            }
        }
        else {
            if(attrs.numbered_pagination) {
                forwardButton.removeClass('rn_Hidden');
            }
            else {
                forwardButton.removeClass('rn_Disabled').removeAttribute('tabIndex');
                forwardButtonSpan.setContent(attrs.label_screen_reader_forward_page);
            }
        }

        if(pageNumber === 1) {
            if(attrs.numbered_pagination) {
                previousButton.addClass('rn_Hidden');
            }
            else {
                previousButton.addClass('rn_Disabled').setAttribute('tabIndex', '-1');
                previousButtonSpan.setContent(attrs.label_screen_reader_previous_page_disabled);
            }
        }
        else {
            if(attrs.numbered_pagination) {
                previousButton.removeClass('rn_Hidden');
            }
            else {
                previousButton.removeClass('rn_Disabled').removeAttribute('tabIndex');
                previousButtonSpan.setContent(attrs.label_screen_reader_previous_page);
            }
        }

        if(attrs.numbered_pagination) {
            this._renderPagedPagination(pageNumber, itemsPerPage, itemsCount, forwardButton);
        }
    },

    /**
     * Render the paged pagination section
     *
     * @param {Int} pageNumber Page navigated/navigating to
     * @param {Number} itemsPerPage Number of items per page
     * @param {Number} itemsCount Count of items in total pagination set
     * @param {Object} forwardButton Node object representing the pagination's forward button
     */
    _renderPagedPagination: function(pageNumber, itemsPerPage, itemsCount, forwardButton) {
        var pageCount = Math.ceil(itemsCount / itemsPerPage),
            forwardButtonListItem = forwardButton.ancestor();

        if(pageCount > 1) {
            var stalePageLinks = this.Y.all(this.baseSelector + ' .rn_ItemPagination li:not(:first-child):not(:last-child)');
            if(stalePageLinks.size() > 0) {
                stalePageLinks.remove(true);
            }

            for (var i = 1; i < pageCount + 1; i++) {
                var pageLinkListItem = this.Y.Node.create('<li><a href="javascript:void(0);">' + i + '</a></li>'),
                    pageLink = pageLinkListItem.one('a'),
                    pageTitle = RightNow.Text.sprintf(this.data.attrs.label_page, pageNumber, pageCount);

                pageLink.setAttribute('title', pageTitle);
                pageLink.setAttribute('aria-label', pageTitle);

                if(i === pageNumber) {
                    pageLink.addClass('rn_CurrentPage');
                }
                else {
                    pageLink.on('click', this._goSpecificPage, this)
                }

                forwardButtonListItem.insertBefore(pageLinkListItem, forwardButtonListItem);
            }
        }
    },

    /**
     * Sets up pagination
     *
     * @param {Object} container Node object representing a collection of items (items)
     * @param {Object=} options Options for pagination containing the following keys:
     *    - direction: {String=} Direction of pagination - either "forward" or "previous",
     *      or the page number for which to navigate
     *    - isInitialLoad: {Boolean=} Whether or not this is happening on the page's intial load
     */
    _paginateItems: function(container, options) {
        var itemsPerPage = this.data.attrs.per_page,
            items = container.all('.rn_Item'),
            itemsCount = items.size();

        if(itemsCount <= itemsPerPage) {
            return;
        }

        var pageNumber = this._getCurrentPageNumber(itemsPerPage, items),
            directionOrPage = options.direction;
        if(directionOrPage) {
            if(!isNaN(directionOrPage)) {
                pageNumber = directionOrPage;
            }
            else {
                (directionOrPage === 'forward') ? pageNumber++ : pageNumber--;
            }
        }

        this._hideShowItemsForPagination(pageNumber, itemsPerPage, items);
        if(!options.isInitialLoad) {
            this._focusFirstLink(this._getVisibleNodes(items).item(0));
        }
        this._setButtonDisabledClass(pageNumber, itemsPerPage, itemsCount, container);
        this.widgetElement.one('.rn_ItemPagination').removeClass('.rn_Hidden');
    },

    /**
     * Goes back to a previously-displayed hierarchy level.
     * The click target is expected to have legit `data-id` and `data-level` attributes.
     *
     * @param {Object} e Click event
     */
    _goToPreviousLevel: function(e) {
        var id = parseInt(e.target.getAttribute('data-id'), 10);

        if (!this._showSubItemsFor(id)) {
            this._showChildren({ id: isNaN(id) ? this.itemLevels[0].id : id });
        }

        this.currentLevel = parseInt(e.target.getAttribute('data-level'), 10);

        if (!this.data.attrs.label_breadcrumb) {
            // Level is off-by-one if there's no top-level title being displayed.
            this.currentLevel++;
        }

        this._updateBreadCrumb(this.currentLevel, this.itemLevels);
    },


    /**
     * Paginates - goes "previous" or "forward" in a paginated item set.
     *
     * @param {Object} e Click event
     * @param {String} direction Direction to navigate
     */
    _paginateItemSet: function(e, direction) {
        e.halt();
        if(!e.currentTarget.hasClass('rn_Disabled')) {
            var itemGroupContainer = this._getVisibleNodes(this.widgetElement.all('.rn_ItemGroup')).item(0);
            this._paginateItems(itemGroupContainer, { direction: direction });
        }
    },

    /**
     * Goes "back" in a paginated item set.
     *
     * @param {Object} e Click event
     */
    _goPreviousPage: function(e) {
        this._paginateItemSet(e, 'previous');
    },

    /**
     * Goes "forward" in a paginated item set.
     *
     * @param {Object} e Click event
     */
    _goForwardPage: function(e) {
        this._paginateItemSet(e, 'forward');
    },

    /**
     * Goes to the specific page based on the link which is clicked. Used in page pagination,
     * in which the links text value is a number.
     *
     * @param {Object} e Click event
     */
    _goSpecificPage: function(e) {
        this._paginateItemSet(e, parseInt(e.target.get('text'), 10));
    },

    /**
     * Shows / hides the loading indicator and aria-busy status.
     *
     * @param  {Boolean} show T to show the loading indicator, F to hide it
     */
    _toggleLoading: function(show) {
        if (!this._loadingIndicator) {
            this._loadingIndicator = this.Y.Node.create('<div class="rn_Loading rn_Hidden"></div>');
            this.widgetElement.one('.rn_Items').prepend(this._loadingIndicator);
        }

        document.body.setAttribute('aria-busy', show + '');
        this._loadingIndicator.toggleClass('rn_Hidden', !show);
    },

    /**
     * Displays the initial set of items.
     *
     * @param {array} items Items to display for a single level
     * @param {array=} currentBranch An array where each item represents a
     *    parent item hierarchy level, including the current level
     */
    _displayInitialItemSet: function(items) {
        var id,
            level = 0;

        this._toggleLoading(true);

        this._showNewGroup(items, { id: id, level: ++level, focusOnFirst: this.data.attrs.initial_focus, isInitialLoad: true });
    },

    /**
     * Given a item id, looks for the sub-group of items for it in the DOM.
     * If it exists, it's shown, otherwise False is returned.
     *
     * @param {Number|Object} id Number item id or Object YUI node
     * @return {Boolean} True if the Node was found and shown, false otherwise
     */
    _showSubItemsFor: function(id) {
        var existing = (typeof id === 'number') ?
                this.widgetElement.one(this.baseSelector + '_' + (id || 'Base') + '_SubItems') :
                id;

        if (existing) {
            this.widgetElement.all('.rn_ItemGroup').addClass('rn_Hidden');
            this._setPaginationVisibility(existing);
            this._focusFirstLink(existing.removeClass('rn_Hidden'));

            return true;
        }

        return false;
    },

    /**
     * Retrieves the children for every parent item in the supplied set.
     *
     * @param  {Array} items A level of items
     */
    _prefetchSubItems: function (items) {
        var ids = [];
        this.Y.Array.each(items, function (item) {
            if (item.hasChildren) {
                ids.push(item.id);
            }
        }, this);

        var eo = new RightNow.Event.EventObject(this, { data: {
            items: ids.join(),
            filter:  this.data.attrs.type,
            linking: false
        }});

        if (!RightNow.Event.fire('evt_ItemRequest', eo)) return false;

        RightNow.Ajax.makeRequest(this.data.attrs.prefetch_ajax, eo.data, {
            json:           true,
            scope:          this,
            data:           eo,
            successHandler: this._prefetchedSubItemsReceived
        });

        return true;
    },

    /**
     * Callback for prefetched sub-items.
     *
     * @param {Object} response Response object
     */
    _prefetchedSubItemsReceived: function (response) {
        var items = ((response && response.result) ? response.result : {}),
            showMoreLink, children;

        this.Y.Object.each(items, function (result, parentID) {
            if (children = result[0]) {
                this.prefetched[parentID] = children;
                if (showMoreLink = this.Y.one(this.baseSelector + ' .rn_ShowChildren[data-id="' + parentID + '"]')) {
                    showMoreLink.set('text', RightNow.Text.sprintf(this.data.attrs.label_prefetched_sub_items, children.length));
                }
            }
        }, this);
    },

    /**
     * Renders the new group of items.
     *
     * @param {Array} items List of items
     * @param {Number} level The level of items being displayed
     * @param {Number=} parentID Parent item id; if not specified, 'Base' is used
     * @return {Object} Created node
     */
    _renderNewItemGroup: function(items, level, parentID) {
        parentID || (parentID = 'Base');
        var attrs = this.data.attrs;
        this._viewTemplate || (this._viewTemplate = new EJS({ text: this.getStatic().templates.view }));

        return this.Y.Node.create('<div class="rn_ItemGroup"></div>')
            .addClass('rn_ItemLevel' + level)
            .addClass(parentID === 'Base' ? 'rn_BaseGroup' : 'rn_SubGroup')
            .addClass('rn_Item_' + parentID + '_SubItems')
            .set('id', this.baseDomID + '_' + parentID + '_SubItems')
            .setHTML(
                this._viewTemplate.render({
                    url:           attrs.landing_page_url + this.data.js.appendedParameters,
                    items:         items,
                    imageBase:     attrs.image_path,
                    hierarchyBase: attrs.type.substring(0, 1),
                    showMoreLabel: attrs.label_show_sub_items,
                    escapeHtml: this.Y.Escape.html
                })
            );
    },

    /**
     * Updates the sub-item breadcrumb. Hides the back link if level is 0.
     * Also hides the breadcrumb area if level is 0 and there is no top-level
     * label (label_breadcrumb attribute).
     *
     * @param {Number} Current level being shown
     * @param {Array} Current Item hierarchy levels
     */
    _updateBreadCrumb: function(level, itemLevels) {
        var toUpdate = [this.widgetElement.one('.rn_NavigationArea')],
            topLevelTitle = this.data.attrs.label_breadcrumb,
            levels;

        if (level === 0) {
            if (topLevelTitle) {
                this._hide(toUpdate[1]);
                this._show(toUpdate[0]);
            }
            else {
                return this._hide(toUpdate);
            }
        }
        else {
            this._show(toUpdate);
        }

        if (!this.data.attrs.display_breadcrumbs) return;

        if (topLevelTitle) {
            levels = [{
                id:    'Base',
                label: topLevelTitle
            }].concat(itemLevels);
            level++;
        }
        else {
            levels = itemLevels;
        }

        toUpdate[0].one('.rn_BreadCrumb').setHTML(this._renderBreadCrumb(level, levels));
    },

    /**
     * Renders the breadcrumb view.
     *
     * @param {Number} Current level being shown
     * @param {Array} Current Item hierarchy levels
     * @return {String} rendered view
     */
    _renderBreadCrumb: function(level, itemLevels) {
        this._breadCrumbView || (this._breadCrumbView = new EJS({ text: this.getStatic().templates.breadcrumb }));

        return this._breadCrumbView.render({
            levels:                 itemLevels,
            currentLevel:           level,
            disallowBackNavigation: this.data.attrs.limit_sub_items_branch && this.data.attrs.show_sub_items_for
        });
    },

    /**
     * Sets up delegate event handlers on element.
     *
     * @param {Object} element Node to attach event handlers onto
     * @param {Array} events List of events; each item should be an array:
     *    `['dom event name', 'css selector', callback function]`
     */
    _attachEventHandlers: function(element, events) {
        this.Y.Array.each(events, function(evt) {
            element.delegate(evt[0], evt[2], evt[1], this);
        }, this);
    },

    /**
     * Sets the `onerror` handler for all the images found within container to
     * set the image src to a default image if the current src results in a 404.
     * Since the `onerror` handler is set, this assumes that the images haven't
     * started attempting to download, so this should be called soon after container
     * is injected into the DOM and shown.
     *
     * @param {Object} container YUI node
     */
    _replaceBrokenImages: function(container) {
        var placeholder = this.data.attrs.image_path + '/default.png',
            replacer = function() {
                this.set('src', placeholder);
            };

        container.all('img').each(function(img) {
            img.once('error', replacer);
        });
    },

    /**
     * Sets the height for each description to the tallest.
     *
     * @param {Object} container YUI node
     */
    _normalizeDescriptionHeights: function(container) {
        var actionContainerHeight,
            tallestActionContainerHeight = 0;

        container.all('.rn_ActionContainer').each(function(el) {
            actionContainerHeight = el.get('clientHeight');
            if(actionContainerHeight > tallestActionContainerHeight)
                tallestActionContainerHeight = actionContainerHeight;
        });

        container.all('.rn_ActionContainer').each(function(el) {
            el.setStyle('height', tallestActionContainerHeight + 'px');
        });
    },

    /**
     * Hides or shows pagination buttons, when pagination is enabled,
     * depending on how many items are visible.
     *
     * @param {Object} container YUI node
     */
    _setPaginationVisibility: function(container) {
        if(this.paginationNode) {
            if(container.all('.rn_Item').size() > this.data.attrs.per_page) {
                this.paginationNode.removeClass('rn_Hidden');
            }
            else {
                this.paginationNode.addClass('rn_Hidden');
            }
        }
    },

    /**
     * Updates the text for the ARIA alert div that appears above the results listings.
     *
     * @param {String} text The text to update the div with
     */
    _updateAriaAlert: function(text) {
        if (!text) return;

        this._ariaAlert = this._ariaAlert || this.Y.one(this.baseSelector + "_Alert");

        if(this._ariaAlert) {
            this._ariaAlert.setHTML(text);
        }
    },

    /**
     * Focuses the first link in the node (if there is one).
     *
     * @param {Object} node YUI Node
     */
    _focusFirstLink: function(node) {
        var firstLink = node.one('a');
        if (firstLink) {
            firstLink.focus();
        }
    },

    /**
     * We  would call `RightNow.UI.hide`, but in IE, it's called before that namespace is ready.
     *
     * @param  {Object} toHide Node to hide
     */
    _hide: function(toHide) {
        if (RightNow.UI && RightNow.UI.hide) return RightNow.UI.hide(toHide);
        (new this.Y.NodeList(toHide)).addClass('rn_Hidden');
    },

    /**
     * We would call `RightNow.UI.show`, but in IE, it's called before that namespace is ready.
     *
     * @param  {Object} toShow Node to show
     */
    _show: function(toShow) {
        if (RightNow.UI && RightNow.UI.show) return RightNow.UI.show(toShow);
        (new this.Y.NodeList(toShow)).removeClass('rn_Hidden');
    }
});
