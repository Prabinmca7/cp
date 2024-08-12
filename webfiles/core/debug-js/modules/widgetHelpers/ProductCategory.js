/**
 * Provides Product / Category TreeView widget functionality:
 * - Dropdown menu
 * - TreeView UI contained within the menu
 * - Accessible TreeView dialog
 *
 *  May be extended by a widget or mixed into a widget using
 *  `Y.augment`, where it is then the widget's responsibility to
 *  override methods in order to add or change specific behavior
 *  (e.g. product linking features form validation, searching,
 *  etc.).
 */
RightNow.ProductCategory = RightNow.EventProvider.extend({
    /**
     * Initializes the various UI components.
     * @param {string} dataType Product or Category
     */
    initializeTreeView: function (dataType) {
        this.dataType = dataType;
        this.displayField || (this.displayField = this.Y.one(this.baseSelector + "_" + this.dataType + "_Button"));

        this.buildPanel();

        this.Y.one(this.baseSelector + "_LinksTrigger").on("click", this.showAccessibleView, this);

        RightNow.Event.on("evt_accessibleTreeViewGetResponse", this.getAccessibleTreeViewResponse, this);
        RightNow.Event.on("evt_menuFilterGetResponse", this.getSubLevelResponse, this);

        if (this.data.js.hierData && this.data.js.hierData[0] && this.data.js.hierData[0].length) {
            this.buildTree();
        }
    },

    /**
     * Builds panel for the treeview menu.
     */
    buildPanel: function() {
        this.dropdown = new this.Y.RightNowTreeViewDropdown({
            srcNode: this.Y.one(this.baseSelector + "_TreeContainer").removeClass('rn_Hidden'),
            render: this.Y.one(this.baseSelector),
            trigger: this.displayField,
            visible: false
        });
        this.dropdown.once('show', this.Y.bind(this.buildTree, this, false));
        this.dropdown.on('show', function () {
            this.tree.focusOnSelectedNode();
        }, this);
    },

    /**
     * Constructs the RightNowTreeView widget for the first time with initial data returned
     * from the server. Pre-selects and expands data that is expected to be populated.
     * @param {boolean} forceRebuild Whether to forcefully recreate the tree if it already exists
     */
    buildTree: function (forceRebuild) {
        if (this.tree && !forceRebuild) {
            return;
        }

        if (this.tree) {
            this.tree.clear();
        }

        if (this.data.js.linkingOn && this.dataType === "Category" && this.data.js.link_map) {
            this.data.js.hierData = this.data.js.link_map;
        }

        this.tree = new this.Y.RightNowTreeView({
            hierarchyData: this.data.js.hierData || this.data.js.hierDataNone,
            contentBox: this.Y.one(this.baseSelector + '_Tree').setStyles({
                'overflow-y': 'auto',
                'display': 'block'
            })
        });
        this.tree.render();

        //If we have a hierDataNone set for this widget, it's a category and linking is on
        //so any subsequent calls to this function should use the reset data in hierDataNone
        if(this.data.js.hierData && this.data.js.hierDataNone) {
            delete this.data.js.hierData;
        }

        this.tree.on('enterKey', this.selectNode, this);
        this.tree.on('dynamicNodeExpand', this.getSubLevelRequest, this);

        if (this.data.attrs.show_confirm_button_in_dialog) {
            this.dropdown.set('confirmButton', this.Y.one(this.baseSelector + '_' + this.data.js.data_type + '_ConfirmButton'));
            this.dropdown.set('cancelButton', this.Y.one(this.baseSelector + '_' + this.data.js.data_type + '_CancelButton'));
            this.dropdown.on('confirm', function () {
                this.selectNode(this.tree.getFocusedNode());
            }, this);
            this.Y.one(this.baseSelector + '_TreeContainer').setStyle('display', 'block');
        }
        else {
            this.tree.on('click', this.selectNode, this);
        }

        if (this.tree.get('value')) {
            this.displaySelectedNodesAndClose(false);
        }

        this.dropdown.set('treeView', this.tree);
    },

    /**
     * Removes any accessible links that are non-permissioned and have no permissioned children
     * @param {Array} accessibleNodes Complete hierarchy of the accessible prodcat dialog
     * @return {Array} Array of permissioned links
     */
    pruneNonPermissionedAccessibleNodes: function(accessibleNodes) {
        this.checkIdsAreValid('readableProdcatIds');

        if(this.data.js.readableProdcatIds.length === 0)
            return accessibleNodes;

        var pathsToKeep = [];
        this.Y.Array.each(accessibleNodes, function(node) {
            // If the link is permissioned, or if it has permissioned children, add it to the list of paths to keep.
            if(this.checkPermissionsOnNode(node[1]) || this.hasPermissionedAccessibleChildren(node)) {
                pathsToKeep.push(node);
            }
        }, this);

        return pathsToKeep;
    },

    /**
     * Checks to see if the current accessible node in the accessibility dialog has any
     * child nodes the user is permissioned to select.
     * @param {Object} accessibleNode Accessible node to check permissions against
     * @return Whether any viewable node was found
     */
    hasPermissionedAccessibleChildren: function(accessibleNode) {
        return this.checkForPermissionedChildren(accessibleNode[1], accessibleNode.level + 1);
    },

    /**
     * Checks to see if the current node in the dropdown tree has any
     * child nodes the user is permissioned to select.
     * @param {Object} hierNode Product node to check permissions against
     * @param {Number} currentLevel Current level of the hierNode to check
     * @return {boolean} Whether any viewable node was found
     */
    hasPermissionedChildren: function(hierNode, currentLevel) {
        if(!hierNode.hasChildren) {
            return false;
        }

        return this.checkForPermissionedChildren(hierNode.id, currentLevel);
    },

    /**
     * Checks to see if the current node has any
     * child nodes the user is permissioned to select.
     * @param {Number} nodeID node ID to check permissions against
     * @param {Number} currentLevel Current level of the node to check
     * @return {boolean} Whether any viewable node was found
     */
    checkForPermissionedChildren: function(nodeID, currentLevel) {
        if(!this.data || !this.data.js || !this.data.js.readableProdcatIds || !this.Y.Lang.isArray(this.data.js.readableProdcatIds))
            throw new Error("Widget does not have this.data.js.readableProdcatIds attribute set, or its value is not an array");

        if(this.data.js.readableProdcatIds.length === 0)
            return true;

        var levelToCheck = "Level" + currentLevel;
        // Look for any permissionedProdcats that have the given nodeID in their hierarchy list.
        // Disregard the permissionedProdcat if it is the node we are checking (a node isn't the child of itself)
        return this.Y.Array.some(this.data.js.permissionedProdcatList, function(product) {
            if(product[levelToCheck] === nodeID && product.ID !== nodeID) {
                return true;
            }
        }, this);
    },

    /**
     * Passes through to the appropriate method to check readability or
     * selectability of the given node by the current user.
     * @param {Number} selectedID Node ID to check permissions against
     * @return {boolean} Whether the current user is permissioned to select the given node
     */
    checkPermissionsOnNode: function(selectedID) {
        var isPermissionedArray = this.data.js.permissionedProdcatIds && this.Y.Lang.isArray(this.data.js.permissionedProdcatIds),
            isReadableArray = this.data.js.readableProdcatIds && this.Y.Lang.isArray(this.data.js.readableProdcatIds);

        if(!(isPermissionedArray || isReadableArray))
            throw new Error("Widget has neither this.data.js.readableProdcatIds or this.data.js.permissionedProdcatIds set, or their values are not an array.");

        if((isPermissionedArray && this.data.js.permissionedProdcatIds.length === 0) ||
            (isReadableArray && this.data.js.readableProdcatIds.length === 0)) {
            return true;
        }

        return isPermissionedArray ? this.isPermissionedNode(selectedID) : this.isReadableNode(selectedID);
    },

    /**
     * Checks to see if the given node is selectable by the current user.
     * @param {Number} selectedID Node ID to check permissions against
     * @return {boolean} Whether the current user is permissioned to select the given node
     */
    isPermissionedNode: function(selectedID) {
        if(this.userHasFullProdcatPermissions())
            return true;

        return (this.Y.Array.indexOf(this.data.js.permissionedProdcatIds, parseInt(selectedID, 10)) !== -1);
    },

    /**
     * Checks to see if the user has permissions to all prodcats
     * @return {boolean} Whether the user has full prodcat permissions
     */
    userHasFullProdcatPermissions: function() {
        this.checkIdsAreValid('permissionedProdcatIds');

        return (this.data.attrs.verify_permissions === "None") || (this.data.js.permissionedProdcatIds.length === 0);
    },

    /**
     * Checks to see if the given node is readable by the current user.
     * @param {Number} selectedID Node ID to check permissions against
     * @return {boolean} Whether the current user is permissioned to select the given node
     */
    isReadableNode: function(selectedID) {
        this.checkIdsAreValid('readableProdcatIds');

        if(this.data.js.readableProdcatIds.length === 0)
            return true;

        return (this.Y.Array.indexOf(this.data.js.readableProdcatIds, parseInt(selectedID, 10)) !== -1);
    },

    /**
     * Goes through the accessible product hierarchy and adds `invalid selection` text to any nodes
     * that have permissioned children, but are not permissioned themselves.
     * @param {Array} accessibleLinks Current hierarchy of the products in the accessible dialog available to the user
     */
    disableNonPermissionedAccessibleNodes: function(accessibleLinks) {
        this.checkIdsAreValid('readableProdcatIds');

        // If the label_selection_not_valid attribute is not set,
        // assume the calling widget explicitely chooses not to add disabled text.
        // Generally, this would be the case in search widgets, since non-permissioned nodes are still searchable.
        if(this.data.js.readableProdcatIds.length === 0 || !this.data.attrs.label_selection_not_valid)
            return;

        var nodeToDisable;
        this.Y.Array.each(accessibleLinks, function(link) {
            if(!this.checkPermissionsOnNode(link[1])) {
                nodeToDisable = this.Y.one(this.baseSelector + '_AccessibleLink_' + link[1]);
                nodeToDisable.setHTML(RightNow.Text.sprintf(this.data.attrs.label_selection_not_valid, nodeToDisable.getHTML()));
            }
        }, this);
    },

    /**
     * Goes through the product hierarchy and visually disables any nodes that
     * have permissioned children, but are not permissioned themselves.
     * @param {Array} hierData Current hierarchy of the products available to the user
     */
    disableNonPermissionedNodes: function(hierData) {
        this.checkIdsAreValid('readableProdcatIds');

        if(this.data.js.readableProdcatIds.length === 0)
            return;

        this.Y.Array.each(hierData, function(data) {
            if(!this.checkPermissionsOnNode(data.id)) {
                this.Y.one('#ygtvlabelel' + this.tree.getNodeByValue(data.id).index).addClass('rn_Disabled');
            }
        }, this);
    },

    /**
     * Executed when a tree item is selected from the accessible view.
     * @param {Object} e YUI event facade
     */
    onAccessibleLinkClick: function(e) {
        var selectedID = e.valueChain[e.valueChain.length - 1];

        this.tree.expandAndCreateNodes(e.valueChain);
        if(this.checkPermissionsOnNode(selectedID)) {
            this.dialog.hide();
        }
    },

    /**
     * Shows the accessible dialog.
     * @param {Object} e Click event
     */
    showAccessibleView: function(e) {
        e.halt();

        this._eo || (this._eo = new RightNow.Event.EventObject(this));

        if (this.dataType === "Category" && this.data.js.linkingOn) {
            this._eo.data.linkingProduct = RightNow.UI.Form.currentProduct;
        }

        if (this.dialog) {
            this.displayAccessibleDialog();
        }
        else {
            RightNow.Event.fire("evt_accessibleTreeViewRequest", this._eo);
        }
    },

    /**
     * Listens to response from the server and constructs an HTML tree according to
     * the flat data structure given.
     * @param {string} e Event name
     * @param {Array} args Event arguments
     */
    getAccessibleTreeViewResponse: function(e, args) {
        args = args[0];
        if(args.w_id !== this._eo.w_id) return;
        if(args.data.hm_type !== this._eo.data.hm_type) return;

        var results = args.data.accessibleLinks;
        if('prod_chain' in results) {
            results = [];
            delete args.data.accessibleLinks.prod_chain;
            for(var result in args.data.accessibleLinks) {
                results.push(args.data.accessibleLinks[result]);
            }
        }

        results.unshift({
           0: this.data.attrs.label_all_values,
           1: 0,
           hier_list: 0,
           level: 0
        });
        results = this.pruneNonPermissionedAccessibleNodes(results);

        this.createAccessibleDialog(results);
        this.buildTree();
        this.displayAccessibleDialog();
        this.disableNonPermissionedAccessibleNodes(results);
    },

    /**
     * Creates a new RightNowTreeViewDialog with the given hierarchy data.
     * @param  {Array} flatHierarchyData Flat hierarchy data the the RightNowTreeViewDialog
     *                                   uses to construct the dialog content.
     */
    createAccessibleDialog: function(flatHierarchyData) {
        if (this.dialog) {
            this.dialog.destroy();
        }

        // Can't use %s placeholder for Product/Category as it doesn't work with gendered languages
        var introLabelStart = (this.dataType === "Product")
            ? RightNow.Interface.getMessage('SELECT_BELOW_USING_LINKS_PROVIDED_MSG')
            : RightNow.Interface.getMessage('CATEGORY_BELOW_USING_LINKS_PROVIDED_MSG');
        var introLabelEnd = RightNow.Interface.getMessage('DEPTH_ANNOUNCED_NAVIGATE_THROUGH_LIST_MSG');

        this.dialog = new this.Y.RightNowTreeViewDialog({
            id: 'rn_' + this.instanceID,
            hierarchyData: flatHierarchyData,
            contentBox: this.Y.one(this.baseSelector + "_Links"),
            dismissLabel: RightNow.Interface.getMessage("CANCEL_CMD"),
            titleLabel: this.data.attrs.label_nothing_selected,
            introLabel: introLabelStart + ' ' + introLabelEnd,
            selectionPlaceholderLabel: RightNow.Interface.getMessage("SELECTION_PCT_S_ACTIVATE_LINK_JUMP_MSG"),
            levelLabel: this.data.attrs.label_level,
            noItemSelectedLabel: this.data.attrs.label_all_values
        });
        this.dialog.on('selectionMade', this.onAccessibleLinkClick, this);

        this.dialog.render();
    },

    /**
     * Sets the currently-selected values and labels on the dialog
     * and shows it.
     */
    displayAccessibleDialog: function () {
        this.dialog.set('selectedValue', this.tree.get('value'));
        this.dialog.set('selectedLabels', this.tree.get('labelChain'));
        this.dialog.show();
        var nodeToFocus = this.Y.one('p.rn_Intro a');
        if(nodeToFocus) {
            nodeToFocus.focus();
        }
    },

    /**
     * Displays the hierarchy of the currently selected node up to it's root node,
     * hides the panel, and focuses on the selection button (if directed).
     * @param {boolean} focus Whether or not the button should be focused
     */
    displaySelectedNodesAndClose: function(focus) {
        // event to notify listeners of selection
        this._eo || (this._eo = new RightNow.Event.EventObject(this));
        this._eo.data.hierChain = this.tree.get('valueChain');

        RightNow.Event.fire("evt_productCategoryFilterSelected", this._eo);
        delete this._eo.data.hierChain;

        var selectedValues = this.tree.get('valueChain');
        var labels = {
            trigger: this.data.attrs.label_nothing_selected,
            desc:    this.data.attrs.label_nothing_selected
        };

        this.dropdown.hide();

        if (selectedValues[0]) {
            labels.trigger = this.tree.get('labelChain').join("<br>");
            labels.desc = this.data.attrs.label_screen_reader_selected + labels.trigger;
        }
        this.dropdown.set('triggerText', labels.trigger);

        this.Y.all(this.baseSelector + "_TreeDescription").setHTML(labels.desc);

        if (focus && !this.dialog) {
            //don't focus if the accessible dialog is in use or was in use during this page load.
            //the acccessible view and the treeview shouldn't really be mixed
            try {
                this.dropdown.get('trigger').focus();
            }
            catch(oldIE){}
        }
    },

    /**
     * Selected a node by clicking on its label
     * (as opposed to expanding it via the expand image).
     * @param {Object} node The node
     */
    selectNode: function(node) {
        this.displaySelectedNodesAndClose(true);
    },

    /**
     * Peforms a request to get children for the given node.
     * @param  {Object} expandingNode The parent node
     */
    getSubLevelRequest: function (expandingNode) {
        // Only allow one node at-a-time to be expanded.
        if (this._nodeBeingExpanded) return;

        this._nodeBeingExpanded = true;

        var eo = this.getSubLevelRequestEventObject(expandingNode);

        if (eo) {
            if (this.dataType === "Product") {
                //Set namespace global for hier menu list linking display
                RightNow.UI.Form.currentProduct = eo.data.value;
            }

            this._requesting = eo.data.value;

            RightNow.Event.fire("evt_menuFilterRequest", eo);
        }

        this._nodeBeingExpanded = false;

        // Remove link_map from this._eo so this widget does not misinform the Event Bus
        // or other widgets about the link_map on subsequent requests.
        if(this._eo.data.link_map)
            delete this._eo.data.link_map;
    },

    /**
     * Called by #getSubLevelRequest to retrieve an EventObject instance
     * for the request. If this method returns a falsy value, the request is
     * not made.
     * @param  {Object} expandingNode The parent node
     * @return {Object}               EventObject for the request
     */
    getSubLevelRequestEventObject: function (expandingNode) {
        return new RightNow.Event.EventObject(this, {
            data: {
                level: expandingNode.depth + 1,
                value: expandingNode.value,
                label: expandingNode.label,
                cache: {}
            }
        });
    },

    /**
     * Called upon server response containing child node data.
     * @param  {Object} eventObject An event object
     * @param  {String} dataType The data type - either 'Product' or 'Category'
     */
    getSubLevelResponse: function (eventObject, dataType) {
        // delete link_map if we have not already so that we don't send stale data
        if(this.data.js.link_map)
            delete this.data.js.link_map;

        var hierLevel = eventObject.data.level,
            hierData = eventObject.data.hier_data,
            currentRoot;

        hierData = this.groomProdcats(hierData);

        this.buildTree();

        if(!eventObject.data.reset_linked_category && this.getSubLevelRequestEventObject._origRequest &&
            this.getSubLevelRequestEventObject._origRequest[dataType]) {
            currentRoot = this.getSubLevelRequestEventObject._origRequest[dataType];
        }
        else if(eventObject.data.reset_linked_category) {
            //prod linking : data's being completely reset
            this.tree.clear(this.data.attrs.label_all_values);
            this.dialog = null;
            this.selectNode(this.tree.getRoot());
        }

        // Add the new nodes to the currently selected node
        if (hierLevel < 7) {
            this.insertChildrenForNode(hierData, currentRoot);
        }

        if (hierData.length === 0) {
            // Leaf node was expanded : display and close
            this.displaySelectedNodesAndClose();
        }
    },

    /**
     * Inserts the given child node data for the node with the given value
     * @param  {Array} hierData    Child data
     * @param  {string|number} currentRoot ID / value of the parent node
     */
    insertChildrenForNode: function (hierData, currentRoot) {
        this.tree.insertChildHierarchyData(hierData, currentRoot);
    },

    /**
     * Removes non-readable objects (prodcats) from an hierData array. Presumes
     * widget has this.data.js.readableProdcatIds set, which would be an array of
     * readable product IDs (eg, [2, 55, 77]).
     * @param  {Array} hierData Hierarchical prodcat data.
     * @return {Array} hierData Hierarchical prodcat data with non-readable products removed.
     */
    removeNonReadableProdcats: function(hierData) {
        this.checkIdsAreValid('readableProdcatIds');

        var data = this.data;
        return this.Y.Array.filter(hierData, function(hierObject) {
            if(this.Y.Array.indexOf(data.js.readableProdcatIds, hierObject.id) !== -1) {
                return hierObject;
            }
        }, this);
    },

    /**
     * Updates the 'hasChildren' attribute of objects (prodcats) within an hierData
     * array. Presumes widget has this.data.js.readableProdcatIdsWithChildren set, which
     * would be an array of product IDs (eg, [1, 14, 77]).
     * @param  {Array} hierData Hierarchical prodcat data
     * @return {Array} hierData Hierarchical prodcat data with each prodcat's 'hasChildren'
     *     attribute updated to whether it has children or not.
     */
    updateProdcatsHasChildrenAttribute: function(hierData) {
        this.checkIdsAreValid('readableProdcatIdsWithChildren');

        var data = this.data;
        return this.Y.Array.map(hierData, function(hierObject) {
            hierObject.hasChildren = (this.Y.Array.indexOf(data.js.readableProdcatIdsWithChildren, hierObject.id) !== -1) ? true : false;
            return hierObject;
        }, this);
    },

    /**
     * Removes any nonreadable prodcats from the hier data and updates the hasChildren attribute
     * @param {Array} hierData Current hierarchy of the prodcats available to the user
     * @return {Array} Updated hierData
     */
    groomProdcats: function(hierData) {
        if (!(this.data.attrs.verify_permissions === false || this.data.attrs.verify_permissions === 'None') && this.data.js.readableProdcatIds.length > 0) {
            hierData = this.removeNonReadableProdcats(hierData);
            hierData = this.updateProdcatsHasChildrenAttribute(hierData);
        }

        return hierData;
    },

    /**
     * Checks to see if the corresponding data for the given value is properly set, and throws an error if it isn't.
     * @param {string} typeToCheck Name of the type of data to check:
     *  - readableProdcatIds
     *  - readableProdcatIdsWithChildren
     *  - permissionedProdcatIds
     * @throws {Error} If expected data is not set
     */
    checkIdsAreValid: function(typeToCheck) {
        if(!this.data || !this.data.js || !this.data.js[typeToCheck] || !this.Y.Lang.isArray(this.data.js[typeToCheck]))
            throw new Error("Widget does not have this.data.js." + typeToCheck + " attribute set, or its value is not an array");
    }
});
