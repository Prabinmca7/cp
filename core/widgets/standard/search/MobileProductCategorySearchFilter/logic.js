RightNow.Widgets.MobileProductCategorySearchFilter = RightNow.SearchFilter.extend({
    overrides: {
        constructor: function() {
            this.parent();

            this._selections = [];
            this._currentLevel = 1;
            this._currentScreenIndex = 1;
            this._baseID = this.baseSelector + "_" + this.data.attrs.filter_type;

            this.Y.Event.attach("click", this._openDialog, this._baseID + "_Launch", this);
            this.Y.Event.attach("click", function() {
                //filter removal handler
               this._clearSelection();
               if(this.data.js.linkingOn && this.data.attrs.filter_type === "Product") {
                   RightNow.Event.fire("evt_menuFilterRequest", this._updateEventObject({data: {level: this._currentLevel, value: -1}}));
               }
            }, this._baseID + "_FilterRemove", this);

            if (this.data.js.initial) {
                this._somethingWasSelected = true;
                for (var i = 0; i < this.data.js.initial.length; i++) {
                    this._selections.push({value: this.data.js.initial[i].id, label: this.Y.Escape.html(this.data.js.initial[i].label)});
                }
                this._commitSelection(this._selections);
            }

            RightNow.Event.subscribe("evt_menuFilterGetResponse", this._getSubLevelResponse, this);
            this.searchSource(this.data.attrs.report_id)
                .on("search", this._onSearch, this)
                .on("response", this._onReportResponse, this);
        }
    },

    /**
    * Displays the dialog; creates the dialog if it doesn't exist.
    */
    _openDialog: function() {
        (this._dialog || this._createDialog());
        this._dialog.show();
    },

    /**
    * Creates a dialog.
    * @param {Object=} element (optional) Element to use as the dialog's content
    */
    _createDialog: function(element) {
        element || (element = this.Y.one(this._baseID + "_Level1Input"));
        if (!element) return;
        this._dialog = RightNow.UI.Dialog.actionDialog(this.data.attrs.label_prompt, element, {navButtons: true, cssClass: "rn_MobileProductCategorySearchFilter"});
        //decrement the current level when the dialog's back button is pressed (up to level one)
        this._dialog.backEvent.subscribe(function() {
            this._currentLevel = Math.max(this._currentLevel - 1, 1);
            this._currentScreenIndex = Math.max(this._currentScreenIndex - 1, 1);
        }, this, true);
        //when the dialog is closed, remove anything that's been selected but not explicitly "chosen" (selected a childless or "all x" node)
        this._dialog.hideEvent.subscribe(function() {
            if (this._currentLevel === 1 && !this._somethingWasSelected && this._selections.length) {
                this._clearSelection();
                if (this.data.js.linkingOn && this.data.attrs.filter_type === "Product") {
                    RightNow.Event.fire("evt_menuFilterRequest", this._updateEventObject({data: {level: this._currentLevel, value: -1}}));
                }
            }
        }, this, true);
        element.removeClass("rn_Hidden").delegate("click", this._getSubLevelRequest, "input", this);
    },

    /**
     * Called when an "All" item in the dialog is selected.
     * @param {Number} parentSelected ID of the parent being selected
     */
    _selectionAllMade: function(parentSelected) {
        this._selectionMade(parentSelected);
        if (this.data.js.linkingOn && this.data.attrs.filter_type === "Product") {
            // decrement current screen index because it will be incremented
            // when evt_menuFilterResponse comes back
            this._currentScreenIndex--;
            RightNow.Event.fire("evt_menuFilterRequest", this._eo);
        }
    },

    /**
    * Called when an item in the dialog is selected.
    * @param {Number} parentSelected ID of the parent being selected
    */
    _selectionMade: function(parentSelected) {
        if (this._selections.length === 0) return;
        var index = 0,
            filterDiv = this.Y.one(this._baseID + "_Filters"),
            eventData = [],
            eventReconstructData = [];
        if(filterDiv) {
            if(parentSelected) {
                //remove items that may exist from choosing a child,
                //going back, and choosing the parent
                while (parentSelected !== this._selections[this._selections.length - 1].value) {
                    this._selections.pop();
                }
                this._currentValue = this._selections[this._selections.length - 1].value;
                this._currentLevel = this._selections.length;
            }
            //clear out all currently displaying filters
            filterDiv.set("innerHTML", "");
            //construct new nodes
            var anchor, urlParams = "", selected, page = this.data.js.searchPage + this.data.js.searchName + '/';
            do {
                if (this._selections[index].value) {
                    //display new filter(s) unless it's the 'no value'
                    selected = ((index === this._selections.length - 1) ? " rn_Selected" : "");
                    anchor = this.Y.Node.create("<a href='" + ((!selected) ? page + this._selections[index].value : 'javascript:void(0);') + "'>" + this._selections[index].label + "</a>");
                    anchor.set("className", "rn_FilterItem" + selected);
                    filterDiv.append(anchor);
                    urlParams += this._selections[index].value + ",";
                    eventReconstructData.push({value: this._selections[index].value, label: this._selections[index].label, url: urlParams});
                    eventData.push(this._selections[index].value);
                }
                index++;
            }
            while (index < this._currentLevel && this._selections[index]);
        }
        this._somethingWasSelected = true;

        (this._dialog && this._dialog.hide());

        this.Y.one(this._baseID + "_FilterRemove")[(this._selections[0].value) ? "removeClass" : "addClass" ]("rn_Hidden");
        this._markSelectionInDialog();
        this._commitSelection(this._selections);
        //update the selection for the filter request event
        this._updateEventObject({data: {level: this._currentLevel, value: this._currentValue}, filters: {data: {0: eventData, reconstructData: eventReconstructData}}});

        // event to notify listeners of selection
        this._eo.data.hierChain = this._getCommittedSelection(true);
        RightNow.Event.fire("evt_productCategoryFilterSelected", this._eo);
        delete this._eo.data.hierChain;

        if (this.data.attrs.search_on_select) {
            this._eo.filters.reportPage = this.data.attrs.report_page_url;
            this.searchSource().fire("search", this._eo);
        }
    },

    /**
    * Clears out any existing selection; returns menu to first-level items.
    */
    _clearSelection: function() {
        this._selections = [{"value": 0}];
        if (this._dialog) {
            while (this._dialog.hasPreviousContent()) {
                this._dialog.previousScreen();
            }
        }
        this._currentLevel = 1;
        this._currentScreenIndex = 1;
        this._currentValue = 0;
        this._selectionMade();
    },

    /**
    * Highlights selected items in the dialog up to the current level that the user has chosen.
    * @param {Object=} deselectAll a DOM node where all sub-items should be deselected
    */
    _markSelectionInDialog: function(deselectAll) {
        if (deselectAll) {
            // Given a DOM node to simply remove selection indicator from all sub-items
            deselectAll.all("div").removeClass("rn_Selected");
        }
        else {
            // Remove the "rn_Selected" class from all dialog content
            if (this._dialog && this._dialog._panel && this._dialog._panel.id) {
                this.Y.one("#" + this._dialog._panel.id).all("div.rn_Selected").removeClass("rn_Selected");
            }
            for (var i = 0, currentValue, currentNode, length = this._selections.length + 1, markSelection = (this._selections[0] && this._selections[0].value), apply = function(node) {
                node.removeClass("rn_Selected");
                if (markSelection && parseInt(node.one("input").get("value"), 10) === currentValue) {
                    node.addClass("rn_Selected");
                }
            }; i < length; i++) {
                currentValue = (this._selections[i]) ? this._selections[i].value : this._selections[this._selections.length - 1].value;
                currentNode = this.Y.one(this._baseID + "_Level" + (i + 1) + "Input" + ((i === 0) ? "" : "_" + this._selections[i - 1].value));
                if (currentNode) {
                    currentNode.all("div").each(apply);
                }
            }
        }
    },

    /**
     * Event handler when a node is expanded.
     * Requests the next sub-level of items from the server.
     * @param {Object} clickEvent The click on the node that's expanding
     */
    _getSubLevelRequest: function(clickEvent) {
        if (!this._beingSelected) {
            this._toggleLoadingIcon(clickEvent.target);
            var input = clickEvent.target,
                label = input.next("label"),
                hasChildren;

            //extract label from dom node and see if it has sub-items
            if (label && label.get("innerHTML")) {
                hasChildren = label.hasClass("rn_HasChildren");
                this._currentLabel = this.Y.Lang.trim(label.get("childNodes").item(0).get("text"));
            }

            //keep track of what's currently selected
            this._currentValue = parseInt(input.get("value"), 10);
            this._selections = this._selections.slice(0, this._currentScreenIndex - 1);
            this._selections.push({value: this._currentValue, label: this.Y.Escape.html(this._currentLabel)});
            this._currentLevel = this._selections.length;

            //if you don't care about prod/cat linking skip this condition...
            if (this.data.js.linkingOn && this.data.attrs.filter_type === "Product") {
                //always fire event so that category widget can get linked categories
                this._currentValue = this._currentValue || -1;
                this._backButtonLabel = (this._currentLevel === 1) ? this.data.attrs.label_filter_type : this._selections[this._currentLevel - 2].label;
                RightNow.Event.fire("evt_menuFilterRequest", this._updateEventObject({data: {level: this._currentLevel, value: this._currentValue}}));
                if (!hasChildren) {
                    //if it's a leaf node, then we're done here
                    this._selectionMade();
                }
                this._toggleLoadingIcon(clickEvent.target);
                return;
            }

            if (!hasChildren) {
                //if it's a leaf node, then we're done here
                this._selectionMade();
                this._toggleLoadingIcon();
            }
            else if (this._currentValue) {
                //otherwise, retrieve the next level of items
                this._backButtonLabel = (this._currentLevel === 1) ? this.data.attrs.label_filter_type : this._selections[this._currentLevel - 2].label;
                RightNow.Event.fire("evt_menuFilterRequest", this._updateEventObject({data: {level: this._currentLevel, value: this._currentValue}}));
                // Remove link_map from this._eo so this widget does not misinform the Event Bus
                // or other widgets about the link_map on subsequent requests.
                if(this._eo.data.link_map)
                    delete this._eo.data.link_map;
            }
        }
    },

    /**
    * Builds dialog content for a sub-level
    * @param {Array} data New data to populate
    * @param {Number} level the new level
    */
    _buildDialogContent: function(data, level) {
        if(data.length && level < 7 && this._dialog) {
            var id = this._baseID + "_Level" + level + "Input_" + this._currentValue,
                existingElement = this.Y.one(id);
            this._currentScreenIndex++;

            //Execute if there is already an existing selection made
            if (existingElement) {
                //already loaded and created this data:
                var committedSelections = this._getCommittedSelection();
                var isSameSelection = false;

                //Check to see if the current node is the same as a node previously chosen, that is still highlighted.
                //If so, break out of the loop and skip the following if condition, that would un-highlight the chosen node.
                for(var i in committedSelections) {
                    if(committedSelections[i].value === this._currentValue) {
                        isSameSelection = true;
                        break;
                    }
                }

                if(!isSameSelection && !(committedSelections.length + 1 === this._currentLevel && committedSelections[committedSelections.length - 1].value === this._currentValue)) {
                    //de-mark anything that may have been previously selected on this level
                    this._markSelectionInDialog(existingElement);
                }
                //re-show the previously created html
                this._dialog.showScreen(id);
            }
            else {
                //add 'select all' node at top
                data.unshift({id: this._currentValue, label: RightNow.Text.sprintf(RightNow.Interface.getMessage("ALL_PCT_S_LBL"), this._currentLabel)});

                var currentSelections = this._getCommittedSelection(),
                    alreadySelected = (currentSelections[level - 1]) ? currentSelections[level - 1].value : (currentSelections[level - 2] ? currentSelections[level - 2].value : false),
                    form = new EJS({text: this.getStatic().templates.view}).render({
                        // remove leading '#' from ids
                        inputID: id.substr(1),
                        labelID: (this._baseID + "_Level" + level + "Label_" + this._currentValue).substr(1),
                        level: level,
                        data: data,
                        alreadySelected: alreadySelected,
                        parentAlt: this.data.attrs.label_parent_menu_alt,
                        escapeHtml: this.Y.Escape.html
                    });
                this._dialog.nextScreen(form, null, this._backButtonLabel);

                //make a selection event
                var allItem = this.Y.one(id).one('input');
                this.Y.one(id).delegate("click", function(e) {
                    if (e.target.compareTo(allItem)) {
                        this._selectionAllMade(data[0].id);
                    }
                    else {
                        this._getSubLevelRequest(e);
                    }
                }, "input", this);
            }
        }
    },

    /**
    * Builds dialog content for a linked category.
    * @param {Number} level the new level
    * @param {Array} data New data to populate
    * @param {Number=} selectedItem Id of currently selected top-level item;
    * Assumed to be nothing selected if omitted
    */
    _buildLinkedCategoryContent: function(level, data, selectedItem) {
        var id = this._baseID + "_Level1Input",
            firstLevelItems = document.getElementById(id.substr(1)),
            element;
        if(this._dialog && firstLevelItems) {
            firstLevelItems = this.Y.one(firstLevelItems);
            // remove the dialog and start from scratch
            element = firstLevelItems.next();
            while (element) {
                this._dialog.previousScreen();
                element.remove();
                element = firstLevelItems.next();
            }
            firstLevelItems.remove();
        }
        // add 'no value' node at top
        data.unshift({id: 0, label: RightNow.Text.sprintf(this.data.attrs.label_all_values, this._currentLabel)});
        firstLevelItems = this.Y.Node.create(new EJS({text: this.getStatic().templates.view}).render({
            // remove leading '#' from ids
            inputID: id.substr(1),
            labelID: (this._baseID + "_Level" + level + "Label_" + this._currentValue).substr(1),
            level: level,
            data: data,
            alreadySelected: selectedItem,
            parentAlt: this.data.attrs.label_parent_menu_alt,
            escapeHtml: this.Y.Escape.html
        }));

        this._createDialog(firstLevelItems);
        return firstLevelItems;
    },

    /**
     * Event handler when returning from ajax data request
     * @param {String} type Event name
     * @param {Array} args Event arguments
     */
    _getSubLevelResponse: function(type, args) {
        var evtObj = args[0];
        //Check if we are supposed to update : only if the original requesting widget or if category widget receiving linked-categories
        if((evtObj.w_id && evtObj.w_id === this.instanceID) || (this.data.js.linkingOn && evtObj.data.data_type === "Category" && this.data.attrs.filter_type === evtObj.data.data_type)) {
            if(evtObj.data.reset_linked_category) {
                // delete linkMap if we have not already so that we don't send stale data
                if(this.data.js.linkMap)
                    delete this.data.js.linkMap;

                //only applies to linked categories
                if (!evtObj.data.hier_data.length) {
                    //clear out any existing category selection
                    this._clearSelection();
                    //set flag: there's no reason to let users open a dialog to select nothing...
                    this.Y.one(this.baseSelector).setStyle("visibility", "hidden");
                }
                else {
                    if (this._selections.length) {
                        var currentSelections = this._selections.slice(0), currentIndex = 0;
                        //clear out any existing category selection if it doesn't exist in the new data
                        for(var i = 0, firstItem = this._selections[0].value, newData = evtObj.data.hier_data, stillSelected; i < newData.length; i++) {
                            if(newData[i].id === firstItem) {
                                stillSelected = firstItem;
                                break;
                            }
                        }
                        if(!stillSelected) {
                            this._clearSelection();
                            this._buildLinkedCategoryContent(1, evtObj.data.hier_data, stillSelected);
                        }
                        else {
                            var firstLevelItems = this._buildLinkedCategoryContent(1, evtObj.data.hier_data, stillSelected),
                                baseID = this._baseID,
                                thisY = this.Y,
                                firstSelectedNode = firstLevelItems.one("input[value='" + firstItem + "']"),
                                followTree = function(input) {
                                    if (!input)
                                        return;
                                    currentIndex++;
                                    var thisRoot = baseID + "_Level" + currentIndex + "Input_" + currentSelections[currentIndex - 2].value,
                                        thisRootNode = thisY.one(thisRoot),
                                        selectedNode;
                                    if (currentIndex > currentSelections.length) {
                                        // the process has reached the end of selected values,
                                        // so we try and select the "All" value, if it exists
                                        if (thisRootNode && (selectedNode = thisRootNode.one("input[value='" + currentSelections[currentIndex - 2].value + "']")))
                                            selectedNode.getDOMNode().click();
                                        return;
                                    }
                                    // click "All" value first, in case appropriate selected sub-value does not exist
                                    if (thisRootNode && (selectedNode = thisRootNode.one("input[value='" + currentSelections[currentIndex - 2].value + "']")))
                                        selectedNode.getDOMNode().click();

                                    // click appropriate sub-value and continue down the hierarchy
                                    if (thisRootNode && (selectedNode = thisRootNode.one("input[value='" + currentSelections[currentIndex - 1].value + "']"))) {
                                        selectedNode.getDOMNode().click();
                                        followTree(selectedNode);
                                    }
                                };

                            // artificially select the first selected element in the dialog
                            // and then continue down the tree until we either select the currently
                            // selected hierarchy or linking prevents us from going further
                            if(firstSelectedNode) {
                                currentIndex++;
                                firstSelectedNode.getDOMNode().click();
                                followTree(firstSelectedNode);
                            }
                        }
                    }
                    else {
                        this._buildLinkedCategoryContent(1, evtObj.data.hier_data);
                    }
                    this.Y.one(this.baseSelector).setStyle("visibility", "visible");
                }
                return;
            }
            this._currentLevel = (evtObj.data.hier_data.length) ? evtObj.data.level : this._currentLevel;
            if(evtObj.data.hier_data.length === 0)
                this._selectionMade();
            else
                this._buildDialogContent(evtObj.data.hier_data, this._currentLevel);
            this._toggleLoadingIcon();
        }
    },

    /**
    * Stores off the the hierarchy chain that has been selected.
    * The _selection member is subject to change if the user navigates back
    * levels without ever "selecting" anything; this function stores what is
    * ultimately selected.
    * @param {Array} selection the current hierarchy chain that has been selected
    */
    _commitSelection: function(selection) {
        this._committedSelection = RightNow.Lang.cloneObject(selection);
    },

    /**
    * Returns the selection hierarchy stored via _commitSelection().
    * @param {Boolean=} justValues {optional} Whether to return an array containing
    *   just values; defaults to false
    * @return {Array} selection or empty array if nothing has been selected
    */
    _getCommittedSelection: function(justValues) {
        if(justValues && this._committedSelection) {
            for(var i = 0, values = []; i < this._committedSelection.length; i++) {
                values.push(this._committedSelection[i].value);
            }
            return values;
        }
        return this._committedSelection || [];
    },

    /**
    * Event handler for when the report changes
    * @param {String} type Event name
    * @param {Array} args Event arguments
    */
    _onReportResponse: function(type, args) {
        var reportData = RightNow.Event.getDataFromFiltersEventResponse(args, this.data.js.searchName, this.data.attrs.report_id);
        if(reportData.reconstructData && reportData.reconstructData.length && !this._eventObjectCreated()) {
            //only hit here if we're coming in from the cold (back button / page refresh); otherwise, there's
            //no reason to update the data
            this._selections = [];
            for(var i = 0, data = reportData.reconstructData, length = data.length; i < length; i++) {
                this._selections.push({value: data[i].value, label: data[i].label});
            }
            this._currentLevel = this._selections.length;
            this._selectionMade();
            this._currentValue = this._selections[this._selections.length - 1].value;
        }
        else if(!reportData[0] && this._selections.length) {
            //going FROM some selection BACK TO no selection
            this._clearSelection();
            this._currentValue = 0;
        }
        //if you don't care about prod/cat linking skip this condition...
        if(this.data.js.linkingOn && this.data.attrs.filter_type === "Product" && typeof this._currentValue !== "undefined") {
            RightNow.Event.fire("evt_menuFilterRequest", this._updateEventObject({data: {level: this._currentLevel, value: this._currentValue || -1}}));
        }
    },

    /**
    * Returns event object for search event if the report matches.
    */
    _onSearch: function() {
        return this._updateEventObject();
    },

    /**
    * Handles all interaction with the internal event object
    * (for interacting with sub-item and report requests)
    * @param options Object itended to represent event object structure
    * ({data: {...}, filters: {...}})
    */
    _updateEventObject: function(options) {
        var widgetData = this.data.js;
        if(!this._initialized) {
            this._eo = new RightNow.Event.EventObject(this, {
                data: {
                    cache: [],
                    hm_type: widgetData.hm_type,
                    data_type: this.data.attrs.filter_type,
                    linking_on: widgetData.linkingOn,
                    linkingProduct: 0
            }, filters: {
                    rnSearchType: "menufilter",
                    searchName: widgetData.searchName,
                    report_id: this.data.attrs.report_id,
                    fltr_id: widgetData.fltr_id,
                    oper_id: widgetData.oper_id,
                    data: []
            }});
            if(widgetData.initial) {
                //populate w/initial values (specified via URL parameter)
                for(var i = 0, length = widgetData.initial.length, initialVals = []; i < length; i++) {
                    initialVals.push(widgetData.initial[i].id);
                }
                this._eo.filters.data[0] = initialVals;
            }
            else {
                this._eo.filters.data[0] = [];
            }
            //statically define a function that'll be needed later on
            this._updateEventObject._updateVals =
                function(origArray, properties) {
                    for(var i in properties) {
                        if(properties.hasOwnProperty(i)) {
                            origArray[i] = properties[i];
                        }
                    }
                };
            this._initialized = true;
        }
        //update values:
        //the only updates allowed are eo.data: {}
        //and eo.filters.data: {}
        if(options) {
            if(options.data) {
                this._updateEventObject._updateVals(this._eo.data, options.data);
            }
            if(options.filters && options.filters.data) {
                this._updateEventObject._updateVals(this._eo.filters.data, options.filters.data);
            }
        }
        if(widgetData.linkMap) {
            //pass prod linking link map to EventBus for first time
            this._eo.data.link_map = widgetData.linkMap;
            delete widgetData.linkMap;
        }
        return this._eo;
    },

    _eventObjectCreated: function() {
        return this._initialized;
    },

    /**
    * Toggles lock on input while Ajax completes request
    * Toggles loading indicator
    * @param {Object} toggleNode Node in search filter to be locked and have loading indicator shown on
    */
    _toggleLoadingIcon: function(toggleNode) {
        if(toggleNode && !toggleNode.hasClass('rn_LoadingIcon')) {
            this._beingSelected = true;
            this.Y.one(toggleNode).next().addClass('rn_LoadingIcon');
        }
        else {
            this._beingSelected = false;
            var existingLoadingIcon = this.Y.one('.rn_MobileProductCategorySearchFilter .rn_LoadingIcon');
            if (existingLoadingIcon) {
                existingLoadingIcon.removeClass('rn_LoadingIcon');
            }
        }
    }
});
