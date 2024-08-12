RightNow.Widgets.SourceProductCategorySearchFilter = RightNow.SearchProducer.extend({
    overrides: {
        constructor: function() {
            this.parent();

            // The methods that exist in this widget with the same
            // name will override - they're responsible
            // for calling the methods on RightNow.ProductCategory's
            // prototype.
            this.Y.augment(this, RightNow.ProductCategory);

            this.searchSource(this.data.attrs.report_id)
                .on('collect', this.collectFilters, this)
                .on('response', this.onFilterUpdate, this)
                .on('reset', this.onReset, this);

            this.initializeEventObject();

            this.initializeTreeView(this.data.attrs.filter_type);
        }
    },

    /**
     * Displays the hierarchy of the currently selected node up to its root node,
     * hides the panel, and focuses on the selection button (if directed).
     * @param {Boolean} focus Whether or not the button should be focused
     */
    displaySelectedNodesAndClose: function(focus) {
        RightNow.ProductCategory.prototype.displaySelectedNodesAndClose.call(this, focus);
    },

    /**
     * Selected a node by clicking on its label
     * (as opposed to expanding it via the expand image).
     * @param {object} node The node
     */
    selectNode: function(node) {
        if(((node.expanded || !node.value) && !this.data.js.linkingOn) ||
            (this.data.attrs.search_on_select && !RightNow.Url.isSameUrl(this.data.attrs.search_results_url))) {
            // There's no need to get the sub-level requests if the 'all value' item is selected or
            // the search happens on a new page, so just update the event object data.
            this._eo.data.level = node.depth + 1;
            this._eo.data.label = node.label;
            this._eo.data.value = node.value;
        }
        else {
            this.getSubLevelRequest(node);
            this.tree.collapseAll();
        }

        RightNow.ProductCategory.prototype.selectNode.call(this, node);

        if (this.data.attrs.search_on_select) {
            this.searchSource().fire('collect').fire('search', this._eo);
        }
    },

    /**
     * Event handler when a node is expanded.
     * Requests the next sub-level of items from the server.
     * @param {object} expandingNode The node that's expanding
     */
    getSubLevelRequestEventObject: function(expandingNode) {
        if(expandingNode.expanded && !this.data.js.linkingOn) return;

        this._eo.data.level = expandingNode.depth + 1;
        this._eo.data.label = expandingNode.label;
        this._eo.data.value = expandingNode.value;

        // Static variable for different widget instances but the same data type
        this.getSubLevelRequestEventObject._origRequest = this.getSubLevelRequestEventObject._origRequest || [];
        this.getSubLevelRequestEventObject._origRequest[this.dataType] = expandingNode.value;

        if(this.data.js.link_map) {
            // Pass link map (prod linking) to EventBus for first time
            this._eo.data.link_map = this.data.js.link_map;
            this.data.js.link_map = null;
        }

        if (!expandingNode.loaded || this.data.js.linkingOn) {
            return this._eo;
        }
    },

    /**
     * Event handler when returning from ajax data request
     * @param {string} type Event name
     * @param {array} args Event arguments
     */
    getSubLevelResponse: function(type, args) {
        var eventObject = args[0];
        // Compute if the sub level response should be rendered. When false we want to
        // render the event object.
        //
        // When linking is enabled, we check a combination of eventObject.data.value,
        // eventObject.data.via_hier_request, and eventObject.data.via_filter_request
        // to see if the response should be rendered. If linking is not enabled, we simply
        // compare the data types of the event and widget itself.
        //
        // * eventObject.data.value: The event object's value, such as product ID, or 0 if
        // top level.
        // * eventObject.data.via_filter_request: True when a category expands and linking
        // is on; used because unlike ProductCategorySearchFilters, report ids are not
        // compared.
        // * eventObject.data.via_hier_request: Indicates whether the event requested a
        // hierarchy, which happens when either product or categories are selected when
        // product category linking is enabled.
        if(!((eventObject.data.linking_on && (!eventObject.data.value && eventObject.data.via_hier_request)) ||
                (eventObject.data.data_type !== this.dataType) || (eventObject.data.via_filter_request && eventObject.data.data_type !== this.dataType))) {
            RightNow.ProductCategory.prototype.getSubLevelResponse.call(this, eventObject, this.dataType);
        }
    },

    /**
     * Event handler when report has been updated
     * @param {string} type Event name
     * @param {array} args Event arguments
     */
    onFilterUpdate: function(type, args) {
        var filterValue = 0;

        this.Y.Object.some(args[0].data.filters, function (filter) {
            if (filter.key === this.data.js.filter.key) {
                filterValue = filter.value;
                return true;
            }
        }, this);

        if (filterValue) {
            this.buildTree();

            if (typeof filterValue === 'string' || typeof filterValue === 'number') {
                filterValue = (filterValue + '').split(',');
            }

            this.tree.expandAndCreateNodes(filterValue);
        }
        else if (this.tree) {
            //going from some selection back to no selection
            this.tree.resetSelectedNode();
            this.displaySelectedNodesAndClose();
        }
    },

    /**
     * Returns the filter's value in response to a collect event.
     * @return {object} EventObject
     */
    collectFilters: function() {
        return this._eo;
    },

    /**
     * Responds to the filter reset event.
     * @param {string} type Event name
     * @param {array} args Event arguments
     */
    onReset: function(type, args) {
        args = args[0];
        if(this.tree) {
            var initial = this.data.js.initial[this.data.js.initial.length - 1] || 0;
            // Insure initial value is correct when fired from the 'Product' widget
            // to update the 'Category' widget
            if(args && args.data.name === 'c' && this.dataType === 'Product') {
                initial = 0;
            }
            this.tree.selectNodeWithValue(initial);
            this.displaySelectedNodesAndClose();
        }
    },

    /**
     * Sets the event object for subsequent searches.
     */
    initializeEventObject: function() {
        this._eo = new RightNow.Event.EventObject(this, {
            data: this.Y.merge(this.data.js.filter, {
                data_type:      this.dataType = this.data.attrs.filter_type,
                new_page:       this.data.attrs.search_results_url,
                linking_on:     this.data.js.linkingOn,
                hm_type:        this.data.js.hm_type,
                cache:          []
            })
        });

        if (this.dataType === "Product") {
            // Set namespace global for hier menu list linking display
            RightNow.UI.Form.currentProduct = this.data.js.initial[this.data.js.initial.length - 1];
        }
    }
});
