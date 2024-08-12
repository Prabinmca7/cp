RightNow.Widgets.AdvancedSearchDialog = RightNow.SearchFilter.extend({
    overrides: {
        constructor: function() {
            this.parent();
            this.Y.one(this.baseSelector + "_TriggerLink").on("click", this._openDialog, this);
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
                    {text: this.data.attrs.label_search_button, handler: {fn: this._performSearch, scope: this}},
                    {text: this.data.attrs.label_cancel_button, handler: {fn: this._cancelFilters, scope: this}}];
                this._dialog = RightNow.UI.Dialog.actionDialog(this.data.attrs.label_dialog_title, dialogDiv, {buttons: buttons});
                this.Y.one("#" + this._dialog.get('id')).addClass("rn_AdvancedSearchDialog");
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
        this._dialog.show();
        // Close icon ('x') click
        this._dialog.hideEvent.subscribe(this._cancelFilters, null, this);
    },
    
    /**
    * Perform a search by firing the searchRequest event
    * and closes the dialog
    */
    _performSearch: function() {
        this._closeDialog();
        var searchPage = this.data.attrs.report_page_url;
        this.searchSource().fire("search", new RightNow.Event.EventObject(this, {filters: {
            report_id: this.data.attrs.report_id,
            reportPage: searchPage,
            newPage: top !== self || (searchPage !== "" && searchPage !== "{current_page}") || !RightNow.Url.isSameUrl(searchPage)
        }}));
    },

    /**
    * Resets all search filters
    */
    _cancelFilters: function() {
        if(this._dialogClosed) return;

        this._closeDialog();
        this.searchSource().fire("reset", new RightNow.Event.EventObject(this, {data: {name: "all"}, filters: {report_id: this.data.attrs.report_id}}));
    },

    /**
    * Closes the dialog
    */
    _closeDialog: function() {
        this._dialogClosed = true;
        if(this._dialog)
            this._dialog.hide();
    }
});
