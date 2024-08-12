RightNow.Widgets.OkcsProductCategoryInput = RightNow.SearchConsumer.extend({
    overrides: {
        constructor: function() {    
            this.parent();
            this.Y.augment(this, RightNow.ProductCategory);
            this._hasMoreLink = false;
            this.type = this.data.attrs.filter_type;
            this._displayFieldVisibleText = this.Y.one(this.baseSelector + "_ButtonVisibleText");
            this.displayField = this.Y.one(this.baseSelector + '_' + this.type + "_Button");
            this.requiredLabel = this.Y.one(this.baseSelector + "_RequiredLabel");
            this.categMap = new Object();
            this.moreCategMap = new Object();
            this.isMoreLinkClicked = false;
            this.isContentType = this.type === 'ContentType' ? true : false;
            RightNow.Event.subscribe("evt_clearFormData", this._clearFormData, this);
            if(this.isContentType) {
                RightNow.Event.subscribe("evt_populateContentType", this._populateContentType, this);
            }
            else {
                RightNow.Event.subscribe("evt_contentTypeChanged_p", this._onContentTypeChange, this);
                RightNow.Event.subscribe("evt_contentTypeChanged_c", this._onContentTypeChange, this);
            }
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
                cache:          [],
                value:          this.dataType,
                key:            this.dataType,
                type:           this.dataType
            })
        });

        if (this.dataType === "Product") {
            // Set namespace global for hier menu list linking display
            RightNow.UI.Form.currentProduct = this.data.js.initial[this.data.js.initial.length - 1];
        }
    },

    /**
     * Event handler when content type is to be populated
     * @param {string} evt Event name
     * @param {Array} args Event arguments
     */
    _populateContentType: function(evt,args) {
        var contentTypeArray = args[0];
        this.initializeEventObject();
        var convertedObject = [];
        RightNow.Event.fire("evt_getCtDom", this.displayField.get('id'));
        if(this.requiredLabel && this.data.attrs.is_required) {
                this.requiredLabel.replaceClass('rn_Hidden', 'rn_Required');
            }
        if(contentTypeArray) {
            this.initializeTreeView(this.data.attrs.filter_type);
            for(var i = 0; i < contentTypeArray.length; i++) {
                var ctRecord = {
                    id : contentTypeArray[i].recordId,
                    label : contentTypeArray[i].name,
                    refKey : contentTypeArray[i].referenceKey
                };
                convertedObject[i] = ctRecord;
            }
            
            var initialItem = { id : 0, label : this.data.attrs.label_all_values};
                convertedObject.unshift(initialItem);
            
            this.data.js.hierData = new Array();
            this.data.js.hierData[0] = convertedObject;
            this._displayFieldVisibleText.setHTML(this.data.attrs.label_nothing_selected);
            this.tree = false;
            this.buildTree(true);
        }
    },

    /**
     * Event handler when content type is updated
     * @param {string} type Event name
     * @param {Array} args Event arguments
     */
    _onContentTypeChange: function(evt,args) {
            this.initializeEventObject();
            this.eventName = evt;
            this._buildCategoriesTree(args[0].categories, this.data.attrs.filter_type);
        },

    /**
     * This function builds tree using okcs data
     * @param {Array} categoryObject Category object
     * @param {string} type Type of data to be checked
     */
    _buildCategoriesTree: function(categoryObject, type) {
        if (categoryObject.items) {
            var categories = this._convertData(categoryObject, type),
                category = { id : 0, label : this.data.attrs.label_all_values };
            this.initializeTreeView(type);
            categories.unshift(category);
            if(this.validCategoryFlag) {
                this.data.js.hierData = new Array();
                this.data.js.hierData[0] = categories;
                this._displayFieldVisibleText.setHTML(this.data.attrs.label_nothing_selected);
                this.tree = false;
                this.buildTree(true);
            }
        }
    },

    /**
    * This function converts okcs data into tree structure
    * @param {object} category object
    * @param {string} type Type of data to be checked
    * @return {object} Category data into tree structure
    */
    _convertData: function(categoryObject, type) {
        var convertedObject = [];
        if(!categoryObject || !categoryObject.items || categoryObject.items.length === 0 ) {
            if((this.eventName === 'evt_contentTypeChanged_p' && type === 'Product') || (this.eventName === 'evt_contentTypeChanged_c' && type === 'Category')) {
            this.validCategoryFlag = true;
        }
        else
            this.validCategoryFlag = false;
        return convertedObject;
        }
        this.validCategoryFlag = ((categoryObject.items[0].externalType === 'PRODUCT' && type === 'Product') ||
                                    (categoryObject.items[0].externalType === 'CATEGORY' && type === 'Category')) ? true : false;
        if(this.validCategoryFlag) {
            for(var i = 0, j = 0; i < categoryObject.items.length; i++) {
                this._hasMoreLink = categoryObject.hasMore;
                var category = {
                    id : categoryObject.items[i].referenceKey,
                    value : categoryObject.items[i].referenceKey,
                    label : categoryObject.items[i].name,
                    hasChildren : categoryObject.items[i].hasChildren,
                    selected : false
                };
                this.categMap[categoryObject.items[i].referenceKey] = categoryObject.items[i].recordId; 
                convertedObject[ j++ ] = category;
            }
        }
        if(this._hasMoreLink) {
            moreCategory = { id : this.baseSelector + "_MoreLink", label : this.data.js.moreLinkLabel }
            convertedObject[ j++ ] = moreCategory;
        }
        return convertedObject;
    },

    /**
     * Event handler when returning from ajax data request
     * @param {string} type Event name
     * @param {Array} args Event arguments
     */
    getSubLevelResponse: function(type, args) {
        var evtObj = args[0];

        var hierLevel = evtObj.data.level,
            hierData = evtObj.data.hier_data,
            currentRoot = evtObj.data.current_root;

        if(!evtObj.data.reset_linked_category && this.getSubLevelRequestEventObject._origRequest &&
            this.getSubLevelRequestEventObject._origRequest[this.dataType]) {
            currentRoot = this.getSubLevelRequestEventObject._origRequest[this.dataType];
        }
        else if(evtObj.data.reset_linked_category) {
            //prod linking : data's being completely reset
            this.tree.clear(this.data.attrs.label_all_values);
            this.dialog = null;

            //since the data's being reset, reset the button's label
            this.dropdown.set('triggerText', this.data.attrs.label_nothing_selected);
            this.Y.all(this.baseSelector + "_TreeDescription").setHTML(this.data.attrs.label_nothing_selected);
        }

        if (hierLevel < 7) {
            //add the new nodes to the currently selected node
            this.insertChildrenForNode(hierData, currentRoot);
        }
        if (hierData.length === 0) {
            //leaf node was expanded : display and close
            this.displaySelectedNodesAndClose();
        }
        if (this._restorationHierArray) {
            //If this._restorationHierArray is set, then prod/cat linking and history management are both in use.
            //Use this._restorationHierArray to restore the value and select the node.
            var hierArray = this._restorationHierArray;
            this._restorationHierArray = null;
            this.tree.expandAndCreateNodes(hierArray);
            var tempNode = this.tree.getNodeByValue(hierArray[hierArray.length - 1]);
            if (tempNode)
                this.selectNode({node: tempNode});
        }
    },

    /**
    * Displays the hierarchy of the currently selected node up to its root node,
    * hides the panel, and focuses on the selection button (if directed).
    * @param {Boolean} focus Whether or not the button should be focused
    */
    displaySelectedNodesAndClose: function(focus) {
        this._eo.data.hierChain = this.tree.get('valueChain');
        RightNow.Event.fire("evt_productCategoryFilterSelected", this._eo);
        delete this._eo.data.hierChain;

        RightNow.ProductCategory.prototype.displaySelectedNodesAndClose.call(this, focus);
    },

    /**
     * Inserts the given child node data for the node with the given value
     * @param  {Array} hierData    Child data
     * @param  {string|number} currentRoot ID / value of the parent node
     */
    insertChildrenForNode: function(hierData, currentRoot) {
        this.insertChildHierarchyData(hierData, currentRoot);
    },

    /**
     * Builds and inserts child nodes for the node with the given value.
     * @param  {Array} hierData          Child node data
     * @param  {number|string} valueOfParentNode ID / value of parent node
     * @return {boolean} Whether the operation succeeded
     */
    insertChildHierarchyData: function(hierData, valueOfParentNode) {
        var parent = valueOfParentNode ? this._getNodeByValue(valueOfParentNode) : this._getRoot();

        if(this.isMoreLinkClicked) {
            parent.dynamicLoadComplete = false;
            this.Y.one(".ygtvcell.ygtvln.ygtvfocus a.ygtvspacer").detachAll("blur");
            this.moreLeafNode.currentTarget._tree.removeNode(this.moreLeafNode.currentTarget._tree.getNodeByIndex(this.moreLeafNode.index),destroy = true);
            this.moreLeafNode = null;
            this.isMoreLinkClicked = false;
        }
        if (!parent || parent.dynamicLoadComplete) return false;

        this.tree._insertNodes(hierData, parent, []);

        return true;
    },

    /**
     * Gets a node by its ID / value.
     * @param  {string} value Node value
     * @return {Object} Y.apm.MenuNode
     */
    _getNodeByValue: function(value) {
        return this.tree._tree.getNodeByProperty('hierValue', value);
    },

    /**
    * Selected a node by clicking on its label
    * (as opposed to expanding it via the expand image).
    * @param {object} node The node
    */
    selectNode: function(node) {
        if(this.isContentType) {
            this.selectNode._selectedWidget = this.data.info.w_id;
            this.tree.collapseAll();
            RightNow.Event.fire("evt_contentTypeSelected", node);
        }
        else {
            if(node.value === this.baseSelector + "_MoreLink") {
                // Make categories call to fetch more children
                this.isMoreLinkClicked = true;
                var depth = node.depth - 1;
                this.Y.one("#" + node.currentTarget._tree.getNodeByIndex(node.details[0].index).labelElId).addClass("rn_moreLink ygtvloading ");
                this.moreLeafNode = node;
                this.moreLeafNode.currentTarget._tree.getNodeByIndex(this.moreLeafNode.index);
                if(this.moreCategMap[node.valueChain[depth]]['offset'] === undefined) {
                    this.moreCategMap[node.valueChain[depth]]['offset'] = this.data.js.limit;
                }
                else {
                    this.moreCategMap[node.valueChain[depth]]['offset'] += this.data.js.limit;
                }
                this.getSubLevelRequest(node);
                return;
            }
            if(node.value !== 0){
                var labelChain = this.tree.get("labelChain");
                var hierLabel = labelChain[0];
                for (var i = 1; i < labelChain.length; i++)
                    hierLabel += ' / ' + labelChain[i];
                this.selectNode._selectedWidget = this.data.info.w_id;
                this.tree.collapseAll();
                node.value = this.categMap[node.value];
                node.label = hierLabel;
                if(node.valueChain[0].indexOf('PRODUCT') !== -1) {
                    RightNow.Event.fire("evt_productSelected", node);
                }
                else if(node.valueChain[0].indexOf('CATEGORY') !== -1) {
                    RightNow.Event.fire("evt_categorySelected", node);
                }
            }
        }
        RightNow.ProductCategory.prototype.selectNode.call(this, node);
    },

    /**
     * Called when a node with unloaded children is to be expanded.
     * @param  {object} expandingNode The node that's expanding
     */
    getSubLevelRequest: function(expandingNode) {
        var eventObject;
        if(expandingNode.value === this.baseSelector + "_MoreLink") {
            // Categories call to fetch more children
            var refKey = expandingNode.valueChain[expandingNode.depth - 1];
            this.moreCategMap[refKey]['childrenRendered'] = true;
            eventObject = new RightNow.Event.EventObject(this, {data: { categoryId: refKey , offset: this.moreCategMap[refKey]['offset'], limit: this.data.js.limit}});
        }
        else {
            var refKey = expandingNode.valueChain[expandingNode.depth];
                if(this.moreCategMap[refKey] === undefined) {
                    this.moreCategMap[refKey] = new Object();
                    if(this.moreCategMap[refKey]['childrenRendered'] === undefined)
                        this.moreCategMap[refKey]['childrenRendered'] = false;
                }
                if(this.moreCategMap[refKey]['childrenRendered'])
                    return;
            this.moreCategMap[refKey]['parentNode'] = new Object();
            this.moreCategMap[refKey]['parentNode'] = expandingNode;
            eventObject = new RightNow.Event.EventObject(this, {data: { categoryId: expandingNode.value , offset: 0, limit: this.data.js.limit}});
        }
        var data_type = this.data.attrs.filter_type;
        RightNow.Ajax.makeRequest(this.data.attrs.get_okcs_data_ajax, eventObject.data, {
            successHandler: function(response,args){
                response = this._convertData(response, data_type);
                response = [{"data": {"data_type" : data_type, "current_root" : this.moreCategMap[refKey]['parentNode'].value, "hier_data" : response, "label" : expandingNode.label, "linking_on" : 0, "linkingProduct" : 0, "value" : expandingNode.hierValue, "reset" : false, "level" : expandingNode.depth + 1}}];
                this.getSubLevelResponse(null, response);
            },
            failureHandler: function(response) {
                    var message = response.suggestedErrorMessage || RightNow.Interface.getMessage('THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG');
                    if(response.status === 403 && response.responseText !== undefined){
                        message = response.responseText;
                    }
                    RightNow.UI.Dialog.messageDialog(message, {"icon": "WARN", exitCallback: function(){window.location.reload(true);}});
            },
            json: true,
            scope: this
        });
        // Remove link_map from this._eo so this widget does not misinform the Event Bus
        // or other widgets about the link_map on subsequent requests.
        if(this._eo.data.link_map)
            delete this._eo.data.link_map;
        return false;
    },

    /**
     * Event handler for when form is to be cleared
     */
    _clearFormData: function() {
        if(this.tree) {
            this.tree.resetSelectedNode();
            if(this.isContentType) {
                this.dropdown.set('triggerText', this.data.attrs.label_nothing_selected);
            }
            // Clear only the Product category tree
            else {
                this.tree.clear();
                this.dropdown.set('triggerText', this.data.attrs.label_nothing_selected);
            }
        }
    }
});
