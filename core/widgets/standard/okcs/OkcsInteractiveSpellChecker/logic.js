RightNow.Widgets.OkcsInteractiveSpellChecker = RightNow.SearchProducer.extend({
    overrides: {
        constructor: function() {
            this.parent();
            this._spellCheckerContainer = this.Y.one('.rn_OkcsSpellCheckerContainer');
            this._suggestedQuestionNode = this.Y.one('.rn_OkcsSpellCheckerLink');

            this.Y.one(this.baseSelector).delegate('click', this._onSuggestedQuestionLink, 'a.rn_OkcsSpellCheckerLink', this);
            this.searchSource().setOptions(this.data.js.sources).on('response', this._createSuggestedQuestionNode, this);
        }
    },

    /**
     * Event Handler fired when the suggested question is clicked
     * @param {Object} evt Event object
     */
    _onSuggestedQuestionLink: function(evt) {
        evt.preventDefault();
        this._newSearch = false;
        this._facet = '';
        this._searchType = 'clearFacet';
        this.Y.one('.' + this.data.attrs.parent_selector + ' input').set('value', this.data.js.fieldParaphrase);
        this.searchSource().setOptions(this.data.js.sources).fire('collect');
        if(this.searchSource().multiple) {
            this.filters = this.searchSource().sources[0].filters;
        }
        else {
            this.filters = this.searchSource().filters;
        }
        if(this.filters.query && this.Y.Lang.trim(this.filters.query.value) !== '') {
            if (this.filters.query.key === "kw" && this.Y.Lang.trim(this.filters.query.value) === "*") {
                this.filters.direction = this.filters.direction || {};
                this.filters.direction.value = 0;
                this.filters.sort = this.filters.sort || {};
                this.filters.sort.value = 1;
            }

            this.searchSource().fire('search', new RightNow.Event.EventObject(this, {
               page: this.data.js.filter,
               sourceCount: 1
           }));
        }
        else {
            RightNow.UI.displayBanner(this.data.attrs.label_enter_search_keyword, {
                type: 'WARNING',
                focusElement: this.searchField
            });
        }
    },

    /**
     * This method is called when response event is fired..
     * @param string Event name
     * @param object Event arguments
     */
    _createSuggestedQuestionNode: function(type, args) {
        if (args[0].data.searchResults && args[0].data.searchResults.query) {
            this._spellCheckerContainer.addClass('rn_Hidden');
            var spellChecked = args[0].data.searchResults.query.spellchecked;
            if (spellChecked) {
                this._suggestedQuestionNode.setHTML(this._constructBestQuestion(spellChecked));
            }
        }
    },

    /** 
     * This method constructs the suggested string from the 
     * search response obtained
     * @param object spellChecked object from search response
     * @return string constructed string
     */
    _constructBestQuestion: function(spellChecked) {
        this._spellCheckerContainer.removeClass('rn_Hidden');
        var paraphrase = fieldParaphrase = '', //fieldParaphrase will contain the suggested string without inline styles
            correctionArray = spellChecked.corrections;

        this.Y.Array.each(correctionArray, function(correctionItem) {
            //For correctly spelt words
            if (correctionItem.correction) {
                paraphrase += (correctionItem.correction).trim() + ' ';
                fieldParaphrase += (correctionItem.correction).trim() + ' ';
            }
            // For misspelt words
            else {
                var confidenceLevel = 0,
                    suggestionArray = correctionItem.suggestions,
                    linkValue = '';
                this.Y.Array.each(suggestionArray, function(suggestionItem) {
                    if (suggestionItem.confidence > confidenceLevel) {
                        confidenceLevel = suggestionItem.confidence;
                        bestValue = (suggestionItem.value).trim();
                    }
                });
                fieldParaphrase += bestValue + ' ';

                linkValue = '<span class=\'rn_CorrectedWord\'>' + bestValue + '</span>';
                paraphrase += linkValue + ' ';
            }
        }, this);
        this.data.js.fieldParaphrase = fieldParaphrase;
        return paraphrase;
    }
});