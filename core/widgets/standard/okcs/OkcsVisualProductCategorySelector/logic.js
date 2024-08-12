RightNow.Widgets.OkcsVisualProductCategorySelector = RightNow.Widgets.VisualProductCategorySelector.extend({
    overrides:{
            constructor: function() {    
            this.parent();
            this.prefetched = this.data.js.subItems;
            this._setShowMoreNumber(this.prefetched);
        },
        
        /**
         * Click handler when a 'show more of this item' link is clicked.
         *
         * @param {Object} e Click event
         */
        _onShowChildrenClick: function(e) {
            e.halt();

            var target = e.target,
                id = target.getAttribute('data-id'),
                el = target.ancestor('.rn_ItemGroup'),
                label = this.Y.Lang.trim(target.ancestor('.rn_VisualItemContainer > .rn_ActionContainer').one('.rn_ItemLink').get('text'));

                this._showChildren({ id: id, label: label, el: el });
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
        _showChildren: function(parentCategory) {
            var subItemsAlreadyRendered = this._showSubItemsFor(parentCategory.id);
            
            if (subItemsAlreadyRendered) {
                this._setCurrentItemLevel(parentCategory);
                this._updateBreadCrumb(this.currentLevel, this.itemLevels);
            }
            else {
                var eventObject = new RightNow.Event.EventObject(this, {data: { categoryId: parentCategory.id, limit: this.data.js.limit, offset: 0}});
                RightNow.Ajax.makeRequest(this.data.attrs.sub_item_ajax, eventObject.data, {
                    successHandler: function(response, args){
                        var children = [];
                        response = response.items;
                        for (var key in response) {
                            children.push({"id": response[key].referenceKey, "label": response[key].name, "hasChildren":response[key].hasChildren, "childrenCount": response[key].childrenCount, "extId": response[key].externalId});
                            this.prefetched[response[key].referenceKey] = response[key].childrenCount;
                        }
                        this._childrenResponse({"result":Array(children)});
                        this._setShowMoreNumber(this.prefetched);
                    },
                    json: true,
                    scope: this
                });
                
                this._toggleLoading(true);
                this._setCurrentItemLevel(parentCategory);
            }
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

            this._toggleLoading(false);
            this._updateAriaAlert(this.data.attrs.label_screen_reader_new_results);

            if (options.focusOnFirst) {
                this._focusFirstLink(newItemGroup);
            }
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
            this._viewTemplate || (this._viewTemplate = new EJS({ text: this.getStatic().templates.okcsView }));

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
                        hierarchyBase: 'categoryRecordID',
                        showMoreLabel: attrs.label_show_sub_items,
                        escapeHtml: this.Y.Escape.html,
                        prodParam: attrs.type.toUpperCase() === 'CATEGORY' ? '/c/' : '/p/'
                    })
                );
        },
        
        /**
         * Given a item id, looks for the sub-group of items for it in the DOM.
         * If it exists, it's shown, otherwise False is returned.
         *
         * @param {Number|Object} id Number item id or Object YUI node
         * @return {Boolean} True if the Node was found and shown, false otherwise
         */
        _showSubItemsFor: function(id) {
            var existing = this.widgetElement.one(this.baseSelector + '_' + (id || 'Base') + '_SubItems');

            if (existing) {
                this.widgetElement.all('.rn_ItemGroup').addClass('rn_Hidden');
                this._setPaginationVisibility(existing);
                this._focusFirstLink(existing.removeClass('rn_Hidden'));

                return true;
            }

            return false;
        },
        
        /**
         * Goes back to a previously-displayed hierarchy level.
         * The click target is expected to have legit `data-id` and `data-level` attributes.
         *
         * @param {Object} e Click event
         */
        _goToPreviousLevel: function(e) {
            var id = e.target.getAttribute('data-id');

            if (!this._showSubItemsFor(id)) {
                this._showChildren({ id: id });
            }

            this.currentLevel = parseInt(e.target.getAttribute('data-level'), 10);

            if (!this.data.attrs.label_breadcrumb) {
                // Level is off-by-one if there's no top-level title being displayed.
                this.currentLevel++;
            }

            this._updateBreadCrumb(this.currentLevel, this.itemLevels);
        },
        
        /**
         * Sets the number of sub items on the show more link. Used when fetching the sub items for the 
         * initial items without an AJAX request
         * @param {Object} items that have sub items
         */
        _setShowMoreNumber: function(items) {
            this.Y.Object.each(items, function (items, parentID) {
                if (showMoreLink = this.Y.one(this.baseSelector + ' .rn_ShowChildren[data-id="' + parentID + '"]')) {
                    showMoreLink.set('text', RightNow.Text.sprintf(this.data.attrs.label_prefetched_sub_items, this.prefetched[parentID]));
                }
            }, this);
        }
    }
});
