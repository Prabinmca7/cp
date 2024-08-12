RightNow.Widgets.ProductCatalogSearchFilter = RightNow.SearchFilter.extend({
    overrides: {
        constructor: function() {
            this.parent();

            this._getFiltersRequest.lastInstance = this._getFiltersRequest.lastInstance || {};
            this._currentIndex = 0;
            this._noValueNodeIndex = 0;

            this._displayField = this.Y.one(this.baseSelector + "_ProductCatalog_Button");

            if(!this._displayField) return;

            this._displayFieldVisibleText = this.Y.one(this.baseSelector + "_Button_Visible_Text");
            this._accessibleView = this.Y.one(this.baseSelector + "_Links");
            this._outerTreeContainer = this.Y.one(this.baseSelector + "_TreeContainer");
            this._treeNode = this.Y.one(this.baseSelector + "_Tree");

            this.searchSource(this.data.attrs.report_id)
                .on('search', this._getFiltersRequest, this)
                .on('response', this._onReportResponse, this)
                .on('reset', this._onResetRequest, this);

            RightNow.Event.subscribe("evt_menuFilterProductCatalogGetResponse", this._getSubLevelResponse, this);
            RightNow.Event.subscribe("evt_accessibleProductCatalogTreeViewGetResponse", this._getAccessibleTreeViewResponse, this);

            //toggle panel on/off when button is clicked
            this._displayField.on("click", this._toggleProductCatalogPicker, this);
            this.Y.one(this.baseSelector + "_LinksTrigger").on("click", this._toggleAccessibleView, this);

            this._initializeFilter();
            this._buildMenuPanel();

            if(this.data.js.hierData[0].length)
                this._buildTree();
        }
    },

    /**
     * Builds panel for the treeview menu.
     */
    _buildMenuPanel: function() {
        this._panel = new this.Y.Panel({
            srcNode: this._outerTreeContainer.removeClass('rn_Hidden'),
            width: 300,
            visible: false,
            render: this.Y.one(this.baseSelector),
            headerContent: null,
            buttons: [],
            hideOn: [{eventName: 'clickoutside'}],
            align: {node: this._displayField, points: [this.Y.WidgetPositionAlign.TL, this.Y.WidgetPositionAlign.BL]},
            zIndex: 1000
        });

        this._panel.on('visibleChange', function(e) {
            // show
            if (e.newVal) {
                this._treeNode.setStyle("display", "block");
            }
            // hide
            else {
                this._treeNode.setStyle("display", "none");
            }
        }, this);
    },

    /**
    * Constructs the YUI Treeview widget for the first time with initial data returned
    * from the server. Pre-selects and expands data that is expected to be populated.
    * @param {boolean} forceRebuild Whether to rebuild the true if it's already been built
    */
    _buildTree: function(forceRebuild) {
        if (this._tree && !forceRebuild) return;

        var gallery = this.Y.apm,
            treeDiv = document.getElementById("rn_" + this.instanceID + "_Tree");

        if(this._treeNode && gallery.TreeView)
        {
            this._tree = new gallery.TreeView(this._treeNode.get('id'));
            this._tree.setDynamicLoad(RightNow.Event.createDelegate(this, this._getSubLevelRequest));
            this._tree.subscribe('focusChanged', function(e) {
                if (e.newNode) {
                    this._treeCurrentFocus = e.newNode;
                }
                else if (e.oldNode) {
                    this._treeCurrentFocus = e.oldNode;
                }
            }, this);
            //if there is no confirm button tab should close the panel
            //but when there is tab should be ignored and by default take you to the confirm button
            if(!this.data.attrs.show_confirm_button_in_dialog) {
                this.Y.one(this._tree.getEl()).on('key', function(ev){
                    var currentNode = this._treeCurrentFocus;
                    if(currentNode.href) {
                        if(currentNode.target) {
                            window.open(currentNode.href, currentNode.target);
                        }
                        else {
                            window.location(currentNode.href);
                        }
                    }
                    else {
                        currentNode.toggle();
                    }
                    this._tree.fireEvent('enterKeyPressed', currentNode);
                    ev.halt();
                }, 'tab', this);
            }

            //Load all of the default data into the tree
            var hasDefaultValue = false,
                hierData = this.data.js.hierData,
                scope = this,
                insertNodes = function(nodeList, root) {
                    var dataNode, node, childNodes = [], i;
                    for(i = 0; i < nodeList.length; i++) {
                        dataNode = nodeList[i];
                        node = new gallery.MenuNode(scope.Y.Escape.html(dataNode.label), root);
                        node.href = 'javascript:void(0)';
                        node.hierValue = dataNode.id;

                        if(!dataNode.hasChildren) {
                            node.dynamicLoadComplete = true;
                            node.iconMode = 1;
                            node.isLeaf = true;
                        }

                        if(dataNode.selected) {
                            hasDefaultValue = true;
                            scope._currentIndex = node.index;
                        }

                        //Child processing must be deferred until after root.loadComplete
                        if(dataNode.hasChildren && hierData[dataNode.id]) {
                            childNodes.push({children: hierData[dataNode.id], parent: node});
                        }
                    }
                    //Let YUI know that all of the (direct) children of this root have been loaded
                    root.loadComplete();
                    for(i = 0; i < childNodes.length; i++) {
                        insertNodes(childNodes[i].children, childNodes[i].parent);
                    }
                };

            //Recursively generate the tree from the tree-like data structure

            insertNodes(hierData[0], this._tree.getRoot());

            var noValueNode = this._tree.getRoot().children[0];
            noValueNode.isLeaf = true;
            this._noValueNodeIndex = noValueNode.index;

            this._tree.subscribe("enterKeyPressed", this._enterPressed, this);
            if(this.data.attrs.show_confirm_button_in_dialog)
            {
                var confirmButton = this.Y.one(this.baseSelector + '_ProductCatalog_ConfirmButton'),
                cancelButton = this.Y.one(this.baseSelector + '_ProductCatalog_CancelButton');
                confirmButton.detach('click');
                cancelButton.detach('click');
                cancelButton.detach('keydown');
                confirmButton.on('click', function(){
                    this._selectNode({node: this._treeCurrentFocus});
                }, this);
                cancelButton.on('click', function() {
                    this._panel.hide();
                }, this);
                cancelButton.on('key', function(ev) {
                    !ev.shiftKey && this._toggleProductCatalogPicker();
                }, 'tab', this);
            }
            else
            {
                this._tree.subscribe('clickEvent', this._selectNode, this);
            }
            //scroll container to 20px above expanded node
            this._tree.subscribe('expandComplete', function(e) {
                this._treeNode.set('scrollTop', this.Y.one('#' + e.contentElId).ancestor('.ygtvitem').get('offsetTop'));
            }, this);
            this._tree.collapseAll();
            this.Y.one(treeDiv.firstChild).setAttribute('aria-label', this.data.attrs.label_nothing_selected);
            if(this.data.attrs.show_confirm_button_in_dialog)
                this._outerTreeContainer.setStyle("display", "block");
            this._treeNode.setStyle("display", "block");
            if(hasDefaultValue)
                this._displaySelectedNodesAndClose(false);
        }
    },

    /**
    * Creates and displays a dialog consisting of an accessible list of items.
    */
    _displayAccessibleDialog: function()
    {
        //build tree for the first time
        if(!this._tree)
            this._buildTree();
        // If the dialog doesn't exist, create it.  (Happens on first click).
        if(!(this._dialog))
        {
            // Set up buttons with handler functions.
            var handleDismiss = function()
            {
                this.hide();
            };

            this._buttons = [ { text: RightNow.Interface.getMessage("CANCEL_CMD"), handler: handleDismiss, isDefault: false} ];
            // Create the dialog.
            RightNow.UI.show(this._accessibleView);
            this._dialog = RightNow.UI.Dialog.actionDialog(this.data.attrs.label_nothing_selected, this._accessibleView, {"buttons": this._buttons});
            this._dialog.after('visibleChange', function(e)
            {
                if (!e.newVal)
                {
                    // When the dialog closes, focus on the main selector button on the page.
                    this._displayField.focus();
                }
            }, this);
        }
        else
        {
            var currentlySelectedSpan = document.getElementById(this.baseDomID + "_IntroCurrentSelection");
            var introLink = document.getElementById(this.baseDomID + "_Intro");
            if(currentlySelectedSpan && introLink)
            {
                var currentNode = this._tree.getNodeByIndex(this._currentIndex);
                if(!currentNode)
                {
                    currentNode = {};
                    currentNode.hierValue = 0;
                }
                var localInstanceID = this.instanceID;
                introLink.onclick = function(){document.getElementById("rn_" + localInstanceID + "_AccessibleLink_" + currentNode.hierValue).focus();};
                var selectedNodes = this._getSelectedNodesMessage();
                selectedNodes = selectedNodes[0] ? selectedNodes.join(", ") : this.data.attrs.label_all_values;
                currentlySelectedSpan.innerHTML = RightNow.Text.sprintf(RightNow.Interface.getMessage("SELECTION_PCT_S_ACTIVATE_LINK_JUMP_MSG"), selectedNodes);
            }
        }

        this._dialog.show();
    },

    /**
    * Toggles accessible view.
    */
    _toggleAccessibleView: function()
    {
        if(this._flatTreeViewData)
        {
            this._displayAccessibleDialog();
        }
        else
        {
            this._eo.data.isSearchRequest = true;
            RightNow.Event.fire("evt_accessibleProductCatalogTreeViewRequest", this._eo);
        }
    },

    /**
    * Listens to response from the server and constructs an HTML tree according to
    * the flat data structure given.
    * @param e String Event name
    * @param args Object Event arguments
    */
    _getAccessibleTreeViewResponse: function(e, args)
    {
        var evtObj = args[0];
        this._flatTreeViewData = evtObj.data.accessibleLinks;
        //add the No Value node
        var noValue = {0: this.data.attrs.label_all_values,
                        1: 0,
                        hier_list: 0,
                        level: 0};
        if(!this.Y.Lang.isArray(this._flatTreeViewData))
        {
            //convert object to array because objects don't support unshift drop off the nonNumeric values
            var tempArray = [];
            for(var i in this._flatTreeViewData)
            {
                if(!isNaN(parseInt(i, 10)))
                    tempArray[i] = this._flatTreeViewData[i];
            }

            this._flatTreeViewData = tempArray;
        }
        this._flatTreeViewData.unshift(noValue);
        var htmlList = "<p><a href='javascript:void(0)' id='rn_" + this.instanceID + "_Intro'" +
                "onclick='document.getElementById(\"rn_" + this.instanceID + "_AccessibleLink_" + noValue[1] +
                "\").focus();'>" + RightNow.Text.sprintf(RightNow.Interface.getMessage("NAVIGATE_PCT_S_ITEMS_LINKS_DEPTH_MSG"), this.data.attrs.label_input) +
                " <span id='rn_" + this.instanceID + "_IntroCurrentSelection'>" + RightNow.Text.sprintf(RightNow.Interface.getMessage("SELECTION_PCT_S_ACTIVATE_LINK_JUMP_MSG"), noValue[0]) + "</span></a></p>",
            previousLevel = -1;
        //loop through each hier_item to figure out nesting structure
        for(i in this._flatTreeViewData)
        {
            if(this._flatTreeViewData.hasOwnProperty(i))
            {
                var item = this._flatTreeViewData[i];
                //print down html
                if(item.level > previousLevel)
                    htmlList += "<ol>";

                //print up html
                while(item.level < previousLevel)
                {
                    htmlList += "</li></ol>";
                    previousLevel--;
                }
                //print across html
                if(item.level === previousLevel)
                    htmlList += "</li>";

                if(item[3])
                {
                    htmlList += '<li><div id="rn_' + this.instanceID + '_AccessibleLink_' + item[1] + '_AccessibleFolder">' +
                        '<span class="rn_ScreenReaderOnly">' + this.data.attrs.label_level + ' ' + (item.level + 1) + '</span>' + item[0] +
                        '</div>';
                }
                else
                {
                    htmlList += '<li><a href="javascript:void(0)" id="rn_' + this.instanceID + '_AccessibleLink_' + item[1] + '" class="rn_AccessibleHierLink" data-hierList="' + item.hier_list + '">' +
                        '<span class="rn_ScreenReaderOnly">' + this.data.attrs.label_level + ' ' + (item.level + 1) + '</span>' + item[0] +
                        '</a>';
                }

                previousLevel = item.level;
            }
        }
        //close list
        for(i = previousLevel; i >= 0; --i)
            htmlList += "</li></ol>";

        htmlList += "<div id='rn_" + this.instanceID + "_AccessibleErrorLocation'></div>";

        this._accessibleView.setHTML(htmlList).all('a.rn_AccessibleHierLink').on('click', this._accessibleLinkClick, this);
        this._displayAccessibleDialog();
    },

    /**
    * Executed when a tree item is selected from the accessible view.
    * @param {object} e YUI event facade
    */
    _accessibleLinkClick: function(e)
    {
        //basically transfer this click to the visible control
        //find the node in this._tree. If it's not there, expand its parents until it is there.
        //call click on that node.
        this._expandAndCreateNodes(e.target.getAttribute("data-hierList").split(","));
        return false;
    },

    /**
    * Shows / hides Panel containing TreeView widget
    * Shows when user clicks button and the Panel is hidden.
    * Hides when user selects a node or the Panel loses focus.
    * @param {Object} e Click event
    */
    _toggleProductCatalogPicker: function(e)
    {
        //build tree for the first time
        if(!this._tree)
            this._buildTree();
        //show panel
        if(!this._panel.get("visible"))
        {
            this._panel.align().show();
            //focus on either the previously selected node or the first node
            var currentNode = this._tree.getNodeByIndex(this._currentIndex) || this._tree.getRoot().children[0];
            if(currentNode && currentNode.focus)
            {
                currentNode.focus();
            }
        }
    },

    /**
    * Returns an array of all the labels of the selected nodes
    * @return array Array of labels
    */
    _getSelectedNodesMessage: function()
    {
        return this._getPropertyChain("label");
    },

    /**
    * Navigates up from the selected node, generating an array
    * consisting of the values of the property passed in.
    * @param {String} property The property you wish to access.
    * @return array Array of values
    */
    _getPropertyChain: function(property)
    {
        property = property || "label";
        //work back up the tree from the selected node
        this._currentIndex = this._currentIndex || 1;
        var hierValues = [],
              currentNode = this._tree.getNodeByIndex(this._currentIndex);
        while(currentNode && !currentNode.isRoot())
        {
            hierValues.push(currentNode[property]);
            currentNode = currentNode.parent;
        }
        return hierValues.reverse();
    },

    /**
    * Displays the hierarchy of the currently selected node up to it's root node,
    * hides the panel, and focuses on the selection button (if directed).
    * @param focus Boolean Whether or not the button should be focused
    */
    _displaySelectedNodesAndClose: function(focus)
    {
        var hierValues, description, descText;

        this._eo.data.value = this._currentIndex;
        // event to notify listeners of selection
        this._eo.data.hierChain = this._getPropertyChain('hierValue');
        RightNow.Event.fire("evt_productCatalogFilterSelected", this._eo);
        delete this._eo.data.hierChain;

        this._panel.hide();
        this._displayField.setAttribute("aria-busy", "true");
        if(this._currentIndex <= this._noValueNodeIndex)
        {
            this._displayFieldVisibleText.setHTML(this.data.attrs.label_nothing_selected);
            descText = this.data.attrs.label_nothing_selected;
        }
        else
        {
            hierValues = this._getSelectedNodesMessage().join("<br>");
            this._displayFieldVisibleText.setHTML(hierValues);
            descText = this.data.attrs.label_screen_reader_selected + hierValues;
        }
        description = this.Y.one(this.baseSelector + "_TreeDescription");
        if(description)
           description.setHTML(descText);

        this._displayField.setAttribute("aria-busy", "false");

        //also close the dialog if it exists
        if(this._dialog && this._dialog.get("visible"))
            this._dialog.hide();

        //don't focus if the accessible dialog is in use or was in use during this page load.
        //the acccessible view and the treeview shouldn't really be mixed
        if(focus && this._displayField.focus && !this._dialog)
            try{this._displayField.focus();} catch(e){}
    },

    /**
    * Handler for when enter was pressed while focused on a node
    * Emulates mouse click
    * @param {Event} keyEvent The node's enterPressed event.
    */
    _enterPressed: function(keyEvent)
    {
        this._selectNode({node:keyEvent.details[0]});
    },

    /**
    * Selected a node by clicking on its label
    * (as opposed to expanding it via the expand image).
    * @param clickEvent Event The node's click event.
    */
    _selectNode: function(clickEvent)
    {
        var selectedNode = clickEvent.node;
        this._currentIndex = selectedNode.index;
        this._selected = true;
        this._getFiltersRequest.lastInstance = this.baseDomID;
        this._selectNode._selectedWidget = this.data.info.w_id;
        if((clickEvent.node.expanded || this._noValueNodeIndex === clickEvent.node.index))
        {
            this._eo.data.level = clickEvent.node.depth + 1;
            //setup filter data for report's filter request
            if(this._eo.data.level !== this._eo.filters.data[0].length)
            {
                //filter's been reset or user skipped a level: make sure to always pass correct values
                this._eo.filters.data[0] = [];
                var currentNode = clickEvent.node;
                while(currentNode && !currentNode.isRoot())
                {
                    this._eo.filters.data[0][currentNode.depth] = currentNode.hierValue;
                    currentNode = currentNode.parent;
                }
            }
            else
            {
                this._eo.filters.data[0][this._eo.data.level - 1] = clickEvent.node.hierValue || this._eo.data.value;
                for(var i = this._eo.data.level; i < this._eo.filters.data[0].length; i++)
                    delete this._eo.filters.data[0][i];
            }
        }
        else
        {
            this._getSubLevelRequest(clickEvent.node);
        }

        if(selectedNode.isLeaf) {
            this._displaySelectedNodesAndClose(true);
        }

        if(clickEvent.event)
            clickEvent.event.preventDefault();

        return false;
    },

    /**
     * Event handler when a node is expanded.
     * Requests the next sub-level of items from the server.
     * @param expandingNode Event The node that's expanding
     */
    _getSubLevelRequest: function(expandingNode)
    {
        //only allow one node at-a-time to be expanded
        if (this._nodeBeingExpanded) return;

        this._nodeBeingExpanded = true;
        this._eo.data.level = expandingNode.depth + 1;
        this._eo.data.value = expandingNode.hierValue;
        this._eo.data.label = expandingNode.label;
        this._eo.data.isSearchRequest = true;

        //When the show_confirm_button_in_dialog attribute is set, we don't want to explicity change the users selection when they drill down
        //into an element. If we did that, the user wouldn't be able to use the cancel button correctly. We just want to set a
        //temporary value which we can use in the response event. If this attribute isn't set, keep the behavior the same as before.
        if(this.data.attrs.show_confirm_button_in_dialog)
            this._requestedIndex = expandingNode.index;
        else
            this._currentIndex = expandingNode.index;

        //static variable for different widget instances but the same data type
        this._getSubLevelRequest._origRequest = expandingNode.hierValue;

        this._eo.data.reset = false; //whether data should be reset for the current level

        //setup filter data for report's filter request
        if(this._eo.data.level !== this._eo.filters.data[0].length)
        {
            //filter's been reset or user skipped a level: make sure to always pass correct values
            this._eo.filters.data[0] = [];
            var currentNode = expandingNode;
            while(currentNode && !currentNode.isRoot())
            {
                this._eo.filters.data[0][currentNode.depth] = currentNode.hierValue;
                currentNode = currentNode.parent;
            }
        }
        else
        {
            this._eo.filters.data[0][this._eo.data.level - 1] = this._eo.data.value;
            for(var i = this._eo.data.level; i < this._eo.filters.data[0].length; i++)
                delete this._eo.filters.data[0][i];
        }
        if(!expandingNode.dynamicLoadComplete) {
            RightNow.Event.fire("evt_menuFilterRequestProductCatalog", this._eo);
        }

        this._nodeBeingExpanded = false;
    },

    /**
     * Event handler when report has been updated
     * @param {string} type Event name
     * @param {array} args Event arguments
     */
    _onReportResponse: function(type, args)
    {
        var data = RightNow.Event.getDataFromFiltersEventResponse(args, this.data.js.searchName);
        this._getFiltersRequest.cachedWidgetHier = {};
        if(data && data[0] && data[0].length) {
            this._buildTree();
            //remove empties
            if(typeof data[0] === "string")
                data[0] = data[0].split(",");
            var finalData = RightNow.Lang.arrayFilter(data[0]);
            this._expandAndCreateNodes(finalData);
            this._eo.filters.data[0] = finalData;
            this._lastSearchValue = finalData.slice(0);
            if(this._eo.filters.data.reconstructData) {
                this._eo.filters.data.level = this._eo.filters.data.reconstructData.level;
                this._eo.filters.data.label = this._eo.filters.data.reconstructData.label;
            }
        }
        else {
            //always set back to empty array since search eventbus may have inadvertantly set it to null...
            this._eo.filters.data[0] = [];
            if(this._tree) {
                //going from some selection back to no selection
                this._currentIndex = this._noValueNodeIndex;
                this._displaySelectedNodesAndClose();
            }
        }
    },

    /**
    * Used to set the tree to a specific state; programatically expands nodes
    * in order to build up the hierarchy tree to the specified array of IDs.
    * @param {array} hierArray IDs denoting the specified prod/cat chain
    */
    _expandAndCreateNodes: function(hierArray)
    {
        var i = hierArray.length - 1,
            currentNode = null;

        //walk up the chain looking for the first available node
        while(!currentNode && i >= 0) {
            currentNode = this._tree.getNodeByProperty("hierValue", parseInt(hierArray[i], 10));
            i--;
        }
        //now currentNode should be something:
        //already selected? return
        if(this._currentIndex === currentNode.index)
        {
            //close the basic view if it is in use
            if(this._dialog && this._dialog.get("visible"))
                this._dialog.hide();
            return;
        }
        //if we already have the one selected, then we can go ahead and select it.
        i++;
        if(this._noValueNodeIndex === currentNode.index || currentNode.hierValue === hierArray[hierArray.length - 1]) {
            this._selectNode({node: currentNode});
        }
        else {
            var onExpandComplete = function(expandingNode) {
                if(expandingNode.nextToExpand) {
                    var nextNode = this._tree.getNodeByProperty("hierValue", parseInt(expandingNode.nextToExpand, 10));
                    if(nextNode) {
                        nextNode.nextToExpand = hierArray[++i];
                        nextNode.expand();
                    }
                }
                else if(i === hierArray.length) {
                    //we don't want to subscribe to this more than once
                    this._tree.unsubscribe("expandComplete", onExpandComplete, null);
                    expandingNode.expanded = false;
                    this._selectNode({node: expandingNode});
                }
                return true;
            };
            //walk back down to their selection from here expanding as you go
            this._tree.subscribe("expandComplete", onExpandComplete, this);
            currentNode.nextToExpand = hierArray[++i];
            currentNode.expand();
        }
    },

    /**
     * Event handler when returning from ajax data request.
     * @param type String Event name
     * @param args Object Event arguments
     */
    _getSubLevelResponse: function(type, args)
    {
        var evtObj = args[0];

        //Check if this widget is supposed to update
        if(evtObj.filters.report_id !== this.data.attrs.report_id)
            return;

        var hierLevel = evtObj.data.level,
            hierData = evtObj.data.hier_data,
            redisplaySelectedNode = false,
            currentRoot,
            tempNode;

        this._buildTree();

        if(this._getSubLevelRequest._origRequest)
        {
            //get the node by its hierValue
            currentRoot = this._tree.getNodeByProperty("hierValue", this._getSubLevelRequest._origRequest);
            if(currentRoot.index !== (this.data.attrs.show_confirm_button_in_dialog ? this._requestedIndex : this._currentIndex))
            {
                this._currentIndex = currentRoot.index;
                redisplaySelectedNode = true;
            }
        }

        //add the new nodes to the currently selected node
        if(currentRoot && !currentRoot.dynamicLoadComplete)
        {
            for(var i = 0; i < hierData.length; i++)
            {
                tempNode = new this.Y.apm.MenuNode(this.Y.Escape.html(hierData[i].label), currentRoot, false);
                tempNode.hierValue = hierData[i].id;
                tempNode.href = 'javascript:void(0);';
                if(!hierData[i].hasChildren)
                {
                    //if it doesn't have children then turn off the +/- icon
                    //and notify that the node is already loaded
                    tempNode.dynamicLoadComplete = true;
                    tempNode.iconMode = 1;
                    tempNode.isLeaf = true;
                }
            }
            currentRoot.loadComplete();
        }
        //leaf node was expanded : display and close
        if(hierData.length === 0 && !this._selected)
        {
            this._displaySelectedNodesAndClose();
        }
        //node was selected : its already selected and closed
        else if(this._selected)
        {
            this._selected = false;
        }
        else if(redisplaySelectedNode && this._selectNode._selectedWidget)
        {
            this._selectNode._selectedWidget = null;
            this._displaySelectedNodesAndClose();
        }
    },

    /**
     * When a search is triggered on the page, returns the filter's value if it matches the current report ID
     * @param {string} type Event name
     * @param {array} args Event arguments
     * @return {object} EventObject
     */
    _getFiltersRequest: function(type, args)
    {
        //If there are multiple instances of prodCatSF for one report, we need to sync them.
        this._getFiltersRequest.lastInstance = this._getFiltersRequest.lastInstance || null;
        this._getFiltersRequest.cachedWidgetHier = this._getFiltersRequest.cachedWidgetHier || {};
        var filterName = (this._eo.filters.searchName || "") + this.data.attrs.report_id,
            filterKey = filterName + this.baseDomID,
            mostRecentFilterKey = filterName + this._getFiltersRequest.lastInstance,
            cachedHier = this._getFiltersRequest.cachedWidgetHier[mostRecentFilterKey];
        this._eo.filters.data.reconstructData = [];

        //The tree is built and a value is selected, construct the data array.
        if(this._tree && this._currentIndex && this._currentIndex !== this._noValueNodeIndex)
        {
            var currentNode = this._tree.getNodeByIndex(this._currentIndex),
                hierValues,
                level;

            this._eo.data.level = currentNode.depth + 1;
            this._eo.data.label = currentNode.label;
            this._eo.data.value = currentNode.hierValue;

            while(currentNode && !currentNode.isRoot())
            {
                level = currentNode.depth + 1;
                hierValues = this._eo.filters.data[0].slice(0, level).join(",");
                this._eo.filters.data.reconstructData.push({"level" : level, "label" : currentNode.label, "hierList" : hierValues});
                currentNode = currentNode.parent;
            }
            this._eo.filters.data.reconstructData.reverse();
            if (cachedHier && filterKey !== mostRecentFilterKey) {
                this._eo.filters.data = cachedHier;
            }
            else {
                this._getFiltersRequest.cachedWidgetHier[filterKey] = RightNow.Lang.cloneObject(this._eo.filters.data);
            }
        }
        //Widget isn't completely initialized, but may still have a cached value of another widget on the page
        else if(cachedHier)
        {
            this._eo.filters.data = cachedHier;
            this._eo.data.value = cachedHier[0][0];
        }
        //Widget has nothing, just send empty data.
        else
        {
            this._eo.filters.data[0] = [];
            this._eo.data.value = 0;
        }
        this._lastSearchValue = this._eo.filters.data[0].slice(0);
        return this._eo;
    },

    /**
     * Responds to the filter reset event which can be fired in three different ways:
     * 1) Using the keyword 'all' - Fired by the advanced search dialog, should cause the last search to be reset
     * 2) Using the specific filterName e.g. 'p' - Fired by the display search filters widget, should revert to no value
     * 3) Using null args parameter - Fired by searchFilter.js when history is restored to init state, should revert to widget init state.
     * @param {string} type Event name
     * @param {array} args Event arguments
     */
    _onResetRequest: function(type, args)
    {
        args = args[0];
        if(this._tree && (!args || args.data.name === 'all' || args.data.name === this.data.js.searchName))
        {
            //If all filters are reseting, go to prior search (typically caused by exiting the advanced search dialog)
            if(args && args.data.name === "all" && this._lastSearchValue)
            {
                this._eo.filters.data[0] = this._lastSearchValue;
                this._currentIndex = this._tree.getNodeByProperty("hierValue", this._lastSearchValue[this._lastSearchValue.length - 1]).index;
            }
            //If only this filter is reseting, go back to the initial value
            else
            {
                //If the history is going back to the initial state, use initial
                //otherwise empty it out because displaySearchFilter is removing it entirely
                if(!args)
                    this._eo.filters.data = [(this.data.js.initial) ? this.data.js.initial : []];
                else
                    this._eo.filters.data[0] = [];
                this._lastSearchValue = this._eo.filters.data[0].slice(0);
                this._currentIndex = this._tree.getNodeByProperty("hierValue", this._lastSearchValue[this._lastSearchValue.length - 1]).index;
                this._getFiltersRequest.cachedWidgetHier = {};
            }
            this._displaySelectedNodesAndClose();
        }
    },

    /**
     * Sets filters for searching on report
     */
    _initializeFilter: function()
    {
        this._eo = new RightNow.Event.EventObject(this, {
            data: {
                data_type:      "ProductCatalog",
                cache:          []
            },
            filters: {
                rnSearchType:   "menufilter",
                searchName:     this.data.js.searchName,
                report_id:      this.data.attrs.report_id,
                fltr_id:        this.data.js.fltr_id,
                oper_id:        this.data.js.oper_id,
                data:           [(this.data.js.initial) ? this.data.js.initial : []]
            }
        });
        this._lastSearchValue = this._eo.filters.data[0].slice(0);
    }
});
