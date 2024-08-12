RightNow.Widgets.FavoritesButton = RightNow.Widgets.extend({
    constructor: function() {
        this._favoritesButton = this.Y.one(this.baseSelector + '_FavoritesButton');
        if(this._favoritesButton && this.data.js.enabled) {
            this._favoritesButton.on('click', this._onFavoritesClick, this);
        }
    },

    /**
    * Event handler executed when the favorite button is clicked
    * @param {Object} e Event
    */
    _onFavoritesClick: function(e) {
        e.halt();
        var eventObject;
        if(this.data.js.favoriteID && this.data.js.favoriteID !== null){
            eventObject = new RightNow.Event.EventObject(this, {data: {
               answerID: this.data.js.answerID,
               action: 'RemoveFavorite'
            }});
            RightNow.Ajax.makeRequest(this.data.attrs.get_okcs_data_ajax, eventObject.data, {
                successHandler: this._displayRemoveFavoriteMessage,
                failureHandler: this._displayErrorOnFailure,
                json: true, scope: this
            });
        }
        else {
            eventObject = new RightNow.Event.EventObject(this, {data: {
               answerID: this.data.js.answerID,
               action: 'AddFavorite'
            }});
            RightNow.Ajax.makeRequest(this.data.attrs.get_okcs_data_ajax, eventObject.data, {
                successHandler: this._displayAddFavoriteMessage,
                failureHandler: this._displayErrorOnFailure,
                json: true, scope: this
            });
        }
    },

    /**
    * Displays Favorite message from the ajax response success.
    * @param response Object response.
    */
    _displayAddFavoriteMessage: function(response) {
        if(response.result && response.result[0].errorCode === 'OKDOM-USRFAV01'){
            this.data.js.favoriteID = response.result[0].extraDetails;//TODO check with vikas
            RightNow.UI.displayBanner(response.result[0].externalMessage, { type: 'ERROR', focusElement: this._favoritesButton });
            this._favoritesButton.set("innerHTML", this.data.attrs.label_remove_favorite_button, { focusElement: this._favoritesButton });
        }
        else if(response.result === 'OK-GEN0004'){
            RightNow.UI.displayBanner(this.data.attrs.label_max_length_error_msg, { type: 'ERROR', focusElement: this._favoritesButton });
        }
        else if(response.failure){
            RightNow.UI.displayBanner(RightNow.Interface.ASTRgetMessage(response.failure), { type: 'ERROR', focusElement: this._favoritesButton });
        }
        else{
            if(response.key === 'favorite_document' && response.value.indexOf(this.data.js.answerID) !== -1)
                this.data.js.favoriteID = response.recordId;
            this._updateAriaAlert(this.data.attrs.label_add_favorite_msg);
            RightNow.UI.displayBanner(this.data.attrs.label_add_favorite_msg, { focusElement: this._favoritesButton });
            this._favoritesButton.set("innerHTML", this.data.attrs.label_remove_favorite_button);
        }
    },

    /**
    * Displays Remove favorite message from the ajax response success.
    * @param response Object response.
    */
    _displayRemoveFavoriteMessage: function(response) {
        if(response.result && response.result[0].errorCode === 'OKDOM-USRFAV02'){
            this.data.js.favoriteID = null;
            RightNow.UI.displayBanner(response.result[0].externalMessage, { type: 'ERROR', focusElement: this._favoritesButton });
            this._favoritesButton.set("innerHTML", this.data.attrs.label_add_favorite_button, { focusElement: this._favoritesButton });
        }
        else if(response.failure){
            RightNow.UI.displayBanner(RightNow.Interface.ASTRgetMessage(response.failure), { type: 'ERROR', focusElement: this._favoritesButton });
        }
        else{
            if(response.key === 'favorite_document' && response.value.indexOf(this.data.js.answerID) === -1) {
                this.data.js.favoriteID = null;
                this._updateAriaAlert(this.data.attrs.label_remove_favorite_msg);
                RightNow.UI.displayBanner(this.data.attrs.label_remove_favorite_msg);
                this._favoritesButton.set("innerHTML", this.data.attrs.label_add_favorite_button, { focusElement: this._favoritesButton });
            }
        }
    },

    /**
    * Displays error message from the ajax response failure.
    * @param response Object response.
    */
    _displayErrorOnFailure: function(response) {
        var message = response.suggestedErrorMessage || RightNow.Interface.getMessage('THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG');
        if(response.status === 403 && response.responseText !== undefined){
            message = response.responseText;
        }
        RightNow.UI.Dialog.messageDialog(message, {"icon": "WARN", exitCallback: function(){window.location.reload(true);}});
    },

    /**
     * Updates the text for the ARIA alert div
     * @param {String} text The text to update the div with
     */
    _updateAriaAlert: function(text) {
        this._ariaAlert = this._ariaAlert || this.Y.one(this.baseSelector + '_Alert');
        if(this._ariaAlert) {
            this._ariaAlert.set('innerHTML', text);
        }
    }
});
