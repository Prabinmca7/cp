RightNow.Widgets.ResultInfo = RightNow.SearchFilter.extend({
    overrides: {
        constructor: function() {
            this.parent();

            this._searchSources = 0;
            if(this.data.attrs.display_knowledgebase_results) {
                this.searchSource(this.data.attrs.report_id).on('response', this._onReportChanged, this);
            }
            if(this.data.attrs.combined_results) {
                this._searchTerm = this.data.js.searchTerm;
                if(this.data.js.social) {
                    if (this.data.js.social) {
                        this._searchSources++;
                    }
                    this.searchSource(this.data.attrs.source_id).on('response', this._reportCombinedResults, this);
                }
                this.searchSource().
                    on('appendFilter',  function(evt, args) {
                        if (args[0].filters.page && args[0].data) {
                            this._page = args[0].data.page;
                        }
                    }, this);
                this.searchSource(this.data.attrs.report_id).on('send', this._watchSearchFilterChange, this);
            }
            if (this.data.js.error) {
                RightNow.UI.Dialog.messageDialog(this.data.js.error, {"icon": "WARN"});
            }
        }
    },

    /**
     * Event handler received when report data is changed
     *
     * @param type String Event type
     * @param args Object Arguments passed with event
     */
    _onReportChanged: function(type, args)
    {
        var newData = args[0].data,
            resultQuery = "",
            parameterList = (this.data.attrs.add_params_to_url)
                ? RightNow.Url.buildUrlLinkString(args[0].filters.allFilters, this.data.attrs.add_params_to_url)
                : '';

        this._determineNewResults(args[0]);

        //construct search results message for the searched-on terms
        if(!this.data.attrs.combined_results && this.data.attrs.display_results && newData.search_term)
        {
            var stopWords = newData.stopword,
                noDictWords = newData.not_dict,
                searchTerms = newData.search_term.split(" "),
                displayedNoResultsMsg = false;

            for(var i = 0, word, strippedWord; i < searchTerms.length; i++)
            {
                word = searchTerms[i];
                strippedWord = word.replace(/\W/, "");
                if(stopWords && strippedWord && stopWords.indexOf(strippedWord) !== -1)
                    word = "<span class='rn_Strike' title='" + this.data.attrs.label_common + "'>" + word + "</span>";
                else if(noDictWords && strippedWord && noDictWords.indexOf(strippedWord) !== -1)
                    word = "<span class='rn_Strike' title='" + this.data.attrs.label_dictionary + "'>" + word + "</span>";
                else
                    word = "<a href='" + RightNow.Url.addParameter(this.data.js.linkUrl + encodeURIComponent(word.replace(/\&amp;/g, "&")) + parameterList + "/search/1", "session", RightNow.Url.getSession()) + "'>" + word + "</a>";
                resultQuery += word + " ";
            }
            resultQuery = this.Y.Lang.trim(resultQuery);
        }

        // suggested
        var suggestedDiv = this.Y.one(this.baseSelector + "_Suggestion");
        if(suggestedDiv)
        {
            if(newData.ss_data)
            {
                var links = this.data.attrs.label_suggestion + " <ul>";
                for(var i = 0; i < newData.ss_data.length; i++)
                    links += '<li><a href="' + this.data.js.linkUrl + newData.ss_data[i] + '/suggested/1' + parameterList + '">' + newData.ss_data[i] + '</a></li>';
                links += "</ul>";
                suggestedDiv.set('innerHTML', links)
                            .removeClass('rn_Hidden');
            }
            else
            {
                RightNow.UI.hide(suggestedDiv);
            }
        }

        // spelling
        var spellingDiv = this.Y.one(this.baseSelector + "_Spell");
        if(spellingDiv)
        {
            if(newData.spelling)
            {
                spellingDiv.set('innerHTML', this.data.attrs.label_spell + ' <a href="' + this.data.js.linkUrl + newData.spelling + '/dym/1/' + parameterList + '">' + newData.spelling + ' </a>')
                           .removeClass('rn_Hidden');
            }
            else
            {
                RightNow.UI.hide(spellingDiv);
            }
        }
        if(!(newData.data !== undefined && newData.data.length !== undefined && newData.data.length == '0' && RightNow.Url.getParameter('page') !== '1')) 
            this._updateSearchResults({
                searchTermToDisplay: resultQuery,
                userSearchedOn: newData.search_term,
                topics: newData.topics,
                truncated: newData.truncated
            });
        if(!this.data.attrs.combined_results)
        {
            this.data.js.totalResults = 0;
            this.data.js.firstResult = 0;
            this.data.js.lastResult = 0;
        }
    },

    /**
     * Updates the search results areas
     * @private
     * @param {?Object} Containing key-values:
     *      searchTermToDisplay: String search term
     *      userSearchedOn: String the orig. terms the user searched
     *      topics: Boolean whether topics were part of the results
     *      truncated: Boolean whether the result set was truncated
     */
    _updateSearchResults: function(options)
    {
        options = options || {};
        var noResultsDiv = this.Y.one(this.baseSelector + "_NoResults"),
            resultsDiv = this.Y.one(this.baseSelector + "_Results"),
            searchTermToDisplay = options.searchTermToDisplay,
            displayedNoResultsMsg = false;

        if(noResultsDiv)
        {
            if(this.data.js.totalResults === 0 && options.userSearchedOn && (!options.topics || options.topics.length === 0))
            {
                noResultsDiv.set('innerHTML', this.data.attrs.label_no_results + "<br/><br/>" + this.data.attrs.label_no_results_suggestions)
                            .removeClass('rn_Hidden');
                displayedNoResultsMsg = true;
            }
            else
            {
                RightNow.UI.hide(noResultsDiv);
            }
        }
        if(resultsDiv)
        {
            if(!displayedNoResultsMsg && !options.truncated)
            {
                resultsDiv.set('innerHTML', (searchTermToDisplay && searchTermToDisplay.length > 0)
                    ? RightNow.Text.sprintf(this.data.attrs.label_results_search_query, this.data.js.firstResult, this.data.js.lastResult, this.data.js.totalResults, searchTermToDisplay)
                    : RightNow.Text.sprintf(this.data.attrs.label_results, this.data.js.firstResult, this.data.js.lastResult, this.data.js.totalResults));
                RightNow.UI.show(resultsDiv);
            }
            else
            {
                RightNow.UI.hide(resultsDiv);
            }
        }
    },

    /**
     * Tallies up totalResults, firstResult, lastResult to be displayed
     * @private
     * @param {Object} eventObject Event object from report response
     */
    _determineNewResults: function(eventObject) {
        var reportData = eventObject.data;

        if(this.data.attrs.combined_results) {
            if(this.data.js.totalResults === 0 || this.data.js.totalResults === this.data.js.combinedResults) {
                // new result set just came back: build back up totalResults
                this.data.js.totalResults += reportData.total_num;
            }
            if(typeof reportData.pruned === "number") {
                // total results only decremented once per result set
                this.data.js.totalResults -= reportData.pruned;
            }
            if(typeof this.data.js.prunedAnswers === "number" && !reportData.pruned) {
                // when a new page of pruned results is navigated to
                reportData.start_num -= this.data.js.prunedAnswers;
                reportData.end_num -= this.data.js.prunedAnswers;
                reportData.pruned = true;
            }
        }
        else {
            this.data.js.totalResults = reportData.total_num;
        }

        this.data.js.firstResult = reportData.start_num;
        if(reportData.page !== 1) {
            this.data.js.firstResult += this.data.js.combinedResults;
        }
        if(this.data.js.firstResult === 0 && this.data.js.combinedResults !== 0) {
            this.data.js.firstResult = 1;
        }
        this.data.js.lastResult = reportData.end_num + this.data.js.combinedResults;
        this._page = reportData.page;

        if(reportData.pruned && eventObject.w_id && eventObject.w_id.indexOf("CombinedSearchResults") > -1) {
            // a pruning occurred; keep track for next pages of results
            this.data.js.prunedAnswers = (this.data.js.prunedAnswers === reportData.pruned) ? false : reportData.pruned;
        }
    },

    /**
     * Listens to Social search responses and adds the combined results.
     * @private
     * @param {String} evt Event name
     * @param {Object} args Event object
     */
    _reportCombinedResults: function(evt, args) {
        args = args[0];

        if (!args.data) return;

        var newTotal = 0,
            argData = args.data,
            jsData = this.data.js;
        if (jsData.social && argData.social) {
            newTotal += Math.min(argData.social.data.totalResults, 20) || 0;
        }
        if (!this._page || this._page < 2) {
            jsData.combinedResults += newTotal;
            jsData.lastResult += newTotal;
            jsData.totalResults += newTotal;
            jsData.firstResult = ((jsData.combinedResults) ? 1 : 0);
            if(jsData.totalResults === 0 || this.data.js.combinedResults > 0) {
                this._updateSearchResults({userSearchedOn: true});
            }
        }
    },

    /**
     * Listens to search filter change and appropriately resets widget members.
     * @private
     * @param {String} evt Event name
     * @param {Object} args Event object
     */
    _watchSearchFilterChange: function(evt, args) {
        args = args[0];

        if (!args) return;

        var filters = args.allFilters;
        if(filters && ((filters.keyword && filters.keyword.filters.data !== this._searchTerm) || (filters.page === 1))) {
            this._page = 1;
            this._searchTerm = filters.keyword.filters.data;
            this.data.js.totalResults = 0;
            this.data.js.combinedResults = 0;
            this.data.js.lastResult = 0;
            this.data.js.firstResult = 0;
            this.data.js.prunedAnswers = false;
        }
    }
});
