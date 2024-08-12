RightNow.Widgets.OkcsFavoritesList = RightNow.Widgets.extend({
    constructor: function() {
        this.messageBox = this.Y.one(this.data.attrs.message_element ? this.Y.one('#' + this.data.attrs.message_element) : null);
        this.favoriteList = this.Y.one(this.baseSelector + '_List');
        this.Y.all(this.baseSelector + ' button').on("click", this._removeFavorite, this);
        this.favoriteListOffset = 0;
        this.hasMoreFavorites = false;
        if(this.data.js.userFavoritesList && this.data.js.userFavoritesList.length > 20 && this.data.attrs.view_type === 'list') {
            this.hasMoreFavorites = true;
            this.Y.one(window).on('scroll', this.Y.bind(this._handleScroll, this));
        }

        if(this.data.attrs.view_type === 'table' && this.data.attrs.enable_pagination_for_table) {
            this.Y.one(this.baseSelector + ' .rn_NextPage').on("click", this.paginate, this);
            this.data.attrs.rows_to_display = parseInt(this.data.attrs.rows_to_display, 10);
            this.tableCount = this.data.attrs.rows_to_display;
            this.totalFavCount = this.data.js.userFavoritesList.length;
            this.favDetailList = this.data.js.favoritesList;
        }
    },
    
    /**
     * Remove answer from the subscription list
     * @param {Object} evt Event
     */
    _removeFavorite: function(evt) {
        var favoriteID = evt.target.getAttribute('id'),
            eventObject = new RightNow.Event.EventObject(this, {data: {
            answerID: favoriteID
        }});
        RightNow.Ajax.makeRequest(this.data.attrs.delete_favorite_ajax, eventObject.data, {
            successHandler: function(response, args){
                if(response.result && response.result[0].errorCode === 'OKDOM-USRFAV02') {
                    RightNow.UI.displayBanner(response.result[0].externalMessage, { type: 'ERROR', focus: true });
                    return;
                }
                else if(response.failure) {
                    RightNow.UI.displayBanner(RightNow.Interface.ASTRgetMessage(response.failure), { type: 'ERROR', focus: true });
                }
                else {
                    this.displayMessage(this.data.attrs.label_favorite_deleted);
                    var item = this.Y.one(this.baseSelector + '_' + favoriteID), scope = this;
                    if(item) {
                        item.transition({
                            opacity: 0,
                            duration: 0.4
                        }, function() {
                            this.remove();
                            if(!scope.favoriteList.all('.rn_Favorite').size()) {
                                if(scope.data.attrs.hide_when_no_favorites)
                                    scope.favoriteList.addClass('rn_Hidden');
                                else 
                                    scope.favoriteList.append(scope.data.attrs.label_no_favorites_list);
                            }
                        });
                    }
                }
            },
            failureHandler: this._displayErrorOnFailure,
            json: true, data: {favoriteID : favoriteID}, scope: this
        });
    },

    /**
    * Displays success message in message box above widget or as user specified div.
    * @param message String Message to display.
    */
    displayMessage: function(message) {
        if(this.messageBox) {
            this.messageBox.setStyle("opacity", 0).addClass("rn_MessageBox");
            this.messageBox.transition({
                opacity: 1,
                duration: 0.4
            });
            this.messageBox.set('innerHTML', message);
            RightNow.UI.updateVirtualBuffer();
            this.messageBox.set('tabIndex', 0).focus();
        }
        else {
            RightNow.UI.displayBanner(message, { baseClass: this.baseSelector });
        }
    },

    /**
     * Get the paginated results when scroll reaches end of window
     * @param {Object} evt Event
     */
    _handleScroll: function(evt){
        if (evt !== undefined && evt.target !== undefined && evt.target._node !== undefined && evt.target._node.documentElement !== undefined && evt._currentTarget !== undefined) {
            var elem = evt.target._node.documentElement;
            if ((evt._currentTarget.pageYOffset > (elem.scrollHeight - elem.offsetHeight - 10) ) && this.hasMoreFavorites && this.data.attrs.view_type === 'list') {
                if(!this._ajaxCallInProgress) {
                    this._ajaxCallInProgress = true;
                    RightNow.Event.fire("evt_pageLoading");
                    this.favoriteListOffset += 20;
                    var eventObject = new RightNow.Event.EventObject(this, {data: { getMoreFavAnswers: 'getMoreFavAnswers', titleLength: this.data.attrs.max_wordbreak_trunc, offset: this.favoriteListOffset, userFavoritesList: this.data.js.userFavoritesList.join()}});
                    RightNow.Ajax.makeRequest(this.data.attrs.get_favorite_answers_ajax, eventObject.data, {
                        successHandler: function(response,args){
                            RightNow.Event.fire("evt_pageLoaded");
                            var favIdArray = response.favIds.split(","),
                                rearrangedResponse = [],
                                favDetailArr = [];
                            //rearrange response object based on sequence of favIds; JSON.parse is unintentionally sorting based on answerIds in the response
                            for(i = 0; i < favIdArray.length; ++i) {
                                rearrangedResponse.push(response[favIdArray[i]]);
                            }
                            for(key in rearrangedResponse) {
                                var answer = rearrangedResponse[key];
                                if(answer && answer.documentId !== undefined) {
                                    var title = answer.title,
                                        item = {'title': title, 'documentId' : answer.documentId, 'answerId' : favIdArray[key]};
                                    favDetailArr.push(item);
                                }
                            }

                            if(favDetailArr && Object.entries(favDetailArr).length > 1) {
                                var endOfPage = this.Y.one('.rn_FavoriteList');
                                endOfPage.appendChild(new EJS({text: this.getStatic().templates.list}).render({favoritesList: favDetailArr,
                                    target: this.data.attrs.target,
                                    labelDeleteButton: this.data.attrs.label_delete_button,
                                    labelDocId: this.data.js.docIdLabel,
                                    widgetInstanceID: this.baseDomID,
                                    answerUrl: this.data.js.answerUrl}));
                                this.hasMoreFavorites = this.data.js.userFavoritesList.length > this.favoriteListOffset + 20 ? true : false;
                                this.Y.all(this.baseSelector + ' button').detach("click", this._removeFavorite, this);
                                this.Y.all(this.baseSelector + ' button').on("click", this._removeFavorite, this);
                                this._ajaxCallInProgress = false;
                                return;
                            }
                        },
                        failureHandler: function(response){
                            RightNow.Event.fire("evt_pageLoaded");
                            this._displayErrorOnFailure(response);
                            this._ajaxCallInProgress = false;
                        },
                        json: true,
                        scope: this
                    });
                }
            }
        }
    },

    /**
    * Handles pagination for table view
    * @param {Object} evt Event
    */
    paginate: function(evt) {
        RightNow.Event.fire("evt_pageLoading");
        var className = evt.currentTarget.getDOMNode().getAttribute('class'),
            favIdList = this.data.js.userFavoritesList;
        if(className === "rn_NextPage") {
            newCount = this.tableCount + this.data.attrs.rows_to_display;
            if(newCount >= favIdList.length) {
                var lastFinalCount = newCount;
                this.isLastPage = true;
                newCount = favIdList.length;
                this.showNext = false;
                this.showPrevious = true;
            }
            else {
                this.showNext = true;
                this.showPrevious = true;
            }
        }
        else if(className === "rn_PreviousPage") {
            newCount = this.tableCount - this.data.attrs.rows_to_display;
            this.tableCount = newCount - this.data.attrs.rows_to_display;
            if(newCount === this.data.attrs.rows_to_display) {
                this.tableCount = 0;
                this.showNext = true;
                this.showPrevious = false;
            }
            else {
                this.showNext = true;
                this.showPrevious = true;
            }
        }
        var slicedArray = favIdList.slice(this.tableCount, newCount);
        this.tableCount = newCount;
        if(this.isLastPage === true) {
            this.tableCount = lastFinalCount;
            this.isLastPage = false;
        }
        if(this.favDetailList.length >= newCount || this.favDetailList.length === this.totalFavCount) {
            //do not make ajax call, skip to success handler
            var init = this.tableCount - this.data.attrs.rows_to_display;
            response = this.favDetailList.slice(init, newCount);
            this._displayFavoritesTable(response, slicedArray, true);
        }
        else {
            var eventObject = new RightNow.Event.EventObject(this, {data: { getMoreFavAnswers: 'getMoreFavTabAnswers', titleLength: this.data.attrs.max_wordbreak_trunc, favIds: slicedArray.toString()}});
            RightNow.Ajax.makeRequest(this.data.attrs.get_favorite_answers_ajax, eventObject.data, {
                successHandler: function(response){
                    var favIdArray = response.favIds.split(",");
                        rearrangedResponse = [];
                    //rearrange response object based on sequence of favIds; JSON.parse is unintentionally sorting based on answerIds in the response
                    for(i = 0; i < favIdArray.length; ++i) {
                        rearrangedResponse.push(response[favIdArray[i]]);
                    }
                    this._displayFavoritesTable(rearrangedResponse, favIdArray, false);
                },
                failureHandler: function() {
                    RightNow.Event.fire("evt_pageLoaded");
                    this._ajaxCallInProgress = false;
                },
                json: true,
                scope: this
            });
        }
    },

    /**
    * Displays the favoritestable
    * @param {Object} response Response Object
    * @param array favIdArray Array of favorite ids
    * @param boolean isCached Determines if favorites are cached or not
    */
    _displayFavoritesTable: function(response, favIdArray, isCached){
        RightNow.Event.fire("evt_pageLoaded");
        var favDetailArr = [];
        if(!isCached) {
            for(key in response) {
                var answer = response[key];
                if(answer && answer.documentId !== undefined) {
                    var title = answer.title,
                        item = {'title': title, 'documentId' : answer.documentId, 'answerId' : favIdArray[key]};
                    favDetailArr.push(item);
                }
            }
            // Populate the cache
            this.favDetailList = this.favDetailList.concat(favDetailArr);
        }
        else {
            favDetailArr = response;
        }
        //Invoke the EJS
        if(response && Object.entries(response).length >= 1) {
            this._favoriteDiv = this.Y.one('#' + this.baseDomID + '_Content');
            this._favoriteDiv.set('innerHTML', new EJS({text: this.getStatic().templates.table}).render({
                favoritesList: favDetailArr,
                data: this.data.js,
                instanceID: this.baseDomID,
                answerUrl: this.data.js.answerUrl,
                showNext: this.showNext,
                showPrevious: this.showPrevious,
                attrs: this.data.attrs
                }));
            if(this.Y.one(this.baseSelector + ' .rn_NextPage'))
                this.Y.one(this.baseSelector + ' .rn_NextPage').on("click", this.paginate, this);
            if(this.Y.one(this.baseSelector + ' .rn_PreviousPage'))
                this.Y.one(this.baseSelector + ' .rn_PreviousPage').on("click", this.paginate, this);
            RightNow.Event.fire("evt_pageLoaded");
            this._ajaxCallInProgress = false;
            return;
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
    }
});
