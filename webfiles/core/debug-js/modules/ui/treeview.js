YUI.add("RightNowTreeView", function(Y) {
    /**
     * Creates a TreeView UI component for navigating hierarchy data.
     * Events emitted:
     *
     *      - dynamicNodeExpand: When a node that indicated it has children is expanded
     *      - enterKey: When the enter key is pressed
     *      - click: When a node is clicked
     */
    Y.RightNowTreeView = Y.Base.create('RightNowTreeView', Y.Widget, [], {
        /**
         * Currently-selected node's index. 1-based.
         */
        _currentIndex: 1,

        /**
         * Renders the treeview.
         */
        render: function () {
            this._tree = new Y.apm.TreeView(this.get('contentBox').get('id'));

            this._initializeTreeEvents();
            this._createTree(this.get('hierarchyData'));
            this._subscribeToTreeEvents();
            this._tree.collapseAll();
        },

        /**
         * Builds and inserts child nodes for the node with the given value.
         * @param  {Array} hierData          Child node data
         * @param  {number|string} valueOfParentNode ID / value of parent node
         * @return {boolean} Whether the operation succeeded
         */
        insertChildHierarchyData: function (hierData, valueOfParentNode) {
            var parent = valueOfParentNode ? this._getNodeByValue(valueOfParentNode) : this._getRoot();

            if (!parent || parent.dynamicLoadComplete) return false;

            this._insertNodes(hierData, parent, []);

            return true;
        },

        /**
         * Removes all nodes from the tree.
         * @param  {string} allValueLabel If specified, a single
         *                                'all value' node with this label
         *                                is added into the tree
         * @return {Object} Chainable
         */
        clear: function (allValueLabel) {
            var root = this._tree.getRoot();
            root.dynamicLoadComplete = false;
            this._tree.removeChildren(root);

            if (allValueLabel) {
                var node = new Y.apm.MenuNode(
                    Y.Escape.html(allValueLabel), root, false);
                node.hierValue = 0;
                node.href = 'javascript:void(0);';
                node.isLeaf = true;
            }

            root.loadComplete();

            this._currentIndex = 1;

            return this;
        },

        /**
         * Collapses all expanded branches in the tree.
         * @return {Object} Chainable
         */
        collapseAll: function () {
            this._tree.collapseAll();

            return this;
        },

        /**
         * Focuses on the selected node.
         * If no node is selected, the first node
         * in the tree is focused.
         * @return {Object} Chainable
         */
        focusOnSelectedNode: function () {
            //focus on either the previously selected node or the first node
            var currentNode = this._getSelectedNode() ||
                this._tree.getRoot().children[0];
            if(currentNode && currentNode.focus) {
                currentNode.focus();
            }

            return this;
        },

        /**
         * Selects the node in the tree with the given value.
         * @param  {string|Number} value ID / value of the node
         * @param  {boolean=} focus Whether to focus on the node
         * @return {boolean} Whether the operation was successful
         */
        selectNodeWithValue: function (value, focus) {
            var node = this._getNodeByValue(value);

            if (node) {
                this._currentIndex = node.index;
                if (focus) {
                    this.focusOnSelectedNode();
                }
                return true;
            }

            return false;
        },

        /**
         * Resets the selection back to the first node
         * in the tree.
         * @return {Object} Chainable
         */
        resetSelectedNode: function () {
            this._currentIndex = 1;

            return this;
        },

        /**
         * Gets the root node of the tree.
         * @return {Object|null}       Node object:
         *                             - expanded: boolean
         *                             - value: number
         *                             - index: number
         *                             - depth: number (0-based)
         *                             - label: string
         *                             - loaded: boolean
         *                             - hasChildren: boolean
         *                             - valueChain: array
         *                             - nodeRef: object YUI MenuNode
         */
        getRoot: function () {
            return this._nodeWrapper(this._getRoot());
        },

        /**
         * Gets a node by its ID / value.
         * @param  {string|number} value Node value
         * @return {Object|null}       Node object:
         *                             - expanded: boolean
         *                             - value: number
         *                             - index: number
         *                             - depth: number (0-based)
         *                             - label: string
         *                             - loaded: boolean
         *                             - hasChildren: boolean
         *                             - valueChain: array
         *                             - nodeRef: object YUI MenuNode
         */
        getNodeByValue: function (value) {
            return this._nodeWrapper(this._getNodeByValue(value));
        },

        /**
         * Gets the currently-focused node.
         * @return {Object|null}       Node object:
         *                             - expanded: boolean
         *                             - value: number
         *                             - index: number
         *                             - depth: number (0-based)
         *                             - label: string
         *                             - loaded: boolean
         *                             - hasChildren: boolean
         *                             - valueChain: array
         *                             - nodeRef: object YUI MenuNode
         */
        getFocusedNode: function () {
            return this._nodeWrapper(this._treeCurrentFocus);
        },

        /**
         * Gets the currently-selected node.
         * @return {Object|null}       Node object:
         *                             - expanded: boolean
         *                             - value: number
         *                             - index: number
         *                             - depth: number (0-based)
         *                             - label: string
         *                             - loaded: boolean
         *                             - hasChildren: boolean
         *                             - valueChain: array
         *                             - nodeRef: object YUI MenuNode
         */
        getSelectedNode: function () {
            return this._nodeWrapper(this._getSelectedNode());
        },

        /**
         * Returns the total number of nodes loaded into the TreeView.
         * @return {number} Total number of nodes
         */
        getNumberOfNodes: function () {
            return this._tree.getNodeCount();
        },

        /**
        * Used to set the tree to a specific state; programatically expands nodes
        * in order to build up the hierarchy tree to the specified array of IDs. Since
        * requested hierarchy items may not be loaded, this function may asynchronously
        * request sub-levels until the request is completed, even after returning.
        * @param {Array} hierArray IDs denoting the hierarchy chain within the tree
        * @return {boolean} Whether nodes were expanded. If a given value couldn't
        * be found or the specified node is already selected, False is returned
        */
        expandAndCreateNodes: function (hierArray) {
            var i = hierArray.length - 1,
                currentNode = null;

            //walk up the chain looking for the first available node
            while(!currentNode && i >= 0) {
                currentNode = this._getNodeByValue(hierArray[i]);
                i--;
            }

            if (!currentNode || this._currentIndex === currentNode.index) return false;

            //if we already have the one selected, then we can go ahead and select it.
            i++;
            if(currentNode.index === 1 ||
                currentNode.hierValue === parseInt(hierArray[hierArray.length - 1], 10)) {
                this._onClick({ node: currentNode });
            }
            else {
                var expandNode = function (node) {
                    node.nextToExpand = hierArray[++i];
                    node.expand();
                };
                var onExpandComplete = function(expandingNode) {
                    if(expandingNode.nextToExpand) {
                        var nextNode = this._getNodeByValue(expandingNode.nextToExpand);
                        if(nextNode) {
                            expandNode(nextNode);
                        }
                    }
                    else if(i === hierArray.length) {
                        //we don't want to subscribe to this more than once
                        this._tree.unsubscribe("expandComplete", onExpandComplete, null);
                        expandingNode.expanded = false;
                        this._onClick({ node: expandingNode });
                    }
                };
                //walk back down to their selection from here expanding as you go
                this._tree.subscribe("expandComplete", onExpandComplete, this);
                expandNode(currentNode);
            }

            return true;
        },

        /**
         * Hook method that YUI.Base calls during
         * the destroy phase.
         * @private
         */
        destructor: function () {
            // Because we are using a port of YUI2's treeview, there are some oddities. One is the lack of Event.removeListener.
            // Because the destroy method for treeView calls this non-existant method, it was causing errors...
            // this._tree.destroy();

            // Just empty out the tree instead
            this.clear();
        },

        /**
         * Initializes event listeners.
         * @private
         */
        _initializeTreeEvents: function () {
            this._tree.setDynamicLoad(Y.bind(function (node) {
                this.fire('dynamicNodeExpand', this._nodeWrapper(node));
            }, this));
            this._tree.subscribe('focusChanged', function(e) {
                if (e.newNode) {
                    this._treeCurrentFocus = e.newNode;
                }
                else if (e.oldNode) {
                    this._treeCurrentFocus = e.oldNode;
                }
            }, this);

            if(this.get('respondToTabKeypress')) {
                Y.one(this._tree.getEl()).on('key', this._onTab, 'tab', this);
            }
        },

        /**
         * Tab keypress handler: performs the same functionality that the
         * enter key does.
         * @param  {Object} e Key event
         * @private
         */
        _onTab: function (e) {
            var currentNode = this._treeCurrentFocus;
            if (currentNode.href) {
                if(currentNode.target) {
                    window.open(currentNode.href, currentNode.target);
                }
                else {
                    window.location = currentNode.href;
                }
            }
            else {
                currentNode.toggle();
            }
            this._tree.fireEvent('enterKeyPressed', currentNode);
            e.halt();
        },

        /**
         * Subscribes to TreeView events
         * @private
         */
        _subscribeToTreeEvents: function () {
            this._tree.subscribe('expandComplete', function(e) {
                //scroll container to 20px above expanded node
                this.get('contentBox').scrollTop = Y.one('#' + e.contentElId)
                    .ancestor('.ygtvitem').get('offsetTop');
            }, this);
            this._tree.subscribe('clickEvent', this._onClick, this);
            this._tree.subscribe('enterKeyPressed', function (keyEvent) {
                this._currentIndex = keyEvent.details[0].index;
                this.fire('enterKey', this._nodeWrapper(keyEvent.details[0]));
            }, this);
        },

        /**
         * Creates child nodes on the TreeView.
         * @param  {Array} hierarchyData Array of nodes
         */
        _createTree: function (hierarchyData) {
            this._insertNodes(hierarchyData[0], this._tree.getRoot(), hierarchyData);
            this._tree.getRoot().children[0].isLeaf = true;
        },

        /**
         * Inserts child nodes into the TreeView.
         * @param  {Array} nodeList Branch of nodes for one parent
         * @param  {Object} root     Root treeview node
         * @param  {Array} all      All nodes in the tree indexed by parent id
         * @private
         */
        _insertNodes: function (nodeList, root, all) {
            var childNodes = [];

            Y.Array.each(nodeList, function (dataNode) {
                var node = new Y.apm.MenuNode(Y.Escape.html(dataNode.label.replace(/(<([^>]+)>)/ig,"")), root);
                node.href = 'javascript:void(0)';
                node.hierValue = dataNode.id;

                if(!dataNode.hasChildren || root.depth === 5) {
                    // if it doesn't have children then turn off the +/- icon
                    // and notify that the node is already loaded
                    node.dynamicLoadComplete = true;
                    node.iconMode = 1;
                }

                if(dataNode.selected) {
                    this._currentIndex = node.index;
                }

                //Child processing must be deferred until after root.loadComplete
                if(dataNode.hasChildren && all[dataNode.id]) {
                    childNodes.push({ children: all[dataNode.id], parent: node });
                }
            }, this);

            //Let YUI know that all of the (direct) children of this root have been loaded
            root.loadComplete();

            Y.Array.each(childNodes, function (child) {
                this._insertNodes(child.children, child.parent, all);
            }, this);
        },

        /**
         * Simplified wrapper object returned externally so that clients aren't
         * directly accessing the TreeView's live nodes.
         * @param  {Object} node Y.apm.MenuNode instance
         * @return {Object|null}      Object hash representing the node or null
         *                                   if node is null
         * @private
         */
        _nodeWrapper: function (node) {
            return node ? {
                expanded: node.expanded,
                value:    node.hierValue,
                index:    node.index,
                depth:    node.depth,
                label:    node.label,
                loaded:   node.dynamicLoadComplete,
                hasChildren: node.hasChildren(false),
                valueChain: this._getPropertyChainForNode('hierValue', node)
            } : null;
        },

        /**
         * On click handler.
         * @param  {Object} clickEvent Click event
         * @private
         */
        _onClick: function (clickEvent) {
            this._currentIndex = clickEvent.node.index;
            this._selected = true;

            if(clickEvent.event)
                clickEvent.event.preventDefault();

            this.fire('click', this._nodeWrapper(clickEvent.node));
        },

        /**
         * Returns the root node of the tree.
         * @return {Object} Root node
         * @private
         */
        _getRoot: function () {
            return this._tree.getRoot();
        },

        /**
         * Returns the value of the selected node.
         * @return {number} Node's id / value
         * @private
         */
        _getSelectedValue: function () {
            return this._getPropertyOfSelectedNode('hierValue', 0);
        },

        /**
         * Returns the depth of the selected node.
         * @return {number} Node's depth
         * @private
         */
        _getSelectedDepth: function () {
            return this._getPropertyOfSelectedNode('depth', 0);
        },

        /**
         * Returns the label of the selected node.
         * @return {string} Node's label
         * @private
         */
        _getSelectedLabel: function () {
            return this._getPropertyOfSelectedNode('label', '');
        },

        /**
         * Returns the specified property of the selected node, or
         * returns the defaultValue if that property is falsy.
         * @param  {string} property     Property name
         * @param  {string|Number} defaultValue Default value
         * @return {string|Number}              Value from node
         */
        _getPropertyOfSelectedNode: function (property, defaultValue) {
            var node = this._getSelectedNode();
            return node ? node[property] : defaultValue;
        },

        /**
         * Gets the currently-selected node.
         * @return {Object} Y.apm.MenuNode
         */
        _getSelectedNode: function () {
            return this._tree.getNodeByIndex(this._currentIndex);
        },

        /**
         * Gets a node by its ID / value.
         * @param  {number|string} value Node value
         * @return {Object}       Y.apm.MenuNode
         * @private
         */
        _getNodeByValue: function (value) {
            return this._tree.getNodeByProperty('hierValue', parseInt(value, 10));
        },

        /**
         * Gets an array of values of node properties for
         * the full selection path within the tree.
         * @param  {string} property Property name
         * @return {Array}          Array of property values
         * @private
         */
        _getPropertyChain: function(property) {
            this._currentIndex = this._currentIndex || 1;

            return this._getPropertyChainForNode(property, this._getSelectedNode());
        },

        /**
         * Returns an array of values of node properties
         * from the root node to the specified node.
         * @param  {string} property Property name
         * @param  {Object} node     Y.apm.MenuNode
         * @return {Array}          Array of property values
         * @private
         */
        _getPropertyChainForNode: function(property, node) {
            var hierValues = [];

            if (node && node.isRoot()) {
                return [node[property]];
            }

            while (node && !node.isRoot()) {
                hierValues.push(node[property]);
                node = node.parent;
            }
            return hierValues.reverse();
        }
    }, {
        ATTRS: {
            /**
             * Hierarchy data of tree data.
             *
             * [
             *   parent id: [{
             *       label: string,
             *       id:    number id / value,
             *       hasChildren: boolean,
             *       selected: boolean
             *     },
             *   ...
             *   ],
             *   ...
             * ]
             *
             * The top-level items are contained within the 0th
             * element, since they do not have parents.
             *
             */
            hierarchyData: {
                value: []
            },

            /**
             * By default, if focus is inside the TreeView and the user presses
             * the tab key, focus leaves the TreeView and goes to the next
             * focusable element on the page. Enabling this attribute makes tab
             * keypress behavior identical to enter keypress behavior.
             */
            respondToTabKeypress: {
                value: false,
                validator: Y.Lang.isBoolean
            },

            /**
             * The id / value of the selected node.
             */
            value: {
                getter: '_getSelectedValue'
            },

            /**
             * The label of the selected node.
             */
            label: {
                getter: '_getSelectedLabel'
            },

            /**
             * The depth of the selected node.
             */
            depth: {
                getter: '_getSelectedDepth'
            },

            /**
             * The array of values representing the
             * ids / values of each node in the tree from the
             * root node to the selected child.
             */
            valueChain: {
                getter: function () { return this._getPropertyChain('hierValue'); }
            },

            /**
             * The array of labels representing the
             * labels of each node in the tree from the
             * root node to the selected child.
             */
            labelChain: {
                getter: function () { return this._getPropertyChain('label'); }
            }
        }
    });
}, "1.0.1", {
    requires: [
        "widget-child",
        "gallery-treeview"
    ]
});
