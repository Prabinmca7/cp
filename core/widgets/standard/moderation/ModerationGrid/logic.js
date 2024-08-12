RightNow.Widgets.ModerationGrid = RightNow.Widgets.Grid.extend({
    /**
     * Place all properties that intend to
     * override those of the same name in
     * the parent inside `overrides`.
     */
    overrides: {
        /**
         * Overrides RightNow.Widgets.Grid#constructor.
         */
        constructor: function() {
            this._confirmDialog = null;
            this._actionEventArgument = null;
            this._contentName = this.baseSelector + '_Content';
            this._hasValidationMessage = false; //used in unit test case
            this._errorDisplay = null;

            this.Y.one(this._contentName).delegate('click', this._toggleSelection, 'table th input[type="checkbox"]', this);
            this.Y.one(this._contentName).delegate('click', this._onRowSelection, 'table td input[type="checkbox"]', this);
            //subscribe to moderation action event, which is triggered from Action widget
            RightNow.Event.subscribe('evt_moderationAction', this._onModerationAction, this);
            this._eo = new RightNow.Event.EventObject(this, {
                filters: {
                    report_id: this.data.attrs.report_id,
                    per_page: this.data.attrs.per_page,
                    page: RightNow.Url.getParameter('page') || 1
                }
            });
            this.parent();
            this.searchSource().on("search", this._onReportSearch, this);
            this.Y.augment(this, RightNow.Avatar);
        },

        /**
         * Event handler received when report search request is triggered
         *
         * @param type String Event type
         * @param args Object Arguments passed with event
         */
        _onReportSearch: function(type, args) {
            if (this._errorDisplay && args[0].w_id !== this.instanceID) {
                this._errorDisplay.hide();
            }
        },

        _onReportChanged: function(type, args) {
            this._toggleProdCatColumn(args);
            if(args[0].data.data !== undefined && args[0].data.data.length !== undefined && args[0].data.data.length == '0' && RightNow.Url.getParameter('page') !== '1') {
                this._eo.filters.page = RightNow.Url.getParameter('page') - 1;
                this.searchSource().fire("appendFilter", this._eo).fire("search", this._eo);
            }
            else 
                this.parent(type, args);
        },

        /**
        * Update the header data (e.g. Using checkbox for the first column instead of default label) before it gets rendered by YUI data table
        *
        * @param {Array} headers An array of header information used to build column data.
        */
        _generateYUITable: function(headers) {
            var data = {
                headers: headers,
                currentRow:0,
                selectAll:this.data.attrs.label_select_all,
                rowExist: this.Y.all(this._contentName + ' table td input[type="checkbox"]').size() > 0
            };
            var ejsHeaderObj = new EJS({text: this.getStatic().templates.tableHeader});
            for (var i = 0; i < headers.length; i++) {
                data.currentRow = i;
                headers[i].heading  = this.Y.Lang.trim(ejsHeaderObj.render(data));
            }
            this.parent(headers);
        }
    },

    /**
     * Toggles the display of Product and Category columns
     * @param {array} args Array of headers and data
     */
    _toggleProdCatColumn: function(args) {
        var prodColumnIndex = this.data.attrs.product_column_index - 1;
        var catColumnIndex = this.data.attrs.category_column_index - 1;
        if (this.data.attrs.prodcat_type === 'Category') {
            if (args[0].data.headers) {
                this.Y.Object.each(Object.keys(args[0].data.headers[catColumnIndex]), function(key) {
                    args[0].data.headers[prodColumnIndex][key] = args[0].data.headers[catColumnIndex][key];
                }, this);
                args[0].data.headers[prodColumnIndex].visible = true;
            }
            if (args[0].data.data) {
                for (i = 0; i < args[0].data.data.length; i++) {
                    args[0].data.data[i][prodColumnIndex] = args[0].data.data[i][catColumnIndex];
                }
            }
        }
    },

    /**
    * Toggle the social content selection based on checkAll checkbox selection status
    * @param {Object} e currently clicked action element
    */
    _toggleSelection: function(e) {
        this.Y.all(this._contentName + ' table td input[type=checkbox]').set("checked",  e.currentTarget.get('checked'));
        this._onRowSelection();
    },

    /**
     * Fires an event to enable/disable the action bar buttons
     * @param {Object} e currently clicked action element
     */
    _onRowSelection: function(e) {
        var isRowSelected = this.Y.one(this._contentName + ' table td input:checked') ? true : false;
        this._actionEventObj = new RightNow.Event.EventObject(this, {data: {
            w_id: this.data.info.w_id,
            isRowSelected: isRowSelected,
            report_id: this.data.attrs.report_id
        }});
        RightNow.Event.fire("evt_rowSelected", this._actionEventObj);
    },

     /*
     * Creates and displays a warning dialog to the user asking whether they would like
     * to continue the delete action
     */
    _confirmAndSubmitRequest: function() {
        var buttons = [
            { text: RightNow.Interface.getMessage("YES_LBL"), handler: {fn: this._submitRequest, scope: this}},
            { text: RightNow.Interface.getMessage("NO_LBL"), handler: {fn: this._hideConfirmDialog, scope: this}, isDefault: true }
         ];
         if (!this._confirmDialog) {
             var dialogBody = this.Y.Node.create("<div>").set("innerHTML", this.data.attrs.label_delete_social_object_confirm);
             this._confirmDialog = RightNow.UI.Dialog.actionDialog(RightNow.Interface.getMessage("WARNING_LBL"), dialogBody, {"buttons" : buttons});
             this.Y.DOM.addClass(this._confirmDialog.id, 'rn_dialog');
             RightNow.UI.Dialog.addDialogEnterKeyListener(this._confirmDialog, this._submitRequest, this);
         }
         this._confirmDialog.show();
    },

    /**
    * Collect values from selected checkboxes. Checkbox values are either object ID as a string OR collection of IDs as a JS array string (e.g [ID1, ID2]).
    * @param {string} action_name Name of the requested action
    * @return {Object} List of selected social object ids in 'object_ids' property and 'rowDataExist' property with value true if page has HTML table rows with checkboxes
    */
    _getSelectedObjectIDs: function(action_name) {
        var rowDataExist = false,
            selectedObjectIDs = [];
        //attribute pseudo selectors are not working in IE8, so iterating it to collect all selected social_object IDs
        this.Y.all(this._contentName + ' table td input[type="checkbox"]').each(function(inputObj) {
            rowDataExist = true;
            if (inputObj.get('checked')) {
                var objectID = this.Y.JSON.parse(inputObj.get('value'));
                if (this.Y.Lang.isArray(objectID)) {
                    selectedObjectIDs.push((this.Y.Array.indexOf(['suspend_user', 'restore_user'], action_name) !== -1) ? objectID[1] : objectID[0]);
                }
                else {
                    selectedObjectIDs.push(objectID);
                }
            }
        }, this);

        return {"rowDataExist": rowDataExist, "object_ids": this.Y.Array.dedupe(selectedObjectIDs)};
    },

    /**
    * Validate the user selection and perform AJAX request when user click on any action button,
    * @param {Object} evt current even object
    * @param {Array} args data passed from widget which trigger this function call
    */
    _onModerationAction: function(evt, args) {
        this._modActionPerformed = true;
        this._actionEventArgument = args;
        if (this.data.attrs.report_id === parseInt(this._actionEventArgument[0].data.report_id, 10)) {

            var selectedObjects = this._getSelectedObjectIDs(this._actionEventArgument[0].data.action_name);
            if (!selectedObjects.rowDataExist) {
                return false;
            }

            this._actionEventArgument.selectedObjectIDs = selectedObjects.object_ids;

            this._errorDisplay = this.Y.one('#' + this._actionEventArgument[0].data.message_location);
            if (this._actionEventArgument.selectedObjectIDs.length === 0) {
                this._addErrorOrSuccessMessage(this._errorDisplay, this.data.attrs.label_select_social_object_error);
                return false;
            }
            else if (this._actionEventArgument.selectedObjectIDs.length > this.data.attrs.max_allowed_selected_rows) {
                this._addErrorOrSuccessMessage(this._errorDisplay, RightNow.Text.sprintf(this.data.attrs.label_max_allowed_selected_rows_error, this.data.attrs.max_allowed_selected_rows));
                return false;
            }
            //confirm the delete action
            if (this.data.js.statuses.deleted && typeof this.data.js.statuses.deleted[this._actionEventArgument[0].data.action] !== 'undefined') {
                this._confirmAndSubmitRequest();
                return;
            }
            this._submitRequest();
        }
    },

     /*
     * Create event object and make AJAX request for a requested action
     */
    _submitRequest: function () {
        this._hideConfirmDialog();
        this._removeErrorMessage(this._errorDisplay);
        this._moderationActionSelector = this._actionEventArgument[0].data.moderation_action_baseselector;

        var eventObj = new RightNow.Event.EventObject(this, {data:{
            w_id: this.data.info.w_id,
            object_ids: this._actionEventArgument.selectedObjectIDs.join(','),
            action: this._actionEventArgument[0].data.action,
            action_name: this._actionEventArgument[0].data.action_name
        }});
        if (this.data.attrs.object_type === 'CommunityQuestion' && this._actionEventArgument[0].data.action_name === 'move') {
            eventObj.data.prodcat_id = this._actionEventArgument[0].data.prodcat_id;
            eventObj.data.prodcat_type = this._actionEventArgument[0].data.prodcat_type;
        }
        this.Y.all(this._moderationActionSelector + " button").set("disabled", true);
        
        if(this.data.js.f_tok){
            RightNow.Event.subscribe("evt_formTokenUpdate", RightNow.Widgets.onFormTokenUpdate, this);
            // Get a new f_tok value on each ajax request
            RightNow.Event.fire("evt_formTokenRequest", new RightNow.Event.EventObject(this, {data:{formToken:this.data.js.f_tok}}));

            eventObj.data.f_tok = this.data.js.f_tok;
        }

        RightNow.Ajax.makeRequest(this.data.attrs.moderate_social_object_ajax, eventObj.data, {
            successHandler: this._onActionSuccess,
            failureHandler: this._onActionFailed,
            scope:          this,
            data:           eventObj,
            json:           true
        });
    },

    /*
     * Hide confirm box when user click on No / Confirmed to perform an action.
     */
    _hideConfirmDialog: function () {
        if (this._confirmDialog) {
            this._confirmDialog.hide();
        }
    },

    /**
    * Refresh the filter, reload the table upon successful reponse
    * @param {Object} response object which has success or error message to display
    * @param {Object} eventObj used for AJAX Request
    */
    _onActionSuccess: function(response, eventObj) {
        if (typeof response.success !== 'undefined' || typeof response.error !== 'undefined') {
            this._addErrorOrSuccessMessage(this._errorDisplay, response);
        }
        //fetch new report only when report_refresh_required is set to true OR any unknown error (to be safer, it is good to fetch new report when response is not a valid JSON object due to some unknown error)
        if (typeof response.report_refresh_required === 'undefined' || response.report_refresh_required === true) {
            //Clear the cache data since there is a moderation action
            this.searchSource().clearCache();
            this._eo.filters.page = RightNow.Url.getParameter('page') || 1;
            this.searchSource().fire("appendFilter", this._eo).fire("search", this._eo);
        }
        else {
            this.Y.all(this._moderationActionSelector + " button").set("disabled", false);
        }
    },

    /**
    * Reset the form and show the error message when there is a failure
    * @param {Object} response object which has success or error message to display
    * @param {Object} eventObj used for AJAX Request
    */
    _onActionFailed: function(response, eventObj) {
        this.Y.all(this._moderationActionSelector + " button").set("disabled", false);
        this._addErrorOrSuccessMessage(this._errorDisplay, response.ajaxError);
    },

     /**
     * Adds an error message to the page and adds the correct CSS classes
     * @param {Object} errorDisplay Error Message Container ID
     * @param {String | Object} message Error/Message to display to the user
     */
    _addErrorOrSuccessMessage: function(errorDisplay, message) {
        this._hasValidationMessage = true;
        if (!errorDisplay) return;
        errorDisplay.show();
        if (!this.Y.DOM.inViewportRegion(this.Y.Node.getDOMNode(errorDisplay), true)) {
            (new this.Y.Anim({
                node: this.Y.one(document.body),
                to:   { scrollTop: errorDisplay.get('offsetTop') - 40 },
                duration: 0.5
            })).run();
        }

        this._removeErrorMessage(errorDisplay);
        var newMessage  = message || this.data.attrs.label_requested_action_not_performed_error,
            cssMsgClass = "rn_MessageBox rn_ErrorMessage";
        errorDisplay.removeClass();

        if (this.Y.Lang.isObject(message)) {
            if (message.success) {
                cssMsgClass = "rn_MessageBox rn_InfoMessage";
                var actualMessage = message.success;
            }
            else if(message.error) {
                var actualMessage = message.error;
            }
            if(typeof actualMessage !== 'undefined' && errorDisplay && actualMessage.length > 0) {
                newMessage = (actualMessage.length === 1) ? actualMessage[0] : actualMessage.shift() + '<br/><br/>' + this.Y.Object.values(actualMessage).join('<br>');
            }
        }
        errorDisplay.addClass(cssMsgClass).set("innerHTML", newMessage);
        errorDisplay.set('tabIndex', 0);
        // Focusing 1/2 second later helps screen readers announce the message correctly.
        this.Y.Lang.later(500, errorDisplay, errorDisplay.focus);
        if(!message.success){
            errorDisplay.one("h2") ? errorDisplay.one("h2").setHTML(RightNow.Interface.getMessage("ERRORS_LBL")) : errorDisplay.prepend("<h2>" + RightNow.Interface.getMessage("ERROR_LBL") + "</h2>");
            errorDisplay.one("h2").setAttribute('role', 'alert');
        }
    },

     /**
     * Removes classes and content from the given node
     * @param {Object} errorDisplay YUI Node/object
     */
    _removeErrorMessage: function(errorDisplay) {
        this._hasValidationMessage = false;
        if (!errorDisplay) return;
        errorDisplay.setHTML('').set('className', '');
    }

});