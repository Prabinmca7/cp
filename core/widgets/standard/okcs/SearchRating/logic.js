RightNow.Widgets.SearchRating = RightNow.SearchConsumer.extend({
    overrides: {
        constructor: function() {
            this.parent();
            this._rating = '';
            this._priorTransactionID = this.data.js.priorTransactionID;
            this._okcsSearchSession = this.data.js.okcsSearchSession;

            this._searchRatingHeader = this.Y.one("#rn_SearchRatingHeader");
            if(this.data.attrs.toggle_title) {
                this.toggleEvent = true;
                this._addToggle();
                if (this.data.attrs.toggle_state === 'collapsed' && this._toggle !== null) {
                    this._toggle.addClass(this.data.attrs.collapsed_css_class);
                    this._onToggle(this);
                }
            }

            this.Y.one(this.baseSelector + '_SubmitButton').on('click', this._onRatingSubmit, this);
            this.Y.one(this.baseSelector).delegate('click', this._onRatingClick, 'button', this);
            this.Y.all(this.baseSelector + ' .rn_Rating').on({mouseenter : this._onMouseOver, mouseleave: this._onMouseOut}, this, this);
            if(this.data.js.filter)
                this.searchSource().setOptions(this.data.js.filter).on('response', this._onReportChanged, this);
        }
    },

    /**
    * Event handler executed when the rating is submitted
    * @param {Object} e Event
    */
    _onRatingSubmit: function(e) {
        e.halt();
        this._toggleLoadingIndicators();
        this.Y.one(this.baseSelector + '_SubmitButton').set("disabled", "true");
        var eventObject = new RightNow.Event.EventObject(this, {data: {
            rating: this._rating,
            feedback: this.Y.one(this.baseSelector + '_FeedbackMessage').get('value'),
            priorTransactionID: this._priorTransactionID,
            okcsSearchSession: this._okcsSearchSession
        }});
        RightNow.Ajax.makeRequest(this.data.attrs.get_okcs_data_ajax, eventObject.data, {
            successHandler: function(response, args){ 
                this.Y.one(this.baseSelector + '_SearchComment').addClass('rn_Hidden');
                this.Y.all(this.baseSelector).detach('click');
                this.Y.all(this.baseSelector + ' .rn_Rating').detach('mouseenter', this._onMouseOver, this);
                this.Y.all(this.baseSelector + ' .rn_Rating').detach('mouseleave', this._onMouseOut, this);
                this.Y.one(this.baseSelector + '_ThanksMessage').removeClass('rn_Hidden');
                this._updateAriaAlert(this.Y.one(this.baseSelector + '_ThanksMessage').get('innerHTML'));
                this._toggleLoadingIndicators(false);
            },
            json: true, scope: this
        });
    },

    /**
    * Event handler executed when the rating star is clicked
    * @param {Object} evt Event
    */
    _onRatingClick: function(evt) {
        this._rating = evt.target.getAttribute('data-rating');
        var ratings = this.Y.all(this.baseSelector + ' .rn_Rating');
        for(var i = 0; i < this._rating; i++)
            ratings.item(i).addClass('rn_Selected');
        this.Y.one(this.baseSelector + '_SearchComment').removeClass('rn_Hidden');
        this.Y.one(this.baseSelector + '_FeedbackMessage').focus();
        this.Y.all(this.baseSelector + ' .rn_Rating').detach('mouseleave', this._onMouseOut, this);
    },

    /**
    * Event handler executed when mouse hovered on rating star
    * @param {Object} evt Event
    */
    _onMouseOver: function(evt) {
        var ratingIndex = evt.target.getAttribute('data-rating');
        var ratings = this.Y.all(this.baseSelector + ' .rn_Rating');
        for(var i = 0; i < ratingIndex; i++)
            ratings.item(i).addClass('rn_Selected');
        this.Y.all(this.baseSelector + ' .rn_Rating').on('mouseleave', this._onMouseOut, this);
    },

    /**
    * Event handler executed when mouse pointer is moved out of the rating star
    * @param {Object} evt Event
    */
    _onMouseOut: function(evt) {
        this.Y.all(this.baseSelector + ' .rn_Rating').removeClass('rn_Selected');
        this.Y.one(this.baseSelector + '_SearchComment').addClass('rn_Hidden');
    },

    /**
     * Hides / shows the loading icon and status message.
     * @param {Boolean=} turnOn Whether to turn on the loading indicators (T),
     * remove the loading indicators (F), or toggle their current state (default) (optional)
     */
    _toggleLoadingIndicators: function(turnOn) {
        var classFunc = ((typeof turnOn === "undefined") ? "toggleClass" : ((turnOn === true) ? "removeClass" : "addClass")),
            message = this.Y.one(this.baseSelector + "_StatusMessage");
        if (message) {
            message[classFunc]("rn_Hidden").setAttribute("aria-live", (message.hasClass("rn_Hidden")) ? "off" : "assertive");
        }
        this.Y.one(this.baseSelector + "_StatusMessage").addClass('rn_OkcsSubmit');
    }, 

    /**
    * Event handler executed when the search result is changed
    * @param {String} type Event type
    * @param {Object} args Arguments passed with event
    */
    _onReportChanged: function(type, args) {
        var searchResult = args[0],
            ratingContainer = this.Y.one(this.baseSelector + '_Content');
        this._searchRatingHeader = this.Y.one("#rn_SearchRatingHeader");

        if (searchResult === undefined || searchResult.data.error === undefined) {
            if(searchResult.data.searchState && searchResult.data.searchResults) {
                this._priorTransactionID = searchResult.data.searchState.priorTransactionID;
                this._okcsSearchSession = searchResult.data.searchState.session;
                if(searchResult.data.searchResults.results !== null && searchResult.data.searchResults.results.results.length <= 0) {
                    ratingContainer.addClass('rn_Hidden');
                    if (this._searchRatingHeader !== null)
                        this._searchRatingHeader.addClass('rn_Hidden');
                }
                else {
                    ratingContainer.removeClass('rn_Hidden');
                    if(this.data.attrs.toggle_title) {
                        if (this._searchRatingHeader !== null) {
                            this._searchRatingHeader.removeClass('rn_Hidden');
                        }
                        else{
                            var headerNode = this.Y.Node.create("<h2 id=\"rn_SearchRatingHeader\">" + this.data.attrs.label_search_rating + "</h2>");
                            this.Y.one(this.baseSelector + '_Alert').insertBefore(headerNode, this.Y.one(this.baseSelector + '_Alert'));
                            this._searchRatingHeader = this.Y.one("#rn_SearchRatingHeader");
                            if(this.data.attrs.toggle_title) {
                                this.toggleEvent = true;
                                this._addToggle();
                                if (this.data.attrs.toggle_state === 'collapsed') {
                                    if (this._toggle !== null) {
                                        this._toggle.addClass(this.data.attrs.collapsed_css_class);
                                        this._onToggle(this);
                                    }
                                }
                            }
                        }
                    }
                    
                    this.Y.one(this.baseSelector + '_SubmitButton').removeAttribute('disabled');
                    this.Y.one(this.baseSelector + '_FeedbackMessage').set('value', '');
                    this.Y.all(this.baseSelector + ' .rn_Rating').removeClass('rn_Selected');
                    this.Y.one(this.baseSelector + '_ThanksMessage').addClass('rn_Hidden');
                    this.Y.one(this.baseSelector).delegate('click', this._onRatingClick, 'button', this);
                    this.Y.all(this.baseSelector + ' .rn_Rating').on({mouseenter : this._onMouseOver, mouseleave: this._onMouseOut}, this, this);
                }
            }
            else if(!ratingContainer.hasClass('rn_Hidden')) {
                this.Y.one(ratingContainer.addClass('rn_Hidden'));
            }
        }
        RightNow.Event.fire("evt_pageLoaded");
    },

    /**
    * Toggles the display of the element.
    */
    _addToggle: function() {
        this._toggle = this._searchRatingHeader;
        if (this._toggle !== null) {
            this._toggle.appendChild(this.Y.Node.create("<span class='rn_Expand'></span>"));
            var current1 = this._toggle.next();
            var current = current1.next();
            if(current)
                this._itemToToggle = current;
            else
                return;
            this._currentlyShowing = this._toggle.hasClass(this.data.attrs.expanded_css_class) ||
                this._itemToToggle.getComputedStyle("display") !== "none";

            //trick to get voiceover to announce state to screen readers.
            this._screenReaderMessageCarrier = this._toggle.appendChild(this.Y.Node.create(
                "<img style='opacity: 0;' src='/euf/core/static/whitePixel.png' alt='" +
                    (this._currentlyShowing ? this.data.attrs.label_expanded_screenreader : this.data.attrs.label_collapsed_screenreader) + "'/>"));

            if(this.toggleEvent) {
                this._searchRatingHeader.delegate('click', this._onToggle, '#rn_SearchRatingHeader', this);
                this.toggleEvent = false;
            }
        }
    },

    /**
    * Toggles the display of the element.
    * @param clickEvent Event Click event
    */
    _onToggle: function(clickEvent) {
        var target = clickEvent.target, cssClassToAdd, cssClassToRemove;
        if(this._currentlyShowing) {
            cssClassToAdd = this.data.attrs.collapsed_css_class;
            cssClassToRemove = this.data.attrs.expanded_css_class;
            this._itemToToggle.setStyle("display", "none");
            this._screenReaderMessageCarrier.set("alt", this.data.attrs.label_collapsed_screenreader);
        }
        else {
            cssClassToAdd = this.data.attrs.expanded_css_class;
            cssClassToRemove = this.data.attrs.collapsed_css_class;
            this._itemToToggle.setStyle("display", "block");
            this._screenReaderMessageCarrier.set("alt", this.data.attrs.label_expanded_screenreader);
        }
        if(target) {
            target.addClass(cssClassToAdd)
                .removeClass(cssClassToRemove);
        }
        this._currentlyShowing = !this._currentlyShowing;
    },

    /**
    * Event handler executed when search rating header is clicked for mobile view
    * @param {Object} clickEvent Event
    */
    _onHeaderClick: function(clickEvent) {
        if(this.data.attrs.toggle_title)
            this._onToggle(clickEvent);
    },

    /**
     * Updates the text for the ARIA alert div that appears above search rating
     * @param {String} text The text to update the div with
     */
    _updateAriaAlert: function(text) {
        this._ariaAlert = this._ariaAlert || this.Y.one(this.baseSelector + '_Alert');
        if(this._ariaAlert) {
            this._ariaAlert.set('innerHTML', text);
        }
    }
});
