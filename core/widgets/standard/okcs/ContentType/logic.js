RightNow.Widgets.ContentType = RightNow.SearchConsumer.extend({
    overrides: {
        constructor: function() {
            this.parent();
            this._source_id = this.data.attrs.source_id;
            this._requestInProgress = false;
            var selectedContentType = this.Y.one('.rn_Selected');
            this._selectedContentTypeID = (selectedContentType !== null) ? selectedContentType.getData('id') : '';
            this._loadingDiv = this.Y.one('#' + this.data.attrs.dom_id_loading_icon);
            var firstContentType = this.Y.one(this.baseSelector + ' a');
            if (firstContentType) {
                firstContentType.focus();
                this.Y.all(this.baseSelector + ' a').on("click", this._viewContentType, this);
                this._setScreenReaderContentType();
            }
            if(this.data.js.sources) {
                this.searchSource().setOptions(this.data.js.sources);
                this.searchSource()
                    .on('collect', this._updateContentType, this)
                    .on('collect', this._updateRecommendationFlag, this)
                    .on('response', this._handleResponse, this);
            }
            if (this.data.attrs.toggle_selection) {
                this._toggle = this.Y.one('#' + this.data.attrs.toggle);
                this._itemToToggle = this.Y.one('#' + this.data.attrs.item_to_toggle);
                this._itemToToggle.addClass(this.data.attrs.toggle_state === 'collapsed' ? this.data.attrs.collapsed_css_class : this.data.attrs.expanded_css_class);

                //trick to get voiceover to announce state to screen readers.
                if (this._toggle) {
                    this._toggle.appendChild(this.Y.Node.create("<span id='rn_contentTypeAlert' class='rn_ScreenReaderOnly' role='alert' aria-live='assertive'></span>"));
                }
                
                this._toggle.on("click", this._onToggle, this);
            }
        }
    },

    /**
    * Toggles the display of the element.
    * @param clickEvent Event Click event
    */
    _onToggle: function(clickEvent) {
        var target = clickEvent.target, 
            cssClassToAdd, 
            cssClassToRemove;
        this._currentlyShowing = this._itemToToggle.hasClass(this.data.attrs.expanded_css_class) ||
            this._itemToToggle.getComputedStyle("display") !== "none";

        if(this._currentlyShowing) {
            cssClassToAdd = this.data.attrs.collapsed_css_class;
            cssClassToRemove = this.data.attrs.expanded_css_class;
            RightNow.UI.hide(this._itemToToggle);
            this._updateAriaAlert(this.data.attrs.label_collapsed);
        }
        else {
            cssClassToAdd = this.data.attrs.expanded_css_class;
            cssClassToRemove = this.data.attrs.collapsed_css_class;
            RightNow.UI.show(this._itemToToggle);
            this._updateAriaAlert(this.data.attrs.label_expanded);
        }
        var firstElement = this.Y.one(this.baseSelector + ' a');
        if(firstElement)
        {
            firstElement.focus();
        }
        this._currentlyShowing = !this._currentlyShowing;
    },

    /**
    * Event handler executed when the channel is clicked
    * @param {Object} evt Event
    */
    _viewContentType: function(evt) {
        if(!this.data.js.sources) {
            this.Y.one('.rn_Selected').removeClass('rn_Selected');
            evt.target.addClass('rn_Selected');
            return;
        }
        if (evt.target.hasClass('rn_Selected') || this._requestInProgress) return;
        this._requestInProgress = true;
        RightNow.Event.fire("evt_pageLoading");
        this._selectedContentTypeID = evt.target.getData('id');
        RightNow.ActionCapture.record('okcsa-browse', 'contentType', this._selectedContentTypeID);
        if(this._source_id === 'OKCSSearch')
            delete this.searchSource().options.new_page;
        this.searchSource().fire('reset');
        this.searchSource().fire('collect');
        this.searchSource().fire('search', new RightNow.Event.EventObject(this, {data: {sourceCount: 1}}));

        if (this.data.attrs.toggle_selection)
            this._onToggle(this);
    },
    
    /**
     * Adds the selected content type to the filter list
     */
    _updateContentType: function() {
        var eoSelectedContentTypeID = this._selectedContentTypeID;
        // set an empty value to the event object variable to fetch all answers
        var displayAllLabel = this.data.attrs.label_display_all_content.replace(/ /g, '-');
        if (this._selectedContentTypeID === displayAllLabel) {
            eoSelectedContentTypeID = '';
        }
        return new RightNow.Event.EventObject(this, {
            data: {value: eoSelectedContentTypeID, key: 'channelRecordID', type: 'channelRecordID'}
        });
    },

    /**
     * Adds the isRecommendationAllowed flag to the filter list
     */
    _updateRecommendationFlag: function() {
        var isRecommendationAllowed = false;
        for (var i = 0; this.data.js.contentTypeRecommendationArray[i]; i++) {
            if (this.data.js.contentTypeRecommendationArray[i].referenceKey === this._selectedContentTypeID) {
                isRecommendationAllowed = true;
            }
        }
        return new RightNow.Event.EventObject(this, {
            data: {value: isRecommendationAllowed, key: 'isRecommendationAllowed', type: 'isRecommendationAllowed'}
        });
    },

    /** 
    * This method is called when response event is fired..
    * @param {object} filter object
    * @param {object} event object
    */
    _handleResponse: function(obj, evt){
        var contentTypeSelectDom = this.Y.one(this.baseSelector + '_' + this._selectedContentTypeID.replace(/\./g, '\\\.')),
            previousContentTypeSelected = this.Y.one('.rn_Selected'),
            articlesDiv = this.Y.one("#rn_PageContentArticles");

        if (previousContentTypeSelected) {
            previousContentTypeSelected.removeClass('rn_Selected');
        }
        
        contentTypeSelectDom.addClass('rn_Selected');

        if(contentTypeSelectDom !== previousContentTypeSelected)
            this._setScreenReaderContentType();

        RightNow.Event.fire("evt_pageLoaded");

        if (articlesDiv)
            articlesDiv.show();
            
        this._requestInProgress = false;
    },

    /**
     * Function for setting screenreader label for selected content type
     */
    _setScreenReaderContentType: function(){
        var selectedChannel = this.Y.one('.rn_ContentType .rn_Selected');
        var selectedChannelLabel = selectedChannel.getHTML();
        (this.Y.one('.rn_ChannelScreenReaderLabel') || this.Y.Node.create('<span class="rn_ChannelScreenReaderLabel rn_ScreenReaderOnly"></span>')).appendTo(selectedChannel).setHTML(RightNow.Text.sprintf(this.data.attrs.label_content_type_selected, selectedChannelLabel));
    },

    /**
     * Updates the text for the ARIA alert div that appears above browse header
     * @param {String} text The text to update the div with
     */
    _updateAriaAlert: function(text) {
        this._ariaAlert = this.Y.one('#rn_contentTypeAlert');
        if(this._ariaAlert) {
            this._ariaAlert.set('innerHTML','<b>' + text + '</b>');
        }
     }
});
