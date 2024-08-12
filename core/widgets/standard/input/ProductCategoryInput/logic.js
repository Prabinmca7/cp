RightNow.Widgets.ProductCategoryInput = RightNow.Field.extend({
    overrides: {
        constructor: function() {
            this.parent();
            this.Y.augment(this, RightNow.ProductCategory);

            this._maxDepth = this.data.attrs.max_lvl || 6;
            this._notRequiredDueToProductLinking = false;
            this.displayField = this.Y.one(this.baseSelector + "_" + this.data.js.data_type + "_Button");
            this.requiredLabel = this.Y.one(this.baseSelector + "_RequiredLabel");
            this.errorLabel = this.Y.one(this.baseSelector + "_ErrorLabel");

            if (this.data.js.readOnly || !this.displayField) return;
            RightNow.Event.subscribe("evt_resetProductCategoryMenu", this._resetProductCategoryMenu, this);

            this.parentForm().on('submit', this._onValidate, this);

            if (this.data.attrs.set_button) {
                this.Y.one(this.baseSelector + "_" + this.data.js.data_type + "_SetButton").on("click", this._setButtonClick, this);
                RightNow.Widgets.formTokenRegistration(this);
            }

            if (this.data.attrs.hint) {
                this._hintOverlay = this._initializeHint();
            }

            //setup event object
            this._eo = new RightNow.Event.EventObject(this, {data: {
                data_type: this.data.js.data_type,
                hm_type: this.data.js.hm_type,
                linking_on: this.data.js.linkingOn,
                linkingProduct: 0,
                table: this.data.js.table,
                cache: [],
                name: ((this.data.js.data_type.indexOf('prod') > -1) ? 'prod' : 'cat')
            }});

            this.on('constraintChange:required_lvl', this.updateRequiredLevel, this);

            if (this.data.attrs.verify_permissions !== 'None' && this.data.js.readableProdcatIds.length > 0) {
                // Before the tree is made, prepare the hierData (and hierDataNone, if linking is enabled) for permissioned insertion
                this._updatePermissionedHierData('hierData');

                if (this.data.js.linkingOn && (this.data.js.data_type === 'Category') && this.data.js.hierDataNone) {
                    this._updatePermissionedHierData('hierDataNone');
                }

                // If linking is enabled, and the widget is referencing a category, after treeView is initialized,
                // hierData is destroyed. We keep a temporary copy so we can disable non-permissioned nodes on the original data set.
                var originalHierData = this.data.js.hierData;
                this.initializeTreeView(this.data.js.data_type);

                // After the tree is made, disable any nodes that are viewable, but not permissioned
                this.Y.Object.each(this.Y.Object.keys(originalHierData), function (key) {
                    this.disableNonPermissionedNodes(originalHierData[key]);
                }, this);
            }
            else {
                this.initializeTreeView(this.data.js.data_type);
            }

            if (this.data.attrs.required_lvl && this.requiredLabel) {
                 this.requiredLabel.replaceClass('rn_Hidden', 'rn_Required');
            }
        },

        /**
         * Shows hint when the input field is focused
         * and hides the hint on the field's blur.
         */
        _initializeHint: function() {
            if (this.Y.Overlay) {
                var overlay;
                if (this.data.attrs.always_show_hint) {
                    overlay = this._createHintElement(true);
                    // FormSubmit's form error message display triggers within the same form
                    // events, so delay just long enough to ensure that any error messages were
                    // hidden/shown prior to the hint getting re-aligned.
                    var realignHintAfterFormValidation = this.Y.bind(this._realignHint, this, 1);
                    this.parentForm().on('validation:pass', realignHintAfterFormValidation)
                                     .on('validation:fail', realignHintAfterFormValidation)
                                     .on('response', realignHintAfterFormValidation);
                }
                else {
                    overlay = this._createHintElement(false);
                    this.displayField.on("focus", overlay.show, overlay);
                    this.displayField.on("blur", overlay.hide, overlay);
                }
                return overlay;
            }
            else {
                //display hint inline if YUI container code isn't being included
                var hint = this.Y.Node.create('<span class="rn_HintText"/>').setHTML(this.data.attrs.hint);
                this.displayField.insert(hint, 'after');
            }
        }
    },

    /**
     * Builds the dropdown panel and adds an event listener to show the hint when the
     * dropdown shows.
     * Overrides and calls into RightNow.ProductCategory.buildPanel.
     */
    buildPanel: function () {
        RightNow.ProductCategory.prototype.buildPanel.call(this);
        this.dropdown.on('show', this.Y.bind(this._toggleHint, this, 'show'));
    },

    /**
     * Event handler for resetting product/category menu
     */
    _resetProductCategoryMenu: function() {
        this.tree.resetSelectedNode();
        this.displaySelectedNodesAndClose(false, false);
        this._removeErrorMessages();
    },

    /**
     * Goes through hierarchy data and verifies only readable data is inserted.
     * @param  {string} dataType Type of data to be checked.
     *                           Either hierData or hierDataNone
     */
    _updatePermissionedHierData: function (dataType) {
        if (!(this.data.js[dataType] && this.Y.Lang.isObject(this.data.js[dataType]))) {
            throw new Error("Widget does not have this.data.js." + dataType + " attribute set, or its value is not a valid object");
        }

        this.Y.Object.each(this.Y.Object.keys(this.data.js[dataType]), function (key) {
            this.data.js[dataType][key] = this.groomProdcats(this.data.js[dataType][key]);
        }, this);
    },

    /**
     * Displays the hierarchy of the currently selected node up to its root node,
     * hides the panel, and focuses on the selection button (if directed).
     * Overrides and calls into RightNow.ProductCategory.displaySelectedNodesAndClose.
     * @param {boolean} focus Whether or not the button should be focused
     * @param {boolean} fireSelectionEvent Whether or not the event should be fired for the selection of product/category. If 'undefined' then event will be fired
     * by default.
     */
    displaySelectedNodesAndClose: function(focus, fireSelectionEvent) {
        fireSelectionEvent = typeof fireSelectionEvent !== 'undefined' ? fireSelectionEvent : true;
        // event to notify listeners of selection
        this._eo.data.hierChain = this.tree.get('valueChain');
        if (fireSelectionEvent && this._checkSelectionErrors()) {
            RightNow.Event.fire("evt_productCategorySelected", this._eo);
        }
        this.fire('change', this);
        delete this._eo.data.hierChain;

        RightNow.ProductCategory.prototype.displaySelectedNodesAndClose.call(this, focus);
    },

    /**
     * Selected a node by clicking on its label
     * (as opposed to expanding it via the expand image).
     * Overrides and calls into RightNow.ProductCategory.selectNode.
     * @param {object} node The node
     */
    selectNode: function(node) {
        this._selected = true;
        //get next level if the node hasn't loaded children yet, isn't expanded, and isn't the 'No Value' node
        //or if product linking is on and this is the product (regardless of level)
        if ((!node.expanded && node.value && !node.loaded) ||
           (this.data.js.linkingOn && this.data.js.data_type === "Product")) {
            this.getSubLevelRequest(node);
        }
        else {
            this._errorLocation = "";
            this._checkSelectionErrors();
        }

        RightNow.ProductCategory.prototype.selectNode.call(this, node);
    },

    /**
     * Makes the request to the server to fetch the children for a
     * given node.
     * Overrides and calls into RightNow.ProductCategory.getSubLevelRequest.
     * @param {object} expandingNode The node
     */
    getSubLevelRequest: function (expandingNode) {
        RightNow.ProductCategory.prototype.getSubLevelRequest.call(this, expandingNode);

        // Remove link_map from this._eo so this widget does not misinform the Event Bus
        // or other widgets about the link_map on subsequent requests.
        if (this._eo.data.link_map)
            delete this._eo.data.link_map;
    },

    /**
     * Builds the EventObject for the next sub-level of items from the server.
     * Overrides RightNow.ProductCategory.getSubLevelRequestEventObject.
     * @param {object} expandingNode The node that's expanding
     * @return {object|undefined} EventObject instance if the request should be made
     */
    getSubLevelRequestEventObject: function(expandingNode) {
        this._eo.data.level = expandingNode.depth + 1;
        this._eo.data.value = expandingNode.value;
        this._eo.data.label = expandingNode.label;

        this._eo.data.reset = false; //whether data should be reset for the current level
        this._requestingParent = this._eo.data.value;
        if (this._eo.data.linking_on) {
            //prod linking
            if (this.data.js.data_type === "Category") {
                if (expandingNode.loaded) {
                    //data's already been loaded
                    return;
                }
                this._eo.data.reset = (this._eo.data.value < 1);
            }
            else if (this._eo.data.value < 1 && this.data.js.data_type === "Product") {
                //product was set back to all: fire event for categories to re-show all
                this._nodeBeingExpanded = false;
                RightNow.Event.fire("evt_menuFilterGetResponse", new RightNow.Event.EventObject(this, {data: {
                    reset_linked_category: true,
                    data_type: "Category",
                    reset: true
                }}));
                return;
            }
        }

        if (this.data.js.link_map) {
            //pass link map (prod linking) to EventBus for first time
            this._eo.data.link_map = this.data.js.link_map;
            delete this.data.js.link_map;
        }

        return this._eo;
    },

    /**
     * Event handler when returning from ajax data request.
     * Overrides RightNow.ProductCategory.getSubLevelResponse.
     * @param {string} type Event name
     * @param {array} args Event arguments
     */
    getSubLevelResponse: function(type, args) {
        var evtObj = args[0],
            tempNode;

        //Check if we are supposed to update : only if the original requesting widget or if category widget receiving prod links
        if ((evtObj.w_id && evtObj.w_id === this.instanceID) || (this.data.js.linkingOn && evtObj.data.data_type === "Category" && this.data.js.data_type === evtObj.data.data_type)) {
            var currentRoot;
            //prod linking : category data's being completely reset
            if (evtObj.data.reset_linked_category && this.data.js.data_type === "Category") {
                // delete link_map if we have not already so that we don't send stale data
                if (this.data.js.link_map)
                    delete this.data.js.link_map;

                if (!this.tree || evtObj.data.reset) {
                    //restore category tree to its orig. state
                    this.buildTree(true);
                    this._linkedCategorySubset = false;

                    if (this.data.attrs.verify_permissions !== 'None' && this.data.js.readableProdcatIds.length > 0) {
                        // After the tree is cleared, disable any nodes that are viewable, but not permissioned
                        this.Y.Object.each(this.Y.Object.keys(this.data.js.hierDataNone), function (key) {
                            this.disableNonPermissionedNodes(this.data.js.hierDataNone[key]);
                        }, this);
                    }
                }

                //clear out the existing tree and add 'no value' node
                currentRoot = this._requestingParent = null;
                if (!evtObj.data.reset) {
                    this._linkedCategorySubset = true;
                    this.tree.clear(this.data.attrs.label_all_values);
                }

                //since the data's being reset, reset the button's label
                this.dropdown.set('triggerText', this.data.attrs.label_nothing_selected);

                this._errorLocation = '';
                this._checkSelectionErrors();
            }
            else {
                currentRoot = this._requestingParent;
            }

            var hierLevel = evtObj.data.level,
                hierData = evtObj.data.hier_data;

            if (hierLevel <= this._maxDepth) {
                if (hierLevel === this._maxDepth) {
                    hierData = this.Y.Array.map(hierData, function (node) {
                        node.hasChildren = false;
                        return node;
                    });
                }

                if (this.data.attrs.verify_permissions !== 'None' && this.data.js.readableProdcatIds.length > 0) {
                    hierData = this.groomProdcats(hierData);
                    this.insertChildrenForNode(hierData, currentRoot);
                    this.disableNonPermissionedNodes(hierData);
                }
                else {
                    this.insertChildrenForNode(hierData, currentRoot);
                }
            }

            if (this._selected) {
                this._errorLocation = "";
                this._checkSelectionErrors();
                if (this.data.attrs.required_lvl) {
                    this._selected = false;
                }
            }

            // Focus on node we just expanded. this._requestingParent is null when prodcat linking is populating category, so ignore that case.
            if(this._requestingParent)
                this.tree.selectNodeWithValue(this._requestingParent, true);
        }
    },

    /**
     * Event handler if set_button attribute is set to true.
     */
    _setButtonClick: function()
    {
        var hierValues = [],
            labelChain = this.tree.get("labelChain"),
            valueChain = this.tree.get("valueChain");

        if (valueChain.length === 0 || valueChain[0] === 0) {
            // Nothing selected
            if (!this._errorMessageDiv) {
                this._errorMessageDiv = this.Y.Node.create("<div class='rn_MessageBox rn_ErrorMessage'/>");
                this.Y.one(this.baseSelector).prepend(this._errorMessageDiv);
            }
            this._errorMessageDiv.setHTML("<b><a href='javascript:void(0);' onclick='document.getElementById(\"" + this.displayField.get('id') + "\").focus(); return false;'>" +
                this.data.attrs.label_nothing_selected + "</a></b>");
            this._errorMessageDiv.prepend("<h2>" + RightNow.Interface.getMessage("ERROR_LBL") + "</h2>");
            this._errorMessageDiv.one("h2").setAttribute('role', 'alert');
            RightNow.UI.show(this._errorMessageDiv);
            var errorLink = this._errorMessageDiv.one('a');
            if (errorLink) {
                errorLink.focus();
            }
            return;
        }

        if (!this._checkSelectionErrors()) {
            return;
        }

        //collect node values: work back up the tree
        RightNow.UI.hide(this._errorMessageDiv);

        for(var i = 0; i < labelChain.length; i++)
            hierValues[i] = {"id" : valueChain[i], "label" : labelChain[i]};

        this.tree.resetSelectedNode();
        this.displaySelectedNodesAndClose(false, false);

        this._eo.data.hierSetData = hierValues;
        this._eo.data.id = hierValues[hierValues.length - 1].id;
        this._eo.data.f_tok = this.data.js.f_tok;

        RightNow.Event.fire("evt_menuFilterSelectRequest", this._eo);
    },

    /**
     * Event handler for when form is being validated
     * @param {string} type Event name
     * @param {array} args Event arguments
     */
    _onValidate: function(type, args) {
        var formEventObject = this.createEventObject();
        this._errorLocation = this.lastErrorLocation = args[0].data.error_location;

        if (this._checkSelectionErrors()) {
            formEventObject.data.value = this.tree.get('value') || null;

            if (formEventObject.data.required && this._notRequiredDueToProductLinking) {
                formEventObject.data.required = false;
            }

            RightNow.Event.fire("evt_formFieldValidatePass", formEventObject);
            return formEventObject;
        }

        RightNow.Event.fire("evt_formFieldValidateFailure", this._eo);
        return false;
    },

    /**
     * Creates the hint element.
     * @param {Boolean} visibility Whether the hint element is initially visible
     * @return {Object} Representing the hint element
     */
    _createHintElement: function(visibility) {
        var overlay = this.Y.Node.create("<span class='rn_HintBox'/>").set('id', this.baseDomID + '_Hint').setHTML(this.data.attrs.hint);
        if (visibility)
            overlay.addClass("rn_AlwaysVisibleHint");

        return new this.Y.Overlay({
            visible: visibility,
            align: {
                node: this.displayField,
                points: [this.Y.WidgetPositionAlign.TL, this.Y.WidgetPositionAlign.TR]
            },
            bodyContent: overlay,
            render: this.Y.one(this.baseSelector)
        });
    },

    /**
     * Toggle the display of the hint overlay if it exists and is not set to always display.
     * @param {String} hideOrShow The toggle function to call on the overlay "hide" or "show"
     */
    _toggleHint: function(hideOrShow) {
        if (this._hintOverlay && this._hintOverlay[hideOrShow] && !this.data.attrs.always_show_hint)
            this._hintOverlay[hideOrShow]();
    },

    /**
     * Realigns the hint, if it exists.
     * @param {Number} delay Number of milliseconds to delay the realignment
     */
    _realignHint: function(delay) {
        if (this._hintOverlay) {
            if (delay) {
                this.Y.later(delay, this._hintOverlay, this._hintOverlay.align);
            }
            else {
                this._hintOverlay.align();
            }
        }
    },

    /**
     * Used by Dynamic Forms to switch between a required and a non-required label
     * @param  {Object} container    The DOM node containing the label
     * @param  {Number} requiredLevel The new required level
     * @param  {String} label        The label text to be inserted
     * @param  {String} template     The template text
     */
    swapLabel: function(container, requiredLevel, label, template) {
        var templateObject = {
            label: label,
            instanceID: this.instanceID,
            fieldName: this._fieldName,
            requiredLevel: requiredLevel,
            requiredMarkLabel: RightNow.Interface.getMessage("FIELD_REQUIRED_MARK_LBL"),
            requiredLabel: RightNow.Interface.getMessage("REQUIRED_LBL")
        };

        container.setHTML('');
        container.append(new EJS({text: template}).render(templateObject));
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
        this.swapLabel(this.Y.one(this.baseSelector + '_Label'), newLevel, this.data.attrs.label_input, this.getStatic().templates.label);

        //Update the field
        this.data.attrs.required_lvl = newLevel;
        if (!newLevel) {
            this.displayField.removeClass("rn_ErrorField");
            this.Y.one(this.baseSelector + "_Label").removeClass("rn_ErrorLabel");
        }
        else {
            this._errorLocation = "";
            this._checkSelectionErrors();
        }
    },

    /**
     * Checks if the current user is able to submit with the selected node
     * @return Whether the current user is able to submit using the selected node
     */
    _checkSelectionErrors: function() {
        var currentNode = this.tree.getSelectedNode(),
            currentDepth = (currentNode ? currentNode.depth : 0) + 1,
            noErrorsFound = true;

        this._removeErrorMessages();
        this._notRequiredDueToProductLinking = false;

        if (this.data.js.linkingOn && this.data.js.data_type === "Category" && this._linkedCategorySubset) {
            // If there's some subset of categories that have been loaded then
            // allow submission if either there's only a single 'no value' node...
            if (this.tree.getNumberOfNodes() === 1) {
                this._notRequiredDueToProductLinking = true;
            }
        }

        if (this.data.attrs.required_lvl || !this.userHasFullProdcatPermissions()) {
            if (!this.tree) {
                this.buildTree();
            }

            // Don't allow submission if nothing is selected or 'no value' node is selected
            // or non-leaf/still-loading node not at the required depth is selected
            if (!this._notRequiredDueToProductLinking &&
                    (!currentNode || !currentNode.value ||
                    ((!currentNode.loaded || currentNode.hasChildren) &&
                    (currentDepth < this.data.attrs.required_lvl)))
                ) {
                var message = (!currentNode || !currentNode.value) ? this.data.attrs.label_nothing_selected : this.data.attrs.label_required;
                this._displayErrorMessage(message, currentNode);
                noErrorsFound = false;
            }
        }

        if (currentNode && currentNode.value && !this.checkPermissionsOnNode(currentNode.value)) {
            this._displayErrorMessage(this.data.attrs.label_not_permissioned, currentNode);
            noErrorsFound = false;
        }

        return noErrorsFound;
    },

    /**
     * Removes any previously set error classes from the widget's label,
     * selection button, and previously erroneous node.
     */
    _removeErrorMessages: function() {
        this.displayField.removeClass("rn_ErrorField");
        if (this.errorLabel) {
            this.errorLabel.replaceClass('rn_ErrorLabel', 'rn_Hidden');
        }
        RightNow.UI.hide(this._accessibleErrorMessageDiv);
        this._realignHint();

        if (!this.data.attrs.required_lvl && this.requiredLabel) {
            this.requiredLabel.replaceClass('rn_Required', 'rn_Hidden');
        }
    },

    /**
     * Adds error classes to the widget's label, selection button,
     * and the currently selected node. Adds the required message
     * to the form's common error location.
     * @param {string} message Message to dispaly on the error
     * @param {object} currentNode The currently selected node
     */
    _displayErrorMessage: function(message, currentNode) {
        //indicate the error
        this.displayField.addClass("rn_ErrorField");

        if(this.requiredLabel) {
            this.requiredLabel.replaceClass('rn_Hidden', 'rn_Required');
        }

        message = (message.indexOf("%s") > -1) ?
            RightNow.Text.sprintf(message, currentNode.label) :
            message;

        //write out the required label
        if (this.errorLabel) {
            this.errorLabel.setHTML(message).replaceClass('rn_Hidden', 'rn_ErrorLabel');
        }

        var label = this.data.attrs.label_error || this.data.attrs.label_input;
        //report error on common form button area
        if (this._errorLocation) {
            var commonErrorDiv = this.Y.one('#' + this._errorLocation);
            if (commonErrorDiv){
                commonErrorDiv.append("<div data-field=\"" + this._fieldName + "\"><b><a href='#' onclick='document.getElementById(\"" + this.displayField.get('id') + "\").focus(); return false;'>" +
                    label + " - " + message + "</a></b></div> ");
            }
        }
        //if the accessible dialog exists and is open, add the error message to it
        if (this.dialog) {
            this.dialog.addErrorMessageForValue(message, currentNode.value);
        }
        this._realignHint();
    }
});
