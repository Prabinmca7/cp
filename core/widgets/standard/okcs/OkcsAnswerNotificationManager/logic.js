RightNow.Widgets.OkcsAnswerNotificationManager = RightNow.Widgets.extend({
    constructor: function() {
        this.messageBox = this.Y.one(this.data.attrs.message_element ? this.Y.one('#' + this.data.attrs.message_element) : null);
        this.notificationList = this.Y.one(this.baseSelector + '_List');
        this._contentName = this.baseSelector + '_Content';
        this._contentDiv = this.Y.one(this._contentName);
        this._loadingDiv = this.Y.one(this.baseSelector + '_Loading');
        this.Y.all(this.baseSelector + ' button').on("click", this._unsubscribeAnswer, this);
        if(this.data.attrs.view_type !== 'list'){
            this.Y.one(this.baseSelector + '_Grid').delegate('click', this._sortSubscriptionList, 'th', this);
            this.Y.one(this.baseSelector + '_Grid').delegate('keydown', this._sortListener, 'th', this);
        }
        this._currentDisplayedNotifCount = 0;
        this._hasmore = true;
        this.Y.one(window).on('scroll', this.Y.bind(this._handleScroll, this));
        RightNow.Event.subscribe("evt_pageLoading", this._showPageLoading, this);
        RightNow.Event.subscribe("evt_pageLoaded", this._hidePageLoading, this);
    },
    /**
     * Get the paginated results when scroll reaches end of window
     * @param {Object} evt Event
     */
    _handleScroll: function(evt){
        if (evt !== undefined && evt.target !== undefined && evt.target._node !== undefined && evt.target._node.documentElement !== undefined && evt._currentTarget !== undefined) {
            var elem = evt.target._node.documentElement;
            if ((evt._currentTarget.pageYOffset > (elem.scrollHeight - elem.offsetHeight - 10) ) && this._hasmore && this.data.attrs.view_type === 'list') {
                this._currentDisplayedNotifCount += 20;
                    if(!this._ajaxCallInProgress)
                    {
                        RightNow.Event.fire("evt_pageLoading");
                        var eventObject = new RightNow.Event.EventObject(this, {data: { getMoreAnswerNotif: 'getMoreAnswerNotif', offset: this._currentDisplayedNotifCount}});
                        RightNow.Ajax.makeRequest(this.data.attrs.get_okcs_data_ajax, eventObject.data, {
                            successHandler: function(response,args){
                                RightNow.Event.fire("evt_pageLoaded");
                                if(response && response.items && response.items.length) {
                                    var endOfPage = this.Y.one('.rn_NotificationList');
                                    endOfPage.appendChild(new EJS({text: this.getStatic().templates.list}).render({subscriptionList: response,
                                        fields: this.data.js.fields,
                                        target: this.data.attrs.target,
                                        labelDeleteButton: this.data.attrs.label_delete_button,
                                        widgetInstanceID: this.baseDomID,
                                        subscriptionListLength: response.items.length,
                                        answerUrl: this.data.js.answerUrl}));
                                    this._hasmore = response.hasMore;
                                    this.Y.all(this.baseSelector + ' button').detach("click", this._unsubscribeAnswer, this);
                                    this.Y.all(this.baseSelector + ' button').on("click", this._unsubscribeAnswer, this);
                                    return;
                                }
                            },
                            failureHandler: function(response){
                                RightNow.Event.fire("evt_pageLoaded");
                                this._displayErrorOnFailure(response);
                            },
                            json: true,
                            scope: this
                        });
                    }
            }
        }
    },
    /**
     * Remove answer from the subscription list
     * @param {Object} evt Event
     */
    _unsubscribeAnswer: function(evt) {
        var subscriptionID = evt.target.getAttribute('id'),
            eventObject = new RightNow.Event.EventObject(this, {data: {
            subscriptionID: subscriptionID
        }});
        RightNow.Ajax.makeRequest(this.data.attrs.delete_notification_ajax, eventObject.data, {
            successHandler: function(response, args){
                if(response.errorCode === 'OKDOM-USER0014') {
                RightNow.UI.displayBanner(this.data.js.failureMsgOnUnsub, { type: 'ERROR', focus: true });
                return;
            }
                if(response.failure) {
                    RightNow.UI.displayBanner(RightNow.Interface.ASTRgetMessage(response.failure), { type: 'ERROR', focus: true });
                }
                else {
                    this.displayMessage(this.data.attrs.label_notif_deleted);
                    var item = this.Y.one(this.baseSelector + '_' + subscriptionID), scope = this;
                    if(item) {
                        item.transition({
                            opacity: 0,
                            duration: 0.4
                        }, function() {
                            this.remove();
                            if(!scope.notificationList.all('.rn_Notification').size()) {
                                scope.notificationList.append(scope.data.attrs.label_no_notifs);
                            }
                        });
                    }
                }
            },
            failureHandler: this._displayErrorOnFailure,
            json: true, data: {subscriptionID : subscriptionID}, scope: this
        });
    },

    /**
     * Listens for Space button press to sort column when focus is present on it.
     * @param {Object} evt Event
     */
    _sortListener: function(evt) {
        if(evt.keyCode === RightNow.UI.KeyMap.SPACE) {
            if(evt.target.hasClass('yui3-datatable-sortable-column')){
                this._sortSubscriptionList(evt);
            }
        }
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
    * Sort event handler executed when the column header is clicked
    * @param {Object} evt Event
    */
    _sortSubscriptionList: function(evt) {
        var sortIconNode = evt.target.getElementsByTagName('span').item(0);
        if(!sortIconNode && evt.target.getDOMNode().nodeName === 'SPAN') {
            sortIconNode = evt.target;
        }

        if(sortIconNode) {
            var sortDirection = sortIconNode.hasClass('rn_NotificationSortDesc') ? 'asc' : 'desc',
                sortColumn = sortIconNode.get('parentNode').getAttribute('id').replace(this.baseDomID + '_', ''),
                eventObject = new RightNow.Event.EventObject(this, {data: {
                    direction: sortDirection,
                    sortColumn: sortColumn.trim(),
                    titleLength: this.data.attrs.max_wordbreak_trunc,
                    maxRecords: this.data.attrs.rows_to_display
                }});
            this._setLoading(true);
            RightNow.Ajax.makeRequest(this.data.attrs.sort_notification_ajax, eventObject.data, {
                successHandler: function(response, args){
                    if(response.failure) {
                        RightNow.UI.displayBanner(RightNow.Interface.ASTRgetMessage(response.failure), { type: 'ERROR', focus: true });
                    }
                    else {
                        var notificationList = this.Y.one(this.baseSelector + '_Body');
                        notificationList.get('childNodes').remove();
                        notificationList.append(new EJS({text: this.getStatic().templates.view}).render({
                            subscriptionList: response,
                            fields: this.data.js.fields,
                            target: this.data.attrs.target,
                            widgetInstanceID: this.baseDomID,
                            answerUrl: this.data.js.answerUrl
                        }));
                        if(sortIconNode.hasClass('yui3-datatable-sort-indicator')) {
                            sortIconNode.removeClass('yui3-datatable-sort-indicator');
                            this.Y.all('.rn_NotificationSortDesc').toggleClass('yui3-datatable-sort-indicator').removeClass('rn_NotificationSortDesc');
                            this.Y.all('.rn_NotificationSortAsc').toggleClass('yui3-datatable-sort-indicator').removeClass('rn_NotificationSortAsc');
                        }
                        sortIconNode.hasClass('rn_NotificationSortDesc') ? sortIconNode.replaceClass ('rn_NotificationSortDesc', 'rn_NotificationSortAsc' ) : sortIconNode.replaceClass ('rn_NotificationSortAsc', 'rn_NotificationSortDesc' );
                    }
                    this._setLoading(false);
                },
                failureHandler: this._displayErrorOnFailure,
                json: true, scope: this
            });
        }
    },

    /**
    * changes the loading icon and hides/unhide the data
    * @param {Bool} loading
    */
    _setLoading: function(loading) {
        var toOpacity = 1,
            method = 'removeClass';
        if(loading) {
            //keep height to prevent collapsing behavior
            this._contentDiv.setStyle('height', this._contentDiv.get('offsetHeight') + 'px');
            toOpacity = 0;
            method = 'addClass';
        }
        this._contentDiv.transition({
            opacity: toOpacity,
            duration: 0.4
        });
        this._loadingDiv[method]('rn_Loading');
    },
        /**
    * This function adds the class rn_OkcsLoading to the dom dom_id_loading_icon.
    */
    _showPageLoading: function(){
        this._ajaxCallInProgress = true;
    },

    /**
    * This function removes the class rn_OkcsLoading from the dom dom_id_loading_icon.
    */
    _hidePageLoading: function(){
        this._ajaxCallInProgress = false;
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