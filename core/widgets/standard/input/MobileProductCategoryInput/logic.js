RightNow.Widgets.MobileProductCategoryInput = RightNow.Field.extend({
    overrides: {
        constructor: function() {
            this.parent();
            this.Y.augment(this, RightNow.ProductCategory);

            this._selections = [];
            this._currentValue = 0;
            this._currentLabel = '';
            this._currentLevel = 1;
            this._currentScreenIndex = 1;
            this._maxDepth = this.data.attrs.max_lvl || 6;
            this._hasChildren = false;
            this._baseID = this.baseSelector + "_" + this.data.js.data_type;
            this._launchButton = this.Y.one(this._baseID + "_Launch");

            if (this.data.js.readOnly) return;

            if (this.data.js.initial.length !== 0) {
                for (var i = 0, currentItem; i < this.data.js.initial.length; i++) {
                    currentItem = this.data.js.initial[i];
                    this._selections.push({
                        value: currentItem.id,
                        label: this.Y.Escape.html(currentItem.label)
                    });
                }
                if (typeof currentItem !== "undefined") {
                    this._currentValue = currentItem.id;
                    this._currentLabel = currentItem.label;
                    this._hasChildren = currentItem.hasChildren;
                }
                this._commitSelection(this._selections);
            }
            else if (this.data.js.linkingOn && this.data.js.data_type === "Category" && this.data.js.linkMap && typeof this.data.js.linkMap[0] === "undefined") {
                this._setCategoryUnavailable();
            }

            this._launchButton.on("click", this._openDialog, this);
            RightNow.Event.subscribe("evt_menuFilterGetResponse", this._getSubLevelResponse, this);
            this.parentForm().on("submit", this._onValidateRequest, this);
            this.on('constraintChange:required_lvl', this.updateRequiredLevel, this);
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
        var firstLevelItems = element || this.Y.one(this._baseID + "_Level1Input");
        if (!firstLevelItems) return;
        this._dialog = RightNow.UI.Dialog.actionDialog(this.data.attrs.label_prompt, firstLevelItems, {
            navButtons: true,
            cssClass: "rn_MobileProductCategoryInput"
        });
        this._dialog.hideEvent.subscribe(this._resetView, this, true);
        this._dialog.backEvent.subscribe(function() {
            this._currentLevel = Math.max(this._currentLevel - 1, 1);
            this._currentScreenIndex = Math.max(this._currentScreenIndex - 1, 1);
        }, this, true);
        firstLevelItems.removeClass("rn_Hidden").delegate("click", this._getSubLevelRequest, "input", this);
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
     * @param {Number=} parentSelected ID of the parent being selected
     */
    _selectionMade: function(parentSelected) {
        var index = 0,
            buttonText = "",
            ariaText = "";
        if (parentSelected) {
            //remove items that may exist from choosing a child,
            //going back, and choosing the parent
            while (parentSelected !== this._selections[this._selections.length - 1].value) {
                this._selections.pop();
            }
            this._currentValue = this._selections[this._selections.length - 1].value;
            this._currentLabel = this._selections[this._selections.length - 1].label;
            this._currentLevel = this._selections.length;
        }
        if (this._launchButton) {
            //construct new labels
            do {
                if (this._selections[index].value) {
                    //display new filter(s) other than 'no value'
                    buttonText += this._selections[index].label + "<br>";
                    ariaText += this._selections[index].label + " ";
                }
                index++;
            }
            while (index < this._currentLevel && this._selections[index]);

            if(ariaText) {
                ariaText = RightNow.Text.sprintf(this.data.attrs.label_current_selection_screenreader, ariaText);
            }
            this._launchButton.set("innerHTML", buttonText || this.data.attrs.label_prompt);
            this.swapLabel(this.Y.one(this._baseID + '_Label'), this.data.attrs.required_lvl, this.data.attrs.label_input, this.getStatic().templates.label, ariaText);
        }

        if (this._dialog && this._dialog.visible()) {
            this._dialog.hide();
        }
        this._markSelectionInDialog();
        this._commitSelection(this._selections);
        this._updateEventObject({
            data: {
                level: this._currentLevel,
                value: this._currentValue
            }
        });

        // event to notify listeners of selection
        this._eo.data.hierChain = this._getCommittedSelection(true);
        RightNow.Event.fire("evt_productCategorySelected", this._eo);
        this.fire('change', this);
        delete this._eo.data.hierChain;
    },

    /**
     * Changes the view so that this field is visible to the user.
     */
    _resetView: function() {
        if (!this._launchButton) return;
        if (this._launchButton.intersect(this._launchButton.get('viewportRegion'))) {
            this._launchButton.scrollIntoView();
        }
    },

    /**
     * Clears out any existing selection; returns menu to first-level items.
     */
    _clearSelection: function() {
        this._selections = [{
            value: 0
        }];
        if (this._dialog) {
            while (this._dialog.hasPreviousContent()) {
                this._dialog.previousScreen();
            }
        }
        this._currentLevel = 1;
        this._currentValue = 0;
        this._currentLabel = '';
        this._hasChildren = false;
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
                label = input.next("label");

            //extract label from dom node and see if it has sub-items
            if (label && label.get("innerHTML")) {
                this._hasChildren = label.hasClass("rn_HasChildren");
                this._currentLabel = this.Y.Lang.trim(label.get("childNodes").item(0).get("text"));
            }

            //keep track of what's currently selected
            this._currentValue = parseInt(input.get("value"), 10);
            this._selections = this._selections.slice(0, this._currentScreenIndex - 1);
            this._selections.push({
                value: this._currentValue,
                label: this.Y.Escape.html(this._currentLabel)
            });
            this._currentLevel = this._selections.length;

            //if you don't care about prod/cat linking skip this condition...
            if (this.data.js.linkingOn && this.data.js.data_type === "Product") {
                //always fire event so that category widget can get linked categories
                this._currentValue = this._currentValue || -1;
                this._backButtonLabel = (this._currentLevel === 1) ? this.data.attrs.label_data_type : this._selections[this._currentLevel - 2].label;
                RightNow.Event.fire("evt_menuFilterRequest", this._updateEventObject({
                    "data": {
                        "level": this._currentLevel,
                        "value": this._currentValue
                    }
                }));
                if (!this._hasChildren) {
                    //if it's a leaf node, then we're done here
                    this._selectionMade();
                }
                this._toggleLoadingIcon(clickEvent.target);
                return;
            }

            if (!this._hasChildren) {
                //if it's a leaf node, then we're done here
                this._selectionMade();
                this._toggleLoadingIcon();
            }
            else if (this._currentValue) {
                //otherwise, retrieve the next level of items
                this._backButtonLabel = (this._currentLevel === 1) ? this.data.attrs.label_data_type : this._selections[this._currentLevel - 2].label;
                RightNow.Event.fire("evt_menuFilterRequest", this._updateEventObject({
                    data: {
                        level: this._currentLevel,
                        value: this._currentValue
                    }
                }));
                // Remove link_map from this._eo so this widget does not misinform the Event Bus
                // or other widgets about the link_map on subsequent requests.
                if (this._eo.data.link_map)
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
        if (data.length && level <= this._maxDepth && this._dialog) {
            var id = this._baseID + "_Level" + level + "Input_" + this._currentValue,
                existingElement = this.Y.one(id),
                requirementMet = false;
            this._currentScreenIndex++;
            if (existingElement) {
                //already loaded and created this data:
                var committedSelections = this._getCommittedSelection();
                var isSameSelection = false;

                //Check to see if the current node is the same as a node previously chosen, that is still highlighted.
                //If so, break out of the loop and skip the following if condition, that would un-highlight the chosen node.
                for (var i in committedSelections) {
                    if (committedSelections[i].value === this._currentValue) {
                        isSameSelection = true;
                        break;
                    }
                }

                if (!isSameSelection && !(committedSelections.length + 1 === this._currentLevel && committedSelections[committedSelections.length - 1].value === this._currentValue)) {
                    //de-mark anything that may have been previously selected on this level
                    this._markSelectionInDialog(existingElement);
                }
                //re-show the previously created html
                this._dialog.showScreen(id);
            }
            else {
                //add 'select all' node at top (if requirement level's been met)
                if ((!this.data.attrs.required_lvl || this._currentLevel > this.data.attrs.required_lvl) &&
                    this.checkPermissionsOnNode(this._currentValue)) {
                    data.unshift({
                        id: this._currentValue,
                        label: RightNow.Text.sprintf(RightNow.Interface.getMessage("ALL_PCT_S_LBL"), this._currentLabel)
                    });
                    requirementMet = true;
                }

                var maxDepth = this._maxDepth,
                    currentSelections = this._getCommittedSelection(),
                    alreadySelected = (currentSelections[level - 1]) ? currentSelections[level - 1].value :
                    ((currentSelections[level - 2]) ? currentSelections[level - 2].value :
                        false),
                    div = new EJS({
                        text: this.getStatic().templates.view
                    }).render({
                        // remove leading '#' from ids
                        inputID: id.substr(1),
                        labelID: (this._baseID + "_Level" + level + "Label_" + this._currentValue).substr(1),
                        level: level,
                        data: data,
                        parentAlt: this.data.attrs.label_parent_menu_alt,
                        getContainerClass: function(item, i) {
                            return ((i === 0) ? "rn_Parent" : "rn_SubItem") + ((item.id === alreadySelected) ? " rn_Selected" : "");
                        },
                        getLabelClass: function(item) {
                            return (level !== maxDepth && item.hasChildren) ? "rn_HasChildren" : "";
                        },
                        escapeHtml: this.Y.Escape.html
                    });

                this._dialog.nextScreen(div, null, this._backButtonLabel);
                // only allowed to make a selection if requirements have been met
                var allItem = (requirementMet) ? this.Y.one(id).one('input') : null;
                this.Y.one(id).delegate("click", function(e) {
                    if (allItem && e.target.compareTo(allItem)) {
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
            firstLevelItems = this.Y.one(id),
            element,
            maxDepth = this._maxDepth;
        if (this._dialog && firstLevelItems) {
            //remove the dialog and start from scratch
            element = firstLevelItems.next();
            while (element) {
                this._dialog.previousScreen();
                element.remove();
                element = firstLevelItems.next();
            }
            firstLevelItems.remove();
        }
        // add 'no value' node at top
        data.unshift({
            id: 0,
            label: RightNow.Text.sprintf(this.data.attrs.label_all_values, this._currentLabel)
        });
        firstLevelItems = this.Y.Node.create(new EJS({
            text: this.getStatic().templates.view
        }).render({
            // remove leading '#' from ids
            inputID: id.substr(1),
            labelID: (this._baseID + "_Level" + level + "Label_" + this._currentValue).substr(1),
            level: level,
            data: data,
            parentAlt: this.data.attrs.label_parent_menu_alt,
            getContainerClass: function(item, i) {
                return ((i === 0) ? "rn_Parent" : "rn_SubItem") + ((item.id === selectedItem) ? " rn_Selected" : "");
            },
            getLabelClass: function(item) {
                return (level !== maxDepth && item.hasChildren) ? "rn_HasChildren" : "";
            },
            escapeHtml: this.Y.Escape.html
        }));

        this._createDialog(firstLevelItems);
        return firstLevelItems;
    },

    /**
     * Event handler when returning from ajax data request.
     * @param {String} type Event name
     * @param {Array} args Event arguments
     */
    _getSubLevelResponse: function(type, args) {
        var evtObj = args[0];
        //Check if we are supposed to update : only if the original requesting widget or if category widget receiving linked-categories
        if ((evtObj.w_id && evtObj.w_id === this.instanceID) || (this.data.js.linkingOn && evtObj.data.data_type === "Category" && this.data.js.data_type === evtObj.data.data_type)) {
            if (evtObj.data.reset_linked_category) {
                // delete linkMap if we have not already so that we don't send stale data
                if (this.data.js.linkMap)
                    delete this.data.js.linkMap;

                //only applies to linked categories
                if (!evtObj.data.hier_data.length) {
                    this._setCategoryUnavailable();
                }
                else {
                    this._categoryUnavailable = false;

                    evtObj.data.hier_data = this.groomProdcats(evtObj.data.hier_data);

                    if (this._selections.length) {
                        var currentSelections = this._selections.slice(0),
                            currentIndex = 0;
                        //clear out any existing category selection if it doesn't exist in the new data
                        for (var i = 0, firstItem = this._selections[0].value, newData = evtObj.data.hier_data, stillSelected; i < newData.length; i++) {
                            if (newData[i].id === firstItem) {
                                stillSelected = firstItem;
                                break;
                            }
                        }
                        if (!stillSelected) {
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
                                    if (thisRootNode)
                                        followTree(thisRootNode.one("input[value='" + currentSelections[currentIndex - 1].value + "']"));
                                };

                            // artificially select the first selected element in the dialog
                            // and then continue down the tree until we either select the currently
                            // selected hierarchy or linking prevents us from going further
                            if (firstSelectedNode) {
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
            if (evtObj.data.hier_data.length === 0) {
                this._selectionMade();
            }
            else {
                evtObj.data.hier_data = this.groomProdcats(evtObj.data.hier_data);

                this._buildDialogContent(evtObj.data.hier_data, this._currentLevel);
            }
            this._toggleLoadingIcon();
        }
    },

    /**
     * Event handler for when form is being validated.
     * @param {String} type Event name
     * @param {Array} args Event arguments
     * @return {Object|Boolean} Widget's EventObject or false if validation failed
     */
    _onValidateRequest: function(type, args) {
        this._errorLocation = this.lastErrorLocation = args[0].data.error_location;

        if (this._checkSelectionErrors()) {
            var formEventObject = this.createEventObject(),
                value = this._getCommittedSelection(true);
            if (value.length) {
                formEventObject.data.value = value[value.length - 1];
            }
            RightNow.Event.fire("evt_formFieldValidatePass", formEventObject);
            return formEventObject;
        }
        RightNow.Event.fire("evt_formFieldValidateFailure", this._eo);
        return false;
    },

    /**
     * Used by Dynamic Forms to switch between a required and a non-required label
     * @param  {Object} container    The DOM node containing the label
     * @param  {Number} requiredLevel The new required level
     * @param  {String} label        The label text to be inserted
     * @param  {String} template     The template text
     * @param  {String=} screenReaderText    Optional text to add for screen readers
     */
    swapLabel: function(container, requiredLevel, label, template, screenReaderText) {
        this.Y.augment(this, RightNow.RequiredLabel);
        var templateObject = {
            label: label,
            instanceID: this.instanceID,
            fieldName: this._fieldName,
            requiredLevel: requiredLevel,
            requiredLabel: RightNow.Interface.getMessage("REQUIRED_LBL"),
            screenReaderText: screenReaderText || ""
        };

        container.setHTML('');
        container.append(new EJS({
            text: template
        }).render(templateObject));
    },

    /**
     * Update the required level
     * @param  {String} evt        The event name
     * @param  {Object} constraint An object containing the altered constraint
     */
    updateRequiredLevel: function(evt, constraint) {
        var newLevel = constraint[0].constraint;
        if (newLevel > this.data.attrs.max_lvl || this.data.attrs.required_lvl === newLevel) return;

        //Clear all error div messages
        if (this.data.attrs.required_lvl > 0 && this.lastErrorLocation) {
            this.Y.one('#' + this.lastErrorLocation).all("[data-field='" + this._fieldName + "']").remove();
        }

        //Update the label HTML
        this.swapLabel(this.Y.one(this._baseID + '_Label'), newLevel, this.data.attrs.label_input, this.getStatic().templates.label);

        //Update the field
        this.data.attrs.required_lvl = newLevel;
        this._toggleErrorLabels(newLevel);
    },

    /**
     * Checks if field has met its required level for submission.
     * @return {Boolean} Whether requirements have been met
     */
    _checkSelectionErrors: function() {
        if (this.data.attrs.required_lvl) {
            this._toggleErrorLabels();
            if (this._categoryUnavailable) {
                //allow submission if no categories are available
                return true;
            }
            if (this._currentValue === 0 || (this._hasChildren && (this._getCommittedSelection()).length < this.data.attrs.required_lvl)) {
                this._displayErrorMessage();
                return false;
            }
        }

        if (!this.checkPermissionsOnNode(this._currentValue)) {
            this._displayErrorMessage();
            return false;
        }

        return true;
    },

    /**
     * Toggles the display of an error class on the widget's label.
     * @param {Boolean} addErrorClass True if the error class is to be added; otherwise removed
     */
    _toggleErrorLabels: function(addErrorClass) {
        // only do this DOM manipulation when necessary
        // (most of the time there's no need to change anything)
        var css, label;
        if (addErrorClass) {
            // show error (only if the previous time it wasn't already shown)
            if (this._failedPreviousCheck) return;
            this._failedPreviousCheck = true;
            css = "addClass";
        }
        else if (this._failedPreviousCheck) {
            // remove error
            this._failedPreviousCheck = false;
            css = "removeClass";
        }
        else return; // no error to remove
        if (label = this.Y.one(this._baseID + "_Label")) {
            label[css]("rn_ErrorLabel");
        }
    },

    /**
     * Adds error classes to the widget's label, selection button,
     * and the currently selected node. Adds the required message
     * to the form's common error location.
     */
    _displayErrorMessage: function() {
        this._toggleErrorLabels(true);
        var commonErrorDiv, message, label;
        //report error on common form button area
        if (this._errorLocation && (commonErrorDiv = this.Y.one("#" + this._errorLocation))) {
            if (!this.checkPermissionsOnNode(this._currentValue) && this._currentLabel) {
                label = this.data.attrs.label_not_permissioned;
                message = this.data.attrs.label_input + " - " + ((label.indexOf("%s") > -1) ? RightNow.Text.sprintf(label, this._currentLabel) : label);
            }
            else if (this._currentLabel) {
                label = this.data.attrs.label_required;
                message = this.data.attrs.label_input + " - " + ((label.indexOf("%s") > -1) ? RightNow.Text.sprintf(label, this._currentLabel) : label);
            }
            else {
                message = this.data.attrs.label_prompt;
            }
            commonErrorDiv.append(new EJS({
                text: this.getStatic().templates.error
            }).render({
                id: this._baseID.substr(1) + "_Launch",
                errorLink: message,
                escapeHtml: this.Y.Escape.html,
                fieldName: this._fieldName
            }));
        }
    },

    /**
     * Stores off the the hierarchy chain that has been selected.
     * The _selection member is subject to change if the user navigates back
     * levels without ever "selecting" anything; this function stores what is
     * ultimately selected.
     * @param {Array} selection The current hierarchy chain that has been selected
     */
    _commitSelection: function(selection) {
        this._committedSelection = RightNow.Lang.cloneObject(selection);
    },

    /**
     * Returns the selection hierarchy stored via _commitSelection().
     * @param {Boolean=} justValues (optional) Whether to return an array containing
     *   just values; defaults to false
     * @return {Array} selection or empty array if nothing has been selected
     */
    _getCommittedSelection: function(justValues) {
        if (justValues && this._committedSelection) {
            for (var i = 0, values = []; i < this._committedSelection.length; i++) {
                values.push(this._committedSelection[i].value);
            }
            return values;
        }
        return this._committedSelection || [];
    },

    /*
     * Sets appropriate variables and elements when categories are not available
     * (i.e. when a product is selected with no categories linked to it).
     */
    _setCategoryUnavailable: function() {
        this._categoryUnavailable = true;
        //reset category selection
        this._clearSelection();
        //set flag: there's no reason to let users open a dialog to select nothing...
        this.Y.one(this.baseSelector).setStyle("visibility", "hidden");
    },

    /**
     * Handles all interaction with the internal event object
     * (for interacting with sub-item and report requests)
     * @param {Object=} options intended to represent event object structure
     * ({data: {...}}) optional, in which case the EventObject is simply returned
     */
    _updateEventObject: function(options) {
        if (!this._initialized) {
            this._eo = new RightNow.Event.EventObject(this, {
                data: {
                    data_type: this.data.js.data_type,
                    hm_type: this.data.js.hm_type,
                    linking_on: this.data.js.linkingOn,
                    linkingProduct: 0,
                    table: this.data.js.table,
                    cache: [],
                    name: ((this.data.js.data_type === "Product") ? 'prod' : 'cat')
                }
            });
            this._updateEventObject._updateVals =
                function(origArray, properties) {
                    for (var i in properties) {
                        if (properties.hasOwnProperty(i)) {
                            origArray[i] = properties[i];
                        }
                    }
                };
            this._initialized = true;
        }
        //update values:
        //the only updates allowed are eo.data: {}
        if (options && options.data) {
            this._updateEventObject._updateVals(this._eo.data, options.data);
        }
        if (this.data.js.linkMap) {
            //pass prod linking link map to EventBus for first time
            this._eo.data.link_map = this.data.js.linkMap;
            delete this.data.js.linkMap;
        }
        return this._eo;
    },

    /**
     * Toggles lock on input while Ajax completes request
     * Toggles loading indicator
     * @param {Object} toggleNode Node in search filter to be locked and have loading indicator shown on
     */
    _toggleLoadingIcon: function(toggleNode) {
        if (toggleNode && !toggleNode.hasClass('rn_LoadingIcon')) {
            this._beingSelected = true;
            this.Y.one(toggleNode).next().addClass('rn_LoadingIcon');
        }
        else {
            this._beingSelected = false;
            this.Y.one('.rn_MobileProductCategoryInput .rn_LoadingIcon').removeClass('rn_LoadingIcon');
        }
    }
});