RightNow.Widgets.OkcsTranslatedAnswerSelector = RightNow.Widgets.extend({
    constructor: function() {
        this._toggleElement = this.Y.one(this.baseSelector + "_DropdownButton");
        this._displayFieldVisibleText = this.Y.one(this.baseSelector + "_DisplayLanguage");
        this._dropdownElement = this.Y.one(this.baseSelector + "_SubNavigation");
        this._dropdownElementHidden = this.Y.one(this.baseSelector + "_SubNavigationHidden");
        this._dropdownOpen = false;
        this.answerId = this.data.js.answerId;
        this.hasExpired = false;

        this._fetchTranslations();
        if(!this._toggleElement || !this._dropdownElement) return;

        this._toggleElement.on('click', this._toggleDropdown, this);
        this._dropdownElement.on('click', this._updateDisplayLanguage, this);
    },
    
    /**
    * This function checks for expiration and returns appropriate flag
    * @return {boolean} Flag indicating expiry 
    */
    _checkExpiration: function(){
        if(sessionStorage.localeDescriptionObject) {
            if(new Date().getTime() > JSON.parse(sessionStorage.localeDescriptionObject).expiryTime)
                this.hasExpired = true;
        }
        return this.hasExpired;
    },

    /**
    * This function fetches all the translations for the specified answer id.
    */
    _fetchTranslations: function(){
        var eventObject = new RightNow.Event.EventObject(this, {data: { answerId: this.answerId }});
        //Fetch all locale descriptions associated with the repository, to be stored in sessionStorage with expiry of thirty minutes
        if(this._checkExpiration() || !sessionStorage.localeDescriptionObject) {
            RightNow.Ajax.makeRequest(this.data.attrs.fetch_all_locale_desc, eventObject.data, {
                successHandler: function(response){
                var object = {localeDescriptionList: JSON.stringify(response), expiryTime: (new Date().getTime() + 30 * 60 * 1000)};
                sessionStorage.localeDescriptionObject = JSON.stringify(object);
                this.localeDescriptionList = JSON.parse(JSON.parse(sessionStorage.localeDescriptionObject).localeDescriptionList);
                if(this.localeDescriptionList && this.translationList)
                    this._processResponse();
                },
                json: true,
                scope: this
            });
        }
        else
            this.localeDescriptionList = JSON.parse(JSON.parse(sessionStorage.localeDescriptionObject).localeDescriptionList);
        // Fetch translation details
        RightNow.Ajax.makeRequest(this.data.attrs.fetch_translations, eventObject.data, {
            successHandler: function(response){
                this.translationList = response;
                if(this.translationList && this.localeDescriptionList)
                this._processResponse();
            },
            json: true,
            scope: this
        });
    },

    /**
    * This function processes the response to display appropriate locale descriptions in the dropdown
    */
    _processResponse: function() {
        for(i = 0; i < this.translationList.length; ++i) {
            if(this.translationList[i].answerId == this.answerId) {
                var displayLanguage = this.localeDescriptionList[this.translationList[i].localeRecordId];
                displayLanguage = displayLanguage ? displayLanguage : this.translationList[i].localeRecordId;
                this._displayFieldVisibleText.setHTML(displayLanguage);
                break;
            }
        }
        this._toggleElement.removeClass('rn_Disabled');
        if(this.translationList && this.translationList.length > 0) {
            this._constructLanguageDropdown(this.translationList);
        }
        this._keyEventTriggers = new this.Y.NodeList(this._toggleElement).concat(this._dropdownElement.all('li').setAttribute('tabindex', -1));
        this._keyEventTriggers.on('keydown', this._onKeydown, this);
    },
    
    /**
    * Creates and appends the language dropdown to the DOM
    * @param  {object} response Response object to be used to construct the language dropdown
    */
    _constructLanguageDropdown: function(response)
    {
        for(i = 0; i < response.length; ++i) {
            var answerId = response[i].answerId;
            var dropDownLanguage = this.localeDescriptionList[response[i].localeRecordId];
            dropDownLanguage = dropDownLanguage ? dropDownLanguage : response[i].localeRecordId;

            if(this.answerId == answerId) {
                var item = this.Y.Node.create('<li class="rn_NavigationItem rn_DefaultLang rn_SelectedLink">' + dropDownLanguage + '</li>');
            }
            else
                var item = this.Y.Node.create('<li data-answer-id = "' + answerId + '" class="rn_NavigationItem">' + dropDownLanguage + '</li>');
            this._dropdownElement.insert(item);
        }
        this._listElement = this.Y.all('.rn_NavigationItem');
        this._listElement.on('mouseover', this._onMouseOver, this);
        this._dropdownElementHidden.setHTML(this._dropdownElement.getHTML());
    },

    /**
     * Event handler to remove all the list items and disable dropdown
     * after selection of a specific item
     * @param {object} e Event name
     */
    _updateDisplayLanguage: function(e) {
        var answerId = e.target.getAttribute('data-answer-id');
        if(answerId == "") {
            return;
        }
        this._displayFieldVisibleText.setHTML(this.data.attrs.label_loading);
        this._dropdownElement.setHTML('');
        this._dropdownElementHidden.setHTML('');
        this._toggleElement.addClass('rn_Disabled');
        window.location.href = '/app/' + RightNow.Interface.getConfig('CP_ANSWERS_DETAIL_URL') + '/a_id/' + answerId;
        return;
    },

    /**
     * Event handler to apply selected style for list elements
     * @param {object} e Event name
     */
    _onMouseOver: function(e) {
        this.Y.all('.rn_SelectedLink').removeClass('rn_SelectedLink');
        e.currentTarget.addClass('rn_SelectedLink');
    },

    /**
     * Handles keydown events for menuitems.
     * Tab: closes the menu
     * Esc: closes the menu and focuses on the trigger
     * ↑:   focuses on the previous element in the menu
     * ↓:   focuses on the next element in the menu
     * @param {object} e Keydown event
     */
    _onKeydown: function(e) {
        if (!this._closeDropdownKeypress(e)) {
            this._dropdownNavKeypress(e);
        }
    },

    /**
     * Closes the dropdown on TAB or ESC.
     * @param {object} e Keydown event
     * @return {boolean} True if the dropdown was closed; False if not
     */
    _closeDropdownKeypress: function (e) {
        if (this.Y.Array.indexOf([RightNow.UI.KeyMap.ESCAPE, RightNow.UI.KeyMap.TAB], e.keyCode) === -1) return false;

        // Close the dropdown on TAB or ESC.
        this._closeDropdown();

        if (e.keyCode === RightNow.UI.KeyMap.ESCAPE) {
            this._toggleElement.focus();
        }

        return true;
    },

    /**
     * Handles ↑ and ↓ key events.
     * @param {object} e Keydown event
     */
    _dropdownNavKeypress: function (e) {
        if (this.Y.Array.indexOf([RightNow.UI.KeyMap.UP, RightNow.UI.KeyMap.DOWN], e.keyCode) > -1) {
            e.halt();
            this._focusAdjacentElement(this._keyEventTriggers, e.keyCode === RightNow.UI.KeyMap.UP ? -1 : 1);
        }

        if(RightNow.UI.KeyMap.ENTER === e.keyCode) {
            if(this._dropdownOpen) {
                if(this.Y.one('.rn_SelectedLink').getAttribute('data-answer-id') === "") {
                    this._toggleDropdown(e);
                }
                this.Y.one('.rn_SelectedLink').simulate("click");
            }
        }
    },

    /**
     * Focuses on the sibling of the currently-focused node
     * in the given nodelist specified by the given index.
     * @param {object} nodeList Y.NodeList
     * @param {number} adjacentIndex Either 1 (next sibling) or -1 (previous sibling)
     */
    _focusAdjacentElement: function (nodeList, adjacentIndex) {
        var selectedNode = this.Y.one('.rn_SelectedLink');

        if(adjacentIndex === -1 && selectedNode.previous()) {// ↑ scenario
            selectedNode.removeClass('rn_SelectedLink');
            selectedNode.previous().addClass('rn_SelectedLink');
        }
        else if(adjacentIndex === 1 && selectedNode.next()) {// ↓ scenario
            selectedNode.removeClass('rn_SelectedLink');
            selectedNode.next().addClass('rn_SelectedLink');
        }
    },

    /**
     * Stops the default event and toggles between
     * displaying and hiding the dropdown list of links.
     * @param {Object} evt Click or Focus event.
     */
    _toggleDropdown: function(evt) {
        this.Y.all('.rn_SelectedLink').removeClass('rn_SelectedLink');
        this.Y.all('.rn_DefaultLang').addClass('rn_SelectedLink');
        evt.halt();

        (this._closeDropdown() || this._openDropdown());
    },

    /**
     * Displays the dropdown list of sublinks and subscribes
     * to appropriate events that dictate the proper closing of
     * the dropdown.
     */
    _openDropdown: function() {
        if(!this._dropdownOpen) {
            RightNow.UI.show(this._dropdownElement);
            this._toggleElement.setAttribute('aria-expanded', 'true');

            // JAWS support - focus on parent tag of menu list first and then focus back on dropdown button.
            this.Y.one(this.baseSelector + "_SubNavigationParent").focus();
            this._toggleElement.focus();

            this.Y.one(document.body).on('click', this._closeDropdown, this);
            this._dropdownOpen = true;
            return true;
        }

        return false;
    },

    /**
     * Hides the dropdown list of sublinks and optionally
     * purges the element that triggered the event.
     */
    _closeDropdown: function() {
        if(this._dropdownOpen) {
            this.Y.one(document.body).detach("click", this._closeDropdown, this);
            RightNow.UI.hide(this._dropdownElement);
            this._toggleElement.setAttribute('aria-expanded', 'false');
            this._dropdownOpen = false;
            return true;
        }

        return false;
    }
});
