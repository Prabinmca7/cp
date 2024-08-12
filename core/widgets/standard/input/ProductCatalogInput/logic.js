RightNow.Widgets.ProductCatalogInput = RightNow.Field.extend({
    overrides: {
        constructor: function() {
            this.parent();
            this._currentIndex = 0;
            this._noValueNodeIndex = 0;

            this._displayField = this.Y.one(this.baseSelector + "_ProductCatalog_Button");

            if(this.data.js.readOnly || !this._displayField) return;

            this._displayFieldVisibleText = this.Y.one(this.baseSelector + "_Button_Visible_Text");
            this._accessibleView = this.Y.one(this.baseSelector + "_Links");
            this._outerTreeContainer = this.Y.one(this.baseSelector + "_TreeContainer");
            this._treeNode = this.Y.one(this.baseSelector + "_Tree");

            var RightNowEvent = RightNow.Event;

            RightNowEvent.subscribe("evt_menuFilterProductCatalogGetResponse", this._getSubLevelResponse, this);
            this.parentForm().on('submit', this._onValidate, this);
            RightNowEvent.subscribe("evt_accessibleProductCatalogTreeViewGetResponse", this._getAccessibleTreeViewResponse, this);
            RightNowEvent.subscribe("evt_noProductSelected", this._noProductSelected, this);
            RightNowEvent.subscribe("evt_hintAlign", this._alignHintOverlay, this);
            RightNowEvent.subscribe("evt_removeErrorsFromProductCatalog", this._removeErrorMessages, this);

            if(this.data.attrs.hint)
                this._hintOverlay = this._initializeHint();

            //toggle panel on/off when button is clicked
            this._displayField.on("click", this._toggleProductCatalogPicker, this);
            this.Y.one(this.baseSelector + "_LinksTrigger").on("click", this._toggleAccessibleView, this);

            //setup event object
            this._eo = new RightNow.Event.EventObject(this, {data: {cache: []}});

            this._buildMenuPanel();

            if(this.data.js.hierData[0].length) {
                RightNowEvent.subscribe("evt_WidgetInstantiationComplete", this._buildTree, this);
            }
        },

        /**
         * Shows hint when the input field is focused
         * and hides the hint on the field's blur.
         */
        _initializeHint: function()
        {
            if(this.Y.Overlay)
            {
                var overlay;
                if (this.data.attrs.always_show_hint)
                {
                    overlay = this._createHintElement(true);
                }
                else
                {
                    overlay = this._createHintElement(false);
                    this._displayField.on("focus", function(){overlay.show();});
                    this._displayField.on("blur", function(){overlay.hide();});
                }
                return overlay;
            }

            //display hint inline if YUI container code isn't being included
            var hint = this.Y.Node.create('<span class="rn_HintText"/>').setHTML(this.data.attrs.hint);
            this._displayField.insert(hint, 'after');
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
            zIndex: 2
        });
        this._panel.on('visibleChange', function(e) {
            // show
            if (e.newVal) {
                this._treeNode.setStyle("display", "block");
            }
            // hide
            else {
                this._treeNode.setStyle("display", "none");
                if (this.data.attrs.hint && !this.data.attrs.always_show_hint) {
                    // Now hiding
                    this._toggleHint("hide");
                }
            }
        }, this);
    },

    /**
    * Constructs the YUI Treeview widget for the first time with initial data returned
    * from the server. Pre-selects and expands data that is expected to be populated.
    */
    _buildTree: function()
    {
        var YAHOO = this.Y.Port(),
            gallery = this.Y.apm,
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
                YAHOO.util.Event.on(this._tree.getEl(), "keyup", function(ev){
                    if(ev.keyCode === RightNow.UI.KeyMap.TAB)
                    {
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
                        YAHOO.util.Event.preventDefault(ev);
                    }
                }, null, this);
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
                            node.serialized = dataNode.serialized;
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
            this._displayAccessibleDialog();
        else
            RightNow.Event.fire("evt_accessibleProductCatalogTreeViewRequest", this._eo);
    },

    /**
    * Listens to response from the server and constructs an HTML tree according to
    * the flat data structure given.
    * @param {String} e String Event name
    * @param {Object} args Object Event arguments
    */
    _getAccessibleTreeViewResponse: function(e, args)
    {
        if(args[0].data.hm_type != this._eo.data.hm_type)
            return;
        var evtObj = args[0];
        if(evtObj.data.data_type == this.data.attrs.data_type)
        {
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

                    if(item[3]) {
                        htmlList += '<li><div id="rn_' + this.instanceID + '_AccessibleLink_' + item[1] + '_AccessibleFolder">' +
                            '<span class="rn_ScreenReaderOnly">' + this.data.attrs.label_level + ' ' + (item.level + 1) + '</span>' + item[0] +
                            '</div>';
                    }
                    else {
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
        }
    },

    /**
    * Executed when a tree item is selected from the accessible view.
    * @param {Event} e Event DOM click event
    */
    _accessibleLinkClick: function(e)
    {
        //basically transfer this click to the visible control
        //find the node in this._tree. If it's not there, expand it's parents until it is there.
        //call click on that node.
        var element = e.target;
        var hierArray = element.getAttribute("data-hierList").split(",");
        //attempt to get the one they clicked first
        var i = hierArray.length - 1;
        var currentNode = null;
        //walk up the chain looking for the first available node
        while(!currentNode && i >= 0)
        {
            currentNode = this._tree.getNodeByProperty("hierValue", parseInt(hierArray[i], 10));
            i--;
        }
        //now currentNode should be something.
        //if we already have the one they selected, then we can go ahead and click it.
        i++;
        if(this._noValueNodeIndex === currentNode.index || currentNode.hierValue == hierArray[hierArray.length - 1])
        {
            this._selectNode({node: currentNode});
        }
        else
        {
            var onExpandComplete = function(expandingNode)
            {
                if(expandingNode.nextToExpand)
                {
                    var nextNode = this._tree.getNodeByProperty("hierValue", parseInt(expandingNode.nextToExpand, 10));
                    if(nextNode)
                    {
                        nextNode.nextToExpand = hierArray[++i];
                        nextNode.expand();
                    }
                }
                else if(i === hierArray.length)
                {
                    //we don't want to subscribe to this more than once
                    this._tree.unsubscribe("expandComplete", onExpandComplete, null);
                    this._selectNode({node: expandingNode});
                }
                return true;
            };
            //walk back down to their selection from here expanding as you go
            this._tree.subscribe("expandComplete", onExpandComplete, this);
            currentNode.nextToExpand = hierArray[++i];
            currentNode.expand();
        }
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

            this._toggleHint("show");
        }
        else
        {
            // The panel's `clickoutside` event takes care of hiding the panel.
            this._toggleHint("hide");
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

        var selectedProductNode = this._tree.getNodeByIndex(this._currentIndex);
        var productID = selectedProductNode.hierValue;
        var serialized = selectedProductNode.serialized;

        RightNow.Event.fire("evt_productSelectedFromCatalog", new RightNow.Event.EventObject(this, {data: {
                    productID: productID,
                    serialized: serialized
        }}));
        this.fire('change', this);
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
        this._selectedNode = clickEvent.node;
        this._currentIndex = this._selectedNode.index;
        this._selected = true;

        //get next level if the node hasn't loaded children yet, isn't expanded, and isn't the 'No Value' node
        if((!this._selectedNode.expanded && this._currentIndex !== this._noValueNodeIndex && !this._selectedNode.dynamicLoadComplete))
        {
            this._getSubLevelRequest(clickEvent.node);
        }
        else
        {
            this._errorLocation = "";
        }

        if(this._selectedNode.isLeaf) {
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

        //When the show_confirm_button_in_dialog attribute is set, we don't want to explicity change the users selection when they drill down
        //into an element. If we did that, the user wouldn't be able to use the cancel button correctly. We just want to set a
        //temporary value which we can use in the response event. If this attribute isn't set, keep the behavior the same as before.
        if(this.data.attrs.show_confirm_button_in_dialog)
            this._requestedIndex = expandingNode.index;
        else
            this._currentIndex = expandingNode.index;

        this._eo.data.reset = false; //whether data should be reset for the current level
        RightNow.Event.fire("evt_menuFilterRequestProductCatalog", this._eo);
        this._nodeBeingExpanded = false;
    },

    /**
     * Event handler when returning from ajax data request.
     * @param type String Event name
     * @param args Object Event arguments
     */
    _getSubLevelResponse: function(type, args)
    {
        var evtObj = args[0],
            tempNode;

        //Check if we are supposed to update : only if the original requesting widget
        if(evtObj.w_id && evtObj.w_id === this.instanceID)
        {
            var currentRoot;
            currentRoot = this._tree.getNodeByIndex(this.data.attrs.show_confirm_button_in_dialog ? this._requestedIndex : this._currentIndex);

            var hierLevel = evtObj.data.level,
                hierData = evtObj.data.hier_data;

            for(var i = 0, hierValue; i < hierData.length; i++)
            {
                hierValue = hierData[i].id;
                if(!currentRoot.children[i] || currentRoot.children[i].hierValue !== hierValue)
                {
                    tempNode = new this.Y.apm.MenuNode(this.Y.Escape.html(hierData[i].label), currentRoot, false);
                    tempNode.hierValue = hierValue;
                    tempNode.href = 'javascript:void(0);';
                    if(!hierData[i].hasChildren)
                    {
                        //if it doesn't have children then turn off the +/- icon
                        // and notify that the node is already loaded
                        tempNode.dynamicLoadComplete = true;
                        tempNode.iconMode = 1;
                        tempNode.isLeaf = true;
                        tempNode.serialized = hierData[i].serialized;
                    }
                }
            }

            currentRoot.loadComplete();

            if(this._selected)
            {
                this._errorLocation = "";
                this._selected = false;
            }
        }
    },

    /**
     * Event handler for when form is being validated
     * @param {String} type Event name
     * @param {Object} args Event arguments
     * @return {Object} formEventObject Event Object
     */
    _onValidate: function(type, args)
    {
        var formEventObject = this.createEventObject();
        this._errorLocation = args[0].data.error_location;

        formEventObject.data.value = (this._currentIndex && this._currentIndex !== this._noValueNodeIndex)
            ? this._tree.getNodeByIndex(this._currentIndex).hierValue
            : null;

        RightNow.Event.fire("evt_formFieldValidatePass", formEventObject);
        return formEventObject;
    },

    /**
     * Event Handler when no product is selected
     * @param {String} type Event name
     * @param {Object} args Event arguments
     */
    _noProductSelected: function(type, args)
    {
        this._displayError(args[0].data.errorMsg, args[0].data.errorLocation);
    },

    /**
     * Adds error messages to the common error element and adds
     * error indicators to the widget field and label.
     * @param {Array} errors Error messages
     * @param {String} errorLocation Error location id
     */
    _displayError: function(errors, errorLocation) {
        this._toggleErrorIndicator(false);
        var errorDisplay = this.Y.one("#" + errorLocation);
        if(errorDisplay) {
            var id = this._displayField.get("id");
            var message = RightNow.Text.sprintf(errors, this.data.attrs.label_input + " " + errors);
            var dataFieldDiv = this.Y.Node.create("<div data-field=\"" + this._fieldName + "\">");
            this.Y.Node.create("<b><a href='javascript:void(0);' onclick='document.getElementById(\"" + id + "\").focus(); return false;'>" + message + "</a></b>").appendTo(dataFieldDiv);
            errorDisplay.addClass('rn_MessageBox rn_ErrorMessage').set("innerHTML","");
            dataFieldDiv.appendTo(errorDisplay);
            errorDisplay.one("a").focus();
            if(this.data.attrs.always_show_hint) {
                this._alignHintOverlay();
            }
        }

        this._toggleErrorIndicator(true);
    },

    /**
     * Adds / removes the error indicators on the
     * field and label.
     * @param {Boolean} showOrHide T to add, F to remove
     */
    _toggleErrorIndicator: function(showOrHide) {
        var method = ((showOrHide) ? "addClass" : "removeClass");
        this._displayField[method]("rn_ErrorField");
        this.Y.one(this.baseSelector + "_Label")[method]("rn_ErrorLabel");
    },

    /**
     * Re-Aligns the hint after occurrence of an error condition only when always_show_hint is set to true
     */
    _alignHintOverlay: function() {
        this._hintOverlay.set("align", {node: this._displayField, points: [this.Y.WidgetPositionAlign.TL, this.Y.WidgetPositionAlign.TR]});
    },

    /**
     * Creates the hint element.
     * @param visibility Boolean whether the hint element is initially visible
     * @return Object representing the hint element
     */
    _createHintElement: function(visibility)
    {
        var overlay = this.Y.Node.create("<span class='rn_HintBox'/>").set('id', this.baseDomID + '_Hint').setHTML(this.data.attrs.hint);
        if (visibility)
            overlay.addClass("rn_AlwaysVisibleHint");

        return new this.Y.Overlay({
            visible: visibility,
            align: {
                node: this._displayField,
                points: [this.Y.WidgetPositionAlign.TL, this.Y.WidgetPositionAlign.TR]
            },
            bodyContent: overlay,
            render: this.Y.one(this.baseSelector)
        });
    },

    /**
     * Toggle the display of the hint overlay if it exists and is not set to always display.
     * @param hideOrShow String The toggle function to call on the overlay "hide" or "show"
     */
    _toggleHint: function(hideOrShow)
    {
        if(this._hintOverlay && this._hintOverlay[hideOrShow] && !this.data.attrs.always_show_hint)
            this._hintOverlay[hideOrShow]();
    },

    /**
     * Removes any previously set error classes from the widget's label,
     * selection button, and previously erroneous node.
     */
    _removeErrorMessages: function()
    {
        this._toggleErrorIndicator(false);
    },

    /**
    * Removes any previously set error classes from the widget's label,
    * selection button, and previously erroneous node.
    * @param currentNode MenuNode the currently selected node
    */
    _removeRequiredError: function(currentNode)
    {
        this._displayField.removeClass("rn_ErrorField");
        this.Y.one(this.baseSelector + "_Label").removeClass("rn_ErrorLabel");
        currentNode = this._displayRequiredError.errorNode || currentNode;
        if(currentNode)
            currentNode.removeClass("rn_ErrorField");
        var requiredLabel = this.Y.one(this.baseSelector + "_RequiredLabel");
        if (requiredLabel)
            requiredLabel.replaceClass("rn_RequiredLabel", "rn_Hidden");
        RightNow.UI.hide(this._accessibleErrorMessageDiv);
    },

    /**
     * Adds error classes to the widget's label, selection button,
     * and the currently selected node. Adds the required message
     * to the form's common error location.
     * @param currentNode MenuNode the currently selected node
     */
    _displayRequiredError: function(currentNode)
    {
        //indicate the error
        this._displayField.addClass("rn_ErrorField");
        this.Y.one(this.baseSelector + "_Label").addClass("rn_ErrorLabel");

        currentNode || (currentNode = this._tree.getRoot().children[0]);
        currentNode.addClass("rn_ErrorField");
        //save a local reference to the error node so that the error class can be removed from it later
        this._displayRequiredError.errorNode = currentNode;

        var message = this.data.attrs.label_nothing_selected;
        if (currentNode.index !== this._noValueNodeIndex)
        {
            message = (this.data.attrs.label_required.indexOf("%s") > -1) ?
                RightNow.Text.sprintf(this.data.attrs.label_required, currentNode.label) :
                this.data.attrs.label_required;
        }
        //write out the required label
        var requiredLabel = this.Y.one(this.baseSelector + "_RequiredLabel");
        if(requiredLabel)
        {
            requiredLabel.setHTML(message).replaceClass('rn_Hidden', 'rn_RequiredLabel');
        }

        var label = this.data.attrs.label_error || this.data.attrs.label_input;
        //report error on common form button area
        if(this._errorLocation)
        {
            var commonErrorDiv = this.Y.one('#' + this._errorLocation);
            if(commonErrorDiv){
                commonErrorDiv.append("<div><b><a href='#' onclick='document.getElementById(\"" + this._displayField.get('id') + "\").focus(); return false;'>" +
                    label + " - " + message + "</a></b></div> ");
            }
        }
        //if the accessible dialog is created & open, add the error message to it
        if(this._dialog && this._dialog.get("visible"))
        {
            this._accessibleErrorMessageDiv || (this._accessibleErrorMessageDiv = this.Y.one(this.baseSelector + "_AccessibleErrorLocation"));
            if(this._accessibleErrorMessageDiv)
            {
                this._accessibleErrorMessageDiv.setHTML("<div><b><a id='rn_" + this.instanceID + "_FocusLink' href='javascript:void(0);' " +
                    " onclick='document.getElementById(\"" + "rn_" + this.instanceID + "_AccessibleLink_" + currentNode.hierValue + "\").focus(); return false;'>" +
                    label + " - " + message + "</a></b></div> ")
                    .addClass('rn_MessageBox')
                    .addClass('rn_ErrorMessage')
                    .removeClass('rn_Hidden');
            }
            var errorLink = this.Y.one(this.baseSelector + "_FocusLink");
            RightNow.UI.updateVirtualBuffer();
            if(errorLink)
                errorLink.focus();
        }
    }
});
