RightNow.Widgets.ModerationFilterDialog = RightNow.SearchFilter.extend({
    overrides: {
        constructor: function() {
            this.parent();
            this._eo = new RightNow.Event.EventObject(this);
            this.Y.one(this.baseSelector + "_TriggerLink").on("click", this._openDialog, this);
            this._moderationDateFilter = this.Y.one('.rn_ModerationDateFilter');
            this._customDateFilterEnabled = false;
            RightNow.Event.subscribe('evt_moderationCustomDateFilterEnabled', function(){
                this._customDateFilterEnabled = true;
                RightNow.Event.subscribe('evt_moderationDateFilterValidated', this._performSearch, this);
            }, this);
            this._errorMessageDiv = this.Y.one(this.baseSelector + "_ErrorLocation");
        }
    },

    /**
     * Build the dialog the first time; show the dialog subsequent times
     * @param {Object} evt Click event
     */
    _openDialog: function(evt) {
        if (!this._dialog) {
            var dialogDiv = this.Y.one(this.baseSelector + "_DialogContent");
            if (dialogDiv) {
                var buttons = [
                    {text: this.data.attrs.label_apply_button, handler: {fn: (this._customDateFilterEnabled || (this.data.attrs.object_type !== 'CommunityUser' && this.Y.Array.indexOf(this.data.attrs.include_filters, 'date') > -1 && this._moderationDateFilter !== null)) ? this._validateDateFilter : this._performSearch, scope: this}},
                    {text: this.data.attrs.label_cancel_button, handler: {fn: this._cancelFilters, scope: this}}];
                this._dialog = RightNow.UI.Dialog.actionDialog(this.data.attrs.label_dialog_title, dialogDiv, {buttons: buttons});
                this.Y.one("#" + this._dialog.get('id')).addClass("rn_ModerationFilterDialog");
                if (this.data.attrs.object_type === 'CommunityUser') {
                    this.Y.one("#" + this._dialog.get('id')).addClass("rn_ModerationFilterDialogSocialUser");
                }

                RightNow.UI.show(dialogDiv);

                //focus on trigger link when the dialog closes
                this._dialog.hideEvent.subscribe(function() {
                    if (evt.target.focus) {
                        evt.target.focus();
                    }
                }, null, this);
            }
        }
        this._dialogClosed = false;
        if (this._dialog) {
            this._dialog.show();
            // Close icon ('x') click
            this._dialog.hideEvent.subscribe(this._cancelFilters, null, this);
        }
    },

    /**
     * Perform a search for the selected filters by firing the searchRequest event and closes the dialog
     */
    _validateDateFilter: function() {
        if (this.searchSource().searchSource && parseInt(this.searchSource().searchSource.id, 10) === this.data.attrs.report_id) {
            RightNow.Event.fire("evt_validateModerationDateFilter", new RightNow.Event.EventObject(this, {data: {
                    report_id: this.data.attrs.report_id
                }}));
        }
    },

    /**
     * Fires the report search event
     * @param {String} evt Event Name
     * @param {Object} args Event data
     */
    _performSearch: function(evt, args) {
        this._errorMessageDiv.get('childNodes').remove();
        if (args && args[0]){
            if (this.data.attrs.report_id !== parseInt(args[0].data.report_id, 10)) {
                return;
            }
            if (args[0].data.errors) {
                this._displayError(args[0].data.errors);
                return;
            }
        }
        if (this.searchSource().searchSource && parseInt(this.searchSource().searchSource.id, 10) === this.data.attrs.report_id) {
            this._closeDialog();
            this.searchSource().fire("search", new RightNow.Event.EventObject(this, {
                filters: {
                    report_id: this.data.attrs.report_id
                }
            }));
        }
    },

    /**
     * Constructs the the error messages HTML and displays
     * @param {Object} errors Error messages
     */
    _displayError: function(errors) {
        errors.forEach(function(error) {
            this._errorMessageDiv.appendChild(error);
            this._errorMessageDiv.addClass("rn_MessageBox").addClass("rn_ErrorMessage").removeClass("rn_Hidden").scrollIntoView();
        }, this);
        var errorLbl = this._errorMessageDiv.all("a").size() > 1 ? RightNow.Interface.getMessage("ERRORS_LBL") : RightNow.Interface.getMessage("ERROR_LBL");
        this._errorMessageDiv.prepend("<h2>" + errorLbl + "</h2>");
        this._errorMessageDiv.one("h2").setAttribute('role', 'alert');
    },

   /**
    * Resets all search filters
    */
    _cancelFilters: function() {
        this._errorMessageDiv.addClass("rn_Hidden");
        if (this.searchSource().searchSource && parseInt(this.searchSource().searchSource.id, 10) === this.data.attrs.report_id) {
            if(this._dialogClosed) return;
            this._closeDialog();
            //Product filter clears the filter on reset if name is passed all
            this._eo.data.name = "all";
            this.searchSource().fire("reset", this._eo);
        }
    },

    /**
     * Closes the dialog
     */
    _closeDialog: function() {
        this._dialogClosed = true;
        if (this._dialog) {
            this._dialog.hide();
        }
    }
});
