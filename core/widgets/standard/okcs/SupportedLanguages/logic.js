RightNow.Widgets.SupportedLanguages = RightNow.SearchProducer.extend({
    overrides: {
        constructor: function() {
            this.parent();
            this._link = this.Y.one(this.baseSelector + "_Link");
            this._container = this.Y.one(this.baseSelector + "_Container");
            this._link.on("click", this._onToggleLanguageContainer, this);
            this.isLanguageSelected = true;
            this.searchSource()
                .on('collect', this.onCollect, this)
                .on('search', this.onSearch, this)
                .on('response', this._hideLanguageSelection, this);
        }
    },

    onCollect: function () {
        var selections = [];
        this.isLanguageSelected = true;
        // When no check-boxes are selected, throw a warning message and do not fire search
        if(this.Y.all('.rn_SupportedLanguagesCheckbox:checked').getDOMNodes().length === 0) {
            this.Y.all('.rn_SupportedLanguagesCheckbox:unchecked').each(function(checkbox) {
                selections.push(checkbox.get('value'));
            });
            this.isLanguageSelected = false;
        }
        this.Y.all('.rn_SupportedLanguagesCheckbox:checked').each(function(checkbox) {
            selections.push(checkbox.get('value'));
        });

        return new RightNow.Event.EventObject(this, {
            data: {value: selections.toString(), key: 'loc', type: 'loc'}
        });
    },

    /**
    * Called when a new search is triggered by a widget with the same source_id attribute.
    * Displays an error banner if no langauges are selected and retains the checkboxes in the
    * visible state for further selection.
    * @return {boolean} false if no langauges are selected.
    */
    onSearch: function() {
        if(!this.isLanguageSelected) {
            RightNow.UI.displayBanner(this.data.attrs.no_languages_selected_msg, { type: 'ERROR' }, {focus: true});
            this.searchSource().fire('response', new RightNow.Event.EventObject(null, {data: {}}));
            this._container.removeClass('rn_Hidden');
            return false;
        }
    },

    /**
    * function to show/hide the languages
    *
    */
    _onToggleLanguageContainer: function()
    {
        this._container.toggleClass('rn_Hidden');
    },

    /**
    * function to hide the languages on search response 
    *
    */
    _hideLanguageSelection: function()
    {
        this._container.addClass('rn_Hidden');
    }
});
