RightNow.Widgets.TopicBrowse = RightNow.SearchFilter.extend({
    overrides: {
        constructor: function() {
            this.parent();
            this._eo = new RightNow.Event.EventObject(this, {filters: {
                searchName: this.data.attrs.filter_name,
                rnSearchType: this.data.js.rnSearchType,
                report_id: this.data.attrs.report_id,
                data: {
                    fltr_id: this.data.js.filters.fltr_id,
                    oper_id: this.data.js.filters.oper_id,
                    val: this.data.js.filters.default_value || 0
                }
            }});

            this.searchSource()
                .on('response', this._onReportResponse, this)
                .on('search', this._onSearch, this)
                .fire('setInitialFilters', this._eo);

            // listen for pagination events
            RightNow.Event.subscribe('evt_switchPagesRequest', function() {
                this._nodeSelected = true;
            }, this);

            // Create the TreeView.
            this._buildTree();
        }
    },

    /**
     * Creates the tree based off of nested hierarchy of topics.
     * @assert data returned from the model is arranged in correct nesting order
     */
    _buildTree: function() {
        this._treeDiv = this.Y.one(this.baseSelector + "_Topics");
        var gallery = this.Y.apm;
        if(this._treeDiv && gallery.TreeView) {
            var i, root, topic, item, nodeText, nodeTitle, temp, selectedNode, originalLabel;

            this._tree = new gallery.TreeView(this._treeDiv.get('id'));

            for(i in this.data.js.topics) {
                if(this.data.js.topics.hasOwnProperty(i)) {
                    topic = this.data.js.topics[i];
                    if(topic.level <= this.data.attrs.depth_limit) {
                        root = (topic.parentID) ? this._tree.getNodeByProperty("clusterID", topic.parentID) : this._tree.getRoot();
                        //generate the label & title
                        nodeText = originalLabel = "<span class='rn_Title'>" + this.Y.Escape.html(topic.summary) + "</span>";
                        nodeTitle = RightNow.Text.sprintf(RightNow.Interface.getMessage("PCT_S_ANSWER_RESULTS_LBL"), topic.leafCount);
                        //generate meter
                        if(this.data.attrs.display_answer_relevancy && topic.weight)
                            nodeText += this._createRelevancyMeter(topic.weight);
                        if(topic.matchedLeaves) {
                            temp = (topic.matchedLeaves > 1) ? RightNow.Interface.getMessage("PCT_S_MATCHES_LBL") : RightNow.Interface.getMessage("PCT_S_MATCH_LBL");
                            nodeText += "<span class='rn_Matches'>" + RightNow.Text.sprintf(temp, topic.matchedLeaves) + "</span>";
                        }
                        else {
                            nodeText += "<span class='rn_ScreenReaderOnly'> " + nodeTitle + "</span>";
                            nodeText = nodeText.replace(/(class\s*=\s*'rn_Title')/, "$1 title='" + nodeTitle + "'");
                        }
                        //create node and set its cluster id
                        item = new gallery.MenuNode(nodeText, root);
                        item.clusterID = topic.clusterID;
                        item.summary = topic.summary;
                        item.originalLabel = originalLabel;
                        item.titleText = nodeTitle;
                        item.renderHidden = true;
                        if(topic.display === 'noDisplay') {
                            //hide the node if there's no relevant search results
                            item.className = "rn_Hidden";
                        }
                        else if(topic.display === 'bestMatch' || item.clusterID === parseInt(this.data.js.filters.default_value, 10)) {
                            selectedNode = item;
                        }
                    }
                }
            }
            this._tree.subscribe("enterKeyPressed", function(e){this._selectNode({"node": e});}, null, this);
            this._tree.subscribe("clickEvent", this._selectNode, this);
            this._tree.subscribe("expandComplete", this._viewportToExpandedNode, this);
            this._tree.render();
            if(selectedNode) {
                this._currentNode = selectedNode.clusterID;
                this._selectNode({"node": selectedNode});
            }

            if(this.data.attrs.label_description !== "")
                this._tree.root.getEl().setAttribute("aria-labelledby", "rn_" + this.instanceID + "_Label");
            if(!RightNow.Event.isHistoryManagerFragment())
                this._setLoading();
        }
    },

    /**
     * Executed when topic link is clicked on.
     * @param {object} nodeEvent node click event
     */
    _selectNode: function(nodeEvent) {
        if(this._currentNode !== nodeEvent.node.clusterID) {
            if(this._currentNode) {
                this._tree.getNodeByProperty("clusterID", this._currentNode).getLabelEl()
                    .removeChild(document.getElementById(this.baseDomID + "_SelectedAlt"));
            }
            this._currentNode = nodeEvent.node.clusterID;
            this._eo.filters.data.val = nodeEvent.node.clusterID;
            this._nodeSelected = true;
            this.searchSource().fire('search', this._eo);
        }
        this._removeSelectionIndicator(this._tree.getRoot().children);
        if(nodeEvent.node.parent && !nodeEvent.node.parent.expanded) {
            //expand node's ancestors if they haven't been expanded
            var current = nodeEvent.node;
            while(current) {
                current.expand();
                current = current.parent;
            }
        }
        this.Y.one(nodeEvent.node.getEl()).one('*').addClass('rn_Selected');
        nodeEvent.node.getLabelEl().innerHTML += "<span id = 'rn_" + this.instanceID + "_SelectedAlt' class='rn_ScreenReaderOnly'> " + RightNow.Interface.getMessage("SELECTED_UC_LBL") + "</span>";
    },

    /**
     * Called when a search is initiated.
     * @return {object} Event object for the search
     */
    _onSearch: function() {
        if (!this._nodeSelected) {
            // Another widget triggered a search: reset the filter
            this._eo.filters.data.val = 0;
        }

        return this._eo;
    },

    /**
     * Event handler when returning from ajax data request
     * @param {string} type Event name
     * @param {array} args Event arguments
     */
    _onReportResponse: function(type, args) {
        var origEvtObjData = RightNow.Event.getDataFromFiltersEventResponse(args, this.data.attrs.filter_name, this.data.attrs.report_id),
            selected;

        if(!origEvtObjData) {
            //default, deselected, collapsed tree
            this._tree.collapseAll();
            this._removeSelectionIndicator(this._tree.getRoot().children);
            this._currentNode = null;
        }
        else if(origEvtObjData && this._currentNode !== origEvtObjData.val) {
            //restore history manager state: expand node's parent(s) and select node
            this._currentNode = origEvtObjData.val;
            selected = this._tree.getNodeByProperty("clusterID", origEvtObjData.val);
            if(selected) {
                this._selectNode({"node": selected});
            }
            else {
                this._removeSelectionIndicator(this._tree.getRoot().children);
                this._tree.collapseAll();
            }
        }

        var topics = args[0].data.topics;
        if(!topics || topics.length === 0) {
            //nothing was returned from report_model: that means no searching is going on.
            //redisplay all nodes (in case some subset was originally shown)
            this._displayAllNodes(this._tree.getRoot().children);
            this._tree.render();
            if(this._currentNode) {
                this._selectNode({"node": this._tree.getNodeByProperty("clusterID", this._currentNode)});
            }
        }
        else {
            //update results & relevancy scores
            this._setLoading(true);
            var i, item, tempLabel, nodeText, nodeTitle;
            for(i in topics) {
                if(topics.hasOwnProperty(i)) {
                    item = this._tree.getNodeByProperty("clusterID", topics[i].clusterID);
                    if(item && topics[i].display === 'noDisplay') {
                        //hide node; don't bother to update its label
                        item.className = "rn_Hidden";
                    }
                    else if(item) {
                        item.className = "";
                        //refresh the label & title
                        nodeText = "<span class='rn_Title'>" + item.summary + "</span>"; //get orig. summary text
                        nodeTitle = RightNow.Text.sprintf(RightNow.Interface.getMessage("PCT_S_ANSWER_RESULTS_LBL"), topics[i].leafCount);
                        //generate meter
                        if(this.data.attrs.display_answer_relevancy && topics[i].weight)
                            nodeText += this._createRelevancyMeter(topics[i].weight);
                        if(topics[i].matchedLeaves) {
                            tempLabel = (topics[i].matchedLeaves > 1) ? RightNow.Interface.getMessage("PCT_S_MATCHES_LBL") : RightNow.Interface.getMessage("PCT_S_MATCH_LBL");
                            nodeText += "<span class='rn_Matches'>" + RightNow.Text.sprintf(tempLabel, topics[i].matchedLeaves) + "</span>";
                        }
                        else {
                            nodeText += "<span class='rn_ScreenReaderOnly'> " + nodeTitle + "</span>";
                            nodeText = nodeText.replace(/(class\s*=\s*'rn_Title')/, "$1 title='" + nodeTitle + "'");
                        }
                        item.label = nodeText;
                        if(topics[i].display === 'bestMatch') {
                            selected = item;
                        }
                    }
                }
            }
            this._tree.render();
            if(selected && !this._nodeSelected) {
                //we're told which node to pre-select
                this._currentNode = selected.clusterID;
                this._selectNode({"node": selected});
            }
            else if(origEvtObjData) {
                //otherwise check if node that was selected before the search was performed is still available: re-select it
                selected = this._tree.getNodeByProperty("clusterID", origEvtObjData.val);
                if(selected && selected.className !== "rn_Hidden"){
                    this._selectNode({"node": selected});
                }
            }
        }
        this._nodeSelected = false;
        this._setLoading();
    },

/****************************
 * Internal utility functions
 ***************************/
    /**
     * Executed when a node has finished expanding.
     * Ensures that the expanded node remains in view.
     */
    _viewportToExpandedNode: function() {
        var node = this.Y.one(this._tree.getNodeByProperty('clusterID', this._currentNode).getEl());
        if (node.get('viewportRegion').top > node.getY()) {
            node.scrollIntoView();
        }
    },

    /**
     * Toggles display of widget content and loading icon.
     * @param {boolean} startLoading T to hide widget content and display loading icon; F to
     *               display widget content and hide loading icon
     */
    _setLoading: function(startLoading) {
        if(!this._loadingDiv && !this._widgetDiv) {
            this._loadingDiv = this.Y.one(this.baseSelector + "_Loading");
            this._widgetDiv = this.Y.one(this.baseSelector);
        }
        if(startLoading) {
            this._widgetDiv.setStyle('height', this._treeDiv.get('offsetHeight') + 'px');
            this._loadingDiv.addClass('rn_Loading');
            RightNow.UI.hide(this._treeDiv);
        }
        else {
            this._loadingDiv.removeClass('rn_Loading');
            RightNow.UI.show(this._treeDiv);
            this._widgetDiv.setStyle('height', 'auto');
        }
    },

    /**
     * Removes rn_Hidden CSS class from all nodes and resets
     * all node titles back to original topic summary.
     * @param {object} nodes List of nodes to start processing
     */
    _displayAllNodes: function(nodes) {
        for(var i in nodes) {
            if(nodes.hasOwnProperty(i)) {
                nodes[i].className = "";
                nodes[i].label = nodes[i].originalLabel;
                this.Y.one(nodes[i].getLabelEl()).all('span.rn_Title').each(function(node) {
                    node.set('title', nodes[i].titleText);
                });
                if(nodes[i].children) {
                    this._displayAllNodes(nodes[i].children);
                }
            }
        }
    },

    /**
     * Removes rn_Selection CSS class from all nodes.
     * @param {object} nodes List of nodes to start processing
     */
    _removeSelectionIndicator: function(nodes) {
        for(var i in nodes) {
            if(nodes.hasOwnProperty(i)) {
                this.Y.one(nodes[i].getEl()).one('*').removeClass('rn_Selected');
                if(nodes[i].expanded) {
                    this._removeSelectionIndicator(nodes[i].children);
                }
            }
        }
    },

    /**
     * Creates relevancy meter html.
     * @param {number} value The percentage relevancy
     * @return string the HTML
     */
    _createRelevancyMeter: function(value) {
        value = parseInt(value, 10);
        return new EJS({text: this.getStatic().templates.relevancyMeter}).render({
            value: value,
            text: RightNow.Text.sprintf(RightNow.Interface.getMessage("PCT_S_PCT_RELEVANCY_LBL"), value)
        });
    }
});
