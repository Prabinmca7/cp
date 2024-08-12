RightNow.Widgets.ModerationAction = RightNow.SearchFilter.extend({
    overrides: {
        constructor: function() {
            this.parent();
            this._eo = new RightNow.Event.EventObject(this);
            this.searchSource().on("response", this._onReportChanged, this);
            this.searchSource().on("search", this._onReportSearch, this);
            this._instanceElement = this.Y.one(this.baseSelector);
            this.Y.all(this.baseSelector + "_ActionButtons button").on("click", this._onAction, this);
            RightNow.Event.subscribe('evt_productCategorySelected', this._getProdcatID, this);
            RightNow.Event.subscribe('evt_rowSelected', this._onRowSelected, this);
            this._actionEventObj = {};
            this.Y.all(this.baseSelector + " button").set("disabled", true);
            this._errorMessageDiv = this.Y.one(this.baseSelector + "_ErrorLocation");
        }
    },

    /**
     * Event handler received when report search request is triggered
     *
     * @param type String Event type
     * @param args Object Arguments passed with event
     */
    _onReportSearch: function(type, args)
    {
        if (parseInt(args[0].filters.report_id, 10) === this.data.attrs.report_id) {
            this.Y.all(this.baseSelector + " button").set("disabled", true);
        }
    },

    /**
     * Event handler received when report data is changed, and show/hide the action button based on result set
     *
     * @param type String Event type
     * @param args Object Arguments passed with event
     */
    _onReportChanged: function(type, args)
    {
        if (parseInt(args[0].filters.report_id, 10) === this.data.attrs.report_id) {
            if ((typeof args[0].data.total_pages === 'undefined') || args[0].data.total_pages <= 0 ) {
                RightNow.UI.hide(this._instanceElement);
            }
            else {
                RightNow.UI.show(this._instanceElement);
            }
            this.Y.all(this.baseSelector + " button").set("disabled", true);
        }
    },

   /**
    * fire an action event, which will be listend by QuestionModerationGrid
    * @param {e} currently clicked action element
    */
    _onAction: function(e) {
        this._actionEventObj = new RightNow.Event.EventObject(this, {data: {
            w_id: this.data.info.w_id,
            action: e.currentTarget.get('value'),
            action_name: e.currentTarget.get('name'),
            message_location: this.data.attrs.message_location,
            moderation_action_baseselector: this.baseSelector,
            report_id: this.data.attrs.report_id
        }});

        if(e.currentTarget.get('value') === 'move') {
            this._openMoveQuestionDialog();
        }
        else {
            RightNow.Event.fire("evt_moderationAction", this._actionEventObj);
        }
    },

    /**
     * Creates and opens a move question dialog
     */
    _openMoveQuestionDialog: function() {
        this.prodcatID = 0;
        var dialogBody = this.Y.one(this.baseSelector + "_MoveDialogBody");
        if(dialogBody && !this._moveQuestionDialog) {
            dialogBody = this.Y.Node.getDOMNode(dialogBody.removeClass('rn_Hidden'));
            var buttons = [
                {text: this.data.attrs.label_move_dialog_move_button, handler: {fn: this._moveQuestion, scope: this}, isDefault: false},
                {text: this.data.attrs.label_move_dialog_cancel_button, handler: {fn: this._closeMoveQuestionDialog, scope: this}, isDefault: false}
            ];
            this._moveQuestionDialog = RightNow.UI.Dialog.actionDialog(this.data.attrs.label_move_dialog_title, dialogBody, {"buttons": buttons});
            this.Y.one('#' + this._moveQuestionDialog.id).ancestor('.yui3-panel').addClass("rn_MoveDialogContainer");
            RightNow.UI.show(dialogBody);
        }
        this._moveQuestionDialog.show();
        // Close on clicking the close icon (x)
        this._moveQuestionDialog.hideEvent.subscribe(this._closeMoveQuestionDialog, null, this);
    },

    /**
     * Event handler executed when a product or category is selected from menu
     * @param {Object} evt Current event object
     * @param {Array} eventData Event data
     */
    _getProdcatID: function(evt, eventData) {
        this.prodcatID = eventData[0].data.hierChain[eventData[0].data.hierChain.length - 1];
    },

    /**
     * Event handler executed when grid fires an event to notify that atleast one row is selected
     * @param {Object} evt Current event object
     * @param {Array} eventData Event data
     */
    _onRowSelected: function(evt, eventData) {
        if (this.data.attrs.report_id === parseInt(eventData[0].data.report_id, 10)) {
            this.isRowSelected = eventData[0].data.isRowSelected;
            this.Y.all(this.baseSelector + " button").set("disabled", !this.isRowSelected);
        }
    },

   /**
    * Fires event to update the move action
    * @param {Object} evt Current event object
    */
    _moveQuestion: function(evt) {
        var selectorString = '#' + this._moveQuestionDialog.id + ' form' + this.baseSelector + "_Form ",
            errorLabelSpan = this.Y.one(selectorString + 'span.rn_ErrorLabel'),
            errorSpanHidden = false;
        if(errorLabelSpan === null) {
            errorLabelSpan = this.Y.one(selectorString + 'span.rn_Hidden');
            errorSpanHidden = true;
        }
        if(errorSpanHidden && this.prodcatID) {
            this._actionEventObj.data.prodcat_type = this.data.attrs.prodcat_type;
            this._actionEventObj.data.prodcat_id = this.prodcatID;
            RightNow.Event.fire("evt_moderationAction", this._actionEventObj);
            this._closeMoveQuestionDialog();
        }
        else if(this.prodcatID === 0) {
            if(this.data.attrs.prodcat_type === 'Product')
                errorLabelSpan.setHTML(this.data.attrs.label_select_product);
            else if(this.data.attrs.prodcat_type === 'Category')
                errorLabelSpan.setHTML(this.data.attrs.label_select_category);
            if(errorSpanHidden)
                errorLabelSpan.replaceClass('rn_Hidden', 'rn_ErrorLabel');
        }
    },

   /**
    * Closes the move question dialog
    * @param {Object} evt Current event object
    */
    _closeMoveQuestionDialog: function(evt) {
        this.prodcatID = 0;
        if(this._moveQuestionDialog) {
            RightNow.Event.fire("evt_resetProductCategoryMenu", new RightNow.Event.EventObject(this));
            this._moveQuestionDialog.hide();
        }
    }
});
