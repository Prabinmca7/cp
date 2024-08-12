RightNow.Widgets.MobileMultiline = RightNow.ResultsDisplay.extend({
    overrides: {
        constructor: function() {
            this.parent();

            this._contentDiv = this.Y.one(this.baseSelector + "_Content");
            this._loadingDiv = this.Y.one(this.baseSelector + "_Loading");

            (RightNow.Event.isHistoryManagerFragment() && this._setLoading(true));
            this.searchSource().on("response", this._onReportChanged, this)
                               .on("send", this._searchInProgress, this);
            this._setFilter();
        }
    },

    /**
     * Initialization function to set up search filters for report.
     */
    _setFilter: function() {
        var eo = new RightNow.Event.EventObject(this, {filters: {
            token: this.data.js.r_tok,
            format: this.data.js.format,
            report_id: this.data.attrs.report_id,
            allFilters: this.data.js.filters
        }});
        eo.filters.format.parmList = this.data.attrs.add_params_to_url;
        this.searchSource().fire("setInitialFilters", eo);
    },

    /**
    * Event handler received when search data is changing.
    * Shows progress icon during searches.
    */
    _searchInProgress: function() {
        this._setLoading(true);

        //Hides keyboard for mobile browsers
        document.activeElement.blur();
    },

    /**
    * Changes the loading icon and hides/unhide the data.
    * @param {Boolean} loading Whether to add or remove the loading indicators
    */
    _setLoading: function(loading) {
        if (this._contentDiv && this._loadingDiv) {
            var method, toOpacity, ariaBusy;
            if (loading) {
                ariaBusy = true;
                method = "addClass";
                toOpacity = 0;

                //keep height to prevent collapsing behavior
                this._contentDiv.setStyle("height", this._contentDiv.get("offsetHeight") + "px");
            }
            else {
                ariaBusy = false;
                method = "removeClass";
                toOpacity = 1;

                //now allow expand/contract
                this._contentDiv.setStyle("height", "auto");
            }
            document.body.setAttribute("aria-busy", ariaBusy + "");
            this._contentDiv.transition({
                opacity: toOpacity,
                duration: 0.4
            });
            this._loadingDiv[method]("rn_Loading");
        }
    },

    /**
     * Event handler received when report data is changed.
     * @param {String} type Event name
     * @param {Array} args Arguments passed with event
     */
    _onReportChanged: function(type, args) {
        var newdata = args[0].data,
            ariaLabel, firstLink,
            newContent = "";

        if (!this._contentDiv) return;

        if(newdata.total_num > 0) {
            ariaLabel = this.data.attrs.label_screen_reader_search_success_alert;
            newdata.hide_empty_columns = this.data.attrs.hide_empty_columns;
            newContent = new EJS({text: this.getStatic().templates.view}).render(newdata);
        }
        else {
            ariaLabel = this.data.attrs.label_screen_reader_search_no_results_alert;
        }

        this._updateAriaAlert(ariaLabel);
        this._contentDiv.set("innerHTML", newContent);

        if (this.data.attrs.hide_when_no_results) {
            this.Y.one(this.baseSelector)[((newContent) ? 'removeClass' : 'addClass')]('rn_Hidden');
        }

        this._setLoading(false);
        RightNow.Url.transformLinks(this._contentDiv);

        if (newdata.total_num && (firstLink = this._contentDiv.one('a'))) {
            //focus on the first result
            firstLink.focus();
            // Scroll back to top of the report because focus on mobile devices is finicky
            this._topCoordinate || (this._topCoordinate = this.Y.one(this.baseSelector).getY() - 20);
            window.scrollTo(0, this._topCoordinate);
        }
    },

    /**
     * Updates the text for the ARIA alert div that appears above the results listings.
     * @private
     * @param {String} text The text to update the div with
     */
    _updateAriaAlert: function(text) {
        if (!text) return;
        this._ariaAlert = this._ariaAlert || this.Y.one(this.baseSelector + "_Alert");
        if(this._ariaAlert) {
            this._ariaAlert.set("innerHTML", text);
        }
    }
});
