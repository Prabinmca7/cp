RightNow.Widgets.SearchButton = RightNow.SearchFilter.extend({
    overrides: {
        constructor: function() {
            this.parent();

            this._requestInProgress = false;
            this._searchButton = this.Y.one(this.baseSelector + "_SubmitButton");
            if (this._searchButton) {
                this._searchButton.on("click", this._startSearch, this);
                this.searchSource().on("response", this._enableClickListener, this);
            }
        }
    },

    /**
    * Event handler executed when the button is clicked
    * @param {Object} evt Event
    */
    _startSearch: function(evt) {
        if (this._requestInProgress) return;

        if (!this.data.attrs.popup_window && !this.data.attrs.report_page_url )
            this._disableClickListener();

        if (this.Y.UA.ie) {
            // since the form is submitted by script, deliberately tell IE to do auto completion of the form data
            this._parentForm = this._parentForm || this.Y.one(this.baseSelector).ancestor("form");
            if (this._parentForm && window.external && "AutoCompleteSaveForm" in window.external) {
                window.external.AutoCompleteSaveForm(this.Y.Node.getDOMNode(this._parentForm));
            }
        }
        var searchPage = this.data.attrs.report_page_url;
        this.searchSource().fire("search", new RightNow.Event.EventObject(this, {filters: {
            report_id: this.data.attrs.report_id,
            source_id: this.data.attrs.source_id,
            reportPage: searchPage,
            newPage: this.data.attrs.force_page_flip || top !== self || (searchPage !== "" && searchPage !== "{current_page}" && !RightNow.Url.isSameUrl(searchPage)),
            target: this.data.attrs.target,
            popupWindow: this.data.attrs.popup_window,
            width: this.data.attrs.popup_window_width_percent,
            height: this.data.attrs.popup_window_height_percent
        }}));
    },

    /**
     * Sets the button click listener.
     */
    _enableClickListener: function() {
        //add call back function again only if it was detached by _disableClickListener
        if(this._requestInProgress){
            this._searchButton.on("click", this._startSearch, this);
        }
        this._requestInProgress = false;
    },

    /**
     * Removes the button click listener.
     */
    _disableClickListener: function() {
        this._requestInProgress = true;
        this._searchButton.detach("click", this._startSearch);
    }
});
